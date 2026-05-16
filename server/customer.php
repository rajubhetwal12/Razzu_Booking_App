<?php
$pageTitle = 'My Dashboard — LuxStay';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();
$db  = getDB();
$user= currentUser();

if($user['role']==='admin'){ redirect(BASE_URL.'/dashboard/admin.php'); }
if($user['role']==='manager'){ redirect(BASE_URL.'/dashboard/manager.php'); }

$bookings=$db->prepare('SELECT b.*,h.name as hotel_name,h.cover_image,h.city FROM bookings b JOIN hotels h ON b.hotel_id=h.id WHERE b.customer_id=? ORDER BY b.created_at DESC');
$bookings->execute([$user['id']]); $bookings=$bookings->fetchAll();

$notifs=$db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5');
$notifs->execute([$user['id']]); $notifs=$notifs->fetchAll();

$unread=$db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$unread->execute([$user['id']]); $unreadCount=$unread->fetchColumn();

$totalSpent=$db->prepare('SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE customer_id=? AND payment_status="paid"');
$totalSpent->execute([$user['id']]); $spent=$totalSpent->fetchColumn();

$tab=$_GET['tab']??'bookings';
include '../includes/header.php';
?>
<div class="page-content">
<div class="container" style="padding-top:1.5rem;padding-bottom:4rem">
  <!-- Header -->
  <div class="flex items-center justify-between" style="margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <div>
      <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;color:var(--text)">My Dashboard</h1>
      <p style="color:var(--text3);font-size:.875rem">Welcome back, <strong style="color:var(--gold)"><?=e($user['name'])?></strong></p>
    </div>
    <a href="<?=BASE_URL?>/hotels.php" class="btn-sm btn-primary">+ Book New Hotel</a>
  </div>

  <!-- Stats -->
  <div class="grid-4" style="margin-bottom:2rem">
    <?php
    $total=count($bookings);
    $confirmed=count(array_filter($bookings,fn($b)=>$b['status']==='confirmed'));
    $pending=count(array_filter($bookings,fn($b)=>$b['status']==='pending'));
    foreach([['Total Bookings',$total,'🏨'],['Confirmed',$confirmed,'✅'],['Pending',$pending,'⏳'],['Total Spent','NPR '.number_format($spent),'💰']] as [$l,$v,$i]):
    ?>
    <div class="stat-card rounded">
      <div style="font-size:1.75rem;margin-bottom:.5rem"><?=$i?></div>
      <div class="val gold-text"><?=$v?></div>
      <div class="lbl"><?=$l?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="dash-layout">
    <!-- Sidebar -->
    <aside class="dash-sidebar">
      <div style="text-align:center;padding:1rem 0;margin-bottom:.5rem;border-bottom:1px solid var(--border)">
        <div style="width:52px;height:52px;background:linear-gradient(135deg,#d4a017,#f5c842);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.375rem;margin:0 auto .5rem"><?=strtoupper($user['name'][0]??'U')?></div>
        <div style="font-weight:700;color:var(--text);font-size:.9375rem"><?=e($user['name'])?></div>
        <span class="badge badge-gold" style="margin-top:.25rem">Customer</span>
      </div>
      <?php foreach([['bookings','🏨 My Bookings'],['notifications','🔔 Notifications '.($unreadCount?"<span class='badge badge-red'>$unreadCount</span>":'')],['profile','👤 Profile']] as [$key,$label]): ?>
        <a href="?tab=<?=$key?>" class="dash-link <?=$tab===$key?'active':''?>"><?=$label?></a>
      <?php endforeach; ?>
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171;margin-top:1rem">🚪 Logout</a>
    </aside>

    <!-- Content -->
    <main>
      <?php if($tab==='bookings'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border)">
            <h2 style="font-weight:700;color:var(--text)">My Bookings</h2>
          </div>
          <?php if(empty($bookings)): ?>
            <div style="text-align:center;padding:3rem;color:var(--text3)">
              <div style="font-size:2.5rem;margin-bottom:.75rem">🏨</div>
              <p>No bookings yet. <a href="<?=BASE_URL?>/hotels.php" class="link-gold">Explore hotels</a></p>
            </div>
          <?php else: ?>
            <table class="dash-table" style="width:100%">
              <thead><tr>
                <th>Hotel</th><th>Dates</th><th>Amount</th><th>Status</th><th>Payment</th><th>Action</th>
              </tr></thead>
              <tbody>
              <?php foreach($bookings as $b): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.625rem">
                      <img src="<?=e($b['cover_image'])?>" style="width:38px;height:38px;border-radius:8px;object-fit:cover">
                      <div>
                        <div style="font-weight:600;color:var(--text);font-size:.875rem"><?=e($b['hotel_name'])?></div>
                        <div style="font-size:.75rem;color:var(--text3)"><?=e($b['booking_ref'])?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-size:.8rem">
                    <?=date('M j',strtotime($b['check_in']))?> → <?=date('M j, Y',strtotime($b['check_out']))?>
                    <div style="color:var(--text3);font-size:.75rem"><?=$b['nights']?> nights</div>
                  </td>
                  <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                  <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
                  <td><span class="badge badge-<?=$b['payment_status']==='paid'?'green':'red'?>"><?=$b['payment_status']?></span></td>
                  <td>
                    <?php if($b['status']==='pending'): ?>
                      <a href="<?=BASE_URL?>/payment.php?booking_id=<?=$b['id']?>" class="btn-sm btn-primary" style="padding:.3rem .75rem;font-size:.75rem">Pay Now</a>
                    <?php else: ?>
                      <span style="font-size:.75rem;color:var(--text3)"><?=date('M j',strtotime($b['created_at']))?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      <?php elseif($tab==='notifications'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h2 style="font-weight:700;color:var(--text)">Notifications</h2>
            <?php if($unreadCount>0): ?>
              <a href="?tab=notifications&mark_read=1" class="btn-sm btn-ghost" style="font-size:.75rem;padding:.3rem .875rem">Mark All Read</a>
            <?php endif; ?>
          </div>
          <?php
          if(isset($_GET['mark_read'])){
            $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
            redirect('?tab=notifications');
          }
          ?>
          <?php if(empty($notifs)): ?>
            <div style="text-align:center;padding:3rem;color:var(--text3)">
              <div style="font-size:2.5rem;margin-bottom:.75rem">🔔</div><p>No notifications yet.</p>
            </div>
          <?php else: ?>
            <div>
              <?php foreach($notifs as $n): ?>
                <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border);background:<?=$n['is_read']?'transparent':'rgba(212,160,23,0.04)'>">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem">
                    <div>
                      <div style="font-weight:600;color:var(--text);font-size:.9375rem;margin-bottom:.25rem"><?=e($n['title'])?></div>
                      <div style="font-size:.8125rem;color:var(--text3)"><?=e($n['message'])?></div>
                    </div>
                    <div style="font-size:.75rem;color:var(--text3);white-space:nowrap"><?=date('M j',strtotime($n['created_at']))?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      <?php elseif($tab==='profile'): ?>
        <div class="card p-3 rounded">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1.5rem">My Profile</h2>
          <?php
          if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
            $name=trim($_POST['name']??'');
            $phone=trim($_POST['phone']??'');
            $db->prepare('UPDATE users SET name=?,phone=? WHERE id=?')->execute([$name,$phone,$user['id']]);
            $_SESSION['user_name']=$name;
            flash('success','Profile updated!');
            redirect('?tab=profile');
          }
          $full=$db->prepare('SELECT * FROM users WHERE id=?');
          $full->execute([$user['id']]); $full=$full->fetch();
          ?>
          <?php $s=flash('success'); if($s): ?><div class="alert alert-success"><?=e($s)?></div><?php endif; ?>
          <form method="POST" style="max-width:440px">
            <div class="form-group"><label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-input" value="<?=e($full['name']??'')?>" required>
            </div>
            <div class="form-group"><label class="form-label">Email</label>
              <input type="email" class="form-input" value="<?=e($full['email']??'')?>" disabled style="opacity:.6">
            </div>
            <div class="form-group"><label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-input" value="<?=e($full['phone']??'')?>">
            </div>
            <button type="submit" name="update_profile" class="btn-sm btn-primary">Save Changes</button>
          </form>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
