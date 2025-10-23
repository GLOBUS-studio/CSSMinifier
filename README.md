# CSSMinifier

CSSMinifier is a compact, simple, and experimental tool crafted in PHP, designed for minifying CSS files. It strategically removes all unnecessary characters from your CSS code without compromising its functionality. The result is faster style loading, reduced bandwidth consumption, and consequently, enhanced user experience and SEO performance.

## Features

- **Simplicity**: Removes comments, whitespaces, and other non-essential characters
- **Compatibility**: Functions with CSS3 and earlier CSS versions
- **Ease of Use**: Easily integrates into any PHP project with minimal setup
- **Flexible Configuration**: Customizable minification options
- **Color Compression**: Converts and compresses color values (#aabbcc → #abc, rgb() → hex)
- **Font Weight Optimization**: Converts named font-weights to numeric values
- **Important Comments Preservation**: Keeps /*! */ comments for licenses
- **File Operations**: Direct file minification support with batch processing
- **Statistics**: Get detailed compression statistics
- **Validation**: Built-in CSS validation to ensure output integrity
- **Feature Detection**: Detect modern CSS features in your code

## Requirements

- PHP 8.0 or higher
- No external dependencies

## Installation

### Option 1: Direct Include
```bash
git clone https://github.com/GLOBUS-studio/CSSMinifier.git
```

### Option 2: Copy Single File
Download `CSSMinifier.php` and include it in your project:
```php
require_once 'path/to/CSSMinifier.php';
```

## Usage Examples

### 1. Basic Minification

```php
require_once 'CSSMinifier.php';

$minifier = new CSSMinifier();
$css = file_get_contents('style.css');
$minified = $minifier->minify($css);

file_put_contents('style.min.css', $minified);
```

### 2. Minification with Statistics

```php
$minifier = new CSSMinifier();
$css = file_get_contents('style.css');

$result = $minifier->minifyWithStats($css);

echo "Minified CSS: " . $result['minified'] . "\n";
echo "Original: {$result['stats']['original_size']} bytes\n";
echo "Minified: {$result['stats']['minified_size']} bytes\n";
echo "Savings: {$result['stats']['savings_percent']}%\n";
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
```

### 3. Batch File Processing

```php
$minifier = new CSSMinifier();

$files = [
    'css/main.css',
    'css/components.css',
    'css/layout.css'
];

// Minify all files and save with .min.css extension
$results = $minifier->minifyFiles($files);

foreach ($results as $file => $result) {
    if ($result['success']) {
        echo "✓ {$file} → {$result['output']} ({$result['savings_percent']}% saved)\n";
    } else {
        echo "✗ {$file}: {$result['error']}\n";
    }
}
```

### 4. Custom Configuration

```php
$minifier = new CSSMinifier([
    'preserve_important_comments' => true,   // Keep /*! */ comments
    'remove_charset' => false,               // Keep @charset
    'compress_colors' => true,               // Compress colors
    'compress_font_weight' => true           // Optimize font-weight
]);

$minified = $minifier->minify($css);
```

### 5. Validation and Error Checking

```php
$minifier = new CSSMinifier();
$minified = $minifier->minify($css);

// Quick validation
if (!$minifier->validate($minified)) {
    echo "Warning: Minified CSS may have issues!\n";
    
    // Get detailed report
    $report = $minifier->getValidationReport($minified);
    foreach ($report['errors'] as $error) {
        echo "- {$error}\n";
    }
}
```

### 6. Feature Detection

```php
$minifier = new CSSMinifier();
$css = file_get_contents('style.css');

$features = $minifier->detectFeatures($css);

echo "Detected CSS features:\n";
foreach ($features as $feature => $detected) {
    if ($detected) {
        echo "- {$feature}\n";
    }
}
```

### 7. Direct File Minification

```php
$minifier = new CSSMinifier();

try {
    // Minify single file
    $minifier->minifyFile('input.css', 'output.min.css');
    echo "✓ Minification successful!\n";
} catch (RuntimeException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `preserve_important_comments` | bool | `true` | Keep comments marked with `/*! ... */` |
| `remove_charset` | bool | `false` | Remove `@charset` declarations |
| `compress_colors` | bool | `true` | Compress hex colors and convert rgb() to hex |
| `compress_font_weight` | bool | `true` | Convert `normal`/`bold` to `400`/`700` |

## Modern CSS Support

### ✅ Fully Supported:
- **CSS Variables**: `--custom-property`, `var(--name)`
- **CSS Functions**: `calc()`, `clamp()`, `min()`, `max()`
- **Color Functions**: `rgb()`, `rgba()`, `hsl()`, `hsla()`
- **Layout**: Grid, Flexbox properties
- **Media Queries**: `@media` with complex conditions
- **At-rules**: `@supports`, `@layer`, `@container`, `@keyframes`
- **Pseudo-elements**: `::before`, `::after`, `:hover`, etc.
- **Nested Functions**: `calc(100% - min(20px, 5vw))`
- **Gradients**: `linear-gradient()`, `radial-gradient()`
- **Transforms & Animations**: All standard properties

### ⚠️ Partially Supported:
- **Multi-line values**: `grid-template-areas` (verify output)
- **Deep nesting**: Functions nested >10 levels
- **URL with special chars**: May need testing

### ❌ Not Supported:
- **Source maps**: Not generated
- **Preprocessors**: SCSS, LESS, Stylus syntax
- **Modern color spaces**: `oklch()`, `lab()`, `lch()`
- **CSS-in-JS**: Runtime-generated styles

## API Reference

### Methods

#### `minify(string $css): string`
Minify CSS string and return minified result.

#### `minifyFile(string $input, ?string $output = null): bool`
Minify a single file. If `$output` is null, overwrites input file.

#### `minifyFiles(array $files, ?string $outputDir = null, string $suffix = '.min'): array`
Batch minify multiple files. Returns array with results for each file.

#### `minifyWithStats(string $css): array`
Minify and return array with minified CSS, stats, and validation result.

#### `validate(string $css): bool`
Quick validation check. Returns `true` if CSS appears valid.

#### `getValidationReport(string $css): array`
Detailed validation with error messages.

#### `getStats(string $original, string $minified): array`
Get size statistics comparing original and minified CSS.

#### `detectFeatures(string $css): array`
Detect modern CSS features used in the code.

#### `getVersion(): string`
Get CSSMinifier version.

## Production Checklist

### ✅ Safe for Production:
- Small websites (< 50 pages)
- Landing pages and portfolios
- Personal blogs
- Prototypes and MVPs
- Internal tools

### ⚠️ Use with Caution:
- Medium sites (test thoroughly first)
- Sites with complex CSS frameworks
- Projects with aggressive deadlines

### ❌ Not Recommended:
- E-commerce platforms
- Banking/Financial applications
- Large corporate sites (> 100 pages)
- Mission-critical systems

## Performance

- **Speed**: ~0.1-0.5s for typical CSS files (< 100KB)
- **Memory**: Minimal overhead, handles files up to 5MB
- **Compression**: Average 30-50% file size reduction

## Error Handling

```php
try {
    $minifier = new CSSMinifier();
    $minified = $minifier->minify($css);
    
    if (!$minifier->validate($minified)) {
        throw new Exception("Validation failed");
    }
    
    file_put_contents('output.min.css', $minified);
} catch (RuntimeException $e) {
    error_log("Minification error: " . $e->getMessage());
    // Fallback: use original CSS
    file_put_contents('output.min.css', $css);
}
```

## Known Limitations

1. **File Size**: Warning triggered for files > 5MB
2. **Nesting Depth**: Functions nested > 10 levels may not preserve correctly
3. **Exotic Features**: Cutting-edge CSS features may need verification
4. **No Optimization**: Doesn't optimize selectors or remove duplicates

## Comparison

| Feature | CSSMinifier | cssnano | clean-css |
|---------|-------------|---------|-----------|
| **Language** | PHP | JavaScript | JavaScript |
| **Size** | ~15KB | ~500KB | ~200KB |
| **Dependencies** | 0 | 20+ | 5+ |
| **Speed** | Fast | Very Fast | Fast |
| **Modern CSS** | ✅ Good | ✅ Excellent | ✅ Excellent |
| **Source Maps** | ❌ | ✅ | ✅ |
| **Advanced Opts** | Basic | Extensive | Extensive |
| **Best For** | PHP projects | Build tools | Node.js apps |

## Troubleshooting

### Issue: Validation fails after minification
**Solution**: Check for unclosed strings or unbalanced braces in original CSS.

### Issue: Output looks wrong
**Solution**: Use `detectFeatures()` to see if exotic features are used.

### Issue: Performance is slow
**Solution**: File may be too large (> 1MB). Consider splitting CSS.

## Contributing

Contributions welcome! Please:
1. Test your changes thoroughly
2. Update documentation
3. Add examples for new features
4. Follow existing code style

## License

MIT License - free to use in personal and commercial projects.

## Version History

- **1.2.0** (Current): 
  - Improved validation with escape sequence handling
  - Enhanced at-rules processing for nested structures
  - Better error reporting
  - Performance optimizations
  - Added feature detection
  - Fixed issues with strings in at-rules

- **1.0.0**: Initial stable release with full modern CSS support

## Support

- 🐛 Issues: [GitHub Issues](https://github.com/GLOBUS-studio/CSSMinifier/issues)
- 📖 Docs: [Documentation](https://github.com/GLOBUS-studio/CSSMinifier)
- 💬 Discussions: [GitHub Discussions](https://github.com/GLOBUS-studio/CSSMinifier/discussions)

---

**Made with ❤️ by GLOBUS Studio**

