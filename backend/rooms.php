<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = check_plan('room', $id_user,$id_virtualtour_sel);
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
$_SESSION['compress_jpg'] = $virtual_tour['compress_jpg'];
$_SESSION['max_width_compress'] = $virtual_tour['max_width_compress'];
$_SESSION['keep_original_panorama'] = $virtual_tour['keep_original_panorama'];
$settings = get_settings();
$enable_ai_room = $settings['enable_ai_room'];
$change_plan = $settings['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
$plan_permissions = get_plan_permission($id_user);
$max_file_size_upload = $plan_permissions['max_file_size_upload'];
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
if($virtual_tour['ar_simulator']) {
    $num_rooms = get_rooms_count($id_virtualtour_sel);
    if($num_rooms>=1) {
        $create_permission = false;
    }
}
$check_ai_generations = false;
$ai_create = true;
$ai_generated = 0;
$n_ai_generate_month = 0;
if($enable_ai_room) {
    $n_ai_generate_month = $plan_permissions['n_ai_generate_month'];
    if($n_ai_generate_month!=-1) {
        $check_ai_generations = true;
        $ai_generated = get_user_ai_generated($id_user);
        $perc_ai_generated = number_format(calculatePercentage($ai_generated,$n_ai_generate_month,0));
        if($ai_generated>=$n_ai_generate_month) {
            $ai_create = false;
        }
    }
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

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create Rooms on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <div class="col-md-12">
        <?php if($create_permission) { ?>
            <?php if($create_content) { ?>
                <?php if($can_create) { ?>
                <div class="card mb-2 py-3 border-left-success">
                    <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                        <div class="row">
                            <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left flex-center">
                                <span><?php echo _("CREATE NEW ROOM"); ?></span>
                            </div>
                            <div class="col-md-4 text-center text-sm-center text-md-right text-lg-right">
                                <a href="#" id="btn_modal_create_room" data-toggle="modal" data-target="#modal_new_room" class="btn btn-success btn-circle">
                                    <i class="fas fa-plus-circle"></i>
                                </a>
                                <a href="index.php?p=rooms_bulk" class="btn btn-success ml-2 <?php echo ($virtual_tour['ar_simulator'])?'hidden_menu':''; ?>">
                                    <?php echo _("BULK"); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } else { ?>
                    <div class="card bg-warning text-white shadow mb-4">
                        <div class="card-body">
                            <?php echo _("You have reached the maximum number of Rooms allowed from your plan!")." ".$msg_change_plan; ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
        <div id="search_div"></div>
        <div id="rooms_list">
            <div class="card mb-4 py-3 border-left-primary">
                <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                    <div class="row">
                        <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left">
                            <?php echo _("LOADING ROOMS ..."); ?>
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

<div id="modal_new_room" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("New Room"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div id="name_div" class="col-md-6">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label>
                            <input type="text" class="form-control" id="name" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type_pano"><?php echo _("Type"); ?></label>
                            <select onchange="change_room_type()" class="form-control <?php echo ($virtual_tour['ar_simulator']) ? 'disabled' : ''; ?>" id="type_pano">
                                <option selected id="image"><?php echo _("Image"); ?></option>
                                <?php if($enable_ai_room) : ?>
                                    <option <?php echo (get_plan_permission($id_user)['enable_ai_room']) ? '' : 'disabled' ; ?> id="ai_room"><?php echo _("A.I. Panorama"); ?></option>
                                <?php endif; ?>
                                <option <?php echo (get_plan_permission($id_user)['enable_panorama_video']) ? '' : 'disabled' ; ?> id="video"><?php echo _("Video 360"); ?></option>
                                <option <?php echo (get_plan_permission($id_user)['enable_panorama_video']) ? '' : 'disabled' ; ?> id="hls"><?php echo _("Video Stream (HLS)"); ?></option>
                                <option <?php echo (get_plan_permission($id_user)['enable_panorama_video']) ? '' : 'disabled' ; ?> id="lottie">Lottie</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:none;" id="hls_div" class="col-md-12">
                        <div class="form-group">
                            <label for="panorama_url"><?php echo _("HLS Video Url"); ?></label>
                            <input type="text" class="form-control" id="panorama_url" />
                        </div>
                    </div>
                    <div style="display:none;" id="ai_room_div" class="col-md-12">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="ai_styles"><?php echo _("Style"); ?></label>
                                    <select class="form-control" id="ai_styles" onchange="set_ai_prompt_max_lenght()" disabled>
                                        <option id="0"><?php echo _("Loading ..."); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="ai_prompt"><?php echo _("Describe the panorama"); ?></label>
                                    <textarea style="resize: none;" id="ai_prompt" class="form-control" rows="3"></textarea>
                                    <span class="pull-right label label-default" id="ai_count_message"></span>
                                </div>
                            </div>
                            <?php if($check_ai_generations) : ?>
                            <div class="col-md-12">
                                <div id="progress_ai_generations" class="progress mb-1 position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                    <div style="width:<?php echo $perc_ai_generated; ?>%" class="progress-bar d-inline-block bg-warning" role="progressbar" aria-valuenow="<?php echo $perc_ai_generated; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("A.I. Panorama generated this month"); ?>:&nbsp;&nbsp;<span id="num_ai_generated"><?php echo $ai_generated; ?></span>&nbsp;<?php echo _("of"); ?>&nbsp;<?php echo $n_ai_generate_month; ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-12">
                                <?php if($ai_create) : ?>
                                <button id="btn_generate_ai_room" onclick="generate_ai_room()" class="btn btn-block btn-primary disabled <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("GENERATE"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-12 mt-3">
                                <input type="hidden" id="ai_image" value="">
                                <div style="display: none;border:1px solid lightgray;" id="preview_image_ai" class="position-relative">
                                    <img draggable="false" style="width: 100%" src="" />
                                    <button onclick="view_preview_panorama_ai();return false;" style="position:absolute;top:5px;right:5px;display:none;" id="btn_preview_panorama_ai" class="btn btn-sm btn-primary"><?php echo _("preview"); ?></button>
                                    <button onclick="fullscreen_preview_panorama_ai();return false;" style="position:absolute;top:5px;left:5px;display:none;z-index:10;" id="btn_full_panorama_ai" class="btn btn-sm btn-primary"><i class="fas fa-expand"></i></button>
                                    <button onclick="close_preview_panorama_ai();return false;" style="position:absolute;top:5px;right:5px;display:none;z-index:10;" id="btn_close_panorama_ai" class="btn btn-sm btn-primary"><i class="fas fa-times"></i></button>
                                    <div style="width: 100%;height: 232px;display: none;" id="preview_panorama_ai"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <form id="frm" action="ajax/upload_room_image.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="room_upload_div" class="form-group">
                                        <label id="label_panorama_type"><?php echo _("Panorama image"); ?></label>&nbsp;&nbsp;&nbsp;<span style="font-size:12px;">(<i><?php echo _("Max allowed file size: "); ?> <?php echo $max_file_size_upload." MB"; ?></i>)</span>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                                <label class="custom-file-label" for="txtFile"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                        <p class="<?php echo ($virtual_tour['ar_simulator']) ? '' : 'd-none'; ?>">
                                            <?php echo _("Upload a 360 degree panoramic image of the environment that you will want to view in Augmented Reality to correctly position the POIs."); ?>
                                        </p>
                                        <p><i id="msg_accept_files"><?php echo _("Accepted only images in JPG/PNG format. For 360 degree in equirectangular 2:1 format."); ?></i></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload"); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="preview text-center">
                                        <div class="progress progress_i mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                            <div class="progress-bar" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                0%
                                            </div>
                                        </div>
                                        <div style="display: none;border:1px solid lightgray;" id="preview_image" class="position-relative">
                                            <img draggable="false" style="width: 100%" src="" />
                                            <button onclick="view_preview_panorama_image();return false;" style="position:absolute;top:5px;right:5px;display:none;" id="btn_preview_panorama_image" class="btn btn-sm btn-primary"><?php echo _("preview"); ?></button>
                                            <button onclick="fullscreen_preview_panorama_image();return false;" style="position:absolute;top:5px;left:5px;display:none;z-index:10;" id="btn_full_panorama_image" class="btn btn-sm btn-primary"><i class="fas fa-expand"></i></button>
                                            <button onclick="close_preview_panorama_image();return false;" style="position:absolute;top:5px;right:5px;display:none;z-index:10;" id="btn_close_panorama_image" class="btn btn-sm btn-primary"><i class="fas fa-times"></i></button>
                                            <div style="width: 100%;height: 232px;display: none;" id="preview_panorama_image"></div>
                                        </div>
                                        <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div style="display:none;" id="lottie_div" class="col-md-12 mt-2">
                        <form id="frm_l" action="ajax/upload_room_json.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Lottie <?php echo _("File"); ?></label>&nbsp;&nbsp;&nbsp;<span style="font-size:12px;">(<i><?php echo _("Max allowed file size: "); ?> <?php echo $max_file_size_upload." MB"; ?></i>)</span>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile_l" name="txtFile_l" />
                                                <label class="custom-file-label" for="txtFile_l"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                        <p><i><?php echo _("Accepted only lottie file in Json format."); ?></i></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_l" value="<?php echo _("Upload"); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="text-center">
                                        <div class="progress progress_l mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                            <div class="progress-bar" id="progressBar_l" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                0%
                                            </div>
                                        </div>
                                        <div id="preview_lottie"></div>
                                        <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_l"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn_create_room" disabled onclick="add_room();" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_room" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Room"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the room <b id='name_room_delete'></b>?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_room" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_duplicate_room" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Duplicate Room"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to duplicate the room?"); ?></p>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="duplicate_target_vt"><?php echo _("Tour"); ?></label><br>
                            <select class="form-control" id="duplicate_target_vt">
                                <option id="0"><?php echo _("On the same tour"); ?></option>
                                <?php
                                $tours_d = get_virtual_tours($id_user,"no");
                                foreach ($tours_d as $tour_d) {
                                    $id_vt_d = $tour_d['id'];
                                    if($id_vt_d!=$id_virtualtour_sel) {
                                        $name_vt_d = $tour_d['name'];
                                        echo "<option id='$id_vt_d'>$name_vt_d</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duplicate_pois"><?php echo _("POIs"); ?></label><br>
                            <input type="checkbox" id="duplicate_pois" checked />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_duplicate_room" onclick="" type="button" class="btn btn-success"><i class="fas fa-copy"></i> <?php echo _("Yes, Duplicate"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_list_alt" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <?php include("rooms_menu_list.php"); ?>
            </div>
            <div class="modal-footer">
                <button onclick="refresh_rooms();" type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
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
        window.can_create = <?php echo $can_create; ?>;
        window.panorama_video = '';
        window.panorama_json = '';
        window.max_file_size_upload = <?php echo $max_file_size_upload; ?>;
        window.ar_simulator = <?php echo ($virtual_tour['ar_simulator']) ? 1 : 0; ?>;
        window.ai_styles_loaded = false;
        window.viewer_preview_room = null;
        window.viewer_video = null;
        window.ai_generated = <?php echo $ai_generated; ?>;
        window.n_ai_generate_month = <?php echo $n_ai_generate_month; ?>;
        var video = document.createElement("video");
        var canvas = document.createElement("canvas");
        var video_preview;
        $(document).ready(function () {
            bsCustomFileInput.init();
            get_rooms(window.id_virtualtour,'list');
        });

        window.refresh_rooms = function () {
            get_rooms(window.id_virtualtour,'list');
        }

        $(document).mousedown(function() {
            try {
                $('#rooms_list .btn').tooltipster('hide');
            } catch (e) {}
        });

        $('#txtFile').bind('change', function() {
            $('#btn_create_room').prop("disabled",true);
            var file_size = this.files[0].size/1024/1024;
            if(file_size>window.max_file_size_upload) {
                show_error(window.backend_labels.file_size_too_big);
                upadte_progressbar(0);
                $('#btnUpload').prop("disabled",true);
            } else {
                $('#error').hide();
                $('#btnUpload').prop("disabled",false);
            }
        });

        $('body').on('submit','#frm',function(e){
            e.preventDefault();
            $('#error').hide();
            $('#modal_new_room .btn').prop("disabled",true);
            $('#preview_image').hide();
            $('#preview_panorama_image').hide();
            $('#btn_close_panorama_image').hide();
            $('#btn_full_panorama_image').hide();
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
                        $('#modal_new_room .btn').prop("disabled",false);
                        var type = $('#type_pano option:selected').attr('id');
                        if(type=='image' || type=='hls' || type=='lottie') {
                            window.panorama_image = evt.target.responseText;
                            view_image(evt.target.responseText);
                        } else {
                            view_video(evt.target.responseText);
                        }
                        if(type=='lottie' && window.panorama_json!='') {
                            load_viewer_preview_room_l('preview_panorama_image',window.panorama_image,window.panorama_json);
                        }
                        if(window.panorama_json=='' && type=='lottie') {
                            $('#btn_create_room').prop("disabled",true);
                        }
                        if(video_preview===null && type=='video') {
                            $('#btn_create_room').prop("disabled",true);
                            $('#preview_image').hide();
                        }
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
                $('.progress_i').hide();
            }else{
                $('.progress_i').show();
            }
        }

        function show_error(error){
            $('.progress_i').hide();
            $('#error').show();
            $('#error').html(error);
            $('#modal_new_room .btn').prop("disabled",false);
            $('#btn_create_room').prop("disabled",true);
        }

        $('body').on('submit','#frm_l',function(e){
            e.preventDefault();
            $('#error_l').hide();
            $('#modal_new_room .btn').prop("disabled",true);
            $('#preview_lottie').html('');
            $('#btn_close_panorama_image').hide();
            $('#btn_full_panorama_image').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            var fileInput = document.getElementById('txtFile_l');
            var filename = fileInput.files[0].name;
            if(frm.find('#txtFile_l[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_l' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_l(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_l(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        $('#modal_new_room .btn').prop("disabled",false);
                        window.panorama_json = evt.target.responseText;
                        $('#preview_lottie').html(filename);
                        $('#btn_preview_panorama_image').show();
                        if(window.panorama_image!='') {
                            load_viewer_preview_room_l('preview_panorama_image',window.panorama_image,window.panorama_json);
                        }
                        if($('#preview_image img').attr('src')=='') {
                            $('#btn_create_room').prop("disabled",true);
                        }
                    }
                }
                upadte_progressbar_l(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_l('upload failed');
                upadte_progressbar_l(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_l('upload aborted');
                upadte_progressbar_l(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_l(value){
            $('#progressBar_l').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_l').hide();
            }else{
                $('.progress_l').show();
            }
        }

        function show_error_l(error){
            $('.progress_l').hide();
            $('#error_l').show();
            $('#error_l').html(error);
            $('#modal_new_room .btn').prop("disabled",false);
            $('#btn_create_room').prop("disabled",true);
        }

        function view_image(path) {
            if(window.wizard_step!=-1) {
                $('#preview_image img')[0].onload = function() {
                    Shepherd.activeTour.next();
                }
            }
            $('#preview_image img').attr('src',path);
            $('#preview_image').show();
            $('#preview_image img').show();
            var type = $('#type_pano option:selected').attr('id');
            switch(type) {
                case 'image':
                    $('#btn_preview_panorama_image').show();
                    load_viewer_preview_room('preview_panorama_image',path);
                    break;
            }
        }

        function view_video(path) {
            window.panorama_video = path;
            $('#preview_image img').attr('src',video_preview);
            $('#preview_image').show();
            $('#btn_preview_panorama_image').show();
            load_viewer_preview_room_v('preview_panorama_image',path);
        }

        video.addEventListener('loadeddata', function() {
            if(video.videoWidth>4096) {
                canvas.width = 4096;
                canvas.height = 2048;
            } else {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
            }
            video.currentTime = 1;
        }, false);

        video.addEventListener('canplaythrough', function () {
            var context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            video_preview = canvas.toDataURL("image/jpeg",0.5);
            $('#preview_image img').attr('src',video_preview);
        });

        video.addEventListener('seeked', function() {
            var context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            video_preview = canvas.toDataURL("image/jpeg",0.5);
            $('#preview_image img').attr('src',video_preview);
        }, false);

        var playSelectedFile = function(event) {
            video_preview = null;
            $('#preview_image').hide();
            var type = $('#type_pano option:selected').attr('id');
            if(type=='video') {
                var file = this.files[0];
                var fileURL = URL.createObjectURL(file);
                video.src = fileURL;
                video.type = file.type;
                video.muted = true;
                video.volume = 0;
                video.load();
            }
        }

        var input = document.getElementById('txtFile');
        input.addEventListener('change', playSelectedFile, false);
    })(jQuery); // End of use strict

    window.addEventListener("beforeunload", function (e) {
        sessionStorage.setItem('scrollpos_room', document.getElementById("content-wrapper").scrollTop);
    });
</script>