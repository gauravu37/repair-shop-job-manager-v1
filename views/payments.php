<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to'] ?? date('Y-m-d');

$from_date = $from . " 00:00:00";
$to_date   = $to . " 23:59:59";

$results = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT method, SUM(amount) as total 
		 FROM {$wpdb->prefix}rsjm_payments
		 WHERE created_at BETWEEN %s AND %s
		 GROUP BY method",
		$from_date,
		$to_date
	),
	ARRAY_A
);

// Convert to easy format
$data = [
	'cash' => 0,
	'upi' => 0,
	'bank' => 0
];

foreach($results as $r){
	$key = strtolower($r['method']);
	$data[$key] = floatval($r['total']);
}

$grand = array_sum($data);
?>


<div class="rsjm-wrap">

    <h2 class="rsjm-title">💰 Payments Report</h2>

    <form method="get" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="rsjm-payments">

        From: <input type="date" name="from" value="<?=esc_attr($from)?>">
        To: <input type="date" name="to" value="<?=esc_attr($to)?>">

        <button class="rsjm-btn">Filter</button>

        <a href="?page=rsjm-payments&from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="rsjm-btn">Today</a>

        <a href="?page=rsjm-payments&from=<?=date('Y-m-d', strtotime('-7 days'))?>&to=<?=date('Y-m-d')?>" class="rsjm-btn">7 Days</a>

        <a href="?page=rsjm-payments&from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>" class="rsjm-btn">This Month</a>

        <a href="?page=rsjm-payments&from=<?=date('Y-01-01')?>&to=<?=date('Y-m-d')?>" class="rsjm-btn">This Year</a>

    </form>
	
	
	<div class="rsjm-grid">

		<div class="rsjm-card">
			<h3>💵 Cash</h3>
			<h2>₹<?=number_format($data['cash'],2)?></h2>
		</div>

		<div class="rsjm-card">
			<h3>📱 UPI</h3>
			<h2>₹<?=number_format($data['upi'],2)?></h2>
		</div>

		<div class="rsjm-card">
			<h3>🏦 Bank</h3>
			<h2>₹<?=number_format($data['bank'],2)?></h2>
		</div>

		<div class="rsjm-card">
			<h3>Total</h3>
			<h2>₹<?=number_format($grand,2)?></h2>
		</div>

	</div>
	
	<table class="rsjm-table" style="margin-top:20px;">
		<thead>
			<tr>
				<th>Method</th>
				<th>Total</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($data as $method => $amount): ?>
			<tr>
				<td><?=ucfirst($method)?></td>
				<td>₹<?=number_format($amount,2)?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<?php 
		$payments = $wpdb->get_results(
			"SELECT p.*, u.display_name, u.user_login
			 FROM {$wpdb->prefix}rsjm_payments p
			 LEFT JOIN {$wpdb->prefix}rsjm_jobs j ON p.job_id = j.id
			 LEFT JOIN {$wpdb->users} u ON j.customer_id = u.ID
			 ORDER BY p.created_at DESC
			 LIMIT 500"
		);
	?>

	<h3 style="margin-top:30px;">📜 Payment History</h3>

	<table id="rsjm-payments-table" class="rsjm-table" style="background-color:#fff">
		<thead>
			<tr>
				<th>Date</th>
				<th>Job ID</th>
				<th>Customer</th>
				<th>Method</th>
				<th>Amount</th>
				<th>Note</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($payments as $p): ?>
			<tr>
				<td><?= date('d M Y H:i', strtotime($p->created_at)) ?></td>

				<td>
					<a href="<?= admin_url('admin.php?page=rsjm-view-job&job_id='.$p->job_id) ?>">
						#<?= $p->job_id ?>
					</a>
				</td>

				<td>
					<?= esc_html($p->display_name) ?><br>
					<small><?= esc_html($p->user_login) ?></small>
				</td>

				<td>
					<span class="badge badge-<?= strtolower($p->method) ?>">
						<?= ucfirst($p->method) ?>
					</span>
				</td>

				<td><strong>₹<?= number_format($p->amount,2) ?></strong></td>

				<td><?= esc_html($p->note) ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>



<script>
jQuery(document).ready(function($){

    $('#rsjm-payments-table').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        responsive: true
    });

});
</script>