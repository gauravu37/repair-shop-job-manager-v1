<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$job_id = intval($_GET['job_id']);
$job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=$job_id");
$user = get_user_by('id', $job->customer_id);
?>

<div class="rsjm-wrap">

<div class="rsjm-card">
<h2 class="rsjm-title">Job #<?=$job->id?></h2>

<p><strong>Customer:</strong> <?=$user->display_name?></p>
<p>
<span class="rsjm-badge rsjm-badge-<?=$job->status?>">
<?=ucfirst($job->status)?>
</span>
</p>
</div>

<div class="rsjm-card">
<h3 class="rsjm-title">Items</h3>

<table class="rsjm-table">
<tr><th>SKU</th><th>Qty</th><th>Price</th><th>Total</th></tr>
<?php foreach($items as $i): ?>
<tr>
<td><?=$i->sku?></td>
<td><?=$i->qty?></td>
<td>₹<?=$i->price?></td>
<td>₹<?=$i->total?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="rsjm-card rsjm-no-print">
<h3 class="rsjm-title">Update Job</h3>

<form method="post">
    <?php wp_nonce_field('rsjm_update_job','rsjm_nonce'); ?>

    <input type="hidden" name="job_id" value="<?php echo esc_attr($job->id); ?>">
    <input type="hidden" name="rsjm_update_job" value="1">

    <div class="rsjm-field">
        <label>Status</label>
        <select name="status">
            <option value="pending" <?php selected($job->status,'pending'); ?>>Pending</option>
            <option value="in_progress" <?php selected($job->status,'in_progress'); ?>>In Progress</option>
            <option value="ready" <?php selected($job->status,'ready'); ?>>Ready to Deliver</option>
            <option value="completed" <?php selected($job->status,'completed'); ?>>Completed</option>
            <option value="partial" <?php selected($job->status,'partial'); ?>>Partial Paid</option>
        </select>
    </div>

    <!-- READY TO DELIVER -->
    <div class="rsjm-field" id="readyBox" style="display:none;">
        <label>Estimated Amount</label>
        <input type="number" step="0.01" name="ready_amount" value="<?php echo esc_attr($job->total); ?>">
    </div>

    <!-- PAYMENT -->
    <div class="rsjm-field" id="paymentBox" style="display:none;">
        <label>Payment Method</label>
        <select name="payment_method">
            <option value="">Select</option>
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="bank">Bank Transfer</option>
        </select>

        <label>Amount Paid</label>
        <input type="number" step="0.01" name="paid_amount">

        <label>Pending Amount</label>
        <input type="number" step="0.01" name="pending_amount">
    </div>

    <button class="rsjm-btn rsjm-btn-success">Update Job</button>
    <button type="button" onclick="window.print()" class="rsjm-btn rsjm-btn-light">Print</button>
</form>
</div>


</div>



<script>
const statusSelect = document.querySelector('select[name="status"]');
const readyBox = document.getElementById('readyBox');
const paymentBox = document.getElementById('paymentBox');

function toggleBoxes(){
    readyBox.style.display = statusSelect.value === 'ready' ? 'block' : 'none';
    paymentBox.style.display = 
        (statusSelect.value === 'completed' || statusSelect.value === 'partial') 
        ? 'block' : 'none';
}
toggleBoxes();
statusSelect.addEventListener('change', toggleBoxes);
</script>
