<h2>Repair Estimate</h2>
<p>Job ID: <?=$job->id?></p>
<p>Customer: <?=$user->display_name?></p>

<table border="1" width="100%">
<?php foreach($items as $i): ?>
<tr>
<td><?=$i->sku?></td>
<td><?=$i->qty?></td>
<td><?=$i->total?></td>
</tr>
<?php endforeach; ?>
</table>

<p>Total: ₹<?=$job->total?></p>
