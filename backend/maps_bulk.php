<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = get_plan_permission($id_user)['enable_maps'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    if($user_info['role']=="editor") {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['create_maps']==1) {
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
            <?php echo _("You cannot create Maps on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Maps!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-12">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo _("Bulk map's create"); ?></h6>
            </div>
            <div class="card-body">
                <p><?php echo _("Uploading multiple JPG/PNG images will automatically create all the maps."); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if($create_content) : ?>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="ajax/upload_map_image.php" class="dropzone noselect <?php echo ($demo || $disabled_upload) ? 'disabled' : ''; ?>" id="maps-dropzone"></form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="display: none;" id="loading_create_maps" class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <h3><i class="fa fa-spin fa-circle-notch"></i> <?php echo _("Creating Maps"); ?> <b id="maps_created">0</b> / <b id="maps_to_upload">0</b>. <?php echo _("Do not close this window!"); ?></h3>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.maps_to_upload = 0;
        window.maps_created = 0;
        window.uploading_map = false;
        Dropzone.autoDiscover = false;
        $(document).ready(function () {
            if($('#maps-dropzone').length) {
                var maps_dropzone = new Dropzone("#maps-dropzone", {
                    url: "ajax/upload_map_image.php",
                    parallelUploads: 1,
                    maxFilesize: 20,
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
                    acceptedFiles: 'image/jpeg,image/png'
                });
                maps_dropzone.on("sending", function(file) {
                    window.uploading_map = true;
                    $('#loading_create_maps').show();
                    window.maps_to_upload++;
                    $('#maps_to_upload').html(window.maps_to_upload);
                });
                maps_dropzone.on("success", function(file,rsp) {
                    var file_name = file.name.split('.').slice(0, -1).join('.');
                    add_bulk_map(id_virtualtour,rsp,file_name);
                });
                maps_dropzone.on("queuecomplete", function() {
                    setInterval(function () {
                        if(window.maps_created==window.maps_to_upload) {
                            window.uploading_map = false;
                            location.href='index.php?p=maps';
                        }
                    },1000);
                });
            }
        });

        $(window).on('beforeunload', function(){
            if(window.uploading_map) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict
</script>