<?php

require_once(__DIR__ . '/../../../config.php');
header("Content-Type: text/css");

function get_theme_mode_css(): string {
    global $CFG, $_GET;
    $mode = $_GET['mode'];

    switch ($mode) {
        case "dark":
            echo ".mode-setting {mode: $mode}";
            $core = new core_scss();
            $core->set_file($CFG->dirroot . '/theme/moove/scss/moove/modes/_dark.scss');
            return $core->to_css();
        default:
            return "";
    }

}

function get_modified_css_content($css, $exposeClassName)
{

    // Save values
    $class = extractCSSClass($css, $exposeClassName);
    $root = extractCSSClass($css, ":root");
    $classProps = extractPropertiesFromClass($class);

    // Temp remove (so it isn't affected by variable replacement)
    $css = str_ireplace($class, "", $css);
    $css = str_ireplace($root, "", $css);

    // Do variable replacement
    foreach ($classProps as $key => $value) {
        $css = str_ireplace($value, "var(--$key)", $css);
    }

    // Return temp-removed items
    return $class . $root . $css;
}

function extractCSSClass($css, $className): string
{
    preg_match("/" . preg_quote($className) ."\s*\{[^}]*?\}/i", $css, $match);
    return $match[0];
}

function extractPropertiesFromClass($cssClassString): array
{

    // Match the contents between the braces
    preg_match('/\{([^}]*)\}/', $cssClassString, $matches);

    // Check if the match was successful
    if (!isset($matches[1])) {
        return [];
    }

    $cssClassContent = $matches[1];

    // split by semicolon to get each property-value pair
    $properties = explode(';', $cssClassContent);

    $propertiesArray = [];

    foreach ($properties as $property) {
        // trim to remove leading/trailing white space
        $property = trim($property);

        // skip if this is an empty string
        if (empty($property)) {
            continue;
        }

        // split by colon to separate property and value
        list($prop, $value) = explode(':', $property);

        // trim and add to array
        $propertiesArray[trim($prop)] = trim($value);
    }

    return $propertiesArray;
}

function get_css_content(): string {
    global $PAGE;

    $css = $PAGE->theme->get_css_content();

    $output = get_modified_css_content($css, ".sass-var-expose");
    $output .= get_theme_mode_css();

    return $output;
}

echo get_css_content();


