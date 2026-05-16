<?php
$pageTitle = 'Admin Panel — LuxStay';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');
$db  = getDB();

// Actions
if(isset($_GET['verify_hotel']))   { $db->prepare('UPDATE hotels SET is_verified=1,is_active=1 WHERE id=?')->execute([(int)$_GET['verify_hotel']]); redirect('?tab=hotels'); }
if(isset($_GET['reject_hotel']))   { $db->prepare('UPDATE hotels SET is_active=0 WHERE id=?')->execute([(int)$_GET['reject_hotel']]); redirect('?tab=hotels'); }
if(isset($_GET['ban_user']))       { $db->prepare('UPDATE users SET is_active=0 WHERE id=?')->execute([(int)$_GET['ban_user']]); redirect('?tab=users'); }
if(isset($_GET['activate_user']))  { $db->prepare('UPDATE users SET is_active=1 WHERE id=?')->execute([(int)$_GET['activate_user']]); redirect('?tab=users'); }

// Stats
$totalUsers   = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalHotels  = $db->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
$pendingHotels= $db->query("SELECT COUNT(*) FROM hotels WHERE is_verified=0 AND is_active=1")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status='paid'")->fetchColumn();
$totalBookings= $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

$tab = $_GET['tab'] ?? 'overview';
$users   = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$hotels  = $db->query("SELECT h.*,u.name as owner_name FROM hotels h LEFT JOIN users u ON h.owner_id=u.id ORDER BY h.created_at DESC")->fetchAll();
$bookings= $db->query("SELECT b.*,u.name as customer_name,h.name as hotel_name FROM bookings b JOIN users u ON b.customer_id=u.id JOIN hotels h ON b.hotel_id=h.id ORDER BY b.created_at DESC LIMIT 50")->fetchAll();
$payments= $db->query("SELECT p.*,u.name as uname,b.booking_ref FROM payments p JOIN users u ON p.user_id=u.id JOIN bookings b ON p.booking_id=b.id ORDER BY p.created_at DESC LIMIT 30")->fetchAll();

include '../includes/header.php';
?>
<div class="page-content">
<div class="container" style="padding-top:1.5rem;padding-bottom:4rem">
  <div class="flex items-center justify-between" style="margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <div>
      <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;color:var(--text)">Admin Panel</h1>
      <p style="color:var(--text3);font-size:.875rem">Platform-wide management</p>
    </div>
    <?php if($pendingHotels>0): ?>
      <a href="?tab=hotels" class="badge badge-red" style="font-size:.8125rem;padding:.375rem .875rem;text-decoration:none">
        ⚠️ <?=$pendingHotels?> Hotels Need Verification
      </a>
    <?php endif; ?>
  </div>

  <!-- Stats -->
  <div class="grid-4" style="margin-bottom:2rem">
    <?php foreach([['Users',$totalUsers,'👥'],['Hotels',$totalHotels,'🏨'],['Bookings',$totalBookings,'📋'],['Revenue','NPR '.number_format($totalRevenue),'💰']] as [$l,$v,$i]): ?>
    <div class="stat-card rounded">
      <div style="font-size:1.75rem;margin-bottom:.5rem"><?=$i?></div>
      <div class="val gold-text"><?=$v?></div>
      <div class="lbl"><?=$l?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="dash-layout">
    <aside class="dash-sidebar">
      <div style="text-align:center;padding:1rem 0;margin-bottom:.5rem;border-bottom:1px solid var(--border)">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#ef4444,#f87171);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.375rem;margin:0 auto .5rem">👑</div>
        <div style="font-weight:700;color:var(--text);font-size:.875rem">Admin</div>
        <span class="badge badge-red" style="margin-top:.25rem">Super Admin</span>
      </div>
      <?php foreach([
        ['overview','📊 Overview'],
        ['users','👥 Users ('.$totalUsers.')'],
        ['hotels','🏨 Hotels '.($pendingHotels?"<span class='badge badge-red'>$pendingHotels</span>":'')],
        ['bookings','📋 Bookings'],
        ['payments','💳 Payments'],
      ] as [$key,$label]): ?>
        <a href="?tab=<?=$key?>" class="dash-link <?=$tab===$key?'active':''?>"><?=$label?></a>
      <?php endforeach; ?>
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171;margin-top:1rem">🚪 Logout</a>
    </aside>

    <main>
      <?php if($tab==='overview'): ?>
        <!-- Quick stats grid -->
        <div class="grid-3" style="margin-bottom:1.25rem">
          <?php
          $verifiedH=$db->query("SELECT COUNT(*) FROM hotels WHERE is_verified=1")->fetchColumn();
          $activeUsers=$db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
          $todayBk=$db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")->fetchColumn();
          foreach([["✅ Verified Hotels",$verifiedH],["🟢 Active Users",$activeUsers],["📅 Today's Bookings",$todayBk]] as [$l,$v]):
          ?>
          <div class="card p-2 rounded" style="text-align:center">
            <div style="font-size:1.5rem;font-weight:800;color:var(--gold)"><?=$v?></div>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.25rem"><?=$l?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Recent activity -->
        <div class="card rounded" style="overflow:hidden;margin-bottom:1.25rem">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">Recent Bookings</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Ref</th><th>Customer</th><th>Hotel</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach(array_slice($bookings,0,8) as $b): ?>
              <tr>
                <td style="font-size:.8rem;color:var(--gold)"><?=e($b['booking_ref'])?></td>
                <td style="font-size:.875rem;color:var(--text2)"><?=e($b['customer_name'])?></td>
                <td style="font-size:.8rem;color:var(--text3)"><?=e($b['hotel_name'])?></td>
                <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='users'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">All Users (<?=count($users)?>)</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($users as $u): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:var(--text);font-size:.875rem"><?=e($u['name'])?></div>
                  <div style="font-size:.75rem;color:var(--text3)"><?=e($u['email'])?></div>
                </td>
                <td><span class="badge badge-<?=$u['role']==='admin'?'red':($u['role']==='manager'?'blue':'gold')?>"><?=$u['role']?></span></td>
                <td style="font-size:.8rem;color:var(--text3)"><?=e($u['phone']??'—')?></td>
                <td style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($u['created_at']))?></td>
                <td><span class="badge badge-<?=$u['is_active']?'green':'red'?>"><?=$u['is_active']?'Active':'Banned'?></span></td>
                <td>
                  <?php if($u['role']!=='admin'): ?>
                    <?php if($u['is_active']): ?>
                      <a href="?ban_user=<?=$u['id']?>&tab=users" class="badge badge-red" style="text-decoration:none;cursor:pointer" onclick="return confirm('Ban this user?')">Ban</a>
                    <?php else: ?>
                      <a href="?activate_user=<?=$u['id']?>&tab=users" class="badge badge-green" style="text-decoration:none">Activate</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="font-size:.75rem;color:var(--text3)">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='hotels'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">All Hotels (<?=count($hotels)?>)</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Hotel</th><th>Owner</th><th>City</th><th>Stars</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($hotels as $h): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.625rem">
                    <img src="<?=e($h['cover_image'])?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover">
                    <div style="font-weight:600;color:var(--text);font-size:.875rem"><?=e($h['name'])?></div>
                  </div>
                </td>
                <td style="font-size:.8rem;color:var(--text3)"><?=e($h['owner_name']??'—')?></td>
                <td style="font-size:.875rem;color:var(--text2)"><?=e($h['city'])?></td>
                <td style="color:var(--gold)"><?=str_repeat('★',$h['stars'])?></td>
                <td style="font-size:.8rem;color:var(--gold)">NPR <?=number_format($h['min_price'])?></td>
                <td>
                  <span class="badge badge-<?=$h['is_verified']?'green':'gold'?>"><?=$h['is_verified']?'Verified':'Pending'?></span>
                  <?php if(!$h['is_active']): ?><span class="badge badge-red" style="margin-left:.25rem">Inactive</span><?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                  <?php if(!$h['is_verified']): ?>
                    <a href="?verify_hotel=<?=$h['id']?>&tab=hotels" class="badge badge-green" style="text-decoration:none;margin-right:.25rem">Verify</a>
                    <a href="?reject_hotel=<?=$h['id']?>&tab=hotels" class="badge badge-red" style="text-decoration:none">Reject</a>
                  <?php else: ?>
                    <a href="?reject_hotel=<?=$h['id']?>&tab=hotels" class="badge badge-red" style="text-decoration:none" onclick="return confirm('Deactivate this hotel?')">Deactivate</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='bookings'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">All Bookings (<?=count($bookings)?>)</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Ref</th><th>Customer</th><th>Hotel</th><th>Dates</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead>
            <tbody>
            <?php foreach($bookings as $b): ?>
              <tr>
                <td style="font-size:.8rem;color:var(--gold);font-weight:600"><?=e($b['booking_ref'])?></td>
                <td style="font-size:.875rem;color:var(--text2)"><?=e($b['customer_name'])?></td>
                <td style="font-size:.8rem;color:var(--text3)"><?=e($b['hotel_name'])?></td>
                <td style="font-size:.8rem;color:var(--text3)"><?=date('M j',strtotime($b['check_in']))?> → <?=date('M j',strtotime($b['check_out']))?></td>
                <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
                <td><span class="badge badge-<?=$b['payment_status']==='paid'?'green':'red'?>"><?=$b['payment_status']?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='payments'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">All Payments (<?=count($payments)?>)</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Booking</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($payments as $p): ?>
              <tr>
                <td style="font-size:.8rem;color:var(--gold)"><?=e($p['booking_ref'])?></td>
                <td style="font-size:.875rem;color:var(--text2)"><?=e($p['uname'])?></td>
                <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($p['amount'])?></td>
                <td><span class="badge badge-blue"><?=e($p['method']??'—')?></span></td>
                <td><span class="badge badge-<?=$p['status']==='success'?'green':($p['status']==='failed'?'red':'gold')?>"><?=$p['status']?></span></td>
                <td style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($p['created_at']))?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
