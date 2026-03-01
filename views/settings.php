<?php
if (!defined('ABSPATH')) exit;

/* SAVE SETTINGS */
if (isset($_POST['rsjm_save_settings'])) {

    if (
        !isset($_POST['rsjm_nonce']) ||
        !wp_verify_nonce($_POST['rsjm_nonce'], 'rsjm_save_settings')
    ) {
        wp_die('Security check failed');
    }

    update_option('rsjm_shop_name', sanitize_text_field($_POST['shop_name']));
    update_option('rsjm_shop_address', sanitize_textarea_field($_POST['shop_address']));
    update_option('rsjm_gst_no', sanitize_text_field($_POST['gst_no']));

    update_option('rsjm_upi', sanitize_text_field($_POST['upi']));

    update_option('rsjm_waha_url', esc_url_raw($_POST['waha_url']));
    update_option('rsjm_waha_session', sanitize_text_field($_POST['waha_session']));

    update_option('rsjm_shop_logo', intval($_POST['shop_logo']));
	
	update_option('rsjm_msg_pending', sanitize_textarea_field($_POST['msg_pending']));
	update_option('rsjm_msg_progress', sanitize_textarea_field($_POST['msg_progress']));
	update_option('rsjm_msg_ready', sanitize_textarea_field($_POST['msg_ready']));
	update_option('rsjm_msg_completed', sanitize_textarea_field($_POST['msg_completed']));
	update_option('rsjm_msg_partial', sanitize_textarea_field($_POST['msg_partial']));


    echo '<div class="updated notice"><p>Settings Saved.</p></div>';
}


/* LOAD VALUES */
$shop_name   = get_option('rsjm_shop_name');
$address     = get_option('rsjm_shop_address');
$gst_no      = get_option('rsjm_gst_no');
$upi         = get_option('rsjm_upi');
$waha_url    = get_option('rsjm_waha_url');
$waha_sess   = get_option('rsjm_waha_session');
$logo_id     = get_option('rsjm_shop_logo');
$logo_url    = $logo_id ? wp_get_attachment_url($logo_id) : '';
?>


<div class="rsjm-wrap">

<div class="rsjm-card">

<h2 class="rsjm-title">⚙ Repair Shop Settings</h2>


<form method="post">

<?php wp_nonce_field('rsjm_save_settings','rsjm_nonce'); ?>


<!-- BRANDING -->
<div class="rsjm-card">

<h3 class="rsjm-title">🏪 Shop Branding</h3>

<div class="rsjm-grid">

    <div class="rsjm-field rsjm-full">
        <label>Shop Name</label>
        <input name="shop_name"
               value="<?php echo esc_attr($shop_name); ?>">
    </div>

    <div class="rsjm-field rsjm-full">
        <label>Shop Address</label>
        <textarea name="shop_address" rows="3"><?php
            echo esc_textarea($address);
        ?></textarea>
    </div>

    <div class="rsjm-field">
        <label>Shop Logo</label>

        <input type="hidden"
               name="shop_logo"
               id="rsjm-logo-id"
               value="<?php echo esc_attr($logo_id); ?>">

        <button type="button"
                class="rsjm-btn rsjm-btn-light"
                id="rsjm-upload-logo">
            Upload Logo
        </button>

        <div style="margin-top:10px">

        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>"
                 id="rsjm-logo-preview"
                 style="max-height:80px">
        <?php else: ?>
            <img id="rsjm-logo-preview"
                 style="display:none;max-height:80px">
        <?php endif; ?>

        </div>
    </div>

</div>
</div>


<!-- TAX -->
<div class="rsjm-card">

<h3 class="rsjm-title">🧾 Tax Information</h3>

<div class="rsjm-grid">

    <div class="rsjm-field">
        <label>GST Number</label>
        <input name="gst_no"
               value="<?php echo esc_attr($gst_no); ?>">
    </div>

</div>

</div>


<!-- PAYMENT -->
<div class="rsjm-card">

<h3 class="rsjm-title">💳 Payment Settings</h3>

<div class="rsjm-grid">

    <div class="rsjm-field">
        <label>UPI ID</label>
        <input name="upi"
               value="<?php echo esc_attr($upi); ?>">
    </div>

</div>

</div>


<!-- WHATSAPP -->
<div class="rsjm-card">

<h3 class="rsjm-title">📲 WhatsApp (WAHA)</h3>

<div class="rsjm-grid">

    <div class="rsjm-field rsjm-full">
        <label>WAHA API URL</label>
        <input name="waha_url"
               value="<?php echo esc_attr($waha_url); ?>">
    </div>

    <div class="rsjm-field">
        <label>WAHA Session</label>
        <input name="waha_session"
               value="<?php echo esc_attr($waha_sess); ?>">
    </div>

</div>

</div>

<!-- WHATSAPP TEMPLATES -->
<div class="rsjm-card">

<h3 class="rsjm-title">💬 WhatsApp Message Templates</h3>

<p>You can use these tags:</p>

<code>
{job_id}, {customer_name}, {status}, {total}, {paid}, {pending}, {payment_link}
</code>

<br><br>

<div class="rsjm-field">
<label>Pending Message</label>
<textarea name="msg_pending" rows="3"><?php
echo esc_textarea(get_option('rsjm_msg_pending'));
?></textarea>
</div>

<div class="rsjm-field">
<label>In Progress Message</label>
<textarea name="msg_progress" rows="3"><?php
echo esc_textarea(get_option('rsjm_msg_progress'));
?></textarea>
</div>

<div class="rsjm-field">
<label>Ready to Deliver Message</label>
<textarea name="msg_ready" rows="3"><?php
echo esc_textarea(get_option('rsjm_msg_ready'));
?></textarea>
</div>

<div class="rsjm-field">
<label>Completed Message</label>
<textarea name="msg_completed" rows="3"><?php
echo esc_textarea(get_option('rsjm_msg_completed'));
?></textarea>
</div>

<div class="rsjm-field">
<label>Partial Paid Message</label>
<textarea name="msg_partial" rows="3"><?php
echo esc_textarea(get_option('rsjm_msg_partial'));
?></textarea>
</div>

</div>



<button class="rsjm-btn rsjm-btn-success"
        style="width:100%;font-size:16px">

💾 Save Settings

</button>


<input type="hidden"
       name="rsjm_save_settings"
       value="1">

</form>

</div>
</div>


<!-- MEDIA UPLOADER -->
<script>
jQuery(document).ready(function($){	

    let frame;

    $('#rsjm-upload-logo').on('click', function(e){

        e.preventDefault();

        if(frame){
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Shop Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });

        frame.on('select', function(){

            let att = frame.state()
                          .get('selection')
                          .first()
                          .toJSON();

            $('#rsjm-logo-id').val(att.id);

            $('#rsjm-logo-preview')
                .attr('src', att.url)
                .show();
        });

        frame.open();
    });

});
</script>
