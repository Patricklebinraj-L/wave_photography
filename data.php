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
echo '(function(){try{
var t=window.WAVE_THEME||{},r=document.documentElement,s=r.style;
var map={
 primary_color:["--primary-color","--b"],
 secondary_color:["--secondary-color","--f"],
 accent_color:["--accent-color","--t"],
 page_background:["--page-background","--paper"],
 section_background:["--section-background","--l"],
 card_background:["--card-background"],
 header_background:["--header-background"],
 footer_background:["--footer-background"],
 heading_color:["--heading-color","--ink","--n"],
 text_color:["--text-color","--muted"],
 nav_text_color:["--nav-text-color"],
 button_background:["--button-background"],
 button_text_color:["--button-text-color"],
 font_family:["--font-family"]
};
Object.keys(map).forEach(function(k){var v=t[k];if(v!==undefined&&v!==null&&String(v)!==""){map[k].forEach(function(name){s.setProperty(name,String(v));});}});
var width=Math.max(760,Math.min(1800,parseInt(t.container_width||1200,10)||1200));
var spacing=Math.max(32,Math.min(180,parseInt(t.section_spacing||80,10)||80));
var radius=Math.max(0,Math.min(48,parseInt(t.border_radius||18,10)||18));
s.setProperty("--container-width",width+"px");
s.setProperty("--section-spacing",spacing+"px");
s.setProperty("--border-radius",radius+"px");
s.setProperty("--line","color-mix(in srgb, "+(t.text_color||"#344f59")+" 18%, transparent)");
s.setProperty("--shadow","0 18px 48px color-mix(in srgb, "+(t.primary_color||"#0b6f82")+" 14%, transparent)");
}catch(e){if(window.console&&console.warn)console.warn("Wave theme configuration could not be applied",e);}})();';
