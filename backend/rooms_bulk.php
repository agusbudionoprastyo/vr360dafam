<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$count_rooms_create = check_plan_rooms_count($id_user,$id_virtualtour_sel);
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $_SESSION['compress_jpg'] = $virtual_tour['compress_jpg'];
    $_SESSION['max_width_compress'] = $virtual_tour['max_width_compress'];
    $_SESSION['keep_original_panorama'] = $virtual_tour['keep_original_panorama'];
    $change_plan = get_settings()['change_plan'];
    if($change_plan) {
        $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
    } else {
        $msg_change_plan = "";
    }
    $max_file_size_upload = get_plan_permission($id_user)['max_file_size_upload'];
    $max_file_size_upload_system = _GetMaxAllowedUploadSize();
    if($max_file_size_upload<=0 || $max_file_size_upload>$max_file_size_upload_system) {
        $max_file_size_upload = $max_file_size_upload_system;
    }
    if($user_info['role']=="editor") {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['create_rooms']==1) {
            $create_permission = true;
        } else {
            $create_permission = false;
        }
    } else {
        $create_permission = true;
    }
} else {
    $create_permission = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$create_permission): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create Rooms on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-12">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo _("Bulk room's create"); ?></h6>
            </div>
            <div class="card-body">
                <p><?php echo _("Uploading multiple 360-degree panorama JPG/PNG images will automatically create all the rooms."); ?> <i><?php echo _("Max allowed upload file size: "); ?> <?php echo $max_file_size_upload." MB"; ?></i></p>
            </div>
        </div>
    </div>
</div>

<?php if(($user_info['plan_status']=='active') || ($user_info['plan_status']=='expiring')) { ?>
    <?php if($count_rooms_create>=0) : ?>
        <div class="card bg-warning text-white shadow mb-3">
            <div class="card-body">
                <?php echo sprintf(_('You have %s remaining uploads!'),$count_rooms_create); ?>
            </div>
        </div>
    <?php endif; ?>
<?php } else { $count_rooms_create=0; } ?>

<?php if($create_content) : ?>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="ajax/upload_room_image.php" class="dropzone noselect <?php echo ($demo || $disabled_upload) ? 'disabled' : ''; ?>" id="rooms-dropzone"></form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="display: none;" id="loading_create_rooms" class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <h3><i class="fa fa-spin fa-circle-notch"></i> <?php echo _("Creating Rooms"); ?> <b id="rooms_created">0</b> / <b id="rooms_to_upload">0</b>. <?php echo _("Do not close this window!"); ?></h3>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.rooms_to_upload = 0;
        window.rooms_created = 0;
        window.uploading_panorama = false;
        Dropzone.autoDiscover = false;
        $(document).ready(function () {
            if($('#rooms-dropzone').length) {
                var rooms_dropzone = new Dropzone("#rooms-dropzone", {
                    url: "ajax/upload_room_image.php",
                    parallelUploads: 1,
                    maxFilesize: <?php echo $max_file_size_upload; ?>,
                    timeout: 990000,
                    dictDefaultMessage: "<?php echo _("Drop files or click here to upload"); ?>",
                    dictFallbackMessage: "<?php echo _("Your browser does not support drag'n'drop file uploads."); ?>",
                    dictFallbackText: "<?php echo _("Please use the fallback form below to upload your files like in the olden days."); ?>",
                    dictFileTooBig: "<?php echo sprintf(_("File is too big (%sMiB). Max filesize: %sMiB."),'{{filesize}}','{{maxFilesize}}'); ?>",
                    dictInvalidFileType: "<?php echo _("You can't upload files of this type."); ?>",
                    dictResponseError: "<?php echo sprintf(_("Server responded with %s code."),'{{statusCode}}'); ?>",
                    dictCancelUpload: "<?php echo _("Cancel upload"); ?>",
                    dictCancelUploadConfirmation: "<?php echo _("Are you sure you want to cancel this upload?"); ?>",
                    dictRemoveFile: "<?php echo _("Remove file"); ?>",
                    dictMaxFilesExceeded: "<?php echo _("You can not upload any more files."); ?>",
                    acceptedFiles: 'image/jpeg,image/png',
                    <?php if($count_rooms_create>=0) : ?>
                    maxFiles: <?php echo $count_rooms_create; ?>,
                    <?php endif; ?>
                });
                rooms_dropzone.on("addedfile", function(file) {
                    window.rooms_to_upload++;
                });
                rooms_dropzone.on("sending", function(file) {
                    window.uploading_panorama = true;
                    $('#loading_create_rooms').show();
                    $('#rooms_to_upload').html(window.rooms_to_upload);
                });
                rooms_dropzone.on("success", function(file,rsp) {
                    var file_name = file.name.split('.').slice(0, -1).join('.');
                    add_bulk_room(id_virtualtour,rsp,file_name);
                });
                rooms_dropzone.on("queuecomplete", function() {
                    setInterval(function () {
                        if(window.rooms_created==window.rooms_to_upload) {
                            window.uploading_panorama = false;
                            location.href='index.php?p=rooms';
                        }
                    },1000);
                });
            }
        });

        $(window).on('beforeunload', function(){
            if(window.uploading_panorama) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict
</script>