<?php
$pageTitle='Manager Dashboard — LuxStay';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
requireRole('manager');
$db=getDB();$user=currentUser();

// Include actions handler (handles all POST and GET actions)
require_once __DIR__.'/manager_actions.php';

$tab=$_GET['tab']??'overview';
$hid=(int)($_GET['hotel_id']??0);

// Load manager's hotels
$hotels=$db->prepare('SELECT * FROM hotels WHERE owner_id=? ORDER BY created_at DESC');
$hotels->execute([$user['id']]);$hotels=$hotels->fetchAll();

// Selected hotel for sub-tabs
$selHotel=null;
if($hid){foreach($hotels as $hh){if((int)$hh['id']===$hid){$selHotel=$hh;break;}}}
if(!$selHotel&&!empty($hotels)){$selHotel=$hotels[0];$hid=(int)$selHotel['id'];}

$hids=array_column($hotels,'id')?:[0];$in=implode(',',array_map('intval',$hids));
$bkgs=$db->query("SELECT b.*,u.name AS cname,u.email AS cemail,h.name AS hname FROM bookings b JOIN users u ON b.customer_id=u.id JOIN hotels h ON b.hotel_id=h.id WHERE b.hotel_id IN($in) ORDER BY b.created_at DESC LIMIT 60")->fetchAll();
$rev=(float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE hotel_id IN($in) AND payment_status='paid'")->fetchColumn();
$pend=count(array_filter($bkgs,fn($b)=>$b['status']==='pending'));
$conf=count(array_filter($bkgs,fn($b)=>$b['status']==='confirmed'));

include __DIR__.'/../includes/header.php';

$hotelTabs=['images'=>'🖼️ Gallery','rooms'=>'🛏️ Rooms','facilities'=>'✅ Facilities','about'=>'📝 About','policies'=>'📋 Policies','reviews'=>'⭐ Reviews'];
?>
<div style="padding:1.5rem 0 4rem">
<div class="container">
  <div class="flex aic jcsb mb3" style="flex-wrap:wrap;gap:1rem">
    <div><h1 style="font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800">Hotel Manager</h1>
    <p class="muted" style="font-size:.875rem">Managing <strong style="color:var(--gold)"><?=count($hotels)?></strong> hotel<?=count($hotels)!=1?'s':''?></p></div>
    <a href="?tab=add_hotel" class="btn btn-primary">+ Add Hotel</a>
  </div>

  <!-- Stats -->
  <div class="g4 mb3">
    <?php foreach([['🏨','My Hotels',count($hotels)],['📋','Bookings',count($bkgs)],['⏳','Pending',$pend],['💰','Revenue',fmtNPR($rev)]] as [$ic,$l,$v]):?>
      <div class="stat-card rounded"><div style="font-size:1.75rem;margin-bottom:.375rem"><?=$ic?></div><div class="stat-num gold-text"><?=$v?></div><div class="stat-lbl"><?=$l?></div></div>
    <?php endforeach;?>
  </div>

  <div class="dash-wrap">
    <!-- Sidebar -->
    <aside class="dash-side">
      <div class="tc" style="padding:1rem 0;border-bottom:1px solid var(--border);margin-bottom:.5rem">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#60a5fa);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:#fff;margin:0 auto .5rem"><?=strtoupper(substr($user['name'],0,1))?></div>
        <div style="font-weight:700;font-size:.875rem"><?=e($user['name'])?></div>
        <span class="badge badge-blue mt2">Manager</span>
      </div>
      <a href="?tab=overview"  class="dash-link <?=$tab==='overview'?'act':''?>">📊 Overview</a>
      <a href="?tab=hotels"    class="dash-link <?=$tab==='hotels'?'act':''?>">🏨 My Hotels</a>
      <a href="?tab=bookings"  class="dash-link <?=$tab==='bookings'?'act':''?>">📋 Bookings<?php if($pend>0):?><span class="ml badge badge-gold"><?=$pend?></span><?php endif;?></a>
      <a href="?tab=add_hotel" class="dash-link <?=$tab==='add_hotel'?'act':''?>">➕ Add Hotel</a>
      <?php if($selHotel):?>
        <hr class="divider">
        <div style="font-size:.7rem;color:var(--text3);padding:.25rem .75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Manage Hotel</div>
        <!-- Hotel selector -->
        <?php if(count($hotels)>1):?>
        <form method="GET" style="padding:.25rem .5rem .5rem">
          <select name="hotel_id" class="form-input" style="font-size:.75rem;padding:.375rem .5rem" onchange="this.form.submit()">
            <?php foreach($hotels as $hh):?>
              <option value="<?=$hh['id']?>" <?=$hh['id']==$hid?'selected':''?>><?=e(mb_substr($hh['name'],0,22))?></option>
            <?php endforeach;?>
          </select>
          <input type="hidden" name="tab" value="<?=e($tab)?>">
        </form>
        <?php else:?>
          <div style="font-size:.75rem;color:var(--gold);padding:.25rem .75rem .5rem"><?=e(mb_substr($selHotel['name'],0,26))?></div>
        <?php endif;?>
        <?php foreach($hotelTabs as $tk=>$tl):?>
          <a href="?tab=<?=$tk?>&hotel_id=<?=$hid?>" class="dash-link <?=$tab===$tk?'act':''?>"><?=$tl?></a>
        <?php endforeach;?>
      <?php endif;?>
      <hr class="divider">
      <a href="<?=BASE_URL?>/logout.php" class="dash-link" style="color:#f87171">🚪 Logout</a>
    </aside>

    <!-- Main Content -->
    <main>
      <?php
      if($tab==='overview'):      include __DIR__.'/mgr_tabs/tab_overview.php';
      elseif($tab==='hotels'):    include __DIR__.'/mgr_tabs/tab_hotels.php';
      elseif($tab==='bookings'):  include __DIR__.'/mgr_tabs/tab_bookings.php';
      elseif($tab==='add_hotel'): include __DIR__.'/mgr_tabs/tab_addhotel.php';
      elseif($selHotel&&$tab==='images'):    include __DIR__.'/mgr_tabs/tab_images.php';
      elseif($selHotel&&$tab==='rooms'):     include __DIR__.'/mgr_tabs/tab_rooms.php';
      elseif($selHotel&&$tab==='facilities'):include __DIR__.'/mgr_tabs/tab_facilities.php';
      elseif($selHotel&&$tab==='about'):     include __DIR__.'/mgr_tabs/tab_about.php';
      elseif($selHotel&&$tab==='policies'):  include __DIR__.'/mgr_tabs/tab_policies.php';
      elseif($selHotel&&$tab==='reviews'):   include __DIR__.'/mgr_tabs/tab_reviews.php';
      elseif($tab==='edit_hotel'&&$selHotel):include __DIR__.'/mgr_tabs/tab_edit.php';
      else: include __DIR__.'/mgr_tabs/tab_overview.php';
      endif;
      ?>
    </main>
  </div>
</div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
