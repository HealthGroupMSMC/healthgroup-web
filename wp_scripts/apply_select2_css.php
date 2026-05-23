<?php
$marker = '/* === HG Select2 fix v1 === */';
$css_add = <<<'CSS'
/* === HG Select2 fix v1 === */
.wpforms-container .select2-container .select2-selection--single {
    height: auto !important;
    min-height: 40px !important;
    padding: 6px 10px !important;
    border: 1px solid #ccc !important;
    background-color: #FFFFFF !important;
}
.wpforms-container .select2-container .select2-selection__rendered {
    color: #1B3A5C !important;
    line-height: 28px !important;
    padding-left: 0 !important;
}
.wpforms-container .select2-container .select2-selection__arrow {
    height: 38px !important;
}
.select2-dropdown {
    background-color: #FFFFFF !important;
    border: 1px solid #ccc !important;
    z-index: 99999 !important;
}
.select2-results__option {
    color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    padding: 8px 12px !important;
}
.select2-results__option--highlighted {
    background-color: #5EBA9E !important;
    color: #FFFFFF !important;
}
.select2-results__option[aria-selected="true"] {
    background-color: #EDEEEC !important;
    color: #1B3A5C !important;
}
.select2-search__field {
    color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    border: 1px solid #ccc !important;
    padding: 6px !important;
}
CSS;

$current = wp_get_custom_css();
if (strpos($current, $marker) !== false) {
    echo "CSS Select2 fix ya aplicado, skip.\n";
    return;
}
$new = $current . "\n\n" . $css_add;
$post = wp_update_custom_css_post($new);
if (is_wp_error($post)) {
    echo "ERROR: " . $post->get_error_message() . "\n";
} else {
    echo "CSS Select2 fix aplicado. Post ID: " . $post->ID . " (total len: " . strlen($new) . " chars)\n";
}
