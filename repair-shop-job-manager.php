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
		advance DECIMAL(10,2),
		paid_amount DECIMAL(10,2),
		pending_amount DECIMAL(10,2),
        payment_mode VARCHAR(20),
		redeem_discount DECIMAL(10,2) DEFAULT 0,
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
	
	dbDelta("CREATE TABLE {$wpdb->prefix}rsjm_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
	    job_id INT,
	    amount DECIMAL(10,2),
	    method VARCHAR(20),
	    note VARCHAR(255),
	    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");
	
	dbDelta("CREATE TABLE {$wpdb->prefix}rsjm_points (
		id INT AUTO_INCREMENT PRIMARY KEY,
		customer_id BIGINT,
		job_id INT,
		points INT,
		type VARCHAR(20), -- earn / redeem / manual
		note VARCHAR(255),
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
	
	add_submenu_page(
		null,
		'Customer Job View',
		'Customer Job View',
		'read',
		'rsjm-customer-job',
		function(){
			include RSJM_PATH.'views/customer-job.php';
		}
	);

	add_submenu_page(
        'rsjm-jobs',
        'Customers',
        'Customers',
        'manage_options',
        'rsjm-customers',
        'rsjm_customers_page'
    );
	
	add_submenu_page(
		null,
		'Customer Detail',
		'Customer Detail',
		'manage_options',
		'rsjm-customer-view',
		'rsjm_customer_detail_page'
	);

});


/* ---------------- SAVE JOB ---------------- */

add_action('admin_init', function () {

    global $wpdb;

    /* =====================================
       1️⃣ SAVE NEW JOB
    ===================================== */
    if (isset($_POST['rsjm_save_job'])) {

        $subtotal = array_sum($_POST['total']);
        $gst_percent = floatval($_POST['gst_percent']);
        $gst_type = $_POST['gst_type'];

        $cgst = $sgst = $igst = 0;

        if ($gst_type === 'cgst_sgst') {
            $cgst = $sgst = ($subtotal * $gst_percent / 100) / 2;
        } elseif ($gst_type === 'igst') {
            $igst = ($subtotal * $gst_percent / 100);
        }

        $raw_total = $subtotal + $cgst + $sgst + $igst;

		$redeem_points = intval($_POST['redeem_points'] ?? 0);
		$redeem_discount = $redeem_points; // 1 point = ₹1

		if($redeem_discount > $raw_total){
			$redeem_discount = $raw_total;
		}

		$total = $raw_total - $redeem_discount;
		
		/* ===============================
		   APPLY REDEEM DISCOUNT
		=============================== */

		$redeem = intval($_POST['redeem_points'] ?? 0);
		$available = rsjm_get_customer_points(intval($_POST['customer_id']));

		if($redeem > $available){
			$redeem = $available;
		}

		if($redeem > 0){
			$total = $total - $redeem;
			if($total < 0) $total = 0;
		}

        $advance = floatval($_POST['advance'] ?? 0);
        //$paid = $advance;
        //$pending = $total - $paid;
        //if ($pending < 0) $pending = 0;

       // $status = $pending > 0 ? 'partial' : 'completed';

        /*$wpdb->insert($wpdb->prefix.'rsjm_jobs', [

            'customer_id' => intval($_POST['customer_id']),
            'subtotal' => $subtotal,
            'gst_type' => $gst_type,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total' => $total,
            'advance' => $advance,
            'paid_amount' => $paid,
            'pending_amount' => $pending,
            'status' => $status,
            'delivery_date' => $_POST['delivery_date']
        ]);*/
		
		$wpdb->insert($wpdb->prefix.'rsjm_jobs', [

			'customer_id' => intval($_POST['customer_id']),
			'subtotal' => $subtotal,
			'gst_type' => $gst_type,
			'cgst' => $cgst,
			'sgst' => $sgst,
			'igst' => $igst,
			'total' => $total,
			'redeem_discount' => $redeem_discount,   // ADD THIS
			'advance' => $advance,
			'paid_amount' => 0,
			'pending_amount' => $total,
			'status' => 'pending',
			'delivery_date' => $_POST['delivery_date']
		]);

        $job_id = $wpdb->insert_id;
		
		/* ===============================
		   SAVE REDEEM POINTS
		=============================== */

		$redeem = intval($_POST['redeem_points'] ?? 0);
		$available = rsjm_get_customer_points(intval($_POST['customer_id']));

		if($redeem > $available){
			$redeem = $available;
		}

		if($redeem > 0){

			$wpdb->insert($wpdb->prefix.'rsjm_points',[
				'customer_id' => intval($_POST['customer_id']),
				'job_id'      => $job_id,
				'points'      => $redeem,
				'type'        => 'redeem',
				'note'        => "Redeemed in Job #$job_id",
				'created_at'  => current_time('mysql')
			]);
		}

        /* Save advance in history */
        if ($advance > 0) {
			//rsjm_add_payment($job_id, $advance, 'Advance', 'Advance payment at booking');
			rsjm_add_payment(
				$job_id,
				$advance,
				'Advance',
				'Advance payment at booking',
				current_time('mysql')
			);
			
		}

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
        rsjm_notify_status_change($job_id);

        wp_redirect(admin_url('admin.php?page=rsjm-jobs'));
        exit;
    }


    /* =====================================
       2️⃣ ADD PAYMENT
    ===================================== */
    if (isset($_POST['rsjm_add_payment'])) {

        if (
            !isset($_POST['rsjm_nonce']) ||
            !wp_verify_nonce($_POST['rsjm_nonce'],'rsjm_add_payment')
        ) {
            wp_die('Security check failed');
        }

        $job_id = intval($_POST['job_id']);
        $amount = floatval($_POST['pay_amount']);
        $method = sanitize_text_field($_POST['pay_method']);
        $note   = sanitize_text_field($_POST['pay_note']);

        if ($amount <= 0) {
            wp_die('Invalid amount');
        }


		$date = sanitize_text_field($_POST['pay_date']);
		rsjm_add_payment($job_id, $amount, $method, $note, $date);
        // Insert payment history
        /*$wpdb->insert($wpdb->prefix.'rsjm_payments', [
            'job_id' => $job_id,
            'amount' => $amount,
            'method' => $method,
            'note'   => $note,
            'created_at' => current_time('mysql')
        ]);*/

        // Get job
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
                $job_id
            )
        );

        $new_paid = $job->paid_amount + $amount;
        $new_pending = $job->total - $new_paid;
        if ($new_pending < 0) $new_pending = 0;

        $new_status = $new_pending > 0 ? 'partial' : 'completed';

        $wpdb->update($wpdb->prefix.'rsjm_jobs', [
            'paid_amount'    => $new_paid,
            'pending_amount' => $new_pending,
            'status'         => $new_status
        ], ['id'=>$job_id]);

        rsjm_notify_status_change($job_id);

        wp_redirect(admin_url("admin.php?page=rsjm-view-job&job_id=$job_id"));
        exit;
    }

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

function waha_send($job_id) {
    global $wpdb;
    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");
    $user = get_user_by('id', $job->customer_id);

    $text = "Repair Job #$job_id\nTotal: ₹{$job->total}\nStatus: {$job->status}";
    $chatId = "91{$user->user_login}@c.us";

	$url = get_option('rsjm_waha_url').'/api/sendText';
	$key = '3373a66655894ec4b874a97175ca79d4';
	
	$payload = ['chatId'=>$chatId,'text'=>$text,'session'=>'default'];
	$args = ['body'=>wp_json_encode($payload),'headers'=>['Content-Type'=>'application/json','x-api-key'=>$key],'timeout'=>20];
	$resp = wp_remote_post($url,$args);
	if (is_wp_error($resp)) error_log('CampRegister WAHA error: '.$resp->get_error_message());
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

    /*if (in_array($status,['completed','partial'])) {
        $data['payment_method'] = sanitize_text_field($_POST['payment_method']);
        $data['paid_amount'] = floatval($_POST['paid_amount']);
        $data['pending_amount'] = floatval($_POST['pending_amount']);
    }*/
	
	if (in_array($status,['completed','partial'])) {

		$method = sanitize_text_field($_POST['payment_method']);
		$amount = floatval($_POST['paid_amount']);
		$note   = "Payment via status update";

		if($amount > 0){
			rsjm_add_payment($job_id, $amount, $method, $note);
		}

	}

    $wpdb->update(
        $wpdb->prefix.'rsjm_jobs',
        $data,
        ['id'=>$job_id]
    );

    rsjm_notify_status_change($job_id);
	
	rsjm_sync_job_payments($job_id);
	rsjm_auto_update_status($job_id);

    wp_redirect(admin_url('admin.php?page=rsjm-view-job&job_id='.$job_id.'&updated=1'));
    exit;
});


function rsjm_notify_status_change($job_id){

    global $wpdb;

    $job = $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id"
    );

    if(!$job) return;

    $user = get_user_by('id', $job->customer_id);
    if(!$user) return;

    /* GET TEMPLATE */
    switch($job->status){
        case 'pending':
            $tpl = get_option('rsjm_msg_pending');
            break;
        case 'in_progress':
            $tpl = get_option('rsjm_msg_progress');
            break;
        case 'ready':
            $tpl = get_option('rsjm_msg_ready');
            break;
        case 'completed':
            $tpl = get_option('rsjm_msg_completed');
            break;
        case 'partial':
            $tpl = get_option('rsjm_msg_partial');
            break;
        default:
            return;
    }

    if(empty($tpl)) return;

    /* PARSE TAGS */
    $message = rsjm_parse_message($tpl, $job);
	
	
	$items = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ji.*, i.name 
			 FROM {$wpdb->prefix}rsjm_job_items ji
			 LEFT JOIN {$wpdb->prefix}rsjm_items i 
			 ON ji.item_id = i.id
			 WHERE ji.job_id = %d",
			$job_id
		),
		ARRAY_A
	);
	
	
	$items_array = [];

	if(!empty($items)){
		foreach($items as $item){

			$qty   = floatval($item['qty']);
			$price = floatval($item['price']);
			$total = floatval($item['total']);

			// fallback
			if(!$total){
				$total = $qty * $price;
			}

			$items_array[] = [
				'name'  => $item['name'] ?? 'Item',
				'qty'   => $qty,
				'price' => $price,
				'total' => $total
			];
		}
	}

    /* ==============================
       🔗 GENERATE PUBLIC LINK
    ============================== */

    $api_url = "https://prontoinfosys.net/api/save-job.php";

	$attachment_id = get_option('rsjm_shop_logo'); // Replace with your actual image ID
    $image_size = 'full';
	$image_url = wp_get_attachment_image_url( $attachment_id, $image_size );
    $data = [
        'job_id' => $job->id,
        'name'   => $user->display_name,
        'phone'  => $user->user_login,
        'status' => $job->status,
        'amount' => $job->total ?? 0,
        'time'   => time(),
		'logo' => $image_url,
		'shop_name' => get_option('rsjm_shop_name'),
		'shop_address' => get_option('rsjm_shop_address'),
		'shop_phone' => get_option('rsjm_shop_phone'),
		'items' => $items_array,
		'subtotal' => $job->subtotal,
		'advance' => $job->advance,
		'pending' => $job->pending,
		'total' => $job->total,
    ];

    $response = wp_remote_post($api_url, [
        'body' => json_encode($data),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 15
    ]);

    if(!is_wp_error($response)){
        $res = json_decode(wp_remote_retrieve_body($response), true);

        if(!empty($res['token'])){
            $link = "https://prontoinfosys.net/api/view.php?id=".$res['token'];

            // Append link in message
            $message .= "\n\nView Details: ".$link;
        }
    }

    /* ==============================
       📲 SEND WHATSAPP
    ============================== */

    $phone = preg_replace('/[^0-9]/','',$user->user_login);

    $url     = get_option('rsjm_waha_url').'/api/sendText';
    $session = get_option('rsjm_waha_session');
    $key     = get_option('rsjm_waha_key');

    if(!$url || !$session || !$key) return;

    $chatId = "91{$phone}@c.us";

    $payload = [
        'chatId' => $chatId,
        'text'   => $message,
        'session'=> 'default'
    ];

    $args = [
        'body' => wp_json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-Key'    => $key
        ],
        'timeout' => 20
    ];

    $resp = wp_remote_post($url, $args);

    // Optional debug
    if(is_wp_error($resp)){
        error_log("WAHA Error: ".$resp->get_error_message());
    }
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

add_action('admin_enqueue_scripts', function($hook){

    // Only load on our settings page
    if ($hook !== 'repair-jobs_page_rsjm-settings') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery');
});


add_action('admin_enqueue_scripts', function($hook){

    // Load only on your plugin page (optional but recommended)
    if(isset($_GET['page']) && $_GET['page'] === 'rsjm-add-job'){

        // jQuery (already available but safe)
        wp_enqueue_script('jquery');

        // Select2 CSS
        wp_enqueue_style(
            'select2-css',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
        );

        // Select2 JS
        wp_enqueue_script(
            'select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            null,
            true
        );
    }

});


add_action('admin_init', function () {

    if (!isset($_POST['rsjm_edit_items'])) return;

    if (
        !isset($_POST['rsjm_nonce']) ||
        !wp_verify_nonce($_POST['rsjm_nonce'],'rsjm_edit_items')
    ) {
        wp_die('Security error');
    }

    global $wpdb;
    $job_id = intval($_POST['job_id']);

    // Remove items
    if (!empty($_POST['remove'])) {
        foreach($_POST['remove'] as $remove_id){
            $wpdb->delete($wpdb->prefix.'rsjm_job_items',['id'=>intval($remove_id)]);
        }
    }

    // Update items
    $subtotal = 0;
    foreach($_POST['qty'] as $id=>$qty){
        $price = $_POST['price'][$id];
        $total = $qty * $price;

        $wpdb->update(
            $wpdb->prefix.'rsjm_job_items',
            [
                'qty'=>$qty,
                'price'=>$price,
                'total'=>$total
            ],
            ['id'=>intval($id)]
        );

        $subtotal += $total;
    }

    // Recalculate job total (keep existing GST)
    $job = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=$job_id");

    $grand = $subtotal + $job->cgst + $job->sgst + $job->igst;

    $wpdb->update(
        $wpdb->prefix.'rsjm_jobs',
        [
            'subtotal'=>$subtotal,
            'total'=>$grand
        ],
        ['id'=>$job_id]
    );

    wp_redirect(admin_url('admin.php?page=rsjm-view-job&job_id='.$job_id.'&updated=1'));
    exit;
});



register_activation_hook(__FILE__, function(){

    if(!get_option('rsjm_msg_pending')){
        update_option('rsjm_msg_pending',
"Hello {customer_name},

Your job #{job_id} is pending.

View status:
{job_link}
");
    }

    if(!get_option('rsjm_msg_progress')){
        update_option('rsjm_msg_progress',
"Hello {customer_name},

Your job #{job_id} is in progress.

Track here:
{job_link}
");
    }

    if(!get_option('rsjm_msg_ready')){
        update_option('rsjm_msg_ready',
"Hello {customer_name},

Your job #{job_id} is ready.

Amount: ₹{total}

Pay here:
{upi_link}

Details:
{job_link}
");
    }

    if(!get_option('rsjm_msg_completed')){
        update_option('rsjm_msg_completed',
"Hello {customer_name},

Your job #{job_id} is completed.

Total Paid: ₹{total}

Receipt:
{job_link}
");
    }

    if(!get_option('rsjm_msg_partial')){
        update_option('rsjm_msg_partial',
"Hello {customer_name},

Partial payment received.

Paid: ₹{paid}
Pending: ₹{pending}

Details:
{job_link}
");
    }

});


function rsjm_parse_message($template, $job){

    $user = get_user_by('id', $job->customer_id);

    // Customer public link
    $job_link = rsjm_customer_job_link($job->id);

    // UPI Link (if ready/completed)
    $upi = get_option('rsjm_upi');

    $upi_link = '';
    if($upi){
        $upi_link = "upi://pay?pa={$upi}&pn=RepairShop&am={$job->total}&cu=INR";
    }
	
	$fin = rsjm_get_job_financials($job->id);


    $tags = [

        '{job_id}'        => $job->id,

        '{customer_name}' => $user ? $user->display_name : '',

        '{status}'        => ucfirst($job->status),

        '{total}'         => number_format($job->total,2),

        //'{paid}'          => number_format($job->paid_amount ?? 0,2),

        //'{pending}'       => number_format($job->pending_amount ?? 0,2),
		
		'{paid}' => number_format($fin['paid'],2),
		'{pending}' => number_format($fin['pending'],2),

        '{job_link}'      => $job_link,

        '{upi_link}'      => $upi_link,
		
		'{advance}' => number_format($job->advance,2),
    ];

    return str_replace(
        array_keys($tags),
        array_values($tags),
        $template
    );
}



function rsjm_customer_job_link($job_id){
    $token = md5($job_id . NONCE_SALT);
    return site_url("/view-job/?job=$job_id&token=$token");
}



add_shortcode('rsjm_customer_job', function(){

    if (!isset($_GET['job'], $_GET['token'])) {
        return '<p>Invalid job link.</p>';
    }

    global $wpdb;

    $job_id = intval($_GET['job']);
    $token  = sanitize_text_field($_GET['token']);

    if (md5($job_id . NONCE_SALT) !== $token) {
        return '<p>Unauthorized access.</p>';
    }

    $job = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
            $job_id
        )
    );

    if (!$job) return '<p>Job not found.</p>';

    $items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsjm_job_items WHERE job_id=%d",
            $job_id
        )
    );

    $user = get_user_by('id', $job->customer_id);

    $shop_name = get_option('rsjm_shop_name');
    $shop_addr = get_option('rsjm_shop_address');
    $gst_no    = get_option('rsjm_gst_no');
    //$logo_url  = wp_get_attachment_url(get_option('rsjm_shop_logo'));
    $upi_id    = get_option('rsjm_upi');
	
	$logo_id     = get_option('rsjm_shop_logo');
	$logo_url    = $logo_id ? wp_get_attachment_url($logo_id) : '';

    $amount = in_array($job->status, ['ready','completed','partial'])
        ? $job->total
        : $job->subtotal;

    $upi_link = "upi://pay?pa={$upi_id}&pn=" . urlencode($shop_name) .
                "&am={$amount}&cu=INR";

    ob_start();
    ?>
	<style>
		header, footer {
			display: none;
		}
	</style>
    <div style="max-width:600px;margin:auto;font-family:system-ui">

        <div style="text-align:center;margin-bottom:20px">
            <?php 
			if($logo_url): ?>
                <img src="<?=esc_url($logo_url)?>" style="max-height:60px"><br>
            <?php endif; ?>
            <strong><?=esc_html($shop_name)?></strong><br>
            <small><?=nl2br(esc_html($shop_addr))?></small><br>
            <?php if($gst_no): ?>
                <small>GST: <?=esc_html($gst_no)?></small>
            <?php endif; ?>
        </div>

        <div style="background:#fff;padding:15px;border-radius:10px">
            <p><strong>Customer:</strong> <?=esc_html($user->display_name)?></p>
            <p><strong>Status:</strong> <?=ucfirst($job->status)?></p>

            <table width="100%" cellpadding="6">
                <tr><th align="left">Item</th><th>Qty</th><th>Total</th></tr>
                <?php foreach($items as $i): ?>
                <tr>
                    <td><?=esc_html($i->sku)?></td>
                    <td align="center"><?=$i->qty?></td>
                    <td align="right">₹<?=number_format($i->total,2)?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h2 style="text-align:right">
                ₹<?=number_format($amount,2)?>
            </h2>

            <?php if($job->status === 'ready'): ?>
            <a href="<?=esc_url($upi_link)?>"
               style="display:block;background:#16a34a;color:#fff;
                      text-align:center;padding:14px;border-radius:10px;
                      font-size:18px;text-decoration:none">
                Pay ₹<?=number_format($amount,2)?> Now
            </a>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
});

function rsjm_sync_job_payments($job_id){

    global $wpdb;

    $job = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT total FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
            $job_id
        )
    );

    if(!$job) return;

    $paid = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}rsjm_payments WHERE job_id=%d",
            $job_id
        )
    );

    $paid = $paid ? floatval($paid) : 0;

    $pending = $job->total - $paid;
    if($pending < 0) $pending = 0;

    $status = $pending > 0 ? 'partial' : 'completed';

    $wpdb->update(
        $wpdb->prefix.'rsjm_jobs',
        [
            //'paid_amount'    => $paid,
            //'pending_amount' => $pending,
            'status'         => $status
        ],
        ['id'=>$job_id]
    );
}


function rsjm_add_payment($job_id,$amount,$method,$note='',$date=null){

    global $wpdb;

    if($amount <= 0) return false;

    // If no date passed, use today
    if(!$date){
        $date = current_time('mysql');
    } else {
        $date = date('Y-m-d H:i:s', strtotime($date));
    }

    $wpdb->insert($wpdb->prefix.'rsjm_payments',[
        'job_id'=>$job_id,
        'amount'=>$amount,
        'method'=>$method,
        'note'=>$note,
        'created_at'=>$date
    ]);

    rsjm_sync_job_payments($job_id);
	
	rsjm_auto_update_status($job_id);

    return true;
}


function rsjm_get_job_financials($job_id){

    global $wpdb;

    $job = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT total FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
            $job_id
        )
    );

    if(!$job){
        return [
            'total'=>0,
            'paid'=>0,
            'pending'=>0,
            'percent'=>0
        ];
    }

    $total = floatval($job->total);

    $paid = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}rsjm_payments WHERE job_id=%d",
            $job_id
        )
    );

    $paid = $paid ? floatval($paid) : 0;

    $pending = $total - $paid;
    if($pending < 0) $pending = 0;

    $percent = $total > 0 ? ($paid / $total) * 100 : 0;

    return [
        'total'=>$total,
        'paid'=>$paid,
        'pending'=>$pending,
        'percent'=>$percent
    ];
}


function rsjm_auto_update_status($job_id){

    global $wpdb;

    $fin = rsjm_get_job_financials($job_id);

    $status = $fin['pending'] > 0 ? 'partial' : 'completed';

    $wpdb->update(
        $wpdb->prefix.'rsjm_jobs',
        ['status'=>$status],
        ['id'=>$job_id]
    );

    /* ===============================
       EARN POINTS WHEN COMPLETED
    =============================== */

    if($status === 'completed'){

        // Get job data
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT customer_id, total FROM {$wpdb->prefix}rsjm_jobs WHERE id=%d",
                $job_id
            )
        );

        if(!$job) return;

        // Prevent duplicate earning
        $already = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_points 
                 WHERE job_id=%d AND type='earn'",
                 $job_id
            )
        );

        if($already > 0) return;

        $points = floor($job->total / 100); // 1 point per ₹100

        if($points > 0){
            $wpdb->insert($wpdb->prefix.'rsjm_points',[
                'customer_id'=>$job->customer_id,
                'job_id'=>$job_id,
                'points'=>$points,
                'type'=>'earn',
                'note'=>"Earned from Job #$job_id",
                'created_at'=>current_time('mysql')
            ]);
        }
    }
}



function rsjm_get_customer_points($customer_id){

    global $wpdb;

    $earned = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT IFNULL(SUM(points),0)
             FROM {$wpdb->prefix}rsjm_points
             WHERE customer_id=%d AND type='earn'",
             $customer_id
        )
    );

    $redeemed = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT IFNULL(SUM(points),0)
             FROM {$wpdb->prefix}rsjm_points
             WHERE customer_id=%d AND type='redeem'",
             $customer_id
        )
    );

    return intval($earned - $redeemed);
}



/* ===============================
   AJAX: GET CUSTOMER POINTS
=============================== */

add_action('wp_ajax_rsjm_get_points', function(){

    if(!isset($_POST['customer_id'])){
        wp_send_json_error();
    }

    $customer_id = intval($_POST['customer_id']);

    $points = rsjm_get_customer_points($customer_id);

    wp_send_json_success([
        'points' => $points
    ]);
});



add_action('wp_ajax_rsjm_add_customer', function(){

    $fname  = sanitize_text_field($_POST['fname']);
	$lname  = sanitize_text_field($_POST['lname']);
	
	$name  = $fname.' '.$lname;
    $phone = sanitize_text_field($_POST['phone']);
    $email = sanitize_email($_POST['email']);
    $address = sanitize_textarea_field($_POST['address']);

    if(!$phone){
        wp_send_json_error('Phone required');
    }

    // Check existing
    $existing = get_user_by('login', $phone);
    if($existing){
        wp_send_json_success([
            'id' => $existing->ID,
            'text' => $existing->display_name . ' (' . $phone . ')'
        ]);
    }

    $user_id = wp_insert_user([
        'user_login' => $phone,
        'user_pass' => wp_generate_password(),
        'user_email' => $email ?: $phone.'@noemail.com',
		'first_name' => $fname,
		'last_name'  => $lname,
        'display_name' => $name,
        'role' => 'subscriber'
    ]);

    if(is_wp_error($user_id)){
        wp_send_json_error($user_id->get_error_message());
    }

    update_user_meta($user_id, 'address', $address);

    wp_send_json_success([
        'id' => $user_id,
        'text' => $name . ' (' . $phone . ')'
    ]);
});



function rsjm_customers_page(){

    global $wpdb;
	
	if(isset($_GET['export'])){

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="customers.csv"');

		$output = fopen("php://output", "w");

		fputcsv($output, ['Name','Phone','Email','Points']);

		$users = get_users();

		foreach($users as $u){
			fputcsv($output, [
				$u->display_name,
				$u->user_login,
				$u->user_email,
				rsjm_get_customer_points($u->ID)
			]);
		}

		fclose($output);
		exit;
	}
	
	if(isset($_GET['qr'])){

		$id = intval($_GET['qr']);
		$user = get_user_by('id', $id);

		if(!$user) exit;

		$text = "ID: {$user->ID}\nName: {$user->display_name}\nPhone: {$user->user_login}";

		//$qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".urlencode($text);
		$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".$user->ID;
		
		//$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".urlencode($text);
		echo "<div style='text-align:center;margin-top:50px;'>";
		echo "<h3>{$user->display_name}</h3>";
		echo "<img src='$qr_url'>";
		echo "<br><br><a href='#' onclick='window.print()'>🖨 Print</a>";
		echo "</div>";
		exit;
	}

    $paged = max(1, $_GET['paged'] ?? 1);
	$per_page = 20;
	$search = sanitize_text_field($_GET['s'] ?? '');

	$args = [
		'number' => $per_page,
		'paged'  => $paged,
	];

	if($search){
		$args['search'] = "*{$search}*";
		$args['search_columns'] = ['user_login','user_email','display_name'];
	}

	$query = new WP_User_Query($args);
	$users = $query->get_results();
	$total = $query->get_total();
    ?>

    <div class="rsjm-wrap">
        <h2 class="rsjm-title">👥 Customers</h2>
		<a href="?page=rsjm-customers&export=1" class="rsjm-btn rsjm-btn-success">📥 Export CSV</a>
        <div class="rsjm-card">

            <div class="rsjm-table-wrap">
			<form method="get" style="margin-bottom:15px;">
				<input type="hidden" name="page" value="rsjm-customers">
				<input type="text" name="s" placeholder="Search name / phone / email" value="<?= esc_attr($_GET['s'] ?? '') ?>">
				<button class="rsjm-btn">Search</button>
			</form>
                <table class="rsjm-table">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Points</th>
                            <th>Pending</th>
                            <th>Jobs</th>
                            <th>Total Spent</th>
							<th>QR</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach($users as $u):

                        // TOTAL JOBS
                        $jobs = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs WHERE customer_id=%d",
                                $u->ID
                            )
                        );

                        // TOTAL SPENT
                        $spent = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT SUM(total) FROM {$wpdb->prefix}rsjm_jobs WHERE customer_id=%d",
                                $u->ID
                            )
                        );

                        // POINTS
                        $points = rsjm_get_customer_points($u->ID);

                        // OPTIONAL pending points logic
                        $pending_points = 0;

                    ?>

                        <tr>
                            <td><?= esc_html($u->display_name) ?></td>
                            <td><?= esc_html($u->user_login) ?></td>
                            <td><?= intval($points) ?></td>
                            <td><?= intval($pending_points) ?></td>
                            <td><?= intval($jobs) ?></td>
                            <td>₹<?= number_format($spent,2) ?></td>
							<td><a href="?page=rsjm-customers&qr=<?= $u->ID ?>" class="rsjm-btn">📱 QR</a></td>
                            <td>
                                <a href="?page=rsjm-customer-view&id=<?= $u->ID ?>" class="rsjm-btn">
                                    View
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>
				
				<?php 
					$total_pages = ceil($total / $per_page);

					if($total_pages > 1){
						echo '<div style="margin-top:15px;">';

						for($i=1;$i<=$total_pages;$i++){
							$active = ($i == $paged) ? 'style="font-weight:bold"' : '';
							echo "<a $active href='?page=rsjm-customers&paged=$i&s=$search' style='margin-right:10px;'>$i</a>";
						}

						echo '</div>';
					}
				?>
            </div>

        </div>
    </div>

    <?php
}


function rsjm_customer_detail_page(){

    global $wpdb;

    $id = intval($_GET['id']);
    $user = get_user_by('id', $id);

    if(!$user){
        echo "Customer not found";
        return;
    }

    $jobs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsjm_jobs WHERE customer_id=%d ORDER BY id DESC",
            $id
        )
    );

    ?>

    <div class="rsjm-wrap">

        <h2 class="rsjm-title">
            👤 <?= esc_html($user->display_name) ?>
        </h2>

        <div class="rsjm-card">
            <p><strong>Phone:</strong> <?= esc_html($user->user_login) ?></p>
            <p><strong>Email:</strong> <?= esc_html($user->user_email) ?></p>
            <p><strong>Points:</strong> <?= rsjm_get_customer_points($id) ?></p>
        </div>

        <div class="rsjm-card">

            <h3>📦 Job History</h3>

            <div class="rsjm-table-wrap">
                <table class="rsjm-table">

                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if($jobs): foreach($jobs as $j): ?>

                        <tr>
                            <td>#<?= $j->id ?></td>
                            <td><?= ucfirst($j->status) ?></td>
                            <td>₹<?= number_format($j->total,2) ?></td>
                            <td><?= date('d M Y', strtotime($j->created_at)) ?></td>
                        </tr>

                    <?php endforeach; else: ?>

                        <tr><td colspan="4">No jobs found</td></tr>

                    <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </div>

    </div>

    <?php
}