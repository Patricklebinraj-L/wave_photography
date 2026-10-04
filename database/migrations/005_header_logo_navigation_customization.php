<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    require_once dirname(__DIR__, 2) . '/includes/css_sanitizer.php';

    // Per-navigation-item style columns so each header/menu link can be customised
    // independently of the global navigation bar styling.
    $columns = [
        'placement' => "VARCHAR(20) NOT NULL DEFAULT 'both'",
        'text_color' => 'VARCHAR(30) NULL',
        'background_color' => 'VARCHAR(30) NULL',
        'hover_text_color' => 'VARCHAR(30) NULL',
        'hover_background_color' => 'VARCHAR(30) NULL',
        'border_radius' => 'SMALLINT UNSIGNED NULL',
        'font_size' => 'SMALLINT UNSIGNED NULL',
        'font_weight' => "VARCHAR(10) NULL",
        'text_transform' => 'VARCHAR(20) NULL',
        'custom_css' => 'VARCHAR(1200) NULL',
    ];
    $existing = $pdo->query("SHOW COLUMNS FROM navigation_items")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($existing)) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS navigation_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(100) NOT NULL,
            url VARCHAR(500) NOT NULL,
            parent_id INT UNSIGNED NULL,
            sort_order INT NOT NULL DEFAULT 0,
            open_new_tab TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            placement VARCHAR(20) NOT NULL DEFAULT 'both',
            text_color VARCHAR(30) NULL,
            background_color VARCHAR(30) NULL,
            hover_text_color VARCHAR(30) NULL,
            hover_background_color VARCHAR(30) NULL,
            border_radius SMALLINT UNSIGNED NULL,
            font_size SMALLINT UNSIGNED NULL,
            font_weight VARCHAR(10) NULL,
            text_transform VARCHAR(20) NULL,
            custom_css VARCHAR(1200) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nav_active_order(is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $existing = $pdo->query("SHOW COLUMNS FROM navigation_items")->fetchAll(PDO::FETCH_COLUMN);
    }
    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existing, true)) {
            $pdo->exec("ALTER TABLE navigation_items ADD COLUMN `$name` $definition");
        }
    }

    // Keep every pre-existing menu item visible in both menus after the upgrade.
    $pdo->exec("UPDATE navigation_items SET placement='both' WHERE placement IS NULL OR placement NOT IN ('both','header','mobile')");

    $seed = $pdo->prepare("INSERT IGNORE INTO theme_settings(setting_key,setting_value) VALUES(?,?)");
    foreach (wave_logo_navigation_theme_defaults() as $key => $value) {
        $seed->execute([$key, $value]);
    }
};
