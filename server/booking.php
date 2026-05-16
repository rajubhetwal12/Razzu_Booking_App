<?php
$pageTitle = 'Book Hotel — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
$db = getDB();

$hotelId = (int)($_GET['hotel_id'] ?? 0);
$roomId  = (int)($_GET['room_id'] ?? 0);
$roomName = urldecode($_GET['room_name'] ?? 'Standard Room');
$price   = (float)($_GET['price'] ?? 0);

$hotel = $hotelId ? $db->prepare('SELECT * FROM hotels WHERE id=?') : null;
if ($hotel) { $hotel->execute([$hotelId]); $hotel = $hotel->fetch(); }
if (!$hotel) { redirect(BASE_URL . '/hotels.php'); }

$rooms = $db->prepare('SELECT * FROM rooms WHERE hotel_id=? AND is_available=1 ORDER BY base_price');
$rooms->execute([$hotelId]); $rooms = $rooms->fetchAll();
if (empty($rooms)) $rooms = [
    ['id'=>1,'type'=>'standard','name'=>'Standard Room','base_price'=>8000,'max_guests'=>2],
    ['id'=>2,'type'=>'deluxe','name'=>'Deluxe Room','base_price'=>12000,'max_guests'=>3],
    ['id'=>3,'type'=>'family','name'=>'Family Room','base_price'=>16000,'max_guests'=>4],
    ['id'=>4,'type'=>'presidential','name'=>'Presidential Suite','base_price'=>45000,'max_guests'=>6],
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkIn   = $_POST['check_in'] ?? '';
    $checkOut  = $_POST['check_out'] ?? '';
    $adults    = (int)($_POST['adults'] ?? 1);
    $children  = (int)($_POST['children'] ?? 0);
    $selRoomId = (int)($_POST['room_id'] ?? $roomId);
    $extras    = $_POST['extras'] ?? [];

    $nights = (new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
    if ($nights < 1) { $error = 'Check-out must be after check-in.'; }
    else {
        $selRoom = null;
        foreach ($rooms as $r) { if ($r['id'] == $selRoomId) { $selRoom = $r; break; } }
        if (!$selRoom) { $selRoom = $rooms[0]; $selRoomId = $selRoom['id']; }

        $roomCost = $selRoom['base_price'] * $nights;
        if ($adults > 2) $roomCost *= (1 + ($adults - 2) * 0.1);
        $extraCost = count($extras) * 500;
        $tax = round(($roomCost + $extraCost) * 0.13);
        $service = round(($roomCost + $extraCost) * 0.05);
        $total = $roomCost + $extraCost + $tax + $service;
        $ref = generateBookingRef();
        $user = currentUser();

        $stmt = $db->prepare('INSERT INTO bookings (booking_ref,customer_id,hotel_id,room_id,check_in,check_out,nights,adults,children,room_cost,tax_amount,service_charge,total_amount,extra_services,status,payment_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$ref,$user['id'],$hotelId,$selRoomId,$checkIn,$checkOut,$nights,$adults,$children,$roomCost,$tax,$service,$total,json_encode($extras),'pending','unpaid']);
        $bookingId = $db->lastInsertId();
        redirect(BASE_URL . '/payment.php?booking_id=' . $bookingId);
    }
}
$today = date('Y-m-d');
$tom = date('Y-m-d', strtotime('+1 day'));
include 'includes/header.php';
?>
<div class="page-content">
<div class="container" style="max-width:860px;padding-top:2.5rem;padding-bottom:4rem">
  <div style="margin-bottom:1.5rem">
    <a href="hotel-detail.php?id=<?=$hotelId?>" style="color:var(--text3);text-decoration:none;font-size:.875rem">← Back to <?=e($hotel['name'])?></a>
  </div>
  <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;color:var(--text);margin-bottom:.375rem">Book Your Stay</h1>
  <p style="color:var(--text3);font-size:.9rem;margin-bottom:2rem">at <strong style="color:var(--gold)"><?=e($hotel['name'])?></strong> · <?=e($hotel['city'])?>, <?=e($hotel['country'])?></p>

  <?php if($error): ?><div class="alert alert-error"><?=e($error)?></div><?php endif; ?>

  <form method="POST" id="book-form">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
      <div>
        <!-- Step 1: Dates & Guests -->
        <div class="card p-3 rounded" style="margin-bottom:1.25rem">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1.25rem;font-size:1.0625rem">📅 Stay Details</h2>
          <div class="grid-2">
            <div class="form-group"><label class="form-label">Check-in Date</label>
              <input type="date" name="check_in" id="ci" class="form-input" min="<?=$today?>" value="<?=$today?>" required onchange="calcPrice()">
            </div>
            <div class="form-group"><label class="form-label">Check-out Date</label>
              <input type="date" name="check_out" id="co" class="form-input" min="<?=$tom?>" value="<?=$tom?>" required onchange="calcPrice()">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group"><label class="form-label">Adults</label>
              <select name="adults" id="adults" class="form-input" onchange="calcPrice()">
                <?php for($i=1;$i<=6;$i++): ?><option value="<?=$i?>" <?=$i==2?'selected':''?>><?=$i?> Adult<?=$i>1?'s':''?></option><?php endfor; ?>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Children</label>
              <select name="children" class="form-input">
                <?php for($i=0;$i<=4;$i++): ?><option value="<?=$i?>"><?=$i?></option><?php endfor; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Room selection -->
        <div class="card p-3 rounded" style="margin-bottom:1.25rem">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1.25rem;font-size:1.0625rem">🛏️ Select Room Type</h2>
          <div style="display:flex;flex-direction:column;gap:.625rem">
            <?php foreach($rooms as $i=>$r): ?>
              <label style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s" id="room-label-<?=$r['id']?>">
                <div style="display:flex;align-items:center;gap:.875rem">
                  <input type="radio" name="room_id" value="<?=$r['id']?>" data-price="<?=$r['base_price']?>"
                    <?=($r['id']==$roomId||$i===0&&!$roomId)?'checked':''?> onchange="calcPrice()" style="accent-color:#d4a017;width:16px;height:16px">
                  <div>
                    <div style="font-weight:700;color:var(--text);font-size:.9375rem"><?=e($r['name']??'')?></div>
                    <div style="font-size:.75rem;color:var(--text3)">Up to <?=$r['max_guests']?> guests · <?=e($r['type'])?>
                    </div>
                  </div>
                </div>
                <div style="text-align:right;font-weight:700;color:var(--gold)">NPR <?=number_format($r['base_price'])?><span style="font-weight:400;color:var(--text3);font-size:.75rem">/night</span></div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Extra services -->
        <div class="card p-3 rounded">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1.25rem;font-size:1.0625rem">✨ Extra Services <small style="font-weight:400;color:var(--text3);font-size:.8rem">NPR 500 each</small></h2>
          <div class="grid-2" style="gap:.5rem">
            <?php foreach(['Airport Pickup','Breakfast','Late Checkout','Room Decoration','Spa Package','City Tour'] as $svc): ?>
              <label style="display:flex;align-items:center;gap:.5rem;padding:.625rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:.875rem;color:var(--text2)">
                <input type="checkbox" name="extras[]" value="<?=$svc?>" onchange="calcPrice()" style="accent-color:#d4a017"> <?=$svc?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Price Summary -->
      <div style="position:sticky;top:80px">
        <div class="card p-3 rounded" style="border-color:rgba(212,160,23,.25)">
          <h3 style="font-weight:700;color:var(--text);margin-bottom:1.25rem">Price Summary</h3>
          <div style="display:flex;flex-direction:column;gap:.625rem;font-size:.875rem;margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Room Cost</span><span id="sum-room" style="color:var(--text);font-weight:600">NPR 0</span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Nights</span><span id="sum-nights" style="color:var(--text);font-weight:600">1</span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Extra Services</span><span id="sum-extra" style="color:var(--text);font-weight:600">NPR 0</span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Tax (13%)</span><span id="sum-tax" style="color:var(--text);font-weight:600">NPR 0</span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Service (5%)</span><span id="sum-svc" style="color:var(--text);font-weight:600">NPR 0</span></div>
          </div>
          <hr class="divider" style="margin:.75rem 0">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
            <span style="font-weight:700;color:var(--text)">Total</span>
            <span id="sum-total" style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800" class="gold-text">NPR 0</span>
          </div>
          <button type="submit" class="btn-full btn-primary">Confirm & Pay →</button>
          <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:.75rem">Free cancellation · Secure checkout</p>
        </div>
      </div>
    </div>
  </form>
</div>
</div>
<script>
function calcPrice(){
  const ci=document.getElementById('ci').value;
  const co=document.getElementById('co').value;
  if(!ci||!co)return;
  const d1=new Date(ci),d2=new Date(co);
  const nights=Math.max(1,Math.round((d2-d1)/(864e5)));
  const radio=document.querySelector('input[name="room_id"]:checked');
  let rp=radio?parseInt(radio.dataset.price):<?=$rooms[0]['base_price']??8000?>;
  const adults=parseInt(document.getElementById('adults').value)||1;
  if(adults>2) rp*=(1+(adults-2)*0.1);
  const roomCost=Math.round(rp*nights);
  const extras=document.querySelectorAll('input[name="extras[]"]:checked').length*500;
  const tax=Math.round((roomCost+extras)*0.13);
  const svc=Math.round((roomCost+extras)*0.05);
  const total=roomCost+extras+tax+svc;
  const f=n=>'NPR '+n.toLocaleString();
  document.getElementById('sum-room').textContent=f(roomCost);
  document.getElementById('sum-nights').textContent=nights+' night'+(nights>1?'s':'');
  document.getElementById('sum-extra').textContent=f(extras);
  document.getElementById('sum-tax').textContent=f(tax);
  document.getElementById('sum-svc').textContent=f(svc);
  document.getElementById('sum-total').textContent=f(total);
}
calcPrice();
</script>
<?php include 'includes/footer.php'; ?>
