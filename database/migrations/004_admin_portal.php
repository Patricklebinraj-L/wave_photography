<?php
return function(PDO $pdo): void {
    $sql = [
      "CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(140) NOT NULL DEFAULT 'Administrator',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        failed_attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        last_login_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS website_sections (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        category VARCHAR(100) NOT NULL,
        description TEXT NULL,
        cover_image VARCHAR(500) NULL,
        layout VARCHAR(40) NOT NULL DEFAULT 'gallery',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        deleted_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sections_active_order(is_active,sort_order)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS website_content (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        content_key VARCHAR(160) NOT NULL UNIQUE,
        content_value LONGTEXT NULL,
        content_type VARCHAR(30) NOT NULL DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS navigation_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(100) NOT NULL,
        url VARCHAR(500) NOT NULL,
        parent_id INT UNSIGNED NULL,
        sort_order INT NOT NULL DEFAULT 0,
        open_new_tab TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_nav_parent FOREIGN KEY(parent_id) REFERENCES navigation_items(id) ON DELETE SET NULL,
        INDEX idx_nav_active_order(is_active,sort_order)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS theme_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value VARCHAR(500) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS media_library (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        alt_text VARCHAR(500) NULL,
        uploaded_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_media_created(created_at),
        CONSTRAINT fk_media_admin FOREIGN KEY(uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
      "CREATE TABLE IF NOT EXISTS admin_activity_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id INT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        details VARCHAR(500) NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_activity_created(created_at),
        CONSTRAINT fk_activity_admin FOREIGN KEY(admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($sql as $q) $pdo->exec($q);
    $columns=$pdo->query("SHOW COLUMNS FROM portfolio_photos")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('caption',$columns,true))$pdo->exec("ALTER TABLE portfolio_photos ADD COLUMN caption VARCHAR(500) NULL");
    if(!in_array('alt_text',$columns,true))$pdo->exec("ALTER TABLE portfolio_photos ADD COLUMN alt_text VARCHAR(500) NULL");
    if(!in_array('is_cover',$columns,true))$pdo->exec("ALTER TABLE portfolio_photos ADD COLUMN is_cover TINYINT(1) NOT NULL DEFAULT 0");
    $testimonialColumns=$pdo->query("SHOW COLUMNS FROM testimonials")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('rating',$testimonialColumns,true))$pdo->exec("ALTER TABLE testimonials ADD COLUMN rating TINYINT UNSIGNED NOT NULL DEFAULT 5");
    if(!in_array('customer_image',$testimonialColumns,true))$pdo->exec("ALTER TABLE testimonials ADD COLUMN customer_image VARCHAR(500) NULL");



    $q=$pdo->prepare("SELECT id FROM admin_users WHERE username=? LIMIT 1");
    $q->execute(['alex']);
    if (!$q->fetchColumn()) {
        $ins=$pdo->prepare("INSERT INTO admin_users(username,password_hash,display_name) VALUES(?,?,?)");
        $ins->execute(['alex','$2y$12$P50ZffW1SONGqFB43k7LguOi25Ts7UqFdbWBwehcTlsTd9WALMtcS','Wave Administrator']);
    }

    $theme=[
      'primary_color'=>'#0b6f82','secondary_color'=>'#1c5664','accent_color'=>'#20b99a',
      'page_background'=>'#f5fbfa','section_background'=>'#ffffff','card_background'=>'#ffffff',
      'header_background'=>'#1c5664','footer_background'=>'#073b49',
      'heading_color'=>'#123342','text_color'=>'#344f59','nav_text_color'=>'#ffffff',
      'button_background'=>'#20b99a','button_text_color'=>'#073b49','font_family'=>'Inter',
      'container_width'=>'1200','section_spacing'=>'80','border_radius'=>'18'
    ];
    $ins=$pdo->prepare("INSERT IGNORE INTO theme_settings(setting_key,setting_value) VALUES(?,?)");
    foreach($theme as $k=>$v)$ins->execute([$k,$v]);
    $extendedTheme = [
      'hero_background' => '#eaf8f5',
      'hero_text_color' => '#123342',
      'hero_heading_color' => '#123342',
      'hero_overlay_color' => '#ffffff',
      'default_section_background' => '#f5fbfa',
      'light_section_background' => '#ffffff',
      'dark_section_background' => '#073b49',
      'alt_section_background' => '#eaf8f5',
      'cta_section_background' => '#1c5664',
      'section_heading_color' => '#123342',
      'section_text_color' => '#344f59',
      'header_text_color' => '#ffffff',
      'header_link_hover_color' => '#20b99a',
      'footer_heading_color' => '#ffffff',
      'footer_text_color' => '#d5e6e9',
      'footer_link_color' => '#ffffff',
      'card_heading_color' => '#123342',
      'card_text_color' => '#344f59',
      'card_border_color' => '#dce9e7',
      'button_hover_background' => '#159d83',
      'button_hover_text_color' => '#ffffff',
      'form_background' => '#ffffff',
      'form_text_color' => '#123342',
      'form_border_color' => '#dce9e7',
      'hero_background_image' => '',
      'default_section_background_image' => '',
      'footer_background_image' => '',
      'dark_section_text_color' => '#ffffff',
      'cta_section_text_color' => '#ffffff',
      'header_button_background' => '#20b99a',
      'header_button_text_color' => '#073b49',
      'overlay_menu_background' => '#073b49',
      'overlay_menu_text_color' => '#ffffff',
      'overlay_menu_hover_color' => '#20b99a'
    ];
    foreach($extendedTheme as $k=>$v)$ins->execute([$k,$v]);

    $globalDefaults=['website_name'=>'Wave Photography','tagline'=>'Photography for every beautiful moment','seo_title'=>'Wave Photography | Weddings, Babies & Family','meta_description'=>'Wedding, baby, maternity, birthday and family photography in Tamil Nadu.','keywords'=>'photography, wedding photography, baby photography, Chennai','google_maps_url'=>'','analytics_id'=>'','maintenance_mode'=>'0','website_visibility'=>'1','favicon'=>'assets/logo/wave-photography-favicon.png','logo'=>'assets/logo/wave-photography-primary.png'];
    $insSettings=$pdo->prepare("INSERT IGNORE INTO site_settings(setting_key,setting_value) VALUES(?,?)");foreach($globalDefaults as $k=>$v)$insSettings->execute([$k,$v]);
    $content=[
      'hero_kicker'=>'WAVE PHOTOGRAPHY · CHENNAI',
      'hero_heading'=>'Every Picture Tells a Beautiful Story.',
      'hero_description'=>'Capturing your happiest moments, precious memories, and beautiful celebrations through timeless photography.',
      'about_heading'=>'Behind every photograph is a moment waiting to be remembered.',
      'about_description'=>'At Wave Photography, we believe photography is more than capturing images. It is about preserving emotions, celebrating relationships, and creating memories that last forever.',
      'featured_heading'=>'Featured Portfolio',
      'categories_heading'=>'Photography Categories',
      'why_heading'=>'Why Choose Wave Photography?',
      'contact_heading'=>'Let’s Capture Your Beautiful Moments.',
      'footer_description'=>'Photography for weddings, babies, maternity, birthdays and families in Tamil Nadu.',
      'copyright_text'=>'© 2026 Wave Photography','about_page_heading'=>'About Us','about_section_heading'=>'Our approach','about_page_description'=>'We plan each session around your story, photograph naturally, and edit with care.','services_page_heading'=>'Our Photography Services','portfolio_page_heading'=>'Portfolio','contact_page_heading'=>'Contact & Booking','hero_cta_text'=>'Explore Our Work','hero_cta_url'=>'portfolio.php','booking_cta_text'=>'Book Your Session','hero_image'=>'assets/pre-wedding/dusk-tower.webp','about_image'=>'assets/photographer/sample.svg'
    ];
    $insc=$pdo->prepare("INSERT IGNORE INTO website_content(content_key,content_value,content_type) VALUES(?,?,?)");
    foreach($content as $k=>$v)$insc->execute([$k,$v,'text']);

    $count=(int)$pdo->query("SELECT COUNT(*) FROM navigation_items")->fetchColumn();
    if($count===0){
      $nav=[['Home','index.php'],['About','about.php'],['Portfolio','portfolio.php'],['Services','services.php'],['Contact','contact.php']];
      $ni=$pdo->prepare("INSERT INTO navigation_items(label,url,sort_order) VALUES(?,?,?)");
      foreach($nav as $i=>$n)$ni->execute([$n[0],$n[1],$i]);
    }

    $count=(int)$pdo->query("SELECT COUNT(*) FROM website_sections")->fetchColumn();
    if($count===0){
      $rows=$pdo->query("SELECT name,slug,category,description,image,sort_order,is_active FROM services ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
      $si=$pdo->prepare("INSERT IGNORE INTO website_sections(name,slug,category,description,cover_image,sort_order,is_active) VALUES(?,?,?,?,?,?,?)");
      foreach($rows as $r)$si->execute([$r['name'],$r['slug'],$r['category'],$r['description'],$r['image'],$r['sort_order'],$r['is_active']]);
    }
};
