<?php
declare(strict_types=1);

/**
 * Shared, strict CSS declaration sanitizer used by both the admin portal (on save)
 * and the public data feeder (on output) so stored custom CSS can never inject
 * unsafe constructs.
 */
function wave_button_css_properties(): array
{
    return ['background', 'background-color', 'background-image', 'color', 'border', 'border-color', 'border-width', 'border-style', 'border-radius', 'padding', 'padding-inline', 'padding-block', 'min-height', 'min-width', 'font-size', 'font-weight', 'letter-spacing', 'text-transform', 'box-shadow', 'text-decoration', 'display', 'gap', 'opacity', 'transition'];
}

function wave_nav_css_properties(): array
{
    return ['color', 'background-color', 'border-color', 'border-width', 'border-style', 'border-radius', 'padding', 'padding-inline', 'padding-block', 'margin', 'margin-inline', 'font-size', 'font-weight', 'font-style', 'letter-spacing', 'line-height', 'text-transform', 'text-decoration', 'text-shadow', 'box-shadow', 'opacity', 'transition', 'gap', 'min-width', 'white-space'];
}

function wave_sanitize_css_declarations($value, array $allowedProperties, int $maxLength = 2400): string
{
    $clean = [];
    foreach (preg_split('/;/', (string) $value) as $declaration) {
        $declaration = trim($declaration);
        if ($declaration === '') {
            continue;
        }
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $property = strtolower(trim($parts[0]));
        $value = trim($parts[1]);
        if (!in_array($property, $allowedProperties, true)) {
            continue;
        }
        if ($value === '' || strlen($value) > 180) {
            continue;
        }
        if (preg_match('/[{}<>\\\\]|url\s*\(|expression|javascript|@import|behavior|position\s*:|-moz-binding|attr\s*\(/i', $value)) {
            continue;
        }
        if (!preg_match('/^[a-zA-Z0-9#(),.%\s\/+\-\'"]+$/', $value)) {
            continue;
        }
        $clean[] = $property . ': ' . $value;
    }
    return substr(implode('; ', $clean), 0, $maxLength);
}

/**
 * Converts a #rrggbb colour plus a 0-100 opacity into a css rgba() string.
 */
function wave_rgba_from_hex(string $hex, int $opacityPercent): string
{
    $opacity = max(0, min(100, $opacityPercent)) / 100;
    if (!preg_match('/^#([0-9a-fA-F]{6})$/', trim($hex), $m)) {
        return 'rgba(0,0,0,' . $opacity . ')';
    }
    $h = $m[1];
    return 'rgba(' . hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ',' . hexdec(substr($h, 4, 2)) . ',' . $opacity . ')';
}

/**
 * The complete default set of theme settings introduced for the logo surface and
 * the header navigation bar. Shared by the admin theme page, the reset handler and
 * the public data feeder so every entry point stays in sync.
 */
function wave_logo_navigation_theme_defaults(): array
{
    return [
        'logo_background_transparent' => '0',
        'logo_border_enabled' => '1',
        'logo_border_color' => '#ffffff',
        'logo_border_width' => '1',
        'logo_border_style' => 'solid',
        'logo_box_shadow_enabled' => '1',
        'logo_box_shadow_color' => '#000000',
        'logo_box_shadow_opacity' => '12',
        'logo_box_shadow_x' => '0',
        'logo_box_shadow_y' => '5',
        'logo_box_shadow_blur' => '18',
        'logo_box_shadow_spread' => '0',
        'navbar_visible' => '1',
        'navbar_mobile_visible' => '1',
        'navbar_label' => 'Quick navigation',
        'navbar_alignment' => 'center',
        'navbar_font_size' => '15',
        'navbar_font_weight' => '700',
        'navbar_text_transform' => 'none',
        'navbar_letter_spacing' => '0',
        'navbar_gap' => '18',
        'navbar_link_padding' => '9px 14px',
        'navbar_border_radius' => '999',
        'navbar_background' => '#1c5664',
        'navbar_background_transparent' => '1',
        'navbar_link_text_color' => '#173b49',
        'navbar_hover_text_color' => '#ffffff',
        'navbar_hover_background_color' => '#20b99a',
        'navbar_active_style' => 'pill',
        'navbar_active_text_color' => '#ffffff',
        'navbar_active_background_color' => '#1c5664',
        'navbar_hover_underline' => '0',
        'navbar_border_color' => '#20b99a',
        'navbar_border_width' => '0',
        'navbar_border_style' => 'solid',
        'navbar_box_shadow_enabled' => '0',
        'navbar_box_shadow_color' => '#000000',
        'navbar_box_shadow_opacity' => '18',
        'navbar_box_shadow_x' => '0',
        'navbar_box_shadow_y' => '4',
        'navbar_box_shadow_blur' => '16',
        'navbar_box_shadow_spread' => '0',
        'navbar_book_button_visible' => '1',
        'navbar_book_button_label' => '',
        'navbar_book_button_url' => 'contact.php',
        'navbar_menu_button_visible' => '1',
        'navbar_menu_button_label' => 'Menu',
    ];
}
