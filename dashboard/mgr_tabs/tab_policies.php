<?php
// tab_policies.php — wrapped in try/catch for missing tables
try{
  $pol=$db->prepare('SELECT * FROM hotel_policies WHERE hotel_id=?');
  $pol->execute([$hid]);$pol=$pol->fetch();
}catch(Exception $e){$pol=null;}
?>
<div class="card p3 rounded">
  <h2 style="font-weight:700;margin-bottom:1.25rem">📋 Policies — <?=e($selHotel['name'])?></h2>
  <form method="POST">
    <input type="hidden" name="hotel_id" value="<?=$hid?>">
    <div class="g2">
      <div class="form-group"><label class="form-label">Check-in Time</label><input type="time" name="check_in" class="form-input" value="<?=e($pol['check_in_time']??$selHotel['check_in_time']??'14:00')?>"></div>
      <div class="form-group"><label class="form-label">Check-out Time</label><input type="time" name="check_out" class="form-input" value="<?=e($pol['check_out_time']??$selHotel['check_out_time']??'11:00')?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Cancellation Policy</label><textarea name="cancellation_policy" class="form-input" rows="2" placeholder="Free cancellation up to 24h before check-in..."><?=e($pol['cancellation_policy']??'')?></textarea></div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Smoking Policy</label><select name="smoking_policy" class="form-input"><option value="not_allowed" <?=($pol['smoking_policy']??'')==='not_allowed'?'selected':''?>>Not Allowed</option><option value="allowed" <?=($pol['smoking_policy']??'')==='allowed'?'selected':''?>>Allowed</option><option value="designated" <?=($pol['smoking_policy']??'')==='designated'?'selected':''?>>Designated Areas</option></select></div>
      <div class="form-group"><label class="form-label">Pet Policy</label><select name="pet_policy" class="form-input"><option value="not_allowed" <?=($pol['pet_policy']??'')==='not_allowed'?'selected':''?>>Not Allowed</option><option value="allowed" <?=($pol['pet_policy']??'')==='allowed'?'selected':''?>>Allowed</option><option value="on_request" <?=($pol['pet_policy']??'')==='on_request'?'selected':''?>>On Request</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Child Policy</label><textarea name="child_policy" class="form-input" rows="2" placeholder="Children under 5 stay free..."><?=e($pol['child_policy']??'')?></textarea></div>
    <div class="form-group"><label class="form-label">Extra Bed Policy</label><textarea name="extra_bed_policy" class="form-input" rows="2" placeholder="Extra beds available on request..."><?=e($pol['extra_bed_policy']??'')?></textarea></div>
    <div class="form-group"><label class="form-label">Important Information</label><textarea name="important_info" class="form-input" rows="3" placeholder="Pool hours, dress code, etc..."><?=e($pol['important_info']??'')?></textarea></div>
    <div class="form-group">
      <label class="form-label">Payment Methods Accepted</label>
      <?php $pm=explode(',', $pol['payment_methods']??'esewa,khalti,cash'); foreach(['esewa'=>'eSewa','khalti'=>'Khalti','cash'=>'Cash','card'=>'Credit Card'] as $v=>$l):?>
        <label style="display:inline-flex;align-items:center;gap:.375rem;margin-right:1rem;font-size:.875rem;cursor:pointer">
          <input type="checkbox" name="payment_methods[]" value="<?=$v?>" <?=in_array($v,$pm)?'checked':''?> style="accent-color:var(--gold)"> <?=$l?>
        </label>
      <?php endforeach;?>
    </div>
    <button type="submit" name="save_policies" value="1" class="btn btn-primary">Save Policies</button>
  </form>
</div>
