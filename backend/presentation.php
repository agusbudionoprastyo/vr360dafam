<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$_SESSION['id_user']);
if($virtual_tour!==false) {
    $tmp_languages = get_languages_vt();
    $array_languages = $tmp_languages[0];
    $default_language = $tmp_languages[1];
    $can_create = get_plan_permission($id_user)['create_presentation'];
    $presentation = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['presentation']==0) {
            $presentation = false;
        }
    }
    if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
    $link = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","viewer/index.php?code=",$_SERVER['SCRIPT_NAME']).$virtual_tour['code'];
    $settings = get_settings();
    $s3_params = check_s3_tour_enabled($id_virtualtour_sel);
    $s3_enabled = false;
    $s3_url = "";
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
} else {
    $presentation = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$presentation): ?>
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
            <?php echo _("You cannot create Presentations on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if($virtual_tour['ar_simulator']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create Markers on an augmented reality tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Presentations!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body py-3">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <div class="form-group mb-1">
                            <label><?php echo _("Type"); ?></label>
                            <select onchange="change_presentation_type();" id="presentation_type" class="form-control">
                                <option <?php echo ($virtual_tour['presentation_type']=='manual') ? 'selected' : ''; ?> id="manual"><?php echo _("Manual Presentation"); ?></option>
                                <option <?php echo ($virtual_tour['presentation_type']=='automatic') ? 'selected' : ''; ?> id="automatic"><?php echo _("Automatic Presentation"); ?></option>
                                <option <?php echo ($virtual_tour['presentation_type']=='video') ? 'selected' : ''; ?> id="video"><?php echo _("Video Presentation"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1">
                            <label for="presentation_inactivity"><?php echo _("Auto Start on Inactivity"); ?> <i title="<?php echo _("time in seconds of inactivity to wait before automatically starting the presentation (0 to disable)"); ?>" class="help_t fas fa-question-circle"></i></label>
                            <div class="input-group mb-0">
                                <input type="number" class="form-control" id="presentation_inactivity" value="<?php echo $virtual_tour['presentation_inactivity']; ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1 <?php echo ($virtual_tour['presentation_type']=='video') ? 'disabled' : ''; ?>">
                            <label for="presentation_loop"><?php echo _("Loop"); ?></label><br>
                            <input type="checkbox" id="presentation_loop" <?php echo ($virtual_tour['presentation_loop'])?'checked':''; ?> />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1 <?php echo ($virtual_tour['presentation_type']=='video') ? 'disabled' : ''; ?>">
                            <label for="presentation_stop_click"><?php echo _("Click to Stop"); ?> <i title="<?php echo _("to stop the presentation just click anywhere and not just on the button"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                            <input type="checkbox" id="presentation_stop_click" <?php echo ($virtual_tour['presentation_stop_click'])?'checked':''; ?> />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1 <?php echo ($virtual_tour['presentation_type']=='video') ? 'disabled' : ''; ?>">
                            <label for="presentation_stop_id_room"><?php echo _("Room (Stop)"); ?> <i title="<?php echo _("destination room when the presentation is stopped"); ?>" class="help_t fas fa-question-circle"></i></label>
                            <select data-live-search="true" id="presentation_stop_id_room" class="form-control">
                                <option id="0" <?php echo ($virtual_tour['presentation_stop_id_room']==0) ? 'checked' : ''; ?>><?php echo _("No change"); ?></option>
                                <?php echo get_rooms_option($id_virtualtour_sel,$virtual_tour['presentation_stop_id_room'],0); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1 <?php echo ($virtual_tour['presentation_type']=='video') ? 'disabled' : ''; ?>">
                            <label for="presentation_view_pois"><?php echo _("View POIs"); ?></label><br>
                            <input type="checkbox" id="presentation_view_pois" <?php echo ($virtual_tour['presentation_view_pois'])?'checked':''; ?> />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-1 <?php echo ($virtual_tour['presentation_type']=='video') ? 'disabled' : ''; ?>">
                            <label for="presentation_view_measures"><?php echo _("View Measures"); ?></label><br>
                            <input type="checkbox" id="presentation_view_measures" <?php echo ($virtual_tour['presentation_view_measures'])?'checked':''; ?> />
                        </div>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0 text-md-right text-center">
                        <div class="form-group mb-1">
                            <a id="save_btn" href="#" onclick="save_presentation()" class="btn btn-success btn-icon-split <?php echo ($demo) ? 'disabled_d':''; ?>">
                            <span class="icon text-white-50">
                              <i class="far fa-circle"></i>
                            </span>
                                <span class="text"><?php echo _("SAVE"); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row video_presentation <?php echo ($virtual_tour['presentation_type']=='video') ? '' : 'd-none'; ?>">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-video"></i> <?php echo _("Video Presentation"); ?> <i title="<?php echo _("a recorded video is used as a presentation"); ?>" class="help_t fas fa-question-circle"></i></h6>
            </div>
            <div class="card-body py-3" style="padding-top: 0;padding-bottom: 0;">
                <div class="col-md-12 px-0">
                    <div class="form-group">
                        <label for="presentation_video"><?php echo _("Youtube/Vimeo Link or upload Video MP4 / Webm"); ?></label>
                        <input type="text" class="form-control" id="presentation_video" value="<?php echo $virtual_tour['presentation_video']; ?>" />
                    </div>
                </div>
                <?php if($create_content) : ?>
                <form id="frm" action="ajax/upload_presentation_video.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                    <label class="custom-file-label text-left" for="txtFile"><?php echo _("Choose file"); ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Video"); ?>" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="preview text-center">
                                <div id="progress" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                    <div class="progress-bar" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                        0%
                                    </div>
                                </div>
                                <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error"></div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                <div class="col-md-12 px-0 <?php echo ($settings['enable_screencast']) ? '' : 'd-none'; ?>">
                    <label><?php echo _("Screencast"); ?></label><br>
                    <button onclick="open_vt_tab();" class="btn btn-primary mb-1"><?php echo _("1. Open tour in a new window"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-external-link-alt"></i></button>
                    <button style="pointer-events:none" class="btn btn-outline-primary mb-1"><?php echo _("2. Click the button Open screencast app"); ?></button>
                    <button style="pointer-events:none" class="btn btn-outline-primary mb-1"><?php echo _("3. Record your screen by selecting the open window"); ?></button>
                    <button style="pointer-events:none" class="btn btn-outline-primary mb-1"><?php echo _("4. Upload the recorded video"); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row automatic_presentation <?php echo ($virtual_tour['presentation_type']=='automatic') ? '' : 'd-none'; ?>">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-play-circle"></i> <?php echo _("Automatic Presentation"); ?> <i title="<?php echo _("automatic presentation consists of looking around the room by rotating and moving on to the next one"); ?>" class="help_t fas fa-question-circle"></i></h6>
            </div>
            <div class="card-body py-3" style="padding-top: 0;padding-bottom: 0;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="auto_presentation_speed"><?php echo _("Rotate speed"); ?> (<span id="auto_presentation_speed_value"><?php echo $virtual_tour['auto_presentation_speed']; ?></span>) <i title="<?php echo _("-1 to -10 speed clockwise, 1 to 10 speed counterclockwise"); ?>" class="help_t fas fa-question-circle"></i></label>
                            <input oninput="change_auto_presentation_speed();" onchange="change_auto_presentation_speed();" min="-10" max="10" type="range" class="form-control" id="auto_presentation_speed" value="<?php echo $virtual_tour['auto_presentation_speed']; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row manual_presentation <?php echo ($virtual_tour['presentation_type']=='manual') ? '' : 'd-none'; ?>">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-dot-circle"></i> <?php echo _("Manual Presentation"); ?> <i title="<?php echo _("the manual presentation consists in defining the order, changing the views of the rooms and add possible narrative text"); ?>" class="help_t fas fa-question-circle"></i> </h6>
            </div>
            <div class="card-body py-3" style="padding-top: 0;padding-bottom: 0;">
                <div id="presentation_list">
                    <div class="row">
                        <div class="col-md-8 text-center text-sm-center text-md-left text-lg-left">
                            <?php echo _("LOADING PRESENTATION ..."); ?>
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

<div id="modal_presentation_room" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="p_room"><?php echo _("Room"); ?></label>
                            <select onchange="change_preview_room_image_presentation(null);" data-live-search="true" id="p_room" class="form-control">
                                <?php echo get_rooms_option($id_virtualtour_sel,0,0); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label><?php echo _("Override Initial Position"); ?></label>&nbsp;
                            <input id="override_pos_presentation" type="checkbox" />
                        </div>
                    </div>
                    <div class="col-md-12 text-center mb-2">
                        <div style="width: 100%;max-width: 600px;height: 240px;margin: 0 auto;" id="panorama_pos_presentation"></div>
                        <p id="presentation_room_pos" style="width:100%;text-align:center"></p>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="p_sleep_r"><?php echo _("Delay (ms)"); ?></label>
                            <input type="number" class="form-control" id="p_sleep_r" value="0">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="p_wait_video_end"><?php echo _("Wait for the video ends"); ?></label><br>
                            <input type="checkbox" id="p_wait_video_end" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> data-toggle="modal" data-target="#modal_delete_p_room" id="btn_delete_p_room" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Remove"); ?></button>
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_add_p_room" onclick="add_presentation_room();" type="button" class="btn btn-success disabled"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_presentation_action" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="p_action"><?php echo _("Action"); ?></label>
                            <select onchange="change_p_action();" id="p_action" class="form-control">
                                <option id="0"><?php echo _("Select an action"); ?></option>
                                <option id="lookAt"><?php echo _("Change the view"); ?></option>
                                <option id="type"><?php echo _("Narrate a text"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div id="div_type" class="col-md-12" style="display: none">
                        <div class="form-group">
                            <label style="margin-bottom:0px;" for="p_text"><?php echo _("Narrate text"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'p_text'); ?>
                            <br><i style="font-size:12px"><?php echo _("Carriage Return to split text into sentences"); ?></i><br>
                            <textarea class="form-control" id="p_text" rows="4"></textarea>
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <textarea rows="4" style="display:none;" class="form-control input_lang" data-target-id="p_text" data-lang="<?php echo $lang; ?>"></textarea>
                                <?php endif;
                            } ?>
                        </div>
                        <div class="form-group">
                            <label for="p_sleep_t"><?php echo _("Delay (ms)"); ?></label>
                            <input type="number" class="form-control" id="p_sleep_t" value="0">
                        </div>
                    </div>
                    <div id="div_lookAt" class="col-md-12" style="display: none">
                        <label for="p_text"><?php echo _("Frame the desired view"); ?></label><br>
                        <div style="width:100%;max-width:600px;height:240px;margin:0 auto;" id="p_lookAt"></div>
                        <p style="width:100%;text-align:center"></p>
                        <div class="form-group">
                            <label for="p_animation"><?php echo _("Animation duration (ms)"); ?></label>
                            <input type="number" class="form-control" id="p_animation" value="1000">
                        </div>
                        <div class="form-group">
                            <label for="p_sleep_l"><?php echo _("Delay (ms)"); ?></label>
                            <input type="number" class="form-control" id="p_sleep_l" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> data-toggle="modal" data-target="#modal_delete_p_action" id="btn_delete_p_action" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Remove"); ?></button>
                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_presentation_action();" id="btn_add_p_action" type="button" class="btn btn-success disabled"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_p_room" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Remove Room"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to remove room and all its actions from presentation?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_remove_p_room" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Remove"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_p_action" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Remove Action"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to remove action from room's presentation?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_remove_p_action" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Remove"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_preview" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" style="width: 90% !important; max-width: 90% !important;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Preview"); ?></h5>
                <button onclick="close_preview_viewer();" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.p_viewer = null;
        window.id_p_room = null;
        window.index_p = null;
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.code_vt = '<?php echo $virtual_tour['code']; ?>';
        window.p_hfov = '<?php echo $virtual_tour['hfov']; ?>';
        window.p_min_hfov = '<?php echo $virtual_tour['min_hfov']; ?>';
        window.p_max_hfov = '<?php echo $virtual_tour['max_hfov']; ?>';
        window.p_viewer_initialized = false;
        window.array_presentation = null;
        window.p_params = '';
        window.array_id_rooms = [];
        window.vt_link = '<?php echo $link; ?>';
        window.presentation_need_save = false;
        window.create_content = '<?php echo $create_content; ?>';
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            bsCustomFileInput.init();
            $('#p_room').selectpicker('refresh');
            $('.help_t').tooltip();
            get_rooms(window.id_virtualtour,'presentation');
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
                        $('#presentation_video').val(evt.target.responseText);
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

        window.open_vt_tab = function() {
            window.open(vt_link+'&record=1','popup_vt','width='+screen.availWidth+',height='+screen.availHeight);
        }
    })(jQuery); // End of use strict
</script>