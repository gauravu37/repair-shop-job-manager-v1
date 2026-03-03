<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

/* SECURITY TOKEN */
$job_id = intval($_GET['job'] ?? 0);
$token  = sanitize_text_field($_GET['token'] ?? '');

if (!$job_id || !$token) {
    wp_die('Invalid link');
}

/* SIMPLE TOKEN CHECK */
$expected = md5($job_id . NONCE_SALT);
if ($token !== $expected) {
    wp_die('Unauthorized access');
}

/* LOAD DATA */
$job = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
        $job_id
    )
);

if (!$job) wp_die('Job not found');

$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=%d",
        $job_id
    )
);

$user = get_user_by('id', $job->customer_id);

/* SETTINGS */
$shop_name = get_option('rsjm_shop_name');
$shop_addr = get_option('rsjm_shop_address');
$gst_no    = get_option('rsjm_gst_no');
$logo_url  = wp_get_attachment_url(get_option('rsjm_shop_logo'));
$upi_id    = get_option('rsjm_upi');

/* AMOUNT LOGIC */
$amount = ($job->status === 'ready' || $job->status === 'completed' || $job->status === 'partial')
    ? $job->total
    : $job->subtotal;

/* UPI LINK */
$upi_link = "upi://pay?pa={$upi_id}&pn=" . urlencode($shop_name) .
            "&am={$amount}&cu=INR";
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Job #<?php echo $job->id; ?></title>

<style>
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont;
    background:#f5f7fa;
    padding:15px;
}
.card {
    background:#fff;
    border-radius:12px;
    padding:16px;
    margin-bottom:15px;
    box-shadow:0 6px 20px rgba(0,0,0,.08);
}
h2,h3 { margin:0 0 10px }
.table {
    width:100%;
    border-collapse:collapse;
}
.table th,.table td {
    padding:8px;
    border-bottom:1px solid #eee;
    text-align:left;
}
.badge {
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}
.pending { background:#fff4e5;color:#92400e }
.in_progress { background:#e0f2fe;color:#075985 }
.ready { background:#dcfce7;color:#166534 }
.completed { background:#dcfce7;color:#166534 }
.partial { background:#fde68a;color:#92400e }

.pay-btn {
    display:block;
    text-align:center;
    background:#16a34a;
    color:#fff;
    padding:14px;
    border-radius:10px;
    text-decoration:none;
    font-size:18px;
    font-weight:600;
}
</style>
</head>

<body>

<div class="card" style="text-align:center">
<?php if($logo_url): ?>
<img src="<?php echo esc_url($logo_url); ?>" style="max-height:60px"><br>
<?php endif; ?>
<strong><?php echo esc_html($shop_name); ?></strong><br>
<small><?php echo nl2br(esc_html($shop_addr)); ?></small><br>
<?php if($gst_no): ?>
<small>GST: <?php echo esc_html($gst_no); ?></small>
<?php endif; ?>
</div>

<div class="card">
<h3>Customer</h3>
<p><?php echo esc_html($user->display_name); ?></p>
<p><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=CUST-<?=$user->ID?>"></p>
</div>



<div class="card">
<h3>Status</h3>
<span class="badge <?php echo esc_attr($job->status); ?>">
<?php echo ucfirst($job->status); ?>
</span>
</div>

<div class="card">
<h3>Items</h3>
<table class="table">
<tr><th>Item</th><th>Qty</th><th>Total</th></tr>
<?php foreach($items as $i): ?>
<tr>
<td><?php echo esc_html($i->sku); ?></td>
<td><?php echo esc_html($i->qty); ?></td>
<td>₹<?php echo number_format($i->total,2); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="card">
<h3><?php echo ($job->status === 'ready') ? 'Amount Payable' : 'Estimated Amount'; ?></h3>
<h2>₹<?php echo number_format($amount,2); ?></h2>
</div>

<?php if($job->status === 'ready'): ?>
<div class="card">
<a href="<?php echo esc_url($upi_link); ?>" class="pay-btn">
Pay ₹<?php echo number_format($amount,2); ?> Now
</a>
</div>
<?php endif; ?>

</body>
</html>
