<?php
$pageTitle = 'Payment — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
$db  = getDB();
$user= currentUser();
$bid = (int)($_GET['booking_id'] ?? 0);
$booking = $db->prepare('SELECT b.*,h.name as hotel_name,h.cover_image,h.city,h.country FROM bookings b JOIN hotels h ON b.hotel_id=h.id WHERE b.id=? AND b.customer_id=?');
$booking->execute([$bid,$user['id']]); $booking=$booking->fetch();
if(!$booking){ redirect(BASE_URL.'/dashboard/customer.php'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $method=$_POST['method']??'esewa';
    $db->prepare('UPDATE bookings SET status="confirmed",payment_status="paid",payment_method=? WHERE id=?')->execute([$method,$bid]);
    $db->prepare('INSERT INTO payments (booking_id,user_id,amount,method,status,paid_at) VALUES (?,?,?,?,"success",NOW())')->execute([$bid,$user['id'],$booking['total_amount'],$method]);
    $db->prepare('INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)')->execute([$user['id'],'Booking Confirmed!','Your booking '.$booking['booking_ref'].' at '.$booking['hotel_name'].' is confirmed.','payment']);
    redirect(BASE_URL.'/dashboard/customer.php?success=1');
}
include 'includes/header.php';
?>
<div class="page-content">
<div class="container" style="max-width:720px;padding-top:2.5rem;padding-bottom:4rem">
  <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;color:var(--text);margin-bottom:.25rem">Complete Payment</h1>
  <p style="color:var(--text3);margin-bottom:2rem;font-size:.9rem">Booking Ref: <strong style="color:var(--gold)"><?=e($booking['booking_ref'])?></strong></p>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start">
    <!-- Booking summary -->
    <div class="card p-3 rounded">
      <h3 style="font-weight:700;color:var(--text);margin-bottom:1rem">Booking Summary</h3>
      <img src="<?=e($booking['cover_image'])?>" alt="" style="width:100%;height:130px;object-fit:cover;border-radius:10px;margin-bottom:1rem">
      <p style="font-weight:700;color:var(--text);margin-bottom:.25rem"><?=e($booking['hotel_name'])?></p>
      <p style="color:var(--text3);font-size:.8125rem;margin-bottom:.875rem">📍 <?=e($booking['city'])?>, <?=e($booking['country'])?></p>
      <div style="font-size:.8125rem;display:flex;flex-direction:column;gap:.5rem">
        <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Check-in</span><span style="color:var(--text)"><?=date('M j, Y',strtotime($booking['check_in']))?></span></div>
        <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Check-out</span><span style="color:var(--text)"><?=date('M j, Y',strtotime($booking['check_out']))?></span></div>
        <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Nights</span><span style="color:var(--text)"><?=$booking['nights']?></span></div>
        <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Guests</span><span style="color:var(--text)"><?=$booking['adults']?> Adults</span></div>
        <hr class="divider" style="margin:.5rem 0">
        <div style="display:flex;justify-content:space-between;font-weight:700">
          <span>Total</span>
          <span class="gold-text" style="font-size:1.125rem">NPR <?=number_format($booking['total_amount'],2)?></span>
        </div>
      </div>
    </div>

    <!-- Payment method -->
    <div class="card p-3 rounded">
      <h3 style="font-weight:700;color:var(--text);margin-bottom:1rem">Choose Payment</h3>
      <form method="POST">
        <div style="display:flex;flex-direction:column;gap:.625rem;margin-bottom:1.25rem">
          <?php foreach([['esewa','eSewa (Digital Wallet)','🟢'],['khalti','Khalti Wallet','🟣'],['cash','Cash on Arrival','💵']] as [$val,$label,$icon]): ?>
            <label style="display:flex;align-items:center;gap:.75rem;padding:.875rem 1rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s">
              <input type="radio" name="method" value="<?=$val?>" <?=$val==='esewa'?'checked':''?> style="accent-color:#d4a017;width:16px;height:16px">
              <span style="font-size:1.1rem"><?=$icon?></span>
              <span style="font-weight:600;color:var(--text);font-size:.9375rem"><?=$label?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <!-- QR code placeholder -->
        <div style="text-align:center;padding:1.25rem;background:var(--bg);border:1px solid var(--border);border-radius:12px;margin-bottom:1.25rem">
          <div style="width:100px;height:100px;background:#fff;border-radius:8px;margin:0 auto .75rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem">📱</div>
          <p style="font-size:.8rem;color:var(--text3)">Scan QR to pay NPR <?=number_format($booking['total_amount'],0)?></p>
          <p style="font-size:.75rem;color:var(--text3);margin-top:.25rem">Ref: <?=e($booking['booking_ref'])?></p>
        </div>

        <button type="submit" class="btn-full btn-primary">✅ Confirm Payment</button>
      </form>
    </div>
  </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
