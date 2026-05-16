<?php
// tab_rooms.php
$rooms=$db->prepare('SELECT * FROM rooms WHERE hotel_id=? ORDER BY base_price');
$rooms->execute([$hid]);$rooms=$rooms->fetchAll();
?>
<div class="card p3 rounded mb3">
  <h2 style="font-weight:700;margin-bottom:1rem">➕ Add Room — <?=e($selHotel['name'])?></h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="hotel_id" value="<?=$hid?>">
    <div class="g2">
      <div class="form-group"><label class="form-label">Room Type</label><select name="rtype" class="form-input"><?php foreach(['standard','deluxe','luxury','presidential','couple','family'] as $t):?><option value="<?=$t?>"><?=ucfirst($t)?></option><?php endforeach;?></select></div>
      <div class="form-group"><label class="form-label">Room Name *</label><input type="text" name="rname" class="form-input" placeholder="Deluxe Mountain View" required></div>
    </div>
    <div class="form-group"><label class="form-label">Description</label><textarea name="rdesc" class="form-input" rows="2" placeholder="Spacious room with panoramic views..."></textarea></div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Price/Night (NPR) *</label><input type="number" name="rprice" class="form-input" value="8000" min="100" required></div>
      <div class="form-group"><label class="form-label">Quantity</label><input type="number" name="qty" class="form-input" value="5" min="1"></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Max Guests</label><input type="number" name="max_guests" class="form-input" value="2" min="1" max="20"></div>
      <div class="form-group"><label class="form-label">Room Size (m²)</label><input type="number" name="room_size" class="form-input" value="32" min="0"></div>
    </div>
    <div class="g2">
      <div class="form-group"><label class="form-label">Max Adults</label><input type="number" name="max_adults" class="form-input" value="2" min="1"></div>
      <div class="form-group"><label class="form-label">Max Children</label><input type="number" name="max_children" class="form-input" value="0" min="0"></div>
    </div>
    <div class="form-group"><label class="form-label">Cancellation Policy</label><input type="text" name="cancel_policy" class="form-input" placeholder="Free cancellation up to 24h before check-in"></div>
    <div class="g2" style="align-items:center">
      <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;cursor:pointer"><input type="checkbox" name="breakfast" value="1" style="accent-color:var(--gold)"> Breakfast Included</label>
    </div>
    <div class="form-group mt2"><label class="form-label">Room Image</label><input type="file" name="room_img" class="form-input" accept="image/*" style="padding:.5rem"></div>
    <button type="submit" name="add_room" value="1" class="btn btn-primary">Add Room</button>
  </form>
</div>

<div class="card rounded" style="overflow:hidden">
  <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">Rooms (<?=count($rooms)?>)</h3></div>
  <?php if(empty($rooms)):?>
    <div class="tc" style="padding:2.5rem;color:var(--text3)">No rooms yet.</div>
  <?php else:?>
    <div class="overflow-x"><table class="data-table"><thead><tr><th>Room</th><th>Type</th><th>Price</th><th>Qty</th><th>Max Guests</th><th>Breakfast</th><th>Action</th></tr></thead><tbody>
    <?php foreach($rooms as $rm):?>
      <tr>
        <td><div style="font-weight:600;font-size:.875rem"><?=e($rm['name'])?></div><div style="font-size:.72rem;color:var(--text3)"><?=e(mb_substr($rm['description']??'',0,40))?></div></td>
        <td><span class="badge badge-gold"><?=ucfirst($rm['type'])?></span></td>
        <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($rm['base_price'])?></td>
        <td><?=$rm['quantity']?></td>
        <td><?=$rm['max_guests']?></td>
        <td><?=$rm['breakfast_included']?'<span class="badge badge-green">✓ Yes</span>':'<span class="badge badge-gray">No</span>'?></td>
        <td><a href="?del_room=<?=$rm['id']?>&hotel_id=<?=$hid?>&tab=rooms" class="badge badge-red" style="cursor:pointer" onclick="return confirm('Delete room?')">🗑 Delete</a></td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  <?php endif;?>
</div>
