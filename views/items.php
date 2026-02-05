<?php global $wpdb;
if(isset($_POST['add'])){
    $wpdb->insert($wpdb->prefix.'rsjm_items',[
        'name'=>$_POST['name'],
        'sku'=>$_POST['sku'],
        'price'=>$_POST['price']
    ]);
}
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
?>

<h2>Item Master</h2>
<form method="post">
<input name="name" placeholder="Name">
<input name="sku" placeholder="SKU">
<input name="price" placeholder="Price">
<button name="add">Add</button>
</form>

<table border="1">
<?php foreach($items as $i): ?>
<tr><td><?=$i->name?></td><td><?=$i->sku?></td><td><?=$i->price?></td></tr>
<?php endforeach; ?>
</table>
