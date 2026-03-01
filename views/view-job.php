<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$job_id = intval($_GET['job_id']);
$job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=$job_id");
$user = get_user_by('id', $job->customer_id);
?>
<div class="rsjm-tabs">

<button class="rsjm-tab-btn active"
        onclick="openTab(event,'job-tab')">
Job Details
</button>

<button class="rsjm-tab-btn"
        onclick="openTab(event,'pay-tab')">
Payments
</button>

</div>


<div class="rsjm-wrap">


<div id="job-tab" class="rsjm-tab-content active">
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

<form method="post">
<?php wp_nonce_field('rsjm_edit_items','rsjm_nonce'); ?>

<input type="hidden" name="job_id" value="<?=$job->id?>">
<input type="hidden" name="rsjm_edit_items" value="1">

<table class="rsjm-table">
<tr>
<th>SKU</th><th>Qty</th><th>Price</th><th>Total</th><th>Remove</th>
</tr>

<?php foreach($items as $i): ?>
<tr>
<td><?=$i->sku?></td>
<td>
<input type="number" name="qty[<?=$i->id?>]" value="<?=$i->qty?>">
</td>
<td>
<input type="number" step="0.01" name="price[<?=$i->id?>]" value="<?=$i->price?>">
</td>
<td>₹<?=$i->total?></td>
<td>
<input type="checkbox" name="remove[]" value="<?=$i->id?>">
</td>
</tr>
<?php endforeach; ?>
</table>

<button class="rsjm-btn rsjm-btn-primary">Update Items</button>
</form>

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


<?php
$fin = rsjm_get_job_financials($job->id);

$total   = $fin['total'];
$paid    = $fin['paid'];
$pending = $fin['pending'];
$paid_percent = $fin['percent'];

$advance = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(amount) FROM {$wpdb->prefix}rsjm_payments 
         WHERE job_id=%d AND method='Advance'",
        $job->id
    )
);

$advance = $advance ? floatval($advance) : 0;
?>


<div class="rsjm-premium-summary">

    <!-- BIG TOTAL -->
    <div class="rsjm-big-total">
        <span>Total Amount</span>
        <h2>₹<?=number_format($total,2)?></h2>
    </div>

    <!-- PROGRESS BAR -->
    <div class="rsjm-progress-wrapper">
        <div class="rsjm-progress-bar">
            <div class="rsjm-progress-fill"
                 style="width: <?=min(100,$paid_percent)?>%"></div>
        </div>
        <div class="rsjm-progress-label">
            <?=round($paid_percent)?>% Paid
        </div>
    </div>

    <!-- BREAKDOWN GRID -->
    <div class="rsjm-breakdown-grid">

        <div class="rsjm-break-item advance">
            <span>Advance</span>
            <strong>₹<?=number_format($advance,2)?></strong>
        </div>

        <div class="rsjm-break-item paid">
            <span>Paid</span>
            <strong>₹<?=number_format($paid,2)?></strong>
        </div>

        <div class="rsjm-break-item pending">
            <span>Pending</span>
            <strong>₹<?=number_format($pending,2)?></strong>
        </div>

    </div>

</div>

</div>


<?php
$payments = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rsjm_payments
     WHERE job_id={$job->id}
     ORDER BY id DESC"
);
?>

<div id="pay-tab" class="rsjm-tab-content">

<div class="rsjm-card">

<h3 class="rsjm-title">💳 Payment History</h3>

<table class="rsjm-table">

<tr>
<th>Date</th>
<th>Amount</th>
<th>Method</th>
<th>Note</th>
</tr>

<?php if($payments): foreach($payments as $p): ?>

<tr>
<td><?=date('d M Y H:i',strtotime($p->created_at))?></td>
<td>₹<?=number_format($p->amount,2)?></td>
<td><?=$p->method?></td>
<td><?=$p->note?></td>
</tr>

<?php endforeach; else: ?>

<tr>
<td colspan="4" style="text-align:center">No payments yet</td>
</tr>

<?php endif; ?>

</table>

</div>


<!-- ADD PAYMENT FORM -->
<div class="rsjm-card">

<h3 class="rsjm-title">➕ Add Payment</h3>

<form method="post">

<?php wp_nonce_field('rsjm_add_payment','rsjm_nonce'); ?>

<input type="hidden" name="job_id" value="<?=$job->id?>">
<input type="hidden" name="rsjm_add_payment" value="1">

<div class="rsjm-grid">

<div class="rsjm-field">
<label>Amount</label>
<input type="number" step="0.01" name="pay_amount" required>
</div>

<div class="rsjm-field">
    <label>Payment Date</label>
    <input type="date"
           name="pay_date"
           value="<?php echo date('Y-m-d'); ?>"
           required>
</div>

<div class="rsjm-field">
<label>Method</label>
<select name="pay_method">
<option>Cash</option>
<option>UPI</option>
<option>Bank</option>
</select>
</div>

<div class="rsjm-field rsjm-full">
<label>Note</label>
<input name="pay_note">
</div>

</div>

<button class="rsjm-btn rsjm-btn-success">
Add Payment
</button>

</form>

</div>

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

function openTab(evt,id){

    document.querySelectorAll('.rsjm-tab-content')
        .forEach(t=>t.classList.remove('active'));

    document.querySelectorAll('.rsjm-tab-btn')
        .forEach(b=>b.classList.remove('active'));

    document.getElementById(id).classList.add('active');

    evt.currentTarget.classList.add('active');
}
</script>