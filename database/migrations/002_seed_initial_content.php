<?php
return function(PDO $pdo): void {
 $seed=json_decode(file_get_contents(dirname(__DIR__).'/seeds/site.json'),true,512,JSON_THROW_ON_ERROR);
 $count=(int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
 if($count===0){$s=$pdo->prepare('INSERT INTO services(slug,name,category,image,description,inclusions,sort_order) VALUES(?,?,?,?,?,?,?)');foreach($seed['services'] as $i=>$x)$s->execute([$x['s'],$x['n'],$x['c'],$x['i'],$x['d'],json_encode($x['inc']),$i]);}
 $count=(int)$pdo->query('SELECT COUNT(*) FROM portfolio_photos')->fetchColumn();
 if($count===0){$s=$pdo->prepare('INSERT INTO portfolio_photos(id,title,category,image,thumbnail,featured,aspect_ratio,is_placeholder,sort_order) VALUES(?,?,?,?,?,?,?,?,?)');foreach($seed['photos'] as $i=>$x)$s->execute([$x['id'],$x['title'],$x['category'],$x['image'],$x['thumb']??$x['image'],!empty($x['featured'])?1:0,$x['ratio']??null,!empty($x['placeholder'])?1:0,$i]);}
 $count=(int)$pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
 if($count===0){$s=$pdo->prepare('INSERT INTO testimonials(customer_name,category,review,is_sample,sort_order) VALUES(?,?,?,?,?)');foreach($seed['testimonials'] as $i=>$x)$s->execute([$x['name'],$x['cat'],$x['text'],1,$i]);}
 $s=$pdo->prepare('INSERT IGNORE INTO site_settings(setting_key,setting_value) VALUES(?,?)');foreach(['whatsapp'=>$seed['whatsapp'],'email'=>'wavephotography24@gmail.com','phone'=>'+91 6379700521','instagram'=>'https://www.instagram.com/wave_photography_cj/','studio_name'=>'Wave Photography'] as $k=>$v)$s->execute([$k,$v]);
};
