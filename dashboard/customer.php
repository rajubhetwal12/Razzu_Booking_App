<?php
$pageTitle='My Dashboard — LuxStay';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireLogin();$db=getDB();$user=currentUser();
if($user['role']==='admin')  redirect(BASE_URL.'/dashboard/admin.php');
if($user['role']==='manager')redirect(BASE_URL.'/dashboard/manager.php');
$tab=$_GET['tab']??'bookings';

// Actions
if(isset($_GET['cancel'])&&$tab==='bookings'){
  $bid=(int)$_GET['cancel'];
  $chk=$db->prepare('SELECT id FROM bookings WHERE id=? AND customer_id=? AND status="pending"');
  $chk->execute([$bid,$user['id']]);
  if($chk->fetch()){$db->prepare('UPDATE bookings SET status="cancelled" WHERE id=?')->execute([$bid]);flash('success','Booking cancelled.');}
  redirect(BASE_URL.'/dashboard/customer.php?tab=bookings');
}
if(isset($_GET['mark_read'])){$db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);redirect(BASE_URL.'/dashboard/customer.php?tab=notifications');}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_profile'])){
  $name=trim($_POST['name']??'');$phone=trim($_POST['phone']??'');$addr=trim($_POST['address']??'');$nat=trim($_POST['nationality']??'');
  if($name){$db->prepare('UPDATE users SET name=?,phone=?,address=?,nationality=? WHERE id=?')->execute([$name,$phone,$addr,$nat,$user['id']]);$_SESSION['uname']=$name;flash('success','Profile updated!');}
  redirect(BASE_URL.'/dashboard/customer.php?tab=profile');
}
// Data
$bkgs=$db->prepare('SELECT b.*,h.name AS hname,h.cover_image,h.city FROM bookings b JOIN hotels h ON b.hotel_id=h.id WHERE b.customer_id=? ORDER BY b.created_at DESC');
$bkgs->execute([$user['id']]);$bkgs=$bkgs->fetchAll();
$notifsSt=$db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 25');
$notifsSt->execute([$user['id']]);$notifs=$notifsSt->fetchAll();
$unreadSt=$db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');$unreadSt->execute([$user['id']]);$unread=(int)$unreadSt->fetchColumn();
$spentSt=$db->prepare('SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE customer_id=? AND payment_status="paid"');$spentSt->execute([$user['id']]);$spent=(float)$spentSt->fetchColumn();
$fullU=$db->prepare('SELECT * FROM users WHERE id=?');$fullU->execute([$user['id']]);$fullU=$fullU->fetch();
$total=count($bkgs);$conf=count(array_filter($bkgs,fn($b)=>$b['status']==='confirmed'));$pend=count(array_filter($bkgs,fn($b)=>$b['status']==='pending'));
include __DIR__.'/../includes/header.php';
?>
<div style="padding:1.5rem 0 4rem">
<div class="container">
  <div class="flex aic jcsb mb3" style="flex-wrap:wrap;gap:1rem">
    <div><h1 style="font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800">My Dashboard</h1><p class="muted" style="font-size:.875rem">Welcome, <strong style="color:var(--gold)"><?=e($user['name'])?></strong></p></div>
    <a href="<?=BASE_URL?>/hotels.php" class="btn btn-primary">+ Book New Hotel</a>
  </div>
  <div class="g4 mb3">
    <?php foreach([['🏨','Total Bookings',$total],['✅','Confirmed',$conf],['⏳','Pending',$pend],['💰','Spent',fmtNPR($spent)]] as [$ic,$l,$v]):?>
      <div class="stat-card rounded"><div style="font-size:1.75rem;margin-bottom:.375rem"><?=$ic?></div><div class="stat-num gold-text"><?=$v?></div><div class="stat-lbl"><?=$l?></div></div>
    <?php endforeach;?>
  </div>
  <div class="dash-wrap">
    <!-- Sidebar -->
    <aside class="dash-side">
      <div class="tc" style="padding:1rem 0 1rem;border-bottom:1px solid var(--border);margin-bottom:.5rem">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,var(--gold),var(--gold2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:#08081a;margin:0 auto .5rem"><?=strtoupper(substr($user['name'],0,1))?></div>
        <div style="font-weight:700;font-size:.9rem"><?=e($user['name'])?></div>
        <span class="badge badge-gold mt2">Customer</span>
      </div>
      <a href="?tab=bookings"      class="dash-link <?=$tab==='bookings'?'act':''?>">🏨 My Bookings<span class="ml" style="font-size:.72rem;color:var(--text3)"><?=$total?></span></a>
      <a href="?tab=notifications" class="dash-link <?=$tab==='notifications'?'act':''?>">🔔 Notifications<?php if($unread>0):?><span class="ml badge badge-red"><?=$unread?></span><?php endif;?></a>
      <a href="?tab=profile"       class="dash-link <?=$tab==='profile'?'act':''?>">👤 My Profile</a>
      <hr class="divider">
      <a href="<?=BASE_URL?>/hotels.php" class="dash-link">🔍 Browse Hotels</a>
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171">🚪 Logout</a>
    </aside>
    <!-- Main -->
    <main>
      <?php if($tab==='bookings'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h2 style="font-weight:700">My Bookings (<?=$total?>)</h2></div>
          <?php if(empty($bkgs)):?>
            <div class="tc" style="padding:3.5rem 2rem;color:var(--text3)">
              <div style="font-size:3rem;margin-bottom:.875rem">🏨</div>
              <h3 style="margin-bottom:.5rem">No bookings yet</h3>
              <p style="font-size:.875rem;margin-bottom:1.25rem">Discover luxury hotels across Nepal</p>
              <a href="<?=BASE_URL?>/hotels.php" class="btn btn-primary">Explore Hotels</a>
            </div>
          <?php else:?>
            <div class="overflow-x">
              <table class="data-table"><thead><tr><th>Hotel</th><th>Dates</th><th>Amount</th><th>Status</th><th>Payment</th><th>Action</th></tr></thead><tbody>
              <?php foreach($bkgs as $b):?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.625rem">
                      <img src="<?=e($b['cover_image'])?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0">
                      <div><div style="font-weight:600;font-size:.875rem"><?=e($b['hname'])?></div><div style="font-size:.67rem;color:var(--gold);font-family:monospace"><?=e($b['booking_ref'])?></div></div>
                    </div>
                  </td>
                  <td style="font-size:.8rem"><?=date('M j',strtotime($b['check_in']))?> → <?=date('M j, Y',strtotime($b['check_out']))?><div style="color:var(--text3);font-size:.7rem"><?=$b['nights']?> night<?=$b['nights']>1?'s':''?></div></td>
                  <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                  <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
                  <td><span class="badge badge-<?=$b['payment_status']==='paid'?'green':'red'?>"><?=$b['payment_status']?></span></td>
                  <td style="white-space:nowrap">
                    <?php if($b['payment_status']==='unpaid'&&$b['status']==='pending'):?>
                      <a href="<?=BASE_URL?>/payment.php?booking_id=<?=$b['id']?>" class="btn btn-primary btn-sm">Pay</a>
                    <?php elseif($b['status']==='pending'):?>
                      <a href="?tab=bookings&cancel=<?=$b['id']?>" class="badge badge-red" style="cursor:pointer" onclick="return confirm('Cancel?')">✗ Cancel</a>
                    <?php else:?>
                      <span style="font-size:.7rem;color:var(--text3)"><?=date('M j',strtotime($b['created_at']))?></span>
                    <?php endif;?>
                  </td>
                </tr>
              <?php endforeach;?>
              </tbody></table>
            </div>
          <?php endif;?>
        </div>
      <?php elseif($tab==='notifications'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <h2 style="font-weight:700">Notifications <?php if($unread>0):?><span class="badge badge-red"><?=$unread?> new</span><?php endif;?></h2>
            <?php if($unread>0):?><a href="?tab=notifications&mark_read=1" class="btn btn-outline btn-sm">Mark All Read</a><?php endif;?>
          </div>
          <?php if(empty($notifs)):?><div class="tc" style="padding:3rem;color:var(--text3)"><div style="font-size:2.5rem;margin-bottom:.75rem">🔔</div><p>No notifications yet.</p></div>
          <?php else:?>
            <?php foreach($notifs as $n):?>
              <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border);background:<?=$n['is_read']?'transparent':'rgba(212,160,23,.02)';?>">
                <div class="flex jcsb gap-2">
                  <div style="flex:1;min-width:0">
                    <div style="font-weight:<?=$n['is_read']?'500':'700'?>;font-size:.875rem;margin-bottom:.2rem"><?=e($n['title'])?></div>
                    <div style="font-size:.8rem;color:var(--text3);line-height:1.6"><?=e($n['message']??'')?></div>
                  </div>
                  <div style="text-align:right;flex-shrink:0"><span style="font-size:.72rem;color:var(--text3)"><?=date('M j',strtotime($n['created_at']))?></span><?php if(!$n['is_read']):?><div style="width:7px;height:7px;border-radius:50%;background:var(--gold);margin:.375rem 0 0 auto"></div><?php endif;?></div>
                </div>
              </div>
            <?php endforeach;?>
          <?php endif;?>
        </div>
      <?php elseif($tab==='profile'):?>
        <div class="card p3 rounded">
          <h2 style="font-weight:700;margin-bottom:1.5rem">My Profile</h2>
          <form method="POST" style="max-width:500px">
            <input type="hidden" name="save_profile" value="1">
            <div class="g2">
              <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-input" value="<?=e($fullU['name']??'')?>" required></div>
              <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-input" value="<?=e($fullU['phone']??'')?>"></div>
            </div>
            <div class="form-group"><label class="form-label">Email (read-only)</label><input type="email" class="form-input" value="<?=e($fullU['email']??'')?>" disabled style="opacity:.55"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-input" rows="2"><?=e($fullU['address']??'')?></textarea></div>
            <div class="form-group"><label class="form-label">Nationality</label><input type="text" name="nationality" class="form-input" value="<?=e($fullU['nationality']??'')?>" placeholder="Nepali"></div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      <?php endif;?>
    </main>
  </div>
</div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
