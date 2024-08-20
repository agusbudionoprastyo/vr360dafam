<?php
session_start();
$id_user = $_SESSION['id_user'];
$can_create = check_plan('virtual_tour',$id_user,$_SESSION['id_virtualtour_sel']);
$settings = get_settings();
$change_plan = $settings['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
$enable_external_vt = $settings['enable_external_vt'];
$enable_ar_vt = $settings['enable_ar_vt'];
$enable_sample = $settings['enable_sample'];
$hide_type = "";
$hide_sample = "";
$col_name = 3;
$col_author = 2;
$col_external = 2;
$col_sample = 1;
$col_button = 4;
$col_name_md = 3;
$col_author_md = 3;
$col_external_md = 3;
$col_sample_md = 3;
if($enable_external_vt==0 && $enable_ar_vt==0 && $enable_sample==0) {
    $hide_type = "d-none";
    $hide_sample = "d-none";
    $col_name = 4;
    $col_author = 4;
    $col_button = 4;
    $col_name_md = 6;
    $col_author_md = 6;
} else if($enable_sample==0 && ($enable_external_vt==1 || $enable_ar_vt==1)) {
    $hide_sample = "d-none";
    $col_name = 3;
    $col_author = 3;
    $col_external = 2;
    $col_button = 4;
    $col_name_md = 4;
    $col_author_md = 4;
    $col_external_md = 4;
} else if($enable_external_vt==0 && $enable_ar_vt==0 && $enable_sample==1) {
    $hide_type = "d-none";
    $col_name = 3;
    $col_author = 3;
    $col_sample = 2;
    $col_button = 4;
    $col_name_md = 4;
    $col_author_md = 4;
    $col_sample_md = 4;
}
if(isset($_GET['cat'])) {
    $id_cat_sel = $_GET['cat'];
} else {
    $id_cat_sel = 0;
}
if(isset($_GET['user'])) {
    $id_user_sel = $_GET['user'];
} else {
    $id_user_sel = 0;
}
$plan_permissions = get_plan_permission($_SESSION['id_user']);
$user_role = get_user_role($_SESSION['id_user']);
$max_file_size_upload = _GetMaxAllowedUploadSize();
$remote_storage_provider = "";
switch($settings['aws_s3_type']) {
    case 'aws':
        $remote_storage_provider = "AWS S3";
        break;
    case 'r2':
        $remote_storage_provider = "Cloudflare R2";
        break;
    case 'digitalocean':
        $remote_storage_provider = "Digital Ocean Space";
        break;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$create_content) : ?>
<style>
    .btn_duplicate {
        display: none !important;
    }
</style>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <?php if($user_info['role']!='editor') { ?>
            <?php if($create_content) { ?>
                <?php if($can_create) { ?>
                    <div id="add_vt_form" class="card mb-2 py-3 border-left-success">
                        <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                            <form autocomplete="off">
                                <div id="modal_new_virtualtour" class="row align-items-end">
                                    <div class="col-md-<?php echo $col_name_md; ?> col-lg-<?php echo $col_name; ?>">
                                        <div id="name_div" class="form-group mb-0">
                                            <label class="mb-0" for="name"><?php echo _("Name"); ?></label>
                                            <input type="text" class="form-control" id="name" />
                                        </div>
                                    </div>
                                    <div class="col-md-<?php echo $col_author_md; ?> col-lg-<?php echo $col_author; ?>">
                                        <div class="form-group mb-0">
                                            <label class="mb-0" for="name"><?php echo _("Author"); ?></label>
                                            <input type="text" class="form-control" id="author" value="<?php echo $user_info['username']; ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-<?php echo $col_external_md; ?> col-lg-<?php echo $col_external; ?> <?php echo $hide_type; ?>">
                                        <div class="form-group mb-0">
                                            <label class="mb-0" for="vt_type"><?php echo _("Type"); ?></label>
                                            <select onchange="change_vt_type();" id="vt_type" class="form-control">
                                                <option selected id="0"><?php echo _("Default"); ?></option>
                                                <?php if($enable_external_vt) : ?><option id="1"><?php echo _("External"); ?></option><?php endif; ?>
                                                <?php if($enable_ar_vt) : ?><option id="2"><?php echo _("Augmented Reality"); ?></option><?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-<?php echo $col_sample_md; ?> col-lg-<?php echo $col_sample; ?> <?php echo $hide_sample; ?>">
                                        <div style="margin-bottom: 12px;" class="form-group">
                                            <label class="mb-0" for="sample_data"><?php echo _("Sample"); ?> <i title="<?php echo _("includes sample data in this tour"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                            <input type="checkbox" id="sample_data" />
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-lg-<?php echo $col_button; ?> mt-3 mt-lg-0 text-lg-right text-center">
                                        <div class="form-group mb-0">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_create_tour" onclick="add_virtualtour(false);" type="button"  class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_create_edit_tour" onclick="add_virtualtour(true);" type="button"  class="btn btn-warning"><i class="fas fa-plus"></i> <?php echo _("Edit"); ?></button>
                                            <?php if($user_role=='administrator') : ?>
                                                <button data-toggle="modal" data-target="#modal_import_tour" type="button" class="btn btn-primary"><i class="fas fa-file-import"></i> <?php echo _("Import"); ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="card bg-warning text-white shadow mb-4">
                        <div class="card-body">
                            <?php echo _("You have reached the maximum number of Virtual Tours allowed from your plan!")." ".$msg_change_plan; ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
        <div id="virtual_tours_list">
            <div class="card mb-4 py-3 border-left-primary">
                <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                    <div class="row">
                        <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left">
                            <?php echo _("LOADING VIRTUAL TOURS ..."); ?>
                        </div>
                        <div class="col-md-4 text-center text-sm-center text-md-right text-lg-right">
                            <a href="#" class="btn btn-primary btn-circle">
                                <i class="fas fa-spin fa-spinner"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_virtualtour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Virtual Tour"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the entire virtual tour <b id='name_vt_delete'></b>, included rooms, markers, pois and map?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_virtualtour" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_duplicate_virtualtour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Duplicate Virtual Tour"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to duplicate the entire virtual tour <b id='name_vt_duplicate'></b>?"); ?></p>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_info_box"><?php echo _("Info Box"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_info_box" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_maps"><?php echo _("Maps"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_maps" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_products"><?php echo _("Products"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_products" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_gallery"><?php echo _("Gallery"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_gallery" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_rooms"><?php echo _("Rooms"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_rooms" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_presentation"><?php echo _("Presentation"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_presentation" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_markers"><?php echo _("Markers"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_markers" checked />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_pois"><?php echo _("POIs"); ?></label><br>
                            <input onchange="change_duplicate_items_vt();" type="checkbox" id="duplicate_pois" checked />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_duplicate_virtualtour" onclick="" type="button" class="btn btn-success"><i class="fas fa-copy"></i> <?php echo _("Yes, Duplicate"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_export_virtualtour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Download Virtual Tour"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to download the virtual tour <b id='name_vt_download'></b>?"); ?></p>
                <span class="alert-danger <?php echo (($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) ? '':'d-none'; ?>"><?php echo _("You cannot download virtual tour from this demo server"); ?></span>
            </div>
            <div class="modal-footer">
                <button <?php echo (($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) ? 'disabled':''; ?> id="btn_export_virtualtour" onclick="" type="button" class="btn btn-success"><i class="fas fa-download"></i> <?php echo _("Standalone"); ?></button>
                <button <?php echo (($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) ? 'disabled':''; ?> id="btn_export_virtualtour_vr" onclick="" type="button" class="btn btn-success"><i class="fas fa-download"></i> <?php echo _("Standalone (VR)"); ?></button>
                <?php if($user_role=='administrator') : ?>
                    <button <?php echo (($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) ? 'disabled':''; ?> id="btn_export_virtualtour_b" onclick="" type="button" class="btn btn-primary"><i class="fas fa-download"></i> <?php echo _("Importable"); ?></button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_sample_tour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("New Virtual Tour"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to create a tour containing sample data?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_create_sample_tour" onclick="create_sample_tour();" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Yes, Create"); ?></button>
                <button type="button" class="btn btn-secondary" onclick="close_virtualtour_sample();"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_import_tour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Import Virtual Tour"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <form id="frm" action="ajax/upload_import_zip.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><?php echo _("Zip File"); ?></label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                            <label class="custom-file-label" for="txtFile"><?php echo _("Choose file"); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload"); ?>" />
                                </div>
                                <i><?php echo _("Max allowed upload file size: "); ?> <?php echo $max_file_size_upload." MB"; ?></i>
                            </div>
                            <div class="col-md-12">
                                <div class="preview text-center">
                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                        <div class="progress-bar" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                            0%
                                        </div>
                                    </div>
                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo (($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) ? 'disabled':''; ?> id="btn_import_tour" onclick="" type="button" class="btn btn-success disabled"><i class="fas fa-file-import"></i> <?php echo _("Import"); ?></button>
                <button type="button" class="btn btn-secondary" onclick="close_virtualtour_import();"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_move_to_s3" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Move Virtual Tour"); ?>&nbsp;&nbsp;&nbsp;<i class="far fa-folder"></i> <i class="fas fa-long-arrow-alt-right"></i> <i class="fas fa-cloud"></i></h5>
            </div>
            <div class="modal-body">
                <p><?php echo sprintf(_("Are you sure you want to move the virtual tour <b id='name_vt_move_to_s3'></b> from Local Storage to %s?"),$remote_storage_provider); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_move_to_s3" onclick="" type="button" class="btn btn-success"><i class="fas fa-arrow-right"></i> <?php echo _("Yes, Move"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_move_to_local" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Move Virtual Tour"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-cloud"></i> <i class="fas fa-long-arrow-alt-right"></i> <i class="far fa-folder"></i></h5>
            </div>
            <div class="modal-body">
                <p><?php echo sprintf(_("Are you sure you want to move the virtual tour <b id='name_vt_move_to_local'></b> from %s to Local Storage?"),$remote_storage_provider); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_move_to_local" onclick="" type="button" class="btn btn-success"><i class="fas fa-arrow-right"></i> <?php echo _("Yes, Move"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.user_role = '<?php echo $user_info['role']; ?>';
        window.can_create = <?php echo $can_create; ?>;
        window.id_cat_sel = <?php echo $id_cat_sel; ?>;
        window.id_user_sel = <?php echo $id_user_sel; ?>;
        window.enable_export_vt = <?php echo $plan_permissions['enable_export_vt']; ?>;
        window.create_and_edit = false;
        $(document).ready(function () {
            bsCustomFileInput.init();
            $('.help_t').tooltip();
            get_virtual_tours(window.id_cat_sel,window.id_user_sel);
        });

        $('#modal_import_tour').on('hidden.bs.modal', function () {
            $('#error').hide();
            $('#modal_import_tour .btn').removeClass('disabled');
            $('#btn_import_tour').addClass('disabled');
            $('#btnUpload').removeClass('disabled');
            $('#btn_import_tour').attr('onclick','');
            $('#frm')[0].reset();
        });

        $('body').on('submit','#frm',function(e){
            e.preventDefault();
            $('#error').hide();
            $('#modal_import_tour .btn').addClass('disabled');
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                $('#modal_import_tour .modal-footer .btn').removeClass('disabled');
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error(evt.target.responseText);
                    $('#modal_import_tour .btn').removeClass('disabled');
                    $('#btn_import_tour').addClass('disabled');
                } else {
                    if(evt.target.responseText!='') {
                        $('#btn_import_tour').attr('onclick','import_tour("'+evt.target.responseText+'");');
                    }
                }
                upadte_progressbar(0);
            },false);
            ajax.addEventListener('error',function(evt){
                show_error('upload failed');
                upadte_progressbar(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error('upload aborted');
                upadte_progressbar(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar(value){
            $('#progressBar').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error(error){
            $('.progress').hide();
            $('#error').show();
            $('#error').html(error);
            $('#modal_new_room .btn').prop("disabled",false);
            $('#btn_create_room').prop("disabled",true);
        }
    })(jQuery); // End of use strict

    window.addEventListener("beforeunload", function (e) {
        sessionStorage.setItem('scrollpos_vt', document.getElementById("content-wrapper").scrollTop);
    });
</script>