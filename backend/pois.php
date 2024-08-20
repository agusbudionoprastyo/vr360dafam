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
$settings = get_settings();
$plan_permissions = get_plan_permission($id_user);
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
$code_vt = $virtual_tour['code'];
$icons_library = $plan_permissions['enable_icons_library'];
$products_permission = $plan_permissions['enable_shop'];
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
    if($editor_permissions['icons_library']==0) {
        $icons_library = 0;
    }
    if($editor_permissions['shop']==0) {
        $products_permission = 0;
    }
    if($editor_permissions['create_pois']==1) {
        $create_permission=true;
    } else {
        $create_permission=false;
    }
    if($editor_permissions['edit_pois']==1) {
        $edit_permission=true;
    } else {
        $edit_permission=false;
    }
    if($editor_permissions['delete_pois']==1) {
        $delete_permission=true;
    } else {
        $delete_permission=false;
    }
} else {
    $create_permission=true;
    $edit_permission=true;
    $delete_permission=true;
}
$_SESSION['compress_jpg'] = $virtual_tour['compress_jpg'];
$_SESSION['max_width_compress'] = $virtual_tour['max_width_compress'];
$_SESSION['keep_original_panorama'] = $virtual_tour['keep_original_panorama'];
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

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create POIs on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<div id="plan_poi_msg" class="card bg-warning text-white shadow mb-4 d-none">
    <div class="card-body">
        <?php echo _("You have reached the maximum number of POIs allowed from your plan!")." ".$msg_change_plan; ?>
    </div>
</div>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel='stylesheet' type="text/css" href="https://fonts.googleapis.com/css?family=<?php echo $virtual_tour['font_viewer']; ?>">
<style>
    .callout_title_text, .callout_description_text, .poi_embed_text *  { font-family: '<?php echo $virtual_tour['font_viewer']; ?>', sans-serif; }
</style>

<div class="row">
    <div class="col-md-12 mb-1">
        <div class="card shadow mb-12">
            <div class="card-body p-0">
                <div class="col-md-12 p-0">
                    <p style="display: none;" id="msg_sel_room" class="text-center mt-2 mb-1"><?php echo _("Select a room first!"); ?></p>
                    <p style="display: none;padding: 15px 15px 0;" id="msg_no_room"><?php echo sprintf(_('No rooms created for this Virtual Tour. Go to %s and create a new one!'),'<a href="index.php?p=rooms">'._("Rooms").'</a>'); ?></p>
                    <div id="poi_editor_div" style="position: relative;background: white;">
                        <div class="modal_fs_container">
                            <div style="display:none;" class="modal-backdrop show"></div>
                            <div id="modal_add_poi" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div style="width: 90%;max-width: 800px;" class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Add POI"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <div id="div_poi_select_style" class="col-md-12 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                                <p class="mb-0"><?php echo _("Style"); ?> <i title="<?php echo _("style of the POI displayed within the panorama"); ?>" class="help_t fas fa-question-circle"></i></p>
                                                <div class="dropdown">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdown_poi_style" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-center" aria-labelledby="dropdown_poi_style">
                                                        <a onclick="select_poi_style('icon');" id="btn_style_icon" class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?></a>
                                                        <a onclick="select_poi_style('callout');" id="btn_style_callout" class="dropdown-item" href="#"><i class="fas fa-bullseye"></i> <?php echo _("Callout Text"); ?></a>
                                                        <a onclick="select_poi_style('embed_selection');" id="btn_style_embed_selection" class="dropdown-item" href="#"><i class="far fa-square"></i> <?php echo _("Selection Area"); ?></a>
                                                        <a onclick="select_poi_style('embed_image');" id="btn_style_embed_image" class="dropdown-item" href="#"><i class="fas fa-image"></i> <?php echo _("Embedded Image"); ?></a>
                                                        <a onclick="select_poi_style('embed_text');" id="btn_style_embed_text" class="dropdown-item" href="#"><i class="fas fa-heading"></i> <?php echo _("Embedded Text"); ?></a>
                                                        <a onclick="select_poi_style('embed_html');" id="btn_style_embed_html" class="dropdown-item" href="#"><i class="fas fa-code"></i> <?php echo _("Embedded HTML"); ?></a>
                                                        <a onclick="select_poi_style('embed_gallery');" id="btn_style_embed_gallery" class="dropdown-item" href="#"><i class="fas fa-images"></i> <?php echo _("Embedded Slideshow"); ?></a>
                                                        <a onclick="select_poi_style('embed_video');" id="btn_style_embed_video" class="dropdown-item" href="#"><i class="fab fa-youtube"></i> <?php echo _("Embedded Video"); ?></a>
                                                        <a onclick="select_poi_style('embed_video_transparent');" id="btn_style_embed_video_transparent" class="dropdown-item" href="#"><i class="fas fa-video"></i> <?php echo _("Embedded Video (with transparency)"); ?></a>
                                                        <a onclick="select_poi_style('embed_video_chroma');" id="btn_style_embed_video_chroma" class="dropdown-item" href="#"><i class="fas fa-video"></i> <?php echo _("Embedded Video (with background removal)"); ?></a>
                                                        <a onclick="select_poi_style('embed_link');" id="btn_style_embed_link" class="dropdown-item" href="#"><i class="fas fa-external-link-alt"></i> <?php echo _("Embedded Link"); ?></a>
                                                        <a onclick="select_poi_style('embed_object3d');" id="btn_style_embed_object3d" class="dropdown-item" href="#"><i class="fas fa-cube"></i> <?php echo _("Embedded Object 3D"); ?></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="div_poi_select_content" class="col-md-12 mt-2 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                                <p class="mb-0"><?php echo _("Content"); ?> <i title="<?php echo _("content displayed when clicked on the POI"); ?>" class="help_t fas fa-question-circle"></i></p>
                                                <button onclick="new_poi('');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-ban"></i></div>
                                                    <?php echo _("None"); ?>
                                                </button>
                                                <button id="btn_new_poi_image" onclick="new_poi('image');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-image"></i></div>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <?php echo _("Image (single)"); ?>
                                                </button>
                                                <button onclick="new_poi('gallery');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-images"></i></div>
                                                    <?php echo _("Images (gallery)"); ?>
                                                </button>
                                                <button onclick="new_poi('video');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fab fa-youtube"></i></div>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <?php echo _("Video"); ?>
                                                </button>
                                                <button onclick="new_poi('video360');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-video"></i></div>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <?php echo _("Video 360"); ?>
                                                </button>
                                                <button onclick="new_poi('lottie');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fab fa-deviantart"></i></div>
                                                    Lottie
                                                </button>
                                                <button onclick="new_poi('audio');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-music"></i></div>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <?php echo _("Audio"); ?>
                                                </button>
                                                <button onclick="new_poi('link');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-link"></i></div>
                                                    <?php echo _("Link (embed)"); ?>
                                                </button>
                                                <button onclick="new_poi('link_ext');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-external-link-alt"></i></div>
                                                    <?php echo _("Link (external)"); ?>
                                                </button>
                                                <button id="btn_new_poi_text" onclick="new_poi('html');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <div style='text-align:center;'><i class="fas fa-heading"></i></div>
                                                    <?php echo _("Text"); ?>
                                                </button>
                                                <button onclick="new_poi('html_sc');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-code"></i></div>
                                                    <?php echo _("Html"); ?>
                                                </button>
                                                <button onclick="new_poi('download');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-download"></i></div>
                                                    <?php echo _("Download"); ?>
                                                </button>
                                                <button onclick="new_poi('pdf');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-file-pdf"></i></div>
                                                    PDF
                                                </button>
                                                <button onclick="new_poi('form');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fab fa-wpforms"></i></div>
                                                    <?php echo _("Form"); ?>
                                                </button>
                                                <button onclick="new_poi('google_maps');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-map"></i></div>
                                                    <?php echo _("Map"); ?>
                                                </button>
                                                <button onclick="new_poi('object360');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-compact-disc"></i></div>
                                                    <?php echo _("Object 360 (images)"); ?>
                                                </button>
                                                <button onclick="new_poi('object3d');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-cube"></i></div>
                                                    <span style="top:-3px;right:-3px;font-size:10px;" class="badge badge-pill badge-secondary position-absolute"><i class="fas fa-vr-cardboard"></i></span>
                                                    <?php echo _("Object 3D")." (GLB/GLTF)"; ?>
                                                </button>
                                                <button onclick="new_poi('pointclouds');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-dice-d20"></i></div>
                                                    <?php echo _("Point Clouds"); ?>
                                                </button>
                                                <button onclick="new_poi('product');" style="min-width:100px;" class='btn btn-info mb-1 position-relative <?php echo ($products_permission==0) ? 'd-none' : ''; ?>'>
                                                    <div style='text-align:center;'><i class="fas fa-shopping-cart"></i></div>
                                                    <?php echo _("Product"); ?>
                                                </button>
                                                <button onclick="new_poi('switch_pano');" style="min-width:100px;" class='btn btn-info mb-1 position-relative'>
                                                    <div style='text-align:center;'><i class="fas fa-sync-alt"></i></div>
                                                    <?php echo _("Switch Panorama"); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_delete_poi" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Delete POI"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to delete the poi?"); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_poi" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_duplicate_poi" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Duplicate POI"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to duplicate the poi?"); ?></p>
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Room Target"); ?></label>
                                                <select data-live-search="true" onchange="" id="room_target" class="form-control"></select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <?php if($create_content) : ?><button <?php echo ($demo) ? 'disabled':''; ?> id="btn_duplicate_poi" onclick="" type="button" class="btn btn-success"><i class="fas fa-clone"></i> <?php echo _("Yes, Duplicate"); ?></button><?php endif; ?>
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
                                                <?php if($upload_content) : ?><form action="ajax/upload_icon_image.php" class="dropzone noselect" id="gallery-dropzone-ip"></form><?php endif; ?>
                                            </div>
                                            <div id="list_images_ip"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_images_gallery" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" style="width: 90% !important; max-width: 90% !important; margin: 0 auto !important;" role="document">
                                    <div class="modal-content">
                                        <div class="modal-body"></div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_media_library" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" style="width: 90% !important; max-width: 90% !important; margin: 0 auto !important;" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Media Library"); ?></h5>
                                        </div>
                                        <div class="modal-body"></div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_music_library" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" style="width: 90% !important; max-width: 90% !important; margin: 0 auto !important;" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("Music Library"); ?></h5>
                                        </div>
                                        <div class="modal-body"></div>
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

                            <div id="modal_pois_style_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("POIs Settings"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to apply these settings to all existing POIs by overwriting them?"); ?></p>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_style"><?php echo _("Type"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_style" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_icon"><?php echo _("Icon"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_icon" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_animation"><?php echo _("Animation"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_animation" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_color"><?php echo _("Color"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_color" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_background"><?php echo _("Background"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_background" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_icon_type"><?php echo _("Style"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_icon_type" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_tooltip_type"><?php echo _("Tooltip Type"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_tooltip_type" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_tooltip_visibility"><?php echo _("Tooltip Visibility"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_tooltip_visibility" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_tooltip_background"><?php echo _("Tooltip Background"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_tooltip_background" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_tooltip_color"><?php echo _("Tooltip Color"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_tooltip_color" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="apply_poi_sound"><?php echo _("Sound"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_sound" checked />
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
                                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_styles('pois_e');" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_pois_move_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo _("POIs Settings"); ?></h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><?php echo _("Are you sure you want to apply these settings to all existing POIs by overwriting them?"); ?></p>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_poi_perspective"><?php echo _("Perspective"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_perspective" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_poi_size"><?php echo _("Size"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_size" checked />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="apply_poi_scale"><?php echo _("Scale"); ?></label><br>
                                                        <input type="checkbox" id="apply_poi_scale" checked />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_moves('pois');" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="div_panorama_container" id="panorama_pois"></div>
                        <div style="display:none" id="canvas_p"></div>
                        <div class="rooms_view_sel noselect"></div>
                        <div class="icon_visible_view noselect">
                            <label>
                                <input checked onchange="toggle_visible_view('poi')" id="check_visibile_view" type="checkbox" />&nbsp;&nbsp;<?php echo _("shows items that are only visible in this view"); ?>
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
                            <div class="poi_edit_label"></div>
                            <i title="<?php echo _("MOVE"); ?>" onclick="" class="move_action fa fa-arrows-alt <?php echo (!$edit_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("EDIT"); ?>" onclick="" class="edit_action fa fa-edit <?php echo (!$edit_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("DUPLICATE"); ?>" onclick="" class="duplicate_action fa fa-clone <?php echo (!$edit_permission) ? 'disabled' : ''; ?>"></i>
                            <i title="<?php echo _("DELETE"); ?>" onclick="" class="delete_action fa fa-trash <?php echo (!$delete_permission) ? 'disabled' : ''; ?>"></i>
                        </div>
                        <div id="confirm_background_removal">
                            <div class="row">
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label for="chroma_color"><?php echo _("Background Color"); ?></label>
                                        <input type="text" id="chroma_color" class="form-control form-control-sm" value="" />
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <input style="display:none;" id="chroma_color_<?php echo $lang; ?>" type="text" class="form-control form-control-sm input_lang" data-target-id="chroma_color" data-lang="<?php echo $lang; ?>" value="" />
                                            <?php endif;
                                        } ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label for="chroma_tolerance"><?php echo _("Tolerance"); ?></label>
                                        <input oninput="change_chroma_tolerance()" min="0" max="255" step="1" id="chroma_tolerance" type="range" class="form-control-range" value="0" />
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <input style="display:none;" id="chroma_tolerance_<?php echo $lang; ?>" oninput="change_chroma_tolerance_lang('<?php echo $lang; ?>')" min="0" max="255" step="1" type="range" class="form-control-range input_lang" data-target-id="chroma_tolerance" data-lang="<?php echo $lang; ?>" value="" />
                                            <?php endif;
                                        } ?>
                                    </div>
                                </div>
                            </div>
                            <span onclick="confirm_background_removal()" class="btn_confirm"><?php echo _("Apply"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            <span onclick="close_background_removal()" class="btn_close_2"><i class="fas fa-times"></i></span>
                        </div>
                        <div id="confirm_edit">
                            <ul style="margin-left:25px;width:calc(100% - 85px);" class="nav nav-pills justify-content-center mb-1" id="edit-tab" role="tablist">
                                <li class="nav-item">
                                    <a onclick="show_poi_apply_style(false);show_poi_confirm(false);maximize_box_edit();" class="nav-link" id="pills-settings-tab" data-toggle="pill" href="#pills-settings" role="tab" aria-controls="pills-setting" aria-selected="true"><i class="fas fa-cog"></i> <?php echo strtoupper(_("Settings")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_poi_apply_style(false);show_poi_confirm(true);maximize_box_edit();" class="nav-link active" id="pills-edit-tab" data-toggle="pill" href="#pills-edit" role="tab" aria-controls="pills-edit" aria-selected="true"><i class="fas fa-photo-video"></i> <?php echo strtoupper(_("Content")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_poi_apply_style(true);show_poi_confirm(true);maximize_box_edit();" class="nav-link" id="pills-style-tab" data-toggle="pill" href="#pills-style" role="tab" aria-controls="pills-style" aria-selected="false"><i class="fas fa-palette"></i> <?php echo strtoupper(_("Style")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_poi_apply_style(false);show_poi_confirm(true);maximize_box_edit();" class="nav-link" id="pills-tooltip-tab" data-toggle="pill" href="#pills-tooltip" role="tab" aria-controls="pills-tooltip" aria-selected="false"><i class="fas fa-comment-dots"></i> <?php echo strtoupper(_("Tooltip")); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a onclick="show_poi_apply_style(false);show_poi_confirm(true);maximize_box_edit();" class="nav-link" id="pills-schedule-tab" data-toggle="pill" href="#pills-schedule" role="tab" aria-controls="pills-schedule" aria-selected="false"><i class="far fa-clock"></i> <?php echo strtoupper(_("Schedule")); ?></a>
                                </li>
                                <i class="fas fa-arrows-alt move_box_edit"></i>
                                <i onclick="minimize_box_edit();" class="fas fa-minus minimize_box_edit"></i>
                                <span class="btn_close"><i class="fas fa-times"></i></span>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <hr>
                                <div style="min-height:420px;" class="tab-pane fade" id="pills-settings" role="tabpanel" aria-labelledby="pills-settings-tab">
                                    <div class="row" style="margin-bottom: 10px">
                                        <div class="col-md-12 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                            <p class="mb-0"><?php echo _("Style"); ?> <i title="<?php echo _("style of the POI displayed within the panorama"); ?>" class="help_t fas fa-question-circle"></i></p>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdown_poi_style_edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-center" aria-labelledby="dropdown_poi_style_edit">
                                                    <a onclick="select_poi_style_edit('icon');" id="btn_edit_style_icon" class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> <?php echo _("Icon"); ?></a>
                                                    <a onclick="select_poi_style_edit('callout');" id="btn_edit_style_callout" class="dropdown-item" href="#"><i class="fas fa-bullseye"></i> <?php echo _("Callout Text"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_selection');" id="btn_edit_style_embed_selection" class="dropdown-item" href="#"><i class="far fa-square"></i> <?php echo _("Selection Area"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_image');" id="btn_edit_style_embed_image" class="dropdown-item" href="#"><i class="fas fa-image"></i> <?php echo _("Embedded Image"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_text');" id="btn_edit_style_embed_text" class="dropdown-item" href="#"><i class="fas fa-heading"></i> <?php echo _("Embedded Text"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_html');" id="btn_edit_style_embed_html" class="dropdown-item" href="#"><i class="fas fa-code"></i> <?php echo _("Embedded HTML"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_gallery');" id="btn_edit_style_embed_gallery" class="dropdown-item" href="#"><i class="fas fa-images"></i> <?php echo _("Embedded Slideshow"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_video');" id="btn_edit_style_embed_video" class="dropdown-item" href="#"><i class="fab fa-youtube"></i> <?php echo _("Embedded Video"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_video_transparent');" id="btn_edit_style_embed_video_transparent" class="dropdown-item" href="#"><i class="fas fa-video"></i> <?php echo _("Embedded Video (with transparency)"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_video_chroma');" id="btn_edit_style_embed_video_chroma" class="dropdown-item" href="#"><i class="fas fa-video"></i> <?php echo _("Embedded Video (with background removal)"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_link');" id="btn_edit_style_embed_link" class="dropdown-item" href="#"><i class="fas fa-external-link-alt"></i> <?php echo _("Embedded Link"); ?></a>
                                                    <a onclick="select_poi_style_edit('embed_object3d');" id="btn_edit_style_embed_object3d" class="dropdown-item" href="#"><i class="fas fa-cube"></i> <?php echo _("Embedded Object 3D"); ?></a>
                                                </div>
                                                <button id="btn_change_poi_embed_style" onclick="change_poi_embed_style();" class="btn btn-sm btn-success disabled"><i class="fas fa-arrow-right"></i> <?php echo _("Change"); ?></button>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mt-2 text-center <?php echo ($demo) ? 'disabled_d':''; ?>">
                                            <p class="mb-0"><?php echo _("Content"); ?> <i title="<?php echo _("content displayed when clicked on the POI"); ?>" class="help_t fas fa-question-circle"></i></p>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdown_poi_content_edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-ban"></i> <?php echo _("None"); ?>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-center multi-column columns-2" aria-labelledby="dropdown_poi_content_edit">
                                                    <div class="row">
                                                        <div class="col-sm-12 col-md-6">
                                                            <a onclick="select_poi_content_edit('none');" id="btn_edit_content_none" class="dropdown-item" href="#"><i class="fas fa-ban"></i> <?php echo _("None"); ?></a>
                                                            <a onclick="select_poi_content_edit('image');" id="btn_edit_content_image" class="dropdown-item" href="#"><i class="fas fa-image"></i> <?php echo _("Image (single)"); ?></a>
                                                            <a onclick="select_poi_content_edit('gallery');" id="btn_edit_content_gallery" class="dropdown-item" href="#"><i class="fas fa-images"></i> <?php echo _("Image (gallery)"); ?></a>
                                                            <a onclick="select_poi_content_edit('video');" id="btn_edit_content_video" class="dropdown-item" href="#"><i class="fab fa-youtube"></i> <?php echo _("Video"); ?></a>
                                                            <a onclick="select_poi_content_edit('video360');" id="btn_edit_content_video360" class="dropdown-item" href="#"><i class="fas fa-video"></i> <?php echo _("Video 360"); ?></a>
                                                            <a onclick="select_poi_content_edit('lottie');" id="btn_edit_content_lottie" class="dropdown-item" href="#"><i class="fab fa-deviantart"></i> Lottie</a>
                                                            <a onclick="select_poi_content_edit('audio');" id="btn_edit_content_audio" class="dropdown-item" href="#"><i class="fas fa-music"></i> <?php echo _("Audio"); ?></a>
                                                            <a onclick="select_poi_content_edit('link');" id="btn_edit_content_link" class="dropdown-item" href="#"><i class="fas fa-external-link-alt"></i> <?php echo _("Link (embed)"); ?></a>
                                                            <a onclick="select_poi_content_edit('link_ext');" id="btn_edit_content_link_ext" class="dropdown-item" href="#"><i class="fas fa-external-link-alt"></i> <?php echo _("Link (external)"); ?></a>
                                                            <a onclick="select_poi_content_edit('html');" id="btn_edit_content_html" class="dropdown-item" href="#"><i class="fas fa-heading"></i> <?php echo _("Text"); ?></a>
                                                        </div>
                                                        <div class="col-sm-12 col-md-6">
                                                            <a onclick="select_poi_content_edit('html_sc');" id="btn_edit_content_html_sc" class="dropdown-item" href="#"><i class="fas fa-code"></i> <?php echo _("Html"); ?></a>
                                                            <a onclick="select_poi_content_edit('download');" id="btn_edit_content_download" class="dropdown-item" href="#"><i class="fas fa-download"></i> <?php echo _("Download"); ?></a>
                                                            <a onclick="select_poi_content_edit('pdf');" id="btn_edit_content_pdf" class="dropdown-item" href="#"><i class="fas fa-file-pdf"></i> PDF</a>
                                                            <a onclick="select_poi_content_edit('form');" id="btn_edit_content_form" class="dropdown-item" href="#"><i class="fab fa-wpforms"></i> <?php echo _("Form"); ?></a>
                                                            <a onclick="select_poi_content_edit('google_maps');" id="btn_edit_content_google_maps" class="dropdown-item" href="#"><i class="fas fa-map"></i> <?php echo _("Map"); ?></a>
                                                            <a onclick="select_poi_content_edit('object360');" id="btn_edit_content_object360" class="dropdown-item" href="#"><i class="fas fa-compact-disc"></i> <?php echo _("Object 360 (images)"); ?></a>
                                                            <a onclick="select_poi_content_edit('object3d');" id="btn_edit_content_object3d" class="dropdown-item" href="#"><i class="fas fa-cube"></i> <?php echo _("Object 3D")." (GLB/GLTF)"; ?></a>
                                                            <a onclick="select_poi_content_edit('pointclouds');" id="btn_edit_content_pointclouds" class="dropdown-item" href="#"><i class="fas fa-dice-d20"></i> <?php echo _("Point Clouds"); ?></a>
                                                            <a onclick="select_poi_content_edit('product');" id="btn_edit_content_product" class="dropdown-item <?php echo ($products_permission==0) ? 'd-none' : ''; ?>" href="#"><i class="fas fa-shopping-cart"></i> <?php echo _("Product"); ?></a>
                                                            <a onclick="select_poi_content_edit('switch_pano');" id="btn_edit_content_switch_pano" class="dropdown-item" href="#"><i class="fas fa-sync-alt"></i> <?php echo _("Switch Panorama"); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button id="btn_change_poi_content" onclick="change_poi_content();" class="btn btn-sm btn-success disabled"><i class="fas fa-arrow-right"></i> <?php echo _("Change"); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade show active" id="pills-edit" role="tabpanel" aria-labelledby="pills-edit-tab">
                                    <div style="display: none" id="div_form_edit">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 0px" for="form_title"><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_title'); ?>
                                                    <input id="form_title" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_title" data-lang="<?php echo $lang; ?>" value="" />
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 0px" for="form_button"><?php echo _("Button send"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_button'); ?>
                                                    <input id="form_button" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_button" data-lang="<?php echo $lang; ?>" value="" />
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 0px" for="form_response"><?php echo _("Reply message"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_response'); ?>
                                                    <input id="form_response" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_response" data-lang="<?php echo $lang; ?>" value="" />
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 0px" for="form_description"><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_description'); ?>
                                                    <input id="form_description" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_description" data-lang="<?php echo $lang; ?>" value="" />
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4 <?php echo (empty($settings['privacy_policy'])) ? 'd-none':''; ?>">
                                                <div class="form-group">
                                                    <label style="margin-bottom: 1px"><?php echo _("Show Privacy Policy"); ?></label><br>
                                                    <input id="form_privacy_policy" type="checkbox">
                                                </div>
                                            </div>
                                            <div class="col-md-4 <?php echo (!$settings['smtp_valid']) ? 'd-none':''; ?>">
                                                <div class="form-group">
                                                    <label style="margin-bottom: 3px"><?php echo _("Send Notification"); ?></label><br>
                                                    <input id="form_send_email" type="checkbox">
                                                </div>
                                            </div>
                                            <div class="col-md-4 <?php echo (!$settings['smtp_valid']) ? 'd-none':''; ?>">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 1px" for="form_email"><?php echo _("E-Mail"); ?></label>
                                                    <input id="form_email" type="email" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                        </div>
                                        <hr style="margin: 3px">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 1px"><?php echo _("Background Color"); ?></label>
                                                    <input id="form_background" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 1px"><?php echo _("Text Color"); ?></label>
                                                    <input id="form_color" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 1px"><?php echo _("Button - Background Color"); ?></label>
                                                    <input id="form_background_button" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="margin-bottom: 3px" class="form-group">
                                                    <label style="margin-bottom: 1px"><?php echo _("Button - Text Color"); ?></label>
                                                    <input id="form_color_button" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                        </div>
                                        <hr style="margin: 3px">
                                        <?php for($i=1;$i<=10;$i++) { ?>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label style="margin-bottom: 0px">F.<?php echo $i; ?> <?php echo _("Enable"); ?></label><br>
                                                        <input id="form_field_<?php echo $i; ?>" type="checkbox">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label style="margin-bottom: 0px">F.<?php echo $i; ?> <?php echo _("Required"); ?></label><br>
                                                        <input id="form_field_required_<?php echo $i; ?>" type="checkbox">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div style="margin-bottom: 3px" class="form-group">
                                                        <label style="margin-bottom: 0px">F.<?php echo $i; ?> <?php echo _("Type"); ?></label><br>
                                                        <select onchange="change_form_field_type(<?php echo $i; ?>);" id="form_field_type_<?php echo $i; ?>" class="form-control form-control-sm">
                                                            <option id="text" value="text"><?php echo _("Text"); ?></option>
                                                            <option id="number" value="number"><?php echo _("Number"); ?></option>
                                                            <option id="tel" value="tel"><?php echo _("Phone"); ?></option>
                                                            <option id="email" value="email"><?php echo _("E-Mail"); ?></option>
                                                            <option id="select" value="select"><?php echo _("Select"); ?></option>
                                                            <option id="checkbox" value="checkbox"><?php echo _("Checkbox"); ?></option>
                                                            <option id="date" value="date"><?php echo _("Date"); ?></option>
                                                            <option id="time" value="time"><?php echo _("Time"); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div style="margin-bottom: 3px" class="form-group">
                                                        <label style="margin-bottom: 0px">F.<?php echo $i; ?> <?php echo _("Label"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_field_label_'.$i); ?><br>
                                                        <input id="form_field_label_<?php echo $i; ?>" type="text" class="form-control form-control-sm" placeholder="">
                                                        <?php foreach ($array_languages as $lang) {
                                                            if($lang!=$default_language) : ?>
                                                                <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_field_label_<?php echo $i; ?>" data-lang="<?php echo $lang; ?>" value="" />
                                                            <?php endif;
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label><?php echo _("Displays the original panorama"); ?></label><br>
                                        <input onclick="change_switch_panorama_default()" id="switch_panorama_default" type="checkbox" />
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label id="content_label"><?php echo _("Content"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_content'); ?>
                                        <div class="input-group">
                                            <input id="poi_content" type="text" class="form-control form-control-sm bg-white" value="">
                                            <?php foreach ($array_languages as $lang) {
                                                if($lang!=$default_language) : ?>
                                                    <input style="display:none;" id="poi_content_<?php echo $lang; ?>" type="text" class="form-control form-control-sm bg-white input_lang" data-target-id="poi_content" data-lang="<?php echo $lang; ?>" value="">
                                                <?php endif;
                                            } ?>
                                            <div class="input-group-append">
                                                <button id="btn_preview_poi_content" onclick="preview_poi_content('poi_content');" class="btn btn-sm btn-secondary" type="button"><i class="fas fa-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label><?php echo _("Target"); ?></label>
                                        <select id="poi_target" class="form-control form-control-sm">
                                            <option selected id="_blank"><?php echo _("Blank"); ?></option>
                                            <option id="_self"><?php echo _("Self"); ?></option>
                                            <option id="_parent"><?php echo _("Parent"); ?></option>
                                            <option id="_top"><?php echo _("Top"); ?></option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label><?php echo _("Background Song Volume"); ?></label>
                                        <input min="0" max="1" step="0.1" id="poi_song_bg_volume" type="range" class="form-control-range" value="0" />
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label><?php echo _("Map Embed Code / Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_gm_map'); ?>
                                        <textarea rows="4" id="poi_gm_map" class="form-control"></textarea>
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <textarea style="display:none;" rows="4" id="poi_gm_map_<?php echo $lang; ?>" class="form-control input_lang" data-target-id="poi_gm_map" data-lang="<?php echo $lang; ?>"></textarea>
                                            <?php endif;
                                        } ?>
                                    </div>
                                    <div style="margin-bottom: 5px;" class="form-group">
                                        <label><?php echo _("Street View Embed Code / Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_gm_street'); ?>
                                        <textarea rows="4" id="poi_gm_street" class="form-control"></textarea>
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <textarea style="display:none;" rows="4" id="poi_gm_street_<?php echo $lang; ?>" class="form-control input_lang" data-target-id="poi_gm_street" data-lang="<?php echo $lang; ?>"></textarea>
                                            <?php endif;
                                        } ?>
                                    </div>
                                    <div id="div_content_text" style="margin-bottom: 5px;display: none" class="form-group">
                                        <label><?php echo _("Content - Text"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_content_html'); ?>
                                        <div><div id="poi_content_html"></div></div>
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <div style="display:none;"><div id="poi_content_html_<?php echo $lang; ?>" class="input_lang" data-target-id="poi_content_html" data-lang="<?php echo $lang; ?>"></div></div>
                                            <?php endif;
                                        } ?>
                                    </div>
                                    <div style="margin-bottom: 5px;display: none" class="form-group">
                                        <label><?php echo _("Content - Mixed"); ?></label>
                                        <div id="poi_content_mixed"></div>
                                    </div>
                                    <div style="margin-bottom: 5px;display: none" class="form-group">
                                        <label><?php echo _("Content - Html"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_content_html_sc'); ?>
                                        <div id="poi_content_html_sc"></div>
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                               <div style="display:none;" id="poi_content_html_sc_<?php echo $lang; ?>" class="input_lang" data-target-id="poi_content_html_sc" data-lang="<?php echo $lang; ?>"></div>
                                            <?php endif;
                                        } ?>
                                        <div class="mt-1 text-right">
                                            <button onclick="open_modal_media_library('all','html');return false;" class="btn btn-sm btn-primary"><?php echo _("Media Library"); ?></button>
                                        </div>
                                    </div>
                                    <div id="poi_content_product_div" style="margin-bottom: 5px;display: none" class="form-group">
                                        <label><?php echo _("Content - Product"); ?></label>
                                        <select data-live-search="true" data-show-subtext="true" title="<?php echo _("Choose a Product"); ?>" id="poi_content_product" class="form-control form-control-sm">
                                            <?php
                                            if($virtual_tour['shop_type']=='woocommerce') {
                                                echo get_option_products_wc($virtual_tour);
                                            } else {
                                                echo get_option_products($id_virtualtour_sel);
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div style="margin-bottom: 5px;display: none" class="form-group">
                                        <button onclick="edit_poi_gallery();" id="btn_poi_gallery" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i>&nbsp;&nbsp;<?php echo _("IMAGES GALLERY"); ?></button><br>
                                    </div>
                                    <div class="gallery_params_div">
                                        <div style="margin-bottom: 5px;" class="form-group mt-2">
                                            <label for="poi_gallery_random"><input id="poi_gallery_random" type="checkbox" /> <?php echo _("Random Order"); ?></label>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="g_transition"><?php echo _("Transition"); ?></label>
                                                    <select class="form-control form-control-sm" id="g_transition">
                                                        <option id="slideAppear"><?php echo _("Slide Appear"); ?></option>
                                                        <option selected id="swipe"><?php echo _("Swipe"); ?></option>
                                                        <option id="swipe2"><?php echo _("Swipe 2"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="g_thumbs"><?php echo _("Thumbnails"); ?></label>
                                                    <select class="form-control form-control-sm" id="g_thumbs">
                                                        <option id="none"><?php echo _("None"); ?></option>
                                                        <option selected id="bottomOverMedia"><?php echo _("Bottom Over Media"); ?></option>
                                                        <option id="bottom"><?php echo _("Bottom"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="g_autoplay"><?php echo _("Autoplay"); ?></label><br>
                                                    <input onchange="change_gallery_autoplay();" type="checkbox" id="g_autoplay">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="g_slide_duration"><?php echo _("Slide Duration"); ?></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" min="0.1" step="0.1" class="form-control form-control-sm" id="g_slide_duration" value="4" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">s</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 5px;display: none" class="form-group mt-3">
                                        <button onclick="edit_poi_object360();" id="btn_poi_object360" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i>&nbsp;&nbsp;<?php echo _("IMAGES OBJECT 360"); ?></button><br>
                                    </div>
                                    <input style="display:none;" id="switch_panorama_id" type="hiddem" />
                                    <?php if($upload_content) : ?>
                                    <form id="frm_sp_edit" action="ajax/upload_room_alt_image_poi.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo _("Panorama Image"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_sp_edit" name="txtFile_sp_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_sp_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_sp_edit" value="<?php echo _("Upload Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_sp_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_sp_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="frm_edit" action="ajax/upload_content_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name"><?php echo _("Image"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_edit" name="txtFile_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_edit" value="<?php echo _("Upload Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <button id="btn_media_library" onclick="open_modal_media_library('images','poi_content');return false;" class="btn btn-sm btn-block btn-primary"><?php echo _("Media Library"); ?></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="frm_j_edit" action="ajax/upload_content_json.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name"><?php echo _("Json"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_j_edit" name="txtFile_j_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_j_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_j_edit" value="<?php echo _("Upload Json"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_j_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_j_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form style="display: none" id="frm_d_edit" action="ajax/upload_content_file.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo _("File"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_d_edit" name="txtFile_d_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_d_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_d_edit" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_d_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_d_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form style="display: none" id="frm_pdf_edit" action="ajax/upload_content_pdf.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo _("PDF File"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_pdf_edit" name="txtFile_pdf_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_pdf_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_pdf_edit" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_pdf_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_pdf_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form style="display: none" id="frm_pc_edit" action="ajax/upload_content_pointclouds.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo _("ZIP File"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_pc_edit" name="txtFile_pc_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_pc_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_pc_edit" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_pc_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_pc_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form style="display: none" id="frm_g_edit" action="ajax/upload_content_3d.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo "GLTF/GLB + USDZ "._("Files"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_g_edit" name="txtFile_g_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_g_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_g_edit" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_g_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_g_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Interaction"); ?> <i title="<?php echo _("3d model can be rotated"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <select id="params_interaction" class="form-control form-control-sm">
                                                        <option id="0"><?php echo _("Disabled"); ?></option>
                                                        <option id="1"><?php echo _("Horizontal and Vertical"); ?></option>
                                                        <option id="2"><?php echo _("Only Horizontal"); ?></option>
                                                        <option id="3"><?php echo _("Only Vertical"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="params_ar">AR <?php echo _("Placement"); ?></label>
                                                    <select class="form-control form-control-sm" id="params_ar">
                                                        <option id="0"><?php echo _("Disabled"); ?></option>
                                                        <option id="floor"><?php echo _("Floor"); ?></option>
                                                        <option id="wall"><?php echo _("Wall"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Exposure"); ?> (<span id="params_exposure_label">1</span>) <i title="<?php echo _("controls the exposure of the 3d model"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input oninput="change_exposure();" id="params_exposure" type="range" min="0.1" max="2.0" step="0.01" val="1" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Environment"); ?></label><br>
                                                    <select id="params_environment" class="form-control form-control-sm">
                                                        <option id="neutral"><?php echo _("Neutral"); ?></option>
                                                        <option id="legacy"><?php echo _("Legacy"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Animation"); ?></label><br>
                                                    <select id="params_animation" class="form-control form-control-sm">
                                                        <option id="none"><?php echo _("None"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="frm_v_edit" action="ajax/upload_content_video.php?e=mp4" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name"><?php echo _("Video MP4 / WEBM"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_v_edit" name="txtFile_v_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_v_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_v_edit" value="<?php echo _("Upload Video"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <button onclick="open_modal_media_library('videos','poi_content');return false;" class="btn btn-sm btn-block btn-primary"><?php echo _("Media Library"); ?></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_v_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_v_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="frm_a_edit" action="ajax/upload_content_audio.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name"><?php echo _("Audio MP3"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_a_edit" name="txtFile_a_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_a_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_a_edit" value="<?php echo _("Upload Audio"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <button onclick="open_modal_music_library('poi_content');return false;" class="btn btn-sm btn-block btn-primary"><?php echo _("Music Library"); ?></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_a_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_a_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_title'); ?>
                                                <input id="poi_title" type="text" class="form-control form-control-sm" value="">
                                                <?php foreach ($array_languages as $lang) {
                                                    if($lang!=$default_language) : ?>
                                                        <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="poi_title" data-lang="<?php echo $lang; ?>" value="" />
                                                    <?php endif;
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_description'); ?>
                                                <input id="poi_description" type="text" class="form-control form-control-sm" value="">
                                                <?php foreach ($array_languages as $lang) {
                                                    if($lang!=$default_language) : ?>
                                                        <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="poi_description" data-lang="<?php echo $lang; ?>" value="" />
                                                    <?php endif;
                                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;display: none;" class="form-group">
                                                <label><?php echo _("View Mode"); ?></label><br>
                                                <select onchange="change_poi_view_type();" id="view_type" class="form-control form-control-sm">
                                                    <option id="0"><?php echo _("Modal"); ?></option>
                                                    <option id="1"><?php echo _("Box (click)"); ?></option>
                                                    <option id="2"><?php echo _("Box (Hover)"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;display: none;" class="form-group">
                                                <label><?php echo _("Box Position"); ?></label><br>
                                                <select id="box_pos" class="form-control form-control-sm">
                                                    <option id="left"><?php echo _("Left"); ?></option>
                                                    <option id="top"><?php echo _("Top"); ?></option>
                                                    <option id="right"><?php echo _("Right"); ?></option>
                                                    <option id="bottom"><?php echo _("Bottom"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Box Width"); ?> (px)</label>
                                                <input id="box_max_width" type="number" class="form-control form-control-sm" value="350">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Box Maximize Button"); ?></label><br>
                                                <input id="box_maximize" type="checkbox" checked>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="box_background"><?php echo _("Background Color"); ?></label>
                                                <input type="text" id="box_background" class="form-control form-control-sm" value="rgba(255,255,255,1)" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="box_color"><?php echo _("Text Color"); ?></label>
                                                <input type="text" id="box_color" class="form-control form-control-sm" value="#000000" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;display: none;" class="form-group">
                                                <label><?php echo _("Auto Open"); ?></label><br>
                                                <input id="id_poi_autoopen" type="checkbox">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;display: none;" class="form-group">
                                                <label><?php echo _("Auto Close"); ?> (ms)</label>
                                                <input id="auto_close" class="form-control form-control-sm" min="0" type="number" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-tooltip" role="tabpanel" aria-labelledby="pills-tooltip-tab">
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <label for="tooltip_type"><?php echo _("Type"); ?></label>
                                                <select onchange="change_tooltip_type_p();" id="tooltip_type" class="form-control form-control-sm">
                                                    <option id="none"><?php echo _("None"); ?></option>
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
                                            <div class="form-group" style="margin-bottom: 5px;">
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
                                    <div style="display: none" class="callout_settings">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_title"><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'callout_title'); ?>
                                                    <input oninput="change_callout_params()" id="callout_title" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input oninput="change_callout_params()" type="text" class="form-control form-control-sm input_lang" data-target-id="callout_title" data-lang="<?php echo $lang; ?>" value="">
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_description"><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'callout_description'); ?>
                                                    <input oninput="change_callout_params()" id="callout_description" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input oninput="change_callout_params()" type="text" class="form-control form-control-sm input_lang" data-target-id="callout_description" data-lang="<?php echo $lang; ?>" value="">
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_title_font_size"><?php echo _("Title - Size"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_title_font_size" type="number" min="1" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_title_font_color"><?php echo _("Title - Color"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_title_font_color" type="text" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_title_bg_color"><?php echo _("Title - Background"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_title_bg_color" type="text" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_title_margin"><?php echo _("Title - Margin"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_title_margin" type="number" min="0" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_description_font_size"><?php echo _("Description - Size"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_description_font_size" type="number" min="1" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_description_font_color"><?php echo _("Description - Color"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_description_font_color" type="text" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_content_bg_color"><?php echo _("Content - Background"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_content_bg_color" type="text" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_content_width"><?php echo _("Content - Width"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_content_width" type="number" min="1" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_content_height"><?php echo _("Content - Height"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_content_height" type="number" min="1" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_dir"><?php echo _("Direction"); ?></label>
                                                    <select onchange="change_callout_params()" id="callout_dir" type="text" class="form-control form-control-sm">
                                                        <option id="left"><?php echo _("Left"); ?></option>
                                                        <option id="right"><?php echo _("Right"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_rotate"><?php echo _("Rotation"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_rotate" type="range" min="0" max="180" step="1" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_line_size"><?php echo _("Line - Size"); ?></label>
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input oninput="change_callout_params()" id="callout_line_size" type="number" min="1" class="form-control form-control-sm" value="">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id="inputGroup-sizing-sm">px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_main_color"><?php echo _("Color"); ?></label>
                                                    <input oninput="change_callout_params()" id="callout_main_color" type="text" class="form-control form-control-sm" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div style="margin-bottom: 5px;" class="form-group">
                                                    <label for="callout_open"><?php echo _("Open on"); ?></label>
                                                    <select onchange="change_callout_params()" id="callout_open" type="text" class="form-control form-control-sm">
                                                        <option id="click"><?php echo _("Click"); ?></option>
                                                        <option id="hover"><?php echo _("Hover"); ?></option>
                                                        <option id="lookat"><?php echo _("Look at"); ?></option>
                                                        <option id="always_open"><?php echo _("Always Open"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <div>
                                                    <label id="embed_content_label"><?php echo _("Content - Image Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_embed_content'); ?>
                                                    <input id="poi_embed_content" type="text" class="form-control form-control-sm" value="">
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <input style="display:none;" id="poi_embed_content_<?php echo $lang; ?>" type="text" class="form-control form-control-sm input_lang" data-target-id="poi_embed_content" data-lang="<?php echo $lang; ?>" value="">
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                                <div>
                                                    <label><?php echo _("Content - Text"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_embed_content_html'); ?>
                                                    <div><div id="poi_embed_content_html"></div></div>
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <div style="display:none;"><div id="poi_embed_content_html_<?php echo $lang; ?>" class="input_lang" data-target-id="poi_embed_content_html" data-lang="<?php echo $lang; ?>"></div></div>
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                                <div>
                                                    <label><?php echo _("Content - HTML"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_embed_content_html_sc'); ?>
                                                    <div id="poi_embed_content_html_sc"></div>
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <div style="display:none;" id="poi_embed_content_html_sc_<?php echo $lang; ?>" class="input_lang" data-target-id="poi_embed_content_html_sc" data-lang="<?php echo $lang; ?>"></div>
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if($upload_content) : ?>
                                    <form class="mb-1" id="frm_edit_e" action="ajax/upload_content_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name"><?php echo _("Image"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_edit_e" name="txtFile_edit_e" />
                                                            <label class="custom-file-label text-left" for="txtFile_edit_e"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_edit_e" value="<?php echo _("Upload Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <button onclick="open_modal_media_library('images','poi_embed_content');return false;" class="btn btn-sm btn-block btn-primary"><?php echo _("Media Library"); ?></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_edit_e" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_edit_e"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                    <div style="display:none;" id="frm_v_edit_e_s" class="row">
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Autoplay"); ?></label><br>
                                                <input id="embed_video_autoplay" type="checkbox">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Muted"); ?></label><br>
                                                <input id="embed_video_muted" type="checkbox">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Loop"); ?></label><br>
                                                <input id="embed_video_loop" type="checkbox">
                                            </div>
                                        </div>
                                        <div style="display:none;" class="col-md-3">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <button onclick="open_background_removal();" id="btn_background_removal" class="btn btn-primary btn-sm disabled"><i class="fas fa-magic"></i> <?php echo _("Background Removal"); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if($upload_content) : ?>
                                    <form class="mb-1" id="frm_v_edit_e" action="ajax/upload_content_video.php?e=mp4" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label id="label_mp4"><?php echo _("Video MP4 / WEBM"); ?></label>
                                                    <label id="label_webm_mov"><?php echo _("Video WEBM + MOV"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_v_edit_e" name="txtFile_v_edit_e" />
                                                            <label class="custom-file-label text-left" for="txtFile_v_edit_e"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_v_edit_e" value="<?php echo _("Upload Video"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <button onclick="open_modal_media_library('videos','poi_embed_content');return false;" class="btn btn-sm btn-block btn-primary ml_btn"><?php echo _("Media Library"); ?></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_v_edit_e" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_v_edit_e"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <form class="mb-1" id="frm_td_edit_e" action="ajax/upload_content_3d.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>GLB/GLTF <?php echo _("File"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_td_edit_e" name="txtFile_td_edit_e" />
                                                            <label class="custom-file-label text-left" for="txtFile_td_edit_e"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_td_edit_e" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_td_edit_e" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_td_edit_e"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Interaction"); ?> <i title="<?php echo _("3d model can be rotated"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <select id="object3d_interaction" class="form-control form-control-sm">
                                                        <option id="0"><?php echo _("Disabled"); ?></option>
                                                        <option id="1"><?php echo _("Horizontal and Vertical"); ?></option>
                                                        <option id="2"><?php echo _("Only Horizontal"); ?></option>
                                                        <option id="3"><?php echo _("Only Vertical"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Exposure"); ?> (<span id="object3d_exposure_label">1</span>) <i title="<?php echo _("controls the exposure of the 3d model"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input oninput="change_exposure_m();" id="object3d_exposure" type="range" min="0.1" max="2.0" step="0.01" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Environment"); ?></label><br>
                                                    <select onchange="change_environment();" id="object3d_environment" class="form-control form-control-sm">
                                                        <option id="neutral"><?php echo _("Neutral"); ?></option>
                                                        <option id="legacy"><?php echo _("Legacy"); ?></option>
                                                        <option id="panorama"><?php echo _("Panorama"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Shadow Intensity"); ?> (<span id="object3d_shadow_intensity_label">1</span>)</label><br>
                                                    <input oninput="change_shadow_intensity();" id="object3d_shadow_intensity" type="range" min="0" max="1.0" step="0.01" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Shadow Softness"); ?> (<span id="object3d_shadow_softness_label">1</span>)</label><br>
                                                    <input oninput="change_shadow_softness();" id="object3d_shadow_softness" type="range" min="0" max="1.0" step="0.01" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label><?php echo _("Animation"); ?></label><br>
                                                    <select onchange="change_animation();" id="object3d_animation" class="form-control form-control-sm">
                                                        <option id="none"><?php echo _("None"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_style"><?php echo _("Type"); ?></label>
                                                <select onchange="change_poi_style()" id="poi_style" class="form-control form-control-sm">
                                                    <option id="2"><?php echo _("Icon + Label"); ?></option>
                                                    <option id="3"><?php echo _("Label + Icon"); ?></option>
                                                    <option id="0"><?php echo _("Only Icon"); ?></option>
                                                    <option id="4"><?php echo _("Only Label"); ?></option>
                                                    <option id="1"><?php echo _("Custom Icons Library"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_icon"><?php echo _("Icon"); ?></label><br>
                                                <button class="btn btn-sm btn-primary" type="button" id="GetIconPicker" data-iconpicker-input="input#poi_icon" data-iconpicker-preview="i#poi_icon_preview"><?php echo _("Select Icon"); ?></button>
                                                <input readonly type="hidden" id="poi_icon" name="Icon" value="fas fa-image" required="" placeholder="" autocomplete="off" spellcheck="false">
                                                <div style="vertical-align: middle;" class="icon-preview d-inline-block ml-1" data-toggle="tooltip" title="">
                                                    <i style="font-size: 24px;" id="poi_icon_preview" class="fas fa-image"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="display: none" class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="marker_library_icon"><?php echo _("Library Icon"); ?></label><br>
                                                <button onclick="open_modal_library_icons()" class="btn btn-sm btn-primary" type="button" id="btn_library_icon"><?php echo _("Select Library Icon"); ?></button>
                                                <input type="hidden" id="poi_library_icon" value="0" />
                                                <img id="poi_library_icon_preview" style="display: none;height:30px" src="" />
                                                <div id="poi_library_icon_preview_l" style="display: none;height:30px;vertical-align:middle;"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_animation"><?php echo _("Animation"); ?></label>
                                                <select onchange="change_poi_animation()" id="poi_animation" class="form-control form-control-sm">
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
                                    <div class="row" style="display: none">
                                        <div class="col-md-12 mt-1">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <button onclick="edit_poi_embed_gallery();" id="btn_poi_embed_gallery" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i>&nbsp;&nbsp;<?php echo _("IMAGES GALLERY"); ?></button><br>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mt-1"></div>
                                        <div class="col-md-4 mt-1">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label><?php echo _("Autoplay (seconds)"); ?></label><br>
                                                <input id="embed_gallery_autoplay" class="form-control form-control-sm" type="number" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mt-1">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_embed_gallery_random"><?php echo _("Random Order"); ?><br><input id="poi_embed_gallery_random" type="checkbox" /></label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mt-1"></div>
                                    </div>
                                    <div class="row mb-2 poi_block_div">
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_css_class"><?php echo _("CSS Class"); ?></label>
                                                <input type="text" id="poi_css_class" class="form-control form-control-sm" value="" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label id="poi_color_label" for="poi_color"><?php echo _("Color"); ?></label>
                                                <input type="text" id="poi_color" class="form-control form-control-sm" value="#000000" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_background"><?php echo _("Background"); ?></label>
                                                <input type="text" id="poi_background" class="form-control form-control-sm" value="rgba(255,255,255,0.7)" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_border_px"><?php echo _("Border"); ?></label>
                                                <input oninput="change_poi_border_px();" min="0" max="10" type="number" id="poi_border_px" class="form-control form-control-sm" value="3" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_icon_type"><?php echo _("Style"); ?></label>
                                                <select onchange="change_poi_icon_type();" id="poi_icon_type" class="form-control form-control-sm">
                                                    <option id="round"><?php echo _("Round"); ?></option>
                                                    <option id="square"><?php echo _("Square"); ?></option>
                                                    <option id="round_outline"><?php echo _("Round (outline)"); ?></option>
                                                    <option id="square_outline"><?php echo _("Square (outline)"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="margin-bottom: 5px;" class="form-group">
                                                <label for="poi_label"><?php echo _("Label"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'poi_label'); ?>
                                                <input type="text" id="poi_label" class="form-control form-control-sm" />
                                                <?php foreach ($array_languages as $lang) {
                                                    if($lang!=$default_language) : ?>
                                                        <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="poi_label" data-lang="<?php echo $lang; ?>" value="" />
                                                    <?php endif;
                                                } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="poi_sound"><?php echo _("Sound"); ?> <i title="<?php echo _("sound file must be uploaded into sound library."); ?>" class="help_t fas fa-question-circle"></i></label>
                                            <div style="margin-bottom: 5px;" class="input-group">
                                                <select id="poi_sound" class="form-control form-control-sm">
                                                    <option id=""><?php echo _("No Sound"); ?></option>
                                                    <?php echo get_option_exist_sound($_SESSION['id_user'],$id_virtualtour_sel,''); ?>
                                                </select>
                                                <div class="input-group-append">
                                                    <button onclick="play_sound('poi_sound');" class="btn btn-sm btn-outline-secondary" type="button"><i class="fas fa-play"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-schedule" role="tabpanel" aria-labelledby="pills-schedule-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="enable_schedule"><?php echo _("Enable Schedule"); ?></label>
                                                <input onchange="check_schedule();" type="checkbox" id="enable_schedule" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_from"><?php echo _("Date - From"); ?></label>
                                                <input type="date" class="form-control form-control-sm" id="date_from" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_to"><?php echo _("Date - To"); ?></label>
                                                <input type="date" class="form-control form-control-sm" id="date_to" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label><input type="checkbox" id="days_1"> <?php echo _("Monday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_2"> <?php echo _("Tuesday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_3"> <?php echo _("Wednesday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_4"> <?php echo _("Thursday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_5"> <?php echo _("Friday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_6"> <?php echo _("Saturday"); ?></label>&nbsp;&nbsp;
                                            <label><input type="checkbox" id="days_7"> <?php echo _("Sunday"); ?></label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hour_from"><?php echo _("Hour - From"); ?></label>
                                                <input type="time" class="form-control form-control-sm" id="hour_from" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hour_to"><?php echo _("Hour - To"); ?></label>
                                                <input type="time" class="form-control form-control-sm" id="hour_to" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:none;" class="mt-1 mb-1"><label style="font-size:12px;"><input style="vertical-align:middle;" id="exclude_from_apply_all" type="checkbox">&nbsp;&nbsp;<?php echo _("exclude from apply to all"); ?></label></div>
                                <span data-toggle="modal" data-target="#modal_pois_style_apply" style="display:none;" class="btn_apply_style_all btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("APPLY STYLE TO ALL"); ?>&nbsp;&nbsp;<i class="fas fa-check-double"></i></span>
                                <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                        <div id="confirm_move">
                            <div class="noselect" style="width: calc(100% - 30px);margin-bottom:5px;">
                                <b id="msg_drag_poi"><?php echo _("drag the poi to change its position"); ?></b>
                                <b style="display:none;" id="msg_drag_embed"><?php echo _("drag the pointers to move and resize the content"); ?></b>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group noselect">
                                        <label style="margin-bottom: 0;"><?php echo _("Perspective"); ?> <i style="font-size:12px;" id="perspective_values"></i></label>
                                        <input oninput="" type="range" min="0" max="70" step="1" class="form-control-range" id="rotateX" />
                                        <input oninput="" type="range" min="-180" max="180" step="1" class="form-control-range" id="rotateZ" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group noselect">
                                        <label style="margin-bottom: 0;"><?php echo _("Size"); ?> <i style="font-size:12px;" id="size_values"></i></label>
                                        <input oninput="" type="range" step="0.1" min="0.5" max="2.0" class="form-control-range" id="size_scale" />
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
                            <div id="object3d_move_params" style="display: none" class="row">
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group noselect">
                                        <span><?php echo _("click and drag the 3d object to rotate its initial position"); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="margin-bottom: 5px;" class="form-group noselect">
                                        <label style="margin-bottom: 0;"><?php echo _("Zoom"); ?> <i style="font-size:12px;" id="object3d_zoom_value"></i></label>
                                        <input oninput="change_object3d_zoom();" type="range" step="1" min="80" max="179" class="form-control-range" id="object3d_zoom" />
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 5px;display: none" class="form-group noselect">
                                <button id="btn_draw_polygon" onclick="draw_polygon_selection();" class="btn btn-sm btn-primary"><i class="fas fa-draw-polygon"></i>&nbsp;&nbsp;<?php echo _("draw polygon inside"); ?></button>
                            </div>
                            <div style="display: none;margin-bottom: 5px;" class="form-group">
                                <input onchange="change_transform3d();" type="checkbox" id="transform3d" checked />
                                <label style="margin-bottom: 0;" for="transform3d"><?php echo _("3d Transform"); ?></label>
                            </div>
                            <div style="display: none;margin-bottom: 5px;" class="form-group">
                                <input onchange="change_scale_p();" type="checkbox" id="scale" />
                                <label style="margin-bottom: 0;" for="scale"><?php echo _("Scale"); ?></label>
                            </div>
                            <span data-toggle="modal" data-target="#modal_pois_move_apply" style="display:none;" class="btn_apply_move_all btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("APPLY TO ALL"); ?>&nbsp;&nbsp;<i class="fas fa-check-double"></i></span>
                            <span class="btn_confirm <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?>&nbsp;&nbsp;<i class="fas fa-check-circle"></i></span>
                            <i class="fas fa-arrows-alt move_box_move"></i>
                            <span class="btn_close"><i class="fas fa-times"></i></span>
                        </div>
                        <div id="confirm_polygon">
                            <div class="noselect" style="width: calc(100% - 30px);margin-bottom:5px;">
                                <b><?php echo _("click to draw the points of the polygon"); ?></b>
                            </div>
                            <div style="margin-bottom: 5px;" class="form-group noselect">
                                <button id="btn_clear_polygon" onclick="clear_polygon_selection();" class="btn btn-sm btn-primary disabled"><i class="fas fa-eraser"></i>&nbsp;&nbsp;<?php echo _("Clear"); ?></button>
                                <button id="btn_save_polygon" onclick="save_polygon_selection();" class="btn btn-sm btn-success disabled"><i class="fas fa-check"></i>&nbsp;&nbsp;<?php echo _("Confirm"); ?></button>
                                <button onclick="close_polygon_selection();" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i>&nbsp;&nbsp;<?php echo _("Close"); ?></button>
                            </div>
                        </div>
                        <button title="<?php echo _("LIST POIs"); ?>" onclick="open_list_hs();" id="btn_list_hs" style="opacity:0;position:absolute;top:10px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-light"><i class="fas fa-list-ol"></i><span class="hs_badge_count badge badge-primary position-absolute">0</span></button>
                        <?php if($create_permission && $create_content) : ?><button title="<?php echo _("ADD POI"); ?>" id="btn_add_poi" style="opacity:0;position:absolute;top:60px;right:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-success"><i class="fas fa-plus"></i></button><?php endif; ?>
                        <button title="<?php echo _("EDIT MARKERS"); ?>" id="btn_switch_to_marker" style="opacity:0;position:absolute;top:10px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary" onclick=""><i class="fas fa-caret-square-up"></i></button>
                        <button onclick="open_preview_viewer();" title="<?php echo _("PREVIEW"); ?>" id="btn_preview_modal" style="opacity:0;position:absolute;top:60px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary" onclick=""><i class="fas fa-eye"></i></button>
                        <button onclick="toggle_fullscreen_div('poi_editor_div');" title="<?php echo _("TOGGLE FULLSCREEN"); ?>" id="btn_toggle_fullscreen" style="opacity:0;position:absolute;top:110px;left:10px;z-index:10;pointer-events:none;" class="btn btn-circle btn-primary"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<model-viewer loading="eager" style="width:1px;height:1px;opacity:0;pointer-events:none;" id="object3d_model" src=""></model-viewer>
<script>
    window.modelViewer_o = document.querySelector('model-viewer#object3d_model');
    modelViewer_o.addEventListener('load', (ev) => {
        var params = window.pois[poi_index_edit].params;
        var animation_sel = '';
        if(params!='') {
            var tmp = params.split(",");
            if(tmp[4]!==undefined) {
                animation_sel=tmp[4];
            }
        }
        var animations = modelViewer_o.availableAnimations;
        if (animations) {
            $('#params_animation').removeClass('disabled');
            animations.forEach((animation) => {
                if(animation_sel==animation) {
                    $('#params_animation').append('<option selected id="'+animation+'">'+animation+'</option>');
                } else {
                    $('#params_animation').append('<option id="'+animation+'">'+animation+'</option>');
                }
            });
        } else {
            $('#params_animation').addClass('disabled');
        }
    });
</script>

<model-viewer loading="eager" style="width:1px;height:1px;opacity:0;pointer-events:none;" id="object3d_model_e" src=""></model-viewer>
<script>
    window.modelViewer_o_e = document.querySelector('model-viewer#object3d_model_e');
    modelViewer_o_e.addEventListener('load', (ev) => {
        var params = window.pois[poi_index_edit].embed_params;
        var animation_sel = '';
        if(params!='') {
            var tmp = params.split(",");
            if(tmp[7]!==undefined) {
                animation_sel=tmp[7];
            }
        }
        var animations = modelViewer_o_e.availableAnimations;
        if (animations) {
            $('#object3d_animation').removeClass('disabled');
            animations.forEach((animation) => {
                if(animation_sel==animation) {
                    $('#object3d_animation').append('<option selected id="'+animation+'">'+animation+'</option>');
                } else {
                    $('#object3d_animation').append('<option id="'+animation+'">'+animation+'</option>');
                }
            });
        } else {
            $('#object3d_animation').addClass('disabled');
        }
    });
</script>

<script>
    (function($) {
        "use strict"; // Start of use strict
        Dropzone.autoDiscover = false;
        window.id_room_poi = <?php echo $id_room; ?>;
        window.id_room_sel = null;
        window.id_room_alt_sel = 0;
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.code_vt = '<?php echo $code_vt; ?>';
        window.pois = null;
        window.pois_initial = null;
        window.can_create = false;
        window.is_editing = false;
        window.poi_index_edit = null;
        window.poi_id_edit = null;
        window.panorama_image = '';
        window.currentYaw = 0;
        window.currentPitch = 0;
        window.viewer = null;
        window.video_viewer = null;
        window.viewer_initialized = false;
        window.poi_color_spectrum = null;
        window.chroma_color_spectrum = null;
        window.chroma_color_spectrum_lang = [];
        window.poi_background_spectrum = null;
        window.tooltip_background_spectrum = null;
        window.tooltip_color_spectrum = null;
        window.box_background_spectrum = null;
        window.box_color_spectrum = null;
        window.callout_title_font_color_spectrum = null;
        window.callout_title_bg_color_spectrum = null;
        window.callout_description_font_color_spectrum = null;
        window.callout_main_color_spectrum = null;
        window.callout_content_bg_color_spectrum = null;
        window.form_background_spectrum = null;
        window.form_color_spectrum = null;
        window.form_background_button_spectrum = null;
        window.form_color_button_spectrum = null;
        window.tooltip_text_editor = null;
        window.tooltip_text_editor_lang = [];
        window.switched_page = false;
        window.new_poi_id_room = null;
        window.new_poi_image = null;
        window.id_poi_autoopen = '';
        window.poi_embed_originals_pos = [];
        window.marker_embed_originals_pos = [];
        window.video_embeds = [];
        window.sync_virtual_staging_enabled = false;
        window.sync_poi_embed_enabled = false;
        window.sync_marker_embed_enabled = false;
        window.embed_type_sel = '';
        window.embed_type_current = '';
        window.content_sel = '';
        window.content_current = '';
        window.poi_content_html_sc = null;
        window.poi_content_html_sc_lang = [];
        window.video_ext_sel = 'mp4';
        window.html_editor = null;
        window.html_editor_lang = [];
        window.poi_embed_content_html_editor = null;
        window.poi_embed_content_html_editor_lang = [];
        window.poi_embed_content_html_sc = null;
        window.poi_embed_content_html_sc_lang = [];
        window.gallery_dropzone_ip = null;
        window.video_chroma = null;
        window.ctx_chroma_tmp = null;
        window.ctx_chroma = null;
        window.width_chroma = null;
        window.height_chroma = null;
        window.draw_polygon_mode = 0;
        window.is_fullscreen = false;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        window.default_language = '<?php echo $default_language; ?>';
        window.selected_language = '';
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
            bsCustomFileInput.init();
            if("currentYaw" in sessionStorage) {
                window.currentYaw = parseFloat(sessionStorage.getItem('currentYaw'));
                window.currentPitch = parseFloat(sessionStorage.getItem('currentPitch'));
                sessionStorage.setItem('currentYaw','0');
                sessionStorage.setItem('currentPitch','0');
                if(window.currentYaw!=0 && window.id_room_poi!=0) {
                    window.switched_page = true;
                }
            }
            var container_h = $('#content-wrapper').height() - 225;
            $('#panorama_pois').css('height',container_h+'px');
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
            check_plan(window.id_user,'poi');
            if(window.can_create) {
                $('#plan_poi_msg').addClass('d-none');
            } else {
                $('#plan_poi_msg').removeClass('d-none');
            }
            setTimeout(function () {
                get_rooms(window.id_virtualtour,'poi');
                get_icon_images_m(window.id_virtualtour,'poi_h');
            },200);
            IconPicker.Init({
                jsonUrl: 'vendor/iconpicker/iconpicker-1.5.0.json',
                searchPlaceholder: '<?php echo _("Search Icon"); ?>',
                showAllButton: '<?php echo _("Show All"); ?>',
                cancelButton: '<?php echo _("Cancel"); ?>',
                noResultsFound: '<?php echo _("No results found."); ?>',
                borderRadius: '20px',
                appendTo: document.getElementById('poi_editor_div')
            });
            IconPicker.Run('#GetIconPicker', function(){
                window.pois[poi_index_edit].icon = $('#poi_icon').val();
                render_poi(window.poi_id_edit,window.poi_index_edit);
            });
            window.poi_color_spectrum = $('#poi_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    window.pois[poi_index_edit].color = color.toHexString();
                    render_poi(window.poi_id_edit,window.poi_index_edit);
                },
                change: function(color) {
                    window.pois[poi_index_edit].color = color.toHexString();
                    render_poi(window.poi_id_edit,window.poi_index_edit);
                }
            });
            window.poi_background_spectrum = $('#poi_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    window.pois[poi_index_edit].background = color.toRgbString();
                    render_poi(window.poi_id_edit,window.poi_index_edit);
                },
                change: function(color) {
                    window.pois[poi_index_edit].background = color.toRgbString();
                    render_poi(window.poi_id_edit,window.poi_index_edit);
                }
            });
            window.tooltip_background_spectrum = $('#tooltip_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    window.pois[poi_index_edit].tooltip_background = color.toRgbString();
                    $('#tooltip_text_html').css('background-color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('background-color',color.toRgbString());
                },
                change: function(color) {
                    window.pois[poi_index_edit].tooltip_background = color.toRgbString();
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
                appendTo: '#poi_editor_div',
                move: function(color) {
                    window.pois[poi_index_edit].tooltip_color = color.toHexString();
                    $('#tooltip_text_html').css('color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('color',color.toRgbString());
                },
                change: function(color) {
                    window.pois[poi_index_edit].tooltip_color = color.toHexString();
                    $('#tooltip_text_html').css('color',color.toRgbString());
                    $('.input_lang[data-target-id="tooltip_text_html"]').css('color',color.toRgbString());
                }
            });
            window.box_background_spectrum = $('#box_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            window.box_color_spectrum = $('#box_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            window.chroma_color_spectrum = $('#chroma_color').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    var chroma_color = color.toString().replace("rgb(","").replace(")","");
                    var chroma_tolerance = $('#chroma_tolerance').val();
                    var params = chroma_color+','+chroma_tolerance;
                    window.pois[poi_index_edit].params = params;
                    remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
                },
                change: function(color) {
                    var chroma_color = color.toString().replace("rgb(","").replace(")","");
                    var chroma_tolerance = $('#chroma_tolerance').val();
                    var params = chroma_color+','+chroma_tolerance;
                    window.pois[poi_index_edit].params = params;
                    remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
                }
            });
            $('.input_lang[data-target-id="chroma_color"]').each(function() {
                var id_l = $(this).attr('id');
                var lang = $(this).attr('data-lang');
                window.chroma_color_spectrum_lang[lang] = $('#'+id_l).spectrum({
                    type: "text",
                    preferredFormat: "rgb",
                    showAlpha: false,
                    showButtons: false,
                    allowEmpty: false,
                    appendTo: '#poi_editor_div',
                    move: function(color) {
                        var chroma_color = color.toString().replace("rgb(","").replace(")","");
                        var chroma_tolerance = $('#chroma_tolerance_'+lang).val();
                        var params = chroma_color+','+chroma_tolerance;
                        remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
                    },
                    change: function(color) {
                        var chroma_color = color.toString().replace("rgb(","").replace(")","");
                        var chroma_tolerance = $('#chroma_tolerance_'+lang).val();
                        var params = chroma_color+','+chroma_tolerance;
                        remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
                    }
                });
            });
            window.callout_title_font_color_spectrum = $('#callout_title_font_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    change_callout_params();
                },
                change: function(color) {
                    change_callout_params();
                }
            });
            window.callout_title_bg_color_spectrum = $('#callout_title_bg_color').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    change_callout_params();
                },
                change: function(color) {
                    change_callout_params();
                }
            });
            window.callout_description_font_color_spectrum = $('#callout_description_font_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    change_callout_params();
                },
                change: function(color) {
                    change_callout_params();
                }
            });
            window.callout_main_color_spectrum = $('#callout_main_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    change_callout_params();
                },
                change: function(color) {
                    change_callout_params();
                }
            });
            window.callout_content_bg_color_spectrum = $('#callout_content_bg_color').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div',
                move: function(color) {
                    change_callout_params();
                },
                change: function(color) {
                    change_callout_params();
                }
            });
            window.form_background_spectrum = $('#form_background').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            window.form_color_spectrum = $('#form_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            window.form_background_button_spectrum = $('#form_background_button').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            window.form_color_button_spectrum = $('#form_color_button').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#poi_editor_div'
            });
            $('#btn_add_poi').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'left'
            });
            $('#btn_switch_to_marker').tooltipster({
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
                    containment: "#poi_editor_div",
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
        $('#poi_label').on('keydown change input',function () {
            var label = $('#poi_label').val();
            window.pois[window.poi_index_edit].label = label;
            render_poi(window.poi_id_edit,window.poi_index_edit);
        });
        $('.input_lang[data-target-id="poi_label"]').on('keydown change input',function () {
            render_poi(window.poi_id_edit,window.poi_index_edit);
        });
        $(window).resize(function () {
            if(window.is_fullscreen) {
                if($('.rooms_slider').is(':visible')) {
                    var h_p = 75;
                } else {
                    var h_p = 0;
                }
                $('#panorama_pois').css('height','calc(100% - '+h_p+'px)');
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
                $('#panorama_pois').css('height',container_h+'px');
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
        $('#modal_pois_style_apply').on('shown.bs.modal', function() {
            $('#modal_pois_style_apply input[type="checkbox"]').prop('checked', true);
            $('#modal_pois_style_apply #set_as_default').prop('checked', false);
        });
        $('#sidebarToggle').click(function() {
            window.sync_poi_embed_enabled = false;
            window.sync_marker_embed_enabled = false;
            $('.draggable_poi_embed').remove();
            $('.draggable_marker_embed').remove();
            setTimeout(function() {
                jQuery.each(window.pois, function(index, poi) {
                    switch(poi.what) {
                        case 'poi':
                            render_poi(parseInt(poi.id),index);
                            break;
                        case 'marker':
                            render_marker_p(parseInt(poi.id),index);
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
                    $('.custom-hotspot-content').css('opacity',1);
                    $('.center_helper').show();
                }
                container.hide();
                $('#slider_hs_list .list-group button').removeClass('active');
            }
        });

        window.change_chroma_tolerance = function () {
            var chroma_color = $('#chroma_color').val().replace("rgb(","").replace(")","");
            var chroma_tolerance = $('#chroma_tolerance').val();
            var params = chroma_color+','+chroma_tolerance;
            window.pois[poi_index_edit].params = params;
            remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
        }

        window.change_chroma_tolerance_lang = function (lang) {
            var chroma_color = $('#chroma_color_'+lang).val().replace("rgb(","").replace(")","");
            var chroma_tolerance = $('#chroma_tolerance_'+lang).val();
            var params = chroma_color+','+chroma_tolerance;
            remove_background_video_chroma(video_chroma,ctx_chroma_tmp,ctx_chroma,width_chroma,height_chroma,params,true);
        }

        $('body').on('submit','#frm_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                        if(window.wizard_step!=-1) {
                            Shepherd.activeTour.next();
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_edit('upload failed');
                upadte_progressbar_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_edit('upload aborted');
                upadte_progressbar_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_edit(value){
            $('#progressBar_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_edit').show();
            $('#error_edit').html(error);
        }

        $('body').on('submit','#frm_sp_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_sp_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_sp_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_sp_edit' )[0].files[0]);
            }
            data.append('id_room',window.id_room_sel);
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_sp_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_sp_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        var rsp = JSON.parse(evt.target.responseText);
                        $('#poi_content').val(rsp.name);
                        $('#switch_panorama_id').val(rsp.id);
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_sp_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_sp_edit('upload failed');
                upadte_progressbar_sp_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_sp_edit('upload aborted');
                upadte_progressbar_sp_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_sp_edit(value){
            $('#progressBar_sp_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_sp_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_sp_edit').show();
            $('#error_sp_edit').html(error);
        }

        $('body').on('submit','#frm_j_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_j_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_j_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_j_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_edit_j(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_edit_j(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_edit_j(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_edit_j('upload failed');
                upadte_progressbar_edit_j(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_edit_j('upload aborted');
                upadte_progressbar_edit_j(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_edit_j(value){
            $('#progressBar_j_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_edit_j(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_edit_j').show();
            $('#error_edit_j').html(error);
        }

        $('body').on('submit','#frm_edit_e',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_edit_e').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_edit_e[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_edit_e' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_edit_e(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_edit_e(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_embed_content').is(':visible')) {
                            $('#poi_embed_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_embed_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                        if(window.pois[poi_index_edit].embed_type!='') {
                            window.pois[poi_index_edit].embed_content = evt.target.responseText;
                            render_poi(poi_id_edit,poi_index_edit);
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_edit_e(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_edit_e('upload failed');
                upadte_progressbar_edit_e(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_edit_e('upload aborted');
                upadte_progressbar_edit_e(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_edit_e(value){
            $('#progressBar_edit_e').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_edit_e(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_edit_e').show();
            $('#error_edit_e').html(error);
        }

        $('body').on('submit','#frm_d_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_d_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_d_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_d_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_d_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_d_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_d_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_d_edit('upload failed');
                upadte_progressbar_d_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_d_edit('upload aborted');
                upadte_progressbar_d_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_d_edit(value){
            $('#progressBar_d_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_d_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_d_edit').show();
            $('#error_d_edit').html(error);
        }

        $('body').on('submit','#frm_pdf_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_pdf_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_pdf_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_pdf_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_pdf_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_pdf_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_pdf_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_pdf_edit('upload failed');
                upadte_progressbar_pdf_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_pdf_edit('upload aborted');
                upadte_progressbar_pdf_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_pdf_edit(value){
            $('#progressBar_pdf_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_pdf_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_pdf_edit').show();
            $('#error_pdf_edit').html(error);
        }

        $('body').on('submit','#frm_pc_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_pc_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_pc_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_pc_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_pc_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_pc_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_pc_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_pc_edit('upload failed');
                upadte_progressbar_pc_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_pc_edit('upload aborted');
                upadte_progressbar_pc_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_pc_edit(value){
            $('#progressBar_pc_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_pc_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_pc_edit').show();
            $('#error_pc_edit').html(error);
        }

        $('body').on('submit','#frm_g_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_g_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_g_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_g_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_g_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_g_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        var file_uploaded = evt.target.responseText;
                        if($('#poi_content').is(':visible')) {
                            var exists_files = $('#poi_content').val();
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    var exists_files = $(this).val();
                                }
                            });
                        }
                        var array_files = exists_files.split(",");
                        var glb_file = '', usdz_file = '';
                        jQuery.each(array_files, function(index_s, file_s) {
                            if(file_s.split('.').pop().toLowerCase()=='glb') {
                                glb_file = file_s;
                            }
                            if(file_s.split('.').pop().toLowerCase()=='glft') {
                                glb_file = file_s;
                            }
                            if(file_s.split('.').pop().toLowerCase()=='usdz') {
                                usdz_file = file_s;
                            }
                        });
                        if(file_uploaded.split('.').pop().toLowerCase()=='glb') {
                            glb_file = file_uploaded;
                        }
                        if(file_uploaded.split('.').pop().toLowerCase()=='gltf') {
                            glb_file = file_uploaded;
                        }
                        if(file_uploaded.split('.').pop().toLowerCase()=='usdz') {
                            usdz_file = file_uploaded;
                        }
                        if(usdz_file!='' && glb_file!='') {
                            var poi_content = usdz_file+','+glb_file;
                        } else if(usdz_file!='' && glb_file=='') {
                            var poi_content = usdz_file;
                        } else if(usdz_file=='' && glb_file!='') {
                            var poi_content = glb_file;
                        }
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(poi_content);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(poi_content);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_g_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_g_edit('upload failed');
                upadte_progressbar_g_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_g_edit('upload aborted');
                upadte_progressbar_g_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_g_edit(value){
            $('#progressBar_g_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_g_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_g_edit').show();
            $('#error_g_edit').html(error);
        }

        $('body').on('submit','#frm_v_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_v_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_v_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_v_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_v_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_v_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_v_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_v_edit('upload failed');
                upadte_progressbar_v_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_v_edit('upload aborted');
                upadte_progressbar_v_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_v_edit(value){
            $('#progressBar_v_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_v_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_v_edit').show();
            $('#error_v_edit').html(error);
        }

        $('body').on('submit','#frm_v_edit_e',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_v_edit_e').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_v_edit_e[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_v_edit_e' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_v_edit_e(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_v_edit_e(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        switch (window.video_ext_sel) {
                            case 'mp4':
                                if($('#poi_embed_content').is(':visible')) {
                                    $('#poi_embed_content').val(evt.target.responseText);
                                } else {
                                    $('.input_lang[data-target-id="poi_embed_content"]').each(function() {
                                        if($(this).is(':visible')) {
                                            $(this).val(evt.target.responseText);
                                        }
                                    });
                                }
                                break;
                            case 'webm_mov':
                                var file_uploaded = evt.target.responseText;
                                var exists_videos = "";
                                if($('#poi_embed_content').is(':visible')) {
                                    exists_videos = $('#poi_embed_content').val();
                                } else {
                                    $('.input_lang[data-target-id="poi_embed_content"]').each(function() {
                                        if($(this).is(':visible')) {
                                            exists_videos = $(this).val();
                                        }
                                    });
                                }
                                if(exists_videos!='') {
                                    var array_videos = exists_videos.split(",");
                                } else {
                                    var array_videos = [];
                                }
                                var mov_video = '', webm_video = '';
                                jQuery.each(array_videos, function(index_s, video_s) {
                                    if(video_s.split('.').pop().toLowerCase()=='mov') {
                                        mov_video = video_s;
                                    }
                                    if(video_s.split('.').pop().toLowerCase()=='webm') {
                                        webm_video = video_s;
                                    }
                                });
                                if(file_uploaded.split('.').pop().toLowerCase()=='mov') {
                                    mov_video = file_uploaded;
                                }
                                if(file_uploaded.split('.').pop().toLowerCase()=='webm') {
                                    webm_video = file_uploaded;
                                }
                                if(webm_video!='' && mov_video!='') {
                                    var poi_embed_content = webm_video+','+mov_video;
                                } else if(webm_video!='' && mov_video=='') {
                                    var poi_embed_content = webm_video;
                                } else if(webm_video=='' && mov_video!='') {
                                    var poi_embed_content = mov_video;
                                }
                                if($('#poi_embed_content').is(':visible')) {
                                    $('#poi_embed_content').val(poi_embed_content);
                                } else {
                                    $('.input_lang[data-target-id="poi_embed_content"]').each(function() {
                                        if($(this).is(':visible')) {
                                            $(this).val(poi_embed_content);
                                        }
                                    });
                                }
                                break;
                        }
                        if(window.pois[poi_index_edit].embed_type!='') {
                            switch(window.pois[poi_index_edit].embed_type) {
                                case 'video':
                                    window.pois[poi_index_edit].embed_content = evt.target.responseText;
                                    break;
                                case 'video_transparent':
                                    window.pois[poi_index_edit].embed_content = poi_embed_content;
                                    break;
                                case 'video_chroma':
                                    window.pois[poi_index_edit].embed_content = evt.target.responseText;
                                    break;
                            }
                            render_poi(poi_id_edit,poi_index_edit);
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_v_edit_e(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_v_edit_e('upload failed');
                upadte_progressbar_v_edit_e(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_v_edit_e('upload aborted');
                upadte_progressbar_v_edit_e(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_v_edit_e(value){
            $('#progressBar_v_edit_e').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_v_edit_e(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_v_edit_e').show();
            $('#error_v_edit_e').html(error);
        }

        $('body').on('submit','#frm_td_edit_e',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_td_edit_e').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_td_edit_e[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_td_edit_e' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_td_edit_e(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_td_edit_e(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_embed_content').is(':visible')) {
                            $('#poi_embed_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_embed_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                        if(window.pois[poi_index_edit].embed_type=='object3d') {
                            window.pois[poi_index_edit].embed_content = evt.target.responseText;
                        }
                        render_poi(poi_id_edit,poi_index_edit);
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_td_edit_e(0);
                frm.find( '#txtFile_td_edit_e' )[0].value = null;
                bsCustomFileInput.destroy();
                bsCustomFileInput.init();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_td_edit_e('upload failed');
                upadte_progressbar_td_edit_e(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_td_edit_e('upload aborted');
                upadte_progressbar_td_edit_e(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_td_edit_e(value){
            $('#progressBar_td_edit_e').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_td_edit_e(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_td_edit_e').show();
            $('#error_td_edit_e').html(error);
        }

        $('body').on('submit','#frm_a_edit',function(e){
            e.preventDefault();
            $('.btn_confirm').addClass('disabled');
            $('.btn_close').addClass('disabled');
            $('#error_a_edit').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_a_edit[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_a_edit' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_a_edit(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_a_edit(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if($('#poi_content').is(':visible')) {
                            $('#poi_content').val(evt.target.responseText);
                        } else {
                            $('.input_lang[data-target-id="poi_content"]').each(function() {
                                if($(this).is(':visible')) {
                                    $(this).val(evt.target.responseText);
                                }
                            });
                        }
                    }
                }
                $('.btn_confirm').removeClass('disabled');
                $('.btn_close').removeClass('disabled');
                upadte_progressbar_a_edit(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_a_edit('upload failed');
                upadte_progressbar_a_edit(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_a_edit('upload aborted');
                upadte_progressbar_a_edit(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_a_edit(value){
            $('#progressBar_a_edit').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_a_edit(error){
            $('.btn_confirm').removeClass('disabled');
            $('.btn_close').removeClass('disabled');
            $('.progress').hide();
            $('#error_a_edit').show();
            $('#error_a_edit').html(error);
        }

        window.open_modal_library_icons = function () {
            if(window.gallery_dropzone_ip==null) {
                if($('#gallery-dropzone-ip').length) {
                    window.gallery_dropzone_ip = new Dropzone("#gallery-dropzone-ip", {
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
                    window.gallery_dropzone_ip.on("addedfile", function(file) {
                        $('#list_images_ip').addClass('disabled');
                    });
                    window.gallery_dropzone_ip.on("success", function(file,rsp) {
                        add_image_to_icon_m(id_virtualtour,rsp,'poi_h');
                    });
                    window.gallery_dropzone_ip.on("queuecomplete", function() {
                        $('#list_images_ip').removeClass('disabled');
                        window.gallery_dropzone_ip.removeAllFiles();
                    });
                }
            }
            $('#modal_library_icons').modal('show');
        }
    })(jQuery); // End of use strict

    function change_gallery_autoplay() {
        if($('#g_autoplay').is(':checked')) {
            $('#g_slide_duration').parent().removeClass('disabled');
        } else {
            $('#g_slide_duration').parent().addClass('disabled');
        }
    }

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