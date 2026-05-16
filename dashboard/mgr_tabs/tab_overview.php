<?php
// tab_overview.php — uses $hotels, $bkgs, $conf, $pend, $rev from manager.php
?>
<div class="g2 mb3">
  <div class="stat-card rounded"><div style="font-size:1.25rem;margin-bottom:.2rem">✅</div><div class="stat-num gold-text"><?=$conf?></div><div class="stat-lbl">Confirmed</div></div>
  <div class="stat-card rounded"><div style="font-size:1.25rem;margin-bottom:.2rem">⭐</div><div class="stat-num gold-text"><?=$hotels?number_format(array_sum(array_column($hotels,'rating'))/count($hotels),1):'—'?></div><div class="stat-lbl">Avg Rating</div></div>
</div>
<div class="card rounded" style="overflow:hidden">
  <div style="padding:1.125rem 1.5rem;border-bottom:1px solid var(--border)"><h3 style="font-weight:700">Recent Bookings</h3></div>
  <?php if(empty($bkgs)):?>
    <div class="tc" style="padding:2.5rem;color:var(--text3)">No bookings yet.</div>
  <?php else:?>
    <div class="overflow-x"><table class="data-table"><thead><tr><th>Guest</th><th>Hotel</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead><tbody>
    <?php foreach(array_slice($bkgs,0,8) as $b):?>
      <tr>
        <td><div style="font-weight:600;font-size:.875rem"><?=e($b['cname'])?></div><div style="font-size:.7rem;color:var(--text3)"><?=e($b['cemail'])?></div></td>
        <td style="font-size:.8125rem"><?=e($b['hname'])?></td>
        <td style="font-size:.78rem;color:var(--text3)"><?=date('M j',strtotime($b['check_in']))?> → <?=date('M j',strtotime($b['check_out']))?></td>
        <td style="font-weight:700;color:var(--gold)">NPR <?=number_format($b['total_amount'])?></td>
        <td><span class="badge badge-<?=$b['status']==='confirmed'?'green':($b['status']==='cancelled'?'red':'gold')?>"><?=$b['status']?></span></td>
      </tr>
    <?php endforeach;?></tbody></table></div>
  <?php endif;?>
</div>
