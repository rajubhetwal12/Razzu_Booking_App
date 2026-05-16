<?php
// tab_facilities.php
$allFacs=$db->query('SELECT * FROM facilities ORDER BY category,name')->fetchAll();
$selFacs=$db->prepare('SELECT facility_id FROM hotel_facilities WHERE hotel_id=?');
$selFacs->execute([$hid]);$selIds=array_column($selFacs->fetchAll(),'facility_id');
$bycat=[];foreach($allFacs as $f){$bycat[$f['category']][]=$f;}
?>
<div class="card p3 rounded">
  <h2 style="font-weight:700;margin-bottom:1.25rem">✅ Facilities — <?=e($selHotel['name'])?></h2>
  <form method="POST">
    <input type="hidden" name="hotel_id" value="<?=$hid?>">
    <?php foreach($bycat as $cat=>$facs):?>
      <div style="margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:.625rem;padding-bottom:.375rem;border-bottom:1px solid var(--border)"><?=$cat?></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.5rem">
          <?php foreach($facs as $f):?>
            <label style="display:flex;align-items:center;gap:.625rem;padding:.625rem .75rem;background:var(--bg);border:1px solid <?=in_array($f['id'],$selIds)?'rgba(212,160,23,.5)':'var(--border)'?>;border-radius:9px;cursor:pointer;font-size:.8125rem;color:var(--text2);transition:border-color .2s">
              <input type="checkbox" name="fids[]" value="<?=$f['id']?>" <?=in_array($f['id'],$selIds)?'checked':''?> style="accent-color:var(--gold)">
              <span><?=$f['icon']??'✅'?></span> <?=e($f['name'])?>
            </label>
          <?php endforeach;?>
        </div>
      </div>
    <?php endforeach;?>
    <button type="submit" name="save_facilities" value="1" class="btn btn-primary">Save Facilities</button>
  </form>
</div>
