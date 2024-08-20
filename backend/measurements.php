<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
if(isset($_GET['id_room'])) {
    $id_room = $_GET['id_room'];
} else {
    $id_room = 0;
}
$can_create = get_plan_permission($id_user)['enable_measurements'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $code_vt = $virtual_tour['code'];
    $measurements = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['measurements']==0) {
            $measurements = false;
        }
    }
} else {
    $measurements = false;
}
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
?>

<?php include("check_plan.php"); ?>

<?php if(!$measurements): ?>
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
            <?php echo _("You cannot create Measurements on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Measurements!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel='stylesheet' type="text/css" href="https://fonts.googleapis.com/css?family=<?php echo $virtual_tour['font_viewer']; ?>">
<style>
    .leader-line { font-family: '<?php echo $virtual_tour['font_viewer']; ?>', sans-serif; }
</style>

<div class="row">
    <div class="col-md-12 mb-1">
        <div class="card shadow mb-12">
            <div class="card-body measure_div p-0">
                <div class="col-md-12 p-0">
                    <p style="display: none;" id="msg_sel_room" class="text-center mt-2 mb-1"><?php echo _("Select a room first!"); ?></p>
                    <p style="display: none;padding: 15px 15px 0;" id="msg_no_room"><?php echo sprintf(_('No rooms created for this Virtual Tour. Go to %s and create a new one!'),'<a href="index.php?p=rooms">'._("Rooms").'</a>'); ?></p>
                    <div id="measure_editor_div" style="position: relative;background: white;">
                        <div class="modal_fs_container">
                            <div style="display:none;" class="modal-backdrop show"></div>
                            <div id="modal_delete_measure" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Delete Measure"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to delete the measure?"); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_measure" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
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
                        </div>
                        <div class="div_panorama_container" id="panorama_measures"></div>
                        <div style="display:none" id="canvas_p"></div>
                        <div class="rooms_view_sel noselect"></div>
                        <div class="icon_visible_view noselect">
                            <label>
                                <input checked onchange="toggle_visible_view('measure')" id="check_visibile_view" type="checkbox" />&nbsp;&nbsp;<?php echo _("shows items that are only visible in this view"); ?>
                            </label>
                        </div>
                        <div id="slider_hs_list">
                            <div onclick="close_list_hs();" id="btn_close_hs_list">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="list-group"></div>
                        </div>
                        <div id="rooms_slider_p" class="rooms_slider mb-1 px-4"></div>
                        <div id="action_box">
                            <div class="measure_edit_label"></div>
                            <i title="<?php echo _("MOVE"); ?>" onclick="" class="move_action fa fa-arrows-alt"></i>
                            <i title="<?php echo _("EDIT"); ?>" onclick="" class="edit_action fa fa-edit"></i>
                            <i title="<?php echo _("DELETE"); ?>" onclick="" class="delete_action fa fa-trash"></i>
                        </div>
                        <div id="confirm_edit">
                            <ul style="width: calc(100% - 60px);" class="nav nav-pills justify-content-center mb-1" id="edit-tab" role="tablist">
                                <li style="height:20px;" class="nav-item"></li>
                                <i class="fas fa-arrows-alt move_box_edit"></i>
                                <i onclick="minimize_box_edit();" class="fas fa-minus minimize_box_edit"></i>
                                <span class="btn_close"><i class="fas fa-times"></i></span>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_label"><?php echo _("Label"); ?></label>
                                            <input oninput="render_edit_measure()" onchange="render_edit_measure()" id="measure_label" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px;" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_size_text"><?php echo _("Label - Size"); ?> (<span id="measure_size_text_value"></span>)</label>
                                            <input style="margin-top:8px;" oninput="render_edit_measure()" onchange="render_edit_measure()" min="10" max="32" step="1" id="measure_size_text" type="range" class="form-control-range" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_color_text"><?php echo _("Label - Color"); ?></label>
                                            <input id="measure_color_text" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_color_outline_text"><?php echo _("Label - Outline Color"); ?></label>
                                            <input id="measure_color_outline_text" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px;" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_line_size"><?php echo _("Line - Size"); ?> (<span id="measure_line_size_value"></span>)</label>
                                            <input style="margin-top:8px;" oninput="render_edit_measure()" onchange="render_edit_measure()" min="1" max="10" step="0.5" id="measure_line_size" type="range" class="form-control-range" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_line_color"><?php echo _("Line - Color"); ?></label>
                                            <input id="measure_line_color" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_start_plug"><?php echo _("Start Plug"); ?></label>
                                            <select onchange="render_edit_measure()" id="measure_start_plug" class="form-control form-control-sm">
                                                <option id="behind"><?php echo _("Behind"); ?></option>
                                                <option id="disc"><?php echo _("Circle"); ?></option>
                                                <option id="square"><?php echo _("Square"); ?></option>
                                                <option id="arrow1"><?php echo _("Arrow")." 1"; ?></option>
                                                <option id="arrow2"><?php echo _("Arrow")." 2"; ?></option>
                                                <option id="arrow3"><?php echo _("Arrow")." 3"; ?></option>
                                                <option id="hand"><?php echo _("Hand"); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px;" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_start_plug_size"><?php echo _("Start Plug - Size"); ?> (<span id="measure_start_plug_size_value"></span>)</label>
                                            <input style="margin-top:8px;" oninput="render_edit_measure()" onchange="render_edit_measure()" min="1" max="6" step="0.1" id="measure_start_plug_size" type="range" class="form-control-range" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_start_plug_color"><?php echo _("Start Plug - Color"); ?></label>
                                            <input id="measure_start_plug_color" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_end_plug"><?php echo _("End Plug"); ?></label>
                                            <select onchange="render_edit_measure()" id="measure_end_plug" class="form-control form-control-sm">
                                                <option id="behind"><?php echo _("Behind"); ?></option>
                                                <option id="disc"><?php echo _("Circle"); ?></option>
                                                <option id="square"><?php echo _("Square"); ?></option>
                                                <option id="arrow1"><?php echo _("Arrow")." 1"; ?></option>
                                                <option id="arrow2"><?php echo _("Arrow")." 2"; ?></option>
                                                <option id="arrow3"><?php echo _("Arrow")." 3"; ?></option>
                                                <option id="hand"><?php echo _("Hand"); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px;" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_end_plug_size"><?php echo _("End Plug - Size"); ?> (<span id="measure_end_plug_size_value"></span>)</label>
                                            <input style="margin-top:8px;" oninput="render_edit_measure()" onchange="render_edit_measure()" min="1" max="6" step="0.1" id="measure_end_plug_size" type="range" class="form-control-range" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="margin-bottom: 5px" class="form-group">
                                            <label style="margin-bottom: 0px" for="measure_end_plug_color"><?php echo _("End Plug - Color"); ?></label>
                                            <input id="measure_end_plug_color" type="text" class="form-control form-control-sm" value="">
                                        </div>
                                    </div>
                                </div>
                                <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                        <div id="confirm_move">
                            <div style="width: calc(100% - 30px);">
                                <b><?php echo _("drag the red points to change the position"); ?></b>
                            </div>
                            <div class="row">
                                <div class="col-md-3"></div>
                                <div class="col-md-6">
                                    <div style="margin-top: 2px;" class="form-group noselect visible_in_div">
                                        <label style="margin-bottom: 0;"><?php echo _("Visible In"); ?> <i title="<?php echo _("it will be visible only in the selected views (selectable only if there are multiple views of the same room)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                        <select disabled id="visibile_in_views" multiple data-iconBase="fa" data-tickIcon="fa-check" data-actions-box="true" data-selected-text-format="count > 8" data-count-selected-text="{0} <?php echo _("views selected"); ?>" data-deselect-all-text="<?php echo _("All the views"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("All the Views"); ?>" class="selectpicker form-control form-control-sm"></select>
                                    </div>
                                </div>
                                <div class="col-md-3"></div>
                            </div>
                            <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            <span class="btn_close"><i class="fas fa-times"></i></span>
                        </div>
                        <button title="<?php echo _("LIST MEASUREMENTS"); ?>" onclick="open_list_hs();" id="btn_list_hs" style="opacity:0;position:absolute;top:10px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-light"><i class="fas fa-list-ol"></i><span class="hs_badge_count badge badge-primary position-absolute">0</span></button>
                        <button title="<?php echo _("ADD MEASUREMENT"); ?>" id="btn_add_measure" onclick="" style="opacity:0;position:absolute;top:60px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-success"><i class="fas fa-plus"></i></button>
                        <button onclick="open_preview_viewer();" title="<?php echo _("PREVIEW"); ?>" id="btn_preview_modal" style="opacity:0;position:absolute;top:10px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-eye"></i></button>
                        <button onclick="toggle_fullscreen_div('measure_editor_div');" title="<?php echo _("TOGGLE FULLSCREEN"); ?>" id="btn_toggle_fullscreen" style="opacity:0;position:absolute;top:60px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_room_measure = <?php echo $id_room; ?>;
        window.id_room_sel = null;
        window.id_room_alt_sel = 0;
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.code_vt = '<?php echo $code_vt; ?>';
        window.measures = null;
        window.measures_initial = null;
        window.rooms_count = 0;
        window.viewer_initialized = false;
        window.viewer = null;
        window.video_viewer = null;
        window.viewer_pos = null;
        window.measure_id_edit = null;
        window.measure_index_edit = null;
        window.is_editing = false;
        window.switched_page = false;
        window.panorama_image = '';
        window.currentYaw = 0;
        window.currentPitch = 0;
        window.is_fullscreen = false;
        window.measure_lines = [];
        window.enable_adjust_measurements = true;
        window.measure_line_color_spectrum = null;
        window.measure_color_text_spectrum = null;
        window.measure_color_outline_text_spectrum = null;
        window.measure_start_plug_color_spectrum = null;
        window.measure_end_plug_color_spectrum = null;
        window.interval_adjust_measures = null;
        window.count_adjust_measures = 0;
        window.viewer_font_family = "<?php echo $virtual_tour['font_viewer']; ?>', sans-serif";
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            var md = new MobileDetect(window.navigator.userAgent);
            if(md.mobile()==null) {
                window.is_mobile = false;
            } else {
                window.is_mobile = true;
            }
            if("currentYaw" in sessionStorage) {
                window.currentYaw = parseFloat(sessionStorage.getItem('currentYaw'));
                window.currentPitch = parseFloat(sessionStorage.getItem('currentPitch'));
                sessionStorage.setItem('currentYaw','0');
                sessionStorage.setItem('currentPitch','0');
                if(window.currentYaw!=0 && window.id_room_marker!=0) {
                    window.switched_page = true;
                }
            }
            var container_h = $('#content-wrapper').height() - 225;
            $('#panorama_measures').css('height',container_h+'px');
            var check_visibile_view = sessionStorage.getItem('check_visibile_view');
            if(check_visibile_view!==null) {
                if(check_visibile_view==1) {
                    $('#check_visibile_view').prop('checked',true);
                } else {
                    $('#check_visibile_view').prop('checked',false);
                }
            }
            $('.help_t').tooltip();
            $('#action_box i').tooltip();
            setTimeout(function () {
                get_rooms(window.id_virtualtour,'measure');
            },200);
            $('#btn_add_measure').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'left'
            });
            $('#btn_preview_modal').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'right'
            });
            $('#btn_toggle_fullscreen').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'right'
            });
            if(!window.is_mobile) {
                $('#confirm_edit').draggable({
                    cursor: "move",
                    handle: ".move_box_edit",
                    containment: "#measure_editor_div",
                    start: function() {
                        var parentRect = $(this).parent()[0].getBoundingClientRect();
                        var rect = this.getBoundingClientRect();
                        $(this).css('transition', 'all 0 ease 0');
                        $(this).css('transform', 'none');
                        $(this).css('left', rect['left']-parentRect['left']);
                    }
                });
            } else {
                $('.sp-palette-container').hide();
                $('.move_box_edit').hide();
            }
            window.measure_line_color_spectrum = $('#measure_line_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#measure_editor_div',
                move: function(color) {
                    render_edit_measure();
                },
                change: function(color) {
                    render_edit_measure();
                }
            });
            window.measure_color_text_spectrum = $('#measure_color_text').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#measure_editor_div',
                move: function(color) {
                    render_edit_measure();
                },
                change: function(color) {
                    render_edit_measure();
                }
            });
            window.measure_color_outline_text_spectrum = $('#measure_color_outline_text').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: true,
                appendTo: '#measure_editor_div',
                move: function(color) {
                    render_edit_measure();
                },
                change: function(color) {
                    render_edit_measure();
                }
            });
            window.measure_start_plug_color_spectrum = $('#measure_start_plug_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#measure_editor_div',
                move: function(color) {
                    render_edit_measure();
                },
                change: function(color) {
                    render_edit_measure();
                }
            });
            window.measure_end_plug_color_spectrum = $('#measure_end_plug_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#measure_editor_div',
                move: function(color) {
                    render_edit_measure();
                },
                change: function(color) {
                    render_edit_measure();
                }
            });
        });
        $('#sidebarToggle').on('click',function() {
            $(document).trigger('resize');
        });
        $(window).resize(function () {
            if(window.is_fullscreen) {
                var panorama_pos_left = 15;
                var panorama_pos_top = 15;
                if($('.rooms_slider').is(':visible')) {
                    var h_p = 75;
                } else {
                    var h_p = 0;
                }
                $('#panorama_measures').css('height','calc(100% - '+h_p+'px)');
                try {
                    $('#video_viewer').css('height','100%');
                } catch (e) {}
            } else {
                var panorama_pos = $('#measure_editor_div').offset();
                var panorama_pos_left = 15+panorama_pos.left*-1;
                var panorama_pos_top = 15+panorama_pos.top*-1;
                if($('.rooms_slider').is(':visible')) {
                    var h_p = 225;
                } else {
                    var h_p = 225-75;
                }
                var container_h = $('#content-wrapper').height() - h_p;
                $('#panorama_measures').css('height',container_h+'px');
                try {
                    $('#video_viewer').css('height',container_h+'px');
                } catch (e) {}
            }
            $('.leader-line').css('margin-left',panorama_pos_left+'px');
            $('.leader-line').css('margin-top',panorama_pos_top+'px');
            setTimeout(function() {
                adjust_measurements();
            },10);
        });
        $(document).mousedown(function(e) {
            if ($("#slider_hs_list").has(e.target).length > 0 || e.target.id=='slider_hs_list') {
                return;
            }
            if ($("#btn_list_hs").has(e.target).length > 0 || e.target.id=='btn_list_hs') {
                return;
            }
            var container = $("#action_box");
            if (!container.is(e.target) && container.has(e.target).length === 0) {
                if(!window.is_editing) {
                    $('.custom-hotspot').css('opacity',1);
                    $('.center_helper').show();
                    $('.leader-line').css({'opacity':1,'pointer-events':'initial'});
                    $('.measure_points').css({'opacity':0,'pointer-events':'none'});
                }
                container.hide();
                $('#slider_hs_list .list-group button').removeClass('active');
            }
        });
    })(jQuery); // End of use strict

    $(document).on('shown.bs.modal', '.modal', function () {
        if(window.is_fullscreen) {
            $('.modal_fs_container .modal-backdrop').show();
        }
    });

    $(document).on('hide.bs.modal', '.modal', function () {
        $('.modal_fs_container .modal-backdrop').hide();
    });

    if (document.addEventListener) {
        document.addEventListener('fullscreenchange', exitHandler, false);
        document.addEventListener('mozfullscreenchange', exitHandler, false);
        document.addEventListener('MSFullscreenChange', exitHandler, false);
        document.addEventListener('webkitfullscreenchange', exitHandler, false);
    }

    function exitHandler() {
        if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
            window.is_fullscreen = false;
            $(window).trigger('resize');
        }
    }
</script>