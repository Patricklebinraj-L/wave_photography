<?php
require_once __DIR__.'/includes/bootstrap.php';
header('Content-Type: application/javascript; charset=utf-8');header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');
$photos=db()->query("SELECT id,title,category,image,thumbnail AS thumb,caption,alt_text,featured,aspect_ratio AS ratio,is_placeholder AS placeholder FROM portfolio_photos WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$services=db()->query("SELECT slug AS s,name AS n,category AS c,image AS i,description AS d,inclusions FROM services WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
foreach($services as &$s){$s['inc']=json_decode($s['inclusions']??'[]',true)?:[];unset($s['inclusions']);}unset($s);
$testimonials=db()->query("SELECT customer_name AS name,category AS cat,review AS text,is_sample,rating,customer_image FROM testimonials WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$settings=db()->query("SELECT setting_key,setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$content=db()->query("SELECT content_key,content_value FROM website_content")->fetchAll(PDO::FETCH_KEY_PAIR);
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
$themeSeed=db()->prepare('INSERT IGNORE INTO theme_settings(setting_key,setting_value) VALUES(?,?)');foreach($extendedTheme as $tk=>$tv)$themeSeed->execute([$tk,$tv]);
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
 font_family:["--font-family"],
 hero_background:["--hero-background"],
 hero_text_color:["--hero-text-color"],
 hero_heading_color:["--hero-heading-color"],
 hero_overlay_color:["--hero-overlay-color"],
 default_section_background:["--default-section-background"],
 light_section_background:["--light-section-background"],
 dark_section_background:["--dark-section-background"],
 alt_section_background:["--alt-section-background"],
 cta_section_background:["--cta-section-background"],
 section_heading_color:["--section-heading-color"],
 section_text_color:["--section-text-color"],
 header_text_color:["--header-text-color"],
 header_link_hover_color:["--header-link-hover-color"],
 footer_heading_color:["--footer-heading-color"],
 footer_text_color:["--footer-text-color"],
 footer_link_color:["--footer-link-color"],
 card_heading_color:["--card-heading-color"],
 card_text_color:["--card-text-color"],
 card_border_color:["--card-border-color"],
 button_hover_background:["--button-hover-background"],
 button_hover_text_color:["--button-hover-text-color"],
 form_background:["--form-background"],
 form_text_color:["--form-text-color"],
 form_border_color:["--form-border-color"],
 dark_section_text_color:["--dark-section-text-color"],
 cta_section_text_color:["--cta-section-text-color"],
 header_button_background:["--header-button-background"],
 header_button_text_color:["--header-button-text-color"],
 overlay_menu_background:["--overlay-menu-background"],
 overlay_menu_text_color:["--overlay-menu-text-color"],
 overlay_menu_hover_color:["--overlay-menu-hover-color"]
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
var imageMap={hero_background_image:"--hero-background-image",default_section_background_image:"--default-section-background-image",footer_background_image:"--footer-background-image"};
Object.keys(imageMap).forEach(function(k){var v=String(t[k]||"").trim();if(v&&/^(https?:\/\/|\/|assets\/)/i.test(v)&&!/[()\\\\]/.test(v)){s.setProperty(imageMap[k],"url(\""+v+"\")");}});

}catch(e){if(window.console&&console.warn)console.warn("Wave theme configuration could not be applied",e);}})();';
