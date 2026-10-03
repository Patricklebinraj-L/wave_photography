<?php
/**
 * Reconcile starter content on deployments where an earlier seed was partial.
 * Safe to run once through the migration tracker; individual inserts are idempotent.
 */
return function(PDO $pdo): void {
    $seedPath = dirname(__DIR__) . '/seeds/site.json';
    if (!is_file($seedPath)) {
        throw new RuntimeException('Starter content file is missing: database/seeds/site.json');
    }
    $seed = json_decode((string) file_get_contents($seedPath), true, 512, JSON_THROW_ON_ERROR);

    $service = $pdo->prepare(
        'INSERT INTO services (slug,name,category,image,description,inclusions,sort_order)
         SELECT ?,?,?,?,?,?,?
         WHERE NOT EXISTS (SELECT 1 FROM services WHERE slug = ?)'
    );
    foreach (($seed['services'] ?? []) as $i => $item) {
        $service->execute([
            $item['s'], $item['n'], $item['c'], $item['i'], $item['d'],
            json_encode($item['inc'] ?? [], JSON_UNESCAPED_SLASHES), $i, $item['s']
        ]);
    }

    $photo = $pdo->prepare(
        'INSERT INTO portfolio_photos
         (id,title,category,image,thumbnail,featured,aspect_ratio,is_placeholder,sort_order)
         SELECT ?,?,?,?,?,?,?,?,?
         WHERE NOT EXISTS (
           SELECT 1 FROM portfolio_photos WHERE title = ? AND category = ?
         )'
    );
    foreach (($seed['photos'] ?? []) as $i => $item) {
        $photo->execute([
            $item['id'], $item['title'], $item['category'], $item['image'],
            $item['thumb'] ?? $item['image'], !empty($item['featured']) ? 1 : 0,
            $item['ratio'] ?? null, !empty($item['placeholder']) ? 1 : 0, $i,
            $item['title'], $item['category']
        ]);
    }

    $testimonial = $pdo->prepare(
        'INSERT INTO testimonials (customer_name,category,review,is_sample,sort_order)
         SELECT ?,?,?,1,?
         WHERE NOT EXISTS (
           SELECT 1 FROM testimonials WHERE customer_name = ? AND review = ?
         )'
    );
    foreach (($seed['testimonials'] ?? []) as $i => $item) {
        $testimonial->execute([
            $item['name'], $item['cat'], $item['text'], $i, $item['name'], $item['text']
        ]);
    }

    $setting = $pdo->prepare(
        'INSERT IGNORE INTO site_settings (setting_key,setting_value) VALUES (?,?)'
    );
    foreach ([
        'whatsapp' => $seed['whatsapp'] ?? '916379700521',
        'email' => 'wavephotography24@gmail.com',
        'phone' => '+91 6379700521',
        'instagram' => 'https://www.instagram.com/wave_photography_cj/',
        'studio_name' => 'Wave Photography'
    ] as $key => $value) {
        $setting->execute([$key, $value]);
    }
};
