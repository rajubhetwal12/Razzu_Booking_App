<?php
$pageTitle='Payment — LuxStay';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
requireLogin();$db=getDB();$user=currentUser();
$bid=(int)($_GET['booking_id']??0);if(!$bid)redirect(BASE_URL.'/dashboard/customer.php');
$b=$db->prepare('SELECT b.*,h.name AS hname,h.cover_image,h.city,h.country FROM bookings b JOIN hotels h ON b.hotel_id=h.id WHERE b.id=? AND b.customer_id=?');
$b->execute([$bid,$user['id']]);$b=$b->fetch();
if(!$b)redirect(BASE_URL.'/dashboard/customer.php');
if($b['payment_status']==='paid')redirect(BASE_URL.'/dashboard/customer.php?tab=bookings');
if($_SERVER['REQUEST_METHOD']==='POST'){
  $method=$_POST['method']??'esewa';
  $txn='TXN'.strtoupper(bin2hex(random_bytes(6)));
  $db->prepare('UPDATE bookings SET status="confirmed",payment_status="paid",payment_method=? WHERE id=?')->execute([$method,$bid]);
  $db->prepare('INSERT INTO payments(booking_id,user_id,amount,method,transaction_id,status,paid_at)VALUES(?,?,?,?,?,"success",NOW())')->execute([$bid,$user['id'],$b['total_amount'],$method,$txn]);
  $db->prepare('INSERT INTO notifications(user_id,title,message,type)VALUES(?,?,?,"booking")')->execute([$user['id'],'✅ Booking Confirmed!','Booking '.$b['booking_ref'].' at '.$b['hname'].' confirmed. Enjoy your luxury stay!']);
  flash('success','🎉 Payment successful! Your booking is confirmed. Ref: '.$b['booking_ref']);
  redirect(BASE_URL.'/dashboard/customer.php?tab=bookings');
}
$extrasText=$b['special_requests']??'';
include __DIR__.'/includes/header.php';
?>
<div style="padding:1.5rem 0 4rem">
<div class="container" style="max-width:740px">
  <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;margin-bottom:.25rem">Complete Payment</h1>
  <p style="color:var(--text3);margin-bottom:2rem">Ref: <strong style="color:var(--gold);font-family:monospace"><?=e($b['booking_ref'])?></strong></p>

  <div class="g2" style="align-items:start">
    <!-- Summary -->
    <div class="card p3 rounded">
      <h3 style="font-weight:700;margin-bottom:1rem">Booking Summary</h3>
      <img src="<?=e($b['cover_image'])?>" alt="<?=e($b['hname'])?>" style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:1rem">
      <p style="font-weight:700;margin-bottom:.2rem"><?=e($b['hname'])?></p>
      <p class="muted" style="font-size:.8rem;margin-bottom:1rem">📍 <?=e($b['city'])?>, <?=e($b['country'])?></p>
      <div style="font-size:.8125rem;display:flex;flex-direction:column;gap:.5rem">
        <div class="flex jcsb"><span class="muted">Check-in</span><strong><?=date('D, M j Y',strtotime($b['check_in']))?></strong></div>
        <div class="flex jcsb"><span class="muted">Check-out</span><strong><?=date('D, M j Y',strtotime($b['check_out']))?></strong></div>
        <div class="flex jcsb"><span class="muted">Nights</span><span><?=$b['nights']?></span></div>
        <div class="flex jcsb"><span class="muted">Guests</span><span><?=$b['adults']?> Adults<?=$b['children']>0?' + '.$b['children'].' Children':''?></span></div>
        <?php if($extrasText):?><div class="flex jcsb"><span class="muted">Notes</span><span style="font-size:.75rem;text-align:right;max-width:160px"><?=e(substr($extrasText,0,60))?></span></div><?php endif;?>
        <hr class="divider" style="margin:.375rem 0">
        <div class="flex jcsb"><span class="muted">Room</span><span>NPR <?=number_format($b['room_cost'])?></span></div>
        <div class="flex jcsb"><span class="muted">Extras</span><span>NPR <?=number_format($b['extra_cost'])?></span></div>
        <div class="flex jcsb"><span class="muted">Tax (13%)</span><span>NPR <?=number_format($b['tax_amount'])?></span></div>
        <div class="flex jcsb"><span class="muted">Service (5%)</span><span>NPR <?=number_format($b['service_charge'])?></span></div>
        <hr class="divider" style="margin:.375rem 0">
        <div class="flex jcsb aic">
          <span style="font-weight:700">Total Due</span>
          <span class="gold-text" style="font-family:'Playfair Display',serif;font-size:1.375rem;font-weight:800">NPR <?=number_format($b['total_amount'])?></span>
        </div>
      </div>
    </div>
    <!-- Payment -->
    <div class="card p3 rounded">
      <h3 style="font-weight:700;margin-bottom:1rem">Payment Method</h3>
      <form method="POST">
        <div style="display:flex;flex-direction:column;gap:.625rem;margin-bottom:1.125rem">
          <?php foreach([['esewa','🟢 eSewa Wallet','Nepal\'s #1 digital wallet'],['khalti','🟣 Khalti','Fast & secure mobile payment'],['cash','💵 Cash on Arrival','Pay at hotel reception']] as [$v,$l,$d]):?>
            <label style="display:flex;align-items:center;gap:.875rem;padding:1rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s">
              <input type="radio" name="method" value="<?=$v?>" <?=$v==='esewa'?'checked':''?> style="accent-color:var(--gold);width:16px;height:16px" onchange="document.querySelectorAll('.pm-lbl').forEach(function(e){e.style.borderColor='var(--border)'});this.closest('label').style.borderColor='var(--gold)'">
              <div><div style="font-weight:700;font-size:.875rem"><?=$l?></div><div style="font-size:.72rem;color:var(--text3)"><?=$d?></div></div>
            </label>
          <?php endforeach;?>
        </div>
        <!-- QR Code -->
        <div id="qr-box" style="text-align:center;padding:1.25rem;background:var(--bg);border:1px solid var(--border);border-radius:12px;margin-bottom:1.125rem">
          <div style="font-weight:700;margin-bottom:.3rem;font-size:1.05rem">📲 Scan to Pay</div>
          <div style="font-weight:800;color:var(--gold);font-size:1.2rem;margin-bottom:.2rem">NPR <?=number_format($b['total_amount'],0)?></div>
          <div style="font-size:.72rem;color:var(--text3);margin-bottom:.875rem">Ref: <?=e($b['booking_ref'])?></div>
          <?php
            $qrData=urlencode('LuxStay Payment|Ref:'.$b['booking_ref'].'|Amount:NPR'.(int)$b['total_amount'].'|To:info@luxstay.com');
            $qrUrl='https://api.qrserver.com/v1/create-qr-code/?size=160x160&data='.$qrData;
          ?>
          <img src="<?=$qrUrl?>" alt="QR Code" style="width:160px;height:160px;border-radius:8px;border:3px solid var(--gold);padding:4px;background:#fff" onerror="this.outerHTML='<div style=&quot;width:160px;height:160px;margin:0 auto;background:var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:var(--text3)&quot;>QR Unavailable</div>'">
          <p style="font-size:.7rem;color:var(--text3);margin-top:.625rem">Or select a payment method below</p>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="font-size:1rem">✅ Confirm Payment</button>
        <p class="tc muted" style="font-size:.7rem;margin-top:.625rem">🔒 256-bit SSL encrypted</p>
        <a href="<?=BASE_URL?>/dashboard/customer.php" class="tc" style="display:block;font-size:.8rem;color:var(--text3);margin-top:.5rem">← Cancel</a>
      </form>
    </div>
  </div>
</div>
</div>
<?php include __DIR__.'/includes/footer.php';?>
