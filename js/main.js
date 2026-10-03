$(function(){
var P=Array.isArray(window.PHOTOS)?window.PHOTOS:[],pg=$('body').data('p'),pages=Array.isArray(window.NAVIGATION)&&window.NAVIGATION.length?window.NAVIGATION.map(function(n){return [n.url.split('?')[0].replace('.php','').replace(/^\//,''),n.label,n.url,n.open_new_tab];}):[["index","Home","index.php"],["about","About","about.php"],["portfolio","Portfolio","portfolio.php"],["services","Services","services.php"],["contact","Contact","contact.php"]];pages.push(['admin','Admin','admin/index.php',false]);
var esc=function(v){return String(v==null?'':v).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c];});};var CONTENT=window.WAVE_CONTENT||{};var SERVICES=Array.isArray(window.SERVICES)?window.SERVICES:[],TESTI=Array.isArray(window.TESTI)?window.TESTI:[],WHY=Array.isArray(window.WHY)?window.WHY:[],WA=window.WA||'916379700521';var IG=(window.WAVE_SETTINGS&&window.WAVE_SETTINGS.instagram)||'https://www.instagram.com/wave_photography_cj/',wa='https://wa.me/'+WA;
if(window.WAVE_SETTINGS){var ws=window.WAVE_SETTINGS;if(ws.logo){$('.brand-lockup img,footer .logo img').attr('src',ws.logo);}if(ws.favicon)$('link[rel="icon"]').attr('href',ws.favicon);if(ws.website_name){document.title=document.title.replace('Wave Photography',ws.website_name);}if(ws.seo_title)document.title=ws.seo_title;if(ws.meta_description)$('meta[name="description"]').attr('content',ws.meta_description);}
if(CONTENT.about_page_heading&&pg==='about')$('.ph1 h1').text(CONTENT.about_page_heading);
if(CONTENT.services_page_heading&&pg==='services')$('.ph1 h1').text(CONTENT.services_page_heading);
if(CONTENT.portfolio_page_heading&&pg==='portfolio')$('.ph1 h1').text(CONTENT.portfolio_page_heading);
if(CONTENT.contact_page_heading&&pg==='contact')$('.ph1 h1').text(CONTENT.contact_page_heading);
if(CONTENT.about_section_heading)$('body[data-p=about] .two h2').text(CONTENT.about_section_heading);
if(CONTENT.about_page_description)$('body[data-p=about] .two p').text(CONTENT.about_page_description);
if(CONTENT.hero_image)$('.hero-photo-main img').attr('src',CONTENT.hero_image);
if(CONTENT.about_image&&pg==='index')$('.two .im img').attr('src',CONTENT.about_image);
if(CONTENT.about_image&&pg==='about')$('.two .im img').attr('src',CONTENT.about_image);
if(CONTENT.hero_cta_text&&$('.hero-actions .btn').length)$('.hero-actions .btn').first().text(CONTENT.hero_cta_text);
if(CONTENT.hero_cta_url&&$('.hero-actions .btn').length){var cu=String(CONTENT.hero_cta_url);if(!/^[a-z][a-z0-9+.-]*:/i.test(cu)||/^https?:\/\//i.test(cu))$('.hero-actions .btn').first().attr('href',cu);}
if(CONTENT.booking_cta_text)$('.hd .btn').text(CONTENT.booking_cta_text);
if(CONTENT.hero_heading)$('.hero h1').text(CONTENT.hero_heading);
if(CONTENT.hero_description)$('.hero-copy p,.hero p').first().text(CONTENT.hero_description);
if(CONTENT.hero_kicker)$('.hero-kicker').text(CONTENT.hero_kicker);
if(CONTENT.categories_heading)$('body[data-p=index] #cats').closest('.w').find('h2').first().text(CONTENT.categories_heading);
if(CONTENT.featured_heading)$('body[data-p=index] #feat').closest('.w').find('h2').first().text(CONTENT.featured_heading);
if(CONTENT.why_heading)$('body[data-p=index] #why').closest('.w').find('h2').first().text(CONTENT.why_heading);
if(CONTENT.about_heading)$('body[data-p=index] .two h2').text(CONTENT.about_heading);
if(CONTENT.about_description)$('body[data-p=index] .two p').text(CONTENT.about_description);
if(CONTENT.contact_heading)$('.cta h2').text(CONTENT.contact_heading);
if(CONTENT.footer_description)$('footer .grid>div:first-child p').text(CONTENT.footer_description);
if(CONTENT.copyright_text)$('footer .w>p').last().text(CONTENT.copyright_text);
if(window.WAVE_SETTINGS){var st=window.WAVE_SETTINGS;if(st.phone){$('footer a[href^="tel:"]').attr('href','tel:'+st.phone.replace(/[^+0-9]/g,'')).text(st.phone);}$('footer a[href^="mailto:"]').attr('href','mailto:'+(st.email||'wavephotography24@gmail.com')).text(st.email||'wavephotography24@gmail.com');if(st.address)$('footer .grid>div:last-child li:last-child').text(st.address);}


$('body').prepend('<div id="prog"></div><header><div class="w"><a class="brand-lockup" href="index.php" aria-label="Wave Photography home"><img class="brand-symbol brand-primary" src="assets/logo/wave-photography-primary.png" alt="Wave Photography"></a><span class="hd"><a class="btn" href="contact.php">Book a Session</a><button id="bg" aria-expanded="false">Menu</button></span></div></header><div id="ov"><button id="cl">Close</button><nav>'+pages.map(p=>'<a href="'+(p[2]||((p[0]=='admin'?'admin/index.php':p[0]+'.php')))+'" class="'+(p[0]==pg?'on':'')+'"'+(p[3]?' target="_blank" rel="noopener"':'')+'>'+esc(p[1])+'</a>').join('')+'<a href="'+IG+'" target="_blank" rel="noopener">Instagram</a></nav><p>+91 6379700521 &nbsp; Tamil Nadu, India</p></div>');
$('body').append('<footer><div class="w"><div class="grid"><div><div class="logo lg"><img src="assets/logo/wave-photography-primary.png" alt="Wave Photography logo" loading="lazy"></div><p>Photography for weddings, babies, maternity, birthdays and families in Tamil Nadu.</p></div><div><h3>Services</h3><ul>'+SERVICES.map(s=>'<li><a href="service.php?s='+s.s+'">'+esc(s.n)+'</a></li>').join('')+'</ul></div><div><h3>Contact</h3><ul><li><a href="tel:+916379700521">+91 6379700521</a></li><li><a href="'+wa+'">WhatsApp</a></li><li><a href="'+IG+'">Instagram</a></li><li><a href="mailto:wavephotography24@gmail.com">wavephotography24@gmail.com</a></li><li>Tamil Nadu, India</li></ul></div></div><p style="margin-top:30px">&copy; 2026 Wave Photography</p></div></footer><a id="wa" href="'+wa+'" target="_blank" rel="noopener" aria-label="WhatsApp">&#9990;</a><div id="lb"><button class="x" aria-label="Close">&times;</button><button class="p" aria-label="Previous">&#8249;</button><img alt=""><p></p><button class="n" aria-label="Next">&#8250;</button></div>');if(window.WAVE_SETTINGS){var ws2=window.WAVE_SETTINGS;if(ws2.logo){$('.brand-lockup img,footer .logo img').attr('src',ws2.logo);}if(ws2.favicon)$('link[rel="icon"]').attr('href',ws2.favicon);if(ws2.phone){$('footer a[href^="tel:"]').attr('href','tel:'+ws2.phone.replace(/[^+0-9]/g,'')).text(ws2.phone);}if(ws2.email){$('footer a[href^="mailto:"],body[data-p=contact] a[href^="mailto:"]').attr('href','mailto:'+ws2.email).text(ws2.email);}if(ws2.phone){$('body[data-p=contact] a[href^="tel:"]').attr('href','tel:'+ws2.phone.replace(/[^+0-9]/g,'')).text(ws2.phone);}if(ws2.whatsapp){$('a[href^="https://wa.me/"]').attr('href','https://wa.me/'+ws2.whatsapp.replace(/[^0-9]/g,''));}}
if(CONTENT.footer_description)$('footer .grid>div:first-child p').text(CONTENT.footer_description);
if(CONTENT.copyright_text)$('footer .w>p').last().text(CONTENT.copyright_text);
if(CONTENT.booking_cta_text)$('.hd .btn').text(CONTENT.booking_cta_text);

$('#bg,#cl,#ov a').on('click',function(){var o=this.id!='cl'&&this.tagName!='A';$('#ov').toggleClass('open',o);$('#bg').attr('aria-expanded',o)});
var lastScroll=0,scrollTick=false;
$(window).on('scroll',function(){
var now=$(window).scrollTop(),max=$(document).height()-$(window).height();
$('#prog').width((max>0?now/max*100:0)+'%');
if(!scrollTick){window.requestAnimationFrame(function(){
if($('#ov').hasClass('open')){$('header').removeClass('header-hidden');}
else if(now<90||now<lastScroll-8){$('header').removeClass('header-hidden');}
else if(now>lastScroll+8){$('header').addClass('header-hidden');}
lastScroll=now;scrollTick=false;
});scrollTick=true;}
});
var ph=(p,i)=>'<figure class="ph" data-i="'+i+'" style="aspect-ratio:'+esc(p.ratio||'4/5')+'"><img loading="lazy" src="'+esc(p.thumb||p.image)+'" onerror="this.onerror=null;this.src=\''+esc(p.image)+'\'" alt="'+esc(p.alt_text||p.title+' – '+p.category+' photography')+'"><span>'+esc(p.category)+'<br><b>'+esc(p.title)+'</b></span></figure>';
var list=P;function lb(i){var p=list[i];$('#lb').addClass('on').data('i',i).find('img').attr({src:p.remote||p.image,alt:p.title}).end().find('p').text(p.title+' · '+p.category)}
$(document).on('click','.ph',function(){lb($(this).data('i'))});
function step(d){lb(($('#lb').data('i')+d+list.length)%list.length)}
$('#lb .x').on('click',()=>$('#lb').removeClass('on'));$('#lb .p').on('click',()=>step(-1));$('#lb .n').on('click',()=>step(1));
$(document).on('keydown',e=>{if(e.key=='Escape')$('#ov').removeClass('open');if(!$('#lb').hasClass('on'))return;if(e.key=='Escape')$('#lb').removeClass('on');if(e.key=='ArrowLeft')step(-1);if(e.key=='ArrowRight')step(1)});
function fill(c){list=c=='All'?P:P.filter(p=>p.category==c);$('#ms').html(list.length?list.map(ph).join(''):'<p class="empty-state">Photos in this category are being updated. Please check back soon.</p>')}
function servicePhotos(s){
var matches=P.filter(function(p){return p.category===s.c;});
return matches.length?matches:[{title:s.n,category:s.c,image:s.i,thumb:s.i,ratio:'4/5',placeholder:/\.svg$/i.test(s.i)}];
}
function serviceImageMarkup(p,s,index){
var src=p.thumb||p.image, fallback=p.image||s.i;
return '<a class="service-shot" href="service.php?s='+s.s+'" aria-label="View '+esc(s.n)+' gallery">'+
'<img loading="lazy" decoding="async" src="'+esc(src)+'" data-fallback="'+esc(fallback)+'" onerror="if(this.dataset.fallback&&this.src.indexOf(this.dataset.fallback)===-1){this.src=this.dataset.fallback;this.dataset.fallback=\'\';}else{this.onerror=null;this.src=\'assets/gallery/sample-14.svg\';}" alt="'+esc(p.alt_text||p.title+' – '+s.n+' photography')+'">'+
'<span>0'+(index+1)+'</span>'+(p.placeholder?'<em class="sample-tag">Sample visual</em>':'')+'</a>';
}
function serviceShowcase(s,idx){
var photos=servicePhotos(s),initial=photos.slice(0,3),hasMore=photos.length>3;
return '<article class="service-showcase rv" data-service="'+s.s+'">'+
'<div class="service-heading"><div><span class="eyebrow">WAVE PHOTOGRAPHY · '+String(idx+1).padStart(2,'0')+'</span><h3>'+esc(s.n)+'</h3><p>'+esc(s.d)+'</p></div></div>'+
'<div class="service-gallery">'+initial.map(function(p,i){return serviceImageMarkup(p,s,i);}).join('')+'</div>'+
'<div class="service-actions">'+
(hasMore?'<button class="load-more" type="button" data-service-more="'+s.s+'">Load more photos <span>＋</span></button>':'<span class="gallery-note">A little collection of beautiful moments</span>')+
'<a class="explore-service-btn" href="service.php?s='+s.s+'">Explore Service <span aria-hidden="true">↗</span></a>'+
'</div></article>';
}
function renderServiceShowcases(target){
$(target).html(SERVICES.map(serviceShowcase).join(''));
}
$('#cats').addClass('service-showcases');
renderServiceShowcases('#cats');
renderServiceShowcases('#svc');
$(document).on('click','[data-service-more]',function(){
var key=$(this).attr('data-service-more'),s=SERVICES.find(function(x){return x.s===key;});
if(!s)return;
var $gallery=$(this).closest('.service-showcase').find('.service-gallery');
var photos=servicePhotos(s),shown=$gallery.children().length,next=photos.slice(shown,shown+3);
next.forEach(function(p,i){$gallery.append(serviceImageMarkup(p,s,shown+i));});
if(shown+next.length>=photos.length){
$(this).replaceWith('<a class="load-more" href="service.php?s='+s.s+'">View complete gallery <span>↗</span></a>');
}
});
list=P.filter(p=>p.featured);$('#feat').html(list.length?list.map(ph).join(''):'<p class="empty-state">Our featured photographs are being updated. Please visit the full portfolio.</p>');
$('#why').html(WHY.map(w=>'<div><h3>'+w+'</h3></div>').join(''));
$('#ig').html(P.filter(p=>!p.placeholder).slice(0,8).map(p=>'<a href="'+IG+'" target="_blank" rel="noopener"><img loading="lazy" src="'+(p.thumb||p.image)+'" alt="Wave Photography sample photograph"></a>').join(''));
if($('#fl').length){var cats=['All'].concat(Array.from(new Set(P.map(function(p){return p.category;}).filter(Boolean))).sort());cats.forEach(c=>$('#fl').append($('<button>').text(c).toggleClass('on',c=='All').on('click',function(){$('#fl button').removeClass('on');$(this).addClass('on');fill(c)})));fill('All')}
var t=0,testimonialTimer=null;
function testimonialInitials(name){
 return String(name||'Wave Client').trim().split(/\\s+/).slice(0,2).map(function(part){return part.charAt(0).toUpperCase();}).join('')||'WC';
}
function renderTestimonial(){
 var $stage=$('#tt');
 if(!TESTI.length){
  $stage.html('<div class="testimonial-empty"><span class="testimonial-empty-icon" aria-hidden="true">✦</span><h3>Your story could be next.</h3><p>We would love to capture a moment that means the world to you.</p><a class="testimonial-contact" href="contact.php">Plan your session <span aria-hidden="true">↗</span></a></div>');
  $('.testimonial-controls').hide();
  return;
 }
 var x=TESTI[t],rating=Math.max(0,Math.min(5,Number(x.rating)||5));
 var avatar=x.customer_image
  ?'<img class="testimonial-avatar" loading="lazy" src="'+esc(x.customer_image)+'" alt="'+esc(x.name)+'" onerror="this.hidden=true;this.nextElementSibling.hidden=false">'
   +'<span class="testimonial-avatar-fallback" hidden>'+esc(testimonialInitials(x.name))+'</span>'
  :'<span class="testimonial-avatar-fallback">'+esc(testimonialInitials(x.name))+'</span>';
 var sample=Number(x.is_sample)?'<span class="testimonial-sample-label">Sample review</span>':'';
 var stars=Array.from({length:5},function(_,i){return '<span class="'+(i<rating?'is-filled':'')+'" aria-hidden="true">★</span>';}).join('');
 var card='<article class="testimonial-entry">'+sample+
  '<div class="testimonial-stars" role="img" aria-label="'+rating+' out of 5 stars">'+stars+'</div>'+
  '<blockquote>'+esc(x.text)+'</blockquote>'+
  '<div class="testimonial-author">'+avatar+'<div class="testimonial-author-copy"><strong>'+esc(x.name)+'</strong><span>'+esc(x.cat||'Wave Photography client')+'</span></div></div>'+
  '</article>';
 $stage.stop(true,true).css('opacity',0).html(card).animate({opacity:1},260);
 var dots=TESTI.map(function(item,i){return '<button type="button" class="testimonial-dot '+(i===t?'is-active':'')+'" data-testimonial-index="'+i+'" aria-label="Show testimonial '+(i+1)+'" aria-current="'+(i===t?'true':'false')+'"></button>';}).join('');
 $('#testimonial-dots').html(dots);
 $('.testimonial-controls').toggle(TESTI.length>1);
}
function moveTestimonial(direction){
 if(TESTI.length<2)return;
 t=(t+direction+TESTI.length)%TESTI.length;
 renderTestimonial();
}
if($('#tt').length){
 renderTestimonial();
 $('#tp').on('click',function(){moveTestimonial(-1);});
 $('#tn').on('click',function(){moveTestimonial(1);});
 $(document).on('click','[data-testimonial-index]',function(){
  t=Number($(this).attr('data-testimonial-index'))||0;renderTestimonial();
 });
 if(TESTI.length>1){
  testimonialTimer=window.setInterval(function(){
   if(!document.hidden&&!$('#testimonials:hover').length)moveTestimonial(1);
  },8000);
 }
}
if(pg=='service'){var s=SERVICES.find(x=>x.s==new URLSearchParams(location.search).get('s'))||SERVICES[0];document.title=s.n+' | Wave Photography';
$('#st').text(s.n);$('#sd').text(s.d);$('#si').attr({src:s.i,alt:s.n});$('#sl').html(s.inc.map(i=>'<li>'+esc(i)+'</li>').join(''));$('#sq').attr('href','contact.php?s='+encodeURIComponent(s.n));
list=P.filter(p=>p.category==s.c);$('#ms').html(list.map(ph).join(''))}
if(pg=='contact'){var q=new URLSearchParams(location.search).get('s');SERVICES.forEach(s=>$('#pk').append($('<option>').text(s.n)));if(q)$('#pk').val(q);
$('#f').on('submit',function(e){e.preventDefault();var waWindow=window.open('about:blank','_blank');var ok=true,v={};$('#f [name]').each(function(){var $e=$(this),x=$.trim($e.val()),bad=$e.prop('required')&&!x;
if(this.name=='phone'&&!/^(\+91)?[ -]?[6-9]\d{9}$/.test(x.replace(/\s/g,'')))bad=true;
if(this.name=='email'&&x&&!/^\S+@\S+\.\S+$/.test(x))bad=true;
if(this.name=='date'&&x&&new Date(x)<new Date(new Date().toDateString()))bad=true;
$e.toggleClass('er',bad);if(bad)ok=false;v[this.name]=x});
if(!ok){$('#m').attr('class','msg').text('Please fix the highlighted fields (valid 10-digit phone, email, future date).');return}
var m='Hi Wave Photography! Enquiry:\nName: '+v.name+'\nPhone: '+v.phone+'\nEmail: '+(v.email||'-')+'\nEvent: '+v.type+'\nDate: '+(v.date||'-')+'\nLocation: '+(v.loc||'-')+'\nPackage: '+v.pkg+'\nMessage: '+(v.msg||'-');
$('#m').attr('class','ok').text('Saving your enquiry…');fetch('api/booking.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(v)}).then(r=>r.json().then(d=>({ok:r.ok,data:d}))).then(r=>{if(!r.ok)throw new Error((r.data.errors||[r.data.message||'Unable to save enquiry.']).join(' '));$('#m').attr('class','ok').text('Enquiry saved. Opening WhatsApp…');if(waWindow){waWindow.location.href='https://wa.me/'+(r.data.whatsapp||WA)+'?text='+encodeURIComponent(m)}else{$('#m').append(' <a target="_blank" rel="noopener" href="https://wa.me/'+(r.data.whatsapp||WA)+'?text='+encodeURIComponent(m)+'">Continue to WhatsApp</a>')}}).catch(err=>{if(waWindow)waWindow.close();$('#m').attr('class','msg').text(err.message)})})}
var io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){$(e.target).addClass('in');io.unobserve(e.target)}}));
$('.sec .w>*').addClass('rv').each(function(){io.observe(this)});$('.service-showcase').each(function(){io.observe(this)});
$('body').append('<div class="orb" style="background:#00b4d8;width:420px;height:420px;left:-120px;top:10vh"></div><div class="orb" style="background:#90e0ef;width:360px;height:360px;right:-100px;top:50vh;animation-delay:-6s"></div><div class="orb" style="background:#0077b6;width:300px;height:300px;left:40vw;bottom:-120px;animation-delay:-12s"></div><div id="cg"></div><div id="ld"><span class="logo lg"><img src="assets/logo/wave-photography-primary.png" alt="Wave Photography"></span></div>');
setTimeout(()=>$('#ld').addClass('go'),1100);
$(document).on('mousemove',e=>$('#cg').css({left:e.clientX,top:e.clientY}));
var m='',k;for(k=0;k<16;k++)m+='<ellipse rx="13" ry="52" cy="-48" transform="rotate('+k*22.5+')"/>';for(k=0;k<8;k++)m+='<ellipse rx="7" ry="22" cy="-22" transform="rotate('+(k*45+22.5)+')"/>';
var sv='<svg viewBox="-100 -100 200 200" fill="none" stroke="#00b4d8" stroke-width=".6">'+m+'<circle r="98"/><circle r="74"/><circle r="10"/></svg>';
$('.cta').prepend('<div class="mand">'+sv+'</div>');
for(var i=0,s2='';i<28;i++)s2+='<i style="left:'+Math.random()*100+'%;top:'+Math.random()*100+'%;animation-delay:'+Math.random()*3+'s"></i>';
$('.hero h1').removeClass('wd');
$(document).on('mousemove','.ph,.card',function(e){var r=this.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;this.style.transform='perspective(800px) rotateY('+x*8+'deg) rotateX('+-y*8+'deg) scale(1.02)'}).on('mouseleave','.ph,.card',function(){this.style.transform=''});
});

$(function(){
var video=document.querySelector('.vid video');
if(!video)return;
video.addEventListener('play',function(){video.removeAttribute('controls');});
video.addEventListener('pause',function(){video.setAttribute('controls','controls');});
video.addEventListener('ended',function(){video.setAttribute('controls','controls');});
if(!video.paused)video.removeAttribute('controls');
});
$(function(){var v=$('.vid video')[0];if(v&&'IntersectionObserver' in window&&!matchMedia('(prefers-reduced-motion:reduce)').matches&&!(navigator.connection&&navigator.connection.saveData))new IntersectionObserver(function(e){e[0].isIntersecting?v.play().catch(function(){}):v.pause()},{threshold:.5}).observe(v)});
