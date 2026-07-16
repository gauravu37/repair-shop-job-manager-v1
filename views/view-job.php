<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$job_id = intval($_GET['job_id']);
$job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d", $job_id));
$items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=%d", $job_id));
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

<?php
/* ==============================================
   Full Job Edit Form — same UI as "Add Job"
   (items, GST, discount, redeem points, advance,
   delivery, status, courier). Replaces the old
   qty/price-only table and the separate mini
   "Update Job" form.
============================================== */
$master_items        = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
$mode                = 'edit';
$existing_line_items = $items;

include RSJM_PATH . 'views/job-form.php';
?>

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

        <div class="rsjm-break-item paid">
            <small>Redeem Discount</small>
            <strong>₹<?=number_format($job->redeem_discount,2)?></strong>
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
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rsjm_payments
         WHERE job_id=%d
         ORDER BY id DESC",
        $job->id
    )
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
function openTab(evt,id){

    document.querySelectorAll('.rsjm-tab-content')
        .forEach(t=>t.classList.remove('active'));

    document.querySelectorAll('.rsjm-tab-btn')
        .forEach(b=>b.classList.remove('active'));

    document.getElementById(id).classList.add('active');

    evt.currentTarget.classList.add('active');
}
</script>