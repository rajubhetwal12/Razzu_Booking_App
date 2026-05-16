<?php
$pageTitle='Admin Panel — LuxStay';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');$db=getDB();
$tab=$_GET['tab']??'overview';
if(isset($_GET['verify']))  {$db->prepare('UPDATE hotels SET is_verified=1 WHERE id=?')->execute([(int)$_GET['verify']]);redirect(BASE_URL.'/dashboard/admin.php?tab=hotels');}
if(isset($_GET['hoff']))    {$db->prepare('UPDATE hotels SET is_active=0 WHERE id=?')->execute([(int)$_GET['hoff']]);redirect(BASE_URL.'/dashboard/admin.php?tab=hotels');}
if(isset($_GET['hon']))     {$db->prepare('UPDATE hotels SET is_active=1 WHERE id=?')->execute([(int)$_GET['hon']]);redirect(BASE_URL.'/dashboard/admin.php?tab=hotels');}
if(isset($_GET['ban']))     {$db->prepare("UPDATE users SET is_active=0 WHERE id=? AND role!='admin'")->execute([(int)$_GET['ban']]);redirect(BASE_URL.'/dashboard/admin.php?tab=users');}
if(isset($_GET['unban']))   {$db->prepare('UPDATE users SET is_active=1 WHERE id=?')->execute([(int)$_GET['unban']]);redirect(BASE_URL.'/dashboard/admin.php?tab=users');}
if(isset($_GET['feature'])) {$db->prepare('UPDATE hotels SET is_featured=1 WHERE id=?')->execute([(int)$_GET['feature']]);redirect(BASE_URL.'/dashboard/admin.php?tab=hotels');}
if(isset($_GET['unfeature'])){$db->prepare('UPDATE hotels SET is_featured=0 WHERE id=?')->execute([(int)$_GET['unfeature']]);redirect(BASE_URL.'/dashboard/admin.php?tab=hotels');}
$users   =$db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$hotels  =$db->query("SELECT h.*,u.name AS owner FROM hotels h LEFT JOIN users u ON h.owner_id=u.id ORDER BY h.created_at DESC")->fetchAll();
$bookings=$db->query("SELECT b.*,u.name AS cname,h.name AS hname FROM bookings b JOIN users u ON b.customer_id=u.id JOIN hotels h ON b.hotel_id=h.id ORDER BY b.created_at DESC LIMIT 60")->fetchAll();
$payments=$db->query("SELECT p.*,u.name AS uname,b.booking_ref FROM payments p JOIN users u ON p.user_id=u.id JOIN bookings b ON p.booking_id=b.id ORDER BY p.created_at DESC LIMIT 40")->fetchAll();
$revenue =(float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status='paid'")->fetchColumn();
$pendH   =(int)$db->query("SELECT COUNT(*) FROM hotels WHERE is_verified=0 AND is_active=1")->fetchColumn();
$todayB  =(int)$db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")->fetchColumn();
include __DIR__.'/../includes/header.php';
?>
<div style="padding:1.5rem 0 4rem">
<div class="container">
  <div class="flex aic jcsb mb3" style="flex-wrap:wrap;gap:1rem">
    <div><h1 style="font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800">Admin Panel</h1><p class="muted" style="font-size:.875rem">Full platform control</p></div>
    <?php if($pendH>0):?><a href="?tab=hotels" class="badge badge-red" style="font-size:.8rem;padding:.375rem .875rem;cursor:pointer">⚠️ <?=$pendH?> hotel<?=$pendH>1?'s':''?> need verification</a><?php endif;?>
  </div>
  <div class="g4 mb3">
    <?php foreach([['👥','Total Users',count($users)],['🏨','Hotels',count($hotels)],['📋','Bookings',count($bookings)],['💰','Revenue',fmtNPR($revenue)]] as [$ic,$l,$v]):?>
      <div class="stat-card rounded"><div style="font-size:1.75rem;margin-bottom:.375rem"><?=$ic?></div><div class="stat-num gold-text"><?=$v?></div><div class="stat-lbl"><?=$l?></div></div>
    <?php endforeach;?>
  </div>
  <div class="dash-wrap">
    <aside class="dash-side">
      <div class="tc" style="padding:1rem 0 1rem;border-bottom:1px solid var(--border);margin-bottom:.5rem">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#ef4444,#f87171);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.375rem;margin:0 auto .5rem">👑</div>
        <div style="font-weight:700;font-size:.875rem">Super Admin</div>
        <span class="badge badge-red mt2">Administrator</span>
      </div>
      <a href="?tab=overview"  class="dash-link <?=$tab==='overview'?'act':''?>">📊 Overview</a>
      <a href="?tab=users"     class="dash-link <?=$tab==='users'?'act':''?>">👥 Users <span class="ml" style="font-size:.72rem;color:var(--text3)"><?=count($users)?></span></a>
      <a href="?tab=hotels"    class="dash-link <?=$tab==='hotels'?'act':''?>">🏨 Hotels<?php if($pendH>0):?><span class="ml badge badge-red"><?=$pendH?></span><?php endif;?></a>
      <a href="?tab=bookings"  class="dash-link <?=$tab==='bookings'?'act':''?>">📋 Bookings</a>
      <a href="?tab=payments"  class="dash-link <?=$tab==='payments'?'act':''?>">💳 Payments</a>
      <hr class="divider">
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171">🚪 Logout</a>
    </aside>
    <main>
      <?php if($tab==='overview'):?>
        <div class="g3 mb3">
          <?php foreach([['✅ Verified Hotels',(int)$db->query("SELECT COUNT(*) FROM hotels WHERE is_verified=1")->fetchColumn()],['🟢 Active Users',(int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn()],['📅 Today\'s Bookings',$todayB]] as [$l,$v]):?>
            <div class="stat-card rounded tc"><div class="stat-num gold-text"><?=$v?></div><div class="stat-lbl mt2"><?=$l?></div></div>
          <?php endforeach;?>
        </div>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">Recent Bookings</h3></div>
          <table class="data-table"><thead><tr><th>Ref</th><th>Customer</th><th>Hotel</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead><tbody>
          <?php foreach(array_slice($bookings,0,8) as $b):?>
            <tr>
              <td style="color:var(--gold);font-family:monospace;font-size:.75rem"><?=e($b['booking_ref'])?></td>
              <td style="font-size:.875rem"><?=e($b['cname'])?></td>
              <td style="font-size:.8rem;color:var(--text3)"><?=e($b['hname'])?></td>
              <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
              <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
              <td><span class="badge badge-<?=$b['payment_status']==='paid'?'green':'red'?>"><?=$b['payment_status']?></span></td>
            </tr>
          <?php endforeach;?></tbody></table>
        </div>
      <?php elseif($tab==='users'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">All Users (<?=count($users)?>)</h3></div>
          <div class="overflow-x"><table class="data-table"><thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead><tbody>
          <?php foreach($users as $u):?>
            <tr>
              <td><div style="font-weight:600;font-size:.875rem"><?=e($u['name'])?></div><div style="font-size:.7rem;color:var(--text3)"><?=e($u['email'])?></div></td>
              <td><span class="badge badge-<?=$u['role']==='admin'?'red':($u['role']==='manager'?'blue':'gold')?>"><?=$u['role']?></span></td>
              <td style="font-size:.8rem;color:var(--text3)"><?=e($u['phone']??'—')?></td>
              <td style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($u['created_at']))?></td>
              <td><span class="badge badge-<?=$u['is_active']?'green':'red'?>"><?=$u['is_active']?'Active':'Banned'?></span></td>
              <td>
                <?php if($u['role']!=='admin'):?>
                  <?php if($u['is_active']):?><a href="?ban=<?=$u['id']?>&tab=users" class="badge badge-red" style="cursor:pointer" onclick="return confirm('Ban user?')">Ban</a>
                  <?php else:?><a href="?unban=<?=$u['id']?>&tab=users" class="badge badge-green" style="cursor:pointer">Activate</a><?php endif;?>
                <?php else:?>—<?php endif;?>
              </td>
            </tr>
          <?php endforeach;?></tbody></table></div>
        </div>
      <?php elseif($tab==='hotels'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">All Hotels (<?=count($hotels)?>)</h3></div>
          <div class="overflow-x"><table class="data-table"><thead><tr><th>Hotel</th><th>Owner</th><th>City</th><th>Price</th><th>Verified</th><th>Actions</th></tr></thead><tbody>
          <?php foreach($hotels as $h):?>
            <tr>
              <td><div style="display:flex;align-items:center;gap:.625rem">
                <img src="<?=e($h['cover_image'])?>" style="width:38px;height:38px;border-radius:7px;object-fit:cover;flex-shrink:0">
                <div><div style="font-weight:600;font-size:.875rem"><?=e($h['name'])?></div><div style="color:var(--gold);font-size:.72rem"><?=str_repeat('★',(int)$h['stars'])?></div></div>
              </div></td>
              <td style="font-size:.8rem;color:var(--text3)"><?=e($h['owner']??'—')?></td>
              <td style="font-size:.875rem"><?=e($h['city'])?></td>
              <td style="font-size:.8rem;color:var(--gold)">NPR <?=number_format($h['min_price'])?></td>
              <td><span class="badge badge-<?=$h['is_verified']?'green':'gold'?>"><?=$h['is_verified']?'Verified':'Pending'?></span><?php if(!$h['is_active']):?><br><span class="badge badge-red mt2">Inactive</span><?php endif;?></td>
              <td style="white-space:nowrap">
                <?php if(!$h['is_verified']):?><a href="?verify=<?=$h['id']?>&tab=hotels" class="badge badge-green" style="cursor:pointer;margin-right:.2rem">Verify</a><?php endif;?>
                <?php if($h['is_active']):?><a href="?hoff=<?=$h['id']?>&tab=hotels" class="badge badge-red" style="cursor:pointer;margin-right:.2rem" onclick="return confirm('Deactivate?')">Off</a><?php else:?><a href="?hon=<?=$h['id']?>&tab=hotels" class="badge badge-green" style="cursor:pointer;margin-right:.2rem">On</a><?php endif;?>
                <?php if($h['is_featured']):?><a href="?unfeature=<?=$h['id']?>&tab=hotels" class="badge badge-gray" style="cursor:pointer">★ Remove</a><?php else:?><a href="?feature=<?=$h['id']?>&tab=hotels" class="badge badge-gold" style="cursor:pointer">★ Feature</a><?php endif;?>
              </td>
            </tr>
          <?php endforeach;?></tbody></table></div>
        </div>
      <?php elseif($tab==='bookings'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">All Bookings (<?=count($bookings)?>)</h3></div>
          <div class="overflow-x"><table class="data-table"><thead><tr><th>Ref</th><th>Customer</th><th>Hotel</th><th>Nights</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead><tbody>
          <?php foreach($bookings as $b):?>
            <tr>
              <td style="color:var(--gold);font-family:monospace;font-size:.75rem"><?=e($b['booking_ref'])?></td>
              <td style="font-size:.875rem"><?=e($b['cname'])?></td>
              <td style="font-size:.8rem;color:var(--text3)"><?=e($b['hname'])?></td>
              <td><?=$b['nights']?></td>
              <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
              <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
              <td><span class="badge badge-<?=$b['payment_status']==='paid'?'green':'red'?>"><?=$b['payment_status']?></span></td>
            </tr>
          <?php endforeach;?></tbody></table></div>
        </div>
      <?php elseif($tab==='payments'):?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">All Payments (<?=count($payments)?>)</h3></div>
          <div class="overflow-x"><table class="data-table"><thead><tr><th>Booking Ref</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
          <?php foreach($payments as $p):?>
            <tr>
              <td style="color:var(--gold);font-family:monospace;font-size:.75rem"><?=e($p['booking_ref'])?></td>
              <td style="font-size:.875rem"><?=e($p['uname'])?></td>
              <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($p['amount'])?></td>
              <td><span class="badge badge-blue"><?=e(strtoupper($p['method']??'—'))?></span></td>
              <td><span class="badge badge-<?=$p['status']==='success'?'green':'red'?>"><?=$p['status']?></span></td>
              <td style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($p['created_at']))?></td>
            </tr>
          <?php endforeach;?></tbody></table></div>
        </div>
      <?php endif;?>
    </main>
  </div>
</div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
