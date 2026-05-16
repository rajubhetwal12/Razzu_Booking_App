<?php // tab_hotels.php — lists manager's hotels with manage links ?>
<div class="hotels-grid">
  <?php if(empty($hotels)):?>
    <div class="tc" style="padding:3rem;color:var(--text3);grid-column:1/-1">
      <div style="font-size:3rem;margin-bottom:.75rem">🏨</div>
      <p>No hotels yet. <a href="?tab=add_hotel" style="color:var(--gold)">Add your first →</a></p>
    </div>
  <?php else:?>
    <?php foreach($hotels as $hh):?>
      <div class="card rounded" style="overflow:hidden">
        <div style="height:160px;overflow:hidden;position:relative">
          <img src="<?=e($hh['cover_image'])?>" style="width:100%;height:100%;object-fit:cover">
          <div style="position:absolute;top:.5rem;right:.5rem">
            <span class="badge badge-<?=$hh['is_verified']?'green':'gold'?>"><?=$hh['is_verified']?'✅ Verified':'⏳ Pending'?></span>
          </div>
        </div>
        <div class="p3">
          <div style="font-weight:700;margin-bottom:.2rem"><?=e($hh['name'])?></div>
          <div class="muted" style="font-size:.78rem;margin-bottom:.75rem">📍 <?=e($hh['city'])?> · <?=$hh['stars']?>★ · NPR <?=number_format($hh['min_price'])?>/night</div>
          <div style="display:flex;gap:.375rem;flex-wrap:wrap">
            <a href="?tab=images&hotel_id=<?=$hh['id']?>" class="btn btn-outline btn-sm">🖼️ Gallery</a>
            <a href="?tab=rooms&hotel_id=<?=$hh['id']?>" class="btn btn-outline btn-sm">🛏️ Rooms</a>
            <a href="?tab=edit_hotel&hotel_id=<?=$hh['id']?>" class="btn btn-outline btn-sm">✏️ Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach;?>
  <?php endif;?>
</div>
