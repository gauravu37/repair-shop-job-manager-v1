<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

/* ===========================
   HANDLE BULK DELETE
=========================== */

if(isset($_POST['bulk_delete']) && !empty($_POST['job_ids'])){

    foreach($_POST['job_ids'] as $job_id){

        $job_id = intval($job_id);

        // Delete job items
        $wpdb->delete($wpdb->prefix.'rsjm_job_items',['job_id'=>$job_id]);

        // Delete payments
        $wpdb->delete($wpdb->prefix.'rsjm_payments',['job_id'=>$job_id]);

        // Delete job
        $wpdb->delete($wpdb->prefix.'rsjm_jobs',['id'=>$job_id]);
    }

    echo '<div class="updated"><p>Selected jobs deleted.</p></div>';
}

/* ===========================
   SEARCH
=========================== */

$where = "1=1";

if(!empty($_GET['s'])){
    $search = sanitize_text_field($_GET['s']);
    $where .= $wpdb->prepare(
        " AND (j.id=%d OR u.display_name LIKE %s OR j.status LIKE %s)",
        intval($search),
        "%$search%",
        "%$search%"
    );
}

/* ===========================
   FETCH JOBS
=========================== */

$jobs = $wpdb->get_results(
    "SELECT j.*, u.display_name
     FROM {$wpdb->prefix}rsjm_jobs j
     LEFT JOIN {$wpdb->prefix}users u ON u.ID = j.customer_id
     WHERE $where
     ORDER BY j.id DESC"
);
?>

<div class="wrap">
<h1>Repair Jobs</h1>

<form method="get" style="margin-bottom:15px;">
    <input type="hidden" name="page" value="rsjm-jobs">
    <input type="text" name="s" placeholder="Search Job ID / Customer / Status"
           value="<?=esc_attr($_GET['s'] ?? '')?>">
    <button class="button">Search</button>
</form>

<form method="post">

<table class="wp-list-table widefat striped">

<thead>
<tr>
    <th><input type="checkbox" id="select-all"></th>
    <th>ID</th>
    <th>Customer</th>
    <th>Total</th>
    <th>Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php if($jobs): foreach($jobs as $job): ?>

<tr>
    <td>
        <input type="checkbox" name="job_ids[]" value="<?=$job->id?>">
    </td>

    <td>#<?=$job->id?></td>

    <td><?=esc_html($job->display_name)?></td>

    <td>₹<?=number_format($job->total,2)?></td>

    <td>
        <span class="rsjm-status rsjm-status-<?=$job->status?>">
            <?=ucfirst($job->status)?>
        </span>
    </td>

    <td><?=date('d M Y', strtotime($job->created_at))?></td>

    <td>
        <a class="button button-small"
           href="<?=admin_url('admin.php?page=rsjm-view-job&job_id='.$job->id)?>">
           View
        </a>
    </td>
</tr>

<?php endforeach; else: ?>

<tr><td colspan="7">No jobs found.</td></tr>

<?php endif; ?>

</tbody>
</table>

<br>

<button name="bulk_delete"
        class="button button-danger"
        onclick="return confirm('Delete selected jobs?')">
    Delete Selected
</button>

</form>
</div>

<script>
document.getElementById('select-all').addEventListener('change',function(){
    document.querySelectorAll('input[name="job_ids[]"]').forEach(cb=>{
        cb.checked = this.checked;
    });
});
</script>