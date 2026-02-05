<?php
if(isset($_POST['save'])){
 update_option('rsjm_waha_url',$_POST['waha_url']);
 update_option('rsjm_waha_session',$_POST['waha_session']);
 update_option('rsjm_upi',$_POST['upi']);
}
?>

<form method="post">
<input name="waha_url" placeholder="WAHA URL">
<input name="waha_session" placeholder="Session">
<input name="upi" placeholder="UPI ID">
<button name="save">Save</button>
</form>
