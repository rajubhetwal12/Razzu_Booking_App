<?php
$pageTitle = 'Hotel Details — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$hotel = $id ? $db->prepare('SELECT * FROM hotels WHERE id=? AND is_active=1') : null;
if ($hotel) { $hotel->execute([$id]); $hotel = $hotel->fetch(); }
if (!$hotel) {
    // demo fallback
    $hotel = ['id'=>1,'name'=>'Kings Hotel','stars'=>5,'rating'=>4.8,'review_count'=>124,
        'cover_image'=>'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80',
        'images'=>'["https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80","https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&q=80","https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200&q=80"]',
        'description'=>'Experience unparalleled luxury at Kings Hotel, a five-star sanctuary in the heart of Kathmandu. Our hotel blends traditional Nepali artistry with contemporary elegance.','short_desc'=>'A majestic five-star sanctuary in Kathmandu.',
        'amenities'=>'["WiFi","Pool","Spa","Gym","Parking","Restaurant","Air conditioning","Laundry","Airport pickup"]',
        'city'=>'Kathmandu','country'=>'Nepal','address'=>'Thamel, Kathmandu 44600',
        'phone'=>'+977-1-4000001','email'=>'info@kingshotel.com','website'=>'www.kingshotel.com',
        'min_price'=>8000,'max_price'=>45000,'discount'=>15,'category'=>'luxury',
        'check_in_time'=>'14:00','check_out_time'=>'11:00',
        'policies'=>'No smoking. Pets not allowed. Free cancellation 48 hours before check-in.'];
    $id = 1;
}
$pageTitle = e($hotel['name']) . ' — LuxStay';
$images = json_decode($hotel['images'] ?? '[]', true) ?: [$hotel['cover_image']];
$amenities = json_decode($hotel['amenities'] ?? '[]', true) ?: [];
$rooms = $db->prepare('SELECT * FROM rooms WHERE hotel_id=? AND is_available=1 ORDER BY base_price ASC');
$rooms->execute([$id]); $rooms = $rooms->fetchAll();
$reviews = $db->prepare('SELECT r.*,u.name as uname,u.avatar FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.hotel_id=? ORDER BY r.created_at DESC LIMIT 5');
$reviews->execute([$id]); $reviews = $reviews->fetchAll();

if(empty($rooms)) $rooms = [
    ['id'=>1,'type'=>'standard','name'=>'Standard Room','description'=>'Cozy and elegant with city views.','base_price'=>8000,'max_guests'=>2,'image'=>'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80'],
    ['id'=>2,'type'=>'deluxe','name'=>'Deluxe Room','description'=>'Spacious with premium furnishings.','base_price'=>12000,'max_guests'=>3,'image'=>'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=600&q=80'],
    ['id'=>3,'type'=>'family','name'=>'Family Room','description'=>'Perfect for families.','base_price'=>16000,'max_guests'=>4,'image'=>'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=600&q=80'],
    ['id'=>4,'type'=>'presidential','name'=>'Presidential Suite','description'=>'Ultimate luxury.','base_price'=>45000,'max_guests'=>6,'image'=>'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80'],
];
include 'includes/header.php';
$AMENITY_ICONS = ['WiFi'=>'📶','Pool'=>'🏊','Spa'=>'💆','Gym'=>'🏋️','Parking'=>'🅿️','Restaurant'=>'🍽️','Air conditioning'=>'❄️','Laundry'=>'👔','Airport pickup'=>'✈️'];
?>
<div class="page-content">
  <!-- Image slider -->
  <div style="position:relative;height:clamp(280px,50vh,520px);overflow:hidden;background:var(--bg2)" id="slider">
    <?php foreach($images as $i=>$img): ?>
      <img src="<?=e($img)?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:opacity .4s;opacity:<?=$i===0?1:0?>" data-slide="<?=$i?>">
    <?php endforeach; ?>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 60%,var(--bg))"></div>
    <?php if(count($images)>1): ?>
      <button onclick="slide(-1)" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,.5);border:none;color:#fff;font-size:1.2rem;cursor:pointer">‹</button>
      <button onclick="slide(1)" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,.5);border:none;color:#fff;font-size:1.2rem;cursor:pointer">›</button>
      <div style="position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);display:flex;gap:.5rem" id="dots">
        <?php foreach($images as $i=>$_): ?>
          <button onclick="goSlide(<?=$i?>)" style="width:<?=$i===0?24:8?>px;height:8px;border-radius:4px;background:<?=$i===0?'#d4a017':'rgba(255,255,255,.4)?>';border:none;cursor:pointer;transition:all .3s" data-dot="<?=$i?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="container" style="padding-top:2rem;padding-bottom:4rem">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:2rem;align-items:start">
      <!-- Main -->
      <div>
        <!-- Title -->
        <div style="margin-bottom:1.5rem">
          <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem">
            <span style="color:var(--gold)"><?=str_repeat('★',$hotel['stars'])?></span>
            <span class="badge badge-gold"><?=e($hotel['category']??'luxury')?></span>
          </div>
          <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:var(--text);margin-bottom:.5rem"><?=e($hotel['name'])?></h1>
          <div style="display:flex;align-items:center;gap:1.5rem;color:var(--text3);font-size:.875rem;flex-wrap:wrap">
            <span>📍 <?=e($hotel['address']??$hotel['city'].', '.$hotel['country'])?></span>
            <span>⭐ <?=$hotel['rating']?> (<?=$hotel['review_count']?> reviews)</span>
          </div>
        </div>

        <!-- Description -->
        <div class="card p-3 rounded" style="margin-bottom:1.5rem">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:.75rem">About the Hotel</h2>
          <p style="color:var(--text2);line-height:1.8;font-size:.9375rem"><?=e($hotel['description']??'')?></p>
        </div>

        <!-- Amenities -->
        <div class="card p-3 rounded" style="margin-bottom:1.5rem">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1rem">Facilities & Amenities</h2>
          <div class="grid-3" style="gap:.625rem">
            <?php foreach($amenities as $a): ?>
              <div style="display:flex;align-items:center;gap:.625rem;padding:.625rem .875rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;font-size:.875rem;color:var(--text2)">
                <span><?=$AMENITY_ICONS[$a]??'✅'?></span> <?=e($a)?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Rooms -->
        <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;color:var(--text);margin-bottom:1.25rem">Available Rooms</h2>
        <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:2rem">
          <?php foreach($rooms as $r): ?>
            <div class="card rounded" style="display:flex;overflow:hidden;flex-wrap:wrap">
              <img src="<?=e($r['image']??'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&q=80')?>" alt="" style="width:180px;height:130px;object-fit:cover;flex-shrink:0">
              <div style="flex:1;padding:1.125rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
                <div>
                  <span class="badge badge-<?=$r['type']==='presidential'?'gold':($r['type']==='deluxe'?'blue':'green')?>" style="margin-bottom:.5rem"><?=e($r['type'])?></span>
                  <h3 style="font-weight:700;color:var(--text);margin-bottom:.25rem"><?=e($r['name']??'')?></h3>
                  <p style="font-size:.8rem;color:var(--text3);margin-bottom:.5rem"><?=e($r['description']??'')?></p>
                  <span style="font-size:.75rem;color:var(--text3)">👥 Up to <?=$r['max_guests']?> guests</span>
                </div>
                <div style="text-align:right">
                  <div style="font-size:1.375rem;font-weight:800;color:var(--gold)">NPR <?=number_format($r['base_price'])?></div>
                  <div style="font-size:.75rem;color:var(--text3);margin-bottom:.75rem">per night</div>
                  <a href="booking.php?hotel_id=<?=$hotel['id']?>&room_id=<?=$r['id']?>&room_name=<?=urlencode($r['name']??'')?>&price=<?=$r['base_price']?>"
                     class="btn-sm btn-primary">Book Now</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Reviews -->
        <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;color:var(--text);margin-bottom:1.25rem">Guest Reviews</h2>
        <?php if(empty($reviews)): ?>
          <?php $reviews = [
            ['uname'=>'Priya Sharma','avatar'=>'https://i.pravatar.cc/48?img=1','rating'=>5,'comment'=>'Absolutely stunning hotel! Service was impeccable.','created_at'=>'2024-12-15'],
            ['uname'=>'Rahul Thapa','avatar'=>'https://i.pravatar.cc/48?img=5','rating'=>5,'comment'=>'Best stay I\'ve ever had. The suite was breathtaking!','created_at'=>'2024-12-10'],
          ]; ?>
        <?php endif; ?>
        <div style="display:flex;flex-direction:column;gap:1rem">
          <?php foreach($reviews as $rv): ?>
            <div class="card p-3 rounded">
              <div style="display:flex;align-items:flex-start;gap:.875rem;margin-bottom:.75rem">
                <img src="<?=e($rv['avatar']??'https://i.pravatar.cc/48?img=1')?>" style="width:40px;height:40px;border-radius:50%;border:2px solid rgba(212,160,23,.3)">
                <div style="flex:1">
                  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <span style="font-weight:700;color:var(--text);font-size:.9375rem"><?=e($rv['uname']??$rv['name']??'Guest')?></span>
                    <span style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($rv['created_at']))?></span>
                  </div>
                  <div style="color:var(--gold);font-size:.875rem"><?=str_repeat('★',$rv['rating'])?><?=str_repeat('☆',5-$rv['rating'])?></div>
                </div>
              </div>
              <p style="color:var(--text2);font-size:.875rem;line-height:1.7"><?=e($rv['comment']??'')?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sidebar -->
      <div style="position:sticky;top:80px">
        <!-- Price card -->
        <div class="card p-3 rounded" style="border-color:rgba(212,160,23,.25);margin-bottom:1.25rem">
          <p style="font-size:.8125rem;color:var(--text3);margin-bottom:.25rem">Starting from</p>
          <div style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:800" class="gold-text">NPR <?=number_format($hotel['min_price'])?></div>
          <p style="font-size:.75rem;color:var(--text3);margin-bottom:1.25rem">per night · includes taxes</p>
          <a href="booking.php?hotel_id=<?=$hotel['id']?>" class="btn-full btn-primary" style="margin-bottom:.75rem;border-radius:10px">Book Now</a>
          <p style="font-size:.75rem;color:var(--text3);text-align:center">Free cancellation · No hidden fees</p>
          <hr class="divider" style="margin:1rem 0">
          <div style="display:flex;justify-content:space-between;font-size:.875rem;margin-bottom:.5rem">
            <span style="color:var(--text3)">Check-in</span><span style="color:var(--text);font-weight:600"><?=e($hotel['check_in_time']??'14:00')?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:.875rem">
            <span style="color:var(--text3)">Check-out</span><span style="color:var(--text);font-weight:600"><?=e($hotel['check_out_time']??'11:00')?></span>
          </div>
        </div>

        <!-- Contact -->
        <div class="card p-3 rounded" style="margin-bottom:1.25rem">
          <h3 style="font-weight:700;color:var(--text);margin-bottom:1rem">Contact Hotel</h3>
          <div style="display:flex;flex-direction:column;gap:.75rem">
            <?php if($hotel['phone']): ?><a href="tel:<?=e($hotel['phone'])?>" style="display:flex;align-items:center;gap:.625rem;font-size:.875rem;color:var(--text2);text-decoration:none"><span style="color:var(--gold)">📞</span><?=e($hotel['phone'])?></a><?php endif; ?>
            <?php if($hotel['email']): ?><a href="mailto:<?=e($hotel['email'])?>" style="display:flex;align-items:center;gap:.625rem;font-size:.875rem;color:var(--text2);text-decoration:none"><span style="color:var(--gold)">✉️</span><?=e($hotel['email'])?></a><?php endif; ?>
          </div>
        </div>

        <!-- Policies -->
        <div class="card p-3 rounded">
          <h3 style="font-weight:700;color:var(--text);margin-bottom:.75rem">Policies</h3>
          <p style="font-size:.8125rem;color:var(--text3);line-height:1.7"><?=e($hotel['policies']??'No smoking. Pets not allowed.')?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let cur=0;
const slides=document.querySelectorAll('[data-slide]');
const dots=document.querySelectorAll('[data-dot]');
function goSlide(n){
  slides[cur].style.opacity=0; if(dots[cur])dots[cur].style.width='8px';
  cur=n; slides[cur].style.opacity=1; if(dots[cur]){dots[cur].style.width='24px';dots[cur].style.background='#d4a017';}
}
function slide(d){ goSlide((cur+d+slides.length)%slides.length); }
setInterval(()=>slides.length>1&&slide(1),5000);
</script>
<?php include 'includes/footer.php'; ?>
