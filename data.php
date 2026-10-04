<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/css_sanitizer.php';
header('Content-Type: application/javascript; charset=utf-8');header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');
$photos=db()->query("SELECT id,title,category,image,thumbnail AS thumb,caption,alt_text,featured,aspect_ratio AS ratio,is_placeholder AS placeholder FROM portfolio_photos WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
$services=db()->query("SELECT slug AS s,name AS n,category AS c,image AS i,description AS d,inclusions FROM services WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
foreach($services as &$s){$s['inc']=json_decode($s['inclusions']??'[]',true)?:[];unset($s['inclusions']);}unset($s);
$testimonials=db()->query("SELECT customer_name AS name,category AS cat,review AS text,is_sample,rating,customer_image FROM testimonials WHERE is_active=1 AND is_sample=0 ORDER BY sort_order,id")->fetchAll();
$settings=db()->query("SELECT setting_key,setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$content=db()->query("SELECT content_key,content_value FROM website_content")->fetchAll(PDO::FETCH_KEY_PAIR);
$extendedTheme = ['logo_background'=>'#ffffff','logo_padding'=>'10','logo_radius'=>'16','logo_width'=>'220','custom_button_target'=>'load_more','custom_button_css'=>'',
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
  'overlay_menu_hover_color' => '#20b99a',
  'header_shortcut_text_color'=>'#173b49','header_shortcut_hover_text_color'=>'#ffffff','header_shortcut_hover_background'=>'#20b99a','header_shortcut_active_text_color'=>'#ffffff','header_shortcut_active_background'=>'#1c5664','header_menu_text_color'=>'#173b49','header_menu_background'=>'transparent','header_menu_border_color'=>'#20b99a'
];
$extendedTheme=array_merge($extendedTheme,['hero_gradient_enabled'=>'0','hero_gradient_start'=>'#eaf8f5','hero_gradient_end'=>'#1c5664','hero_gradient_angle'=>'135','header_gradient_enabled'=>'0','header_gradient_start'=>'#1c5664','header_gradient_end'=>'#073b49','header_gradient_angle'=>'135','footer_gradient_enabled'=>'0','footer_gradient_start'=>'#073b49','footer_gradient_end'=>'#0b4556','footer_gradient_angle'=>'135','page_gradient_enabled'=>'0','page_gradient_start'=>'#f5fbfa','page_gradient_end'=>'#eaf8f5','page_gradient_angle'=>'135','section_gradient_enabled'=>'0','section_gradient_start'=>'#f5fbfa','section_gradient_end'=>'#eaf8f5','section_gradient_angle'=>'135','light_section_gradient_enabled'=>'0','light_section_gradient_start'=>'#ffffff','light_section_gradient_end'=>'#eaf8f5','light_section_gradient_angle'=>'135','dark_section_gradient_enabled'=>'0','dark_section_gradient_start'=>'#073b49','dark_section_gradient_end'=>'#0b4556','dark_section_gradient_angle'=>'135','alt_section_gradient_enabled'=>'0','alt_section_gradient_start'=>'#eaf8f5','alt_section_gradient_end'=>'#ffffff','alt_section_gradient_angle'=>'135','cta_section_gradient_enabled'=>'0','cta_section_gradient_start'=>'#1c5664','cta_section_gradient_end'=>'#073b49','cta_section_gradient_angle'=>'135','card_gradient_enabled'=>'0','card_gradient_start'=>'#ffffff','card_gradient_end'=>'#eaf8f5','card_gradient_angle'=>'135','button_gradient_enabled'=>'0','button_gradient_start'=>'#20b99a','button_gradient_end'=>'#0b6f82','button_gradient_angle'=>'135','overlay_menu_gradient_enabled'=>'0','overlay_menu_gradient_start'=>'#073b49','overlay_menu_gradient_end'=>'#1c5664','overlay_menu_gradient_angle'=>'135','form_gradient_enabled'=>'0','form_gradient_start'=>'#ffffff','form_gradient_end'=>'#eaf8f5','form_gradient_angle'=>'135']);
$themeSeed=db()->prepare('INSERT IGNORE INTO theme_settings(setting_key,setting_value) VALUES(?,?)');foreach($extendedTheme as $tk=>$tv)$themeSeed->execute([$tk,$tv]);
foreach(wave_logo_navigation_theme_defaults() as $tk=>$tv)$themeSeed->execute([$tk,$tv]);
$theme=db()->query("SELECT setting_key,setting_value FROM theme_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
if(($theme['hero_heading_color']??'')==='#123342'){$theme['hero_heading_color']='#ffffff';$pdoFix=db()->prepare("UPDATE theme_settings SET setting_value='#ffffff' WHERE setting_key='hero_heading_color' AND setting_value='#123342'");$pdoFix->execute();}
$allowedFonts=['Inter','Arial','Georgia','Poppins','Roboto','Montserrat'];if(!in_array($theme['font_family']??'Inter',$allowedFonts,true))$theme['font_family']='Inter';
foreach(['container_width','section_spacing','border_radius'] as $numeric){$theme[$numeric]=(string)max(0,min(2400,(int)($theme[$numeric]??0)));}
$navigation=[];
try{
 $navigation=db()->query("SELECT label,url,open_new_tab,placement,text_color,background_color,hover_text_color,hover_background_color,border_radius,font_size,font_weight,text_transform,custom_css FROM navigation_items WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
}catch(Throwable $navError){
 error_log('[Wave navigation] '.$navError->getMessage());
 $navigation=db()->query("SELECT label,url,open_new_tab FROM navigation_items WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
}
$navHex=function($v){return is_string($v)&&preg_match('/^#[0-9a-fA-F]{6}$/',$v)?strtolower($v):'';};
$navInt=function($v,$min,$max){if($v===null||$v==='')return null;$n=(int)$v;return($n<$min||$n>$max)?null:$n;};
foreach($navigation as &$navItem){
 $navItem['placement']=in_array($navItem['placement']??'both',['both','header','mobile'],true)?$navItem['placement']:'both';
 foreach(['text_color','background_color','hover_text_color','hover_background_color'] as $navColorKey)$navItem[$navColorKey]=$navHex($navItem[$navColorKey]??'');
 $navItem['border_radius']=$navInt($navItem['border_radius']??null,0,80);
 $navItem['font_size']=$navInt($navItem['font_size']??null,8,48);
 $navItem['font_weight']=in_array((string)($navItem['font_weight']??''),['400','500','600','700','800'],true)?(string)$navItem['font_weight']:null;
 $navItem['text_transform']=in_array((string)($navItem['text_transform']??''),['none','uppercase','capitalize','lowercase'],true)?(string)$navItem['text_transform']:null;
 $navCss=wave_sanitize_css_declarations($navItem['custom_css']??'',wave_nav_css_properties(),600);
 $navItem['custom_css']=$navCss===''?'':$navCss.' !important';
}
unset($navItem);
$navFlag=function($key,$default='1')use($theme){return (string)($theme[$key]??$default)==='1';};
$navSettings=[
 'visible'=>$navFlag('navbar_visible'),
 'mobileVisible'=>$navFlag('navbar_mobile_visible'),
 'label'=>(string)($theme['navbar_label']??'Quick navigation'),
 'bookVisible'=>$navFlag('navbar_book_button_visible'),
 'bookLabel'=>trim((string)($theme['navbar_book_button_label']??'')),
 'bookUrl'=>trim((string)($theme['navbar_book_button_url']??''))?:'contact.php',
 'menuVisible'=>$navFlag('navbar_menu_button_visible'),
 'menuLabel'=>trim((string)($theme['navbar_menu_button_label']??''))?:'Menu',
];
$sections=db()->query("SELECT name,slug,category,description,cover_image,layout FROM website_sections WHERE is_active=1 AND deleted_at IS NULL ORDER BY sort_order,id")->fetchAll();
$encode=function($v){return json_encode($v,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);};
echo 'window.PHOTOS='.$encode($photos).';';
echo 'window.SERVICES='.$encode($services).';';
echo 'window.TESTI='.$encode($testimonials).';';
echo 'window.NAVIGATION='.$encode($navigation).';';
echo 'window.WAVE_NAV='.$encode($navSettings).';';
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
 overlay_menu_hover_color:["--overlay-menu-hover-color"],
 header_shortcut_text_color:["--header-shortcut-text"],header_shortcut_hover_text_color:["--header-shortcut-hover-text"],header_shortcut_hover_background:["--header-shortcut-hover-bg"],header_shortcut_active_text_color:["--header-shortcut-active-text"],header_shortcut_active_background:["--header-shortcut-active-bg"],header_menu_text_color:["--header-menu-text"],header_menu_background:["--header-menu-background"],header_menu_border_color:["--header-menu-border"]
};

Object.keys(map).forEach(function(k){var v=t[k];if(v!==undefined&&v!==null&&String(v)!==""){map[k].forEach(function(name){s.setProperty(name,String(v));});}});
var gradientTargets = {
"hero":{solid:"--hero-background",enabled:"hero_gradient_enabled",start:"hero_gradient_start",end:"hero_gradient_end",angle:"hero_gradient_angle"},"header":{solid:"--header-background",enabled:"header_gradient_enabled",start:"header_gradient_start",end:"header_gradient_end",angle:"header_gradient_angle"},"footer":{solid:"--footer-background",enabled:"footer_gradient_enabled",start:"footer_gradient_start",end:"footer_gradient_end",angle:"footer_gradient_angle"},"page":{solid:"--page-background",enabled:"page_gradient_enabled",start:"page_gradient_start",end:"page_gradient_end",angle:"page_gradient_angle"},"section":{solid:"--default-section-background",enabled:"section_gradient_enabled",start:"section_gradient_start",end:"section_gradient_end",angle:"section_gradient_angle"},"light_section":{solid:"--light-section-background",enabled:"light_section_gradient_enabled",start:"light_section_gradient_start",end:"light_section_gradient_end",angle:"light_section_gradient_angle"},"dark_section":{solid:"--dark-section-background",enabled:"dark_section_gradient_enabled",start:"dark_section_gradient_start",end:"dark_section_gradient_end",angle:"dark_section_gradient_angle"},"alt_section":{solid:"--alt-section-background",enabled:"alt_section_gradient_enabled",start:"alt_section_gradient_start",end:"alt_section_gradient_end",angle:"alt_section_gradient_angle"},"cta_section":{solid:"--cta-section-background",enabled:"cta_section_gradient_enabled",start:"cta_section_gradient_start",end:"cta_section_gradient_end",angle:"cta_section_gradient_angle"},"card":{solid:"--card-background",enabled:"card_gradient_enabled",start:"card_gradient_start",end:"card_gradient_end",angle:"card_gradient_angle"},"button":{solid:"--button-background",enabled:"button_gradient_enabled",start:"button_gradient_start",end:"button_gradient_end",angle:"button_gradient_angle"},"overlay_menu":{solid:"--overlay-menu-background",enabled:"overlay_menu_gradient_enabled",start:"overlay_menu_gradient_start",end:"overlay_menu_gradient_end",angle:"overlay_menu_gradient_angle"},"form":{solid:"--form-background",enabled:"form_gradient_enabled",start:"form_gradient_start",end:"form_gradient_end",angle:"form_gradient_angle"}
};
Object.keys(gradientTargets).forEach(function(name){
 var g=gradientTargets[name],enabled=String(t[g.enabled]||"0")==="1";
 var start=/^#[0-9a-f]{6}$/i.test(t[g.start]||"")?t[g.start]:"#ffffff";
 var end=/^#[0-9a-f]{6}$/i.test(t[g.end]||"")?t[g.end]:"#eaf8f5";
 var angle=Math.max(0,Math.min(360,parseInt(t[g.angle]||135,10)||135));
 var paint=enabled?"linear-gradient("+angle+"deg,"+start+","+end+")":"var("+g.solid+")";
 s.setProperty("--"+name.replace(/_/g,"-")+"-background-paint",paint);
});

var width=Math.max(760,Math.min(1800,parseInt(t.container_width||1200,10)||1200));
var spacing=Math.max(32,Math.min(180,parseInt(t.section_spacing||80,10)||80));
var radius=Math.max(0,Math.min(48,parseInt(t.border_radius||18,10)||18));
s.setProperty("--container-width",width+"px");
s.setProperty("--section-spacing",spacing+"px");
s.setProperty("--border-radius",radius+"px");
var hexOrNull=function(v){var m=/^#([0-9a-f]{6})$/i.exec(String(v==null?"":v).trim());return m?"#"+m[1].toLowerCase():null;};
var clampNum=function(v,min,max,fallback){var n=parseInt(v,10);if(isNaN(n))return fallback;return Math.max(min,Math.min(max,n));};
var oneOf=function(v,list,fallback){v=String(v==null?"":v);return list.indexOf(v)!==-1?v:fallback;};
var rgbaFrom=function(hex,opacityPercent){var o=Math.max(0,Math.min(100,opacityPercent))/100;var m=/^#([0-9a-f]{6})$/i.exec(String(hex||""));if(!m)return "rgba(0,0,0,"+o+")";var h=m[1];return "rgba("+parseInt(h.substr(0,2),16)+","+parseInt(h.substr(2,2),16)+","+parseInt(h.substr(4,2),16)+","+o+")";};

/* ---- Logo surface: transparent background, border and box shadow ---- */
var logoPadding=Math.max(0,Math.min(60,parseInt(t.logo_padding||10,10)||0)),logoRadius=Math.max(0,Math.min(60,parseInt(t.logo_radius||16,10)||0)),logoWidth=Math.max(80,Math.min(520,parseInt(t.logo_width||220,10)||220));
var logoTransparent=String(t.logo_background_transparent||"0")==="1";
var logoBg=logoTransparent?"transparent":(hexOrNull(t.logo_background)||"#ffffff");
var logoBorderStyle=oneOf(t.logo_border_style,["solid","dashed","dotted","double","none"],"solid");
var logoBorderWidth=clampNum(t.logo_border_width,0,24,1);
var logoBorderColor=hexOrNull(t.logo_border_color)||"#ffffff";
var logoBorder=(String(t.logo_border_enabled||"1")==="1"&&logoBorderWidth>0&&logoBorderStyle!=="none")?logoBorderWidth+"px "+logoBorderStyle+" "+logoBorderColor:"none";
var logoShadowEnabled=String(t.logo_box_shadow_enabled||"1")==="1";
var logoShadowColor=rgbaFrom(t.logo_box_shadow_color||"#000000",clampNum(t.logo_box_shadow_opacity,0,100,12));
var logoShadow=logoShadowEnabled?[clampNum(t.logo_box_shadow_x,-60,60,0),clampNum(t.logo_box_shadow_y,-60,60,5),clampNum(t.logo_box_shadow_blur,0,120,18),clampNum(t.logo_box_shadow_spread,-60,60,0)].map(function(v){return v+"px";}).join(" ")+" "+logoShadowColor:"none";
s.setProperty("--logo-background",logoBg);s.setProperty("--logo-padding",logoPadding+"px");s.setProperty("--logo-radius",logoRadius+"px");s.setProperty("--logo-width",logoWidth+"px");
s.setProperty("--logo-border",logoBorder);s.setProperty("--logo-border-color",logoBorderColor);s.setProperty("--logo-border-width",logoBorderWidth+"px");s.setProperty("--logo-border-style",logoBorderStyle);s.setProperty("--logo-box-shadow",logoShadow);
function applyWaveLogoAppearance(){document.querySelectorAll(".brand-lockup, footer .logo, footer .brand-lockup, #ld .logo").forEach(function(el){el.style.setProperty("background-color",logoBg,"important");el.style.setProperty("background-image","none","important");el.style.setProperty("padding",logoPadding+"px","important");el.style.setProperty("border-radius",logoRadius+"px","important");el.style.setProperty("border",logoBorder,"important");el.style.setProperty("box-shadow",logoShadow,"important");el.style.setProperty("box-sizing","border-box","important");});document.querySelectorAll(".brand-lockup img, footer .logo img, footer .brand-lockup img, #ld .logo img").forEach(function(img){img.style.setProperty("width",logoWidth+"px","important");img.style.setProperty("max-width","100%","important");img.style.setProperty("height","auto","important");img.style.setProperty("object-fit","contain","important");});}
applyWaveLogoAppearance();
if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",applyWaveLogoAppearance,{once:true});}
if(window.MutationObserver&&!window.__waveLogoObserver){window.__waveLogoObserver=new MutationObserver(function(){applyWaveLogoAppearance();});window.__waveLogoObserver.observe(document.documentElement,{childList:true,subtree:true});}

/* ---- Header navigation bar: layout, typography and link states ---- */
var navBackground=String(t.navbar_background_transparent||"1")==="1"?"transparent":(hexOrNull(t.navbar_background)||"#1c5664");
var navAlign=({left:"flex-start",center:"center",right:"flex-end"})[oneOf(t.navbar_alignment,["left","center","right"],"center")]||"center";
var navRadius=clampNum(t.navbar_border_radius,0,80,999);
var navBorderStyle=oneOf(t.navbar_border_style,["solid","dashed","dotted","double","none"],"solid");
var navBorderWidth=clampNum(t.navbar_border_width,0,12,0);
var navBorderColor=hexOrNull(t.navbar_border_color)||"#20b99a";
var navBorder=navBorderWidth>0&&navBorderStyle!=="none"?navBorderWidth+"px "+navBorderStyle+" "+navBorderColor:"none";
var navShadowEnabled=String(t.navbar_box_shadow_enabled||"0")==="1";
var navShadowColor=rgbaFrom(t.navbar_box_shadow_color||"#000000",clampNum(t.navbar_box_shadow_opacity,0,100,18));
var navShadow=navShadowEnabled?[clampNum(t.navbar_box_shadow_x,-60,60,0),clampNum(t.navbar_box_shadow_y,-60,60,4),clampNum(t.navbar_box_shadow_blur,0,120,16),clampNum(t.navbar_box_shadow_spread,-60,60,0)].map(function(v){return v+"px";}).join(" ")+" "+navShadowColor:"none";
var navLinkText=hexOrNull(t.navbar_link_text_color)||"#173b49";
var navHoverText=hexOrNull(t.navbar_hover_text_color)||"#ffffff";
var navHoverBg=hexOrNull(t.navbar_hover_background_color)||"#20b99a";
var navActiveStyle=oneOf(t.navbar_active_style,["pill","underline","solid","none"],"pill");
var navActiveText=hexOrNull(t.navbar_active_text_color)||"#ffffff";
var navActiveBg=hexOrNull(t.navbar_active_background_color)||"#1c5664";
var navActiveBackground=navActiveStyle==="none"?"transparent":navActiveBg;
var navActiveShadow=navActiveStyle==="underline"?"inset 0 -3px 0 "+navActiveBg:"none";
var navActiveRadius=navActiveStyle==="solid"?"0px":navRadius+"px";
var navWeight=oneOf(t.navbar_font_weight,["400","500","600","700","800"],"700");
var navTransform=oneOf(t.navbar_text_transform,["none","uppercase","capitalize","lowercase"],"none");
var navPadding=String(t.navbar_link_padding||"").trim();
if(!/^\d{1,3}px(\s+\d{1,3}px){0,3}$/.test(navPadding))navPadding="9px 14px";
s.setProperty("--navbar-alignment",navAlign);
s.setProperty("--navbar-background",navBackground);
s.setProperty("--navbar-radius",navRadius+"px");
s.setProperty("--navbar-border",navBorder);
s.setProperty("--navbar-border-color",navBorderColor);
s.setProperty("--navbar-border-width",navBorderWidth+"px");
s.setProperty("--navbar-border-style",navBorderStyle);
s.setProperty("--navbar-box-shadow",navShadow);
s.setProperty("--navbar-font-size",clampNum(t.navbar_font_size,9,40,15)+"px");
s.setProperty("--navbar-font-weight",navWeight);
s.setProperty("--navbar-text-transform",navTransform);
s.setProperty("--navbar-letter-spacing",clampNum(t.navbar_letter_spacing,-5,10,0)+"px");
s.setProperty("--navbar-gap",clampNum(t.navbar_gap,0,80,18)+"px");
s.setProperty("--navbar-link-padding",navPadding);
s.setProperty("--navbar-link-text",navLinkText);
s.setProperty("--navbar-hover-text",navHoverText);
s.setProperty("--navbar-hover-bg",navHoverBg);
s.setProperty("--navbar-active-text",navActiveText);
s.setProperty("--navbar-active-bg",navActiveBackground);
s.setProperty("--navbar-active-shadow",navActiveShadow);
s.setProperty("--navbar-active-radius",navActiveRadius);
s.setProperty("--navbar-hover-decoration",String(t.navbar_hover_underline||"0")==="1"?"underline":"none");

var buttonTargets={load_more:".load-more",explore_service:".explore-service-btn",header_booking:"header .hd>a.btn",hero_primary:".hero .hero-actions>a.btn:not(.o)",hero_secondary:".hero .hero-actions>a.btn.o",menu_button:"header #bg",all_buttons:".btn,.explore-service-btn,.load-more"},buttonTarget=buttonTargets[t.custom_button_target]||buttonTargets.load_more,buttonCss=String(t.custom_button_css||"");
if(buttonCss&&/^[a-zA-Z0-9#(),.%\s\/+\-":;]+$/.test(buttonCss)&&!/(url\s*\(|expression|javascript|@import|[{}<>])/i.test(buttonCss)){var customStyle=document.createElement("style");customStyle.id="wave-admin-button-style";customStyle.textContent=buttonTarget+"{"+buttonCss+"}";document.head.appendChild(customStyle);}
s.setProperty("--line","color-mix(in srgb, "+(t.text_color||"#344f59")+" 18%, transparent)");
s.setProperty("--shadow","0 18px 48px color-mix(in srgb, "+(t.primary_color||"#0b6f82")+" 14%, transparent)");
var imageMap={hero_background_image:"--hero-background-image",default_section_background_image:"--default-section-background-image",footer_background_image:"--footer-background-image"};
Object.keys(imageMap).forEach(function(k){var v=String(t[k]||"").trim();if(v&&/^(https?:\/\/|\/|assets\/)/i.test(v)&&!/[()\\\\]/.test(v)){s.setProperty(imageMap[k],"url(\""+v+"\")");}});

}catch(e){if(window.console&&console.warn)console.warn("Wave theme configuration could not be applied",e);}})();';
