<?php
$pageTitle = 'Manager Dashboard — LuxStay';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('manager');
$db  = getDB();
$user= currentUser();

// Get manager's hotels
$hotels=$db->prepare('SELECT * FROM hotels WHERE owner_id=? ORDER BY created_at DESC');
$hotels->execute([$user['id']]); $hotels=$hotels->fetchAll();
$hotelIds = array_column($hotels,'id') ?: [0];
$inIds = implode(',', array_map('intval', $hotelIds));

$bookings=$db->query("SELECT b.*,u.name as customer_name,u.email as customer_email,h.name as hotel_name FROM bookings b JOIN users u ON b.customer_id=u.id JOIN hotels h ON b.hotel_id=h.id WHERE b.hotel_id IN ($inIds) ORDER BY b.created_at DESC LIMIT 30")->fetchAll();
$revenue=$db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE hotel_id IN ($inIds) AND payment_status='paid'")->fetchColumn();
$totalBookings=count($bookings);
$pending=count(array_filter($bookings,fn($b)=>$b['status']==='pending'));

$tab=$_GET['tab']??'overview';

// Add Hotel
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_hotel'])){
    $name=trim($_POST['name']??'');
    $city=trim($_POST['city']??'');
    $desc=trim($_POST['description']??'');
    $stars=(int)($_POST['stars']??5);
    $minP=(float)($_POST['min_price']??5000);
    $cat=$_POST['category']??'luxury';
    $phone=trim($_POST['phone']??'');
    $email_h=trim($_POST['email']??'');
    $img=trim($_POST['cover_image']??'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80');
    $slug=strtolower(preg_replace('/[^a-z0-9]+/','-',$name)).'-'.time();
    $db->prepare('INSERT INTO hotels (owner_id,name,slug,description,category,stars,cover_image,city,country,phone,email,min_price,max_price,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)')
       ->execute([$user['id'],$name,$slug,$desc,$cat,$stars,$img,$city,'Nepal',$phone,$email_h,$minP,$minP*5]);
    redirect('?tab=hotels&added=1');
}
// Update booking status
if(isset($_GET['approve'])){
    $db->prepare('UPDATE bookings SET status="confirmed" WHERE id=?')->execute([(int)$_GET['approve']]);
    redirect('?tab=bookings');
}
if(isset($_GET['cancel'])){
    $db->prepare('UPDATE bookings SET status="cancelled" WHERE id=?')->execute([(int)$_GET['cancel']]);
    redirect('?tab=bookings');
}

include '../includes/header.php';
?>
<div class="page-content">
<div class="container" style="padding-top:1.5rem;padding-bottom:4rem">
  <div class="flex items-center justify-between" style="margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <div>
      <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;color:var(--text)">Hotel Dashboard</h1>
      <p style="color:var(--text3);font-size:.875rem">Manager: <strong style="color:var(--gold)"><?=e($user['name'])?></strong></p>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid-4" style="margin-bottom:2rem">
    <?php foreach([['My Hotels',count($hotels),'🏨'],['Total Bookings',$totalBookings,'📋'],['Pending',$pending,'⏳'],['Revenue','NPR '.number_format($revenue),'💰']] as [$l,$v,$i]): ?>
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
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#60a5fa);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin:0 auto .5rem"><?=strtoupper($user['name'][0]??'M')?></div>
        <div style="font-weight:700;color:var(--text);font-size:.875rem"><?=e($user['name'])?></div>
        <span class="badge badge-blue" style="margin-top:.25rem">Manager</span>
      </div>
      <?php foreach([['overview','📊 Overview'],['hotels','🏨 My Hotels'],['bookings','📋 Bookings'],['add_hotel','➕ Add Hotel']] as [$key,$label]): ?>
        <a href="?tab=<?=$key?>" class="dash-link <?=$tab===$key?'active':''?>"><?=$label?></a>
      <?php endforeach; ?>
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171;margin-top:1rem">🚪 Logout</a>
    </aside>

    <main>
      <?php if($tab==='overview'): ?>
        <!-- Revenue chart (simple bars) -->
        <div class="card p-3 rounded" style="margin-bottom:1.25rem">
          <h3 style="font-weight:700;color:var(--text);margin-bottom:1.25rem">Revenue Overview</h3>
          <?php
          $months=$db->query("SELECT DATE_FORMAT(created_at,'%b') as m, COALESCE(SUM(total_amount),0) as rev FROM bookings WHERE hotel_id IN ($inIds) AND payment_status='paid' GROUP BY MONTH(created_at) ORDER BY MONTH(created_at) DESC LIMIT 6")->fetchAll();
          $maxRev=max(array_column($months,'rev')+[1]);
          ?>
          <div style="display:flex;align-items:flex-end;gap:.625rem;height:120px">
            <?php if(empty($months)): ?>
              <p style="color:var(--text3);font-size:.875rem">No revenue data yet.</p>
            <?php endif; ?>
            <?php foreach(array_reverse($months) as $m): ?>
              <?php $h=max(8,round(($m['rev']/$maxRev)*100)); ?>
              <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.375rem">
                <span style="font-size:.7rem;color:var(--gold)">NPR <?=number_format($m['rev']/1000,0)?>k</span>
                <div style="width:100%;background:linear-gradient(to top,#d4a017,#f5c842);border-radius:4px 4px 0 0;height:<?=$h?>px"></div>
                <span style="font-size:.7rem;color:var(--text3)"><?=e($m['m'])?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Recent bookings -->
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">Recent Bookings</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Guest</th><th>Hotel</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach(array_slice($bookings,0,5) as $b): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:var(--text);font-size:.875rem"><?=e($b['customer_name'])?></div>
                  <div style="font-size:.75rem;color:var(--text3)"><?=e($b['customer_email'])?></div>
                </td>
                <td style="font-size:.875rem;color:var(--text2)"><?=e($b['hotel_name'])?></td>
                <td style="font-size:.8rem;color:var(--text3)"><?=date('M j',strtotime($b['check_in']))?> → <?=date('M j',strtotime($b['check_out']))?></td>
                <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='hotels'): ?>
        <?php if(isset($_GET['added'])): ?><div class="alert alert-success">✅ Hotel added successfully!</div><?php endif; ?>
        <div class="hotel-grid">
          <?php foreach($hotels as $h): ?>
            <div class="card rounded" style="overflow:hidden">
              <div style="height:160px;overflow:hidden"><img src="<?=e($h['cover_image'])?>" style="width:100%;height:100%;object-fit:cover"></div>
              <div style="padding:1.125rem">
                <div style="font-weight:700;color:var(--text);margin-bottom:.25rem"><?=e($h['name'])?></div>
                <div style="font-size:.8rem;color:var(--text3);margin-bottom:.75rem">📍 <?=e($h['city'])?></div>
                <div class="flex" style="gap:.5rem;flex-wrap:wrap">
                  <span class="badge badge-<?=$h['is_verified']?'green':'gold'?>"><?=$h['is_verified']?'✅ Verified':'⏳ Pending'?></span>
                  <span class="badge badge-gold">NPR <?=number_format($h['min_price'])?>/night</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if(empty($hotels)): ?>
            <div style="text-align:center;padding:3rem;color:var(--text3);grid-column:1/-1">
              <div style="font-size:3rem;margin-bottom:.75rem">🏨</div>
              <p>No hotels yet. <a href="?tab=add_hotel" class="link-gold">Add your first hotel</a></p>
            </div>
          <?php endif; ?>
        </div>

      <?php elseif($tab==='bookings'): ?>
        <div class="card rounded" style="overflow:hidden">
          <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)">
            <h3 style="font-weight:700;color:var(--text)">All Bookings</h3>
          </div>
          <table class="dash-table">
            <thead><tr><th>Ref</th><th>Guest</th><th>Hotel</th><th>Nights</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <?php foreach($bookings as $b): ?>
              <tr>
                <td style="font-size:.8rem;color:var(--gold);font-weight:600"><?=e($b['booking_ref'])?></td>
                <td>
                  <div style="font-weight:600;color:var(--text);font-size:.875rem"><?=e($b['customer_name'])?></div>
                  <div style="font-size:.75rem;color:var(--text3)"><?=date('M j, Y',strtotime($b['created_at']))?></div>
                </td>
                <td style="font-size:.8rem;color:var(--text2)"><?=e($b['hotel_name'])?></td>
                <td style="font-size:.875rem;color:var(--text2)"><?=$b['nights']?></td>
                <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
                <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
                <td style="white-space:nowrap">
                  <?php if($b['status']==='pending'): ?>
                    <a href="?approve=<?=$b['id']?>&tab=bookings" class="badge badge-green" style="text-decoration:none;margin-right:.25rem">Approve</a>
                    <a href="?cancel=<?=$b['id']?>&tab=bookings" class="badge badge-red" style="text-decoration:none" onclick="return confirm('Cancel this booking?')">Cancel</a>
                  <?php else: ?>
                    <span style="font-size:.75rem;color:var(--text3)">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif($tab==='add_hotel'): ?>
        <div class="card p-3 rounded">
          <h2 style="font-weight:700;color:var(--text);margin-bottom:1.5rem">➕ Add New Hotel</h2>
          <form method="POST">
            <div class="grid-2">
              <div class="form-group"><label class="form-label">Hotel Name *</label>
                <input type="text" name="name" class="form-input" placeholder="Kings Hotel" required>
              </div>
              <div class="form-group"><label class="form-label">City *</label>
                <input type="text" name="city" class="form-input" placeholder="Kathmandu" required>
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group"><label class="form-label">Category</label>
                <select name="category" class="form-input">
                  <?php foreach(['luxury','resort','boutique','standard','budget'] as $c): ?>
                    <option value="<?=$c?>"><?=ucfirst($c)?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Stars</label>
                <select name="stars" class="form-input">
                  <?php for($s=5;$s>=1;$s--): ?><option value="<?=$s?>"><?=$s?>★</option><?php endfor; ?>
                </select>
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group"><label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" placeholder="+977-1-XXXXXX">
              </div>
              <div class="form-group"><label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="hotel@email.com">
              </div>
            </div>
            <div class="form-group"><label class="form-label">Base Price (NPR/night)</label>
              <input type="number" name="min_price" class="form-input" value="5000" min="500">
            </div>
            <div class="form-group"><label class="form-label">Cover Image URL</label>
              <input type="url" name="cover_image" class="form-input" placeholder="https://images.unsplash.com/...">
            </div>
            <div class="form-group"><label class="form-label">Description</label>
              <textarea name="description" class="form-input" rows="3" placeholder="Describe your hotel..."></textarea>
            </div>
            <button type="submit" name="add_hotel" class="btn-sm btn-primary">Add Hotel →</button>
          </form>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
