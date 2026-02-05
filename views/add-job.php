<?php
global $wpdb;
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
?>

<div class="rsjm-wrap">
<div class="rsjm-card">

<h2 class="rsjm-title">🧾 New Repair Job</h2>

<form method="post">
<?php wp_nonce_field('rsjm_save_job','rsjm_nonce'); ?>

<!-- CUSTOMER -->
<div class="rsjm-card" style="margin-bottom:20px">
    <div class="rsjm-field">
        <label>Customer</label>
        <select name="customer_id" required>
            <option value="">Select Customer</option>
            <?php foreach(get_users() as $u): ?>
                <option value="<?=$u->ID?>">
                    <?=$u->display_name?> (<?=$u->user_email?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- ITEMS -->
<div class="rsjm-card">
<h3 class="rsjm-title">Repair Items</h3>

<div id="items-wrapper"></div>

<button type="button"
        onclick="addItemCard()"
        class="rsjm-btn rsjm-btn-primary">
➕ Add Item
</button>
</div>

<!-- GST -->
<div class="rsjm-card">
<h3 class="rsjm-title">Tax & Delivery</h3>

<div class="rsjm-field">
<label>GST Type</label>
<select name="gst_type">
    <option value="cgst_sgst">CGST + SGST</option>
    <option value="igst">IGST</option>
</select>
</div>

<div class="rsjm-field">
<label>GST %</label>
<input name="gst_percent" value="18">
</div>

<div class="rsjm-field">
<label>Estimated Delivery Date</label>
<input type="date" name="delivery_date">
</div>
</div>

<input type="hidden" name="rsjm_save_job" value="1">

<button class="rsjm-btn rsjm-btn-success" style="width:100%;font-size:16px">
💾 Save Repair Job
</button>

</form>
</div>
</div>

<!-- ITEM CARD TEMPLATE -->
<script>
const ITEMS = <?= json_encode($items) ?>;

function addItemCard() {

    let options = '<option value="">Select Item</option>';
    ITEMS.forEach(i => {
        options += `<option value="${i.id}" data-price="${i.price}" data-sku="${i.sku}">
                        ${i.name}
                    </option>`;
    });

    let card = `
    <div class="rsjm-card rsjm-item-card">
        <div class="rsjm-grid">

            <div class="rsjm-field">
                <label>Item</label>
                <select name="item_id[]" onchange="setItemData(this)">
                    ${options}
                </select>
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
                <label>Total</label>
                <input name="total[]" readonly>
            </div>

            <div class="rsjm-field rsjm-full">
                <label>Problem Description</label>
                <textarea name="problem[]" rows="2"></textarea>
            </div>

            <div class="rsjm-field">
                <label>
                    <input type="checkbox" name="replacement[]" onchange="toggleReplacement(this)">
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
                onclick="this.closest('.rsjm-item-card').remove()">
            ✖ Remove Item
        </button>
    </div>
    `;

    document.getElementById('items-wrapper').insertAdjacentHTML('beforeend', card);
}

function setItemData(select) {
    let card = select.closest('.rsjm-item-card');
    let option = select.options[select.selectedIndex];

    card.querySelector('[name="price[]"]').value = option.dataset.price || '';
    card.querySelector('[name="sku[]"]').value = option.dataset.sku || '';

    calcItem(card.querySelector('[name="qty[]"]'));
}

function calcItem(el) {
    let card = el.closest('.rsjm-item-card');
    let qty = parseFloat(card.querySelector('[name="qty[]"]').value || 0);
    let price = parseFloat(card.querySelector('[name="price[]"]').value || 0);
    card.querySelector('[name="total[]"]').value = (qty * price).toFixed(2);
}

function toggleReplacement(cb) {
    let card = cb.closest('.rsjm-item-card');
    card.querySelector('.rsjm-replacement').style.display = cb.checked ? 'block' : 'none';
}
</script>
