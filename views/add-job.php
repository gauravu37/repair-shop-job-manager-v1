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

const ITEMS = <?php echo json_encode($items); ?>;


/* ADD ITEM CARD */
function addItemCard() {

    let options = '<option value="">Select Item</option>';

    ITEMS.forEach(i => {
        options += `
            <option value="${i.id}"
                    data-price="${i.price}"
                    data-sku="${i.sku}" 
					data-image="${i.image}">
                ${i.name}
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
}


/* CALCULATE ITEM */
function calcItem(el) {

    let card = el.closest('.rsjm-item-card');

    let qty   = parseFloat(card.querySelector('[name="qty[]"]').value || 0);
    let price = parseFloat(card.querySelector('[name="price[]"]').value || 0);

    card.querySelector('[name="total[]"]').value =
        (qty * price).toFixed(2);

    calculateTotals();
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

    document.querySelectorAll('[name="total[]"]').forEach(el => {
        subtotal += parseFloat(el.value || 0);
    });

    document.getElementById('rsjm-subtotal').value = subtotal.toFixed(2);

    let gstType = document.getElementById('rsjm-gst-type').value;
    let gstPercent = parseFloat(document.getElementById('rsjm-gst-percent').value || 0);

    let cgst = 0, sgst = 0, igst = 0;

    document.getElementById('cgst-box').style.display = 'none';
    document.getElementById('sgst-box').style.display = 'none';
    document.getElementById('igst-box').style.display = 'none';

    if (gstType === 'cgst_sgst') {
        cgst = sgst = (subtotal * gstPercent / 100) / 2;
        document.getElementById('cgst-box').style.display = 'block';
        document.getElementById('sgst-box').style.display = 'block';
    }

    if (gstType === 'igst') {
        igst = subtotal * gstPercent / 100;
        document.getElementById('igst-box').style.display = 'block';
    }

    document.getElementById('rsjm-cgst').value = cgst.toFixed(2);
    document.getElementById('rsjm-sgst').value = sgst.toFixed(2);
    document.getElementById('rsjm-igst').value = igst.toFixed(2);

    let grand = subtotal + cgst + sgst + igst;

    // APPLY REDEEM
    let redeem = parseFloat(document.getElementById('rsjm-redeem-points')?.value || 0);

    if (redeem > grand) {
        redeem = grand;
        document.getElementById('rsjm-redeem-points').value = grand;
    }
	
	// Show redeem discount
	let redeemDisplay = document.getElementById('rsjm-redeem-display');
	if(redeemDisplay){
		redeemDisplay.value = redeem.toFixed(2); 
	}

    grand = grand - redeem;

    if (grand < 0) grand = 0;

    document.getElementById('rsjm-grand-total').value = grand.toFixed(2);

    // APPLY ADVANCE
    let advance = parseFloat(document.querySelector('[name="advance"]')?.value || 0);

    let pending = grand - advance;
    if (pending < 0) pending = 0;

    document.getElementById('rsjm-pending-preview').value = pending.toFixed(2);
}
	
	
	
	
	document.addEventListener("DOMContentLoaded", function(){

		const customerSelect = document.querySelector('[name="customer_id"]');
		const availableField = document.getElementById('rsjm-available-points');
		const redeemField    = document.getElementById('rsjm-redeem-points');

		function setMaxRedeem(points){
			redeemField.max = points;
		}

		if(customerSelect){

			customerSelect.addEventListener('change', function(){

				let customerId = this.value;

				if(!customerId){
					availableField.value = 0;
					setMaxRedeem(0);
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
						availableField.value = points;
						setMaxRedeem(points);
					} else {
						availableField.value = 0;
						setMaxRedeem(0);
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

	
</script>
