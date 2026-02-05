<?php
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs");
$pending = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs WHERE status='pending'");
$revenue = $wpdb->get_var("SELECT SUM(total) FROM {$wpdb->prefix}rsjm_jobs");
?>
<h2>Dashboard</h2>
<p>Total Jobs: <?=$total?></p>
<p>Pending Jobs: <?=$pending?></p>
<p>Total Revenue: ₹<?=$revenue?></p>
