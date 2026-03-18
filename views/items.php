<?php 
global $wpdb;

if(isset($_POST['add'])){
    $wpdb->insert($wpdb->prefix.'rsjm_items',[
        'name'  => sanitize_text_field($_POST['name']),
        'sku'   => sanitize_text_field($_POST['sku']),
        'price' => floatval($_POST['price'])
    ]);
}

$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
?>

<div class="rsjm-wrap">

    <!-- TITLE -->
    <h2 class="rsjm-title">📦 Item Master</h2>

    <!-- ADD FORM -->
    <div class="rsjm-card">
        <h3>Add New Item</h3>

        <form method="post" class="rsjm-grid">

            <div class="rsjm-field">
                <label>Item Name</label>
                <input name="name" required placeholder="Enter item name">
            </div>

            <div class="rsjm-field">
                <label>SKU</label>
                <input name="sku" placeholder="Enter SKU">
            </div>

            <div class="rsjm-field">
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="price" required placeholder="0.00">
            </div>

            <div class="rsjm-field rsjm-full">
                <button class="rsjm-btn rsjm-btn-primary" name="add">
                    ➕ Add Item
                </button>
            </div>

        </form>
    </div>

    <!-- ITEM LIST -->
    <div class="rsjm-card">

        <h3>Item List</h3>

        <div class="rsjm-table-wrap">
            <table class="rsjm-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if($items): foreach($items as $index => $i): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc_html($i->name) ?></td>
                            <td><?= esc_html($i->sku) ?></td>
                            <td>₹<?= number_format($i->price,2) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<!-- STYLE -->
<style>

.rsjm-wrap {
    max-width: 900px;
}

.rsjm-title {
    margin-bottom: 15px;
}

.rsjm-card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.rsjm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.rsjm-field label {
    display: block;
    font-size: 13px;
    margin-bottom: 5px;
    color: #555;
}

.rsjm-field input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.rsjm-full {
    grid-column: 1 / -1;
}

.rsjm-btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.rsjm-btn-primary {
    background: #007bff;
    color: #fff;
}

.rsjm-btn-primary:hover {
    background: #0056b3;
}

.rsjm-table-wrap {
    overflow-x: auto;
}

.rsjm-table {
    width: 100%;
    border-collapse: collapse;
}

.rsjm-table th {
    background: #f4f6f9;
    padding: 10px;
    text-align: left;
    font-size: 14px;
}

.rsjm-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

/* MOBILE */
@media(max-width:600px){
    .rsjm-card {
        padding: 15px;
    }
}

</style>