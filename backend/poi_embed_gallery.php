<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once(__DIR__."/functions.php");
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$id_poi = $_GET['id_poi'];
$upload_content = true;
if($user_info['plan_status']=='expired') {
    $upload_content = false;
}
$demo = $_SESSION['demo'];
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-grip-horizontal"></i> <?php echo _("Images List"); ?> <i style="font-size:12px">(<?php echo _("drag images to change order"); ?>)</i></h6>
            </div>
            <div class="card-body">
                <?php if($upload_content) : ?><form action="ajax/upload_gallery_image.php" class="dropzone mb-3 noselect" id="gallery-dropzone"></form><?php endif; ?>
                <div id="list_images" class="noselect">
                    <p><?php echo _("Loading images ..."); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_poi = '<?php echo $id_poi; ?>';
        window.gallery_images = [];
        Dropzone.autoDiscover = false;
        $(document).ready(function () {
            get_poi_embed_gallery_images(id_poi);
            if($('#gallery-dropzone').length) {
                var gallery_dropzone = new Dropzone("#gallery-dropzone", {
                    url: "ajax/upload_gallery_image.php",
                    parallelUploads: 1,
                    maxFilesize: 20,
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
                    acceptedFiles: 'image/*'
                });
                gallery_dropzone.on("addedfile", function(file) {
                    $('#list_images').addClass('disabled');
                });
                gallery_dropzone.on("success", function(file,rsp) {
                    add_image_to_poi_embed_gallery(id_poi,rsp);
                });
                gallery_dropzone.on("queuecomplete", function() {
                    $('#list_images').removeClass('disabled');
                    gallery_dropzone.removeAllFiles();
                });
            }
        });
    })(jQuery); // End of use strict
</script>