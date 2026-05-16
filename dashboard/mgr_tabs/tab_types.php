<?php if(!$selHotel):?>
<div class="tab-card tc"><p class="muted">Select a hotel from My Hotels first.</p></div>
<?php else:?>
<div class="tab-card" style="margin-bottom:1.5rem">
  <h3 style="font-weight:700;margin-bottom:1.25rem">🏷️ Hotel Types — <?=e($selHotel['name'])?></h3>
  <?php if(empty($allTypes)):?>
    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:1rem;font-size:.875rem">
      ⚠️ Please run <strong>hotel_settings_migration.sql</strong> in phpMyAdmin first to enable hotel types.
    </div>
  <?php else:?>
  <p class="muted" style="font-size:.875rem;margin-bottom:1.25rem">Select which accommodation types your hotel offers.</p>
  <form method="POST">
    <input type="hidden" name="hotel_id" value="<?=$selHotelId?>">
    <div class="type-grid">
      <?php foreach($allTypes as $t):$sel=in_array($t['type_key'],$offeredTypes);?>
      <label class="type-card <?=$sel?'selected':''?>" style="border-color:<?=$sel?$t['badge_color']:'var(--border)'?>">
        <input type="checkbox" name="types[]" value="<?=$t['type_key']?>" <?=$sel?'checked':''?> style="display:none">
        <div style="font-size:1.75rem;margin-bottom:.5rem"><?=$t['icon']?></div>
        <div style="font-weight:700;margin-bottom:.25rem"><?=e($t['display_name'])?></div>
        <div style="font-size:.75rem;color:var(--text3)"><?=e($t['tagline']??'')?></div>
        <?php if($t['price_label']):?><div style="font-size:.75rem;color:var(--gold);margin-top:.375rem"><?=e($t['price_label'])?></div><?php endif;?>
      </label>
      <?php endforeach;?>
    </div>
    <button type="submit" name="save_hotel_types" value="1" class="btn btn-primary">💾 Save Hotel Types</button>
  </form>
  <?php endif;?>
</div>
<?php endif;?>
<script>
document.querySelectorAll('.type-card').forEach(c=>{
  c.addEventListener('click',function(e){
    if(e.target.tagName==='INPUT')return;
    const cb=this.querySelector('input');cb.checked=!cb.checked;
    this.classList.toggle('selected',cb.checked);
    this.style.borderColor=cb.checked?this.dataset.color||'var(--gold)':'var(--border)';
  });
});
</script>
