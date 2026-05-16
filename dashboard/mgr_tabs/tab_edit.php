<?php // tab_edit.php — uses $selHotel, $hid ?>
<div class="card p3 rounded">
  <h2 style="font-weight:700;margin-bottom:1.5rem">✏️ Edit Hotel: <?=e($selHotel['name'])?></h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="hotel_id" value="<?=$hid?>">
    <div class="g2">
      <div class="form-group"><label class="form-label">Hotel Name *</label><input type="text" name="name" class="form-input" value="<?=e($selHotel['name'])?>" required></div>
      <div class="form-group"><label class="form-label">City *</label><input type="text" name="city" class="form-input" value="<?=e($selHotel['city'])?>" required></div>
    </div>
    <div class="form-group"><label class="form-label">Short Description</label><input type="text" name="short_desc" class="form-input" value="<?=e($selHotel['short_desc']??'')?>"></div>
    <div class="form-group"><label class="form-label">Full Description</label><textarea name="description" class="form-input" rows="4"><?=e($selHotel['description']??'')?></textarea></div>
    <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-input" value="<?=e($selHotel['address']??'')?>"></div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Category</label><select name="category" class="form-input"><?php foreach(['luxury','resort','boutique','standard','budget'] as $c):?><option value="<?=$c?>" <?=$selHotel['category']===$c?'selected':''?>><?=ucfirst($c)?></option><?php endforeach;?></select></div>
      <div class="form-group"><label class="form-label">Stars</label><select name="stars" class="form-input"><?php for($s=5;$s>=1;$s--):?><option value="<?=$s?>" <?=(int)$selHotel['stars']===$s?'selected':''?>><?=$s?>★</option><?php endfor;?></select></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Check-in</label><input type="time" name="check_in" class="form-input" value="<?=e($selHotel['check_in_time']??'14:00')?>"></div>
      <div class="form-group"><label class="form-label">Check-out</label><input type="time" name="check_out" class="form-input" value="<?=e($selHotel['check_out_time']??'11:00')?>"></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="<?=e($selHotel['phone']??'')?>"></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?=e($selHotel['email']??'')?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Base Price (NPR/night)</label><input type="number" name="min_price" class="form-input" value="<?=$selHotel['min_price']?>" min="100" required></div>
    <div class="form-group">
      <label class="form-label">Update Cover Image</label>
      <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
        <?php if($selHotel['cover_image']):?><img src="<?=e($selHotel['cover_image'])?>" style="width:80px;height:60px;object-fit:cover;border-radius:8px"><?php endif;?>
        <input type="file" name="cover_file" class="form-input" accept="image/*" style="padding:.5rem;flex:1">
      </div>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <button type="submit" name="edit_hotel" value="1" class="btn btn-primary">Save Changes</button>
      <a href="?tab=images&hotel_id=<?=$hid?>" class="btn btn-outline">🖼️ Manage Gallery</a>
      <a href="?tab=rooms&hotel_id=<?=$hid?>" class="btn btn-outline">🛏️ Manage Rooms</a>
    </div>
  </form>
</div>
