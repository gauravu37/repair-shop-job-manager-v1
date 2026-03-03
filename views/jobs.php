<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$per_page = 10;
$paged = max(1, intval($_GET['paged'] ?? 1));
$offset = ($paged - 1) * $per_page;

/* ===========================
   STATUS FILTER
=========================== */

$status_filter = sanitize_text_field($_GET['status'] ?? '');

$where = "1=1";

if($status_filter){
    $where .= $wpdb->prepare(" AND j.status=%s",$status_filter);
}

/* ===========================
   SEARCH
=========================== */

if(!empty($_GET['s'])){
    $search = sanitize_text_field($_GET['s']);
    $where .= $wpdb->prepare(
        " AND (j.id=%d OR u.display_name LIKE %s)",
        intval($search),
        "%$search%"
    );
}

/* ===========================
   BULK DELETE
=========================== */

if(isset($_POST['bulk_delete']) && !empty($_POST['job_ids'])){
    foreach($_POST['job_ids'] as $job_id){
        $job_id = intval($job_id);
        $wpdb->delete($wpdb->prefix.'rsjm_job_items',['job_id'=>$job_id]);
        $wpdb->delete($wpdb->prefix.'rsjm_payments',['job_id'=>$job_id]);
        $wpdb->delete($wpdb->prefix.'rsjm_jobs',['id'=>$job_id]);
    }
    echo '<div class="updated"><p>Jobs deleted.</p></div>';
}

/* ===========================
   SINGLE DELETE
=========================== */

if(isset($_GET['delete_job'])){
    $job_id = intval($_GET['delete_job']);
    $wpdb->delete($wpdb->prefix.'rsjm_job_items',['job_id'=>$job_id]);
    $wpdb->delete($wpdb->prefix.'rsjm_payments',['job_id'=>$job_id]);
    $wpdb->delete($wpdb->prefix.'rsjm_jobs',['id'=>$job_id]);
    echo '<div class="updated"><p>Job deleted.</p></div>';
}

/* ===========================
   EXPORT CSV
=========================== */

if(isset($_GET['export_csv'])){
    $export = $wpdb->get_results(
        "SELECT j.*, u.display_name
         FROM {$wpdb->prefix}rsjm_jobs j
         LEFT JOIN {$wpdb->prefix}users u ON u.ID=j.customer_id
         WHERE $where
         ORDER BY j.id DESC"
    );

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=repair-jobs.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out,['ID','Customer','Total','Status','Date']);

    foreach($export as $e){
        fputcsv($out,[
            $e->id,
            $e->display_name,
            $e->total,
            $e->status,
            $e->created_at
        ]);
    }

    fclose($out);
    exit;
}

/* ===========================
   FETCH JOBS (WITH LIMIT)
=========================== */

$total_jobs = $wpdb->get_var(
    "SELECT COUNT(*) 
     FROM {$wpdb->prefix}rsjm_jobs j
     LEFT JOIN {$wpdb->prefix}users u ON u.ID=j.customer_id
     WHERE $where"
);

$jobs = $wpdb->get_results(
    "SELECT j.*, u.display_name
     FROM {$wpdb->prefix}rsjm_jobs j
     LEFT JOIN {$wpdb->prefix}users u ON u.ID=j.customer_id
     WHERE $where
     ORDER BY j.id DESC
     LIMIT $offset,$per_page"
);

$total_pages = ceil($total_jobs / $per_page);
?>

<div class="wrap">
<h1>Repair Jobs</h1>

<form method="get" style="margin-bottom:15px;">
    <input type="hidden" name="page" value="rsjm-jobs">

    <input type="text" name="s"
           placeholder="Search Job ID / Customer"
           value="<?=esc_attr($_GET['s'] ?? '')?>">

    <select name="status">
        <option value="">All Status</option>
        <option value="pending" <?=selected($status_filter,'pending',false)?>>Pending</option>
        <option value="in_progress" <?=selected($status_filter,'in_progress',false)?>>In Progress</option>
        <option value="ready" <?=selected($status_filter,'ready',false)?>>Ready</option>
        <option value="partial" <?=selected($status_filter,'partial',false)?>>Partial</option>
        <option value="completed" <?=selected($status_filter,'completed',false)?>>Completed</option>
    </select>

    <button class="button">Filter</button>

    <a class="button button-primary"
       href="<?=admin_url('admin.php?page=rsjm-jobs&export_csv=1')?>">
       Export CSV
    </a>
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
    <td><input type="checkbox" name="job_ids[]" value="<?=$job->id?>"></td>
    <td>#<?=$job->id?></td>
    <td><?=esc_html($job->display_name)?></td>
    <td>₹<?=number_format($job->total,2)?></td>
    <td><span class="rsjm-status rsjm-status-<?=$job->status?>">
        <?=ucfirst($job->status)?>
    </span></td>
    <td><?=date('d M Y',strtotime($job->created_at))?></td>
    <td>
        <a class="button button-small"
           href="<?=admin_url('admin.php?page=rsjm-view-job&job_id='.$job->id)?>">
           View
        </a>

        <a class="button button-small button-danger"
           href="<?=admin_url('admin.php?page=rsjm-jobs&delete_job='.$job->id)?>"
           onclick="return confirm('Delete this job?')">
           Delete
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

<!-- Pagination -->
<div class="tablenav">
    <div class="tablenav-pages">
        <?php for($i=1;$i<=$total_pages;$i++): ?>
            <a class="button <?=($i==$paged?'button-primary':'')?>"
               href="<?=admin_url('admin.php?page=rsjm-jobs&paged='.$i)?>">
               <?=$i?>
            </a>
        <?php endfor; ?>
    </div>
</div>

</div>

<script>
document.getElementById('select-all').addEventListener('change',function(){
    document.querySelectorAll('input[name="job_ids[]"]').forEach(cb=>{
        cb.checked = this.checked;
    });
});
</script>