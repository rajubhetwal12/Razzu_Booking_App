<?php
$pageTitle='LuxStay — Premium Hotel Booking Nepal';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
$db=getDB();
$hotels=$db->query("SELECT * FROM hotels WHERE is_active=1 ORDER BY is_featured DESC,rating DESC LIMIT 8")->fetchAll();
include __DIR__.'/includes/header.php';
?>

<!-- ── HERO ── -->
<section class="hero" style="margin-top:-64px">
  <canvas id="hero-canvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="hero-body">
    <div class="hero-chip">⭐ Nepal's #1 Luxury Hotel Platform</div>
    <h1 class="hero-title">Discover Your<br><span class="gold-text">Perfect Luxury Stay</span></h1>
    <p class="hero-sub">Book world-class hotels across Nepal. Instant confirmation, best prices, unforgettable experiences.</p>

    <!-- Search -->
    <form action="<?=BASE_URL?>/hotels.php" method="GET">
      <div class="search-box">
        <div class="search-grid">
          <div class="form-group" style="margin:0">
            <label class="form-label">Destination</label>
            <div class="input-wrap"><span class="input-icon">📍</span>
              <input type="text" name="city" class="form-input" placeholder="City or hotel name">
            </div>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Check-in</label>
            <input type="date" name="check_in" class="form-input" min="<?=date('Y-m-d')?>">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Check-out</label>
            <input type="date" name="check_out" class="form-input" min="<?=date('Y-m-d',strtotime('+1 day'))?>">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Guests</label>
            <div class="input-wrap"><span class="input-icon">👥</span>
              <select name="guests" class="form-input">
                <?php for($i=1;$i<=8;$i++):?><option><?=$i?> Guest<?=$i>1?'s':''?></option><?php endfor;?>
              </select>
            </div>
          </div>
        </div>
        <div class="search-footer">
          <select name="stars" class="form-input" style="width:auto;padding:.5rem .875rem;font-size:.8rem">
            <option value="">Any Stars</option>
            <?php for($s=5;$s>=2;$s--):?><option value="<?=$s?>"><?=$s?>★</option><?php endfor;?>
          </select>
          <select name="sort" class="form-input" style="width:auto;padding:.5rem .875rem;font-size:.8rem">
            <option value="rating">Top Rated</option>
            <option value="price_asc">Price ↑</option>
            <option value="price_desc">Price ↓</option>
          </select>
          <button type="submit" class="btn btn-primary">🔍 Search Hotels</button>
        </div>
      </div>
    </form>

    <div class="stats-row">
      <?php foreach([['500+','Hotels'],['50K+','Guests'],['20+','Cities'],['4.9★','Rating']] as [$v,$l]):?>
        <div><div class="stat-n gold-text"><?=$v?></div><div class="stat-l"><?=$l?></div></div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ── WHY LUXSTAY ── -->
<section class="section" style="padding-top:4rem">
  <div class="container">
    <div class="g4">
      <?php foreach([['⚡','Instant Booking','Confirm your stay in seconds with real-time availability.'],['🔒','Secure Payments','eSewa & Khalti with bank-grade 256-bit encryption.'],['🕐','24/7 Support','Expert assistance around the clock for all your needs.'],['🎁','Best Price','Find it cheaper? We match or beat the price, guaranteed.']] as [$ic,$t,$d]):?>
        <div class="card p3 rounded" style="text-align:center">
          <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--gold),var(--gold2));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin:0 auto 1rem;box-shadow:0 4px 20px rgba(212,160,23,.3)"><?=$ic?></div>
          <h3 style="font-weight:700;margin-bottom:.375rem;font-size:.9375rem"><?=$t?></h3>
          <p style="font-size:.8rem;color:var(--text3);line-height:1.65"><?=$d?></p>
        </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ── CHOOSE YOUR EXPERIENCE ── -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-header">
      <div><div class="section-label">🏷️ Room Packages</div><h2 class="section-title">Choose Your Experience</h2></div>
    </div>
    <p style="color:var(--text3);margin-bottom:2rem;font-size:.9375rem">Click any package to see exactly what's included — then browse matching hotels.</p>
    <div id="pkg-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:1rem">
      <?php
      // Inline package data fallback (works even before migration is run)
      $pkgFallback=[
        ['standard','Standard','🛏️','#6b7280','Clean, comfortable stays for savvy travellers','From NPR 5,000'],
        ['deluxe','Deluxe','✨','#3b82f6','Elevated comfort with premium views & amenities','From NPR 10,000'],
        ['luxury','Luxury','👑','#d4a017','Unparalleled elegance & exclusive perks','From NPR 20,000'],
        ['family','Family','👨‍👩‍👧‍👦','#10b981','Spacious rooms designed for families','From NPR 12,000'],
        ['couple','Couple','💑','#ec4899','Romantic suites with jacuzzis & champagne','From NPR 18,000'],
        ['presidential','Presidential','🏆','#f59e0b','The pinnacle of luxury — panoramic views','From NPR 40,000'],
      ];
      // Try to load from DB
      try {
        $pkgRows=$db->query("SELECT * FROM hotel_type_packages WHERE is_active=1 ORDER BY sort_order")->fetchAll();
        if($pkgRows) $pkgFallback=array_map(fn($p)=>[$p['type_key'],$p['display_name'],$p['icon'],$p['badge_color'],$p['tagline'],$p['price_label']],$pkgRows);
      } catch(Exception $e2){}
      foreach($pkgFallback as [$key,$name,$icon,$color,$tagline,$priceLabel]):
        $hCount=0;
        try{$s=$db->prepare("SELECT COUNT(DISTINCT h.id) FROM hotels h JOIN rooms r ON r.hotel_id=h.id WHERE r.type=? AND h.is_verified=1 AND h.is_active=1");$s->execute([$key]);$hCount=(int)$s->fetchColumn();}catch(Exception $e3){}
      ?>
        <div class="pkg-card" data-type="<?=e($key)?>" onclick="openPkgModal('<?=e($key)?>')"
             style="border-radius:16px;border:1px solid var(--border);padding:1.5rem 1rem;text-align:center;cursor:pointer;transition:.25s;background:var(--bg2);position:relative;overflow:hidden">
          <div style="position:absolute;inset:0;background:linear-gradient(135deg,<?=e($color)?>15,transparent);pointer-events:none"></div>
          <div style="font-size:2.25rem;margin-bottom:.625rem"><?=e($icon)?></div>
          <div style="font-weight:800;font-size:1rem;margin-bottom:.375rem"><?=e($name)?></div>
          <div style="font-size:.75rem;color:var(--text3);line-height:1.5;margin-bottom:.875rem"><?=e($tagline)?></div>
          <div style="font-size:.7rem;font-weight:700;color:<?=e($color)?>;margin-bottom:.875rem"><?=e($priceLabel)?></div>
          <div style="font-size:.7rem;color:var(--text3);margin-bottom:.875rem"><?=$hCount?> hotel<?=$hCount!=1?'s':''?> available</div>
          <div style="display:inline-block;padding:.3rem .875rem;border-radius:20px;font-size:.72rem;font-weight:700;color:<?=e($color)?>;border:1.5px solid <?=e($color)?>">
            See Facilities →
          </div>
        </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ── PACKAGE MODAL ── -->
<div id="pkg-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1rem">
  <div id="pkg-modal-box" style="background:var(--bg);border:1px solid var(--border);border-radius:24px;width:100%;max-width:560px;max-height:88vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.6)">
    <div style="padding:1.5rem 1.5rem 0;display:flex;align-items:center;justify-content:space-between">
      <div id="pkg-modal-title" style="font-family:'Playfair Display',serif;font-size:1.375rem;font-weight:800"></div>
      <button onclick="closePkgModal()" style="background:var(--bg2);border:1px solid var(--border);border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:1rem;color:var(--text2)">✕</button>
    </div>
    <div id="pkg-modal-tagline" style="padding:.375rem 1.5rem 0;font-size:.875rem;color:var(--text3)"></div>
    <div id="pkg-modal-price" style="padding:.375rem 1.5rem .75rem;font-size:.8rem;font-weight:700"></div>
    <hr style="border-color:var(--border);margin:0 1.5rem">
    <div style="padding:1.25rem 1.5rem">
      <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:1rem">✅ What's Included</div>
      <div id="pkg-modal-facilities" style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem"></div>
      <div id="pkg-modal-empty" style="display:none;color:var(--text3);font-size:.875rem;padding:1rem 0">Facility details not yet configured.</div>
    </div>
    <div style="padding:0 1.5rem 1.5rem">
      <a id="pkg-modal-browse-btn" href="#" class="btn btn-primary" style="width:100%;display:block;text-align:center">Browse Hotels →</a>
    </div>
  </div>
</div>

<script>
const pkgData={};
let pkgLoaded=false;

async function loadPkgData(){
  if(pkgLoaded)return;
  pkgLoaded=true;
  try{
    const res=await fetch('<?=BASE_URL?>/server/hotel-types.php');
    const json=await res.json();
    if(json.success)json.packages.forEach(p=>pkgData[p.type_key]=p);
  }catch(e){pkgLoaded=false;}
}

async function openPkgModal(typeKey){
  await loadPkgData();
  const pkg=pkgData[typeKey];
  const card=document.querySelector(`.pkg-card[data-type="${typeKey}"]`);
  const color=card?getComputedStyle(card).getPropertyValue('--c')||'#d4a017':'#d4a017';

  const icons={standard:'🛏️',deluxe:'✨',luxury:'👑',family:'👨‍👩‍👧‍👦',couple:'💑',presidential:'🏆'};
  const colors={standard:'#6b7280',deluxe:'#3b82f6',luxury:'#d4a017',family:'#10b981',couple:'#ec4899',presidential:'#f59e0b'};
  const labels={standard:'Standard',deluxe:'Deluxe',luxury:'Luxury',family:'Family',couple:'Couple',presidential:'Presidential'};
  const c=colors[typeKey]||'#d4a017';

  document.getElementById('pkg-modal-title').innerHTML=`<span style="margin-right:.5rem">${icons[typeKey]||'🏨'}</span>${pkg?pkg.display_name:labels[typeKey]}`;
  document.getElementById('pkg-modal-title').style.color=c;
  document.getElementById('pkg-modal-tagline').textContent=pkg?pkg.tagline:'';
  document.getElementById('pkg-modal-price').textContent=pkg?pkg.price_label:'';
  document.getElementById('pkg-modal-price').style.color=c;
  document.getElementById('pkg-modal-browse-btn').href=`<?=BASE_URL?>/hotels.php?room_type=${typeKey}`;
  document.getElementById('pkg-modal-browse-btn').style.background=c;

  const facDiv=document.getElementById('pkg-modal-facilities');
  const emptyDiv=document.getElementById('pkg-modal-empty');
  facDiv.innerHTML='';

  const facils=pkg?.facilities||[];
  if(facils.length===0){
    facDiv.style.display='none';emptyDiv.style.display='block';
  }else{
    facDiv.style.display='grid';emptyDiv.style.display='none';
    // Group by category
    const bycat={};
    facils.forEach(f=>{if(!bycat[f.category])bycat[f.category]=[];bycat[f.category].push(f);});
    Object.entries(bycat).forEach(([cat,facs])=>{
      const catEl=document.createElement('div');
      catEl.style.cssText='grid-column:1/-1;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-top:.75rem;margin-bottom:.25rem;border-bottom:1px solid var(--border);padding-bottom:.25rem';
      catEl.textContent=cat;
      facDiv.appendChild(catEl);
      facs.forEach(f=>{
        const el=document.createElement('div');
        el.style.cssText=`display:flex;align-items:center;gap:.5rem;padding:.45rem .625rem;border-radius:8px;background:var(--bg2);font-size:.8125rem;border:1px solid var(--border)`;
        el.innerHTML=`<span style="font-size:1rem">${f.icon}</span><span>${f.name}</span>`;
        facDiv.appendChild(el);
      });
    });
  }

  const modal=document.getElementById('pkg-modal');
  modal.style.display='flex';
  setTimeout(()=>modal.style.opacity='1',10);
  document.body.style.overflow='hidden';
}

function closePkgModal(){
  document.getElementById('pkg-modal').style.display='none';
  document.body.style.overflow='';
}
document.getElementById('pkg-modal').addEventListener('click',function(e){if(e.target===this)closePkgModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closePkgModal();});

// Hover effect for pkg cards
document.querySelectorAll('.pkg-card').forEach(c=>{
  c.addEventListener('mouseenter',()=>{c.style.transform='translateY(-4px)';c.style.boxShadow='0 12px 40px rgba(0,0,0,.3)';c.style.borderColor='rgba(212,160,23,.4)';});
  c.addEventListener('mouseleave',()=>{c.style.transform='';c.style.boxShadow='';c.style.borderColor='var(--border)';});
});
</script>

<!-- ── FEATURED HOTELS ── -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-header">
      <div><div class="section-label">🏅 Top Picks</div><h2 class="section-title">Featured Luxury Hotels</h2></div>
      <a href="<?=BASE_URL?>/hotels.php" class="link-more">View All Hotels →</a>
    </div>
    <?php if(empty($hotels)):?>
      <div class="tc" style="padding:3rem;color:var(--text3)">No hotels yet. <a href="<?=BASE_URL?>/setup.php" style="color:var(--gold)">Run setup.php</a> first.</div>
    <?php else:?>
      <div class="hotels-grid">
        <?php foreach($hotels as $h):
          $dp=$h['discount']>0?round($h['min_price']*(1-$h['discount']/100)):null;
        ?>
          <a href="<?=BASE_URL?>/hotel-detail.php?id=<?=$h['id']?>" class="hotel-card">
            <div class="hotel-img">
              <img src="<?=e($h['cover_image'])?>" alt="<?=e($h['name'])?>" loading="lazy">
              <div class="hotel-img-overlay"></div>
              <div class="hotel-stars"><?=str_repeat('★',(int)$h['stars'])?></div>
              <?php if($h['discount']>0):?><div class="hotel-disc">-<?=$h['discount']?>% OFF</div><?php endif;?>
              <?php if($h['is_featured']):?><div class="hotel-feat">⭐ Featured</div><?php endif;?>
            </div>
            <div class="hotel-body">
              <div class="hotel-name"><?=e($h['name'])?></div>
              <div class="hotel-loc">📍 <?=e($h['city'])?>, <?=e($h['country'])?></div>
              <div class="hotel-desc"><?=e($h['short_desc']??'')?></div>
              <div class="hotel-foot">
                <div class="hotel-price">
                  <?php if($dp):?><s style="font-size:.67rem;color:var(--text3);font-weight:400">NPR <?=number_format($h['min_price'])?></s><br><?php endif;?>
                  NPR <?=number_format($dp??$h['min_price'])?>
                  <small>/night · <?=$h['rating']?>★ (<?=$h['review_count']?>)</small>
                </div>
                <span class="badge badge-gold">Book Now</span>
              </div>
            </div>
          </a>
        <?php endforeach;?>
      </div>
    <?php endif;?>
  </div>
</section>

<!-- ── PROMO BANNER ── -->
<section style="padding:0 0 5rem">
  <div class="container">
    <div style="background:linear-gradient(135deg,rgba(212,160,23,.07),rgba(8,8,26,.97));border:1px solid rgba(212,160,23,.2);border-radius:20px;padding:3.5rem 2rem;text-align:center">
      <span class="badge badge-gold">🏷️ Limited Offer</span>
      <h2 style="font-family:'Playfair Display',serif;font-size:clamp(1.75rem,4vw,2.875rem);font-weight:800;color:#fff;margin:1rem 0 .5rem">
        Up to <span class="gold-text">40% OFF</span> Weekend Stays
      </h2>
      <p style="color:rgba(255,255,255,.55);margin-bottom:2rem">
        Use code <strong style="color:var(--gold);background:rgba(212,160,23,.1);padding:.1rem .5rem;border-radius:5px;font-family:monospace">LUXWEEKEND</strong> at checkout
      </p>
      <a href="<?=BASE_URL?>/hotels.php?sort=price_asc" class="btn btn-primary btn-lg">Explore Deals</a>
    </div>
  </div>
</section>

<!-- ── DESTINATIONS ── -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-header">
      <div><div class="section-label">📈 Trending</div><h2 class="section-title">Popular Destinations</h2></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem">
      <?php foreach([['Kathmandu',48,'https://images.unsplash.com/photo-1605640840605-14ac1855827b?w=400&q=80'],['Pokhara',32,'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=400&q=80'],['Chitwan',24,'https://images.unsplash.com/photo-1553697388-94e804e2f0f6?w=400&q=80'],['Lumbini',16,'https://images.unsplash.com/photo-1588088884372-29a8c67c28ed?w=400&q=80'],['Nagarkot',12,'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&q=80'],['Bhaktapur',18,'https://images.unsplash.com/photo-1580654712603-eb43273aff33?w=400&q=80']] as [$city,$cnt,$img]):?>
        <a href="<?=BASE_URL?>/hotels.php?city=<?=urlencode($city)?>" style="display:block;border-radius:14px;overflow:hidden;position:relative;height:180px">
          <img src="<?=$img?>" alt="<?=$city?>" style="width:100%;height:100%;object-fit:cover;transition:transform .4s" onmouseover="this.style.transform='scale(1.07)'" onmouseout="this.style.transform='scale(1)'">
          <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(8,8,26,.88),transparent)"></div>
          <div style="position:absolute;bottom:.75rem;left:.875rem">
            <div style="color:#fff;font-weight:700;font-size:.9rem"><?=$city?></div>
            <div style="color:rgba(255,255,255,.5);font-size:.72rem"><?=$cnt?> Hotels</div>
          </div>
        </a>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ── TESTIMONIALS ── -->
<section class="section" style="background:var(--bg2)">
  <div class="container">
    <div class="tc mb3"><div class="section-label" style="justify-content:center">⭐ Reviews</div><h2 class="section-title">What Travelers Say</h2></div>
    <div class="g3">
      <?php foreach([['Priya Sharma','Travel Blogger','https://i.pravatar.cc/48?img=1',5,'LuxStay changed how I book hotels in Nepal. Premium from start to finish!'],['Rahul Thapa','Business Executive','https://i.pravatar.cc/48?img=5',5,'Seamless eSewa payment, instant confirmation. Booked Kings Hotel in 2 minutes.'],['Anita Gurung','Newlywed','https://i.pravatar.cc/48?img=9',5,'Presidential Suite at Royal Palace made our honeymoon absolutely magical!']] as [$n,$r,$av,$s,$c]):?>
        <div class="card p3 rounded">
          <div style="color:var(--gold);margin-bottom:.875rem"><?=str_repeat('★',$s)?></div>
          <p style="font-size:.875rem;color:var(--text2);line-height:1.75;font-style:italic;margin-bottom:1.25rem">"<?=$c?>"</p>
          <div class="flex aic gap-2">
            <img src="<?=$av?>" style="width:40px;height:40px;border-radius:50%;border:2px solid rgba(212,160,23,.3)">
            <div><div style="font-weight:700;font-size:.875rem"><?=$n?></div><div style="font-size:.72rem;color:var(--text3)"><?=$r?></div></div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="section tc">
  <div class="container" style="max-width:580px">
    <div style="font-size:3rem;margin-bottom:1rem">🌏</div>
    <h2 style="font-family:'Playfair Display',serif;font-size:2.25rem;font-weight:800;margin-bottom:1rem">Ready for <span class="gold-text">Luxury?</span></h2>
    <p style="color:var(--text3);margin-bottom:2rem;line-height:1.8">Join 50,000+ travelers who trust LuxStay for extraordinary stays across Nepal.</p>
    <div class="flex gap-2" style="justify-content:center;flex-wrap:wrap">
      <a href="<?=BASE_URL?>/register.php" class="btn btn-primary btn-lg">Register Free</a>
      <a href="<?=BASE_URL?>/hotels.php"   class="btn btn-outline btn-lg">Browse Hotels</a>
    </div>
  </div>
</section>

<?php include __DIR__.'/includes/footer.php';?>
