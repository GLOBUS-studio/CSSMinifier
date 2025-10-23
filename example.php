<?php

require_once 'CSSMinifier.php';

echo "=== CSSMinifier v1.2.0 Examples ===\n\n";
echo "Version: " . (new CSSMinifier())->getVersion() . "\n\n";

// Example 1: Basic minification with stats
echo "--- Example 1: Basic Minification ---\n";
$css1 = "
/* Main styles */
body {
    margin: 0px;
    padding: 0.5em;
    background-color: #ffffff;
    font-weight: bold;
}

.container {
    width: 100%;
    color: rgb(255, 0, 0);
}
";

$minifier = new CSSMinifier();
$result1 = $minifier->minifyWithStats($css1);

echo "Original ({$result1['stats']['original_size']} bytes):\n{$css1}\n";
echo "Minified ({$result1['stats']['minified_size']} bytes):\n{$result1['minified']}\n";
echo "Savings: {$result1['stats']['savings_percent']}%\n";
echo "Valid: " . ($result1['valid'] ? 'Yes' : 'No') . "\n\n";

// Example 2: Modern CSS features
echo "--- Example 2: Modern CSS Features ---\n";
$css2 = "
:root {
    --primary-color: #3498db;
    --spacing: 1rem;
}

.element {
    width: calc(100% - var(--spacing));
    background: linear-gradient(45deg, var(--primary-color), #2ecc71);
    transform: translateX(10px);
}

@media (min-width: 768px) {
    .responsive { display: flex; }
}
";

$minified2 = $minifier->minify($css2);
$features2 = $minifier->detectFeatures($css2);

echo "Detected features:\n";
foreach ($features2 as $feature => $detected) {
    echo ($detected ? '✓' : '✗') . " {$feature}\n";
}
echo "\nMinified:\n{$minified2}\n\n";

// Example 3: Important comments preservation
echo "--- Example 3: Important Comments ---\n";
$css3 = "
/*! 
 * Theme: MyTheme v1.0
 * License: MIT
 */
/* Regular comment - will be removed */
.header { 
    color: #aabbcc;
    font-weight: normal;
}
";

$minifier3 = new CSSMinifier(['preserve_important_comments' => true]);
$minified3 = $minifier3->minify($css3);

echo "Original:\n{$css3}\n";
echo "Minified (comment preserved):\n{$minified3}\n\n";

// Example 4: Validation with error detection
echo "--- Example 4: Validation ---\n";
$validCSS = ".test { color: #fff; }";
$invalidCSS = ".test { color: #fff"; // Missing closing brace

echo "Valid CSS: " . ($minifier->validate($validCSS) ? '✓ PASS' : '✗ FAIL') . "\n";
echo "Invalid CSS: " . ($minifier->validate($invalidCSS) ? '✗ FAIL' : '✓ PASS (correctly detected)') . "\n";

$report = $minifier->getValidationReport($invalidCSS);
if (!$report['valid']) {
    echo "Errors found: " . implode(', ', $report['errors']) . "\n";
}
echo "\n";

// Example 5: Complex nested structures
echo "--- Example 5: Complex Nesting ---\n";
$css5 = "
@media (min-width: 768px) {
    .container {
        width: calc(100% - min(50px, 10%));
    }
    
    @supports (display: grid) {
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
    }
}
";

$minified5 = $minifier->minify($css5);
$valid5 = $minifier->validate($minified5);

echo "Original CSS:\n{$css5}\n";
echo "Minified:\n{$minified5}\n";
echo "Validation: " . ($valid5 ? '✓ PASSED' : '✗ FAILED') . "\n\n";

// Example 6: Batch processing simulation
echo "--- Example 6: Batch Processing (simulated) ---\n";
echo "To process multiple files:\n";
echo "\$files = ['style1.css', 'style2.css', 'style3.css'];\n";
echo "\$results = \$minifier->minifyFiles(\$files);\n";
echo "\nThis would minify all files and create .min.css versions\n\n";

echo "=== All Examples Completed ===\n";
