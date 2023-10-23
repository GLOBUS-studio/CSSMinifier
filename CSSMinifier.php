<?php

/**
 * Class CSSMinifier
 *
 * A utility class that provides an implementation for minifying CSS files.
 */
class CSSMinifier
{
    /**
     * Minify the provided CSS string.
     *
     * @param string $css The original CSS string.
     *
     * @return string The minified CSS string.
     */
    public function minify(string $css): string
    {
        // Normalize whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove spaces before and after comment
        $css = preg_replace('/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css);

        // Remove comment blocks, everything between /* and */, unless preserved with /*! ... */ or /** ... */
        $css = preg_replace('~/\*(?![\!|\*])(.*?)\*/~', '', $css);

        // Remove ; before }
        $css = preg_replace('/;(?=\s*})/', '', $css);

        // Remove space after , : ; { } */ >
        $css = preg_replace('/(,|:|;|\{|}|\*\/|>) /', '$1', $css);

        // Remove space before , ; { } ( ) >
        $css = preg_replace('/ (,|;|\{|}|\(|\)|>)/', '$1', $css);

        // Strips leading 0 on decimal values (converts 0.5px into .5px)
        $css = preg_replace('/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css);

        // Strips units if value is 0 (converts 0px to 0)
        $css = preg_replace('/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc|deg|rad|grad|ms|s|hz|khz|rem|vw|vh|vmin|vmax|fr)/i', '${1}0', $css);

        // Trim
        $css = trim($css);

        return $css;
    }
}