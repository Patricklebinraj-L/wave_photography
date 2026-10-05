<?php require_once __DIR__ . "/includes/bootstrap.php"; ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#1c5664"><title>Home | Wave Photography</title><meta name="description" content="Wave Photography: wedding, baby, maternity and family photography in Tamil Nadu."><meta property="og:title" content="Home | Wave Photography"><meta property="og:description" content="Wave Photography: wedding, baby, maternity and family photography in Tamil Nadu."><meta property="og:image" content="assets/logo/wave-photography-primary.png"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="css/style.css?v=<?=is_file(__DIR__.'/css/style.css')?filemtime(__DIR__.'/css/style.css'):1?>"><link rel="icon" href="assets/logo/wave-photography-favicon.png"></head><body data-p="index">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Photographer","name":"Wave Photography","telephone":"+916379700521","email":"wavephotography24@gmail.com","sameAs":["https://www.instagram.com/wave_photography_cj/"],"areaServed":"Tamil Nadu, India"}</script>
<section class="hero">
  <div class="hero-inner w">
    <div class="hero-copy">
      <span class="hero-kicker"><i></i> WAVE PHOTOGRAPHY · CHENNAI</span>
      <h1>Every Picture Tells a Beautiful Story.</h1>
      <p>Capturing your happiest moments, precious memories, and beautiful celebrations through timeless photography.</p>
      <div class="hero-actions">
        <a class="btn" href="portfolio.php">Explore Our Work <span aria-hidden="true">↗</span></a>
        <a class="btn o" href="contact.php">Book Your Session</a>
      </div>
      <div class="hero-proof"><span>♡</span> Weddings · Families · Little Moments</div>
    </div>
    <div class="hero-visual" aria-label="Wave Photography featured photographs">
      <div class="hero-photo-main"><img src="assets/pre-wedding/dusk-tower.webp" alt="A beautifully captured couple at dusk" fetchpriority="high"></div>
      <div class="hero-photo-small hero-photo-one"><img src="assets/pre-wedding/twirl-steps-sm.webp" alt="Couple sharing a joyful moment"></div>
      <div class="hero-photo-small hero-photo-two"><img src="assets/pre-wedding/yellow-bench-sm.webp" alt="Couple portrait beside a colourful backdrop"></div>
      <div class="hero-orbit"></div>
    </div>
  </div>
  <a class="hero-scroll" href="#cats"><span></span> SCROLL TO EXPLORE</a>
</section>
<section class="sec"><div class="w"><h2>Photography Categories</h2><div class="rows" id="cats"></div></div></section>
<section class="sec dk"><div class="w"><h2>Featured Portfolio</h2><div class="bento" id="feat"></div><p style="margin-top:20px"><a class="btn" href="portfolio.php">View full portfolio</a></p></div></section>
<section class="sec"><div class="w tc"><h2>Our Story in Motion</h2><div class="vid"><video controls muted loop playsinline preload="none" poster="assets/hero/poster.jpg" aria-label="Wave Photography brand film"><source src="assets/hero/hero.mp4" type="video/mp4"></video></div></div></section>
<section class="sec lt"><div class="w two"><div class="im"><img loading="lazy" src="assets/photographer/sample.svg" alt="Photographer portrait placeholder"></div><div><h2>Behind every photograph is a moment waiting to be remembered.</h2><p>At Wave Photography, we believe photography is more than capturing images. It is about preserving emotions, celebrating relationships, and creating memories that last forever. (Placeholder text: replace with your own story.)</p><br><a class="btn" href="about.php">About us</a></div></div></section>
<section class="sec"><div class="w"><h2>Why Choose Wave Photography?</h2><div class="why" id="why"></div></div></section>
<section class="sec alt testimonials-section" id="testimonials" aria-labelledby="testimonials-title">
  <div class="w testimonials-wrap">
    <div class="testimonials-heading">
      <span class="eyebrow">REAL STORIES · REAL MOMENTS</span>
      <h2 id="testimonials-title">Kind Words From Our Clients</h2>
      <p>Every celebration is personal. We are honoured to preserve the moments our clients treasure.</p>
      <span class="section-rule" aria-hidden="true"></span>
    </div>
    <div class="testimonial-stage">
      <span class="testimonial-quote-mark" aria-hidden="true">“</span>
      <div id="tt" class="testimonial-card" aria-live="polite" aria-atomic="true">
        <p class="testimonial-loading">Loading client stories…</p>
      </div>
      <div class="testimonial-controls">
        <button class="testimonial-arrow" id="tp" type="button" aria-label="Previous testimonial">←</button>
        <div class="testimonial-dots" id="testimonial-dots" aria-label="Choose a testimonial"></div>
        <button class="testimonial-arrow" id="tn" type="button" aria-label="Next testimonial">→</button>
      </div>
    </div>
    <p class="testimonials-footnote">Your memories matter to us. <a href="contact.php">Let's create yours <span aria-hidden="true">↗</span></a></p>
  </div>
</section>
<section class="sec"><div class="w"><h2>Follow @wave_photography_cj</h2><div class="ig" id="ig"></div><br><a class="btn" href="https://www.instagram.com/wave_photography_cj/" target="_blank" rel="noopener">Follow on Instagram</a></div></section>
<section class="sec cta"><div class="w"><h2>Let's Capture Your Beautiful Moments.</h2><p>From the smallest smiles to the biggest celebrations, let's create memories you'll cherish forever.</p><a class="btn" href="contact.php">Book Your Session</a> <a class="btn o" href="https://wa.me/916379700521">WhatsApp Us</a></div></section>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="data.php"></script><script src="js/main.js"></script></body></html>
