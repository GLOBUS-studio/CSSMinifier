<?php

/**
 * Class CSSMinifier
 *
 * A utility class that provides an implementation for minifying CSS files.
 * 
 * @version 1.2.0
 * @author GLOBUS Studio
 * @license MIT
 */
class CSSMinifier
{
    /**
     * @var string Version of the minifier
     */
    public const VERSION = '1.2.0';

    /**
     * @var array Configuration options
     */
    private array $options = [
        'preserve_important_comments' => true,
        'remove_charset' => false,
        'compress_colors' => true,
        'compress_font_weight' => true,
    ];

    /**
     * @var array Strings to preserve during minification
     */
    private array $preservedStrings = [];

    /**
     * @var array Preserved at-rules
     */
    private array $preservedAtRules = [];

    /**
     * CSSMinifier constructor.
     *
     * @param array $options Configuration options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Minify the provided CSS string.
     *
     * @param string $css The original CSS string.
     *
     * @return string The minified CSS string.
     * @throws InvalidArgumentException If the input is not a string.
     */
    public function minify(string $css): string
    {
        if (empty($css)) {
            return '';
        }

        // Check file size limit (default 5MB)
        $maxSize = 5 * 1024 * 1024;
        if (strlen($css) > $maxSize) {
            trigger_error("CSS file is very large (" . strlen($css) . " bytes). Performance may be affected.", E_USER_WARNING);
        }

        // Preserve @charset if needed
        $charset = '';
        if (!$this->options['remove_charset']) {
            if (preg_match('/^@charset\s+["\'][^"\']+["\']\s*;/i', $css, $matches)) {
                $charset = $matches[0];
                $css = substr($css, strlen($charset));
            }
        }

        // Preserve media queries and other at-rules
        $css = $this->preserveAtRules($css);

        // Preserve strings with content that should not be minified
        $css = $this->preserveStrings($css);

        // Preserve calc() and other CSS functions (improved for nested functions)
        $css = $this->preserveFunctions($css);

        // Preserve important comments (/*! ... */)
        $importantComments = [];
        if ($this->options['preserve_important_comments']) {
            $css = preg_replace_callback('/\/\*![\s\S]*?\*\//', function($matches) use (&$importantComments) {
                $placeholder = '___IMPORTANT_COMMENT_' . count($importantComments) . '___';
                $importantComments[$placeholder] = $matches[0];
                return $placeholder;
            }, $css);
        }

        // Normalize whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove comments (except important ones already preserved)
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);

        // Remove space after , : ; { } */ > but not in preserved content
        $css = preg_replace('/(,|:|;|\{|}|\*\/|>) /', '$1', $css);

        // Remove space before , ; { } ) > but keep space before ( for functions
        $css = preg_replace('/ (,|;|\{|}|\)|>)/', '$1', $css);
        
        // Handle space before ( carefully - keep for functions like calc, var, etc.
        // Remove space before ( only if it's not a function
        $css = preg_replace('/([^a-z0-9_-]) \(/', '$1(', $css);

        // Remove ; before }
        $css = preg_replace('/;(?=\s*})/', '', $css);

        // Strips leading 0 on decimal values (converts 0.5px into .5px)
        $css = preg_replace('/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css);

        // Strips units if value is 0 (improved to avoid breaking in calc)
        $css = preg_replace('/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc|deg|rad|grad|ms|s|hz|khz|rem|vw|vh|vmin|vmax|fr)(?![a-z])/i', '${1}0', $css);

        // Compress colors
        if ($this->options['compress_colors']) {
            $css = $this->compressColors($css);
        }

        // Compress font-weight
        if ($this->options['compress_font_weight']) {
            $css = str_replace(['font-weight:normal', 'font-weight:bold'], ['font-weight:400', 'font-weight:700'], $css);
        }

        // Restore preserved content in correct order
        $css = $this->restorePreservedContent($css);
        
        // Restore at-rules
        if (!empty($this->preservedAtRules)) {
            $css = str_replace(array_keys($this->preservedAtRules), array_values($this->preservedAtRules), $css);
            $this->preservedAtRules = [];
        }

        // Restore important comments
        if (!empty($importantComments)) {
            $css = str_replace(array_keys($importantComments), array_values($importantComments), $css);
        }

        // Trim and add charset back
        $css = trim($css);
        
        return $charset . $css;
    }

    /**
     * Preserve at-rules during minification.
     *
     * @param string $css CSS content
     * @return string CSS with preserved at-rules
     */
    private function preserveAtRules(string $css): string
    {
        // Preserve @media, @supports, @container, @layer, @keyframes
        $atRules = ['media', 'supports', 'container', 'layer', 'keyframes', '-webkit-keyframes', '-moz-keyframes'];
        
        foreach ($atRules as $rule) {
            // Improved pattern to handle deeply nested braces
            $css = $this->preserveAtRuleRecursive($css, $rule);
        }

        return $css;
    }

    /**
     * Recursively preserve at-rule with nested braces.
     *
     * @param string $css CSS content
     * @param string $rule At-rule name
     * @return string CSS with preserved at-rule
     */
    private function preserveAtRuleRecursive(string $css, string $rule): string
    {
        $pattern = '/@' . preg_quote($rule, '/') . '\s+[^{]+\{/is';
        
        while (preg_match($pattern, $css, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $openPos = $start + strlen($matches[0][0]) - 1;
            
            // Find matching closing brace, accounting for strings
            $depth = 1;
            $pos = $openPos + 1;
            $length = strlen($css);
            $inString = false;
            $stringChar = '';
            $escaped = false;
            
            while ($pos < $length && $depth > 0) {
                $char = $css[$pos];
                
                // Handle escape sequences
                if ($escaped) {
                    $escaped = false;
                    $pos++;
                    continue;
                }
                
                if ($char === '\\') {
                    $escaped = true;
                    $pos++;
                    continue;
                }
                
                // Handle strings
                if (!$inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    $inString = false;
                    $stringChar = '';
                }
                
                // Only count braces outside of strings
                if (!$inString) {
                    if ($char === '{') {
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;
                    }
                }
                
                $pos++;
            }
            
            if ($depth === 0) {
                $atRuleBlock = substr($css, $start, $pos - $start);
                $placeholder = '___ATRULE_' . count($this->preservedAtRules) . '___';
                $this->preservedAtRules[$placeholder] = $atRuleBlock;
                $css = substr_replace($css, $placeholder, $start, $pos - $start);
            } else {
                break; // Unbalanced braces, stop processing
            }
        }
        
        return $css;
    }

    /**
     * Preserve string values during minification.
     *
     * @param string $css CSS content
     * @return string CSS with preserved strings
     */
    private function preserveStrings(string $css): string
    {
        // Preserve double-quoted strings
        $css = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/', function($matches) {
            $placeholder = '___STRING_' . count($this->preservedStrings) . '___';
            $this->preservedStrings[$placeholder] = $matches[0];
            return $placeholder;
        }, $css);

        // Preserve single-quoted strings
        $css = preg_replace_callback("/'(?:[^'\\\\]|\\\\.)*'/", function($matches) {
            $placeholder = '___STRING_' . count($this->preservedStrings) . '___';
            $this->preservedStrings[$placeholder] = $matches[0];
            return $placeholder;
        }, $css);

        return $css;
    }

    /**
     * Preserve CSS functions during minification (improved for nested functions).
     *
     * @param string $css CSS content
     * @return string CSS with preserved functions
     */
    private function preserveFunctions(string $css): string
    {
        // First, preserve url() separately as it can contain unquoted strings
        $css = preg_replace_callback('/url\s*\(([^)]*)\)/i', function($matches) {
            $placeholder = '___URL_' . count($this->preservedStrings) . '___';
            $this->preservedStrings[$placeholder] = $matches[0];
            return $placeholder;
        }, $css);

        // Preserve calc(), clamp(), min(), max(), var(), etc. with proper nesting support
        $functions = ['calc', 'clamp', 'min', 'max', 'var', 'env', 'attr', 'rgba', 'hsla', 'rgb', 'hsl', 'color-mix', 'linear-gradient', 'radial-gradient'];
        
        foreach ($functions as $func) {
            // Improved pattern to handle nested parentheses correctly
            $depth = 0;
            $maxDepth = 10; // Prevent infinite loops
            
            do {
                $prevCss = $css;
                $pattern = '/' . preg_quote($func, '/') . '\s*\(([^()]*)\)/i';
                $css = preg_replace_callback($pattern, function($matches) {
                    $placeholder = '___FUNC_' . count($this->preservedStrings) . '___';
                    // Preserve the entire function with its original spacing
                    $this->preservedStrings[$placeholder] = $matches[0];
                    return $placeholder;
                }, $css);
                $depth++;
            } while ($prevCss !== $css && $depth < $maxDepth);
        }

        return $css;
    }

    /**
     * Compress color values.
     *
     * @param string $css CSS content
     * @return string CSS with compressed colors
     */
    private function compressColors(string $css): string
    {
        // Convert simple rgb(255,255,255) to hex (only if no alpha)
        $css = preg_replace_callback('/\brgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i', function($matches) {
            $r = min(255, max(0, (int)$matches[1]));
            $g = min(255, max(0, (int)$matches[2]));
            $b = min(255, max(0, (int)$matches[3]));
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }, $css);

        // Compress hex colors #aabbcc to #abc
        $css = preg_replace('/#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3\b/i', '#$1$2$3', $css);

        // Compress named colors to shorter hex where beneficial
        $colorMap = [
            'white' => '#fff',
            'black' => '#000',
        ];
        
        foreach ($colorMap as $name => $hex) {
            $css = preg_replace('/\b' . $name . '\b/i', $hex, $css);
        }

        return $css;
    }

    /**
     * Restore preserved content.
     *
     * @param string $css CSS content
     * @return string CSS with restored content
     */
    private function restorePreservedContent(string $css): string
    {
        if (empty($this->preservedStrings)) {
            return $css;
        }

        // Restore in reverse order to handle nested placeholders
        $placeholders = array_keys($this->preservedStrings);
        rsort($placeholders);
        
        foreach ($placeholders as $placeholder) {
            $css = str_replace($placeholder, $this->preservedStrings[$placeholder], $css);
        }

        $this->preservedStrings = [];
        
        return $css;
    }

    /**
     * Minify a CSS file and save the result.
     *
     * @param string $inputFile Path to the input CSS file.
     * @param string|null $outputFile Path to the output CSS file. If null, overwrites the input file.
     *
     * @return bool True on success, false on failure.
     * @throws RuntimeException If file operations fail.
     */
    public function minifyFile(string $inputFile, ?string $outputFile = null): bool
    {
        if (!file_exists($inputFile)) {
            throw new RuntimeException("Input file does not exist: {$inputFile}");
        }

        if (!is_readable($inputFile)) {
            throw new RuntimeException("Input file is not readable: {$inputFile}");
        }

        $css = file_get_contents($inputFile);
        if ($css === false) {
            throw new RuntimeException("Failed to read input file: {$inputFile}");
        }

        $minified = $this->minify($css);

        $outputFile = $outputFile ?? $inputFile;
        
        $result = file_put_contents($outputFile, $minified);
        if ($result === false) {
            throw new RuntimeException("Failed to write output file: {$outputFile}");
        }

        return true;
    }

    /**
     * Minify multiple CSS files at once.
     *
     * @param array $files Array of input file paths
     * @param string|null $outputDir Output directory (if null, files are overwritten)
     * @param string $suffix Suffix for minified files (default: '.min')
     * 
     * @return array Results array with success/failure for each file
     */
    public function minifyFiles(array $files, ?string $outputDir = null, string $suffix = '.min'): array
    {
        $results = [];
        
        foreach ($files as $inputFile) {
            if (!file_exists($inputFile)) {
                $results[$inputFile] = [
                    'success' => false,
                    'error' => 'File does not exist'
                ];
                continue;
            }
            
            try {
                $pathInfo = pathinfo($inputFile);
                
                if ($outputDir !== null) {
                    $outputFile = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 
                                 $pathInfo['filename'] . $suffix . '.' . $pathInfo['extension'];
                } else {
                    $outputFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 
                                 $pathInfo['filename'] . $suffix . '.' . $pathInfo['extension'];
                }
                
                $this->minifyFile($inputFile, $outputFile);
                
                $originalSize = filesize($inputFile);
                $minifiedSize = filesize($outputFile);
                
                $results[$inputFile] = [
                    'success' => true,
                    'output' => $outputFile,
                    'original_size' => $originalSize,
                    'minified_size' => $minifiedSize,
                    'savings_percent' => round((($originalSize - $minifiedSize) / $originalSize) * 100, 2)
                ];
            } catch (Exception $e) {
                $results[$inputFile] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Minify CSS from string and return with statistics.
     *
     * @param string $css Original CSS string
     * @return array Array with 'minified' CSS and 'stats'
     */
    public function minifyWithStats(string $css): array
    {
        $minified = $this->minify($css);
        $stats = $this->getStats($css, $minified);
        
        return [
            'minified' => $minified,
            'stats' => $stats,
            'valid' => $this->validate($minified)
        ];
    }

    /**
     * Get version of CSSMinifier.
     *
     * @return string Version string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Check if CSS contains specific features.
     *
     * @param string $css CSS content
     * @return array Array of detected features
     */
    public function detectFeatures(string $css): array
    {
        $features = [
            'css_variables' => (bool)preg_match('/--[a-zA-Z0-9-]+/', $css),
            'calc_function' => (bool)preg_match('/calc\s*\(/', $css),
            'grid' => (bool)preg_match('/grid-template|display:\s*grid/', $css),
            'flexbox' => (bool)preg_match('/display:\s*flex|flex-direction/', $css),
            'media_queries' => (bool)preg_match('/@media/', $css),
            'keyframes' => (bool)preg_match('/@keyframes/', $css),
            'important_comments' => (bool)preg_match('/\/\*!/', $css),
            'gradients' => (bool)preg_match('/linear-gradient|radial-gradient/', $css),
            'transforms' => (bool)preg_match('/transform:/', $css),
            'animations' => (bool)preg_match('/animation:/', $css)
        ];
        
        return $features;
    }

    /**
     * Get statistics about the minification.
     *
     * @param string $original Original CSS string.
     * @param string $minified Minified CSS string.
     *
     * @return array Statistics array with original size, minified size, and savings.
     */
    public function getStats(string $original, string $minified): array
    {
        $originalSize = strlen($original);
        $minifiedSize = strlen($minified);
        $savings = $originalSize - $minifiedSize;
        $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100, 2) : 0;

        return [
            'original_size' => $originalSize,
            'minified_size' => $minifiedSize,
            'savings_bytes' => $savings,
            'savings_percent' => $savingsPercent,
        ];
    }

    /**
     * Validate minified CSS (enhanced check).
     *
     * @param string $css Minified CSS
     * @return bool True if valid, false otherwise
     */
    public function validate(string $css): bool
    {
        // Check for balanced braces
        if (!$this->checkBalancedCharacters($css, '{', '}')) {
            return false;
        }

        // Check for balanced parentheses
        if (!$this->checkBalancedCharacters($css, '(', ')')) {
            return false;
        }

        // Check for balanced brackets
        if (!$this->checkBalancedCharacters($css, '[', ']')) {
            return false;
        }

        // Check for unclosed strings with proper escape handling
        if (!$this->validateStrings($css)) {
            return false;
        }

        return true;
    }

    /**
     * Check if characters are balanced, ignoring those inside strings.
     *
     * @param string $css CSS content
     * @param string $openChar Opening character
     * @param string $closeChar Closing character
     * @return bool True if balanced
     */
    private function checkBalancedCharacters(string $css, string $openChar, string $closeChar): bool
    {
        $depth = 0;
        $length = strlen($css);
        $inString = false;
        $stringChar = '';
        $escaped = false;
        
        for ($i = 0; $i < $length; $i++) {
            $char = $css[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            
            // Handle strings
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
            
            // Only count characters outside of strings
            if (!$inString) {
                if ($char === $openChar) {
                    $depth++;
                } elseif ($char === $closeChar) {
                    $depth--;
                    if ($depth < 0) {
                        return false; // More closing than opening
                    }
                }
            }
        }
        
        return $depth === 0;
    }

    /**
     * Validate strings with proper escape handling.
     *
     * @param string $css CSS content
     * @return bool True if all strings are properly closed
     */
    private function validateStrings(string $css): bool
    {
        $length = strlen($css);
        $inString = false;
        $stringChar = '';
        $escaped = false;
        
        for ($i = 0; $i < $length; $i++) {
            $char = $css[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            
            // Handle string delimiters
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
        }
        
        return !$inString; // String should be closed at the end
    }

    /**
     * Get detailed validation report.
     *
     * @param string $css CSS to validate
     * @return array Validation report
     */
    public function getValidationReport(string $css): array
    {
        $report = [
            'valid' => true,
            'errors' => []
        ];

        // Check braces
        if (!$this->checkBalancedCharacters($css, '{', '}')) {
            $report['valid'] = false;
            $openBraces = $this->countCharactersOutsideStrings($css, '{');
            $closeBraces = $this->countCharactersOutsideStrings($css, '}');
            $report['errors'][] = "Unbalanced braces: {$openBraces} open, {$closeBraces} close";
        }

        // Check parentheses
        if (!$this->checkBalancedCharacters($css, '(', ')')) {
            $report['valid'] = false;
            $openParens = $this->countCharactersOutsideStrings($css, '(');
            $closeParens = $this->countCharactersOutsideStrings($css, ')');
            $report['errors'][] = "Unbalanced parentheses: {$openParens} open, {$closeParens} close";
        }

        // Check brackets
        if (!$this->checkBalancedCharacters($css, '[', ']')) {
            $report['valid'] = false;
            $openBrackets = $this->countCharactersOutsideStrings($css, '[');
            $closeBrackets = $this->countCharactersOutsideStrings($css, ']');
            $report['errors'][] = "Unbalanced brackets: {$openBrackets} open, {$closeBrackets} close";
        }

        // Check for unclosed strings
        if (!$this->validateStrings($css)) {
            $report['valid'] = false;
            $report['errors'][] = "Unclosed string detected";
        }

        return $report;
    }

    /**
     * Count specific characters outside of strings.
     *
     * @param string $css CSS content
     * @param string $searchChar Character to count
     * @return int Count of characters
     */
    private function countCharactersOutsideStrings(string $css, string $searchChar): int
    {
        $count = 0;
        $length = strlen($css);
        $inString = false;
        $stringChar = '';
        $escaped = false;
        
        for ($i = 0; $i < $length; $i++) {
            $char = $css[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            
            // Handle strings
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
            
            // Count only outside of strings
            if (!$inString && $char === $searchChar) {
                $count++;
            }
        }
        
        return $count;
    }
}