<?php
if(!isset($settings)) {
    $settings = get_settings();
}
if(!isset($user_info)) {
    $user_info = get_user_info($_SESSION['id_user']);
}
$change_plan = $settings['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
$upload_content = true;
$create_content = true;
if($user_info['plan_status']=='expired') : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan has expired! You will not be able to add new content, but only edit existing ones.'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php
    $upload_content = false;
    $create_content = false;
    endif;
?>