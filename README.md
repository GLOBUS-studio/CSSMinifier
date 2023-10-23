# CSSMinifier

CSSMinifier is a compact, simple, and experimental tool crafted in PHP, designed for minifying CSS files. It strategically removes all unnecessary characters from your CSS code without compromising its functionality. The result is faster style loading, reduced bandwidth consumption, and consequently, enhanced user experience and SEO performance.

## Features

**Simplicity**: Removes comments, whitespaces, and other non-essential characters.
**Compatibility**: Functions with CSS3 and earlier CSS versions.
**Ease of Use**: Easily integrates into any PHP project with minimal setup.

## Installation

To employ CSSMinifier in your project, simply copy and paste the CSSMinifier class code from the CSSMinifier.php file into your PHP project. Alternatively, you can clone the entire repository or add it as a submodule to an existing Git project using the following command:
```bash
git clone https://github.com/yourusername/CSSMinifier.git
```

## Usage
```php
require_once 'path_to/CSSMinifier.php';
$minifier = new CSSMinifier();
$originalCss = file_get_contents('path_to_your_original.css'); // Replace with the path to your original CSS file
$minifiedCss = $minifier->minify($originalCss);

// To save the minified CSS
file_put_contents('path_to_your_minified.css', $minifiedCss); // Replace with the path where you wish to save the minified CSS file
```

## Contribution

Suggestions and improvements are welcome! If you have ideas on how to enhance the CSSMinifier, feel free to open an issue or submit a pull request.

## License

CSSMinifier is open-source software licensed under the MIT license.

