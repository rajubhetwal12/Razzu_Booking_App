<?php
// tab_images.php — Gallery management
$imgs=$db->prepare('SELECT * FROM hotel_images WHERE hotel_id=? ORDER BY is_cover DESC,sort_order ASC');
$imgs->execute([$hid]);$imgs=$imgs->fetchAll();
?>
<div class="card p3 rounded mb3">
  <h2 style="font-weight:700;margin-bottom:1rem">🖼️ Upload Image — <?=e($selHotel['name'])?></h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="hotel_id" value="<?=$hid?>">
    <div class="g2">
      <div class="form-group"><label class="form-label">Select Image (JPG/PNG/WebP, max 5MB)</label><input type="file" name="gallery_file" class="form-input" accept="image/*" style="padding:.5rem" required></div>
      <div class="form-group"><label class="form-label">Caption (optional)</label><input type="text" name="caption" class="form-input" placeholder="e.g. Pool Area, Lobby..."></div>
    </div>
    <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--text2);margin-bottom:1rem;cursor:pointer">
      <input type="checkbox" name="is_cover" value="1" style="accent-color:var(--gold)"> Set as cover image
    </label>
    <button type="submit" name="upload_gallery" value="1" class="btn btn-primary">Upload Image</button>
  </form>
</div>

<div class="card rounded" style="overflow:hidden">
  <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">Gallery (<?=count($imgs)?> images)</h3></div>
  <?php if(empty($imgs)):?>
    <div class="tc" style="padding:3rem;color:var(--text3)">No images yet. Upload your first photo above.</div>
  <?php else:?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;padding:1.5rem">
      <?php foreach($imgs as $img):?>
        <div style="position:relative;border-radius:12px;overflow:hidden;border:2px solid <?=$img['is_cover']?'var(--gold)':'var(--border)'?>">
          <img src="<?=e($img['image_url'])?>" style="width:100%;height:130px;object-fit:cover;display:block">
          <?php if($img['is_cover']):?><div style="position:absolute;top:.4rem;left:.4rem;background:var(--gold);color:#08081a;font-size:.65rem;font-weight:700;padding:.15rem .5rem;border-radius:20px">⭐ Cover</div><?php endif;?>
          <div style="padding:.625rem;background:var(--bg2)">
            <div style="font-size:.75rem;color:var(--text3);margin-bottom:.5rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($img['caption']??'No caption')?></div>
            <div style="display:flex;gap:.375rem">
              <?php if(!$img['is_cover']):?>
                <a href="?set_cover=<?=$img['id']?>&hotel_id=<?=$hid?>&tab=images" class="badge badge-gold" style="cursor:pointer;font-size:.65rem">★ Set Cover</a>
              <?php endif;?>
              <a href="?del_img=<?=$img['id']?>&hotel_id=<?=$hid?>&tab=images" class="badge badge-red" style="cursor:pointer;font-size:.65rem" onclick="return confirm('Delete image?')">🗑 Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
  <?php endif;?>
</div>
