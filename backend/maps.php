<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
if(isset($_SESSION['id_room_point_sel'])) {
    $id_room_point_sel = $_SESSION['id_room_point_sel'];
    unset($_SESSION['id_room_point_sel']);
} else {
    $id_room_point_sel = '';
}

$can_create = get_plan_permission($id_user)['enable_maps'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
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
$show_in_ui_map = $virtual_tour['show_map'];
$show_in_ui_map_tour = $virtual_tour['show_map_tour'];
?>

<?php include("check_plan.php"); ?>

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
    <div class="col-md-12">
        <?php if($create_permission && $create_content) { ?>
        <div class="card mb-2 py-3 border-left-success">
            <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                <div class="row">
                    <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left flex-center">
                        <span><?php echo _("CREATE NEW MAP"); ?></span>
                    </div>
                    <div class="col-md-4 text-center text-sm-center text-md-right text-lg-right">
                        <a href="#" data-toggle="modal" data-target="#modal_new_map" class="btn btn-success btn-circle">
                            <i class="fas fa-plus-circle"></i>
                        </a>
                        <a href="index.php?p=maps_bulk" class="btn btn-success ml-2">
                            <?php echo _("BULK"); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <div id="search_div"></div>
        <div id="maps_list">
            <div class="card mb-4 py-3 border-left-primary">
                <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                    <div class="row">
                        <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left">
                            <?php echo _("LOADING MAPS ..."); ?>
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

<div id="modal_new_map" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("New Map"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label>
                            <input type="text" class="form-control" id="name" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="map_type"><?php echo _("Map Type"); ?></label>
                            <select onchange="change_map_type();" id="map_type" class="form-control">
                                <option id="floorplan"><?php echo _("Floorplan (image)"); ?></option>
                                <option <?php echo (check_map_type($id_virtualtour_sel)) ? 'disabled' : ''; ?> id="map"><?php echo _("Map"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <form id="frm" action="ajax/upload_map_image.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="name"><?php echo _("Map image"); ?></label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                                <label class="custom-file-label text-left" for="txtFile"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Map Image"); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="preview text-center">
                                        <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                            <div class="progress-bar" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                0%
                                            </div>
                                        </div>
                                        <div style="display: none;" id="preview_image">
                                            <img style="width: 100%" src="" />
                                        </div>
                                        <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn_create_map" disabled onclick="add_map();" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_map" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Map"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the entire map <b id='name_map_delete'></b> and all the points?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_map" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
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
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.icon_show_ui_map = `<i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_map>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_map==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_map==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i>`;
        window.icon_show_ui_map_tour = `<i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_map_tour>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_map_tour==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_map_tour==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i>`;
        $(document).ready(function () {
            $('.help_t').tooltip();
            bsCustomFileInput.init();
            get_maps(id_virtualtour);
        });
        $('body').on('submit','#frm',function(e){
            e.preventDefault();
            $('#error').hide();
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
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        view_image(evt.target.responseText);
                    }
                }
                upadte_progressbar(0);
                frm[0].reset();
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
        }

        function view_image(path) {
            $('#preview_image img').attr('src',path);
            $('#preview_image').show();
            $('#btn_create_map').prop("disabled",false);
        }
    })(jQuery); // End of use strict
</script>