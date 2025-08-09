<?php
/**
 * Simple JavaScript syntax checker for the admin users index page
 */

$file = __DIR__ . '/resources/views/admin/users/index.blade.php';
$content = file_get_contents($file);

// Extract JavaScript content between <script> tags
preg_match('/<script>(.*?)<\/script>/s', $content, $matches);

if (empty($matches[1])) {
    echo "No JavaScript found in the file.\n";
    exit(1);
}

$js_content = $matches[1];

// Write temporary JS file for syntax checking
$temp_file = tempnam(sys_get_temp_dir(), 'js_check_') . '.js';
file_put_contents($temp_file, $js_content);

echo "JavaScript content extracted and saved to: $temp_file\n";
echo "\n=== JavaScript Content ===\n";

// Show the JavaScript content with line numbers
$lines = explode("\n", $js_content);
foreach ($lines as $i => $line) {
    $line_num = str_pad($i + 1, 3, ' ', STR_PAD_LEFT);
    echo "$line_num: $line\n";
}

echo "\n=== Potential Issues Found ===\n";

// Check for common JavaScript syntax issues
$issues = [];

// Check for unclosed functions
$function_opens = substr_count($js_content, 'function');
$function_closes = substr_count($js_content, '}');
if ($function_opens > 0) {
    echo "Functions found: $function_opens\n";
}

// Check for unmatched braces
$open_braces = substr_count($js_content, '{');
$close_braces = substr_count($js_content, '}');
echo "Open braces: $open_braces\n";
echo "Close braces: $close_braces\n";
if ($open_braces !== $close_braces) {
    echo "❌ ISSUE: Unmatched braces! Open: $open_braces, Close: $close_braces\n";
}

// Check for unmatched parentheses
$open_parens = substr_count($js_content, '(');
$close_parens = substr_count($js_content, ')');
echo "Open parentheses: $open_parens\n";
echo "Close parentheses: $close_parens\n";
if ($open_parens !== $close_parens) {
    echo "❌ ISSUE: Unmatched parentheses! Open: $open_parens, Close: $close_parens\n";
}

// Check for potential syntax issues
if (strpos($js_content, 'catch (error) {') !== false) {
    echo "✅ Error handling found\n";
}

// Check for Alpine.js compatibility
if (strpos($js_content, 'return {') !== false) {
    echo "✅ Alpine.js component structure found\n";
}

// Look for duplicate function definitions
if (preg_match_all('/(\w+)\s*\(.*?\)\s*{/', $js_content, $function_matches)) {
    $function_names = $function_matches[1];
    $function_counts = array_count_values($function_names);
    foreach ($function_counts as $func_name => $count) {
        if ($count > 1) {
            echo "❌ ISSUE: Duplicate function '$func_name' found $count times\n";
        }
    }
}

echo "\n=== Analysis Complete ===\n";

// Clean up
unlink($temp_file);
