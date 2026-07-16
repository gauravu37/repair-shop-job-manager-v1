<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$master_items        = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_items");
$mode                = 'add';
$job                 = null;
$existing_line_items = [];
?>

<div class="rsjm-wrap">
<?php include RSJM_PATH . 'views/job-form.php'; ?>
</div>