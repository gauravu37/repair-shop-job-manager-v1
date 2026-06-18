<?php
global $wpdb;
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
?>

<div class="rsjm-wrap">
<div class="rsjm-card">

<h2 class="rsjm-title">🧾 New Job</h2>

<form method="post">
<?php wp_nonce_field('rsjm_save_job','rsjm_nonce'); ?>


<!-- CUSTOMER -->
<div class="rsjm-card" style="margin-bottom:20px">

    <div class="rsjm-field">
        <label>Customer</label>
        <select name="customer_id" id="rsjm_customer" style="width:100%" required>
			<option value="">Search Customer...</option>
			<?php foreach(get_users() as $u): ?>
				<option value="<?php echo esc_attr($u->ID); ?>">
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
		<input type="text" id="rsjm-available-points" readonly value="0">
	</div>

	<div class="rsjm-field">
		<label>Redeem Points</label>
		<input type="number" name="redeem_points" id="rsjm-redeem-points" min="0" value="0">
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
				<option value="amount">Amount</option>
				<option value="percent">Percentage</option>
			</select>

			

		</div>
	</div>
	<div class="rsjm-field">
		<label>Discount</label>
		<input type="number"
		   step="0.01"
		   name="discount_value"
		   id="rsjm-discount-value"
		   value="0"
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
            <option value="">No GST</option>
            <option value="cgst_sgst">CGST + SGST</option>
            <option value="igst">IGST</option>
        </select>
    </div>

    <div class="rsjm-field">
        <label>GST %</label>
        <input name="gst_percent"
               id="rsjm-gst-percent"
               value="18"
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

<h3 class="rsjm-title">Advance Payment (Optional)</h3>

<div class="rsjm-grid">

    <div class="rsjm-field">
        <label>Advance Paid</label>
        <input type="number"
               step="0.01"
               name="advance"
               value="0"
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
    <input type="date" name="delivery_date">
</div>

</div>

<!-- JOB STATUS -->
<div class="rsjm-card">

    <h3 class="rsjm-title">Job Status</h3>

    <div class="rsjm-field">
        <label>Status</label>
        <select name="job_status" required>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="ready_to_deliver">Ready to Deliver</option>
            <option value="completed">Completed</option>
            <option value="partial_paid">Partial Paid</option>
        </select>
    </div>

</div>


<!-- PAYMENT (ONLY FOR COMPLETED) -->
<!--<div class="rsjm-card" id="payment-box" style="display:none;">

    <h3 class="rsjm-title">Payment Details</h3>

    <div class="rsjm-field">
        <label>Payment Method</label>
        <select name="payment_method">
            <option value="">Select</option>
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="bank">Bank Transfer</option>
        </select>
    </div>

    <div class="rsjm-field">
        <label>Amount Paid</label>
        <input type="number" name="paid_amount" step="0.01">
    </div>

</div>-->

<div class="rsjm-card" id="payment-box" style="display:none;">

    <h3 class="rsjm-title">Payment Details</h3>

    <div id="payment-rows"></div>

    <button type="button" onclick="addPaymentRow()" class="rsjm-btn">
        + Add Payment
    </button>

</div>


<!-- COURIER DETAILS -->
<div class="rsjm-card">

    <h3 class="rsjm-title">Courier / Tracking Details</h3>

    <div class="rsjm-grid">

        <div class="rsjm-field">
            <label>Courier Company</label>
            <input type="text"
                   name="courier_company"
                   placeholder="e.g. DTDC, Delhivery, Blue Dart">
        </div>

        <div class="rsjm-field">
            <label>Tracking Number</label>
            <input type="text"
                   name="tracking_number"
                   placeholder="AWB / Tracking Number">
        </div>

        <div class="rsjm-field rsjm-full">
            <label>Tracking Website</label>
            <input type="url"
                   name="tracking_website"
                   placeholder="https://www.delhivery.com">
        </div>

        <div class="rsjm-field rsjm-full">
            <label>Direct Tracking Link (Optional)</label>
            <input type="url"
                   name="tracking_link"
                   placeholder="https://tracking-company.com/track/123456">
        </div>

        <div class="rsjm-field">
            <label>Courier Date</label>
            <input type="date"
                   name="courier_date">
        </div>

    </div>

</div>

<?php
$current_points = rsjm_get_customer_points($_POST['customer_id'] ?? 0);
?>
<!--
<div class="rsjm-card">
    <h3>Use Reward Points</h3>

    <div class="rsjm-field">
        <label>Available Points</label>
        <input value="<?=$current_points?>" readonly>
    </div>

    <div class="rsjm-field">
        <label>Redeem Points</label>
        <input type="number"
               name="redeem_points"
               value="0"
               min="0"
               oninput="calculateTotals()">
    </div>
</div>-->


<input type="hidden" name="rsjm_save_job" value="1">

<button class="rsjm-btn rsjm-btn-success"
        style="width:100%;font-size:16px">
💾 Save Repair Job
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
</div>


<!-- JS -->
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
const ITEMS = <?php echo json_encode($items); ?>;


/* ADD ITEM CARD */
function addItemCard() {

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
			
	initItemSelect2(document.getElementById('items-wrapper'));		

    calculateTotals();
}


/* SET ITEM DATA */
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

	if(img){
		imgTag.src = img;
		imgTag.style.display = 'block';
		imgInput.value = img; // 🔥 THIS IS WHAT SAVES
	}else{
		imgTag.style.display = 'none';
		imgInput.value = '';
	}
	
	<?php if(rsjm_is_stock_enabled()): ?>
	let stock = parseInt(opt.dataset.stock || 0);

	if(stock <= 0){
		alert('❌ This item is OUT OF STOCK');
	}

	if(stock > 0 && stock <= 5){
		alert('⚠️ Low stock: Only ' + stock + ' left');
	}
	<?php endif; ?>
}


/* CALCULATE ITEM */
/*function calcItem(el) {

    let card = el.closest('.rsjm-item-card');

    let qty   = parseFloat(card.querySelector('[name="qty[]"]').value || 0);
    let price = parseFloat(card.querySelector('[name="price[]"]').value || 0);

    card.querySelector('[name="total[]"]').value =
        (qty * price).toFixed(2);

    calculateTotals();
}*/

function calcItem(el) {

    let card = el.closest('.rsjm-item-card');

    let qty   = parseFloat(card.querySelector('[name="qty[]"]').value || 0);
    let price = parseFloat(card.querySelector('[name="price[]"]').value || 0);

    let discount = parseFloat(card.querySelector('[name="discount_amount[]"]')?.value || 0);

    let total = (qty * price) - discount;

    if(total < 0) total = 0;
	
	<?php if(rsjm_is_stock_enabled()): ?>
	let stock = parseInt(card.querySelector('.rsjm-item-select').selectedOptions[0].dataset.stock || 0);

	if(qty > stock){
		alert('❌ Only ' + stock + ' items available');
		card.querySelector('[name="qty[]"]').value = stock;
		qty = stock;
	}
	<?php endif; ?>
	
    card.querySelector('[name="total[]"]').value = total.toFixed(2);

    calculateTotals();
}

function handleDiscountPercent(el){

    let card = el.closest('.rsjm-item-card');

    let percent = parseFloat(el.value || 0);
    let price   = parseFloat(card.querySelector('[name="price[]"]').value || 0);
    let qty     = parseFloat(card.querySelector('[name="qty[]"]').value || 0);

    let base = price * qty;

    let discountAmount = (base * percent) / 100;

    card.querySelector('[name="discount_amount[]"]').value = discountAmount.toFixed(2);

    calcItem(el);
}

function handleDiscountAmount(el){

    let card = el.closest('.rsjm-item-card');

    let discount = parseFloat(el.value || 0);
    let price    = parseFloat(card.querySelector('[name="price[]"]').value || 0);
    let qty      = parseFloat(card.querySelector('[name="qty[]"]').value || 0);

    let base = price * qty;

    let percent = 0;

    if(base > 0){
        percent = (discount / base) * 100;
    }

    card.querySelector('[name="discount_percent[]"]').value = percent.toFixed(2);

    calcItem(el);
}


/* REMOVE ITEM */
function removeItem(btn){

    btn.closest('.rsjm-item-card').remove();

    calculateTotals();
}


/* TOGGLE REPLACEMENT */
function toggleReplacement(cb){

    let card = cb.closest('.rsjm-item-card');

    card.querySelector('.rsjm-replacement').style.display =
        cb.checked ? 'block' : 'none';
}


/* CALCULATE TOTALS */
function calculateTotals() {

    let subtotal = 0;

    // Item totals
    document.querySelectorAll('[name="total[]"]').forEach(el => {
        subtotal += parseFloat(el.value || 0);
    });

    document.getElementById('rsjm-subtotal').value = subtotal.toFixed(2);

    /* =========================
       ORDER DISCOUNT
    ========================= */

    let discountType =
        document.getElementById('rsjm-discount-type')?.value || 'amount';

    let discountValue =
        parseFloat(document.getElementById('rsjm-discount-value')?.value || 0);

    let discountAmount = 0;

    if(discountType === 'percent'){
        discountAmount = (subtotal * discountValue) / 100;
    } else {
        discountAmount = discountValue;
    }

    if(discountAmount > subtotal){
        discountAmount = subtotal;
    }

    let taxableAmount = subtotal - discountAmount;

    document.getElementById('rsjm-discount-amount').value =
        discountAmount.toFixed(2);

    document.getElementById('rsjm-price-after-discount').value =
        taxableAmount.toFixed(2);

    /* =========================
       GST ON DISCOUNTED PRICE
    ========================= */

    let gstType =
        document.getElementById('rsjm-gst-type').value;

    let gstPercent =
        parseFloat(document.getElementById('rsjm-gst-percent').value || 0);

    let cgst = 0,
        sgst = 0,
        igst = 0;

    document.getElementById('cgst-box').style.display = 'none';
    document.getElementById('sgst-box').style.display = 'none';
    document.getElementById('igst-box').style.display = 'none';

    if (gstType === 'cgst_sgst') {

        cgst = sgst =
            (taxableAmount * gstPercent / 100) / 2;

        document.getElementById('cgst-box').style.display = 'block';
        document.getElementById('sgst-box').style.display = 'block';
    }

    if (gstType === 'igst') {

        igst =
            taxableAmount * gstPercent / 100;

        document.getElementById('igst-box').style.display = 'block';
    }

    document.getElementById('rsjm-cgst').value = cgst.toFixed(2);
    document.getElementById('rsjm-sgst').value = sgst.toFixed(2);
    document.getElementById('rsjm-igst').value = igst.toFixed(2);

    /* =========================
       GRAND TOTAL
    ========================= */

    let grand =
        taxableAmount + cgst + sgst + igst;

    /* =========================
       REDEEM
    ========================= */

    let redeem =
        parseFloat(document.getElementById('rsjm-redeem-points')?.value || 0);

    if (redeem > grand) {
        redeem = grand;

        document.getElementById('rsjm-redeem-points').value =
            grand.toFixed(2);
    }

    grand -= redeem;

    if (grand < 0) grand = 0;

    document.getElementById('rsjm-grand-total').value =
        grand.toFixed(2);

    /* =========================
       ADVANCE
    ========================= */

    let advance =
        parseFloat(document.querySelector('[name="advance"]')?.value || 0);

    let pending = grand - advance;

    if (pending < 0) pending = 0;

    document.getElementById('rsjm-pending-preview').value =
        pending.toFixed(2);
}
	
	
	
	
	document.addEventListener("DOMContentLoaded", function(){

		const customerSelect = document.querySelector('[name="customer_id"]');
		const availableField = document.getElementById('rsjm-available-points');
		const redeemField    = document.getElementById('rsjm-redeem-points');

		function setMaxRedeem(points){
			redeemField.max = points;
		}

		if(customerSelect){

			jQuery('#rsjm_customer').on('change', function(){

    let customerId = this.value;

    if(!customerId){
        jQuery('#rsjm-available-points').val(0);
        return;
    }

    fetch(ajaxurl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            action: 'rsjm_get_points',
            customer_id: customerId
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            let points = parseInt(data.data.points) || 0;
            jQuery('#rsjm-available-points').val(points);
            jQuery('#rsjm-redeem-points').attr('max', points);
        } else {
            jQuery('#rsjm-available-points').val(0);
        }
    });

});

		}

		if(redeemField){
			redeemField.addEventListener('input', function(){

				let max = parseInt(availableField.value) || 0;
				let val = parseInt(this.value) || 0;

				if(val > max){
					this.value = max;
				}

				if(val < 0){
					this.value = 0;
				}

				calculateTotals();
			});
		}

	});
	
	

	jQuery(document).ready(function($){

		$('#rsjm_customer').select2({
			placeholder: "Search or select customer",
			width: '100%'
		});
		
		initItemSelect2();

	});
	
	document.getElementById('add_customer_btn').addEventListener('click', function(){
		document.getElementById('rsjm_customer_modal').style.display = 'block';
	});
	
	
	document.getElementById('save_customer').addEventListener('click', function(){

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
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: data
			})
			.then(res => res.json())
			.then(res => {

				if(res.success){

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

		function openItemModal(btn){
			currentSelect = btn.closest('.rsjm-item-card').querySelector('.rsjm-item-select');
			document.getElementById('rsjm_item_modal').style.display = 'block';
		}

		function closeItemModal(){
			document.getElementById('rsjm_item_modal').style.display = 'none';
		}
		
		
	document.getElementById('save_item').addEventListener('click', function(){

		let formData = new FormData();

		formData.append('action', 'rsjm_add_item');
		formData.append('name', document.getElementById('item_name').value);
		formData.append('sku', document.getElementById('item_sku').value);
		formData.append('price', document.getElementById('item_price').value);

		let file = document.getElementById('item_image').files[0];
		if(file){
			formData.append('image', file);
		}

		fetch(ajaxurl, {
			method: 'POST',
			body: formData
		})
		.then(res => res.json())
		.then(res => {

			if(res.success){

				let option = new Option(res.data.name, res.data.id, true, true);
				option.dataset.price = res.data.price;
				option.dataset.sku   = res.data.sku;
				option.dataset.image = res.data.image; // 🔥 NEW

				jQuery(currentSelect).append(option).trigger('change');

				closeItemModal();

			} else {
				alert(res.data);
			}

		});

	});
	
	document.querySelector('[name="job_status"]').addEventListener('change', function(){

		let box = document.getElementById('payment-box');
		let total = parseFloat(document.getElementById('rsjm-grand-total').value || 0);

		if(this.value === 'completed'){
			box.style.display = 'block';
			document.querySelector('[name="paid_amount"]').value = total;
		} else {
			box.style.display = 'none';
		}

	});
		
	
	function initItemSelect2(context = document){

		jQuery(context).find('.rsjm-item-select').select2({
			placeholder: "Search or select item",
			width: '100%',
			allowClear: true
		});

	}
	
	document.getElementById('item_image').addEventListener('change', function(e){

		let file = e.target.files[0];
		if(!file) return;

		let reader = new FileReader();

		reader.onload = function(ev){
			let img = document.getElementById('item_preview');
			img.src = ev.target.result;
			img.style.display = 'block';
		};

		reader.readAsDataURL(file);

	});
	
	function updateItemImage(input){

		let file = input.files[0];
		if(!file) return;

		let reader = new FileReader();

		reader.onload = function(e){
			let card = input.closest('.rsjm-item-card');

			let imgTag = card.querySelector('.item-image-preview');
			let hidden = card.querySelector('[name="item_image[]"]');

			imgTag.src = e.target.result;
			imgTag.style.display = 'block';

			hidden.value = e.target.result; // base64 (or upload separately if needed)
		};

		reader.readAsDataURL(file);
	}
	
	
	function formatItem(item){

		if(!item.id) return item.text;

		let img = jQuery(item.element).data('image');

		if(img){
			return $(`
				<div style="display:flex;align-items:center;gap:10px;">
					<img src="${img}" style="width:30px;height:30px;object-fit:cover;border-radius:4px;">
					<span>${item.text}</span>
				</div>
			`);
		}

		return item.text;
	}

	function addPaymentRow(){

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
	
	document.querySelector('[name="job_status"]').addEventListener('change', function(){

		let box = document.getElementById('payment-box');

		if(this.value === 'completed'){
			box.style.display = 'block';

			if(document.querySelectorAll('.payment-row').length === 0){
				addPaymentRow();
			}

		} else {
			box.style.display = 'none';
		}

	});
</script>


