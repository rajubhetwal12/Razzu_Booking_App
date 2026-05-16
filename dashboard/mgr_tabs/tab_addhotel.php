<?php // tab_addhotel.php ?>
<div class="card p3 rounded">
  <h2 style="font-weight:700;margin-bottom:1.5rem">➕ Add New Hotel</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="g2">
      <div class="form-group"><label class="form-label">Hotel Name *</label><input type="text" name="name" class="form-input" placeholder="Kings Hotel" required></div>
      <div class="form-group"><label class="form-label">City *</label><input type="text" name="city" class="form-input" placeholder="Kathmandu" required></div>
    </div>
    <div class="form-group"><label class="form-label">Short Description</label><input type="text" name="short_desc" class="form-input" placeholder="One-line summary..."></div>
    <div class="form-group"><label class="form-label">Full Description</label><textarea name="description" class="form-input" rows="3" placeholder="Describe your hotel..."></textarea></div>
    <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-input" placeholder="Thamel, Kathmandu 44600"></div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Category</label><select name="category" class="form-input"><?php foreach(['luxury','resort','boutique','standard','budget'] as $c):?><option value="<?=$c?>"><?=ucfirst($c)?></option><?php endforeach;?></select></div>
      <div class="form-group"><label class="form-label">Stars</label><select name="stars" class="form-input"><?php for($s=5;$s>=1;$s--):?><option value="<?=$s?>"><?=$s?>★</option><?php endfor;?></select></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Check-in Time</label><input type="time" name="check_in" class="form-input" value="14:00"></div>
      <div class="form-group"><label class="form-label">Check-out Time</label><input type="time" name="check_out" class="form-input" value="11:00"></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" placeholder="+977-1-..."></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" placeholder="hotel@example.com"></div>
    </div>
    <div class="form-group"><label class="form-label">Base Price (NPR/night) *</label><input type="number" name="min_price" class="form-input" value="5000" min="100" required></div>
    <div class="form-group">
      <label class="form-label">Cover Image</label>
      <input type="file" name="cover_file" class="form-input" accept="image/*" style="padding:.5rem">
      <p style="font-size:.72rem;color:var(--text3);margin-top:.3rem">Upload a photo (JPG/PNG/WebP, max 5MB)</p>
    </div>
    <button type="submit" name="add_hotel" value="1" class="btn btn-primary">Submit Hotel →</button>
    <p class="muted" style="font-size:.75rem;margin-top:.5rem">Your hotel will be reviewed by admin before going live.</p>
  </form>
</div>
