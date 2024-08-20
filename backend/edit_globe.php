<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_globe = $_GET['id'];
$globe = get_globe($id_globe,$id_user);
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","",$_SERVER['SCRIPT_NAME']);
$link = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","globe/index.php?code=",$_SERVER['SCRIPT_NAME']);
$link_f = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","globe/",$_SERVER['SCRIPT_NAME']);
$settings = get_settings();
if(empty($settings['globe_googlemaps_key'])) {
    $globe['type']="default";
}
?>

<?php if(!$globe): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
    <script>
        $('.vt_select_header').remove();
    </script>
<?php die(); endif; ?>

<style>
    .map_globe_icon {
        background-color: <?php echo $globe['pointer_color']; ?>;
        border-color: <?php echo $globe['pointer_border']; ?>;
        width: <?php echo $globe['pointer_size']; ?>px;
        height: <?php echo $globe['pointer_size']; ?>px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> <?php echo _("Details"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Name"); ?></label>
                                    <input type="text" class="form-control" id="name" value="<?php echo $globe['name']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type"><?php echo _("Type"); ?></label>
                                    <select onchange="change_globe_type();" id="type" class="form-control <?php echo (empty($settings['globe_googlemaps_key'])) ? 'disabled' : ''; ?>">
                                        <option <?php echo ($globe['type']=='default') ? 'selected' : ''; ?> id="default"><?php echo _("Default"); ?></option>
                                        <option <?php echo ($globe['type']=='google') ? 'selected' : ''; ?> id="google"><?php echo _("Google Photorealistic 3D"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="default_view"><?php echo _("Default View"); ?></label>
                                    <select id="default_view" class="form-control <?php echo ($globe['type']=='google') ? 'disabled' : ''; ?>">
                                        <option <?php echo ($globe['default_view']=='street') ? 'selected' : ''; ?> id="street"><?php echo _("Street"); ?></option>
                                        <option <?php echo ($globe['default_view']=='satellite') ? 'selected' : ''; ?> id="satellite"><?php echo _("Satellite"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pointer_size"><?php echo _("Point Size"); ?></label>
                                    <input oninput="adjust_point_g()" onchange="adjust_point_g()" type="number" id="pointer_size" class="form-control" value="<?php echo $globe['pointer_size']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="zoom_duration"><?php echo _("Zoom Duration"); ?> <i title="<?php echo _("time in seconds to zoom the globe to approach the tour"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <div class="input-group">
                                        <input min="1" type="number" class="form-control" id="zoom_duration" value="<?php echo $globe['zoom_duration']; ?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pointer_color"><?php echo _("Point Color"); ?></label>
                                    <input type="text" id="pointer_color" class="form-control" value="<?php echo $globe['pointer_color']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pointer_border"><?php echo _("Point Border"); ?></label>
                                    <input type="text" id="pointer_border" class="form-control" value="<?php echo $globe['pointer_border']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="min_altitude"><?php echo _("Minimum Altitude"); ?> <i title="<?php echo _("minimum height of the top view in kilometers. leave blank for the default height"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="number" id="min_altitude" class="form-control" value="<?php echo $globe['min_altitude']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="center_altitude"><?php echo _("Initial Altitude"); ?> <i title="<?php echo _("initial height of the top view in kilometers. leave blank for the default height"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="number" id="center_altitude" class="form-control" value="<?php echo $globe['center_altitude']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="center_lat"><?php echo _("Center - Latitude"); ?> <i title="<?php echo _("initial central latitude. leave blank to position in the center of all points"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="number" id="center_lat" class="form-control" value="<?php echo $globe['center_lat']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="center_lon"><?php echo _("Center - Longitude"); ?> <i title="<?php echo _("initial central longitude. leave blank to position in the center of all points"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="number" id="center_lon" class="form-control" value="<?php echo $globe['center_lon']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <label><?php echo _("Logo"); ?></label>
                            <div style="background-color:#4e73df;display: none" id="div_image_logo" class="col-md-12">
                                <img style="width: 100%" src="../viewer/content/<?php echo $globe['logo']; ?>" />
                            </div>
                            <div style="display: none" id="div_delete_logo" class="col-md-12 p-0 mt-3">
                                <div class="form-group">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_g_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                </div>
                            </div>
                            <div style="display: none" id="div_upload_logo">
                                <form id="frm" action="ajax/upload_s_logo_image.php" method="POST" enctype="multipart/form-data">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="file" class="form-control" id="txtFile" name="txtFile" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Logo Image"); ?>" />
                                        </div>
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
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="map_div_container" class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-map"></i> <?php echo _("Globe Map"); ?> <i style="font-size: 12px">(<?php echo _("click on point to edit, drag to change position"); ?>)</i></h6>
            </div>
            <div class="card-body p-0">
                <div style="position: relative">
                    <div id="map_globe_div"></div>
                    <button data-toggle="modal" data-target="#modal_add_map_point" id="btn_add_point" style="opacity:0;position:absolute;top:10px;right:10px;z-index:998" class="btn btn-circle btn-primary d-inline-block float-right"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-map-marker-alt"></i> <?php echo _("Globe Point"); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div id="msg_select_point" class="col-md-12">
                            <p><?php echo _("Select point from map or add a new one"); ?></p>
                        </div>
                        <div class="col-md-12 point_settings" style="display: none">
                            <img id="preview_vt_image_map" src="" style="width:100%;" />
                        </div>
                        <div class="col-md-12 mt-2 point_settings" style="display: none">
                            <div class="form-group">
                                <label><?php echo _("Tour Target"); ?></label>
                                <input readonly type="text" id="room_target" class="form-control bg-white" value="" >
                            </div>
                        </div>
                        <div class="col-md-12 point_settings" style="display: none">
                            <div class="form-group">
                                <label><?php echo _("Position - Latitude"); ?></label>
                                <input oninput="change_marker_globe_position();" type="text" id="point_pos_lat" class="form-control bg-white" value="" >
                            </div>
                        </div>
                        <div class="col-md-12 point_settings" style="display: none">
                            <div class="form-group">
                                <label><?php echo _("Position - Longitude"); ?></label>
                                <input oninput="change_marker_globe_position();" type="text" id="point_pos_lon" class="form-control bg-white" value="" >
                            </div>
                        </div>
                        <div class="col-md-12 point_settings" style="display: none">
                            <a style="pointer-events: none" id="save_btn" href="#" class="btn btn-block btn-success btn-icon-split mb-2">
                            <span class="icon text-white-50">
                              <i class="far fa-circle"></i>
                            </span>
                                <span class="text"><?php echo _("AUTO SAVE"); ?></span>
                            </a>
                            <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_point" onclick="" class="btn btn-block btn-danger"><?php echo _("DELETE"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <a href="#collapsePI" class="d-block card-header py-3 collapsed" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-hashtag"></i> <?php echo _("Meta Tag"); ?></h6>
            </a>
            <div class="collapse" id="collapsePI">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="meta_title"><?php echo _("Title"); ?></label>
                                <input oninput="change_meta_title();" onchange="change_meta_title();" type="text" class="form-control" id="meta_title" value="<?php echo $globe['meta_title']; ?>" />
                            </div>
                            <div class="form-group">
                                <label for="meta_description"><?php echo _("Description"); ?></label>
                                <textarea oninput="change_meta_description();" onchange="change_meta_description();" rows="3" class="form-control" id="meta_description"><?php echo $globe['meta_description']; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><?php echo _("Image"); ?></label>
                                <div style="display: none" id="div_delete_image_meta" class="form-group mt-2">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_image_meta('globe',<?php echo $id_globe; ?>);" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                </div>
                                <div style="display: none" id="div_upload_image_meta">
                                    <form id="frm_im" action="ajax/upload_meta_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <input type="file" class="form-control" id="txtFile_im" name="txtFile_im" />
                                        </div>
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_im" value="<?php echo _("Upload Image"); ?>" />
                                        </div>
                                        <div class="preview text-center">
                                            <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                <div class="progress-bar" id="progressBar_im" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                    0%
                                                </div>
                                            </div>
                                            <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_im"></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label><?php echo _("Preview"); ?></label><br>
                            <div class="facebook-preview preview">
                                <div class="facebook-preview__link">
                                    <?php if(empty($globe['meta_image'])) {
                                        $meta_image = '';
                                    } else {
                                        $meta_image = $globe['meta_image'];
                                    } ?>
                                    <img class="facebook-preview__image <?php echo (empty($meta_image)) ? 'd-none' : ''; ?>" src="../viewer/content/<?php echo $meta_image; ?>" alt="">
                                    <div class="facebook-preview__content">
                                        <div class="facebook-preview__url">
                                            <?php echo $_SERVER['SERVER_NAME']; ?>
                                        </div>
                                        <h2 class="facebook-preview__title">
                                            <?php if(empty($globe['meta_title'])) {
                                                echo $globe['name'];
                                            } else {
                                                echo $globe['meta_title'];
                                            } ?>
                                        </h2>
                                        <div class="facebook-preview__description">
                                            <?php if(!empty($globe['meta_description'])) {
                                                echo $globe['meta_description'];
                                            } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-share-alt"></i> <?php echo _("Share & Embed"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group <?php echo ($user_info['role']!='administrator') ? 'd-none' : 'd-inline-block' ?>">
                            <label for="show_in_first_page"><?php echo _("Show as first page"); ?> (<?php echo $base_url; ?>) <i title="<?php echo _("only visible to administrators"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                            <input <?php echo ($demo) ? 'disabled' : ''; ?> id="show_in_first_page" <?php echo ($globe['show_in_first_page']) ? 'checked' : ''; ?> type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="light" data-size="normal" data-on="<?php echo _("Yes"); ?>" data-off="<?php echo _("No"); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="link"><i class="fas fa-link"></i> <?php echo _("Link"); ?></label>
                            <div class="input-group mb-0">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="link" value="<?php echo $link . $globe['code']; ?>" />
                                <div class="input-group-append">
                                    <a title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link . $globe['code']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#link">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link . $globe['code']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                        <div style="margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style" data-a2a-url="<?php echo $link . $globe['code']; ?>">
                            <a class="a2a_button_email <?php echo (in_array('email',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_whatsapp <?php echo (in_array('whatsapp',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_facebook <?php echo (in_array('facebook',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_twitter <?php echo (in_array('twitter',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_linkedin <?php echo (in_array('linkedin',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_telegram <?php echo (in_array('telegram',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_facebook_messenger <?php echo (in_array('facebook_messenger',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_pinterest <?php echo (in_array('pinterest',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_reddit <?php echo (in_array('reddit',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_line <?php echo (in_array('line',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_viber <?php echo (in_array('viber',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_vk <?php echo (in_array('vk',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_qzone <?php echo (in_array('qzone',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                            <a class="a2a_button_wechat <?php echo (in_array('wechat',$array_share_providers) ? '' : 'hidden'); ?>"></a>
                        </div>
                        <script async src="https://static.addtoany.com/menu/page.js"></script>
                        <!--<div id="social_share" style="font-size:12px"></div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="link_f"><i class="fas fa-link"></i> <?php echo _("Friendly Link"); ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text noselect" id="basic-addon3"><?php echo $link_f; ?></span>
                                </div>
                                <input <?php echo ($demo) ? 'disabled' : ''; ?> type="text" class="form-control bg-white" id="link_f" value="<?php echo $globe['friendly_url']; ?>" />
                                <div class="input-group-append <?php echo (empty($globe['friendly_url'])) ? 'disabled' : '' ; ?>">
                                    <a id="link_open" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link_f . $globe['friendly_url']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button id="link_copy" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-text="<?php echo $link_f . $globe['friendly_url'];; ?>">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button id="link_qr" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_f . $globe['friendly_url']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="code"><i class="fas fa-code"></i> <?php echo _("Embed Code"); ?></label>
                            <div class="input-group">
                                <textarea id="code" class="form-control" rows="2"><iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $link . $globe['code']; ?>"></iframe></textarea>
                                <div class="input-group-append">
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#code">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-12">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-css3-alt"></i> <?php echo _("Custom CSS"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div style="position: relative;width: 100%;height: 400px;" class="editors_css" id="custom_s"><?php echo get_editor_css_content_g('custom_'.$globe['code']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_globe" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Globe"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the globe?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_globe" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_qrcode" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("QR Code"); ?></h5>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-spin fa-spinner"></i>
                <img style="width: 100%;" src="" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_add_map_point" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("New Globe Point"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?php echo _("Select Tour"); ?></label>
                            <select onchange="change_preview_vt_image_map(null);" data-live-search="true" id="vt_select" class="form-control"></select>
                        </div>
                    </div>
                    <div class="col-md-12 text-center">
                        <img style="display: none" class="preview_vt_target" src="" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn_add_map_point" onclick="add_map_point_g()" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_globe_point" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Globe Point"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the globe point?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_globe_point" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.globe_need_save = false;
        window.id_globe = <?php echo $id_globe; ?>;
        window.bg_color_spectrum = null;
        window.link_f = '<?php echo $link_f; ?>';
        window.s_logo_image = '<?php echo $globe['logo']; ?>';
        window.editor_css = null;
        window.pointer_color_spectrum = null;
        window.pointer_border_spectrum = null;
        window.map_globe_l = null;
        window.map_points = null;
        window.map_markers = [];
        window.id_vt_point_sel = '';
        window.id_vt_sel = 0;
        window.marker_sel = null;
        window.image_meta = '<?php echo $globe['meta_image']; ?>';
        window.image_meta_default = '';
        window.title_meta_default = `<?php echo $globe['name']; ?>`;
        window.description_meta_default = ``;
        $(document).ready(function () {
            $('.help_t').tooltip();
            if(sessionStorage.getItem('add_point')=='1') {
                document.getElementById('map_div_container').scrollIntoView({block:'center'});
                sessionStorage.setItem('add_point','');
            }
            window.editor_css = ace.edit('custom_s');
            window.editor_css.session.setMode("ace/mode/css");
            window.editor_css.setOption('enableLiveAutocompletion',true);
            window.editor_css.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                window.editor_css.setTheme("ace/theme/one_dark");
            }
            window.pointer_color_spectrum = $('#pointer_color').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                move: function(color) {
                    adjust_point_g();
                },
                change: function(color) {
                    adjust_point_g();
                }
            });
            window.pointer_border_spectrum = $('#pointer_border').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                move: function(color) {
                    adjust_point_g();
                },
                change: function(color) {
                    adjust_point_g();
                }
            });
            $('.cpy_btn').tooltip({
                trigger: 'click',
                placement: 'bottom'
            });
            var clipboard = new ClipboardJS('.cpy_btn');
            clipboard.on('success', function(e) {
                setTooltip(e.trigger, window.backend_labels.copied+"!");
                hideTooltip(e.trigger);
            });
            $('#show_in_first_page').change(function() {
                if($(this).prop('checked')) {
                    var show_in_first_page = 1;
                } else {
                    var show_in_first_page = 0;
                }
                set_show_in_first_page(show_in_first_page,'globe');
            });
            /*$("#social_share").jsSocials({
                url: '<?php echo $link . $globe['code']; ?>',
                shareIn: "popup",
                showLabel: false,
                showCount: false,
                shares: ["email", "twitter", "facebook", "linkedin", "pinterest", "stumbleupon", "viber", "messenger", "telegram", "line" , "whatsapp"]
            });*/
            if(window.s_logo_image=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            if(window.image_meta=='') {
                $('#div_delete_image_meta').hide();
                $('#div_upload_image_meta').show();
            } else {
                $('#div_delete_image_meta').show();
                $('#div_upload_image_meta').hide();
            }
            var street_basemap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
            var satellite_basemap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
            window.map_globe_l = L.map('map_globe_div', {
                layers: [street_basemap]
            }).setView([0,0], 2);
            var baseMaps = {
                "Street": street_basemap,
                "Satellite": satellite_basemap
            };
            L.control.layers(baseMaps, {}, {position: 'bottomleft'}).addTo(window.map_globe_l);
            $('#map_globe_div').append('<i style="color:black;z-index:998;" class="fas fa-dot-circle center_helper"></i>');
            get_globe_map(window.id_globe,true);
            var timer_furl;
            $('#link_f').on('input',function(){
                if(timer_furl) {
                    clearTimeout(timer_furl);
                }
                timer_furl = setTimeout(function() {
                    change_friendly_url('globe','link_f',window.id_globe);
                },400);
            });

            $('body').on('submit','#frm_im',function(e){
                e.preventDefault();
                $('#error_im').hide();
                var url = $(this).attr('action');
                var frm = $(this);
                var data = new FormData();
                if(frm.find('#txtFile_im[type="file"]').length === 1 ){
                    data.append('file', frm.find( '#txtFile_im' )[0].files[0]);
                }
                var ajax  = new XMLHttpRequest();
                ajax.upload.addEventListener('progress',function(evt){
                    var percentage = (evt.loaded/evt.total)*100;
                    upadte_progressbar_im(Math.round(percentage));
                },false);
                ajax.addEventListener('load',function(evt){
                    if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                        show_error_im(evt.target.responseText);
                    } else {
                        if(evt.target.responseText!='') {
                            window.image_meta = evt.target.responseText;
                            $('.facebook-preview__image').attr('src','../viewer/content/'+window.image_meta);
                            $('.facebook-preview__image').removeClass('d-none');
                            $('#div_delete_image_meta').show();
                            $('#div_upload_image_meta').hide();
                            save_metadata('globe',window.id_globe);
                        }
                    }
                    upadte_progressbar_im(0);
                    frm[0].reset();
                },false);
                ajax.addEventListener('error',function(evt){
                    show_error_im('upload failed');
                    upadte_progressbar_im(0);
                },false);
                ajax.addEventListener('abort',function(evt){
                    show_error_im('upload aborted');
                    upadte_progressbar_im(0);
                },false);
                ajax.open('POST',url);
                ajax.send(data);
                return false;
            });

            function upadte_progressbar_im(value){
                $('#progressBar_im').css('width',value+'%').html(value+'%');
                if(value==0){
                    $('.progress').hide();
                }else{
                    $('.progress').show();
                }
            }

            function show_error_im(error){
                $('.progress').hide();
                $('#error_im').show();
                $('#error_im').html(error);
            }

            var timer_meta;
            $('#meta_title,#meta_description').on('input',function(){
                if(timer_meta) {
                    clearTimeout(timer_meta);
                }
                timer_meta = setTimeout(function() {
                    save_metadata('globe',window.id_globe);
                },400);
            });
        });
        $("input[type='text']").change(function(){
            if($(this).attr('id')!='point_pos_lat' && $(this).attr('id')!='point_pos_lon' && $(this).attr('id')!='link_f' && $(this).attr('id')!='meta_title' && $(this).attr('id')!='meta_description') {
                window.globe_need_save = true;
            }
        });
        $("input[type='checkbox']").change(function(){
            window.globe_need_save = true;
        });
        $("select").change(function(){
            window.globe_need_save = true;
        });
        $(window).on('beforeunload', function(){
            if(window.globe_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict

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
                    window.globe_need_save = true;
                    window.s_logo_image = evt.target.responseText;
                    $('#div_image_logo img').attr('src','../viewer/content/'+window.s_logo_image);
                    $('#div_delete_logo').show();
                    $('#div_image_logo').show();
                    $('#div_upload_logo').hide();
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

    function setTooltip(btn, message) {
        $(btn).tooltip('hide')
            .attr('data-original-title', message)
            .tooltip('show');
    }
    function hideTooltip(btn) {
        setTimeout(function() {
            $(btn).tooltip('hide');
        }, 1000);
    }
</script>