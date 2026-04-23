<?php 
global $wpdb;
$table = $wpdb->prefix.'rsjm_items';

/* DELETE */
if(isset($_GET['delete'])){
    $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
}

/* ADD / UPDATE */
if(isset($_POST['save'])){

    $data = [
        'name'  => sanitize_text_field($_POST['name']),
        'sku'   => sanitize_text_field($_POST['sku']),
        'price' => floatval($_POST['price']),
        'image' => esc_url_raw($_POST['image'])
    ];

    if(!empty($_POST['id'])){
        $wpdb->update($table, $data, ['id' => intval($_POST['id'])]);
    }else{
        $wpdb->insert($table, $data);
    }
}

/* EDIT DATA */
$edit = null;
if(isset($_GET['edit'])){
    $edit = $wpdb->get_row("SELECT * FROM $table WHERE id=".intval($_GET['edit']));
}

$items = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
?>

<div class="rsjm-wrap">

<h2 class="rsjm-title">📦 Item Master</h2>

<!-- FORM -->
<div class="rsjm-card">
    <h3><?= $edit ? 'Edit Item' : 'Add New Item' ?></h3>

    <form method="post" class="rsjm-grid">

        <input type="hidden" name="id" value="<?= $edit->id ?? '' ?>">

        <div class="rsjm-field">
            <label>Name</label>
            <input name="name" required value="<?= $edit->name ?? '' ?>">
        </div>

        <div class="rsjm-field">
            <label>SKU</label>
            <input name="sku" value="<?= $edit->sku ?? '' ?>">
        </div>

        <div class="rsjm-field">
            <label>Price</label>
            <input type="number" step="0.01" name="price" required value="<?= $edit->price ?? '' ?>">
        </div>

        <div class="rsjm-field">
			<label>Item Image</label>

			<input type="hidden" name="image" id="image" value="<?= $edit->image ?? '' ?>">

			<div style="display:flex; gap:10px; align-items:center;">
				<button type="button" class="button" id="upload_image_btn">Upload Image</button>

				<img id="image_preview" 
					 src="<?= !empty($edit->image) ? esc_url($edit->image) : '' ?>" 
					 style="width:60px;height:60px;object-fit:cover;border-radius:6px;<?= empty($edit->image) ? 'display:none;' : '' ?>">
			</div>

		</div>

        <div class="rsjm-field rsjm-full">
            <button class="rsjm-btn rsjm-btn-primary" name="save">
                <?= $edit ? 'Update Item' : 'Add Item' ?>
            </button>
        </div>

    </form>
</div>

<!-- LIST -->
<div class="rsjm-card">

<h3>Item List</h3>

<table class="rsjm-table">
<thead>
<tr>
    <th>#</th>
    <th>Image</th>
    <th>Name</th>
    <th>SKU</th>
    <th>Price</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php if($items): foreach($items as $index => $i): ?>
<tr>
    <td><?= $index+1 ?></td>

    <td>
        <?php if($i->image): ?>
            <img src="<?= esc_url($i->image) ?>" width="50" height="50" style="border-radius:6px;">
        <?php else: ?>
            —
        <?php endif; ?>
    </td>

    <td><?= esc_html($i->name) ?></td>
    <td><?= esc_html($i->sku) ?></td>
    <td>₹<?= number_format($i->price,2) ?></td>

    <td>
        <a href="?page=rsjm-items&edit=<?= $i->id ?>" class="rsjm-btn">✏️</a>
        <a href="?page=rsjm-items&delete=<?= $i->id ?>" 
           class="rsjm-btn rsjm-btn-danger"
           onclick="return confirm('Delete this item?')">🗑️</a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="6" style="text-align:center;">No items found</td>
</tr>
<?php endif; ?>
</tbody>

</table>

</div>

</div>

<style>

.rsjm-btn-danger {
    background:#dc3545;
    color:#fff;
}

.rsjm-btn-danger:hover {
    background:#a71d2a;
}

.rsjm-table img {
    object-fit: cover;
}

</style>

<script>
jQuery(document).ready(function($){

    let mediaUploader;

    $('#upload_image_btn').click(function(e){
        e.preventDefault();

        if(mediaUploader){
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Item Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        mediaUploader.on('select', function(){
            let attachment = mediaUploader.state().get('selection').first().toJSON();

            $('#image').val(attachment.url);
            $('#image_preview').attr('src', attachment.url).show();
        });

        mediaUploader.open();
    });

});
</script>