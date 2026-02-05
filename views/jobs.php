<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$jobs = $wpdb->get_results("
    SELECT j.*, u.display_name, u.user_email
    FROM {$wpdb->prefix}rsjm_jobs j
    LEFT JOIN {$wpdb->users} u ON u.ID = j.customer_id
    ORDER BY j.id DESC
");
?>

<div class="rsjm-wrap">
<div class="rsjm-card">
<h2 class="rsjm-title">Repair Jobs</h2>

<table class="rsjm-table">
<thead>
<tr>
<th>ID</th>
<th>Customer</th>
<th>Total</th>
<th>Status</th>
<th>Delivery</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($jobs as $job): ?>
<tr>
<td>#<?=$job->id?></td>
<td><?=$job->display_name?></td>
<td>₹<?=number_format($job->total,2)?></td>
<td>
<span class="rsjm-badge rsjm-badge-<?=$job->status?>">
<?=ucfirst($job->status)?>
</span>
</td>
<td><?=$job->delivery_date?></td>
<td>
<a class="rsjm-btn rsjm-btn-primary"
href="<?=admin_url('admin.php?page=rsjm-view-job&job_id='.$job->id)?>">
View
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>
<div>----OLD-----</div>


<div class="wrap">
    <h1>Repair Jobs</h1>

    <?php if (empty($jobs)) : ?>
        <p><strong>No jobs found.</strong></p>
    <?php else : ?>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Job ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Delivery</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($jobs as $job) : ?>
            <tr>
                <td>#<?php echo esc_html($job->id); ?></td>
                <td>
                    <?php echo esc_html($job->display_name); ?><br>
                    <small><?php echo esc_html($job->user_email); ?></small>
                </td>
                <td>₹<?php echo number_format($job->total, 2); ?></td>
                <td>
                    <strong><?php echo ucfirst($job->status); ?></strong>
                </td>
                <td><?php echo esc_html($job->delivery_date); ?></td>
                <td><?php echo esc_html($job->created_at); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=rsjm-view-job&job_id='.$job->id); ?>">
						View / Update
					</a>
                    |
                    <a href="<?php echo admin_url('admin.php?page=rsjm-jobs&print='.$job->id); ?>" target="_blank">
                        Print
                    </a>
                    |
                    <a href="<?php echo admin_url('admin.php?page=rsjm-jobs&pdf='.$job->id); ?>">
                        PDF
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>
