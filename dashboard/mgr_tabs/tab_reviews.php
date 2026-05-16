<?php
// tab_reviews.php
$revs=$db->prepare('SELECT r.*,u.name AS uname FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.hotel_id=? ORDER BY r.created_at DESC LIMIT 50');
$revs->execute([$hid]);$revs=$revs->fetchAll();
?>
<div class="card rounded" style="overflow:hidden">
  <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">⭐ Guest Reviews — <?=e($selHotel['name'])?> (<?=count($revs)?>)</h3></div>
  <?php if(empty($revs)):?>
    <div class="tc" style="padding:3rem;color:var(--text3)">No reviews yet.</div>
  <?php else:?>
    <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1.25rem">
      <?php foreach($revs as $rv):?>
        <div class="card p3 rounded <?=$rv['is_hidden']?'':'?>'>" style="<?=$rv['is_hidden']?'opacity:.5':''?>">
          <div class="flex aic jcsb mb2">
            <div>
              <span style="font-weight:700;font-size:.9rem"><?=e($rv['uname'])?></span>
              <span style="color:var(--gold);margin-left:.5rem"><?=str_repeat('★',(int)$rv['rating'])?></span>
              <?php if($rv['is_hidden']):?><span class="badge badge-red" style="margin-left:.5rem">Hidden</span><?php endif;?>
            </div>
            <span style="font-size:.72rem;color:var(--text3)"><?=date('M j, Y',strtotime($rv['created_at']))?></span>
          </div>
          <?php if($rv['title']):?><div style="font-weight:600;margin-bottom:.3rem"><?=e($rv['title'])?></div><?php endif;?>
          <p style="font-size:.875rem;color:var(--text2);line-height:1.7;margin-bottom:.875rem"><?=e($rv['comment']??'')?></p>
          <?php if($rv['manager_reply']):?>
            <div style="padding:.75rem;background:rgba(212,160,23,.06);border-left:3px solid var(--gold);border-radius:0 8px 8px 0;margin-bottom:.875rem">
              <div style="font-size:.72rem;color:var(--gold);font-weight:700;margin-bottom:.25rem">🏨 Your Reply</div>
              <p style="font-size:.8rem;color:var(--text3)"><?=e($rv['manager_reply'])?></p>
            </div>
          <?php endif;?>
          <form method="POST">
            <input type="hidden" name="hotel_id" value="<?=$hid?>">
            <input type="hidden" name="review_id" value="<?=$rv['id']?>">
            <div class="form-group" style="margin-bottom:.5rem">
              <textarea name="reply_text" class="form-input" rows="2" placeholder="Write a reply to this review..."><?=e($rv['manager_reply']??'')?></textarea>
            </div>
            <button type="submit" name="reply_review" value="1" class="btn btn-outline btn-sm"><?=$rv['manager_reply']?'Update Reply':'Post Reply'?></button>
          </form>
        </div>
      <?php endforeach;?>
    </div>
  <?php endif;?>
</div>
