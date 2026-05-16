<?php
$pageTitle = 'LuxStay — Premium Hotel Booking Nepal';
require_once 'config/db.php';
require_once 'includes/auth.php';
$db = getDB();
$hotels = $db->query("SELECT * FROM hotels WHERE is_active=1 ORDER BY is_featured DESC, rating DESC LIMIT 8")->fetchAll();
include 'includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <canvas id="hero-canvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-tag">⭐ Nepal's #1 Luxury Booking Platform</div>
    <h1 class="hero-title">
      Discover Your<br>
      <span class="gold-text">Perfect Luxury Stay</span>
    </h1>
    <p class="hero-sub">Book world-class hotels across Nepal and beyond. Instant confirmation, no hidden fees.</p>

    <!-- Search -->
    <form action="hotels.php" method="GET">
      <div class="search-box">
        <div class="search-grid">
          <div class="form-group" style="margin:0">
            <label class="form-label">Destination</label>
            <div class="input-wrapper">
              <span class="input-icon">📍</span>
              <input type="text" name="city" class="form-input" placeholder="City or hotel name">
            </div>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Check-in</label>
            <input type="date" name="check_in" class="form-input" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Check-out</label>
            <input type="date" name="check_out" class="form-input" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Guests</label>
            <div class="input-wrapper">
              <span class="input-icon">👥</span>
              <select name="guests" class="form-input">
                <?php for($i=1;$i<=6;$i++): ?><option value="<?=$i?>"><?=$i?> Guest<?=$i>1?'s':''?></option><?php endfor; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="search-actions">
          <select name="stars" class="form-input" style="width:auto;padding:.5rem .875rem;font-size:.8125rem">
            <option value="">Any Stars</option>
            <?php for($s=5;$s>=2;$s--): ?><option value="<?=$s?>"><?=$s?>★</option><?php endfor; ?>
          </select>
          <select name="min_price" class="form-input" style="width:auto;padding:.5rem .875rem;font-size:.8125rem">
            <option value="">Any Price</option>
            <option value="3000">NPR 3,000+</option>
            <option value="8000">NPR 8,000+</option>
            <option value="15000">NPR 15,000+</option>
          </select>
          <button type="submit" class="btn-sm btn-primary" style="padding:.6rem 1.5rem">🔍 Search Hotels</button>
        </div>
      </div>
    </form>

    <!-- Stats -->
    <div class="stats-row" style="margin-top:2.5rem">
      <div class="stat-item"><div class="stat-val gold-text">500+</div><div class="stat-lbl">Luxury Hotels</div></div>
      <div class="stat-item"><div class="stat-val gold-text">50K+</div><div class="stat-lbl">Happy Guests</div></div>
      <div class="stat-item"><div class="stat-val gold-text">20+</div><div class="stat-lbl">Destinations</div></div>
      <div class="stat-item"><div class="stat-val gold-text">4.9★</div><div class="stat-lbl">Avg Rating</div></div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="section">
  <div class="container">
    <div class="grid-4">
      <?php foreach([
        ['⚡','Instant Booking','Confirm in seconds with real-time availability.'],
        ['🔒','Secure Payments','eSewa & card payments with bank-grade security.'],
        ['🕐','24/7 Support','Round-the-clock assistance for all travel needs.'],
        ['🎁','Best Deals','Exclusive offers and member discounts every day.'],
      ] as [$icon,$title,$desc]): ?>
      <div class="card p-3 rounded" style="text-align:center">
        <div style="width:52px;height:52px;background:linear-gradient(135deg,#d4a017,#f5c842);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin:0 auto 1rem;box-shadow:0 4px 16px rgba(212,160,23,0.3)"><?= $icon ?></div>
        <h3 style="font-weight:700;color:var(--text);margin-bottom:.375rem"><?= $title ?></h3>
        <p style="font-size:.875rem;color:var(--text3);line-height:1.6"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Featured Hotels -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-header">
      <div>
        <div class="section-label">🏅 Featured Stays</div>
        <h2 class="section-title">Top Luxury Hotels</h2>
      </div>
      <a href="hotels.php" class="link-gold" style="display:flex;align-items:center;gap:.25rem">View All →</a>
    </div>
    <?php if (empty($hotels)): ?>
      <!-- Demo hotels if DB empty -->
      <?php $hotels = [
        ['id'=>1,'name'=>'Kings Hotel','stars'=>5,'rating'=>4.8,'review_count'=>124,'cover_image'=>'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80','city'=>'Kathmandu','country'=>'Nepal','min_price'=>8000,'amenities'=>'["WiFi","Pool","Spa"]','discount'=>15,'slug'=>'kings-hotel','short_desc'=>'Five-star sanctuary in Kathmandu.'],
        ['id'=>2,'name'=>'Royal Palace Hotel','stars'=>5,'rating'=>4.9,'review_count'=>200,'cover_image'=>'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&q=80','city'=>'Pokhara','country'=>'Nepal','min_price'=>12000,'amenities'=>'["WiFi","Pool","Restaurant"]','discount'=>0,'slug'=>'royal-palace-hotel','short_desc'=>'Breathtaking lake-view suites.'],
        ['id'=>3,'name'=>'Everest Luxury Resort','stars'=>5,'rating'=>4.7,'review_count'=>89,'cover_image'=>'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=600&q=80','city'=>'Namche Bazaar','country'=>'Nepal','min_price'=>15000,'amenities'=>'["WiFi","Spa","Gym"]','discount'=>10,'slug'=>'everest-luxury-resort','short_desc'=>'Alpine luxury with Himalayan views.'],
        ['id'=>4,'name'=>'Himalayan Suites','stars'=>4,'rating'=>4.6,'review_count'=>156,'cover_image'=>'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&q=80','city'=>'Chitwan','country'=>'Nepal','min_price'=>5000,'amenities'=>'["WiFi","Pool","Restaurant"]','discount'=>5,'slug'=>'himalayan-suites','short_desc'=>'Eco-luxury surrounded by Chitwan forest.'],
      ]; ?>
    <?php endif; ?>
    <div class="hotel-grid">
      <?php foreach($hotels as $h):
        $amenities = json_decode($h['amenities']??'[]',true);
        $discountedPrice = $h['discount'] > 0 ? round($h['min_price'] * (1 - $h['discount']/100)) : null;
      ?>
      <a href="hotel-detail.php?id=<?= $h['id'] ?>" class="hotel-card">
        <div class="hotel-img-wrap">
          <img src="<?= e($h['cover_image']) ?>" alt="<?= e($h['name']) ?>" loading="lazy">
          <div class="hotel-img-overlay"></div>
          <div class="hotel-stars">
            <?php for($i=0;$i<$h['stars'];$i++): ?><span class="star-filled">★</span><?php endfor; ?>
          </div>
          <?php if($h['discount']>0): ?>
            <div class="hotel-discount">-<?= $h['discount'] ?>% OFF</div>
          <?php endif; ?>
        </div>
        <div class="hotel-body">
          <div class="hotel-name"><?= e($h['name']) ?></div>
          <div class="hotel-location">📍 <?= e($h['city']) ?>, <?= e($h['country']) ?></div>
          <?php if(!empty($h['short_desc'])): ?>
            <p style="font-size:.8rem;color:var(--text3);margin-bottom:.75rem;line-height:1.5"><?= e($h['short_desc']) ?></p>
          <?php endif; ?>
          <div class="hotel-amenities">
            <?php foreach(array_slice($amenities,0,3) as $a): ?>
              <span class="amenity-tag"><?= e($a) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="hotel-footer">
            <div class="hotel-price">
              NPR <?= number_format($discountedPrice ?? $h['min_price']) ?>
              <small>per night · <?= $h['rating'] ?>★ (<?= $h['review_count'] ?>)</small>
            </div>
            <span class="badge badge-gold">Book Now</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Offer Banner -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div style="background:linear-gradient(135deg,rgba(212,160,23,.08),rgba(8,8,26,.95),rgba(212,160,23,.08));border:1px solid rgba(212,160,23,.2);border-radius:20px;padding:3.5rem 2rem;text-align:center;position:relative;overflow:hidden">
      <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:500px;height:500px;background:radial-gradient(ellipse,rgba(212,160,23,0.08),transparent 70%);pointer-events:none"></div>
      <span class="badge badge-gold" style="font-size:.75rem;padding:.35rem .875rem">🏷️ Limited Time Offer</span>
      <h2 style="font-family:'Playfair Display',serif;font-size:clamp(1.75rem,4vw,3rem);font-weight:800;color:#fff;margin:1rem 0 .5rem">Up to <span class="gold-text">40% OFF</span> on Weekend Stays</h2>
      <p style="color:rgba(255,255,255,.6);margin-bottom:2rem">Book any hotel this weekend and save big. Use code <strong style="color:var(--gold);background:rgba(212,160,23,.1);padding:.1rem .5rem;border-radius:6px">LUXWEEKEND</strong></p>
      <a href="hotels.php" class="btn-sm btn-primary" style="padding:.875rem 2.5rem;font-size:.9375rem">Explore Deals</a>
    </div>
  </div>
</section>

<!-- Destinations -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-header">
      <div>
        <div class="section-label">📈 Trending</div>
        <h2 class="section-title">Popular Destinations</h2>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem">
      <?php foreach([
        ['Kathmandu',48,'https://images.unsplash.com/photo-1605640840605-14ac1855827b?w=400&q=80'],
        ['Pokhara',32,'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=400&q=80'],
        ['Chitwan',24,'https://images.unsplash.com/photo-1553697388-94e804e2f0f6?w=400&q=80'],
        ['Lumbini',16,'https://images.unsplash.com/photo-1588088884372-29a8c67c28ed?w=400&q=80'],
        ['Nagarkot',12,'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&q=80'],
        ['Bhaktapur',18,'https://images.unsplash.com/photo-1580654712603-eb43273aff33?w=400&q=80'],
      ] as [$city,$cnt,$img]): ?>
      <a href="hotels.php?city=<?= urlencode($city) ?>" style="display:block;border-radius:14px;overflow:hidden;position:relative;height:170px;text-decoration:none">
        <img src="<?= $img ?>" alt="<?= $city ?>" style="width:100%;height:100%;object-fit:cover;transition:transform .4s">
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(8,8,26,.85),transparent)"></div>
        <div style="position:absolute;bottom:.75rem;left:.875rem">
          <div style="color:#fff;font-weight:700;font-size:.9375rem"><?= $city ?></div>
          <div style="color:rgba(255,255,255,.6);font-size:.75rem"><?= $cnt ?> Hotels</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="section" style="background:var(--bg2)">
  <div class="container">
    <div style="text-align:center;margin-bottom:3rem">
      <div class="section-label" style="justify-content:center">⭐ Guest Reviews</div>
      <h2 class="section-title">What Our Guests Say</h2>
    </div>
    <div class="grid-3">
      <?php foreach([
        ['Priya Sharma','Travel Blogger','https://i.pravatar.cc/64?img=1',5,'LuxStay completely transformed how I book hotels. The 3D previews are incredible!'],
        ['Rahul Thapa','Business Executive','https://i.pravatar.cc/64?img=5',5,'Seamless eSewa integration. Booked the Everest Resort in under 2 minutes!'],
        ['Anita Gurung','Newlywed','https://i.pravatar.cc/64?img=9',5,'The Presidential Suite was beyond expectations. LuxStay made our honeymoon perfect!'],
      ] as [$name,$role,$avatar,$stars,$comment]): ?>
      <div class="card p-3 rounded">
        <div style="color:var(--gold);margin-bottom:.875rem;font-size:1rem"><?= str_repeat('★',$stars) ?></div>
        <p style="font-size:.875rem;color:var(--text2);line-height:1.7;font-style:italic;margin-bottom:1.25rem">"<?= $comment ?>"</p>
        <div class="flex items-center gap-1">
          <img src="<?= $avatar ?>" alt="<?= $name ?>" style="width:40px;height:40px;border-radius:50%;border:2px solid rgba(212,160,23,.3)">
          <div>
            <div style="font-weight:700;font-size:.875rem;color:var(--text)"><?= $name ?></div>
            <div style="font-size:.75rem;color:var(--text3)"><?= $role ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section" style="text-align:center">
  <div class="container" style="max-width:600px">
    <div style="font-size:3rem;margin-bottom:1rem">🌏</div>
    <h2 style="font-family:'Playfair Display',serif;font-size:2.25rem;font-weight:800;color:var(--text);margin-bottom:1rem">
      Ready to Experience <span class="gold-text">Luxury?</span>
    </h2>
    <p style="color:var(--text3);margin-bottom:2rem;line-height:1.7">Join 50,000+ travelers who trust LuxStay for world-class accommodation across Nepal.</p>
    <div class="flex" style="gap:1rem;justify-content:center;flex-wrap:wrap">
      <a href="register.php" class="btn-sm btn-primary" style="padding:.875rem 2rem;font-size:.9375rem">Start Booking Free</a>
      <a href="hotels.php" class="btn-sm btn-ghost" style="padding:.875rem 2rem;font-size:.9375rem">Explore Hotels</a>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
