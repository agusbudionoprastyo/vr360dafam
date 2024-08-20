<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = get_plan_permission($id_user)['enable_music_library'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $max_file_size_upload = _GetMaxAllowedUploadSize();
    $music_library = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['music_library']==0) {
            $music_library = false;
        }
    }
    if(isset($_SESSION['library_type'])) {
        $library_type = $_SESSION['library_type'];
    } else {
        $library_type = 'tour';
    }
} else {
    $music_library = false;
}
$s3_enabled = false;
$s3_url = "";
if($library_type=='tour') {
    $s3_params = check_s3_tour_enabled($id_virtualtour_sel);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$music_library): ?>
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
            <?php echo _("You cannot use Music Library on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Music Library!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if($user_info['role']=='administrator') : ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <button onclick="session_library('tour');" class="btn btn-block <?php echo ($library_type=='tour') ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo _("Tour Library"); ?></button>
        </div>
        <div class="col-md-6">
            <button onclick="session_library('public');" class="btn btn-block <?php echo ($library_type=='public') ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo _("Public Library"); ?></button>
        </div>
    </div>
<?php endif; ?>

<?php if($library_type=='public') : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("The contents of this library will be shared for all the virtual tours."); ?>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-grip-horizontal"></i> <?php echo _("Files List"); ?></h6>
            </div>
            <div class="card-body">
                <?php if($create_content) : ?><form action="ajax/upload_music_library_file.php" class="dropzone mb-3 noselect <?php echo ($demo || $disabled_upload) ? 'disabled' : ''; ?>" id="music-dropzone"></form><?php endif; ?>
                <div id="list_files">
                    <p><?php echo _("Loading files ..."); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo ($library_type=='tour') ? $id_virtualtour_sel : ''; ?>';
        window.music_library_files = [];
        Dropzone.autoDiscover = false;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            get_music_library_files(id_virtualtour);
            var music_files_dropzone = new Dropzone("#music-dropzone", {
                url: "ajax/upload_music_library_file.php",
                parallelUploads: 1,
                maxFilesize: <?php echo $max_file_size_upload; ?>,
                timeout: 120000,
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
                acceptedFiles: 'audio/mpeg'
            });
            music_files_dropzone.on("addedfile", function(file) {
                $('#list_files').addClass('disabled');
            });
            music_files_dropzone.on("success", function(file,rsp) {
                add_file_to_music_library(id_virtualtour,rsp);
            });
            music_files_dropzone.on("queuecomplete", function() {
                $('#list_files').removeClass('disabled');
                music_files_dropzone.removeAllFiles();
            });
        });
    })(jQuery); // End of use strict
</script>