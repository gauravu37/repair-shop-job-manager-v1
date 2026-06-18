<?php
	if (!defined('ABSPATH')) exit;

	global $wpdb;

	$from = $_GET['from'] ?? date('Y-m-01');
	$to   = $_GET['to'] ?? date('Y-m-d');

	$from_date = $from." 00:00:00";
	$to_date   = $to." 23:59:59";

	// SALES
	$sales = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT i.name,
					SUM(ji.qty) as qty,
					SUM(ji.total) as revenue,
					SUM(ji.qty * it.cost_price) as cost
			 FROM {$wpdb->prefix}rsjm_job_items ji
			 LEFT JOIN {$wpdb->prefix}rsjm_items it ON ji.item_id = it.id
			 LEFT JOIN {$wpdb->prefix}rsjm_jobs j ON ji.job_id = j.id
			 LEFT JOIN {$wpdb->prefix}rsjm_items i ON ji.item_id = i.id
			 WHERE j.delivery_date BETWEEN %s AND %s
			 GROUP BY ji.item_id",
			$from_date,
			$to_date
		),
		ARRAY_A
	);
	$total_qty = 0;
	$total_rev = 0;
	$total_profit = 0;

?>
<br><br>
<form method="get">
    <input type="hidden" name="page" value="rsjm-sales">

    From: <input type="date" name="from" value="<?= $from ?>">
    To: <input type="date" name="to" value="<?= $to ?>">

    <button class="rsjm-btn">Filter</button>
</form>


<table class="rsjm-table" style="margin-top:20px;background:#fff">
<thead>
<tr>
    <th>Item</th>
    <th>Qty Sold</th>
    <th>Revenue</th>
    <th>Cost</th>
    <th>Profit</th>
</tr>
</thead>

<tbody>
<?php foreach($sales as $s): 
    $profit = $s['revenue'] - $s['cost'];
	$total_qty += $s['qty'];
    $total_rev += $s['revenue'];
	$total_cos += $s['cost'];
    $total_profit += ($s['revenue'] - $s['cost']);
?>
<tr>
    <td><?= esc_html($s['name']) ?></td>
    <td><?= $s['qty'] ?></td>
    <td>₹<?= number_format($s['revenue'],2) ?></td>
    <td>₹<?= number_format($s['cost'],2) ?></td>
    <td style="color:<?= $profit >= 0 ? 'green' : 'red' ?>">
        ₹<?= number_format($profit,2) ?>
    </td>
</tr>

<?php endforeach; ?>
<tr style="background:#111;color:#fff;">
    <td><strong>Total</strong></td>
    <td><?= $total_qty ?></td>
    <td>₹<?= number_format($total_rev,2) ?></td>
	<td>₹<?= number_format($total_cos,2) ?></td>
    <td>₹<?= number_format($total_profit,2) ?></td>
</tr>
</tbody>
</table>