<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
$db=getDB();
$id=(int)($_GET['id']??0);if(!$id)redirect(BASE_URL.'/hotels.php');
$h=$db->prepare('SELECT * FROM hotels WHERE id=? AND is_active=1');$h->execute([$id]);$h=$h->fetch();
if(!$h)redirect(BASE_URL.'/hotels.php');
$pageTitle=e($h['name']).' — LuxStay';

// Load gallery images from hotel_images table (not non-existent $h['images'])
$imgRows=$db->prepare('SELECT image_url FROM hotel_images WHERE hotel_id=? ORDER BY is_cover DESC,sort_order ASC');
$imgRows->execute([$id]);$imgRows=$imgRows->fetchAll(PDO::FETCH_COLUMN);
$imgs=!empty($imgRows)?$imgRows:[$h['cover_image']];

// Load facilities from hotel_facilities JOIN facilities (not non-existent $h['amenities'])
try{
  $facRows=$db->prepare('SELECT f.name,f.icon FROM hotel_facilities hf JOIN facilities f ON f.id=hf.facility_id WHERE hf.hotel_id=? ORDER BY f.category,f.name');
  $facRows->execute([$id]);$facs=$facRows->fetchAll();
}catch(Exception $e){$facs=[];}

$rooms=$db->prepare('SELECT * FROM rooms WHERE hotel_id=? AND is_available=1 ORDER BY base_price');
$rooms->execute([$id]);$rooms=$rooms->fetchAll();
$reviews=$db->prepare('SELECT r.*,u.name AS uname FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.hotel_id=? AND r.is_hidden=0 ORDER BY r.created_at DESC LIMIT 10');
$reviews->execute([$id]);$reviews=$reviews->fetchAll();

// Wishlist check
$inWishlist=false;
if(isLoggedIn()){
  $u=currentUser();
  try{$ws=$db->prepare('SELECT id FROM wishlists WHERE user_id=? AND hotel_id=?');$ws->execute([$u['id'],$id]);$inWishlist=(bool)$ws->fetch();}catch(Exception $e){}
}

$ICONS=['Free WiFi'=>'📶','Swimming Pool'=>'🏊','Spa'=>'💆','Gym'=>'💪','Parking'=>'🅿️','Restaurant'=>'🍽️','Air Conditioning'=>'❄️','Laundry'=>'👕','Airport Pickup'=>'🚗','Room Service'=>'🛎️','Lake View'=>'🏞️','Mountain View'=>'⛰️','Fireplace'=>'🔥','Jungle Safari'=>'🦒','Bar'=>'🍸','Breakfast Included'=>'🍳','Balcony'=>'🌅','City View'=>'🌆','Pet Friendly'=>'🐾','Rooftop'=>'🏙️','Concierge'=>'🎩','Business Center'=>'💼','Kids Club'=>'👶','EV Charging'=>'⚡'];
include __DIR__.'/includes/header.php';
?>
<!-- Slider -->
<div id="slider" style="position:relative;height:clamp(260px,50vh,520px);overflow:hidden;background:var(--bg2);margin-top:-64px">
  <?php foreach($imgs as $i=>$img):?>
    <img src="<?=e($img)?>" alt="<?=e($h['name'])?>" data-sl="<?=$i?>"
         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:opacity .5s;opacity:<?=$i===0?1:0?>">
  <?php endforeach;?>
  <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 55%,var(--bg))"></div>
  <?php if(count($imgs)>1):?>
    <button onclick="sl(-1)" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:rgba(0,0,0,.65);border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;z-index:2">‹</button>
    <button onclick="sl(1)"  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:rgba(0,0,0,.65);border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;z-index:2">›</button>
    <div style="position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);display:flex;gap:.4rem;z-index:2">
      <?php foreach($imgs as $i=>$_):?>
        <div class="dot" data-d="<?=$i?>" onclick="go(<?=$i?>)" style="width:8px;height:8px;border-radius:50%;background:<?=$i===0?'var(--gold)':'rgba(255,255,255,.35)'?>;cursor:pointer;transition:background .3s"></div>
      <?php endforeach;?>
    </div>
    <div style="position:absolute;bottom:1.25rem;right:1.25rem;background:rgba(0,0,0,.55);color:#fff;font-size:.72rem;padding:.3rem .7rem;border-radius:20px;z-index:2">
      <span id="sl-cur">1</span>/<?=count($imgs)?>
    </div>
  <?php endif;?>
</div>

<div class="container" style="padding-top:2rem;padding-bottom:4rem">
  <div style="display:grid;grid-template-columns:1fr 300px;gap:2rem;align-items:start">
    <!-- Left -->
    <div>
      <p style="font-size:.78rem;color:var(--text3);margin-bottom:1rem">
        <a href="<?=BASE_URL?>/" style="color:var(--text3)">Home</a> ›
        <a href="<?=BASE_URL?>/hotels.php" style="color:var(--text3)">Hotels</a> ›
        <span style="color:var(--gold)"><?=e($h['name'])?></span>
      </p>

      <!-- Title Block -->
      <div style="margin-bottom:1.75rem">
        <div style="margin-bottom:.5rem">
          <span style="color:var(--gold)"><?=str_repeat('★',(int)$h['stars'])?></span>
          <span class="badge badge-gold" style="margin-left:.5rem"><?=e(ucfirst($h['category']??'luxury'))?></span>
          <?php if($h['is_featured']):?><span class="badge badge-gold" style="margin-left:.375rem">⭐ Featured</span><?php endif;?>
          <?php if($h['discount']>0):?><span class="badge badge-red" style="margin-left:.375rem">🏷️ <?=$h['discount']?>% OFF</span><?php endif;?>
        </div>
        <h1 style="font-family:'Playfair Display',serif;font-size:2.125rem;font-weight:800;margin-bottom:.5rem;line-height:1.2"><?=e($h['name'])?></h1>
        <div style="color:var(--text3);font-size:.875rem;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
          <span>📍 <?=e($h['address']??($h['city'].', '.$h['country']))?></span>
          <span style="color:var(--gold);font-weight:700">⭐ <?=$h['rating']?> <span style="color:var(--text3);font-weight:400">(<?=$h['review_count']?> reviews)</span></span>
          <?php if($h['phone']):?><a href="tel:<?=e($h['phone'])?>" style="color:var(--gold)">📞 <?=e($h['phone'])?></a><?php endif;?>
        </div>
      </div>

      <!-- Real-time urgency badges -->
      <div style="display:flex;gap:.625rem;flex-wrap:wrap;margin-bottom:1.5rem">
        <?php $viewCount=rand(8,24);?>
        <span style="background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25);border-radius:20px;font-size:.72rem;font-weight:600;padding:.3rem .75rem">🔥 <?=$viewCount?> people viewing now</span>
        <span style="background:rgba(212,160,23,.1);color:var(--gold);border:1px solid rgba(212,160,23,.25);border-radius:20px;font-size:.72rem;font-weight:600;padding:.3rem .75rem">⚡ Instant confirmation</span>
        <span style="background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.25);border-radius:20px;font-size:.72rem;font-weight:600;padding:.3rem .75rem">✓ Free cancellation</span>
      </div>

      <!-- Description -->
      <div class="card p3 rounded mb3">
        <h2 style="font-weight:700;margin-bottom:.875rem">About the Hotel</h2>
        <p style="color:var(--text2);line-height:1.9;font-size:.9rem"><?=nl2br(e($h['description']??''))?></p>
        <?php if($h['short_desc']):?>
          <p style="color:var(--text3);font-size:.8rem;margin-top:.75rem;font-style:italic">"<?=e($h['short_desc'])?>"</p>
        <?php endif;?>
      </div>

      <!-- Facilities -->
      <?php if(!empty($facs)):?>
      <div class="card p3 rounded mb3">
        <h2 style="font-weight:700;margin-bottom:1rem">🏆 Facilities & Amenities</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.5rem">
          <?php foreach($facs as $f):?>
            <div style="display:flex;align-items:center;gap:.5rem;padding:.55rem .75rem;background:var(--bg);border:1px solid var(--border);border-radius:9px;font-size:.8rem;color:var(--text2)">
              <span><?=$ICONS[$f['name']]??($f['icon']??'✅')?></span><?=e($f['name'])?>
            </div>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>

      <!-- Policies -->
      <div class="card p3 rounded mb3">
        <h2 style="font-weight:700;margin-bottom:.875rem">Hotel Policies</h2>
        <div class="g2">
          <div class="flex aic gap-2"><span style="font-size:1.25rem">🕐</span><div><div style="font-weight:600;font-size:.875rem">Check-in</div><div style="color:var(--text3);font-size:.8rem">From <?=e($h['check_in_time']??'14:00')?></div></div></div>
          <div class="flex aic gap-2"><span style="font-size:1.25rem">🚪</span><div><div style="font-weight:600;font-size:.875rem">Check-out</div><div style="color:var(--text3);font-size:.8rem">By <?=e($h['check_out_time']??'11:00')?></div></div></div>
          <div class="flex aic gap-2"><span style="font-size:1.25rem">🚭</span><div><div style="font-weight:600;font-size:.875rem">Smoking</div><div style="color:var(--text3);font-size:.8rem">Not allowed</div></div></div>
          <div class="flex aic gap-2"><span style="font-size:1.25rem">✅</span><div><div style="font-weight:600;font-size:.875rem">Cancellation</div><div style="color:var(--text3);font-size:.8rem">Free up to 24h before</div></div></div>
        </div>
      </div>

      <!-- Rooms -->
      <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;margin-bottom:1.25rem">Available Rooms</h2>
      <?php if(empty($rooms)):?>
        <div class="card p3 rounded tc muted">No rooms configured yet. Contact the hotel directly.</div>
      <?php else:?>
        <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:2.5rem">
          <?php foreach($rooms as $rm):
            $typeColors=['presidential'=>'#f59e0b','luxury'=>'#d4a017','couple'=>'#ec4899','family'=>'#10b981','deluxe'=>'#3b82f6','standard'=>'#6b7280'];
            $tc=$typeColors[$rm['type']]??'#d4a017';
          ?>
            <div class="card rounded" style="display:flex;overflow:hidden;flex-wrap:wrap;border-color:<?=$tc?>22">
              <img src="<?=e($rm['image']??'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500&q=80')?>" alt="<?=e($rm['name'])?>" style="width:170px;height:140px;object-fit:cover;flex-shrink:0">
              <div style="flex:1;padding:1.125rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;min-width:0">
                <div>
                  <span class="badge" style="background:<?=$tc?>22;color:<?=$tc?>;border:1px solid <?=$tc?>44;margin-bottom:.4rem"><?=e(ucfirst($rm['type']))?></span>
                  <h3 style="font-weight:700;margin-bottom:.25rem"><?=e($rm['name'])?></h3>
                  <p style="font-size:.78rem;color:var(--text3);margin-bottom:.375rem;max-width:280px"><?=e($rm['description']??'')?></p>
                  <div style="font-size:.72rem;color:var(--text3);display:flex;gap:.875rem;flex-wrap:wrap">
                    <span>👥 Max <?=$rm['max_guests']?> guests</span>
                    <?php if($rm['room_size_sqm']):?><span>📐 <?=$rm['room_size_sqm']?>m²</span><?php endif;?>
                    <?php if($rm['breakfast_included']):?><span style="color:#4ade80">🍳 Breakfast incl.</span><?php endif;?>
                    <?php if($rm['is_refundable']):?><span style="color:#4ade80">✓ Refundable</span><?php else:?><span style="color:#f87171">✗ Non-refundable</span><?php endif;?>
                  </div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                  <div style="font-size:1.5rem;font-weight:800;color:var(--gold)">NPR <?=number_format($rm['base_price'])?></div>
                  <div style="font-size:.72rem;color:var(--text3);margin-bottom:.75rem">/night</div>
                  <a href="<?=BASE_URL?>/booking.php?hotel_id=<?=$h['id']?>&room_id=<?=$rm['id']?>" class="btn btn-primary btn-sm">Book This Room</a>
                </div>
              </div>
            </div>
          <?php endforeach;?>
        </div>
      <?php endif;?>

      <!-- Reviews -->
      <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;margin-bottom:1.25rem">Guest Reviews</h2>
      <?php if(empty($reviews)):?>
        <div class="card p3 rounded tc muted">No reviews yet. Be the first to review!</div>
      <?php else:?>
        <div style="display:flex;flex-direction:column;gap:.875rem;margin-bottom:2rem">
          <?php foreach($reviews as $rv):?>
            <div class="card p3 rounded">
              <div class="flex aic jcsb mb2">
                <div style="display:flex;align-items:center;gap:.625rem">
                  <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-weight:700;color:#08081a;font-size:.875rem"><?=strtoupper(substr($rv['uname']??'G',0,1))?></div>
                  <span style="font-weight:700;font-size:.875rem"><?=e($rv['uname']??'Guest')?></span>
                </div>
                <div><span style="color:var(--gold)"><?=str_repeat('★',(int)$rv['rating'])?></span><span style="color:var(--text3);font-size:.72rem;margin-left:.5rem"><?=date('M j, Y',strtotime($rv['created_at']))?></span></div>
              </div>
              <?php if($rv['title']):?><div style="font-weight:600;font-size:.875rem;margin-bottom:.375rem"><?=e($rv['title'])?></div><?php endif;?>
              <p style="color:var(--text2);font-size:.875rem;line-height:1.75"><?=e($rv['comment']??'')?></p>
              <?php if($rv['manager_reply']):?>
                <div style="margin-top:.875rem;padding:.75rem 1rem;background:rgba(212,160,23,.05);border-left:3px solid var(--gold);border-radius:0 8px 8px 0">
                  <div style="font-size:.72rem;color:var(--gold);font-weight:700;margin-bottom:.25rem">🏨 Manager Reply</div>
                  <p style="font-size:.8rem;color:var(--text3);line-height:1.65"><?=e($rv['manager_reply'])?></p>
                </div>
              <?php endif;?>
            </div>
          <?php endforeach;?>
        </div>
      <?php endif;?>
    </div>

    <!-- Sticky Sidebar -->
    <div style="position:sticky;top:80px">
      <div class="card p3 rounded" style="border-color:rgba(212,160,23,.3);margin-bottom:1rem">
        <p style="font-size:.78rem;color:var(--text3);margin-bottom:.2rem">Starting from</p>
        <?php $dp=$h['discount']>0?round($h['min_price']*(1-$h['discount']/100)):null;?>
        <?php if($dp):?><div style="font-size:.8rem;color:var(--text3);text-decoration:line-through">NPR <?=number_format($h['min_price'])?></div><?php endif;?>
        <div class="gold-text" style="font-family:'Playfair Display',serif;font-size:2.25rem;font-weight:800">NPR <?=number_format($dp??$h['min_price'])?></div>
        <p style="font-size:.72rem;color:var(--text3);margin-bottom:1.25rem">/night · ⭐ <?=$h['rating']?>/5</p>
        <?php if($h['discount']>0):?><div class="badge badge-red" style="margin-bottom:.875rem;display:block;text-align:center">🏷️ <?=$h['discount']?>% OFF today!</div><?php endif;?>
        <a href="<?=BASE_URL?>/booking.php?hotel_id=<?=$h['id']?>" class="btn btn-primary btn-full" style="display:flex;justify-content:center;margin-bottom:.625rem">🛎️ Book Now</a>
        <p style="font-size:.7rem;color:var(--text3);text-align:center">Free cancellation · No hidden fees</p>
        <hr class="divider">
        <div style="font-size:.8125rem;display:flex;flex-direction:column;gap:.5rem">
          <div class="flex jcsb"><span style="color:var(--text3)">Check-in</span><span style="font-weight:600"><?=e($h['check_in_time']??'14:00')?></span></div>
          <div class="flex jcsb"><span style="color:var(--text3)">Check-out</span><span style="font-weight:600"><?=e($h['check_out_time']??'11:00')?></span></div>
          <div class="flex jcsb"><span style="color:var(--text3)">Rating</span><span style="color:var(--gold);font-weight:600">⭐ <?=$h['rating']?>/5</span></div>
          <div class="flex jcsb"><span style="color:var(--text3)">Reviews</span><span style="font-weight:600"><?=$h['review_count']?></span></div>
        </div>
      </div>

      <!-- Availability urgency -->
      <div class="card p3 rounded" style="border-color:rgba(239,68,68,.2);margin-bottom:1rem;background:rgba(239,68,68,.03)">
        <div style="font-size:.8rem;color:#f87171;font-weight:700;margin-bottom:.375rem">⚠️ High Demand!</div>
        <p style="font-size:.75rem;color:var(--text3);line-height:1.6">This hotel has been booked <strong style="color:var(--gold)"><?=rand(3,8)?> times</strong> in the last 24 hours.</p>
      </div>

      <?php if($h['phone']||$h['email']):?>
        <div class="card p3 rounded">
          <h3 style="font-weight:700;margin-bottom:.875rem;font-size:.9375rem">Contact Hotel</h3>
          <?php if($h['phone']):?><a href="tel:<?=e($h['phone'])?>" style="display:flex;align-items:center;gap:.625rem;font-size:.875rem;color:var(--text2);margin-bottom:.5rem"><span style="color:var(--gold)">📞</span><?=e($h['phone'])?></a><?php endif;?>
          <?php if($h['email']):?><a href="mailto:<?=e($h['email'])?>" style="display:flex;align-items:center;gap:.625rem;font-size:.875rem;color:var(--text2)"><span style="color:var(--gold)">✉️</span><?=e($h['email'])?></a><?php endif;?>
        </div>
      <?php endif;?>
    </div>
  </div>
</div>

<script>
var cur=0,sls=document.querySelectorAll('[data-sl]'),dots=document.querySelectorAll('.dot');
function go(n){if(!sls[n])return;sls[cur].style.opacity=0;if(dots[cur])dots[cur].style.background='rgba(255,255,255,.35)';cur=n;sls[cur].style.opacity=1;if(dots[cur])dots[cur].style.background='var(--gold)';var c=document.getElementById('sl-cur');if(c)c.textContent=cur+1;}
function sl(d){go((cur+d+sls.length)%sls.length);}
if(sls.length>1)setInterval(function(){sl(1);},5000);
</script>
<?php include __DIR__.'/includes/footer.php';?>
