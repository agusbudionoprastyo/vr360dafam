<?php
if(!isset($settings)) {
    $settings = get_settings();
}
if(!isset($user_info)) {
    $user_info = get_user_info($_SESSION['id_user']);
}
$max_storage_space = $user_info['max_storage_space'];
$storage_space = $user_info['storage_space'];
if($max_storage_space>=1000) {
    $max_storage_space_f = ($max_storage_space/1000)." GB";
} else {
    $max_storage_space_f = $max_storage_space." MB";
}
$disabled_upload=false;
?>
<?php if($storage_space>=$max_storage_space && $max_storage_space!=-1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php $disabled_upload=true; echo sprintf(_('You have reached your quota limit of %s! Please update your plan or delete some contents.'),$max_storage_space_f); ?>
        </div>
    </div>
<?php endif; ?>