<?php
/**
 * Shared Job Form — used by:
 *   - views/add-job.php   ($mode = 'add')
 *   - views/view-job.php  ($mode = 'edit')
 *
 * Expected variables set by the caller BEFORE including this file:
 *
 *   $mode                 'add' | 'edit'
 *   $master_items         array of stdClass rows from {$wpdb->prefix}rsjm_items
 *                          (the item catalog, for the "Select Item" dropdown)
 *   $job                  stdClass|null  — existing row from rsjm_jobs (null when $mode = 'add')
 *   $existing_line_items  array of stdClass — existing rows from rsjm_job_items
 *                          (empty array when $mode = 'add')
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$is_edit = ($mode === 'edit' && $job);

/* Points already redeemed against THIS job (edit mode), so we don't
   lock the user out of their own previously-redeemed points while editing. */
$already_redeemed_for_job = 0;
if ($is_edit) {
    $already_redeemed_for_job = intval($wpdb->get_var($wpdb->prepare(
        "SELECT IFNULL(SUM(points),0) FROM {$wpdb->prefix}rsjm_points
         WHERE job_id=%d AND type='redeem'",
        $job->id
    )));
}

$customer_id_val = $is_edit ? intval($job->customer_id) : 0;
$current_points  = rsjm_get_customer_points($customer_id_val) + $already_redeemed_for_job;
$redeem_val       = $is_edit ? floatval($wpdb->get_var($wpdb->prepare(
    "SELECT IFNULL(SUM(points),0) FROM {$wpdb->prefix}rsjm_points
     WHERE job_id=%d AND type='redeem'", $job->id
))) : 0;

?>

<div class="rsjm-card">
<h2 class="rsjm-title"><?= $is_edit ? '✏️ Edit Job #'.intval($job->id) : '🧾 New Job' ?></h2>

<form method="post">
<?php
if ($is_edit) {
    wp_nonce_field('rsjm_update_job_full', 'rsjm_nonce');
    echo '<input type="hidden" name="job_id" value="'.intval($job->id).'">';
    echo '<input type="hidden" name="rsjm_update_job_full" value="1">';
} else {
    wp_nonce_field('rsjm_save_job', 'rsjm_nonce');
    echo '<input type="hidden" name="rsjm_save_job" value="1">';
}
?>

<div class="rsjm-card" style="margin-bottom:20px;">
    <label class="rsjm-label"><strong>Job Type</strong></label>

    <div class="rsjm-job-type">
        <input type="radio" id="job_new" name="job_type" value="new"
            <?= (!$is_edit || $job->job_type === 'new') ? 'checked' : '' ?>>
        <label for="job_new">🛒 New</label>

        <input type="radio" id="job_repair" name="job_type" value="repair"
            <?= ($is_edit && $job->job_type === 'repair') ? 'checked' : '' ?>>
        <label for="job_repair">🔧 Repair</label>
    </div>
</div>

<!-- CUSTOMER -->
<div class="rsjm-card" style="margin-bottom:20px">

    <div class="rsjm-field">
        <label>Customer</label>
        <select name="customer_id" id="rsjm_customer" style="width:100%" required>
            <option value="">Search Customer...</option>
            <?php foreach (get_users() as $u): ?>
                <option value="<?php echo esc_attr($u->ID); ?>"
                    <?php selected($customer_id_val, $u->ID); ?>>
                    <?php echo esc_html($u->display_name); ?> (<?php echo esc_html($u->user_login); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="button" class="rsjm-btn" id="add_customer_btn">
        ➕ Add Customer
        </button>
    </div>

    <div class="rsjm-field">
        <label>Available Points</label>
        <input type="text" id="rsjm-available-points" readonly value="<?= intval($current_points) ?>">
    </div>

    <div class="rsjm-field">
        <label>Redeem Points</label>
        <input type="number" name="redeem_points" id="rsjm-redeem-points" min="0"
               value="<?= esc_attr($redeem_val) ?>" max="<?= intval($current_points) ?>">
    </div>
</div>



<!-- ITEMS -->
<div class="rsjm-card">

<h3 class="rsjm-title">Items</h3>

<div id="items-wrapper"></div>

<button type="button"
        onclick="addItemCard()"
        class="rsjm-btn rsjm-btn-primary">
➕ Add Item
</button>

</div>


<!-- SUMMARY -->
<div class="rsjm-card">

<h3 class="rsjm-title">Invoice Summary</h3>

<div class="rsjm-grid">

    <div class="rsjm-field">
        <label>Subtotal</label>
        <input id="rsjm-subtotal" readonly>
    </div>
    <div class="rsjm-field">
        <div class="rsjm-row">

        <label>Discount Type</label>
            <select name="discount_type" id="rsjm-discount-type">
                <option value="amount" <?= ($is_edit && $job->discount_type === 'amount') ? 'selected' : '' ?>>Amount</option>
                <option value="percent" <?= ($is_edit && $job->discount_type === 'percent') ? 'selected' : '' ?>>Percentage</option>
            </select>

        </div>
    </div>
    <div class="rsjm-field">
        <label>Discount</label>
        <input type="number"
           step="0.01"
           name="discount_value"
           id="rsjm-discount-value"
           value="<?= $is_edit ? esc_attr($job->discount_value) : 0 ?>"
           oninput="calculateTotals()">
    </div>
    <div class="rsjm-field">
        <label>Price After Discount</label>
        <input id="rsjm-price-after-discount" readonly>
    </div>

    <div class="rsjm-field">
        <label>Discount Amount</label>
        <input id="rsjm-discount-amount" readonly>
    </div>

    <div class="rsjm-field">
        <label>GST Type</label>
        <select name="gst_type" id="rsjm-gst-type" onchange="calculateTotals()">
            <option value="" <?= ($is_edit && $job->gst_type === '') ? 'selected' : '' ?>>No GST</option>
            <option value="cgst_sgst" <?= ($is_edit && $job->gst_type === 'cgst_sgst') ? 'selected' : '' ?>>CGST + SGST</option>
            <option value="igst" <?= ($is_edit && $job->gst_type === 'igst') ? 'selected' : '' ?>>IGST</option>
        </select>
    </div>

    <div class="rsjm-field">
        <label>GST %</label>
        <input name="gst_percent"
               id="rsjm-gst-percent"
               value="<?= $is_edit ? esc_attr(($job->cgst + $job->sgst + $job->igst) > 0 && $job->subtotal > 0
                    ? '' : '18') : 18 ?>"
               oninput="calculateTotals()">
    </div>

    <div class="rsjm-field" id="cgst-box" style="display:none">
        <label>CGST</label>
        <input id="rsjm-cgst" readonly>
    </div>

    <div class="rsjm-field" id="sgst-box" style="display:none">
        <label>SGST</label>
        <input id="rsjm-sgst" readonly>
    </div>

    <div class="rsjm-field" id="igst-box" style="display:none">
        <label>IGST</label>
        <input id="rsjm-igst" readonly>
    </div>

    <div class="rsjm-field rsjm-full">
        <label>Grand Total</label>
        <input id="rsjm-grand-total"
               readonly
               style="font-size:18px;font-weight:600">
    </div>

</div>

</div>


<!-- ADVANCE PAYMENT -->
<div class="rsjm-card">

<h3 class="rsjm-title">Advance Payment</h3>

<div class="rsjm-grid">

    <div class="rsjm-field">
        <label>Advance Paid</label>
        <input type="number"
               step="0.01"
               name="advance"
               value="<?= $is_edit ? esc_attr($job->advance) : 0 ?>"
               oninput="calculateTotals()">
    </div>

    <div class="rsjm-field">
        <label>Balance After Advance</label>
        <input id="rsjm-pending-preview" readonly>
    </div>

</div>

</div>

<!-- DELIVERY -->
<div class="rsjm-card">

<h3 class="rsjm-title">Delivery</h3>

<div class="rsjm-field">
    <label>Estimated Delivery Date</label>
    <input type="date" name="delivery_date"
           value="<?= $is_edit ? esc_attr($job->delivery_date) : '' ?>">
</div>

</div>

<!-- JOB STATUS -->
<div class="rsjm-card">

    <h3 class="rsjm-title">Job Status</h3>

    <div class="rsjm-field">
        <label>Status</label>
        <select name="job_status" id="job_status" required>
            <?php
            $statuses = [
                'pending'          => 'Pending',
                'in_progress'      => 'In Progress',
                'ready_to_deliver' => 'Ready to Deliver',
                'completed'        => 'Completed',
            ];
            $current_status = $is_edit ? $job->status : 'pending';
            foreach ($statuses as $val => $label):
            ?>
                <option value="<?= $val ?>" <?php selected($current_status, $val); ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($is_edit): ?>
    <div class="rsjm-field">
        <label>Payment Status <small>(auto, based on payments received)</small></label>
        <?php
        $pay_status = $job->payment_status ?? 'pending';
        $pay_colors = ['paid' => '#16a34a', 'partial' => '#d97706', 'pending' => '#dc2626'];
        $pay_color  = $pay_colors[$pay_status] ?? '#6b7280';
        ?>
        <input type="text" readonly value="<?= esc_attr(ucfirst($pay_status)) ?>"
               style="font-weight:600;color:<?= $pay_color ?>">
    </div>
    <?php endif; ?>

</div>


<?php if (!$is_edit): ?>
<!-- PAYMENT (only relevant at creation time; once the job exists, use the Payments tab) -->
<div class="rsjm-card" id="payment-box" style="display:none;">

    <h3 class="rsjm-title">Payment Details</h3>

    <div id="payment-rows"></div>

    <button type="button" onclick="addPaymentRow()" class="rsjm-btn">
        + Add Payment
    </button>

</div>
<?php endif; ?>


<!-- COURIER DETAILS -->
<div class="rsjm-card" id="courier_card" style="display:none;">

    <h3 class="rsjm-title">Courier / Tracking Details</h3>

    <div class="rsjm-grid">

        <div class="rsjm-field">
            <label>Courier Company</label>
            <input type="text"
                   name="courier_company"
                   value="<?= $is_edit ? esc_attr($job->courier_company) : '' ?>"
                   placeholder="e.g. DTDC, Delhivery, Blue Dart">
        </div>

        <div class="rsjm-field">
            <label>Tracking Number</label>
            <input type="text"
                   name="tracking_number"
                   value="<?= $is_edit ? esc_attr($job->tracking_number) : '' ?>"
                   placeholder="AWB / Tracking Number">
        </div>

        <div class="rsjm-field rsjm-full">
            <label>Tracking Website</label>
            <input type="url"
                   name="tracking_website"
                   value="<?= $is_edit ? esc_attr($job->tracking_website) : '' ?>"
                   placeholder="https://www.delhivery.com">
        </div>

        <div class="rsjm-field rsjm-full">
            <label>Direct Tracking Link (Optional)</label>
            <input type="url"
                   name="tracking_link"
                   value="<?= $is_edit ? esc_attr($job->tracking_link) : '' ?>"
                   placeholder="https://tracking-company.com/track/123456">
        </div>

        <div class="rsjm-field">
            <label>Courier Date</label>
            <input type="date"
                   name="courier_date"
                   value="<?= $is_edit ? esc_attr($job->courier_date) : '' ?>">
        </div>

    </div>

</div>


<button class="rsjm-btn rsjm-btn-success"
        style="width:100%;font-size:16px">
<?= $is_edit ? '💾 Update Job' : '💾 Save Job' ?>
</button>

</form>

<div id="rsjm_customer_modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; max-width:400px; margin:80px auto; padding:20px; border-radius:8px;">
        <h3>Add Customer</h3>
        <input type="text" id="cust_fname" placeholder="First Name" style="width:100%;margin-bottom:10px">
        <input type="text" id="cust_lname" placeholder="Last Name" style="width:100%;margin-bottom:10px">
        <input type="text" id="cust_phone" placeholder="Phone" style="width:100%;margin-bottom:10px">
        <input type="text" id="alt_phone" placeholder="Alternative Phone" style="width:100%;margin-bottom:10px">
        <input type="email" id="cust_email" placeholder="Email" style="width:100%;margin-bottom:10px">
        <textarea id="cust_address" placeholder="Address" style="width:100%;margin-bottom:10px"></textarea>
        <button id="save_customer" class="rsjm-btn rsjm-btn-success">Save</button>
        <button onclick="document.getElementById('rsjm_customer_modal').style.display='none'" class="rsjm-btn">Cancel</button>
    </div>
</div>

<div id="rsjm_item_modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; max-width:400px; margin:80px auto; padding:20px; border-radius:8px;">
        <h3>Add Item</h3>
        <input type="text" id="item_name" placeholder="Item Name" style="width:100%;margin-bottom:10px">
        <input type="text" id="item_sku" placeholder="SKU" style="width:100%;margin-bottom:10px">
        <input type="number" id="item_price" placeholder="Price" style="width:100%;margin-bottom:10px">
        <input type="file" id="item_image" accept="image/*" style="width:100%;margin-bottom:10px">
        <img id="item_preview" style="max-width:100px; display:none; margin-bottom:10px;">
        <button id="save_item" class="rsjm-btn rsjm-btn-success">Save</button>
        <button onclick="closeItemModal()" class="rsjm-btn">Cancel</button>
    </div>
</div>

</div>

<!-- JS -->
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
const ITEMS = <?php echo json_encode($master_items); ?>;

/* Existing line items to prefill when editing a job. Empty array when adding. */
const EXISTING_LINE_ITEMS = <?php echo json_encode($existing_line_items); ?>;

/* ADD ITEM CARD  (data = existing line item row, or null for a blank card) */
function addItemCard(data) {

    data = data || null;

    let options = '<option value="">Select Item</option>';

    ITEMS.forEach(i => {
        options += `
            <option value="${i.id}"
                    data-price="${i.price}"
                    data-sku="${i.sku}"
                    data-image="${i.image}"
                    data-stock="${i.stock}">
                ${i.name} (${i.stock} in stock)
            </option>`;
    });

    let card = `

    <div class="rsjm-card rsjm-item-card">

        <div class="rsjm-grid">

            <div class="rsjm-field">
                <label>Item</label>
                <div style="display:flex; gap:5px;">
                    <select name="item_id[]" onchange="setItemData(this)" class="rsjm-item-select">
                        ${options}
                    </select>

                    <button type="button" onclick="openItemModal(this)" class="rsjm-btn">
                        ➕
                    </button>
                </div>
            </div>

            <div class="rsjm-field">
                <label>SKU</label>
                <input name="sku[]">
            </div>

            <div class="rsjm-field">
                <label>Quantity</label>
                <input name="qty[]" value="1" oninput="calcItem(this)">
            </div>

            <div class="rsjm-field">
                <label>Price</label>
                <input name="price[]" oninput="calcItem(this)">
            </div>

            <div class="rsjm-field">
                <label>Discount %</label>
                <input name="discount_percent[]"
                       value="0"
                       oninput="handleDiscountPercent(this)">
            </div>

            <div class="rsjm-field">
                <label>Discount ₹</label>
                <input name="discount_amount[]"
                       value="0"
                       oninput="handleDiscountAmount(this)">
            </div>

            <div class="rsjm-field">
                <label>Total</label>
                <input name="total[]" readonly>
            </div>

            <div class="rsjm-field">
                <label>Item Image</label>
                <img class="item-image-preview" style="max-width:80px; display:none;">
                <input type="hidden" name="item_image[]">

                <input type="file" onchange="updateItemImage(this)">
            </div>

            <div class="rsjm-field rsjm-full">
                <label>Problem</label>
                <textarea name="problem[]" rows="2"></textarea>
            </div>

            <div class="rsjm-field">
                <label>
                    <input type="checkbox"
                           name="replacement[]"
                           onchange="toggleReplacement(this)">
                    Replacement
                </label>
            </div>

            <div class="rsjm-field rsjm-replacement" style="display:none">
                <label>Replacement SKU</label>
                <input name="replacement_sku[]">
            </div>

        </div>

        <button type="button"
                class="rsjm-btn rsjm-btn-danger"
                onclick="removeItem(this)">
            ✖ Remove Item
        </button>

    </div>`;

    document.getElementById('items-wrapper')
            .insertAdjacentHTML('beforeend', card);

    let newCard = document.getElementById('items-wrapper').lastElementChild;

    initItemSelect2(newCard);

    if (data) {
        newCard.querySelector('[name="sku[]"]').value = data.sku || '';
        newCard.querySelector('[name="qty[]"]').value = data.qty || 1;
        newCard.querySelector('[name="price[]"]').value = data.price || 0;
        newCard.querySelector('[name="discount_percent[]"]').value = data.disc_per || 0;
        newCard.querySelector('[name="discount_amount[]"]').value = data.disc_price || 0;
        newCard.querySelector('[name="total[]"]').value = data.total || 0;
        newCard.querySelector('[name="problem[]"]').value = data.problem || '';

        if (data.item_image) {
            let imgTag = newCard.querySelector('.item-image-preview');
            let imgInput = newCard.querySelector('[name="item_image[]"]');
            imgTag.src = data.item_image;
            imgTag.style.display = 'block';
            imgInput.value = data.item_image;
        }

        if (parseInt(data.replacement) === 1) {
            let cb = newCard.querySelector('[name="replacement[]"]');
            cb.checked = true;
            toggleReplacement(cb);
            newCard.querySelector('[name="replacement_sku[]"]').value = data.replacement_sku || '';
        }

        if (data.item_id) {
            jQuery(newCard.querySelector('.rsjm-item-select')).val(data.item_id).trigger('change.select2');
        }
    }

    calculateTotals();
}


/* SET ITEM DATA (only used when the item is picked/changed from the dropdown) */
function setItemData(select) {

    let card = select.closest('.rsjm-item-card');
    let opt  = select.options[select.selectedIndex];

    card.querySelector('[name="price[]"]').value =
        opt.dataset.price || '';

    card.querySelector('[name="sku[]"]').value =
        opt.dataset.sku || '';

    calcItem(card.querySelector('[name="qty[]"]'));

    let img = opt.dataset.image || '';

    let imgTag = card.querySelector('.item-image-preview');
    let imgInput = card.querySelector('[name="item_image[]"]');

    if (img) {
        imgTag.src = img;
        imgTag.style.display = 'block';
        imgInput.value = img;
    } else {
        imgTag.style.display = 'none';
        imgInput.value = '';
    }

    <?php if (rsjm_is_stock_enabled()): ?>
    let stock = parseInt(opt.dataset.stock || 0);

    if (stock <= 0) {
        alert('❌ This item is OUT OF STOCK');
    }

    if (stock > 0 && stock <= 5) {
        alert('⚠️ Low stock: Only ' + stock + ' left');
    }
    <?php endif; ?>
}


/* CALCULATE ITEM */
function calcItem(el) {

    let card = el.closest('.rsjm-item-card');

    let qty   = parseFloat(card.querySelector('[name="qty[]"]').value || 0);
    let price = parseFloat(card.querySelector('[name="price[]"]').value || 0);

    let discount = parseFloat(card.querySelector('[name="discount_amount[]"]')?.value || 0);

    let total = (qty * price) - discount;

    if (total < 0) total = 0;

    <?php if (rsjm_is_stock_enabled()): ?>
    let stock = parseInt(card.querySelector('.rsjm-item-select').selectedOptions[0]?.dataset.stock || 0);

    if (qty > stock) {
        alert('❌ Only ' + stock + ' items available');
        card.querySelector('[name="qty[]"]').value = stock;
        qty = stock;
    }
    <?php endif; ?>

    card.querySelector('[name="total[]"]').value = total.toFixed(2);

    calculateTotals();
}

function handleDiscountPercent(el) {

    let card = el.closest('.rsjm-item-card');

    let percent = parseFloat(el.value || 0);
    let price   = parseFloat(card.querySelector('[name="price[]"]').value || 0);
    let qty     = parseFloat(card.querySelector('[name="qty[]"]').value || 0);

    let base = price * qty;

    let discountAmount = (base * percent) / 100;

    card.querySelector('[name="discount_amount[]"]').value = discountAmount.toFixed(2);

    calcItem(el);
}

function handleDiscountAmount(el) {

    let card = el.closest('.rsjm-item-card');

    let discount = parseFloat(el.value || 0);
    let price    = parseFloat(card.querySelector('[name="price[]"]').value || 0);
    let qty      = parseFloat(card.querySelector('[name="qty[]"]').value || 0);

    let base = price * qty;

    let percent = 0;

    if (base > 0) {
        percent = (discount / base) * 100;
    }

    card.querySelector('[name="discount_percent[]"]').value = percent.toFixed(2);

    calcItem(el);
}


/* REMOVE ITEM (Edit mode: the item's DB row is simply not resubmitted,
   the server wipes & reinserts the full item list on update) */
function removeItem(btn) {
    btn.closest('.rsjm-item-card').remove();
    calculateTotals();
}


/* TOGGLE REPLACEMENT */
function toggleReplacement(cb) {
    let card = cb.closest('.rsjm-item-card');
    card.querySelector('.rsjm-replacement').style.display =
        cb.checked ? 'block' : 'none';
}


/* CALCULATE TOTALS */
function calculateTotals() {

    let subtotal = 0;

    document.querySelectorAll('[name="total[]"]').forEach(el => {
        subtotal += parseFloat(el.value || 0);
    });

    document.getElementById('rsjm-subtotal').value = subtotal.toFixed(2);

    let discountType =
        document.getElementById('rsjm-discount-type')?.value || 'amount';

    let discountValue =
        parseFloat(document.getElementById('rsjm-discount-value')?.value || 0);

    let discountAmount = 0;

    if (discountType === 'percent') {
        discountAmount = (subtotal * discountValue) / 100;
    } else {
        discountAmount = discountValue;
    }

    if (discountAmount > subtotal) {
        discountAmount = subtotal;
    }

    let taxableAmount = subtotal - discountAmount;

    document.getElementById('rsjm-discount-amount').value =
        discountAmount.toFixed(2);

    document.getElementById('rsjm-price-after-discount').value =
        taxableAmount.toFixed(2);

    let gstType =
        document.getElementById('rsjm-gst-type').value;

    let gstPercent =
        parseFloat(document.getElementById('rsjm-gst-percent').value || 0);

    let cgst = 0, sgst = 0, igst = 0;

    document.getElementById('cgst-box').style.display = 'none';
    document.getElementById('sgst-box').style.display = 'none';
    document.getElementById('igst-box').style.display = 'none';

    if (gstType === 'cgst_sgst') {
        cgst = sgst = (taxableAmount * gstPercent / 100) / 2;
        document.getElementById('cgst-box').style.display = 'block';
        document.getElementById('sgst-box').style.display = 'block';
    }

    if (gstType === 'igst') {
        igst = taxableAmount * gstPercent / 100;
        document.getElementById('igst-box').style.display = 'block';
    }

    document.getElementById('rsjm-cgst').value = cgst.toFixed(2);
    document.getElementById('rsjm-sgst').value = sgst.toFixed(2);
    document.getElementById('rsjm-igst').value = igst.toFixed(2);

    let grand = taxableAmount + cgst + sgst + igst;

    let redeem =
        parseFloat(document.getElementById('rsjm-redeem-points')?.value || 0);

    if (redeem > grand) {
        redeem = grand;
        document.getElementById('rsjm-redeem-points').value = grand.toFixed(2);
    }

    grand -= redeem;

    if (grand < 0) grand = 0;

    document.getElementById('rsjm-grand-total').value = grand.toFixed(2);

    let advance =
        parseFloat(document.querySelector('[name="advance"]')?.value || 0);

    let pending = grand - advance;

    if (pending < 0) pending = 0;

    document.getElementById('rsjm-pending-preview').value = pending.toFixed(2);
}


document.addEventListener("DOMContentLoaded", function () {

    /* Load existing line items when editing */
    if (EXISTING_LINE_ITEMS && EXISTING_LINE_ITEMS.length) {
        EXISTING_LINE_ITEMS.forEach(item => addItemCard(item));
    }

    const customerSelect = document.querySelector('[name="customer_id"]');
    const availableField = document.getElementById('rsjm-available-points');
    const redeemField    = document.getElementById('rsjm-redeem-points');

    if (customerSelect) {
        jQuery('#rsjm_customer').on('change', function () {

            let customerId = this.value;

            if (!customerId) {
                jQuery('#rsjm-available-points').val(0);
                return;
            }

            fetch(ajaxurl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: 'rsjm_get_points',
                    customer_id: customerId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let points = parseInt(data.data.points) || 0;
                    jQuery('#rsjm-available-points').val(points);
                    jQuery('#rsjm-redeem-points').attr('max', points);
                } else {
                    jQuery('#rsjm-available-points').val(0);
                }
            });
        });
    }

    if (redeemField) {
        redeemField.addEventListener('input', function () {

            let max = parseInt(availableField.value) || 0;
            let val = parseInt(this.value) || 0;

            if (val > max) this.value = max;
            if (val < 0) this.value = 0;

            calculateTotals();
        });
    }

    calculateTotals();
});


jQuery(document).ready(function ($) {
    $('#rsjm_customer').select2({
        placeholder: "Search or select customer",
        width: '100%'
    });
});

document.getElementById('add_customer_btn').addEventListener('click', function () {
    document.getElementById('rsjm_customer_modal').style.display = 'block';
});

document.getElementById('save_customer').addEventListener('click', function () {

    let data = new URLSearchParams({
        action: 'rsjm_add_customer',
        fname: document.getElementById('cust_fname').value,
        lname: document.getElementById('cust_lname').value,
        phone: document.getElementById('cust_phone').value,
        altphone: document.getElementById('alt_phone').value,
        email: document.getElementById('cust_email').value,
        address: document.getElementById('cust_address').value
    });

    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            let select = jQuery('#rsjm_customer');
            let option = new Option(res.data.text, res.data.id, true, true);
            select.append(option).trigger('change');
            document.getElementById('rsjm_customer_modal').style.display = 'none';
        } else {
            alert(res.data);
        }
    });
});

let currentSelect = null;

function openItemModal(btn) {
    currentSelect = btn.closest('.rsjm-item-card').querySelector('.rsjm-item-select');
    document.getElementById('rsjm_item_modal').style.display = 'block';
}

function closeItemModal() {
    document.getElementById('rsjm_item_modal').style.display = 'none';
}

document.getElementById('save_item').addEventListener('click', function () {

    let formData = new FormData();

    formData.append('action', 'rsjm_add_item');
    formData.append('name', document.getElementById('item_name').value);
    formData.append('sku', document.getElementById('item_sku').value);
    formData.append('price', document.getElementById('item_price').value);

    let file = document.getElementById('item_image').files[0];
    if (file) formData.append('image', file);

    fetch(ajaxurl, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            let option = new Option(res.data.name, res.data.id, true, true);
            option.dataset.price = res.data.price;
            option.dataset.sku   = res.data.sku;
            option.dataset.image = res.data.image;

            jQuery(currentSelect).append(option).trigger('change');
            closeItemModal();
        } else {
            alert(res.data);
        }
    });
});

<?php if (!$is_edit): ?>
document.querySelector('[name="job_status"]').addEventListener('change', function () {

    let box = document.getElementById('payment-box');

    if (this.value === 'completed') {
        box.style.display = 'block';
        if (document.querySelectorAll('.payment-row').length === 0) {
            addPaymentRow();
        }
    } else {
        box.style.display = 'none';
    }
});

function addPaymentRow() {
    let html = `
    <div class="payment-row" style="display:flex; gap:10px; margin-bottom:10px;">
        <select name="payment_method[]">
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="bank">Bank</option>
        </select>
        <input type="number" step="0.01" name="payment_amount[]" placeholder="Amount">
        <button type="button" onclick="this.parentNode.remove()">❌</button>
    </div>
    `;
    document.getElementById('payment-rows').insertAdjacentHTML('beforeend', html);
}
<?php endif; ?>

function updateItemImage(input) {

    let file = input.files[0];
    if (!file) return;

    let reader = new FileReader();

    reader.onload = function (e) {
        let card = input.closest('.rsjm-item-card');
        let imgTag = card.querySelector('.item-image-preview');
        let hidden = card.querySelector('[name="item_image[]"]');

        imgTag.src = e.target.result;
        imgTag.style.display = 'block';
        hidden.value = e.target.result;
    };

    reader.readAsDataURL(file);
}

document.getElementById('item_image').addEventListener('change', function (e) {

    let file = e.target.files[0];
    if (!file) return;

    let reader = new FileReader();

    reader.onload = function (ev) {
        let img = document.getElementById('item_preview');
        img.src = ev.target.result;
        img.style.display = 'block';
    };

    reader.readAsDataURL(file);
});

function initItemSelect2(context = document) {
    jQuery(context).find('.rsjm-item-select').select2({
        placeholder: "Search or select item",
        width: '100%',
        allowClear: true
    });
}


function toggleCourierCard() {
    const status = document.getElementById('job_status').value;
    const courierCard = document.getElementById('courier_card');

    if (status === 'completed') {
        courierCard.style.display = 'block';
    } else {
        courierCard.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('job_status');

    if (statusSelect) {
        statusSelect.addEventListener('change', toggleCourierCard);
        toggleCourierCard(); // Set initial state on page load
    }
});
</script>