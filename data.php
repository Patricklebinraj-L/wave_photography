<?php
require_once __DIR__.'/includes/bootstrap.php';
header('Content-Type: application/javascript; charset=utf-8');header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');
$photos=db()->query("SELECT id,title,category,image,thumbnail AS thumb,caption,alt_text,featured,aspect_ratio AS ratio,is_placeholder AS placeholder FROM portfolio_photos WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$services=db()->query("SELECT slug AS s,name AS n,category AS c,image AS i,description AS d,inclusions FROM services WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
foreach($services as &$s){$s['inc']=json_decode($s['inclusions']??'[]',true)?:[];unset($s['inclusions']);}unset($s);
$testimonials=db()->query("SELECT customer_name AS name,category AS cat,review AS text,is_sample,rating,customer_image FROM testimonials WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$settings=db()->query("SELECT setting_key,setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$content=db()->query("SELECT content_key,content_value FROM website_content")->fetchAll(PDO::FETCH_KEY_PAIR);
$theme=db()->query("SELECT setting_key,setting_value FROM theme_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$allowedFonts=['Inter','Arial','Georgia','Poppins','Roboto','Montserrat'];if(!in_array($theme['font_family']??'Inter',$allowedFonts,true))$theme['font_family']='Inter';
foreach(['container_width','section_spacing','border_radius'] as $numeric){$theme[$numeric]=(string)max(0,min(2400,(int)($theme[$numeric]??0)));}
$navigation=db()->query("SELECT label,url,open_new_tab FROM navigation_items WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$sections=db()->query("SELECT name,slug,category,description,cover_image,layout FROM website_sections WHERE is_active=1 AND deleted_at IS NULL ORDER BY sort_order,id")->fetchAll();
$encode=function($v){return json_encode($v,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);};
echo 'window.PHOTOS='.$encode($photos).';';
echo 'window.SERVICES='.$encode($services).';';
echo 'window.TESTI='.$encode($testimonials).';';
echo 'window.NAVIGATION='.$encode($navigation).';';
echo 'window.WAVE_SECTIONS='.$encode($sections).';';
echo 'window.WAVE_CONTENT='.$encode($content).';';
echo 'window.WAVE_THEME='.$encode($theme).';';
echo 'window.WHY='.$encode(['Creative Photography','Personalized Themes','Attention to Details','Beautiful Photo Albums','Professional Editing','Customer-Focused Experience','Memorable Moments','Customized Sessions']).';';
echo 'window.WA='.$encode($settings['whatsapp']??'916379700521').';';
echo 'window.WAVE_SETTINGS='.$encode($settings).';';
echo '(function(){var t=window.WAVE_THEME||{},m={primary_color:"--primary-color",secondary_color:"--secondary-color",accent_color:"--accent-color",page_background:"--page-background",section_background:"--section-background",card_background:"--card-background",header_background:"--header-background",footer_background:"--footer-background",heading_color:"--heading-color",text_color:"--text-color",nav_text_color:"--nav-text-color",button_background:"--button-background",button_text_color:"--button-text-color",font_family:"--font-family",container_width:"--container-width",section_spacing:"--section-spacing",border_radius:"--border-radius"};Object.keys(m).forEach(function(k){if(t[k])document.documentElement.style.setProperty(m[k],/^(container_width|section_spacing|border_radius)$/.test(k)?t[k]+"px":t[k]);});})();';
