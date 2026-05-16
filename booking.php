<?php
$pageTitle='Book Your Stay — LuxStay';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
requireLogin();
$db=getDB();$user=currentUser();
$hid=(int)($_GET['hotel_id']??0);$rid=(int)($_GET['room_id']??0);
if(!$hid)redirect(BASE_URL.'/hotels.php');
$h=$db->prepare('SELECT * FROM hotels WHERE id=? AND is_active=1');$h->execute([$hid]);$h=$h->fetch();
if(!$h)redirect(BASE_URL.'/hotels.php');
$rooms=$db->prepare('SELECT * FROM rooms WHERE hotel_id=? AND is_available=1 ORDER BY base_price');
$rooms->execute([$hid]);$rooms=$rooms->fetchAll();
if(empty($rooms)){$rooms=[['id'=>0,'type'=>'standard','name'=>'Standard Room','description'=>'Comfortable and elegant.','base_price'=>$h['min_price'],'max_guests'=>2,'image'=>null],['id'=>0,'type'=>'deluxe','name'=>'Deluxe Room','description'=>'Spacious with premium amenities.','base_price'=>round($h['min_price']*1.6),'max_guests'=>3,'image'=>null]];}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $ci=$_POST['check_in']??'';$co=$_POST['check_out']??'';
  $adults=max(1,(int)($_POST['adults']??1));$childs=max(0,(int)($_POST['children']??0));
  $selRid=(int)($_POST['room_id']??0);$extras=$_POST['extras']??[];$special=trim($_POST['special']??'');
  $ciD=DateTime::createFromFormat('Y-m-d',$ci);$coD=DateTime::createFromFormat('Y-m-d',$co);
  if(!$ciD||!$coD)$err='Invalid dates.';
  elseif($coD<=$ciD)$err='Check-out must be after check-in.';
  else{
    $nights=(int)$ciD->diff($coD)->days;
    $selRoom=null;foreach($rooms as $rm){if((int)$rm['id']===$selRid){$selRoom=$rm;break;}}
    if(!$selRoom)$selRoom=$rooms[0];
    $rp=(float)$selRoom['base_price'];if($adults>2)$rp*=(1+($adults-2)*0.12);
    $rc=round($rp*$nights);$ec=count($extras)*500;$tax=round(($rc+$ec)*0.13);$svc=round(($rc+$ec)*0.05);$total=$rc+$ec+$tax+$svc;
    $ref=genRef();
    $extrasNote = $extras ? 'Extras: '.implode(', ',$extras).($special?'. '.$special:'') : $special;
    $db->prepare('INSERT INTO bookings(booking_ref,customer_id,hotel_id,room_id,check_in,check_out,nights,adults,children,room_cost,extra_cost,tax_amount,service_charge,total_amount,special_requests,status,payment_status)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"pending","unpaid")')
       ->execute([$ref,$user['id'],$hid,$selRoom['id']>0?$selRoom['id']:null,$ci,$co,$nights,$adults,$childs,$rc,$ec,$tax,$svc,$total,$extrasNote]);
    redirect(BASE_URL.'/payment.php?booking_id='.(int)$db->lastInsertId());
  }
}
$today=date('Y-m-d');$tom=date('Y-m-d',strtotime('+1 day'));
include __DIR__.'/includes/header.php';
?>
<div style="padding:1.5rem 0 4rem">
<div class="container" style="max-width:900px">
  <p style="font-size:.78rem;color:var(--text3);margin-bottom:1.5rem">
    <a href="<?=BASE_URL?>/" style="color:var(--text3)">Home</a> › <a href="<?=BASE_URL?>/hotel-detail.php?id=<?=$hid?>" style="color:var(--text3)"><?=e($h['name'])?></a> › <span style="color:var(--gold)">Book</span>
  </p>
  <h1 style="font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:800;margin-bottom:.25rem">Book Your Stay</h1>
  <p style="color:var(--text3);margin-bottom:2rem;font-size:.9rem">at <strong style="color:var(--gold)"><?=e($h['name'])?></strong> · <?=e($h['city'])?>, <?=e($h['country'])?></p>
  <?php if($err):?><div class="alert alert-error"><?=e($err)?></div><?php endif;?>

  <form method="POST" id="bform">
    <div style="display:grid;grid-template-columns:1fr 290px;gap:1.5rem;align-items:start">
      <div>
        <!-- Dates -->
        <div class="card p3 rounded mb3">
          <h2 style="font-weight:700;margin-bottom:1.125rem">📅 Stay Details</h2>
          <div class="g2">
            <div class="form-group"><label class="form-label">Check-in *</label><input type="date" name="check_in" id="ci" class="form-input" min="<?=$today?>" value="<?=$today?>" required onchange="calc()"></div>
            <div class="form-group"><label class="form-label">Check-out *</label><input type="date" name="check_out" id="co" class="form-input" min="<?=$tom?>" value="<?=$tom?>" required onchange="calc()"></div>
            <div class="form-group"><label class="form-label">Adults</label>
              <select name="adults" id="ad" class="form-input" onchange="calc()">
                <?php for($i=1;$i<=6;$i++):?><option value="<?=$i?>" <?=$i==2?'selected':''?>><?=$i?> Adult<?=$i>1?'s':''?></option><?php endfor;?>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Children</label>
              <select name="children" class="form-input">
                <?php for($i=0;$i<=4;$i++):?><option><?=$i?></option><?php endfor;?>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0"><label class="form-label">Special Requests</label>
            <textarea name="special" class="form-input" rows="2" placeholder="Late check-in, high floor, dietary needs..."></textarea>
          </div>
        </div>
        <!-- Room -->
        <div class="card p3 rounded mb3">
          <h2 style="font-weight:700;margin-bottom:1.125rem">🛏️ Room Type</h2>
          <div style="display:flex;flex-direction:column;gap:.625rem">
            <?php foreach($rooms as $i=>$rm):?>
              <label style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s;gap:1rem" onclick="this.style.borderColor='var(--gold)'">
                <div style="display:flex;align-items:center;gap:.875rem;min-width:0">
                  <input type="radio" name="room_id" value="<?=$rm['id']?>" data-price="<?=$rm['base_price']?>" <?=($rm['id']==$rid||$i===0&&!$rid)?'checked':''?> onchange="calc()" style="accent-color:var(--gold);width:16px;height:16px;flex-shrink:0">
                  <div><div style="font-weight:700;font-size:.9rem"><?=e($rm['name'])?></div><div style="font-size:.75rem;color:var(--text3)">👥 Max <?=$rm['max_guests']?> · <?=e(ucfirst($rm['type']))?></div></div>
                </div>
                <div style="font-weight:700;color:var(--gold);white-space:nowrap;flex-shrink:0">NPR <?=number_format($rm['base_price'])?><span style="font-weight:400;color:var(--text3);font-size:.72rem">/night</span></div>
              </label>
            <?php endforeach;?>
          </div>
        </div>
        <!-- Extras -->
        <div class="card p3 rounded">
          <h2 style="font-weight:700;margin-bottom:.3rem">✨ Extra Services</h2>
          <p style="font-size:.78rem;color:var(--text3);margin-bottom:1rem">NPR 500 each</p>
          <div class="g2" style="gap:.5rem">
            <?php foreach(['🚗 Airport Pickup','🍳 Breakfast','🌙 Late Checkout','💐 Room Decoration','💆 Spa Package','🏙️ City Tour'] as $s):?>
              <label style="display:flex;align-items:center;gap:.5rem;padding:.625rem;background:var(--bg);border:1px solid var(--border);border-radius:9px;cursor:pointer;font-size:.8125rem;color:var(--text2);transition:border-color .2s">
                <input type="checkbox" name="extras[]" value="<?=$s?>" onchange="calc()" style="accent-color:var(--gold)"> <?=$s?>
              </label>
            <?php endforeach;?>
          </div>
        </div>
      </div>
      <!-- Price Summary -->
      <div style="position:sticky;top:80px">
        <div class="card p3 rounded" style="border-color:rgba(212,160,23,.25)">
          <h3 style="font-weight:700;margin-bottom:1.125rem">💰 Price Summary</h3>
          <div style="font-size:.875rem;display:flex;flex-direction:column;gap:.55rem;margin-bottom:1rem">
            <div class="flex jcsb"><span class="muted">Nights</span><span id="s-n" style="font-weight:600">1 night</span></div>
            <div class="flex jcsb"><span class="muted">Room cost</span><span id="s-r" style="font-weight:600">—</span></div>
            <div class="flex jcsb"><span class="muted">Extras</span><span id="s-e" style="font-weight:600">NPR 0</span></div>
            <div class="flex jcsb"><span class="muted">Tax (13%)</span><span id="s-t" style="font-weight:600">—</span></div>
            <div class="flex jcsb"><span class="muted">Service (5%)</span><span id="s-sv" style="font-weight:600">—</span></div>
          </div>
          <hr class="divider" style="margin:.625rem 0">
          <div class="flex jcsb aic" style="margin-bottom:1.5rem">
            <span style="font-weight:700">Total</span>
            <span id="s-tot" class="gold-text" style="font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800">NPR 0</span>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Confirm & Pay →</button>
          <p class="tc muted" style="font-size:.7rem;margin-top:.625rem">🔒 Secure · Free cancellation</p>
        </div>
      </div>
    </div>
  </form>
</div>
</div>
<script>
var base=<?=(float)($rooms[0]['base_price']??$h['min_price'])?>;
function fmt(v){return'NPR '+v.toLocaleString();}
function calc(){
  var ci=new Date(document.getElementById('ci').value);
  var co=new Date(document.getElementById('co').value);
  if(isNaN(ci)||isNaN(co)||co<=ci)return;
  var nights=Math.round((co-ci)/864e5);
  var r=document.querySelector('input[name="room_id"]:checked');
  var rp=r?parseFloat(r.dataset.price):base;
  var ad=parseInt(document.getElementById('ad').value)||1;
  if(ad>2)rp*=(1+(ad-2)*0.12);
  var ec=document.querySelectorAll('input[name="extras[]"]:checked').length*500;
  var rc=Math.round(rp*nights);var tax=Math.round((rc+ec)*0.13);var svc=Math.round((rc+ec)*0.05);var tot=rc+ec+tax+svc;
  document.getElementById('s-n').textContent=nights+' night'+(nights>1?'s':'');
  document.getElementById('s-r').textContent=fmt(rc);
  document.getElementById('s-e').textContent=fmt(ec);
  document.getElementById('s-t').textContent=fmt(tax);
  document.getElementById('s-sv').textContent=fmt(svc);
  document.getElementById('s-tot').textContent=fmt(tot);
}
calc();
</script>
<?php include __DIR__.'/includes/footer.php';?>
