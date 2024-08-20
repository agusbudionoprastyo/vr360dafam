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
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
$code_vt = $virtual_tour['code'];
$icons_library = get_plan_permission($id_user)['enable_icons_library'];
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
    if($editor_permissions['icons_library']==0) {
        $icons_library = 0;
    }
    if($editor_permissions['create_markers']==1) {
        $create_permission=true;
    } else {
        $create_permission=false;
    }
    if($editor_permissions['edit_markers']==1) {
        $edit_permission=true;
    } else {
        $edit_permission=false;
    }
    if($editor_permissions['delete_markers']==1) {
        $delete_permission=true;
    } else {
        $delete_permission=false;
    }
} else {
    $create_permission=true;
    $edit_permission=true;
    $delete_permission=true;
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

<?php if(!$create_content) : ?>
<style>
    .rooms_slider .room_quick_btn {
        display: none !important;
    }
    #rooms_slider_m .room_image {
        border-radius: 6px;
    }
</style>
<?php endif; ?>

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create Markers on an external virtual tour!"); ?>
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

<div id="plan_marker_msg" class="card bg-warning text-white shadow mb-4 d-none">
    <div class="card-body">
        <?php echo _("You have reached the maximum number of Markers allowed from your plan!")." ".$msg_change_plan; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-1">
        <div class="card shadow mb-12">
            <div class="card-body marker_div p-0">
                <div class="col-md-12 p-0">
                    <p style="display: none;" id="msg_sel_room" class="text-center mt-2 mb-1"><?php echo _("Select a room first!"); ?></p>
                    <p style="display: none;padding: 15px 15px 0;" id="msg_no_room"><?php echo sprintf(_('No rooms created for this Virtual Tour. Go to %s and create a new one!'),'<a href="index.php?p=rooms">'._("Rooms").'</a>'); ?></p>
                    <div id="marker_editor_div" style="position: relative;background: white;">
                        <div class="modal_fs_container">
                            <div style="display:none;" class="modal-backdrop show"></div>
                            <div id="modal_add_marker" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Add Marker"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div id="div_poi_select_style" class="col-md-6 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                                    <p class="mb-0"><?php echo _("Style"); ?></p>
                                                    <div class="dropdown">
                                                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdown_marker_style" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-center" aria-labelledby="dropdown_marker_style">
                                                            <a onclick="select_marker_style('icon');" id="btn_style_icon" class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?></a>
                                                            <a onclick="select_marker_style('embed_selection');" id="btn_style_embed_selection" class="dropdown-item" href="#"><i class="far fa-square"></i> <?php echo _("Selection Area"); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-center">
                                                    <div class="form-group">
                                                        <label class="mb-0"><?php echo _("LookAt"); ?> <i title="<?php echo _("moves the view in the direction of the clicked marker"); ?>" class="help_t fas fa-question-circle"></i></label>
                                                        <select id="lookat_add" class="form-control">
                                                            <option <?php echo ($virtual_tour['markers_default_lookat']==0) ? 'selected' : ''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                                            <option <?php echo ($virtual_tour['markers_default_lookat']==1) ? 'selected' : ''; ?> id="1"><?php echo _("Horizontal only"); ?></option>
                                                            <option <?php echo ($virtual_tour['markers_default_lookat']==2) ? 'selected' : ''; ?> id="2"><?php echo _("Horizontal and Vertical"); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 text-center">
                                                    <div id="room_target_add_div" class="form-group">
                                                        <label class="mb-0"><?php echo _("Room Target"); ?></label>
                                                        <select data-live-search="true" onchange="" id="room_target_add" class="form-control"></select>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 text-center">
                                                    <div class="form-group mb-0">
                                                        <?php if($virtual_tour['sameAzimuth']) { echo "<i style='font-size:14px;'>"._("Same Azimuth enabled: can not override the Initial Position.")."</i>"; }  ?>
                                                        <label <?php echo ($virtual_tour['sameAzimuth']) ? 'style="display:none;' : '"' ; ?>><?php echo _("Override Initial Position"); ?> <i title="<?php echo _("Drag the view to set the starting position belongs to this marker. Only works if 'Same Azimuth' is disabled."); ?>" class="help_t fas fa-question-circle"></i></label>&nbsp;
                                                        <input <?php echo ($virtual_tour['sameAzimuth']) ? 'style="display:none;' : '"' ; ?> id="override_pos_add" type="checkbox" />
                                                    </div>
                                                    <div class="form-group mb-0">
                                                        <label><?php echo _("Add Marker to go back"); ?> <i title="<?php echo _("To position it correctly, override the initial position of the Room Target in the forward direction."); ?>" class="help_t fas fa-question-circle"></i></label>&nbsp;
                                                        <input onclick="click_backlink();" <?php echo ($virtual_tour['markers_default_backlink']==1) ? 'checked' : ''; ?> id="backlink_marker" type="checkbox" />
                                                    </div>
                                                    <div style="width: 100%;max-width: 400px;height: 200px;margin: 0 auto;" id="panorama_pos_add"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_new_marker" onclick="" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_delete_marker" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Delete Marker"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to delete the marker?"); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_marker" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_library_icons" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Library Icons"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3 <?php echo ($icons_library==0) ? 'd-none' : ''; ?>">
                                                <?php if($upload_content) : ?><form action="ajax/upload_icon_image.php" class="dropzone noselect <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?>" id="gallery-dropzone-im"></form><?php endif; ?>
                                            </div>
                                            <div id="list_images_im"></div>
                                        </div>
                                        <div class="modal-footer">
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

                            <div id="modal_markers_style_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Markers Settings"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to apply these settings to all existing markers by overwriting them?"); ?></p>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_style"><?php echo _("Type"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_style" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_icon"><?php echo _("Icon"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_icon" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_animation"><?php echo _("Animation"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_animation" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_color"><?php echo _("Color"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_color" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_background"><?php echo _("Background"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_background" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_icon_type"><?php echo _("Style"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_icon_type" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_tooltip_type"><?php echo _("Tooltip Type"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_tooltip_type" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_tooltip_visibility"><?php echo _("Tooltip Visibility"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_tooltip_visibility" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_tooltip_background"><?php echo _("Tooltip Background"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_tooltip_background" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_tooltip_color"><?php echo _("Tooltip Color"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_tooltip_color" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_marker_sound"><?php echo _("Sound"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_sound" checked />
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-12 mb-0">
                                                    <div class="form-group mb-0">
                                                        <label class="mb-0" for="set_as_default"><input type="checkbox" id="set_as_default" />&nbsp;&nbsp;<?php echo _("Set as default for the tour"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_styles('markers_e');" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_markers_move_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Markers Settings"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to apply these settings to all existing markers by overwriting them?"); ?></p>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_marker_perspective"><?php echo _("Perspective"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_perspective" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_marker_size"><?php echo _("Size"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_size" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_marker_scale"><?php echo _("Scale"); ?></label><br>
                                                        <input type="checkbox" id="apply_marker_scale" checked />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_moves('markers');" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="div_panorama_container" id="panorama_markers"></div>
                        <div style="display:none" id="canvas_p"></div>
                        <div class="rooms_view_sel noselect"></div>
                        <div class="icon_visible_view noselect">
                            <label>
                                <input checked onchange="toggle_visible_view('marker')" id="check_visibile_view" type="checkbox" />&nbsp;&nbsp;<?php echo _("shows items that are only visible in this view"); ?>
                            </label>
                        </div>
                        <div id="slider_hs_list">
                            <div onclick="close_list_hs();" id="btn_close_hs_list">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="list-group"></div>
                        </div>
                        <div id="rooms_slider_m" class="rooms_slider mb-1 px-4"></div>
                        <div id="action_box">
                            <div class="marker_edit_label"></div>
                            <i title="<?php echo _("MOVE"); ?>" onclick="" class="move_action fa fa-arrows-alt <?php echo (!$edit_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("EDIT"); ?>" onclick="" class="edit_action fa fa-edit <?php echo (!$edit_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("DELETE"); ?>" onclick="" class="delete_action fa fa-trash <?php echo (!$delete_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("GO TO"); ?>" onclick="" class="goto_action fas fa-sign-in-alt"></i>
                        </div>
                        <div id="confirm_edit">
                            <ul style="margin-left:25px;width:calc(100% - 85px);" class="nav nav-pills justify-content-center mb-1" id="edit-tab" role="tablist">
                                <li class="nav-item">
                                    <a onclick="show_marker_apply_style(false);maximize_box_edit();" class="nav-link active" id="pills-edit-tab" data-toggle="pill" href="#pills-edit" role="tab" aria-controls="pills-edit" aria-selected="true"><i class="fas fa-cog"></i> <?php echo strtoupper(_("Settings")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_marker_apply_style(true);maximize_box_edit();" class="nav-link" id="pills-style-tab" data-toggle="pill" href="#pills-style" role="tab" aria-controls="pills-style" aria-selected="false"><i class="fas fa-palette"></i> <?php echo strtoupper(_("Style")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_marker_apply_style(false);maximize_box_edit();" class="nav-link" id="pills-tooltip-tab" data-toggle="pill" href="#pills-tooltip" role="tab" aria-controls="pills-tooltip" aria-selected="false"><i class="fas fa-comment-dots"></i> <?php echo strtoupper(_("Tooltip")); ?></a>
                                </li>
                                <i class="fas fa-arrows-alt move_box_edit"></i>
                                <i onclick="minimize_box_edit();" class="fas fa-minus minimize_box_edit"></i>
                                <span class="btn_close"><i class="fas fa-times"></i></span>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <hr>
                                <div class="tab-pane fade show active" id="pills-edit" role="tabpanel" aria-labelledby="pills-edit-tab">
                                    <div class="row">
                                        <div style="margin-bottom: 5px;" class="col-md-6 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                            <p class="mb-0"><?php echo _("Style"); ?></p>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdown_marker_style_edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-center" aria-labelledby="dropdown_marker_style_edit">
                                                    <a onclick="select_marker_style_edit('icon');" id="btn_edit_style_icon" class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?></a>
                                                    <a onclick="select_marker_style_edit('embed_selection');" id="btn_edit_style_embed_selection" class="dropdown-item" href="#"><i class="far fa-square"></i> <?php echo _("Selection Area"); ?></a>
                                                </div>
                                                <button id="btn_change_marker_embed_style" onclick="change_marker_embed_style();" class="btn btn-sm btn-success disabled"><i class="fas fa-arrow-right"></i> <?php echo _("Change"); ?></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("LookAt"); ?> <i title="<?php echo _("moves the view in the direction of the clicked marker"); ?>" class="help_t fas fa-question-circle"></i></label>
                                                <select id="lookat" class="form-control form-control-sm">
                                                    <option id="0"><?php echo _("Disabled"); ?></option>
                                                    <option id="1"><?php echo _("Horizontal only"); ?></option>
                                                    <option id="2"><?php echo _("Horizontal and Vertical"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div style="margin:0 auto;margin-bottom: 5px;width: 100%;max-width: 400px;" class="form-group">
                                                <label><?php echo _("Room Target"); ?></label>
                                                <select data-live-search="true" onchange="" id="room_target" class="form-control form-control-sm"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mt-1 mb-2">
                                            <div class="form-group mb-0">
                                                <?php if($virtual_tour['sameAzimuth']) { echo "<i style='font-size:14px;'>"._("Same Azimuth enabled: can not override the Initial Position.")."</i>"; }  ?>
                                                <label <?php echo ($virtual_tour['sameAzimuth']) ? 'style="display:none;' : '"' ; ?>><?php echo _("Override Initial Position"); ?> <i title="<?php echo _("Drag the view to set the starting position belongs to this marker. Only works if 'Same Azimuth' is disabled."); ?>" class="help_t fas fa-question-circle"></i></label>&nbsp;
                                                <input <?php echo ($virtual_tour['sameAzimuth']) ? 'style="display:none;' : '"' ; ?> id="override_pos_edit" type="checkbox" />
                                            </div>
                                            <div style="width: 100%;max-width: 400px;height: 200px;margin: 0 auto;" id="panorama_pos_edit"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-tooltip" role="tabpanel" aria-labelledby="pills-tooltip-tab">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tooltip_type"><?php echo _("Type"); ?></label>
                                                <select onchange="change_tooltip_type_m();" id="tooltip_type" class="form-control form-control-sm">
                                                    <option id="none"><?php echo _("None"); ?></option>
                                                    <option id="room_name"><?php echo _("Target Room's Name"); ?></option>
                                                    <option id="preview"><?php echo _("Target Room's Preview (Rounded)"); ?></option>
                                                    <option id="preview_square"><?php echo _("Target Room's Preview (Squared)"); ?></option>
                                                    <option id="preview_rect"><?php echo _("Target Room's Preview (Rectangular)"); ?></option>
                                                    <option id="text"><?php echo _("Custom Text"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tooltip_visibility"><?php echo _("Visibility"); ?></label>
                                                <select id="tooltip_visibility" class="form-control form-control-sm">
                                                    <option id="hover"><?php echo _("Hover (Desktop)"); ?></option>
                                                    <option id="visible"><?php echo _("Always (Desktop - Mobile)"); ?></option>
                                                    <option id="visible_mobile"><?php echo _("Hover (Desktop) - Always (Mobile)"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tooltip_background"><?php echo _("Background"); ?></label>
                                                <input type="text" id="tooltip_background" class="form-control form-control-sm" value="rgba(255,255,255,1)" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tooltip_color"><?php echo _("Color"); ?></label>
                                                <input type="text" id="tooltip_color" class="form-control form-control-sm" value="#000000" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="tooltip_text"><?php echo _("Text"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'tooltip_text_html'); ?>
                                                <div><div id="tooltip_text_html"></div></div>
                                                <?php foreach ($array_languages as $lang) {
                                                    if($lang!=$default_language) : ?>
                                                        <div style="display:none;"><div id="tooltip_text_html_<?php echo $lang; ?>" class="input_lang" data-target-id="tooltip_text_html" data-lang="<?php echo $lang; ?>"></div></div>
                                                    <?php endif;
                                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-style" role="tabpanel" aria-labelledby="pills-style-tab">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="markers_style"><?php echo _("Type"); ?></label>
                                                <select onchange="change_marker_style()" id="marker_style" class="form-control form-control-sm">
                                                    <option id="1"><?php echo _("Icon + Room's Name"); ?></option>
                                                    <option id="2"><?php echo _("Room's Name + Icon"); ?></option>
                                                    <option id="0"><?php echo _("Only Icon"); ?></option>
                                                    <option id="3"><?php echo _("Only Room's Name"); ?></option>
                                                    <option id="4"><?php echo _("Custom Icons Library"); ?></option>
                                                    <option id="5"><?php echo _("Preview Room"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_icon"><?php echo _("Icon"); ?></label><br>
                                                <button class="btn btn-sm btn-primary" type="button" id="GetIconPicker" data-iconpicker-input="input#marker_icon" data-iconpicker-preview="i#marker_icon_preview"><?php echo _("Select Icon"); ?></button>
                                                <input readonly type="hidden" id="marker_icon" name="Icon" value="fas fa-image" required="" placeholder="" autocomplete="off" spellcheck="false">
                                                <div style="vertical-align: middle;" class="icon-preview d-inline-block ml-1" data-toggle="tooltip" title="">
                                                    <i style="font-size: 24px;" id="marker_icon_preview" class="fas fa-image"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="display: none" class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_library_icon"><?php echo _("Library Icon"); ?></label><br>
                                                <button onclick="open_modal_library_icons()" class="btn btn-sm btn-primary" type="button" id="btn_library_icon"><?php echo _("Select Library Icon"); ?></button>
                                                <input type="hidden" id="marker_library_icon" value="0" />
                                                <img id="marker_library_icon_preview" style="display: none;height:30px" src="" />
                                                <div id="marker_library_icon_preview_l" style="display: none;height:30px;vertical-align:middle;"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_animation"><?php echo _("Animation"); ?></label>
                                                <select onchange="change_marker_animation()" id="marker_animation" class="form-control form-control-sm">
                                                    <option id="none"><?php echo _("None"); ?></option>
                                                    <option id="bounce"><?php echo _("Bounce"); ?></option>
                                                    <option id="flash"><?php echo _("Flash"); ?></option>
                                                    <option id="rubberBand"><?php echo _("Rubberband"); ?></option>
                                                    <option id="shakeX"><?php echo _("Shake X"); ?></option>
                                                    <option id="shakeY"><?php echo _("Shake Y"); ?></option>
                                                    <option id="swing"><?php echo _("Swing"); ?></option>
                                                    <option id="tada"><?php echo _("Tada"); ?></option>
                                                    <option id="wobble"><?php echo _("Wobble"); ?></option>
                                                    <option id="jello"><?php echo _("Jello"); ?></option>
                                                    <option id="heartBeat"><?php echo _("Heartbeat"); ?></option>
                                                    <option id="flip"><?php echo _("Flip"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_css_class"><?php echo _("CSS Class"); ?></label>
                                                <input type="text" id="marker_css_class" class="form-control form-control-sm" value="" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label id="marker_color_label" for="marker_color"><?php echo _("Color"); ?></label>
                                                <input type="text" id="marker_color" class="form-control form-control-sm" value="#000000" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_background"><?php echo _("Background"); ?></label>
                                                <input type="text" id="marker_background" class="form-control form-control-sm" value="rgba(255,255,255,0.7)" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_border_px"><?php echo _("Border"); ?></label>
                                                <input oninput="change_marker_border_px();" min="0" max="10" type="number" id="marker_border_px" class="form-control form-control-sm" value="3" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_icon_type"><?php echo _("Style"); ?></label>
                                                <select onchange="change_marker_icon_type();" id="marker_icon_type" class="form-control form-control-sm">
                                                    <option id="round"><?php echo _("Round"); ?></option>
                                                    <option id="square"><?php echo _("Square"); ?></option>
                                                    <option id="round_outline"><?php echo _("Round (outline)"); ?></option>
                                                    <option id="square_outline"><?php echo _("Square (outline)"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="marker_sound"><?php echo _("Sound"); ?> <i title="<?php echo _("sound file must be uploaded into sound library."); ?>" class="help_t fas fa-question-circle"></i></label>
                                            <div style="margin-bottom: 5px;" class="input-group">
                                                <select id="marker_sound" class="form-control form-control-sm">
                                                    <option id=""><?php echo _("No Sound"); ?></option>
                                                    <?php echo get_option_exist_sound($_SESSION['id_user'],$id_virtualtour_sel,''); ?>
                                                </select>
                                                <div class="input-group-append">
                                                    <button onclick="play_sound('marker_sound');" class="btn btn-sm btn-outline-secondary" type="button"><i class="fas fa-play"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:none;" class="mt-1 mb-1"><label style="font-size:12px;"><input style="vertical-align:middle;" id="exclude_from_apply_all" type="checkbox">&nbsp;&nbsp;<?php echo _("exclude from apply to all"); ?></label></div>
                                <span data-toggle="modal" data-target="#modal_markers_style_apply" style="display:none;" class="btn_apply_style_all btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("APPLY STYLE TO ALL"); ?>&nbsp;&nbsp;<i class="fas fa-check-double"></i></span>
                                <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                        <div id="confirm_move">
                            <div style="width: calc(100% - 30px);">
                                <b id="msg_drag_marker"><?php echo _("drag the marker to change its position"); ?></b>
                                <b tyle="width: calc(100% - 30px);" style="display:none;" id="msg_drag_embed"><?php echo _("drag the pointers to move and resize the content"); ?></b>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label style="margin-bottom: 0;"><?php echo _("Perspective"); ?> <i style="font-size:12px;" id="perspective_values"></i></label><br>
                                        <input oninput="" type="range" min="0" max="70" step="1" class="form-control-range" id="rotateX">
                                        <input oninput="" type="range" min="-180" max="180" step="1" class="form-control-range" id="rotateZ">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label style="margin-bottom: 0;"><?php echo _("Size"); ?> <i style="font-size:12px;" id="size_values"></i></label>
                                        <input oninput="" type="range" step="0.1" min="0.5" max="2.0" class="form-control-range" id="size_scale">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-top: 2px;" class="form-group noselect visible_in_div">
                                        <label style="margin-bottom: 0;"><?php echo _("Visible In"); ?> <i title="<?php echo _("it will be visible only in the selected views (selectable only if there are multiple views of the same room)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                        <select disabled id="visibile_in_views" multiple data-iconBase="fa" data-tickIcon="fa-check" data-actions-box="true" data-selected-text-format="count > 8" data-count-selected-text="{0} <?php echo _("views selected"); ?>" data-deselect-all-text="<?php echo _("All the views"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("All the Views"); ?>" class="selectpicker form-control form-control-sm"></select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-top: 2px;" class="form-group noselect">
                                        <label style="margin-bottom: 0;"><?php echo _("Z Order"); ?><br><i id="btn_change_zindex_left" onclick="" style="cursor:pointer;" class="fas fa-caret-left"></i>&nbsp;&nbsp;<span id="zIndex_value">1</span>&nbsp;&nbsp;<i id="btn_change_zindex_right" onclick="" style="cursor:pointer;" class="fas fa-caret-right"></i></label>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 5px;display: none" class="form-group noselect">
                                <button id="btn_draw_polygon_m" onclick="draw_polygon_selection_m();" class="btn btn-sm btn-primary"><i class="fas fa-draw-polygon"></i>&nbsp;&nbsp;<?php echo _("draw polygon inside"); ?></button>
                            </div>
                            <div style="display: none;margin-bottom: 5px;" class="form-group">
                                <input onchange="change_scale_m()" type="checkbox" id="scale" />
                                <label style="margin-bottom: 0;" for="scale"><?php echo _("Scale"); ?></label>
                            </div>
                            <span data-toggle="modal" data-target="#modal_markers_move_apply" style="display:none;" class="btn_apply_move_all btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("APPLY TO ALL"); ?>&nbsp;&nbsp;<i class="fas fa-check-double"></i></span>
                            <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            <i class="fas fa-arrows-alt move_box_move"></i>
                            <span class="btn_close"><i class="fas fa-times"></i></span>
                        </div>
                        <div id="confirm_polygon">
                            <div class="noselect" style="width: calc(100% - 30px);margin-bottom:5px;">
                                <b><?php echo _("click to draw the points of the polygon"); ?></b>
                            </div>
                            <div style="margin-bottom: 5px;" class="form-group noselect">
                                <button id="btn_clear_polygon" onclick="clear_polygon_selection_m();" class="btn btn-sm btn-primary disabled"><i class="fas fa-eraser"></i>&nbsp;&nbsp;<?php echo _("Clear"); ?></button>
                                <button id="btn_save_polygon" onclick="save_polygon_selection_m();" class="btn btn-sm btn-success disabled"><i class="fas fa-check"></i>&nbsp;&nbsp;<?php echo _("Confirm"); ?></button>
                                <button onclick="close_polygon_selection_m();" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i>&nbsp;&nbsp;<?php echo _("Close"); ?></button>
                            </div>
                        </div>
                        <button title="<?php echo _("LIST MARKERS"); ?>" onclick="open_list_hs();" id="btn_list_hs" style="opacity:0;position:absolute;top:10px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-light"><i class="fas fa-list-ol"></i><span class="hs_badge_count badge badge-primary position-absolute">0</span></button>
                        <?php if($create_permission && $create_content) : ?><button title="<?php echo _("ADD MARKER"); ?>" id="btn_add_marker" style="opacity:0;position:absolute;top:60px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-success"><i class="fas fa-plus"></i></button><?php endif; ?>
                        <button title="<?php echo _("EDIT POIs"); ?>" id="btn_switch_to_poi" style="opacity:0;position:absolute;top:10px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-bullseye"></i></button>
                        <button onclick="open_preview_viewer();" title="<?php echo _("PREVIEW"); ?>" id="btn_preview_modal" style="opacity:0;position:absolute;top:60px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-eye"></i></button>
                        <button onclick="toggle_fullscreen_div('marker_editor_div');" title="<?php echo _("TOGGLE FULLSCREEN"); ?>" id="btn_toggle_fullscreen" style="opacity:0;position:absolute;top:110px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        Dropzone.autoDiscover = false;
        window.id_room_marker = <?php echo $id_room; ?>;
        window.id_room_sel = null;
        window.id_room_alt_sel = 0;
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.code_vt = '<?php echo $code_vt; ?>';
        window.markers = null;
        window.markers_initial = null;
        window.rooms_count = 0;
        window.can_create = false;
        window.viewer_initialized = false;
        window.viewer = null;
        window.video_viewer = null;
        window.marker_background_spectrum = null;
        window.tooltip_background_spectrum = null;
        window.tooltip_color_spectrum = null;
        window.viewer_pos = null;
        window.is_editing = false;
        window.marker_index_edit = null;
        window.marker_id_edit = null;
        window.panorama_image = '';
        window.currentYaw = 0;
        window.currentPitch = 0;
        window.switched_page = false;
        window.poi_embed_originals_pos = [];
        window.marker_embed_originals_pos = [];
        window.video_embeds = [];
        window.sync_virtual_staging_enabled = false;
        window.sync_poi_embed_enabled = false;
        window.sync_marker_embed_enabled = false;
        window.embed_type_sel = '';
        window.embed_type_current = '';
        window.gallery_dropzone_im = null;
        window.tooltip_text_editor = null;
        window.tooltip_text_editor_lang = [];
        window.is_fullscreen = false;
        window.draw_polygon_mode = 0;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        var DirectionAttribute = Quill.import('attributors/attribute/direction');
        Quill.register(DirectionAttribute,true);
        var AlignClass = Quill.import('attributors/class/align');
        Quill.register(AlignClass,true);
        var BackgroundClass = Quill.import('attributors/class/background');
        Quill.register(BackgroundClass,true);
        var ColorClass = Quill.import('attributors/class/color');
        Quill.register(ColorClass,true);
        var DirectionClass = Quill.import('attributors/class/direction');
        Quill.register(DirectionClass,true);
        var FontClass = Quill.import('attributors/class/font');
        Quill.register(FontClass,true);
        var SizeClass = Quill.import('attributors/class/size');
        Quill.register(SizeClass,true);
        var AlignStyle = Quill.import('attributors/style/align');
        Quill.register(AlignStyle,true);
        var BackgroundStyle = Quill.import('attributors/style/background');
        Quill.register(BackgroundStyle,true);
        var ColorStyle = Quill.import('attributors/style/color');
        Quill.register(ColorStyle,true);
        var DirectionStyle = Quill.import('attributors/style/direction');
        Quill.register(DirectionStyle,true);
        var FontStyle = Quill.import('attributors/style/font');
        Quill.register(FontStyle,true);
        var SizeStyle = Quill.import('attributors/style/size');
        SizeStyle.whitelist = ['12px','14px','16px','18px','24px','28px','32px','40px','48px','56px','64px','72px'];
        Quill.register(SizeStyle,true);
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
            $('#panorama_markers').css('height',container_h+'px');
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
            check_plan(window.id_user,'marker');
            if(window.can_create) {
                $('#plan_marker_msg').addClass('d-none');
            } else {
                $('#plan_marker_msg').removeClass('d-none');
            }
            setTimeout(function () {
                get_rooms(window.id_virtualtour,'marker');
                get_icon_images_m(window.id_virtualtour,'marker_h');
            },200);
            IconPicker.Init({
                jsonUrl: 'vendor/iconpicker/iconpicker-1.5.0.json',
                searchPlaceholder: '<?php echo _("Search Icon"); ?>',
                showAllButton: '<?php echo _("Show All"); ?>',
                cancelButton: '<?php echo _("Cancel"); ?>',
                noResultsFound: '<?php echo _("No results found."); ?>',
                borderRadius: '20px',
                appendTo: document.getElementById('marker_editor_div')
            });
            IconPicker.Run('#GetIconPicker', function(){
                window.markers[marker_index_edit].icon = $('#marker_icon').val();
                render_marker(window.marker_id_edit,window.marker_index_edit);
            });
            window.marker_color_spectrum = $('#marker_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#marker_editor_div',
                move: function(color) {
                    window.markers[marker_index_edit].color = color.toHexString();
                    render_marker(window.marker_id_edit,window.marker_index_edit);
                },
                change: function(color) {
                    window.markers[marker_index_edit].color = color.toHexString();
                    render_marker(window.marker_id_edit,window.marker_index_edit);
                }
            });
            window.marker_background_spectrum = $('#marker_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#marker_editor_div',
                move: function(color) {
                    window.markers[marker_index_edit].background = color.toRgbString();
                    render_marker(window.marker_id_edit,window.marker_index_edit);
                },
                change: function(color) {
                    window.markers[marker_index_edit].background = color.toRgbString();
                    render_marker(window.marker_id_edit,window.marker_index_edit);
                }
            });
            window.tooltip_background_spectrum = $('#tooltip_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#marker_editor_div',
                move: function(color) {
                    window.markers[marker_index_edit].tooltip_background = color.toRgbString();
                    $('#tooltip_text_html').css('background-color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('background-color',color.toRgbString());
                },
                change: function(color) {
                    window.markers[marker_index_edit].tooltip_background = color.toRgbString();
                    $('#tooltip_text_html').css('background-color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('background-color',color.toRgbString());
                }
            });
            window.tooltip_color_spectrum = $('#tooltip_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#marker_editor_div',
                move: function(color) {
                    window.markers[marker_index_edit].tooltip_color = color.toHexString();
                    $('#tooltip_text_html').css('color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('color',color.toRgbString());
                },
                change: function(color) {
                    window.markers[marker_index_edit].tooltip_color = color.toHexString();
                    $('#tooltip_text_html').css('color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('color',color.toRgbString());
                }
            });
            $('#btn_add_marker').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'left'
            });
            $('#btn_switch_to_poi').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'right'
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
            $('.lottie_icon_list').each(function () {
                var id = $(this).attr('data-id');
                var image = $(this).attr('data-image');
                var id_vt = $(this).attr('data-id_vt');
                if(window.s3_enabled==1 && id_vt!='') {
                    var json_url = window.s3_url+'viewer/icons/'+image;
                } else {
                    var json_url = '../viewer/icons/'+image;
                }
                bodymovin.loadAnimation({
                    container: document.getElementById('lottie_icon_'+id),
                    renderer: 'svg',
                    loop: true,
                    autoplay: true,
                    path: json_url,
                    rendererSettings: {
                        progressiveLoad: true,
                    }
                });
            });
            if(!window.is_mobile) {
                $('#confirm_edit').draggable({
                    cursor: "move",
                    handle: ".move_box_edit",
                    containment: "#marker_editor_div",
                    start: function() {
                        var parentRect = $(this).parent()[0].getBoundingClientRect();
                        var rect = this.getBoundingClientRect();
                        $(this).css('transition', 'all 0 ease 0');
                        $(this).css('transform', 'none');
                        $(this).css('left', rect['left']-parentRect['left']);
                    }
                });
                $('#confirm_move').draggable({
                    cursor: "move",
                    handle: ".move_box_move",
                    containment: "#poi_editor_div",
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
                $('.move_box_move').hide();
            }
        });
        $(window).resize(function () {
            if(window.is_fullscreen) {
                if($('.rooms_slider').is(':visible')) {
                    var h_p = 75;
                } else {
                    var h_p = 0;
                }
                $('#panorama_markers').css('height','calc(100% - '+h_p+'px)');
                try {
                    $('#video_viewer').css('height','100%');
                } catch (e) {}
            } else {
                if($('.rooms_slider').is(':visible')) {
                    var h_p = 225;
                } else {
                    var h_p = 225-75;
                }
                var container_h = $('#content-wrapper').height() - h_p;
                $('#panorama_markers').css('height',container_h+'px');
                try {
                    $('#video_viewer').css('height',container_h+'px');
                } catch (e) {}
            }
            var poi_embed_count = $('.poi_embed').length;
            if(poi_embed_count>0) {
                setTimeout(function () {
                    adjust_poi_embed_helpers_all();
                },50);
            }
            var marker_embed_count = $('.marker_embed').length;
            if(marker_embed_count>0) {
                setTimeout(function () {
                    adjust_marker_embed_helpers_all();
                },50);
            }
        });
        $('#modal_markers_style_apply').on('shown.bs.modal', function() {
            $('#modal_markers_style_apply input[type="checkbox"]').prop('checked', true);
            $('#modal_markers_style_apply #set_as_default').prop('checked', false);
        });
        $('#sidebarToggle').click(function() {
            window.sync_poi_embed_enabled = false;
            window.sync_marker_embed_enabled = false;
            $('.draggable_poi_embed').remove();
            $('.draggable_marker_embed').remove();
            setTimeout(function() {
                jQuery.each(window.markers, function(index, marker) {
                    switch(marker.what) {
                        case 'marker':
                            render_marker(parseInt(marker.id),index);
                            break;
                        case 'poi':
                            render_poi_embed_m(parseInt(marker.id),index);
                            break;
                    }
                });
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
                }
                container.hide();
                $('#slider_hs_list .list-group button').removeClass('active');
            }
        });

        window.open_modal_library_icons = function () {
            if(window.gallery_dropzone_im==null) {
                if($('#gallery-dropzone-im').length) {
                    window.gallery_dropzone_im = new Dropzone("#gallery-dropzone-im", {
                        url: "ajax/upload_icon_image.php",
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
                        acceptedFiles: 'image/*,application/json'
                    });
                    window.gallery_dropzone_im.on("addedfile", function(file) {
                        $('#list_images_im').addClass('disabled');
                    });
                    window.gallery_dropzone_im.on("success", function(file,rsp) {
                        add_image_to_icon_m(id_virtualtour,rsp,'marker_h');
                    });
                    window.gallery_dropzone_im.on("queuecomplete", function() {
                        $('#list_images_im').removeClass('disabled');
                        window.gallery_dropzone_im.removeAllFiles();
                    });
                }
            }
            $('#modal_library_icons').modal('show');
        }
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
            $('.modal_fs_container .modal-backdrop').hide();
            $(window).trigger('resize');
        }
        setTimeout(function() {
            init_poi_embed(true);
            init_marker_embed(true);
        },0);
    }
</script>