<?php
/*
Plugin Name: Repair Shop Job Manager
Description: Complete repair shop system with GST, PDF, WhatsApp & UPI
Version: 1.0
Author: Gaurav Mittal
*/

if (!defined('ABSPATH')) exit;

global $wpdb;

define('RSJM_PATH', plugin_dir_path(__FILE__));
define('RSJM_URL', plugin_dir_url(__FILE__));

require_once RSJM_PATH.'lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

/* ---------------- ACTIVATE ---------------- */

register_activation_hook(__FILE__, function () {
    add_role('repair_customer', 'Repair Customer');

    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH.'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}rsjm_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        sku VARCHAR(100),
        price DECIMAL(10,2)
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}rsjm_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT,
        subtotal DECIMAL(10,2),
        gst_type VARCHAR(20),
        cgst DECIMAL(10,2),
        sgst DECIMAL(10,2),
        igst DECIMAL(10,2),
        total DECIMAL(10,2),
        delivery_date DATE,
        status VARCHAR(20),
		payment_method VARCHAR(20),
		paid_amount DECIMAL(10,2),
		pending_amount DECIMAL(10,2),
        payment_mode VARCHAR(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}rsjm_job_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT,
        item_id INT,
        sku VARCHAR(100),
        qty INT,
        price DECIMAL(10,2),
        total DECIMAL(10,2),
        problem TEXT,
        replacement TINYINT,
        replacement_sku VARCHAR(100)
    ) $charset;");
});

/* ---------------- ADMIN MENU ---------------- */




add_action('admin_menu', function () {

    add_menu_page(
        'Repair Jobs',
        'Repair Jobs',
        'manage_options',
        'rsjm-jobs',
        function () {
            include RSJM_PATH.'views/jobs.php';
        },
        'dashicons-hammer'
    );

    add_submenu_page(
        'rsjm-jobs',
        'Add Job',
        'Add Job',
        'manage_options',
        'rsjm-add-job',
        function () {
            include RSJM_PATH.'views/add-job.php';
        }
    );

    add_submenu_page(
        'rsjm-jobs',
        'Item Master',
        'Item Master',
        'manage_options',
        'rsjm-items',
        function () {
            include RSJM_PATH.'views/items.php';
        }
    );

    add_submenu_page(
        'rsjm-jobs',
        'Settings',
        'Settings',
        'manage_options',
        'rsjm-settings',
        function () {
            include RSJM_PATH.'views/settings.php';
        }
    );

    // ✅ HIDDEN PAGE (THIS WAS CAUSING ERROR)
    add_submenu_page(
        null,
        'View Job',
        'View Job',
        'manage_options',
        'rsjm-view-job',
        function () {
            include RSJM_PATH.'views/view-job.php';
        }
    );
	
	add_submenu_page(
	 'rsjm-jobs','Analytics','Analytics','manage_options','rsjm-analytics',
	 function(){ include RSJM_PATH.'views/analytics.php'; }
	);


});


/* ---------------- SAVE JOB ---------------- */

add_action('admin_init', function () {
    if (!isset($_POST['rsjm_save_job'])) return;

    global $wpdb;
	
	
	if(isset($_POST['rsjm_update_items'])){
		foreach($_POST['qty'] as $id=>$qty){
			$wpdb->update(
				$wpdb->prefix.'rsjm_job_items',
				[
					'qty'=>$qty,
					'price'=>$_POST['price'][$id],
					'total'=>$qty*$_POST['price'][$id]
				],
				['id'=>$id]
			);
		}
}


    $subtotal = array_sum($_POST['total']);
    $gst_percent = floatval($_POST['gst_percent']);
    $gst_type = $_POST['gst_type'];

    $cgst = $sgst = $igst = 0;
    if ($gst_type === 'cgst_sgst') {
        $cgst = $sgst = ($subtotal * $gst_percent / 100) / 2;
    } elseif ($gst_type === 'igst') {
        $igst = ($subtotal * $gst_percent / 100);
    }

    $total = $subtotal + $cgst + $sgst + $igst;

    $wpdb->insert($wpdb->prefix.'rsjm_jobs', [
        'customer_id' => intval($_POST['customer_id']),
        'subtotal' => $subtotal,
        'gst_type' => $gst_type,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'igst' => $igst,
        'total' => $total,
        'delivery_date' => $_POST['delivery_date'],
        'status' => 'pending'
    ]);

    $job_id = $wpdb->insert_id;

    foreach ($_POST['item_id'] as $k => $item_id) {
        $wpdb->insert($wpdb->prefix.'rsjm_job_items', [
            'job_id' => $job_id,
            'item_id' => $item_id,
            'sku' => $_POST['sku'][$k],
            'qty' => $_POST['qty'][$k],
            'price' => $_POST['price'][$k],
            'total' => $_POST['total'][$k],
            'problem' => $_POST['problem'][$k],
            'replacement' => isset($_POST['replacement'][$k]) ? 1 : 0,
            'replacement_sku' => $_POST['replacement_sku'][$k]
        ]);
    }

    rsjm_send_email($job_id);
    rsjm_send_whatsapp($job_id);

    wp_redirect(admin_url('admin.php?page=rsjm-jobs'));
    exit;
});

/* ---------------- PDF ---------------- */

function rsjm_generate_pdf($job_id) {
    global $wpdb;

    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
    $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=$job_id");
    $user = get_user_by('id', $job->customer_id);

    ob_start();
    include RSJM_PATH.'templates/job-pdf.php';
    $html = ob_get_clean();

    $pdf = new Dompdf();
    $pdf->loadHtml($html);
    $pdf->setPaper('A4');
    $pdf->render();

    $file = RSJM_PATH."pdf/job-$job_id.pdf";
    file_put_contents($file, $pdf->output());

    return $file;
}

/* ---------------- EMAIL ---------------- */

function rsjm_send_email($job_id) {
    global $wpdb;
    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
    $user = get_user_by('id', $job->customer_id);
    $pdf = rsjm_generate_pdf($job_id);

    wp_mail(
        $user->user_email,
        "Repair Estimate #$job_id",
        "Your repair estimate is attached.",
        [],
        [$pdf]
    );
}

/* ---------------- WHATSAPP (WAHA) ---------------- */

function rsjm_send_whatsapp($job_id) {
    global $wpdb;
    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
    $user = get_user_by('id', $job->customer_id);

    $msg = "Repair Job #$job_id\nTotal: ₹{$job->total}\nStatus: {$job->status}";

    wp_remote_post(get_option('rsjm_waha_url').'/sendText', [
        'headers' => ['Content-Type'=>'application/json'],
        'body' => json_encode([
            'session' => get_option('rsjm_waha_session'),
            'chatId' => "91{$user->user_login}@c.us",
            'text' => $msg
        ])
    ]);
}




add_action('admin_init', function () {

    if (!isset($_POST['rsjm_update_job'])) return;

    if (
        !isset($_POST['rsjm_nonce']) ||
        !wp_verify_nonce($_POST['rsjm_nonce'],'rsjm_update_job')
    ) {
        wp_die('Security check failed');
    }

    global $wpdb;

    $job_id = intval($_POST['job_id']);
    $status = sanitize_text_field($_POST['status']);

    $data = [
        'status' => $status
    ];

    if ($status === 'ready') {
        $data['total'] = floatval($_POST['ready_amount']);
    }

    if (in_array($status,['completed','partial'])) {
        $data['payment_method'] = sanitize_text_field($_POST['payment_method']);
        $data['paid_amount'] = floatval($_POST['paid_amount']);
        $data['pending_amount'] = floatval($_POST['pending_amount']);
    }

    $wpdb->update(
        $wpdb->prefix.'rsjm_jobs',
        $data,
        ['id'=>$job_id]
    );

    rsjm_notify_status_change($job_id);

    wp_redirect(admin_url('admin.php?page=rsjm-view-job&job_id='.$job_id.'&updated=1'));
    exit;
});



function rsjm_notify_status_change($job_id) {
    global $wpdb;

    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
    $user = get_user_by('id', $job->customer_id);

    $msg = "";
    if ($job->status === 'in_progress') {
        $msg = "Your repair job #$job_id is now IN PROGRESS.";
    }

    if ($job->status === 'completed') {
        if ($job->payment_mode === 'upi') {
            $upi = get_option('rsjm_upi');
            $link = "upi://pay?pa=$upi&pn=RepairShop&am={$job->total}";
            $msg = "Your job #$job_id is completed.\nAmount: ₹{$job->total}\nPay via UPI:\n$link";
        } else {
            $msg = "Your job #$job_id is completed.\nAmount: ₹{$job->total}\nPayment Mode: Cash";
        }
    }
	
	if ($job->status === 'ready') {
		$msg = "Your job #$job_id is ready.\nAmount: ₹{$job->total}\nPay here:\n".site_url("/pay-job/?job=$job_id");
	}

	if ($job->status === 'partial') {
		$msg = "Partial payment received.\nPaid: ₹{$job->paid_amount}\nPending: ₹{$job->pending_amount}";
	}


    // Email
    wp_mail(
        $user->user_email,
        "Job Update #$job_id",
        $msg
    );

    // WhatsApp
    wp_remote_post(get_option('rsjm_waha_url').'/sendText', [
        'headers' => ['Content-Type'=>'application/json'],
        'body' => json_encode([
            'session' => get_option('rsjm_waha_session'),
            'chatId' => "91{$user->user_login}@c.us",
            'text' => $msg
        ])
    ]);
}


add_shortcode('repair_job_status', function () {
    if (!is_user_logged_in()) {
        return '<p>Please login to view your jobs.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    $jobs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE customer_id=%d",
            $user_id
        )
    );

    ob_start();
    ?>
    <h3>My Repair Jobs</h3>
    <table>
    <tr><th>Job</th><th>Status</th><th>Total</th></tr>
    <?php foreach($jobs as $j): ?>
        <tr>
            <td>#<?= $j->id ?></td>
            <td><?= ucfirst($j->status) ?></td>
            <td>₹<?= $j->total ?></td>
        </tr>
    <?php endforeach; ?>
    </table>
    <?php
    return ob_get_clean();
});


function rsjm_generate_receipt($job_id){
    ob_start();
    include RSJM_PATH.'templates/payment-receipt.php';
    $html = ob_get_clean();

    $pdf = new Dompdf();
    $pdf->loadHtml($html);
    $pdf->render();

    file_put_contents(RSJM_PATH."pdf/receipt-$job_id.pdf",$pdf->output());
}


add_action('wp_dashboard_setup', function(){
    wp_add_dashboard_widget('rsjm_dash','Repair Jobs Summary', function(){
        global $wpdb;
        echo "Pending: ".$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs WHERE status='pending'");
    });
});


if(!wp_next_scheduled('rsjm_reminder')){
    wp_schedule_event(time(),'daily','rsjm_reminder');
}


add_action('rsjm_reminder', function(){
    global $wpdb;
    $jobs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE status='pending'");
    foreach($jobs as $j){
        rsjm_notify_status_change($j->id);
    }
});


add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('rsjm-style', RSJM_URL.'assets/style.css');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('rsjm-style', RSJM_URL.'assets/style.css');
});
