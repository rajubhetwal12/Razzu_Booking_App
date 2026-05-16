<?php // tab_bookings.php ?>
<div class="card rounded" style="overflow:hidden">
  <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">All Bookings (<?=count($bkgs)?>)</h3></div>
  <?php if(empty($bkgs)):?>
    <div class="tc" style="padding:2.5rem;color:var(--text3)">No bookings yet.</div>
  <?php else:?>
    <div class="overflow-x"><table class="data-table"><thead><tr><th>Ref</th><th>Guest</th><th>Hotel</th><th>Dates</th><th>Nights</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($bkgs as $b):?>
      <tr>
        <td style="color:var(--gold);font-family:monospace;font-size:.75rem"><?=e($b['booking_ref'])?></td>
        <td><div style="font-weight:600;font-size:.875rem"><?=e($b['cname'])?></div><div style="font-size:.7rem;color:var(--text3)"><?=date('M j, Y',strtotime($b['created_at']))?></div></td>
        <td style="font-size:.8rem"><?=e($b['hname'])?></td>
        <td style="font-size:.75rem;color:var(--text3)"><?=date('M j',strtotime($b['check_in']))?> → <?=date('M j',strtotime($b['check_out']))?></td>
        <td><?=$b['nights']?></td>
        <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
        <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
        <td style="white-space:nowrap">
          <?php if($b['status']==='pending'):?>
            <a href="?approve=<?=$b['id']?>&tab=bookings" class="badge badge-green" style="cursor:pointer;margin-right:.25rem">✓ Approve</a>
            <a href="?cancel=<?=$b['id']?>&tab=bookings" class="badge badge-red" style="cursor:pointer" onclick="return confirm('Cancel booking?')">✗ Cancel</a>
          <?php else:?>—<?php endif;?>
        </td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  <?php endif;?>
</div>
