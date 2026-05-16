<?php
// tab_about.php
try{
  $hls=$db->prepare('SELECT * FROM hotel_highlights WHERE hotel_id=? ORDER BY sort_order');
  $hls->execute([$hid]);$hls=$hls->fetchAll();
}catch(Exception $e){$hls=[];}
?>
<div class="g2" style="align-items:start">
  <!-- Description -->
  <div>
    <div class="card p3 rounded mb3">
      <h3 style="font-weight:700;margin-bottom:1rem">📝 Description — <?=e($selHotel['name'])?></h3>
      <form method="POST">
        <input type="hidden" name="hotel_id" value="<?=$hid?>">
        <div class="form-group"><label class="form-label">Short Description</label><input type="text" name="short_desc" class="form-input" value="<?=e($selHotel['short_desc']??'')?>"></div>
        <div class="form-group"><label class="form-label">Full Description</label><textarea name="description" class="form-input" rows="6"><?=e($selHotel['description']??'')?></textarea></div>
        <button type="submit" name="save_about" value="1" class="btn btn-primary">Save Description</button>
      </form>
    </div>
  </div>
  <!-- Highlights -->
  <div>
    <div class="card p3 rounded mb3">
      <h3 style="font-weight:700;margin-bottom:1rem">✨ Hotel Highlights</h3>
      <form method="POST">
        <input type="hidden" name="hotel_id" value="<?=$hid?>">
        <div class="g2">
          <div class="form-group"><label class="form-label">Icon (emoji)</label><input type="text" name="h_icon" class="form-input" value="✨" style="width:80px"></div>
          <div class="form-group"><label class="form-label">Title *</label><input type="text" name="h_title" class="form-input" placeholder="e.g. Mountain View" required></div>
        </div>
        <div class="form-group"><label class="form-label">Detail</label><input type="text" name="h_detail" class="form-input" placeholder="Brief description..."></div>
        <button type="submit" name="add_highlight" value="1" class="btn btn-outline btn-sm">+ Add Highlight</button>
      </form>
    </div>
    <?php if(!empty($hls)):?>
      <div class="card rounded" style="overflow:hidden">
        <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--border)"><h4 style="font-weight:700;font-size:.9rem">Current Highlights</h4></div>
        <div style="padding:.875rem;display:flex;flex-direction:column;gap:.5rem">
          <?php foreach($hls as $hl):?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.625rem .875rem;background:var(--bg);border:1px solid var(--border);border-radius:8px">
              <div style="font-size:.875rem"><?=e($hl['icon']??'✨')?> <strong><?=e($hl['title'])?></strong><?php if($hl['detail']):?> — <span style="color:var(--text3)"><?=e($hl['detail'])?></span><?php endif;?></div>
              <a href="?del_hl=<?=$hl['id']?>&hotel_id=<?=$hid?>&tab=about" class="badge badge-red" style="cursor:pointer;font-size:.65rem" onclick="return confirm('Delete?')">✕</a>
            </div>
          <?php endforeach;?>
        </div>
      </div>
    <?php elseif(empty($hls)):?>
      <p style="font-size:.8rem;color:var(--text3)">No highlights yet. Add some above to showcase your hotel's best features.</p>
    <?php endif;?>
  </div>
</div>
