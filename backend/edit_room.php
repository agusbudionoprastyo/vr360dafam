<?php
session_start();
$id_room = $_GET['id'];
$room = get_room($id_room,$_SESSION['id_user']);
if($room!==false) {
    $_SESSION['id_room_sel']=$id_room;
    $_SESSION['id_virtualtour_sel']=$room['id_virtualtour'];
    $virtual_tour = get_virtual_tour($room['id_virtualtour'],$_SESSION['id_user']);
    $tmp_languages = get_languages_vt();
    $array_languages = $tmp_languages[0];
    $default_language = $tmp_languages[1];
    if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
    $link = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","viewer/index.php?code=",$_SERVER['SCRIPT_NAME']).$virtual_tour['code']."&room=".$room['id'];
    $virtual_tour = get_virtual_tour($room['id_virtualtour'],$_SESSION['id_user']);
    $_SESSION['compress_jpg'] = $virtual_tour['compress_jpg'];
    $_SESSION['max_width_compress'] = $virtual_tour['max_width_compress'];
    $_SESSION['keep_original_panorama'] = $virtual_tour['keep_original_panorama'];
    if(!empty($room['filters'])) {
        $filters = json_decode($room['filters'],true);
    } else {
        $filters = [];
        $filters['brightness'] = 100;
        $filters['contrast'] = 100;
        $filters['saturate'] = 100;
        $filters['grayscale'] = 0;
    }
    switch($room['type']) {
        case 'image':
            $pano_label = '<i class="far fa-image"></i> '._("Panorama Image");
            $upload_label = _("Upload Image");
            break;
        case 'video':
            $pano_label = '<i class="fas fa-video"></i> '._("Panorama Video");
            $upload_label = _("Upload Video");
            break;
        case 'hls':
            $pano_label = '<i class="fas fa-film"></i> '._("Panorama Video Stream (HLS)");
            $upload_label = _("Upload Initial Image");
            break;
        case 'lottie':
            $pano_label = '<i class="fab fa-deviantart"></i> Lottie';
            $upload_label = _("Upload Initial Image");
            break;
    }
    $s3_params = check_s3_tour_enabled($room['id_virtualtour']);
    $s3_enabled = false;
    $s3_url = "";
    $path_base_url = "../viewer/";
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $path_base_url = $s3_url."viewer/";
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
    $thumb_link = "";
    if($s3_enabled) {
        $panorama_image_link = $s3_url."viewer/panoramas/".$room['panorama_image'];
    } else {
        $panorama_image_link = "../viewer/panoramas/".$room['panorama_image'];
    }
    if(empty($room['thumb_image'])) {
        if($s3_enabled) {
            $thumb_link = $s3_url."viewer/panoramas/preview/".$room['panorama_image'];
        } else {
            $thumb_link = "../viewer/panoramas/preview/".$room['panorama_image'];
        }
    } else {
        if($s3_enabled) {
            $thumb_link = $s3_url."viewer/panoramas/thumb_custom/".$room['thumb_image'];
        } else {
            $thumb_link = "../viewer/panoramas/thumb_custom/".$room['thumb_image'];
        }
    }
    if($s3_enabled) {
        list($width, $height, $type, $attr) = getimagesize("s3://$s3_bucket_name/viewer/panoramas/".$room['panorama_image']);
    } else {
        list($width, $height, $type, $attr) = getimagesize("../viewer/panoramas/".$room['panorama_image']);
    }
    $ratio = $width/$height;
    if(($ratio>2.2) || ($ratio < 1.8)) {
        $equirectangular = false;
    } else {
        $equirectangular = true;
    }
    $background_color = $room['background_color'];
    $tmp = explode(",",$background_color);
    $tmp[0] = round(((float) $tmp[0]) * 255);
    $tmp[1] = round(((float) $tmp[1]) * 255);
    $tmp[2] = round(((float) $tmp[2]) * 255);
    $background_color = implode(",",$tmp);
    $settings = get_settings();
    $change_plan = $settings['change_plan'];
    if($change_plan) {
        $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
    } else {
        $msg_change_plan = "";
    }
    $presets_position = get_presets($room['id_virtualtour'],'room_positions');
    $show_in_ui_annotation = $virtual_tour['show_annotations'];
    $show_in_ui_audio = $virtual_tour['show_audio'];
    $blur_disabled = false;
    if($s3_enabled) {
        if(!file_exists("s3://$s3_bucket_name/viewer/panoramas/original/".$room['panorama_image'])) {
            $blur_disabled = true;
        }
    } else {
        if(!file_exists(dirname(__FILE__).'/../viewer/panoramas/original/'.$room['panorama_image'])) {
            $blur_disabled = true;
        }
    }
    if(!isset($_SESSION['tab_edit_room'])) {
        $_SESSION['tab_edit_room']='settings';
    } else {
        if($virtual_tour['ar_simulator'] && $_SESSION['tab_edit_room']=='protect') {
            $_SESSION['tab_edit_room']='settings';
        }
        if(($virtual_tour['ar_simulator'] || $room['type']!='image') && $_SESSION['tab_edit_room']=='multiroom') {
            $_SESSION['tab_edit_room']='settings';
        }
    }
    if(!isset($_SESSION['tab_edit_room_preview'])) {
        $_SESSION['tab_edit_room_preview']='view';
    }
    $plan_permissions = get_plan_permission($_SESSION['id_user']);
    $max_file_size_upload = $plan_permissions['max_file_size_upload'];
    $max_file_size_upload_system = _GetMaxAllowedUploadSize();
    if($max_file_size_upload<=0 || $max_file_size_upload>$max_file_size_upload_system) {
        $max_file_size_upload = $max_file_size_upload_system;
    }
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($_SESSION['id_user'],$room['id_virtualtour']);
        if($editor_permissions['edit_rooms']==0) {
            $room=false;
        }
    }
    $nadir_permission = $plan_permissions['enable_nadir_logo'];
    $nadir_logo = $virtual_tour['nadir_logo'];
    $nadir_size = $virtual_tour['nadir_size'];
    $url_logo = "";
    $url_map = "";
    $url_song = "";
    if(!empty($room['logo'])) {
        if($s3_enabled) {
            $url_logo = $s3_url."viewer/content/".$room['logo'];
        } else {
            $url_logo = $path_base_url."content/".$room['logo'];
        }
    }
    if(!empty($room['map'])) {
        if($s3_enabled) {
            $url_map = $s3_url."viewer/maps/".$room['map'];
        } else {
            $url_map = $path_base_url."maps/".$room['map'];
        }
    }
    if(!empty($room['song'])) {
        if($s3_enabled) {
            $url_song = $s3_url."viewer/content/".$room['song'];
        } else {
            $url_song = $path_base_url."content/".$room['song'];
        }
    }
    $array_input_lang = array();
    $query_lang = "SELECT * FROM svt_rooms_lang WHERE id_room=$id_room;";
    $result_lang = $mysqli->query($query_lang);
    if($result_lang) {
        if ($result_lang->num_rows > 0) {
            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                $language = $row_lang['language'];
                unset($row_lang['id_room']);
                unset($row_lang['language']);
                $array_input_lang[$language]=$row_lang;
            }
        }
    }
}
?>

<?php if(!$room): ?>
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

<?php include("check_plan.php"); ?>

<link rel="stylesheet" type="text/css" href="vendor/cropper/cropper.min.css">
<script type="text/javascript" src="vendor/cropper/cropper.min.js"></script>

<ul class="nav bg-white nav-pills nav-fill mb-2">
    <li class="nav-item">
        <a class="nav-link <?php echo ($_SESSION['tab_edit_room']=='settings') ? 'active' : ''; ?>" onclick="set_session_tab('edit_room','settings')" data-toggle="pill" href="#settings_tab"><i class="fas fa-cogs"></i> <?php echo strtoupper(_("SETTINGS")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($_SESSION['tab_edit_room']=='preview') ? 'active' : ''; ?>" onclick="click_preview();set_session_tab('edit_room','preview')" data-toggle="pill" href="#preview_tab"><i class="fas fa-eye"></i> <?php echo strtoupper(_("PREVIEW")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($_SESSION['tab_edit_room']=='contents') ? 'active' : ''; ?>" onclick="set_session_tab('edit_room','contents')" data-toggle="pill" href="#contents_tab"><i class="fas fa-photo-video"></i> <?php echo strtoupper(_("CONTENTS")); ?></a>
    </li>
    <li class="nav-item <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>" >
        <a class="nav-link <?php echo ($_SESSION['tab_edit_room']=='protect') ? 'active' : ''; ?>" onclick="set_session_tab('edit_room','protect')" data-toggle="pill" href="#protect_tab"><i class="fas fa-lock"></i> <?php echo strtoupper(_("PROTECT")); ?></a>
    </li>
    <li class="nav-item <?php echo ($room['type']=='image') ? '' : 'd-none'; ?> <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
        <a class="nav-link <?php echo ($_SESSION['tab_edit_room']=='multiroom') ? 'active' : ''; ?>" onclick="set_session_tab('edit_room','multiroom')" data-toggle="pill" href="#multiroom_tab"><i class="fas fa-columns"></i> <?php echo strtoupper(_("MULTIPLE ROOM VIEWS")); ?></a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room']=='settings') ? 'active' : ''; ?>" id="settings_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cog"></i> <?php echo _("General"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-<?php echo ($virtual_tour['ar_simulator']) ? '12' : '6'; ?>">
                                        <div class="form-group">
                                            <label for="name"><?php echo _("Name"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'name'); ?>
                                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($room['name']); ?>" />
                                            <?php foreach ($array_languages as $lang) {
                                                if($lang!=$default_language) : ?>
                                                    <input style="display:none;" type="text" class="form-control input_lang" data-target-id="name" data-lang="<?php echo $lang; ?>" value="<?php echo htmlspecialchars($array_input_lang[$lang]['name']); ?>" />
                                                <?php endif;
                                            } ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
                                        <div class="form-group">
                                            <label for="visible"><?php echo _("Visible"); ?> <i title="<?php echo _("show room on tour"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                            <input type="checkbox" id="visible" <?php echo ($room['visible'])?'checked':''; ?> />
                                        </div>
                                    </div>
                                    <div class="col-md-3 <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
                                        <div class="form-group">
                                            <label for="visible_list"><?php echo _("Visible List"); ?> <i title="<?php echo _("show room on list slider"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                            <input type="checkbox" id="visible_list" <?php echo ($room['visible_list'])?'checked':''; ?> />
                                        </div>
                                    </div>
                                    <?php if ($room['type']=='video') : ?>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="video_end_goto"><?php echo _("Video End - Go to"); ?> <i title="<?php echo _("destination room when the video ends"); ?>" class="help_t fas fa-question-circle"></i></label>
                                                <select data-live-search="true" id="video_end_goto" class="form-control">
                                                    <option id="0" <?php echo ($room['video_end_goto']==0) ? 'checked' : ''; ?>><?php echo _("None"); ?></option>
                                                    <?php echo get_rooms_option($room['id_virtualtour'],$room['video_end_goto'],$id_room); ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="link"><i class="fas fa-link"></i> <?php echo _("Room Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'link'); ?>
                                            <div class="input-group">
                                                <input readonly type="text" class="form-control bg-white" id="link" value="<?php echo $link; ?>" />
                                                <?php foreach ($array_languages as $lang) {
                                                    if($lang!=$default_language) : ?>
                                                        <input id="link_<?php echo $lang; ?>" style="display:none;" readonly type="text" class="form-control input_lang" data-target-id="link" data-lang="<?php echo $lang; ?>" value="<?php echo $link."&lang=$lang"; ?>" />
                                                    <?php endif;
                                                } ?>
                                                <div class="input-group-append">
                                                    <a id="open_link" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success btn-xs help_t" href="<?php echo $link; ?>" target="_blank">
                                                        <i style="padding-top:5px;" class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <a style="display:none;" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success btn-xs help_t input_lang" data-target-id="open_link" data-lang="<?php echo $lang; ?>" href="<?php echo $link."&lang=$lang"; ?>" target="_blank">
                                                                <i style="padding-top:5px;" class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        <?php endif;
                                                    } ?>
                                                    <button id="copy_link" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn_link btn btn-primary btn-xs help_t" data-clipboard-target="#link">
                                                        <i style="padding-bottom:2px;" class="far fa-clipboard"></i>
                                                    </button>
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <button style="display:none;" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn_link btn btn-primary btn-xs help_t cpy_btn input_lang" data-target-id="copy_link" data-lang="<?php echo $lang; ?>" data-clipboard-target="#link_<?php echo $lang; ?>">
                                                                <i style="padding-bottom:2px;" class="far fa-clipboard"></i>
                                                            </button>
                                                        <?php endif;
                                                    } ?>
                                                    <button id="qrcode_link" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link; ?>');" class="btn btn-secondary btn-xs help_t">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                    <?php foreach ($array_languages as $lang) {
                                                        if($lang!=$default_language) : ?>
                                                            <button style="display:none;" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link."&lang=$lang"; ?>');" class="btn btn-secondary btn-xs help_t input_lang" data-target-id="qrcode_link" data-lang="<?php echo $lang; ?>">
                                                                <i class="fas fa-qrcode"></i>
                                                            </button>
                                                        <?php endif;
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                                        <div id="share_link" style="margin-top: 5px" class="a2a_kit a2a_kit_size_32 a2a_default_style" data-a2a-url="<?php echo $link; ?>">
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
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <div style="display:none;margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style input_lang" data-a2a-url="<?php echo $link."&lang=$lang"; ?>" data-target-id="share_link" data-lang="<?php echo $lang; ?>">
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
                                            <?php endif;
                                        } ?>
                                        <script async src="https://static.addtoany.com/menu/page.js"></script>
                                    </div>
                                </div>
                            </div>
                           <div class="col-md-4">
                               <div class="col-md-12">
                                   <label style="margin-left:12px"><?php echo _("Logo"); ?> <i title="<?php echo _("if the logo is present it will be displayed instead of the room name"); ?>" class="help_t fas fa-question-circle"></i></label>
                                   <div style="background-color:#868686;display:none;width:calc(100% - 24px);margin:0 auto;" id="div_image_logo" class="col-md-12 text-center">
                                       <img style="width:100%;max-width:200px;max-height:60px;object-fit:contain" src="<?php echo $url_logo; ?>" />
                                   </div>
                                   <div style="display: none" id="div_delete_logo" class="col-md-12 mt-2">
                                       <label><?php echo _("Size"); ?></label>
                                       <div class="input-group input-group-sm mb-2">
                                           <input type="number" min="1" id="logo_height" class="form-control form-control-sm" value="<?php echo $room['logo_height']; ?>" >
                                           <div class="input-group-append">
                                               <span class="input-group-text">px</span>
                                           </div>
                                       </div>
                                       <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_room_logo();" class="btn btn-sm btn-block btn-danger"><?php echo _("DELETE LOGO"); ?></button>
                                   </div>
                                   <div style="display: none" id="div_upload_logo">
                                       <?php if($upload_content) : ?>
                                       <form id="frm_l" action="ajax/upload_logo_image.php" method="POST" enctype="multipart/form-data">
                                           <div class="row">
                                               <div class="col-md-12">
                                                   <div class="input-group">
                                                       <div class="custom-file">
                                                           <input type="file" class="custom-file-input" id="txtFile_l" name="txtFile_l" />
                                                           <label class="custom-file-label text-left" for="txtFile_l"><?php echo _("Choose file"); ?></label>
                                                       </div>
                                                   </div>
                                               </div>
                                               <div class="col-md-12">
                                                   <div class="form-group">
                                                       <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_l" value="<?php echo _("Upload Logo Image"); ?>" />
                                                   </div>
                                               </div>
                                               <div class="col-md-12">
                                                   <div class="preview text-center">
                                                       <div id="progress_l" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                           <div class="progress-bar" id="progressBar_l" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                               0%
                                                           </div>
                                                       </div>
                                                       <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_l"></div>
                                                   </div>
                                               </div>
                                           </div>
                                       </form>
                                       <?php endif; ?>
                                   </div>
                               </div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <a href="#collapsePI" class="d-block card-header py-3 collapsed" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                        <h6 class="m-0 font-weight-bold text-primary"><?php echo $pano_label; ?> <i style="font-size: 12px">(<?php echo _("click to view / change"); ?>)</i></h6>
                    </a>
                    <div class="collapse" id="collapsePI">
                        <div class="card-body">
                            <img id="panorama_image" style="width: 100%" data-src="<?php echo $panorama_image_link; ?>">
                            <?php if($upload_content) : ?>
                            <form class="mt-4" id="frm" action="<?php echo ($room['type']=='video') ? 'ajax/upload_room_video.php' : 'ajax/upload_room_image.php'; ?>" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                                <label class="custom-file-label" for="txtFile"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo $upload_label; ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
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
                            <?php endif; ?>
                            <?php if($room['type']=='hls') : ?>
                                <p><?php echo _("The initial image must be the same size as the video stream."); ?></p>
                                <div class="form-group">
                                    <label for="panorama_url"><?php echo _("HLS Video Url"); ?></label>
                                    <input type="text" class="form-control" id="panorama_url" value="<?php echo $room['panorama_url']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if($room['type']=='lottie') : ?>
                                <p><?php echo _("The initial image must be the same size as the lottie file."); ?></p>
                                <?php if($upload_content) : ?>
                                    <form class="mt-4" id="frm_j" action="ajax/upload_room_json.php" method="POST" enctype="multipart/form-data">
                                        <label>Lottie <?php echo _("File"); ?></label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_j" name="txtFile_l" />
                                                        <label class="custom-file-label" for="txtFile_j"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_j" value="<?php echo _("Upload JSON File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="preview text-center">
                                                    <div class="progress progress_j mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_j" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_j"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if($user_info['role']=='administrator' && $room['type']!='hls') : ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-pie"></i> <?php echo _("Disk Space Used"); ?> <i style="font-size:12px;"><?php echo _("(only visible to administrators)"); ?></i></h6>
                    </div>
                    <div class="card-body pb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <?php
                                    switch($room['type']) {
                                        case 'image':
                                            $label_image = _("Original Image");
                                            break;
                                        case 'video':
                                            $label_image = _("Original Video");
                                            break;
                                        case 'lottie':
                                            $label_image = _("Original JSON");
                                            break;
                                    }
                                    ?>
                                    <label><?php echo $label_image; ?></label><br>
                                    <span id="disk_space_original" class="h5 mb-0 font-weight-bold text-gray-800">--</span>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo ($room['type']!='image') ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label><?php echo _("Compressed Image"); ?></label><br>
                                    <span id="disk_space_compressed" class="h5 mb-0 font-weight-bold text-gray-800">--</span>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo ($room['type']!='image') ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label><?php echo _("Multires Assets"); ?></label><br>
                                    <span id="disk_space_multires" class="h5 mb-0 font-weight-bold text-gray-800">--</span>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo ($room['type']!='image') ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label><?php echo _("Total"); ?></label><br>
                                    <span id="disk_space_total" class="h5 mb-0 font-weight-bold text-gray-800">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="row <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary d-inline-block"><i class="fas fa-retweet"></i> <?php echo _("Transition"); ?></h6>
                        <input class="d-inline-block ml-2" type="checkbox" id="transition_override" <?php echo ($room['transition_override']==1) ? 'checked':''; ?>>
                        <label class="mb-0 align-middle" for="transition_override"><?php echo _("Override"); ?> <i title="<?php echo _("override transition settings for this room"); ?>" class="help_t fas fa-question-circle"></i></label>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_effect"><?php echo _("Transition Effect"); ?> <i title="<?php echo _("transition animation effect"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <select <?php echo ($room['transition_override']==0) ? 'disabled':''; ?> id="transition_effect" class="form-control">
                                        <option <?php echo ($room['transition_effect']=='blind') ? 'selected':''; ?> id="blind">Blind</option>
                                        <option <?php echo ($room['transition_effect']=='bounce') ? 'selected':''; ?> id="bounce">Bounce</option>
                                        <option <?php echo ($room['transition_effect']=='clip') ? 'selected':''; ?> id="clip">Clip</option>
                                        <option <?php echo ($room['transition_effect']=='drop') ? 'selected':''; ?> id="drop">Drop</option>
                                        <option <?php echo ($room['transition_effect']=='fade') ? 'selected':''; ?> id="fade">Fade</option>
                                        <option <?php echo ($room['transition_effect']=='puff') ? 'selected':''; ?> id="puff">Puff</option>
                                        <option <?php echo ($room['transition_effect']=='pulsate') ? 'selected':''; ?> id="pulsate">Pulsate</option>
                                        <option <?php echo ($room['transition_effect']=='scale') ? 'selected':''; ?> id="scale">Scale</option>
                                        <option <?php echo ($room['transition_effect']=='shake') ? 'selected':''; ?> id="shake">Shake</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_fadeout"><?php echo _("Transition Duration"); ?> <i title="<?php echo _("transition duration in milliseconds"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input <?php echo ($room['transition_override']==0) ? 'disabled':''; ?> type="number" min="0" class="form-control" id="transition_fadeout" value="<?php echo $room['transition_fadeout']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_time"><?php echo _("Transition Zoom Time"); ?> <i title="<?php echo _("transition time before entering in this room in milliseconds"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input <?php echo ($room['transition_override']==0) ? 'disabled':''; ?> type="number" min="0" class="form-control" id="transition_time" value="<?php echo $room['transition_time']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_zoom"><?php echo _("Transition Zoom Level"); ?> (<span id="transition_zoom_val"><?php echo $virtual_tour['transition_zoom']; ?></span>) <i title="<?php echo _("transition zoom level before entering in this room"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($room['transition_override']==0) ? 'disabled':''; ?> oninput="change_transition_zoom();" type="range" min="0" max="100" class="form-control-range" id="transition_zoom" value="<?php echo $room['transition_zoom']; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room']=='preview') ? 'active' : ''; ?>" id="preview_tab">
        <?php if(!$equirectangular) : ?>
            <div id="warning_not_equirectangular" class="card bg-warning text-white shadow mb-4">
                <div class="card-body">
                    <div><?php echo _("A not fully equirectangular and 360 degree image was detected. Please adjust the position settings for correct viewing."); ?></div>
                    <div class="mt-2"><?php echo _("Alternatively, try to fix with these presets:"); ?>
                        <button onclick="preset_positions(0);" class="btn btn-sm btn-light mb-1"><?php echo _("360 Horizontal Panorama"); ?></button>
                        <button onclick="preset_positions(1);" class="btn btn-sm btn-light mb-1"><?php echo _("180 Horizontal Panorama"); ?></button>
                        <button onclick="preset_positions(2);" class="btn btn-sm btn-light mb-1"><?php echo _("16:9 Flat Image"); ?></button>
                        <button onclick="preset_positions(3);" class="btn btn-sm btn-light mb-1"><?php echo _("4:3 Flat Image"); ?></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header p-0 pt-2">
                        <h6 class="float-left pt-2 pl-3 font-weight-bold text-primary"><i class="far fa-eye"></i> <?php echo _("Preview"); ?> <i title="<?php echo _("hold click and move the mouse to change the position"); ?>" class="help_t fas fa-question-circle"></i></h6>
                        <ul class="nav nav-tabs float-right">
                            <li class="nav-item">
                                <a onclick="hide_grid_position();hide_btn_toggle_effects();show_btn_screenshot();set_session_tab('edit_room_preview','view');" class="nav-link <?php echo ($_SESSION['tab_edit_room_preview']=='view') ? 'active' : ''; ?>" data-toggle="tab" href="#view_tab"><?php echo strtoupper(_("view")); ?></a>
                            </li>
                            <li class="nav-item">
                                <a onclick="hide_grid_position();hide_btn_toggle_effects();hide_btn_screenshot();fix_north();set_session_tab('edit_room_preview','north');" id="north_tab_btn" class="nav-link disabled <?php echo ($_SESSION['tab_edit_room_preview']=='north') ? 'active' : ''; ?>" data-toggle="tab" href="#north_tab"><?php echo strtoupper(_("north")); ?></a>
                            </li>
                            <li class="nav-item">
                                <a onclick="show_grid_position();hide_btn_toggle_effects();hide_btn_screenshot();set_session_tab('edit_room_preview','positions');" id="positions_tab_btn" class="nav-link <?php echo ($_SESSION['tab_edit_room_preview']=='positions') ? 'active' : ''; ?>" data-toggle="tab" href="#position_tab"><?php echo ((!$equirectangular) ? '<i class="fas fa-exclamation-circle"></i> ' : '') . strtoupper(_("positions")); ?></a>
                            </li>
                            <li class="nav-item">
                                <a onclick="hide_grid_position();show_btn_toggle_effects();hide_btn_screenshot();set_session_tab('edit_room_preview','effects');" class="nav-link <?php echo ($_SESSION['tab_edit_room_preview']=='effects') ? 'active' : ''; ?>" data-toggle="tab" href="#effects_tab"><?php echo strtoupper(_("effects")); ?></a>
                            </li>
                            <li class="nav-item">
                                <a onclick="hide_grid_position();hide_btn_toggle_effects();hide_btn_screenshot();set_session_tab('edit_room_preview','bulk');" class="nav-link <?php echo ($_SESSION['tab_edit_room_preview']=='bulk') ? 'active' : ''; ?>" data-toggle="tab" href="#bulk_tab"><?php echo strtoupper(_("bulk")); ?></a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8 col-md-6">
                                <div id="div_panorama">
                                    <div style="width:100%;height:400px;position:relative;border-radius:10px;" id="panorama"></div>
                                    <div style="position:relative;" id="panorama_video"></div>
                                    <div style="display:none" id="canvas_p"></div>
                                    <div style="display:none" id="canvas_lottie"></div>
                                    <div class="mt-2 text-center" style="width: 100%;">
                                        <div style="display:none;" id="initial_position_div">
                                            <input readonly type="hidden" id="yaw_pitch" value="<?php echo $room['yaw'].",".$room['pitch']; ?>" />
                                            <?php echo _("Initial Position"); ?> <i title="<?php echo _("initial position when you enter in this room (drag the view and click set)"); ?>" class="help_t fas fa-question-circle"></i>&nbsp;&nbsp;&nbsp;<b><span id="yaw_pitch_debug"><?php echo $room['yaw'].",".$room['pitch']; ?></span></b>
                                            <button onclick="set_yaw_pitch();return false;" class="btn btn-sm btn-info ml-2" type="button"><?php echo _("Set"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                                            <div style="font-weight:bold;border-color:#36b9cc;min-width:60px;" class="btn btn-sm px-2 text-center"><span style="color: #36b9cc" id="yaw_pitch_saved"><?php echo $room['yaw'].",".$room['pitch']; ?></span></div>
                                        </div>
                                        <div style="display:none;" id="north_div">
                                            <input readonly type="hidden" id="northOffset" value="<?php echo $room['northOffset']; ?>" />
                                            <?php echo _("Compass North"); ?> <i title="<?php echo _("indication of the north position of this room (drag the view and click set)"); ?>" class="help_t fas fa-question-circle"></i>&nbsp;&nbsp;&nbsp;<b><span id="northOffset_debug">--</span></b>
                                            <button onclick="set_northOffset();return false;" class="btn btn-sm btn-warning ml-2" type="button"><?php echo _("Set"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                                            <div style="font-weight:bold;border-color:#f6c23e;min-width:60px;" class="btn btn-sm px-2 text-center"><span style="color: #f6c23e" id="northOffset_save"><?php echo $room['northOffset']; ?></span></div>
                                        </div>
                                    </div>
                                    <div class="row mt-2 mb-3" style="width: 100%;max-width:400px;margin:0 auto;">

                                    </div>
                                </div>
                                <div id="div_thumbnail" style="display: none">
                                    <div style="width: 100%;height: 400px;">
                                        <img id="panorama_image_edit" style="display: block;max-width: 100%;height: 100%;" src="" />
                                    </div>
                                    <div class="mt-2 text-center">
                                        <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_crop_thumb" onclick="crop_thumbnail();" type="button" class="btn btn-success"><?php echo _("Save"); ?></button>
                                        <button onclick="close_edit_thumbnail();" type="button" class="btn btn-secondary"><?php echo _("Close"); ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="tab-content">
                                    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room_preview']=='view') ? 'active' : ''; ?>" id="view_tab">
                                        <div class="row">
                                            <div class="col-md-12 text-center mb-2">
                                                <div class="form-group <?php echo (empty($nadir_logo) || !$nadir_permission) ? 'd-none' : ''; ?>">
                                                    <label style="color: #36b9cc" for="show_nadir"><?php echo _("Show Nadir Logo"); ?> <input onchange="toggle_nadir_logo();" id="show_nadir" type="checkbox" <?php echo ($room['show_nadir']?'checked':'') ?>></label>
                                                </div>
                                            </div>
                                            <div class="col-md-12 text-center">
                                                <img id="thumb_image" style="width: 100%;max-width: 250px;" src="<?php echo $thumb_link; ?>" /><br>
                                                <button id="btn_edit_thumbnail" onclick="edit_thumbnail();" style="width: 100%;max-width: 250px;" class="btn btn-sm btn-primary disabled"><i class="fas fa-crop-alt"></i>&nbsp;&nbsp;<?php echo _("EDIT THUMBNAIL"); ?></button>
                                                <?php if($upload_content) : ?>
                                                    <form class="mt-3 disabled" id="frm_thumb" action="ajax/upload_custom_thumb.php" method="POST" enctype="multipart/form-data">
                                                        <div class="form-group text-center m-auto" style="width: 100%;max-width: 250px;">
                                                            <div class="input-group mb-1">
                                                                <div class="custom-file">
                                                                    <input type="file" class="custom-file-input" id="txtFile_thumb" name="txtFile_thumb" />
                                                                    <label class="custom-file-label text-left" for="txtFile_thumb"><?php echo _("Choose file"); ?></label>
                                                                </div>
                                                            </div>
                                                            <button <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" form="frm_thumb" class="btn btn-sm btn-block btn-primary" id="btnUpload_thumb"><i class='fas fa-upload'></i>&nbsp;&nbsp;<?php echo _('UPLOAD THUMBNAIL'); ?></button>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room_preview']=='positions') ? 'active' : ''; ?>" id="position_tab">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="presets"><?php echo _("Presets"); ?></label>
                                                    <div class="input-group">
                                                        <select onchange="change_preset();" id="presets" class="form-control">
                                                            <option id="0"><?php echo _("Add new preset"); ?></option>
                                                            <?php foreach ($presets_position as $preset) {
                                                                $id_preset = $preset['id'];
                                                                $name_preset = $preset['name'];
                                                                $value_preset = $preset['value'];
                                                                echo "<option data-value='$value_preset' id='$id_preset'>$name_preset</option>";
                                                            } ?>
                                                        </select>
                                                        <div class="input-group-append preset_buttons">
                                                            <button id="btn_save_preset" title="<?php echo _("Save Preset"); ?>" onclick="save_preset('room_positions');" class="btn btn-success" type="button"><i class="fas fa-save"></i></button>
                                                            <button id="btn_apply_preset_room" title="<?php echo _("Apply Preset to this Room"); ?>" onclick="apply_preset_room('room_positions');" class="btn btn-primary disabled" type="button"><i class="fas fa-vector-square"></i></button>
                                                            <button id="btn_apply_preset_tour" title="<?php echo _("Apply Preset to all Rooms"); ?>" onclick="open_modal_apply_preset_tour('room_positions');" class="btn btn-primary disabled" type="button"><i class="fas fa-route"></i></button>
                                                            <button id="btn_delete_preset" title="<?php echo _("Delete Preset"); ?>" onclick="delete_preset('room_positions');" class="btn btn-danger disabled" type="button"><i class="fas fa-trash"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="h_pitch"><?php echo _("Horizontal Pitch"); ?> (<span id="h_pitch_val"><?php echo $room['h_pitch']; ?></span>) <i title="<?php echo _("specifies pitch of image horizon (for correcting non-leveled panoramas)"); ?>" class="help_t fas fa-question-circle"></i></label>
                                                    <input min="-20" max="20" step="1" type="range" id="h_pitch" value="<?php echo $room['h_pitch']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="h_roll"><?php echo _("Horizontal Roll"); ?> (<span id="h_roll_val"><?php echo $room['h_roll']; ?></span>) <i title="<?php echo _("specifies roll of image horizon (for correcting non-leveled panoramas)"); ?>" class="help_t fas fa-question-circle"></i></label>
                                                    <input min="-20" max="20" step="1" type="range" id="h_roll" value="<?php echo $room['h_roll']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="min_pitch"><?php echo _("Lower Pitch"); ?> ° <i title="<?php echo _("maximum vertical inclination in degrees down (min 0 - max 90)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input <?php echo ($room['allow_pitch'])?'':'disabled'; ?> min="0" max="90" type="number" class="form-control" id="min_pitch" value="<?php echo $room['min_pitch']*-1; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="max_pitch"><?php echo _("Upper Pitch"); ?> ° <i title="<?php echo _("maximum vertical inclination in degrees up (min 0 - max 90)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input <?php echo ($room['allow_pitch'])?'':'disabled'; ?> min="0" max="90" type="number" class="form-control" id="max_pitch" value="<?php echo $room['max_pitch']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="min_yaw"><?php echo _("Left Yaw"); ?> ° <i title="<?php echo _("maximum horizontal inclination in degrees left (min 0 - max 180)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input min="0" max="180" type="number" class="form-control" id="min_yaw" value="<?php echo $room['min_yaw']*-1; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="max_yaw"><?php echo _("Right Yaw"); ?> ° <i title="<?php echo _("maximum horizontal inclination in degrees right (min 0 - max 180)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input min="0" max="180" type="number" class="form-control" id="max_yaw" value="<?php echo $room['max_yaw']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="haov"><?php echo _("HAOV"); ?> ° <i title="<?php echo _("sets the panorama’s horizontal angle of view, in degrees (min 0 - max 360)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input min="0" max="360" type="number" class="form-control <?php echo ($room['multires']==1) ? 'disabled' : ''; ?>" id="haov" value="<?php echo $room['haov']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="vaov"><?php echo _("VAOV"); ?> ° <i title="<?php echo _("sets the panorama’s vertical angle of view, in degrees (min 0 - max 180)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input min="0" max="180" type="number" class="form-control <?php echo ($room['multires']==1) ? 'disabled' : ''; ?>" id="vaov" value="<?php echo $room['vaov']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="hfov"><?php echo _("HFOV"); ?> ° <i title="<?php echo _("sets the panorama’s horizontal field of view (0 to keep default virtual tour setting)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input type="number" class="form-control" id="hfov" value="<?php echo $room['hfov']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="background_color"><?php echo _("Background Color"); ?> <i title="<?php echo _("background color shown for partial panoramas"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input type="text" class="form-control" id="background_color" value="rgb(<?php echo $background_color; ?>)" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="allow_hfov"><?php echo _("Allow Zoom"); ?> <i title="<?php echo _("enables zoom"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input type="checkbox" id="allow_hfov" <?php echo ($room['allow_hfov'])?'checked':''; ?> />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="allow_pitch"><?php echo _("Allow Pitch"); ?> <i title="<?php echo _("enables vertical inclination"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <input type="checkbox" id="allow_pitch" <?php echo ($room['allow_pitch'])?'checked':''; ?> />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room_preview']=='north') ? 'active' : ''; ?>" id="north_tab">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label style="color:#f6c23e;" for="north_map"><?php echo _("Compass North"); ?> (<b id="north_map_val"><?php echo $room['northOffset']; ?></b>)</label>
                                                    <input oninput="change_north_map();" onchange="change_north_map();" min="0" max="360" step="1" type="range" id="north_map" value="<?php echo $room['northOffset']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="btn-group btn-group-toggle mt-2 mb-2" data-toggle="buttons" style="width: 100%;">
                                                    <label class="btn btn-secondary active">
                                                        <input type="radio" name="north_radio" id="floorplan" autocomplete="off" checked> <?php echo _("Floorplan"); ?>
                                                    </label>
                                                    <label class="btn btn-secondary">
                                                        <input type="radio" name="north_radio" id="map" autocomplete="off"> <?php echo _("Map"); ?>
                                                    </label>
                                                </div>
                                                <div id="floorplan_div" style="position:relative;" class="map">
                                                    <?php if(!empty($room['map'])) { ?>
                                                        <img style="width: 100%" class='map_image' draggable='false' src='<?php echo $url_map; ?>'>
                                                        <div data-scale='1.0' style='display:none;transform: rotate(0deg) scale(1.0);top:<?php echo $room['map_top']; ?>px;left:<?php echo $room['map_left']; ?>px;' class='pointer_view pointer_<?php echo $room['id']; ?>'>
                                                            <div class="view_direction__arrow"></div>
                                                            <div class="view_direction__center"></div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <p><?php echo _("No associated floorplan."); ?></p>
                                                    <?php } ?>
                                                </div>
                                                <div style="display:none;" id="map_div">
                                                    <?php if(!empty($room['lat'])) { ?>
                                                        <div id="map_container"></div>
                                                    <?php } else { ?>
                                                        <p><?php echo _("No associated map."); ?></p>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room_preview']=='bulk') ? 'active' : ''; ?>" id="bulk_tab">
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <button class="btn btn-sm btn-block btn-info" data-toggle="modal" data-target="#modal_initial_position_apply" type="button"><?php echo _("Apply Initial Position to all Rooms"); ?></button>
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <button class="btn btn-sm btn-block btn-warning" data-toggle="modal" data-target="#modal_north_apply" type="button"><?php echo _("Apply North to all Rooms"); ?></button>
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <button class="btn btn-sm btn-block btn-primary" data-toggle="modal" data-target="#modal_effects_apply" type="button"><?php echo _("Apply Effects to all Rooms"); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room_preview']=='effects') ? 'active' : ''; ?>" id="effects_tab">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="brightness"><?php echo _("Brightness"); ?> (<span id="brightness_val"><?php echo $filters['brightness']; ?>%</span>)</label>
                                                    <input oninput="apply_room_filters();" min="50" max="150" step="1" type="range" id="brightness" value="<?php echo $filters['brightness']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="contrast"><?php echo _("Contrast"); ?> (<span id="contrast_val"><?php echo $filters['contrast']; ?>%</span>)</label>
                                                    <input oninput="apply_room_filters();" min="50" max="150" step="1" type="range" id="contrast" value="<?php echo $filters['contrast']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="saturate"><?php echo _("Saturate"); ?> (<span id="saturate_val"><?php echo $filters['saturate']; ?>%</span>)</label>
                                                    <input oninput="apply_room_filters();" min="50" max="150" step="1" type="range" id="saturate" value="<?php echo $filters['saturate']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="grayscale"><?php echo _("Grayscale"); ?> (<span id="grayscale_val"><?php echo $filters['grayscale']; ?>%</span>)</label>
                                                    <input oninput="apply_room_filters();" min="0" max="100" step="1" type="range" id="grayscale" value="<?php echo $filters['grayscale']; ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="effect"><?php echo _("Effect"); ?></label><br>
                                                    <select onchange="change_effect();" id="effect" class="form-control">
                                                        <option <?php echo ($room['effect']=='none') ? 'selected' : ''; ?> id="none"><?php echo _("None"); ?></option>
                                                        <option <?php echo ($room['effect']=='snow') ? 'selected' : ''; ?> id="snow"><?php echo _("Snow"); ?></option>
                                                        <option <?php echo ($room['effect']=='rain') ? 'selected' : ''; ?> id="rain"><?php echo _("Rain"); ?></option>
                                                        <option <?php echo ($room['effect']=='fog') ? 'selected' : ''; ?> id="fog"><?php echo _("Fog"); ?></option>
                                                        <option <?php echo ($room['effect']=='fireworks') ? 'selected' : ''; ?> id="fireworks"><?php echo _("Fireworks"); ?></option>
                                                        <option <?php echo ($room['effect']=='confetti') ? 'selected' : ''; ?> id="confetti"><?php echo _("Confetti"); ?></option>
                                                        <option <?php echo ($room['effect']=='sparkle') ? 'selected' : ''; ?> id="sparkle"><?php echo _("Sparkle"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="btn_edit_blur"><?php echo _("Blur"); ?> <i title="<?php echo _("allows you to blur parts of the panoramic image, such as faces and license plates"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                                    <button id="btn_edit_blur" onclick="save_room('blur',0);" class="btn btn-xs btn-block btn-primary <?php echo ($room['type']=='image' && !$blur_disabled) ? '' : 'disabled'; ?>"><i class="fas fa-fire-extinguisher"></i> <?php echo _("EDIT BLUR"); ?></button>
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
        <script>
            <?php if($_SESSION['tab_edit_room']=='preview') { ?>
            $(document).ready(function () {
               click_preview();
            });
            <?php } ?>
        </script>
    </div>
    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room']=='contents') ? 'active' : ''; ?>" id="contents_tab">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-comment-alt"></i> <?php echo _("Annotation"); ?> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_annotation>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_annotation==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_annotation==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="annotation_title"><?php echo _("Annotation Title"); ?> <i title="<?php echo _("title of the information about the room contained in the block at the top left (blank to not display)"); ?>" class="help_t fas fa-question-circle"></i></label><?php echo print_language_input_selector($array_languages,$default_language,'annotation_title'); ?>
                                    <input <?php echo (!$plan_permissions['enable_annotations']) ? 'disabled' : '' ; ?> type="text" class="form-control" id="annotation_title" value="<?php echo htmlspecialchars($room['annotation_title']); ?>" />
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input <?php echo (!$plan_permissions['enable_annotations']) ? 'disabled' : '' ; ?> style="display:none;" type="text" class="form-control input_lang" data-target-id="annotation_title" data-lang="<?php echo $lang; ?>" value="<?php echo htmlspecialchars($array_input_lang[$lang]['annotation_title']); ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="annotation_description"><?php echo _("Annotation Description"); ?> <i title="<?php echo _("description of the information about the room contained in the block at the top left (blank to not display)"); ?>" class="help_t fas fa-question-circle"></i></label><?php echo print_language_input_selector($array_languages,$default_language,'annotation_description'); ?>
                                    <textarea rows="3" <?php echo (!$plan_permissions['enable_annotations']) ? 'disabled' : '' ; ?> class="form-control" id="annotation_description"><?php echo htmlspecialchars($room['annotation_description']); ?></textarea>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <textarea rows="3" <?php echo (!$plan_permissions['enable_annotations']) ? 'disabled' : '' ; ?> style="display:none;" class="form-control input_lang" data-target-id="annotation_description" data-lang="<?php echo $lang; ?>"><?php echo htmlspecialchars($array_input_lang[$lang]['annotation_description']); ?></textarea>
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-music"></i> <?php echo _("Audio"); ?> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_audio>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_audio==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_audio==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row <?php echo (!$plan_permissions['enable_song']) ? 'disabled' : '' ; ?>">
                            <?php if($room['type']=='video' || $room['type']=='hls') { ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="audio_track_enable"><?php echo _("Audio embedded"); ?> <i title="<?php echo _("uses the audio track embedded in the video"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                        <input type="checkbox" id="audio_track_enable" <?php echo ($room['audio_track_enable'])?'checked':''; ?> />
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="song_loop"><?php echo _("Loop"); ?> <i title="<?php echo _("loops the audio or play once"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                        <input type="checkbox" id="song_loop" <?php echo ($room['song_loop'])?'checked':''; ?> />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="song_once"><?php echo _("Once"); ?> <i title="<?php echo _("play the audio only the first access to this room"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                        <input type="checkbox" id="song_once" <?php echo ($room['song_once'])?'checked':''; ?> />
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="song_bg_volume"><?php echo _("Background Volume"); ?> <i title="<?php echo _("sets the volume of the main tour audio when listening to this audio"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input min="0" max="1" step="0.1" id="song_bg_volume" type="range" class="form-control-range" value="<?php echo $room['song_bg_volume']; ?>">
                                </div>
                            </div>
                            <div id="div_exist_song" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_song"><?php echo _("Exist Audio"); ?></label>
                                    <select onchange="change_exist_song();" class="form-control" id="exist_song">
                                        <option selected id="0"><?php echo _("Upload new Audio"); ?></option>
                                        <?php echo get_option_exist_song(null,$room['id_virtualtour'],null); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_player_song" class="col-md-12">
                                <audio style="width: 100%" controls>
                                    <source src="<?php echo $url_song; ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            </div>
                            <div style="display: none" id="div_delete_song" class="col-md-12">
                                <button onclick="delete_room_song();return false;" id="btn_delete_song" class="btn btn-block btn-danger"><?php echo _("REMOVE AUDIO"); ?></button>
                            </div>
                            <div style="display: none" id="div_upload_song" class="col-md-12">
                                <?php if($upload_content) : ?>
                                <form id="frm_s" action="ajax/upload_song.php" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="txtFile_s" name="txtFile_s" />
                                                    <label class="custom-file-label" for="txtFile_s"><?php echo _("Choose file"); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_s" value="<?php echo _("Upload Audio (MP3)"); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="preview text-center">
                                                <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                    <div class="progress-bar" id="progressBar_s" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                        0%
                                                    </div>
                                                </div>
                                                <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_s"></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room']=='protect') ? 'active' : ''; ?>" id="protect_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-lock"></i> <?php echo _("Protect"); ?> <i title="<?php echo _("block room display until the protect form is filled"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_rooms_protect']) ? 'disabled' : '' ; ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="protect_type"><?php echo _("Type"); ?></label>
                                    <select onchange="change_protect_type();" class="form-control" id="protect_type">
                                        <option <?php echo ($room['protect_type']=='none') ? 'selected' : ''; ?> id="none"><?php echo _("None"); ?></option>
                                        <option <?php echo ($room['protect_type']=='passcode') ? 'selected' : ''; ?> id="passcode"><?php echo _("Passcode"); ?></option>
                                        <option <?php echo ($room['protect_type']=='leads') ? 'selected' : ''; ?> id="leads"><?php echo _("Leads"); ?></option>
                                        <option <?php echo ($room['protect_type']=='mailchimp') ? 'selected':''; ?> id="mailchimp"><?php echo _("Mailchimp Signup Form"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="protect_remember"><?php echo _("Remember"); ?> <i title="<?php echo _("if the correct information is entered, do not request it at the next access"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($room['protect_type']=='none') ? 'disabled' : ''; ?> type="checkbox" id="protect_remember" <?php echo ($room['protect_remember']) ? 'checked' : ''; ?> />
                                </div>
                            </div>
                            <div class="col-md-6 <?php echo ($room['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                                <div class="form-group">
                                    <label for="passcode_title"><?php echo _("Title"); ?> <i title="<?php echo _("title of the protect form"); ?>" class="help_t fas fa-question-circle"></i></label><?php echo print_language_input_selector($array_languages,$default_language,'passcode_title'); ?>
                                    <input type="text" class="form-control" id="passcode_title" value="<?php echo $room['passcode_title']; ?>" />
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control input_lang" data-target-id="passcode_title" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['passcode_title']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-12 <?php echo ($room['protect_type']=='mailchimp') ? '':'d-none'; ?>">
                                <div class="form-group">
                                    <label for="protect_mc_form"><?php echo _("Embedded Form Code"); ?> <i title="<?php echo _("Mailchimp -> Audience -> Signup Forms -> Embedded forms"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <textarea class="form-control" id="protect_mc_form" rows="4"><?php echo $room['protect_mc_form']; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-12 <?php echo ($room['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                                <div class="form-group">
                                    <label for="passcode_description"><?php echo _("Description"); ?> <i title="<?php echo _("description of the protect form"); ?>" class="help_t fas fa-question-circle"></i></label><?php echo print_language_input_selector($array_languages,$default_language,'passcode_description'); ?>
                                    <input type="text" class="form-control" id="passcode_description" value="<?php echo $room['passcode_description']; ?>" />
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control input_lang" data-target-id="passcode_description" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['passcode_description']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-4 <?php echo ($room['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                                <div class="form-group">
                                    <label for="passcode_code"><?php echo _("Passcode"); ?> <i title="<?php echo _("passcode to unlock the room"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="passcode_code" value="<?php echo ($room['passcode']!='') ? 'keep_passcode' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-2 <?php echo ($room['protect_type']=='mailchimp') ? 'd-none':''; ?> <?php echo (!$settings['smtp_valid']) ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label for="protect_send_email"><?php echo _("Send Notification"); ?> <i title="<?php echo _("sends a notification to the specified email when the lead form is submitted"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="protect_send_email" <?php echo ($room['protect_send_email']) ? 'checked' : ''; ?> />
                                </div>
                            </div>
                            <div class="col-md-6 <?php echo ($room['protect_type']=='mailchimp') ? 'd-none':''; ?> <?php echo (!$settings['smtp_valid']) ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label for="protect_email"><?php echo _("E-Mail"); ?></label>
                                    <input type="text" class="form-control" id="protect_email" value="<?php echo $room['protect_email']; ?>" />
                                </div>
                            </div>
                            <script>
                                change_protect_type();
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane <?php echo ($_SESSION['tab_edit_room']=='multiroom') ? 'active' : ''; ?> <?php echo ($room['type']=='image') ? '' : 'd-none'; ?>" id="multiroom_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-columns"></i> <?php echo _("Multiple Room Views"); ?> <i title="<?php echo _("allows you to load various versions of the same room and switch them in the viewer"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_rooms_multiple']) ? 'disabled' : '' ; ?>">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label><?php echo _("View Type"); ?></label><br>
                                <div id="mrv_type_0" onclick="change_mrv_type(0);" style="cursor:pointer;font-size:12px;opacity:<?php echo ($room['virtual_staging']==0) ? '1' : '0.3'; ?>" class="d-inline-block text-center mr-2 mrv_type">
                                    <img style="height:100px;" src="img/mrv_single.jpg"><br>
                                    <?php echo _("Single view"); ?>
                                </div>
                                <div id="mrv_type_1" onclick="change_mrv_type(1);" style="cursor:pointer;font-size:12px;opacity:<?php echo ($room['virtual_staging']==1) ? '1' : '0.3'; ?>" class="d-inline-block text-center mr-2 mrv_type">
                                    <img style="height:100px;" src="img/mrv_slider.jpg"><br>
                                    <?php echo _("Split view with slider"); ?>
                                </div>
                                <div id="mrv_type_2" onclick="change_mrv_type(2);" style="cursor:pointer;font-size:12px;opacity:<?php echo ($room['virtual_staging']==2) ? '1' : '0.3'; ?>" class="d-inline-block text-center mr-2 mrv_type">
                                    <img style="height:100px;" src="img/mrv_live.jpg"><br>
                                    <?php echo _("Live panorama view"); ?>
                                </div>
                                <div id="mrv_type_3" onclick="change_mrv_type(3);" style="cursor:pointer;font-size:12px;opacity:<?php echo ($room['virtual_staging']==3) ? '1' : '0.3'; ?>" class="d-inline-block text-center mr-2 mrv_type">
                                    <img style="height:100px;" src="img/mrv_time.jpg"><br>
                                    <?php echo _("Based on time slots"); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lp_duration"><?php echo _("Duration view"); ?> <i title="<?php echo _("duration in milliseconds in which the panorama view will be visible"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input <?php echo ($room['virtual_staging']==2) ? '':'disabled'; ?> type="number" min="0" class="form-control" id="lp_duration" value="<?php echo $room['lp_duration']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lp_fade"><?php echo _("Fade view"); ?> <i title="<?php echo _("duration in milliseconds of the fade animation between panoramas"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input <?php echo ($room['virtual_staging']==2) ? '':'disabled'; ?> type="number" min="0" class="form-control" id="lp_fade" value="<?php echo $room['lp_fade']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <form id="frm_alt" action="ajax/upload_room_alt_image.php" method="POST" enctype="multipart/form-data">
                                    <?php if($upload_content) : ?>
                                    <label><?php echo _("Add New Panorama"); ?></label><br>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="txtFile_alt" name="txtFile_alt" />
                                                    <label class="custom-file-label" for="txtFile_alt"><?php echo _("Choose file"); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_alt" value="<?php echo _('Upload'); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preview text-center">
                                                <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                    <div class="progress-bar" id="progressBar_alt" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                        0%
                                                    </div>
                                                </div>
                                                <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_alt"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="row">
                                        <div class="col-md-12 mt-2">
                                            <label><?php echo _("Panorama List"); ?></label>
                                        </div>
                                        <div class="col-md-12" id="list_rooms_alt">
                                            <p><?php echo _("Loading images ..."); ?></p>
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

<div id="modal_view_tooltip" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="view_tooltip"><?php echo _("View name"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'view_tooltip'); ?>
                            <input type="text" class="form-control" id="view_tooltip" />
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <input style="display:none;" type="text" class="form-control input_lang" data-target-id="view_tooltip" data-lang="<?php echo $lang; ?>" value="" />
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="auto_open"><?php echo _("Auto Open"); ?></label><br>
                            <input <?php echo ($room['virtual_staging']==3) ? 'disabled' : ''; ?> type="checkbox" id="auto_open" />
                        </div>
                    </div>
                    <div class="col-md-6 <?php echo ($room['virtual_staging']!=3) ? 'd-none' : ''; ?>">
                        <div class="form-group">
                            <label for="from_hour"><?php echo _("From Hour"); ?></label>
                            <input type="time" min="00:00" max="23:59" class="form-control" id="from_hour" />
                        </div>
                    </div>
                    <div class="col-md-6 <?php echo ($room['virtual_staging']!=3) ? 'd-none' : ''; ?>">
                        <div class="form-group">
                            <label for="to_hour"><?php echo _("To Hour"); ?></label>
                            <input type="time" min="00:00" max="23:59" class="form-control" id="to_hour" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_view_tooltip" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_view_tooltip_main" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="view_tooltip"><?php echo _("View name"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'main_view_tooltip'); ?>
                            <input type="text" class="form-control disabled" id="main_view_tooltip" value="<?php echo $room['main_view_tooltip']; ?>" />
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <input style="display:none;" type="text" class="form-control input_lang disabled" data-target-id="main_view_tooltip" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['main_view_tooltip']; ?>" />
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php echo _("Ok"); ?></button>
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

<div id="modal_new_preset" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Add New Preset"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name_preset"><?php echo _("Preset Name"); ?></label>
                            <input id="name_preset" type="text" class="form-control" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_add_new_preset" onclick="" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_save_preset" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Save Preset"); ?></h5>
            </div>
            <div class="modal-body">
                <?php echo _("Are you sure you want to save this preset?"); ?>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_preset" onclick="save_exist_preset('room_positions');" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Yes, Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_apply_preset_tour" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Apply Preset"); ?></h5>
            </div>
            <div class="modal-body">
                <?php echo _("Are you sure you want to apply this preset to all the rooms of this Virtual Tour?"); ?>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_apply_preset_tour" onclick="apply_preset_tour('room_positions')" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
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
                <p><?php echo _("Are you sure you want to delete the room?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_room" onclick="delete_room(<?php echo $id_room; ?>,true)" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_initial_position_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Initial Position"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to apply initial position to all existing rooms by overwriting them?"); ?></p>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="apply_initial_pos_yaw"><i class="fas fa-arrows-alt-h"></i> <?php echo _("Yaw"); ?> (<span id="ip_yaw_l"><?php echo $room['yaw']; ?></span>)</label><br>
                            <input type="checkbox" id="apply_initial_pos_yaw" checked />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="apply_initial_pos_pitch"><i class="fas fa-arrows-alt-v"></i> <?php echo _("Pitch"); ?> (<span id="ip_pitch_l"><?php echo $room['pitch']; ?></span>)</label><br>
                            <input type="checkbox" id="apply_initial_pos_pitch" checked />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_initial_pos();" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_north_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("North"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to apply north to all existing rooms by overwriting them?"); ?></p>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><i class="far fa-compass"></i> <?php echo _("North"); ?> (<span id="north_l"><?php echo $room['northOffset']; ?></span>)</label><br>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_north();" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_effects_apply" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Effects"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to apply effects to all existing rooms by overwriting them?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="apply_default_effects();" type="button" class="btn btn-success"><i class="fas fa-check"></i> <?php echo _("Yes, Apply"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_room = <?php echo $id_room; ?>;
        window.id_virtualtour = <?php echo $room['id_virtualtour']; ?>;
        var hfov = '<?php echo ($room['hfov']==0) ? $virtual_tour['hfov'] : $room['hfov']; ?>';
        var hfov_default = '<?php echo $virtual_tour['hfov']; ?>';
        var min_hfov = '<?php echo $virtual_tour['min_hfov']; ?>';
        var max_hfov = '<?php echo $virtual_tour['max_hfov']; ?>';
        var yaw = '<?php echo $room['yaw']; ?>';
        var pitch = '<?php echo $room['pitch']; ?>';
        var h_pitch = '<?php echo $room['h_pitch']; ?>';
        var h_roll = '<?php echo $room['h_roll']; ?>';
        var northOffset = '<?php echo $room['northOffset']; ?>';
        var allow_pitch = <?php echo $room['allow_pitch']; ?>;
        var allow_hfov = <?php echo $room['allow_hfov']; ?>;
        var min_pitch = '<?php echo $room['min_pitch']; ?>';
        var max_pitch = '<?php echo $room['max_pitch']; ?>';
        var min_yaw = '<?php echo $room['min_yaw']; ?>';
        var max_yaw = '<?php echo $room['max_yaw']; ?>';
        var haov = '<?php echo $room['haov']; ?>';
        var vaov = '<?php echo $room['vaov']; ?>';
        var nadir_logo = '<?php echo $nadir_logo; ?>';
        var nadir_size = '<?php echo $nadir_size; ?>';
        window.room_type = '<?php echo $room['type']; ?>';
        window.virtual_staging = <?php echo $room['virtual_staging']; ?>;
        window.viewer = null;
        window.viewer_video = null;
        var ratio_hfov = 1;
        var viewer_initialized = false;
        var video = document.createElement("video");
        var canvas = document.createElement("canvas");
        var video_preview;
        var point_size = '<?php echo $room['point_size']; ?>';
        var map_north = '<?php echo $room['north_degree']; ?>';
        var map_top = '<?php echo $room['map_top']; ?>';
        var map_left = '<?php echo $room['map_left']; ?>';
        var map_lat = '<?php echo $room['lat']; ?>';
        var map_lon = '<?php echo $room['lon']; ?>';
        window.map_tour_l = null;
        window.change_image = 0;
        window.change_video = 0;
        window.change_json = 0;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        if(window.s3_enabled==1) {
            window.panorama_image = window.s3_url+"viewer/panoramas/<?php echo $room['panorama_image']; ?>";
            window.panorama_video = window.s3_url+"viewer/videos/<?php echo $room['panorama_video']; ?>";
            window.panorama_json = window.s3_url+"viewer/panoramas/<?php echo $room['panorama_json']; ?>";
        } else {
            window.panorama_image = "../viewer/panoramas/<?php echo $room['panorama_image']; ?>";
            window.panorama_video = "../viewer/videos/<?php echo $room['panorama_video']; ?>";
            window.panorama_json = "../viewer/panoramas/<?php echo $room['panorama_json']; ?>";
        }
        window.panorama_url = "<?php echo $room['panorama_url']; ?>";
        window.song = `<?php echo $room['song']; ?>`;
        window.logo = '<?php echo $room['logo']; ?>';
        var multires = <?php echo $room['multires']; ?>;
        var multires_config = '<?php echo $room['multires_config']; ?>';
        window.rooms_alt_images = [];
        window.max_file_size_upload = <?php echo $max_file_size_upload; ?>;
        window.room_need_save = false;
        window.cropper_thumb=null;
        window.thumb_image = '<?php echo $room['thumb_image']; ?>';
        window.background_color = '<?php echo $room['background_color']; ?>';
        window.background_color_spectrum = null;
        window.pois = [];
        window.video_embeds = [];
        window.poi_embed_originals_pos = [];
        window.sync_poi_embed_enabled = false;
        window.sync_marker_embed_enabled = false;
        window.preserveDrawingBuffer = true;
        var street_basemap_url = '<?php echo $settings['leaflet_street_basemap']; ?>';
        var street_subdomain = '<?php echo $settings['leaflet_street_subdomain']; ?>';
        var street_maxzoom = '<?php echo $settings['leaflet_street_maxzoom']; ?>';
        var satellite_basemap_url = '<?php echo $settings['leaflet_satellite_basemap']; ?>';
        var satellite_subdomain = '<?php echo $settings['leaflet_satellite_subdomain']; ?>';
        var satellite_maxzoom = '<?php echo $settings['leaflet_satellite_maxzoom']; ?>';
        var video_p = null, app_p = null, loader_p = null;
        var tab_edit_room_preview = '<?php echo $_SESSION['tab_edit_room_preview']; ?>';
        $(document).ready(function () {
            var md = new MobileDetect(window.navigator.userAgent);
            if(md.mobile()==null) {
                window.is_mobile = false;
            } else {
                window.is_mobile = true;
            }
            if($('#disk_space_original').length!=0) {
                get_disk_size_room();
            }
            show_info_edit_room(tab_edit_room_preview);
            yaw = parseFloat(yaw);
            pitch = parseFloat(pitch);
            try {
                multires_config = JSON.parse(multires_config);
            } catch (e) {
                multires = false;
            }
            bsCustomFileInput.init();
            $('#exist_song').selectator({
                useSearch: false
            });
            $('.preset_buttons button').tooltipster({
                delay: 10,
                hideOnClick: true
            });
            get_rooms_alt_images(id_room);
            new ClipboardJS('.btn_link');
            $('.help_t').tooltip();
            $('.tooltip_arrows').tooltipster({
                delay: 10,
                hideOnClick: true
            });
            if(window.logo=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            if(window.song=='') {
                $('#div_delete_song').hide();
                $('#div_player_song').hide();
                $('#div_upload_song').show();
                $('#div_exist_song').show();
            } else {
                $('#div_delete_song').show();
                $('#div_player_song').show();
                $('#div_upload_song').hide();
                $('#div_exist_song').hide();
            }
            if(window.room_type=='video') {
                var id_panorama = 'video_viewer';
            } else {
                var id_panorama = 'panorama';
            }
            try {
                var c_w = parseFloat($('#'+id_panorama).css('width').replace('px',''));
                var new_height = c_w / 1.7771428571428571;
                $('#'+id_panorama).css('height',new_height+'px');
                $('#panorama_image_edit').parent().css('height',new_height+'px');
            } catch (e) {}
            window.background_color_spectrum = $('#background_color').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: false,
                showButtons: true,
                allowEmpty: false,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(viewer_initialized) {
                        var color = color.toString();
                        color = color.replace('rgb(','');
                        color = color.replace(')','');
                        var tmp = color.split(",");
                        tmp[0] = (tmp[0]/255).toFixed(4);
                        tmp[1] = (tmp[1]/255).toFixed(4);
                        tmp[2] = (tmp[2]/255).toFixed(4);
                        window.background_color = tmp.join();
                        load_viewer(room_type,window.panorama_image,window.panorama_video,window.panorama_url,window.panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw);
                    }
                }
            });
            get_pois_edit_rooms(window.id_room);
            var panorama_image_open = sessionStorage.getItem('panorama_image_open');
            if(panorama_image_open) {
                if(panorama_image_open==1) {
                    $('a[href="#collapsePI"]').trigger('click');
                }
            }
        });

        $('input[type=radio][name=north_radio]').change(function() {
            switch(($(this).attr('id'))) {
                case 'floorplan':
                    $('#floorplan_div').show();
                    $('#map_div').hide();
                    adjust_point_position();
                    break;
                case 'map':
                    $('#floorplan_div').hide();
                    $('#map_div').show();
                    if(map_lat!='') {
                        var point_size = 40;
                        if(window.map_tour_l==null) {
                            var street_subdomain_t = street_subdomain.split(",");
                            var street_maxzoom_t = parseInt(street_maxzoom);
                            if(street_subdomain!='') {
                                var street_basemap = L.tileLayer(street_basemap_url,{
                                    maxZoom: street_maxzoom_t,
                                    subdomains: street_subdomain_t
                                });
                            } else {
                                var street_basemap = L.tileLayer(street_basemap_url,{
                                    maxZoom: street_maxzoom_t
                                });
                            }
                            var satellite_subdomain_t = satellite_subdomain.split(",");
                            var satellite_maxzoom_t = parseInt(satellite_maxzoom);
                            if(satellite_subdomain!='') {
                                var satellite_basemap = L.tileLayer(satellite_basemap_url,{
                                    maxZoom: satellite_maxzoom_t,
                                    subdomains: satellite_subdomain_t
                                });
                            } else {
                                var satellite_basemap = L.tileLayer(satellite_basemap_url,{
                                    maxZoom: satellite_maxzoom_t
                                });
                            }
                            window.map_tour_l = L.map('map_container', {
                                layers: [street_basemap]
                            }).setView([0,0], 2);
                            var baseMaps = {
                                "Street": street_basemap,
                                "Satellite": satellite_basemap
                            };
                            L.control.layers(baseMaps, {}, {position: 'topright'}).addTo(map_tour_l);
                            var icon = new L.DivIcon({
                                html: "<div id='map_tour_arrow_"+id_room+"' class=\"view_direction_m__arrow\"></div><div id='map_tour_icon_"+id_room+"' class='map_tour_icon map_tour_icon_top map_tour_icon_active' style='background-image: url(\"<?php echo $thumb_link; ?>\");'></div>",
                                iconSize: [point_size, point_size],
                                iconAnchor: [(point_size/2), (point_size/2)]
                            });
                            var marker = L.marker([map_lat, map_lon], {
                                id: id_room,
                                icon: icon,
                                draggable: false,
                                autoPan: true
                            });
                            marker.addTo(window.map_tour_l);
                        }
                        window.map_tour_l.setView([map_lat, map_lon], 14);
                        try {
                            viewer.resize();
                        } catch (e) {}
                        try {
                            viewer_video.resize();
                        } catch (e) {}
                        $('.map_tour_icon').css('width',point_size+'px');
                        $('.map_tour_icon').css('height',point_size+'px');
                        var border = parseInt($('.map_tour_icon').css('borderLeftWidth'),10);
                        $('.map_tour_icon').parent().addClass('map_tour_icon_top');
                        $('.view_direction_m__arrow').css('top',(point_size/2)+(border/2)+'px');
                        $('.view_direction_m__arrow').css('left',(point_size/2)+(border/2)+'px');
                        $('.view_direction_m__arrow').css('border-radius','0 0 '+(point_size*2)+'px');
                        $('.view_direction_m__arrow').css('width',(point_size*2)+'px');
                        $('.view_direction_m__arrow').css('height',(point_size*2)+'px');
                    }
                    break;
            }
        });

        $("#collapsePI").on('show.bs.collapse', function(){
            var src_image = $('#panorama_image').attr('data-src');
            $('#panorama_image').attr('src',src_image);
            sessionStorage.setItem('panorama_image_open',1);
        });
        $("#collapsePI").on('hide.bs.collapse', function(){
            sessionStorage.setItem('panorama_image_open',0);
        });

        $('#transition_override').click(function(){
            window.room_need_save = true;
            if($(this).is(':checked')){
                $('#transition_time').prop('disabled',false);
                $('#transition_fadeout').prop('disabled',false);
                $('#transition_zoom').prop('disabled',false);
                $('#transition_effect').prop('disabled',false);
            } else {
                $('#transition_time').prop('disabled',true);
                $('#transition_fadeout').prop('disabled',true);
                $('#transition_zoom').prop('disabled',true);
                $('#transition_effect').prop('disabled',true);
            }
        });

        $('#allow_pitch').click(function(){
            window.room_need_save = true;
            if($(this).is(':checked')){
                allow_pitch=1;
                $('#min_pitch').prop('disabled',false);
                $('#max_pitch').prop('disabled',false);
                min_pitch = (parseInt($('#min_pitch').val())*-1)-34;
                max_pitch = parseInt($('#max_pitch').val())+34;
                if(room_type=='video' && !window.is_mobile) {
                    viewer_video.pnlmViewer.setPitchBounds([min_pitch,max_pitch]);
                } else {
                    viewer.setPitchBounds([min_pitch,max_pitch]);
                }
            } else {
                allow_pitch=0;
                $('#min_pitch').prop('disabled',true);
                $('#max_pitch').prop('disabled',true);
                if(room_type=='video' && !window.is_mobile) {
                    viewer_video.pnlmViewer.setPitchBounds([0,0]);
                    viewer_video.pnlmViewer.setPitch(0);
                } else {
                    viewer.setPitchBounds([0,0]);
                    viewer.setPitch(0);
                }
            }
        });

        $('#allow_hfov').click(function(){
            window.room_need_save = true;
            if($('#hfov').val()=='') {
                hfov=0;
            } else {
                hfov = parseInt($('#hfov').val());
            }
            if(hfov==0) {
                hfov = parseInt(hfov_default);
            }
            if($(this).is(':checked')){
                allow_hfov=1;
                if(room_type=='video' && !window.is_mobile) {
                    viewer_video.pnlmViewer.setHfovBounds([min_hfov,max_hfov]);
                } else {
                    viewer.setHfovBounds([min_hfov,max_hfov]);
                }
            } else {
                allow_hfov=0;
                if(room_type=='video' && !window.is_mobile) {
                    viewer_video.pnlmViewer.setHfov(hfov,false);
                    viewer_video.pnlmViewer.setHfovBounds([hfov,hfov]);
                } else {
                    viewer.setHfov(hfov,false);
                    viewer.setHfovBounds([hfov,hfov]);
                }
            }
        });

        $('#min_pitch, #max_pitch').on('change',function(){
            window.room_need_save = true;
            min_pitch = (parseInt($('#min_pitch').val())*-1)-34;
            max_pitch = parseInt($('#max_pitch').val())+34;
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setPitchBounds([min_pitch,max_pitch]);
            } else {
                viewer.setPitchBounds([min_pitch,max_pitch]);
            }
        });

        $('#min_yaw, #max_yaw').on('change',function(){
            window.room_need_save = true;
            min_yaw = (parseInt($('#min_yaw').val())*-1);
            max_yaw = parseInt($('#max_yaw').val());
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setYawBounds([min_yaw,max_yaw]);
            } else {
                viewer.setYawBounds([min_yaw,max_yaw]);
            }
        });

        $('#h_pitch, #h_roll').on('input',function(){
            window.room_need_save = true;
            var h_pitch = parseInt($('#h_pitch').val());
            var h_roll = parseInt($('#h_roll').val());
            $('#h_pitch_val').html(h_pitch);
            $('#h_roll_val').html(h_roll);
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setHorizonPitch(h_pitch);
                viewer_video.pnlmViewer.setHorizonRoll(h_roll);
            } else {
                viewer.setHorizonPitch(h_pitch);
                viewer.setHorizonRoll(h_roll);
            }
            var poi_embed_count = $('.poi_embed').length;
            if(poi_embed_count>0) {
                //init_poi_embed(true);
            } else {
                window.sync_poi_embed_enabled = false;
            }
            var poi_embed_count = $('.poi_embed').length;
            if(poi_embed_count>0) {
                setTimeout(function () {
                    adjust_poi_embed_helpers_all();
                },50);
            }
        });

        $('#hfov').on('input',function(){
            window.room_need_save = true;
            hfov = parseInt($('#hfov').val());
            if(hfov==0) {
                $('#hfov').val(0);
                hfov=parseInt(hfov_default);
            } else if(hfov<parseInt(min_hfov)) {
                $('#hfov').val(min_hfov);
                hfov=parseInt(min_hfov);
            } else if(hfov>parseInt(max_hfov)) {
                $('#hfov').val(max_hfov);
                hfov=parseInt(max_hfov);
            }
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setHfov(hfov,false);
                if(allow_hfov==0) {
                    viewer_video.pnlmViewer.setHfovBounds([hfov,hfov]);
                }
            } else {
                viewer.setHfov(hfov,false);
                if(allow_hfov==0) {
                    viewer.setHfovBounds([hfov,hfov]);
                }
            }
        });

        $('#haov, #vaov').on('change',function(){
            var h_pitch = parseInt($('#h_pitch').val());
            var h_roll = parseInt($('#h_roll').val());
            var haov_t = $('#haov').val();
            var vaov_t = $('#vaov').val();
            if(haov_t!='') haov=parseInt(haov_t);
            if(vaov_t!='') vaov=parseInt(vaov_t);
            load_viewer(room_type,window.panorama_image,window.panorama_video,window.panorama_url,window.panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw);
        });

        window.fix_north = function() {
            $('.pointer_view').css('opacity',0);
            setTimeout(function () {
                adjust_point_position();
                $('.pointer_view').css('opacity',1);
                if($('#floorplan_div .map_image').length===0) {
                    $('#floorplan').prop('checked',true);
                    $('#floorplan').parent().removeClass('active');
                    $('#map').parent().addClass('active');
                    $('input[type=radio][name=north_radio]').trigger('change');
                }
            },50);
        }

        window.preset_positions = function(id) {
            $('#positions_tab_btn').trigger('click');
            switch (id) {
                case 0:
                    allow_pitch = 0;
                    allow_hfov = 0;
                    vaov = 60;
                    haov = 360;
                    min_yaw = -180;
                    max_yaw = 180;
                    hfov = 90;
                    h_pitch = 0;
                    h_roll = 0;
                    min_pitch = -90;
                    max_pitch = 90;
                    break;
                case 1:
                    allow_pitch = 0;
                    allow_hfov = 0;
                    vaov = 60;
                    haov = 220;
                    min_yaw = -110;
                    max_yaw = 110;
                    hfov = 90;
                    h_pitch = 0;
                    h_roll = 0;
                    min_pitch = -90;
                    max_pitch = 90;
                    break;
                case 2:
                    allow_pitch = 0;
                    allow_hfov = 0;
                    vaov = 36;
                    haov = 60;
                    min_yaw = -25;
                    max_yaw = 25;
                    hfov = 60;
                    h_pitch = 0;
                    h_roll = 0;
                    min_pitch = -90;
                    max_pitch = 90;
                    break;
                case 3:
                    allow_pitch = 0;
                    allow_hfov = 0;
                    vaov = 36;
                    haov = 50;
                    min_yaw = -25;
                    max_yaw = 25;
                    hfov = 60;
                    h_pitch = 0;
                    h_roll = 0;
                    min_pitch = -90;
                    max_pitch = 90;
                    break;
            }
            if(allow_pitch==1) {
                $('#allow_pitch').prop('checked',true);
                $('#min_pitch').prop('disabled',false);
                $('#max_pitch').prop('disabled',false);
            } else  {
                $('#allow_pitch').prop('checked',false);
                $('#min_pitch').prop('disabled',true);
                $('#max_pitch').prop('disabled',true);
            }
            if(allow_hfov==1) $('#allow_hfov').prop('checked', true); else $('#allow_hfov').prop('checked', false);
            $('#vaov').val(vaov);
            $('#haov').val(haov);
            $('#min_yaw').val(min_yaw*-1);
            $('#max_yaw').val(max_yaw);
            $('#hfov').val(hfov);
            $('#min_pitch').val(min_pitch*-1);
            $('#max_pitch').val(max_pitch);
            $('#h_roll').val(h_roll);
            $('#h_pitch').val(h_pitch);
            load_viewer(room_type,window.panorama_image,window.panorama_video,window.panorama_url,window.panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw);
        }

        window.open_modal_apply_preset_tour = function(type) {
            $('#modal_apply_preset_tour').modal('show');
        }

        window.apply_preset_tour = function(type) {
            $('#modal_apply_preset_tour button').addClass('disabled');
            apply_preset_room('room_positions');
            save_room(null,1);
        }

        window.apply_preset_room = function(type) {
            var id_preset = $('#presets option:selected').attr('id');
            var value = $("#presets option[id='"+id_preset+"']").attr('data-value');
            var array_value = JSON.parse(value);
            var allow_pitch = array_value['allow_pitch'];
            var allow_hfov = array_value['allow_hfov'];
            var min_pitch = array_value['min_pitch'];
            var max_pitch = array_value['max_pitch'];
            var min_yaw = array_value['min_yaw'];
            var max_yaw = array_value['max_yaw'];
            var haov = array_value['haov'];
            var vaov = array_value['vaov'];
            var hfov = array_value['hfov'];
            var h_pitch = array_value['h_pitch'];
            var h_roll = array_value['h_roll'];
            var background_color = array_value['background_color'];
            window.background_color = background_color;
            background_color = background_color.replace('rgb(','');
            background_color = background_color.replace(')','');
            var tmp = background_color.split(",");
            tmp[0] = (tmp[0]*255).toFixed(0);
            tmp[1] = (tmp[1]*255).toFixed(0);
            tmp[2] = (tmp[2]*255).toFixed(0);
            var background_color_t = tmp.join();
            if(allow_pitch==1) {
                $('#allow_pitch').prop('checked',true);
                $('#min_pitch').prop('disabled',false);
                $('#max_pitch').prop('disabled',false);
            } else  {
                $('#allow_pitch').prop('checked',false);
                $('#min_pitch').prop('disabled',true);
                $('#max_pitch').prop('disabled',true);
            }
            if(allow_hfov==1) $('#allow_hfov').prop('checked', true); else $('#allow_hfov').prop('checked', false);
            $('#vaov').val(vaov);
            $('#haov').val(haov);
            $('#min_yaw').val(min_yaw);
            $('#max_yaw').val(max_yaw);
            $('#hfov').val(hfov);
            $('#min_pitch').val(min_pitch);
            $('#max_pitch').val(max_pitch);
            $('#h_roll').val(h_roll);
            $('#h_pitch').val(h_pitch);
            $('#h_pitch_val').html(h_pitch);
            $('#h_roll_val').html(h_roll);
            $('#background_color').val("rgb("+background_color_t+")");
            window.background_color_spectrum.spectrum("set", $('#background_color').val());
            min_yaw = min_yaw*-1;
            load_viewer(room_type,window.panorama_image,window.panorama_video,window.panorama_url,window.panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw);
        }

        window.click_preview = function() {
            setTimeout(function() {
                if(!viewer_initialized || room_type=='hls') {
                    load_viewer(room_type,window.panorama_image,window.panorama_video,window.panorama_url,window.panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw);
                }
                $(window).trigger('resize');
            },100);
        }

        window.toggle_nadir_logo = function() {
            if($('#show_nadir').is(':checked')) {
                $('.nadir-hotspot').show();
            } else {
                $('.nadir-hotspot').hide();
            }
        }

        function hotspot_nadir(hotSpotDiv, args) {
            hotSpotDiv.classList.add('noselect');
            hotSpotDiv.style = "background-image:url(../viewer/content/"+args+");background-size:cover;";
            if(window.innerWidth<540) {
                var nadir_size_mobile = parseInt(nadir_size.replace('px'))*0.4;
                hotSpotDiv.style.width = nadir_size_mobile+'px';
                hotSpotDiv.style.height = nadir_size_mobile+'px';
            } else {
                hotSpotDiv.style.width = nadir_size;
                hotSpotDiv.style.height = nadir_size;
            }
        }

        function load_viewer(room_type,panorama_image,panorama_video,panorama_url,panorama_json,yaw,pitch,h_pitch,h_roll,haov,vaov,min_yaw,max_yaw) {
            if(panorama_image.includes('tmp_panoramas')) multires = false;
            var background_color_t = window.background_color.split(',');
            if(allow_pitch==1) {
                min_pitch = (parseInt($('#min_pitch').val())*-1)-34;
                max_pitch = parseInt($('#max_pitch').val())+34;
            } else {
                min_pitch = 0;
                max_pitch = 0;
                pitch = 0;
            }
            if(allow_hfov==0) {
                min_hfov = hfov;
                max_hfov = hfov;
            }
            if(map_north=='') map_north=0;
            var hotSpots = [];
            if(nadir_logo!='') {
                hotSpots.push({
                    "type": "nadir",
                    "view_type": 0,
                    "object": "nadir",
                    "transform3d": false,
                    "pitch": -90,
                    "yaw": 0,
                    "rotateX": 0,
                    "rotateZ": 0,
                    "scale": true,
                    "cssClass": "nadir-hotspot",
                    "createTooltipFunc": hotspot_nadir,
                    "createTooltipArgs": nadir_logo
                });
            }
            jQuery.each(window.pois, function(index, poi) {
                if(poi.what=='poi') {
                    if(poi.embed_type!='' && poi.transform3d==1) {
                        hotSpots.push({
                            "id": "p"+poi.id,
                            "type": poi.embed_type,
                            "object": "poi_embed",
                            "scale": false,
                            "transform3d": parseInt(poi.transform3d),
                            "tooltip_type": "",
                            "pitch": parseFloat(poi.pitch),
                            "yaw": parseFloat(poi.yaw),
                            "rotateX": 0,
                            "rotateZ": 0,
                            "size_scale": 1,
                            "cssClass": "hotspot-embed",
                            "createTooltipFunc": hotspot_embed,
                            "createTooltipArgs": poi,
                        });
                        if(poi.transform3d==1) {
                            var poi_embed_helpers = poi.embed_coords.split("|");
                            poi_embed_helpers[0] = poi_embed_helpers[0].split(",");
                            poi_embed_helpers[1] = poi_embed_helpers[1].split(",");
                            poi_embed_helpers[2] = poi_embed_helpers[2].split(",");
                            poi_embed_helpers[3] = poi_embed_helpers[3].split(",");
                            jQuery.each(poi_embed_helpers, function(index_h, poi_embed_helper) {
                                hotSpots.push({
                                    "id": "p"+poi.id+"_"+(index_h+1),
                                    "type": 'pointer',
                                    "object": "poi_embed_helper",
                                    "transform3d": false,
                                    "pitch": parseFloat(poi_embed_helper[0]),
                                    "yaw": parseFloat(poi_embed_helper[1]),
                                    "size_scale": 1,
                                    "rotateX": 0,
                                    "rotateZ": 0,
                                    "draggable": true,
                                    "cssClass": "hotspot-helper",
                                    "createTooltipFunc": hotspot_embed_helper,
                                    "createTooltipArgs": [poi.id,(index_h+1)],
                                });
                            });
                        }
                    } else if(poi.embed_type!='' && poi.transform3d==0) {
                        hotSpots.push({
                            "id": "p"+poi.id,
                            "type": poi.embed_type,
                            "object": "poi_embed",
                            "scale": false,
                            "transform3d": parseInt(poi.transform3d),
                            "tooltip_type": "",
                            "pitch": parseFloat(poi.pitch),
                            "yaw": parseFloat(poi.yaw),
                            "rotateX": parseInt(poi.rotateX),
                            "rotateZ": parseInt(poi.rotateZ),
                            "size_scale": parseFloat(poi.size_scale),
                            "cssClass": "hotspot-embed",
                            "createTooltipFunc": hotspot_embed,
                            "createTooltipArgs": poi,
                        });
                    }
                }
            });
            try {
                viewer.destroy();
                $('#panorama').empty();
            } catch (e) {}
            try {
                window.viewer_video.pnlmViewer.destroy();
                window.viewer_video.dispose();
                window.viewer_video = null;
                $('#panorama_video').empty();
            } catch (e) {}
            switch(room_type) {
                case 'image':
                    if(multires) {
                        viewer = pannellum.viewer('panorama', {
                            "id_room": window.id_room,
                            "type": "multires",
                            "multiRes": multires_config,
                            "backgroundColor": background_color_t,
                            "autoLoad": true,
                            "showFullscreenCtrl": false,
                            "showControls": false,
                            "multiResMinHfov": true,
                            "horizonPitch": parseInt(h_pitch),
                            "horizonRoll": parseInt(h_roll),
                            "hfov": parseInt(hfov),
                            "minHfov": parseInt(min_hfov),
                            "maxHfov": parseInt(max_hfov),
                            "yaw": parseInt(yaw),
                            "pitch": parseInt(pitch),
                            "minPitch": min_pitch,
                            "maxPitch" : max_pitch,
                            "minYaw": parseInt(min_yaw),
                            "maxYaw" : parseInt(max_yaw),
                            "haov": parseInt(haov),
                            "vaov": parseInt(vaov),
                            "compass": true,
                            "northOffset": parseInt(northOffset),
                            "map_north": parseInt(map_north),
                            "hotSpots": hotSpots,
                            "friction": 1,
                            "strings": {
                                "loadingLabel": "<?php echo _("Loading"); ?>...",
                            },
                        });
                        setTimeout(function () {
                            viewer_initialized = true;
                            $('#north_tab_btn').removeClass('disabled');
                            var yaw = parseInt(viewer.getYaw());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#northOffset_debug').html(northOffset);
                            adjust_ratio_hfov('panorama',viewer,hfov,min_hfov,max_hfov);
                            adjust_point_position();
                            apply_room_filters();
                            toggle_nadir_logo();
                            var poi_embed_count = $('.poi_embed').length;
                            if(poi_embed_count>0) {
                                //init_poi_embed();
                            } else {
                                window.sync_poi_embed_enabled = false;
                            }
                            var dataURL = window.viewer.getRenderer().render(window.viewer.getPitch() / 180 * Math.PI,
                                window.viewer.getYaw() / 180 * Math.PI,
                                window.viewer.getHfov() / 180 * Math.PI,
                                {'returnImage': 'image/jpeg'});
                            initialize_cropper_thumbnail(dataURL);
                            $('.pnlm-container').append('<div class="grid_position"></div>');
                            if($('#position_tab').hasClass('active')) show_grid_position();
                            $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                            $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                            change_effect();
                            switch(tab_edit_room_preview) {
                                case 'view':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    show_btn_screenshot();
                                    break;
                                case 'positions':
                                    show_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'north':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    fix_north();
                                    break;
                                case 'effects':
                                    hide_grid_position();
                                    show_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'bulk':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                            }
                        },100);
                    } else {
                        viewer = pannellum.viewer('panorama', {
                            "id_room": window.id_room,
                            "type": "equirectangular",
                            "panorama": panorama_image+((window.s3_enabled) ? '?s3='+Date.now() : ''),
                            "autoLoad": true,
                            "backgroundColor": background_color_t,
                            "showFullscreenCtrl": false,
                            "showControls": false,
                            "multiResMinHfov": true,
                            "horizonPitch": parseInt(h_pitch),
                            "horizonRoll": parseInt(h_roll),
                            "hfov": parseInt(hfov),
                            "minHfov": parseInt(min_hfov),
                            "maxHfov": parseInt(max_hfov),
                            "yaw": parseInt(yaw),
                            "pitch": parseInt(pitch),
                            "minPitch": min_pitch,
                            "maxPitch" : max_pitch,
                            "minYaw": parseInt(min_yaw),
                            "maxYaw" : parseInt(max_yaw),
                            "haov": parseInt(haov),
                            "vaov": parseInt(vaov),
                            "compass": true,
                            "northOffset": parseInt(northOffset),
                            "map_north": parseInt(map_north),
                            "hotSpots": hotSpots,
                            "friction": 1,
                            "strings": {
                                "loadingLabel": "<?php echo _("Loading"); ?>...",
                            },
                        });
                        viewer.on('load', function () {
                            viewer_initialized = true;
                            $('#north_tab_btn').removeClass('disabled');
                            var yaw = parseInt(viewer.getYaw());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#northOffset_debug').html(northOffset);
                            adjust_ratio_hfov('panorama',viewer,hfov,min_hfov,max_hfov);
                            adjust_point_position();
                            apply_room_filters();
                            toggle_nadir_logo();
                            var poi_embed_count = $('.poi_embed').length;
                            if(poi_embed_count>0) {
                                //init_poi_embed();
                            } else {
                                window.sync_poi_embed_enabled = false;
                            }
                            var dataURL = window.viewer.getRenderer().render(window.viewer.getPitch() / 180 * Math.PI,
                                window.viewer.getYaw() / 180 * Math.PI,
                                window.viewer.getHfov() / 180 * Math.PI,
                                {'returnImage': 'image/jpeg'});
                            initialize_cropper_thumbnail(dataURL);
                            $('.pnlm-container').append('<div class="grid_position"></div>');
                            if($('#position_tab').hasClass('active')) show_grid_position();
                            $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                            $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                            change_effect();
                            switch(tab_edit_room_preview) {
                                case 'view':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    show_btn_screenshot();
                                    break;
                                case 'positions':
                                    show_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'north':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    fix_north();
                                    break;
                                case 'effects':
                                    hide_grid_position();
                                    show_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'bulk':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                            }
                        });
                    }
                    viewer.on('animatefinished',function () {
                        var yaw = parseInt(viewer.getYaw());
                        var pitch = parseInt(viewer.getPitch());
                        if(yaw<0) {
                            var northOffset = Math.abs(yaw);
                        } else {
                            var northOffset =  360 - yaw;
                        }
                        if(northOffset==360) northOffset=0;
                        $('#yaw_pitch_debug').html(yaw+','+pitch);
                        $('#northOffset_debug').html(northOffset);
                    });
                    break;
                case 'video':
                    if(window.is_mobile) {
                        try {
                            loader_p.reset();
                        } catch (e) {}
                        try {
                            video_p.remove();
                        } catch (e) {}
                        $("#canvas_p").empty();
                        var setup_video_p = (loader, resources) => {
                            PIXI.utils.sayHello("WebGL");
                            var index_bg = Object.keys(resources)[0];
                            app_p = new PIXI.Application({
                                antialias: false,
                                transparent: false,
                                resolution: 1,
                                width: resources[index_bg].baseTexture.width,
                                height: resources[index_bg].baseTexture.height
                            });
                            $("#canvas_p").append(app_p.view);
                            let bg = PIXI.Sprite.from(resources[index_bg]);
                            /*bg.anchor.y = 1;
                            bg.scale.y = -1;*/
                            app_p.stage.addChild(bg);
                            video_p = document.createElement('video');
                            video_p.id = 'video_viewer';
                            video_p.crossOrigin = 'anonymous';
                            video_p.preload = 'auto';
                            video_p.autoplay = true;
                            video_p.muted = true;
                            video_p.loop = true;
                            video_p.oncanplay = function() {
                                video_p.play();
                            };
                            video_p.setAttribute('playsinline','');
                            video_p.setAttribute('webkit-playsinline','');
                            video_p.src = panorama_video;
                            const sprite = PIXI.Sprite.from(video_p);
                            /*sprite.anchor.y = 1;
                            sprite.scale.y = -1;*/
                            app_p.stage.addChild(sprite);
                            let canvas = $('#canvas_p canvas')[0];
                            viewer = pannellum.viewer('panorama', {
                                "id_room": window.id_room,
                                "type": "equirectangular",
                                "panorama": canvas,
                                "autoLoad": true,
                                "dynamic": true,
                                "dynamicUpdate": true,
                                "backgroundColor": background_color_t,
                                "showFullscreenCtrl": false,
                                "showControls": false,
                                "multiResMinHfov": true,
                                "horizonPitch": parseInt(h_pitch),
                                "horizonRoll": parseInt(h_roll),
                                "hfov": parseInt(hfov),
                                "minHfov": parseInt(min_hfov),
                                "maxHfov": parseInt(max_hfov),
                                "yaw": parseInt(yaw),
                                "pitch": parseInt(pitch),
                                "minPitch": min_pitch,
                                "maxPitch" : max_pitch,
                                "minYaw": parseInt(min_yaw),
                                "maxYaw" : parseInt(max_yaw),
                                "haov": parseInt(haov),
                                "vaov": parseInt(vaov),
                                "compass": true,
                                "northOffset": parseInt(northOffset),
                                "map_north": parseInt(map_north),
                                "hotSpots": hotSpots,
                                "friction": 1,
                                "strings": {
                                    "loadingLabel": "<?php echo _("Loading"); ?>...",
                                },
                            });
                            setTimeout(function () {
                                viewer_initialized = true;
                                $('#north_tab_btn').removeClass('disabled');
                                var yaw = parseInt(viewer.getYaw());
                                if(yaw<0) {
                                    var northOffset = Math.abs(yaw);
                                } else {
                                    var northOffset =  360 - yaw;
                                }
                                if(northOffset==360) northOffset=0;
                                $('#northOffset_debug').html(northOffset);
                                adjust_ratio_hfov('panorama',viewer,hfov,min_hfov,max_hfov);
                                adjust_point_position();
                                apply_room_filters();
                                toggle_nadir_logo();
                                var poi_embed_count = $('.poi_embed').length;
                                if(poi_embed_count>0) {
                                    //init_poi_embed();
                                } else {
                                    window.sync_poi_embed_enabled = false;
                                }
                                var dataURL = window.viewer.getRenderer().render(window.viewer.getPitch() / 180 * Math.PI,
                                    window.viewer.getYaw() / 180 * Math.PI,
                                    window.viewer.getHfov() / 180 * Math.PI,
                                    {'returnImage': 'image/jpeg'});
                                initialize_cropper_thumbnail(dataURL);
                                $('.pnlm-container').append('<div class="grid_position"></div>');
                                if($('#position_tab').hasClass('active')) show_grid_position();
                                $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                                $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                                change_effect();
                                switch(tab_edit_room_preview) {
                                    case 'view':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        show_btn_screenshot();
                                        break;
                                    case 'positions':
                                        show_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                    case 'north':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        fix_north();
                                        break;
                                    case 'effects':
                                        hide_grid_position();
                                        show_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                    case 'bulk':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                }
                            },200);
                            viewer.on('mouseup',function () {
                                var yaw = parseInt(viewer.getYaw());
                                var pitch = parseInt(viewer.getPitch());
                                if(yaw<0) {
                                    var northOffset = Math.abs(yaw);
                                } else {
                                    var northOffset =  360 - yaw;
                                }
                                if(northOffset==360) northOffset=0;
                                $('#yaw_pitch_debug').html(yaw+','+pitch);
                                $('#northOffset_debug').html(northOffset);
                            });
                        };
                        var list_assets = ["background"];
                        PIXI.Assets.add("background", panorama_image);
                        PIXI.Assets.load(list_assets).then((textures) => {
                            setup_video_p(null,textures);
                        });
                    } else {
                        $('#panorama').hide();
                        $('#panorama_video').append('<video playsinline webkit-playsinline id="video_viewer" class="video-js vjs-default-skin vjs-big-play-centered" style="width: 100%;height: 400px;margin: 0 auto;" muted preload="none" crossorigin="anonymous"><source src="'+panorama_video+'" type="video/mp4"/></video>');
                        viewer_video = videojs('video_viewer', {
                            loop: true,
                            autoload: true,
                            muted: true,
                            plugins: {
                                pannellum: {
                                    "id_room": window.id_room,
                                    "autoLoad": true,
                                    "showFullscreenCtrl": false,
                                    "showControls": false,
                                    "backgroundColor": background_color_t,
                                    "horizonPitch": parseInt(h_pitch),
                                    "horizonRoll": parseInt(h_roll),
                                    "hfov": parseInt(hfov),
                                    "minHfov": parseInt(min_hfov),
                                    "maxHfov": parseInt(max_hfov),
                                    "yaw": parseInt(yaw),
                                    "pitch": parseInt(pitch),
                                    "minPitch": min_pitch,
                                    "maxPitch" : max_pitch,
                                    "minYaw": parseInt(min_yaw),
                                    "maxYaw" : parseInt(max_yaw),
                                    "haov": parseInt(haov),
                                    "vaov": parseInt(vaov),
                                    "compass": true,
                                    "northOffset": parseInt(northOffset),
                                    "map_north": parseInt(map_north),
                                    "hotSpots": hotSpots,
                                    "friction": 1,
                                    "strings": {
                                        "loadingLabel": "<?php echo _("Loading"); ?>...",
                                    },
                                }
                            }
                        });
                        viewer_video.load();
                        viewer_video.on('ready', function() {
                            viewer_video.play();
                            viewer_video.pnlmViewer.on('load',function () {
                                viewer_initialized = true;
                                $('#north_tab_btn').removeClass('disabled');
                                var yaw = parseInt(viewer_video.pnlmViewer.getYaw());
                                if(yaw<0) {
                                    var northOffset = Math.abs(yaw);
                                } else {
                                    var northOffset =  360 - yaw;
                                }
                                if(northOffset==360) northOffset=0;
                                $('#northOffset_debug').html(northOffset);
                                adjust_ratio_hfov('panorama',viewer_video.pnlmViewer,hfov,min_hfov,max_hfov);
                                adjust_point_position();
                                apply_room_filters();
                                toggle_nadir_logo();
                                var poi_embed_count = $('.poi_embed').length;
                                if(poi_embed_count>0) {
                                    //init_poi_embed();
                                } else {
                                    window.sync_poi_embed_enabled = false;
                                }
                                var dataURL = window.viewer_video.pnlmViewer.getRenderer().render(window.viewer_video.pnlmViewer.getPitch() / 180 * Math.PI,
                                    window.viewer_video.pnlmViewer.getYaw() / 180 * Math.PI,
                                    window.viewer_video.pnlmViewer.getHfov() / 180 * Math.PI,
                                    {'returnImage': 'image/jpeg'});
                                initialize_cropper_thumbnail(dataURL);
                                $('.pnlm-container').append('<div class="grid_position"></div>');
                                if($('#position_tab').hasClass('active')) show_grid_position();
                                $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                                $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                                change_effect();
                                switch(tab_edit_room_preview) {
                                    case 'view':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        show_btn_screenshot();
                                        break;
                                    case 'positions':
                                        show_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                    case 'north':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        fix_north();
                                        break;
                                    case 'effects':
                                        hide_grid_position();
                                        show_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                    case 'bulk':
                                        hide_grid_position();
                                        hide_btn_toggle_effects();
                                        hide_btn_screenshot();
                                        break;
                                }
                            });
                            viewer_video.pnlmViewer.on('mouseup',function () {
                                var yaw = parseInt(viewer_video.pnlmViewer.getYaw());
                                var pitch = parseInt(viewer_video.pnlmViewer.getPitch());
                                if(yaw<0) {
                                    var northOffset = Math.abs(yaw);
                                } else {
                                    var northOffset =  360 - yaw;
                                }
                                if(northOffset==360) northOffset=0;
                                $('#yaw_pitch_debug').html(yaw+','+pitch);
                                $('#northOffset_debug').html(northOffset);
                            });
                        });
                    }
                    break;
                case 'hls':
                    var panorama_url = $('#panorama_url').val();
                    try {
                        loader_p.reset();
                    } catch (e) {}
                    try {
                        video_p.remove();
                    } catch (e) {}
                    $("#canvas_p").empty();
                    var setup_video_p = (loader, resources) => {
                        PIXI.utils.sayHello("WebGL");
                        var index_bg = Object.keys(resources)[0];
                        app_p = new PIXI.Application({
                            antialias: false,
                            transparent: false,
                            resolution: 1,
                            width: resources[index_bg].baseTexture.width,
                            height: resources[index_bg].baseTexture.height
                        });
                        $("#canvas_p").append(app_p.view);
                        let bg = PIXI.Sprite.from(resources[index_bg]);
                        /*bg.anchor.y = 1;
                        bg.scale.y = -1;*/
                        app_p.stage.addChild(bg);
                        video_p = document.createElement('video');
                        video_p.id = 'video_viewer';
                        video_p.crossOrigin = 'anonymous';
                        video_p.preload = 'auto';
                        video_p.autoplay = true;
                        video_p.muted = true;
                        video_p.loop = true;
                        video_p.setAttribute('playsinline','');
                        video_p.setAttribute('webkit-playsinline','');
                        video_p.addEventListener('playing',function() {
                            var width = video_p.videoWidth;
                            var height = video_p.videoHeight;
                        });
                        if (Hls.isSupported()) {
                            var hls = new Hls();
                            hls.loadSource(panorama_url);
                            hls.attachMedia(video_p);
                            hls.on(Hls.Events.MANIFEST_PARSED,function() {
                                video_p.play();
                            });
                        } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
                            video_p.src = panorama_url;
                            video_p.addEventListener('loadedmetadata',function() {
                                video_p.play();
                            });
                        }
                        const sprite = PIXI.Sprite.from(video_p);
                        /*sprite.anchor.y = 1;
                        sprite.scale.y = -1;*/
                        app_p.stage.addChild(sprite);
                        let canvas = $('#canvas_p canvas')[0];
                        viewer = pannellum.viewer('panorama', {
                            "id_room": window.id_room,
                            "type": "equirectangular",
                            "panorama": canvas,
                            "autoLoad": true,
                            "dynamic": true,
                            "dynamicUpdate": true,
                            "backgroundColor": background_color_t,
                            "showFullscreenCtrl": false,
                            "showControls": false,
                            "multiResMinHfov": true,
                            "horizonPitch": parseInt(h_pitch),
                            "horizonRoll": parseInt(h_roll),
                            "hfov": parseInt(hfov),
                            "minHfov": parseInt(min_hfov),
                            "maxHfov": parseInt(max_hfov),
                            "yaw": parseInt(yaw),
                            "pitch": parseInt(pitch),
                            "minPitch": min_pitch,
                            "maxPitch" : max_pitch,
                            "minYaw": parseInt(min_yaw),
                            "maxYaw" : parseInt(max_yaw),
                            "haov": parseInt(haov),
                            "vaov": parseInt(vaov),
                            "compass": true,
                            "northOffset": parseInt(northOffset),
                            "map_north": parseInt(map_north),
                            "hotSpots": hotSpots,
                            "friction": 1,
                            "strings": {
                                "loadingLabel": "<?php echo _("Loading"); ?>...",
                            },
                        });
                        setTimeout(function () {
                            viewer_initialized = true;
                            $('#north_tab_btn').removeClass('disabled');
                            var yaw = parseInt(viewer.getYaw());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#northOffset_debug').html(northOffset);
                            adjust_ratio_hfov('panorama',viewer,hfov,min_hfov,max_hfov);
                            adjust_point_position();
                            apply_room_filters();
                            toggle_nadir_logo();
                            var poi_embed_count = $('.poi_embed').length;
                            if(poi_embed_count>0) {
                                //init_poi_embed();
                            } else {
                                window.sync_poi_embed_enabled = false;
                            }
                            var dataURL = window.viewer.getRenderer().render(window.viewer.getPitch() / 180 * Math.PI,
                                window.viewer.getYaw() / 180 * Math.PI,
                                window.viewer.getHfov() / 180 * Math.PI,
                                {'returnImage': 'image/jpeg'});
                            initialize_cropper_thumbnail(dataURL);
                            $('.pnlm-container').append('<div class="grid_position"></div>');
                            if($('#position_tab').hasClass('active')) show_grid_position();
                            $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                            $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                            change_effect();
                            switch(tab_edit_room_preview) {
                                case 'view':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    show_btn_screenshot();
                                    break;
                                case 'positions':
                                    show_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'north':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    fix_north();
                                    break;
                                case 'effects':
                                    hide_grid_position();
                                    show_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'bulk':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                            }
                        },200);
                        viewer.on('mouseup',function () {
                            var yaw = parseInt(viewer.getYaw());
                            var pitch = parseInt(viewer.getPitch());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#yaw_pitch_debug').html(yaw+','+pitch);
                            $('#northOffset_debug').html(northOffset);
                        });
                    };
                    var list_assets = ["background"];
                    PIXI.Assets.add("background", panorama_image);
                    PIXI.Assets.load(list_assets).then((textures) => {
                        setup_video_p(null,textures);
                    });
                    break;
                case 'lottie':
                    var img_lottie = new Image();
                    img_lottie.onload = function() {
                        var canvas = document.createElement('canvas');
                        canvas.width = this.width;
                        canvas.height = this.height;
                        var lottie_context = canvas.getContext('2d');
                        lottie_context.drawImage(img_lottie, 0, 0);
                        var lottie_pano = bodymovin.loadAnimation({
                            renderer: 'canvas',
                            loop: true,
                            autoplay: true,
                            path: panorama_json,
                            rendererSettings: {
                                context: lottie_context,
                                progressiveLoad: true,
                            }
                        });
                        viewer = pannellum.viewer('panorama', {
                            "id_room": window.id_room,
                            "type": "equirectangular",
                            "panorama": canvas,
                            "autoLoad": true,
                            "dynamic": true,
                            "dynamicUpdate": true,
                            "backgroundColor": background_color_t,
                            "showFullscreenCtrl": false,
                            "showControls": false,
                            "multiResMinHfov": true,
                            "horizonPitch": parseInt(h_pitch),
                            "horizonRoll": parseInt(h_roll),
                            "hfov": parseInt(hfov),
                            "minHfov": parseInt(min_hfov),
                            "maxHfov": parseInt(max_hfov),
                            "yaw": parseInt(yaw),
                            "pitch": parseInt(pitch),
                            "minPitch": min_pitch,
                            "maxPitch" : max_pitch,
                            "minYaw": parseInt(min_yaw),
                            "maxYaw" : parseInt(max_yaw),
                            "haov": parseInt(haov),
                            "vaov": parseInt(vaov),
                            "compass": true,
                            "northOffset": parseInt(northOffset),
                            "map_north": parseInt(map_north),
                            "hotSpots": hotSpots,
                            "friction": 1,
                            "strings": {
                                "loadingLabel": "<?php echo _("Loading"); ?>...",
                            },
                        });
                        setTimeout(function () {
                            viewer_initialized = true;
                            $('#north_tab_btn').removeClass('disabled');
                            var yaw = parseInt(viewer.getYaw());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#northOffset_debug').html(northOffset);
                            adjust_ratio_hfov('panorama',viewer,hfov,min_hfov,max_hfov);
                            adjust_point_position();
                            apply_room_filters();
                            toggle_nadir_logo();
                            var poi_embed_count = $('.poi_embed').length;
                            if(poi_embed_count>0) {
                                //init_poi_embed();
                            } else {
                                window.sync_poi_embed_enabled = false;
                            }
                            var dataURL = window.viewer.getRenderer().render(window.viewer.getPitch() / 180 * Math.PI,
                                window.viewer.getYaw() / 180 * Math.PI,
                                window.viewer.getHfov() / 180 * Math.PI,
                                {'returnImage': 'image/jpeg'});
                            initialize_cropper_thumbnail(dataURL);
                            $('.pnlm-container').append('<div class="grid_position"></div>');
                            if($('#position_tab').hasClass('active')) show_grid_position();
                            $('.pnlm-container').append('<button onclick="toggle_effects();" id="btn_toggle_effetcs" class="btn btn-sm btn-light"><i class="fas fa-circle active"></i> <?php echo str_replace("'","\'",_("effects")); ?></button>');
                            $('.pnlm-container').append('<button onclick="take_screenshot();" id="btn_screenshot" class="btn btn-sm btn-light"><i class="fas fa-camera"></i> <?php echo str_replace("'","\'",_("screenshot")); ?></button>');
                            change_effect();
                            switch(tab_edit_room_preview) {
                                case 'view':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    show_btn_screenshot();
                                    break;
                                case 'positions':
                                    show_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'north':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    fix_north();
                                    break;
                                case 'effects':
                                    hide_grid_position();
                                    show_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                                case 'bulk':
                                    hide_grid_position();
                                    hide_btn_toggle_effects();
                                    hide_btn_screenshot();
                                    break;
                            }
                        },200);
                        viewer.on('mouseup',function () {
                            var yaw = parseInt(viewer.getYaw());
                            var pitch = parseInt(viewer.getPitch());
                            if(yaw<0) {
                                var northOffset = Math.abs(yaw);
                            } else {
                                var northOffset =  360 - yaw;
                            }
                            if(northOffset==360) northOffset=0;
                            $('#yaw_pitch_debug').html(yaw+','+pitch);
                            $('#northOffset_debug').html(northOffset);
                        });
                    }
                    img_lottie.crossOrigin = false;
                    img_lottie.src = panorama_image;
                    break;
            }
        }

        window.take_screenshot = function () {
            if(room_type=='video') {
                var elem = document.getElementById('video_viewer');
            } else {
                var elem = document.getElementById('panorama');
            }
            var maxWidth = elem.style.maxWidth;
            if(elem.requestFullscreen){
                elem.requestFullscreen();
            } else if(elem.mozRequestFullScreen){
                elem.mozRequestFullScreen();
            } else if(elem.webkitRequestFullscreen){
                elem.webkitRequestFullscreen();
            } else if(elem.msRequestFullscreen){
                elem.msRequestFullscreen();
            }
            elem.style.maxWidth = '100%';
            setTimeout(function () {
                if(room_type=='video' && !window.is_mobile) {
                    var dataURL =  viewer_video.pnlmViewer.getRenderer().render(viewer_video.pnlmViewer.getPitch() / 180 * Math.PI,
                        viewer_video.pnlmViewer.getYaw() / 180 * Math.PI,
                        viewer_video.pnlmViewer.getHfov() / 180 * Math.PI,
                        {'returnImage': 'screenshot'});

                } else {
                    var dataURL = viewer.getRenderer().render(viewer.getPitch() / 180 * Math.PI,
                        viewer.getYaw() / 180 * Math.PI,
                        viewer.getHfov() / 180 * Math.PI,
                        {'returnImage': 'screenshot'});
                }
                if(document.exitFullscreen){
                    document.exitFullscreen();
                } else if(document.mozCancelFullScreen){
                    document.mozCancelFullScreen();
                } else if(document.webkitExitFullscreen){
                    document.webkitExitFullscreen();
                } else if(document.msExitFullscreen){
                    document.msExitFullscreen();
                }
                setTimeout(function () {
                    elem.style.maxWidth = maxWidth;
                },750);
                var d = new Date();
                var n = d.getMilliseconds();
                download_screenshot(dataURL,'screenshot_'+n+'.jpeg');
            },1000);
        }

        const download_screenshot = (path, filename) => {
            const anchor = document.createElement('a');
            anchor.href = path;
            anchor.download = filename;
            document.body.appendChild(anchor);
            anchor.click();
            document.body.removeChild(anchor);
        };

        window.set_yaw_pitch = function() {
            if(room_type=='video' && !window.is_mobile) {
                var yaw = parseInt(viewer_video.pnlmViewer.getYaw());
                var pitch = parseInt(viewer_video.pnlmViewer.getPitch());
            } else {
                var yaw = parseInt(viewer.getYaw());
                var pitch = parseInt(viewer.getPitch());
            }
            $('#yaw_pitch').val(yaw+","+pitch);
            $('#yaw_pitch_saved').html(yaw+","+pitch);
            $('#ip_yaw_l').html(yaw);
            $('#ip_pitch_l').html(pitch);
            window.room_need_save = true;
        }

        window.change_north_map = function() {
            var northOffset = $('#north_map').val();
            $('#north_map_val').html(northOffset);
            $('#northOffset').val(northOffset);
            $('#northOffset_save').html(northOffset);
            $('#north_l').html(northOffset);
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setNorthOffset(northOffset);
            } else {
                viewer.setNorthOffset(northOffset);
            }
            window.room_need_save = true;
        }

        window.set_northOffset = function() {
            if(room_type=='video' && !window.is_mobile) {
                var yaw = parseInt(viewer_video.pnlmViewer.getYaw());
            } else {
                var yaw = parseInt(viewer.getYaw());
            }
            if(yaw<0) {
                var northOffset = Math.abs(yaw);
            } else {
                var northOffset =  360 - yaw;
            }
            $('#north_map').val(northOffset);
            $('#north_map_val').html(northOffset);
            $('#northOffset').val(northOffset);
            $('#northOffset_save').html(northOffset);
            $('#north_l').html(northOffset);
            if(room_type=='video' && !window.is_mobile) {
                viewer_video.pnlmViewer.setNorthOffset(northOffset);
            } else {
                viewer.setNorthOffset(northOffset);
            }
            window.room_need_save = true;
        }

        window.adjust_point_position = function () {
            $('.pointer_view').show();
            var image_w = $('.map_image').width();
            var image_h = $('.map_image').height();
            var ratio = image_w / image_h;
            var ratio_w = image_w / 300;
            var ratio_h = image_h / ((image_w / ratio_w) / ratio);
            var pos_left = (parseInt(map_left)+parseInt(point_size)/2) * ratio_w;
            var pos_top = (parseInt(map_top)+parseInt(point_size)/2) * ratio_h;
            $('.pointer_'+window.id_room).css('top',pos_top+'px');
            $('.pointer_'+window.id_room).css('left',pos_left+'px');
        }

        window.apply_room_filters = function () {
            $('#btn_toggle_effetcs i').addClass('active');
            var brightness = $('#brightness').val();
            $('#brightness_val').html(brightness+'%');
            var contrast = $('#contrast').val();
            $('#contrast_val').html(contrast+'%');
            var saturate = $('#saturate').val();
            $('#saturate_val').html(saturate+'%');
            var grayscale = $('#grayscale').val();
            $('#grayscale_val').html(grayscale+'%');
            if(room_type=='video' && !window.is_mobile) {
                var canvas = viewer_video.pnlmViewer.getRenderer().getCanvas();
            } else {
                var canvas = viewer.getRenderer().getCanvas();
            }
            var filter = '';
            if(brightness!=100) {
                filter += 'brightness('+brightness+'%) ';
            }
            if(contrast!=100) {
                filter += 'contrast('+contrast+'%) ';
            }
            if(saturate!=100) {
                filter += 'saturate('+saturate+'%) ';
            }
            if(grayscale!=0) {
                filter += 'grayscale('+grayscale+'%) ';
            }
            canvas.style.filter = filter;
        }

        window.change_effect = function () {
            reset_effects();
            $('.snow_effect').remove();
            $('.rain_effect').remove();
            $('.fireworks_effect').remove();
            $('.fog_effect').remove();
            $('.confetti_effect').remove();
            $('.sparkle_effect').remove();
            var effect = $('#effect option:selected').attr('id');
            switch(effect) {
                case 'snow':
                    $('.pnlm-dragfix').append('<canvas class="snow_effect"></canvas>');
                    init_snow();
                    $('.snow_effect').fadeIn();
                    break;
                case 'rain':
                    $('.pnlm-dragfix').append('<canvas class="rain_effect"></canvas>');
                    init_rain();
                    $('.rain_effect').fadeIn();
                    break;
                case 'fireworks':
                    $('.pnlm-dragfix').append('<canvas class="fireworks_effect"></canvas>');
                    init_fireworks();
                    $('.fireworks_effect').fadeIn();
                    break;
                case 'fog':
                    $('.pnlm-dragfix').append('<canvas class="fog_effect"></canvas>');
                    init_fog();
                    $('.fog_effect').fadeIn();
                    break;
                case 'confetti':
                    $('.pnlm-dragfix').append('<canvas class="confetti_effect"></canvas>');
                    init_confetti();
                    $('.confetti_effect').fadeIn();
                    break;
                case 'sparkle':
                    $('.pnlm-dragfix').append('<canvas class="sparkle_effect"></canvas>');
                    $('.sparkle_effect').show();
                    init_sparkle();
                    break;
                default:
                    break;
            }
        }

        $(window).resize(function() {
            if(viewer_initialized) {
                adjust_point_position();
            }
            if(room_type=='video') {
                var id_panorama = 'video_viewer';
            } else {
                var id_panorama = 'panorama';
            }
            try {
                if($('#div_panorama').is(':visible')) {
                    var c_w = parseFloat($('#'+id_panorama).css('width').replace('px',''));
                    var new_height = c_w / 1.7771428571428571;
                    $('#'+id_panorama).css('height',new_height+'px');
                    $('#panorama_image_edit').parent().css('height', new_height + 'px');
                }
            } catch (e) {}
            window.poi_embed_originals_pos = [];
            var poi_embed_count = $('.poi_embed').length;
            if(poi_embed_count>0) {
                //init_poi_embed(true);
            } else {
                window.sync_poi_embed_enabled = false;
            }
            var poi_embed_count = $('.poi_embed').length;
            if(poi_embed_count>0) {
                setTimeout(function () {
                    adjust_poi_embed_helpers_all();
                },50);
            }
        });

        $('#txtFile').bind('change', function() {
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
                        if(room_type=='image' || room_type=='hls') {
                            change_image(evt.target.responseText);
                        } else {
                            change_video(evt.target.responseText);
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

        $('body').on('submit','#frm_j',function(e){
            e.preventDefault();
            $('#error_j').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_j[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_j' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_j(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_j(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        viewer_initialized = false;
                        window.change_json = 1;
                        window.panorama_json = evt.target.responseText;
                        window.room_need_save = true;
                        $('#disk_space_original').html('--');
                        $('#disk_space_compressed').html('--');
                        $('#disk_space_multires').html('--');
                        $('#disk_space_total').html('--');
                    }
                }
                upadte_progressbar(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_j('upload failed');
                upadte_progressbar_j(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_j('upload aborted');
                upadte_progressbar_j(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_j(value){
            $('#progressBar_j').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_j').hide();
            }else{
                $('.progress_j').show();
            }
        }

        function show_error_j(error){
            $('.progress_j').hide();
            $('#error_j').show();
            $('#error_j').html(error);
        }

        function change_image(path) {
            viewer_initialized = false;
            window.panorama_image = path;
            $('#panorama_image').attr('src',path);
            $('#panorama_image').attr('data-src',path);
            window.change_image = 1;
            window.change_video = 0;
            window.room_need_save = true;
            $('#disk_space_original').html('--');
            $('#disk_space_compressed').html('--');
            $('#disk_space_multires').html('--');
            $('#disk_space_total').html('--');
        }

        function change_video(path) {
            viewer_initialized = false;
            window.panorama_video = path;
            $('#panorama_image').attr('src',video_preview);
            window.change_image = 0;
            window.change_video = 1;
            window.room_need_save = true;
            $('#disk_space_original').html('--');
            $('#disk_space_compressed').html('--');
            $('#disk_space_multires').html('--');
            $('#disk_space_total').html('--');
        }

        video.addEventListener('loadeddata', function() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            video.currentTime = 0;
        }, false);

        video.addEventListener('seeked', function() {
            var context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            video_preview = canvas.toDataURL("image/jpeg",0.8);
        }, false);

        var playSelectedFile = function(event) {
            if(room_type=='video') {
                var file = this.files[0];
                var fileURL = URL.createObjectURL(file);
                video.src = fileURL;
            }
        }

        try {
            var input = document.getElementById('txtFile');
            input.addEventListener('change', playSelectedFile, false);
        } catch (e) {}

        $('body').on('submit','#frm_s',function(e){
            e.preventDefault();
            $('#error_s').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_s[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_s' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_s(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_s(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.room_need_save = true;
                        window.song = evt.target.responseText;
                        $('#div_delete_song').show();
                        $('#div_player_song').show();
                        $('#div_upload_song').hide();
                        $('#div_exist_song').hide();
                        if(window.s3_enabled==1) {
                            $('#div_player_song audio').attr('src',window.s3_url+'viewer/content/'+window.song);
                        } else {
                            $('#div_player_song audio').attr('src','../viewer/content/'+window.song);
                        }
                    }
                }
                upadte_progressbar_s(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_s('upload failed');
                upadte_progressbar_s(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_s('upload aborted');
                upadte_progressbar_s(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_s(value){
            $('#progressBar_s').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_s(error){
            $('.progress').hide();
            $('#error_s').show();
            $('#error_s').html(error);
        }

        $('body').on('submit','#frm_alt',function(e){
            e.preventDefault();
            $('#error_alt').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_alt[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_alt' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_alt(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_alt(evt.target.responseText);
                } else {
                    get_rooms_alt_images(window.id_room);
                }
                upadte_progressbar_alt(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_alt('upload failed');
                upadte_progressbar_alt(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_alt('upload aborted');
                upadte_progressbar_alt(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_alt(value){
            $('#progressBar_alt').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress').hide();
            }else{
                $('.progress').show();
            }
        }

        function show_error_alt(error){
            $('.progress').hide();
            $('#error_alt').show();
            $('#error_alt').html(error);
        }

        $('body').on('submit','#frm_thumb',function(e){
            var html_button = $('#btnUpload_thumb').html();
            $('#btnUpload_thumb').html('<i class="fas fa-circle-notch fa-spin"></i>');
            $('#btn_edit_thumbnail').addClass('disabled');
            $('#frm_thumb').addClass('disabled');
            e.preventDefault();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_thumb[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_thumb' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_thumb(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_thumb(evt.target.responseText);
                } else {
                    window.thumb_image = evt.target.responseText;
                    if(window.s3_enabled==1) {
                        $('#thumb_image').attr('src',window.s3_url+'viewer/panoramas/thumb_custom/'+window.thumb_image);
                    } else {
                        $('#thumb_image').attr('src','../viewer/panoramas/thumb_custom/'+window.thumb_image);
                    }
                }
                $('#btn_edit_thumbnail').removeClass('disabled');
                $('#frm_thumb').removeClass('disabled');
                $('#btnUpload_thumb').html(html_button);
                upadte_progressbar_thumb(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_thumb('upload failed');
                upadte_progressbar_thumb(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_thumb('upload aborted');
                upadte_progressbar_thumb(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_thumb(value){
            if(value==0) {
                $('#btnUpload_thumb').removeClass('disabled');
            } else {
                $('#btnUpload_thumb').addClass('disabled');
            }
        }

        function show_error_thumb(error){
            alert(error);
        }

        $('body').on('submit','#frm_l',function(e){
            e.preventDefault();
            $('#error_l').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
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
                        window.room_need_save = true;
                        window.logo = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_logo img').attr('src',window.s3_url+'viewer/content/'+window.logo);
                        } else {
                            $('#div_image_logo img').attr('src','../viewer/content/'+window.logo);
                        }
                        $('#div_delete_logo').show();
                        $('#div_image_logo').show();
                        $('#div_upload_logo').hide();
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
                $('#progress_l').hide();
            }else{
                $('#progress_l').show();
            }
        }

        function show_error_l(error){
            $('#progress_l').hide();
            $('#error_l').show();
            $('#error_l').html(error);
        }

        $("input:not(:radio)").change(function(){
            window.room_need_save = true;
        });

        $("select").change(function(){
            window.room_need_save = true;
        });

        $(window).on('beforeunload', function(){
            if(window.room_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });

    })(jQuery); // End of use strict

    function change_mrv_type(id) {
        window.virtual_staging = id;
        $('.mrv_type').css('opacity',0.3);
        $('#mrv_type_'+id).css('opacity',1);
        switch(id) {
            case 0:
            case 1:
                $('#lp_duration').prop('disabled',true);
                $('#lp_fade').prop('disabled',true);
                $('#auto_open').prop('disabled',false);
                $('#from_hour').parent().parent().addClass('d-none');
                $('#to_hour').parent().parent().addClass('d-none');
                $('.remove_image_room_alt .fa-edit').removeClass('disabled');
                break;
            case 3:
                $('#lp_duration').prop('disabled',true);
                $('#lp_fade').prop('disabled',true);
                $('#auto_open').prop('disabled',true);
                $('#from_hour').parent().parent().removeClass('d-none');
                $('#to_hour').parent().parent().removeClass('d-none');
                $('.remove_image_room_alt .fa-edit').removeClass('disabled');
                break;
            case 2:
                $('#lp_duration').prop('disabled',false);
                $('#lp_fade').prop('disabled',false);
                $('#auto_open').prop('disabled',false);
                $('#from_hour').parent().parent().addClass('d-none');
                $('#to_hour').parent().parent().addClass('d-none');
                $('.remove_image_room_alt .fa-edit').addClass('disabled');
                break;
        }
    }

    function change_transition_zoom() {
        var transition_zoom = $('#transition_zoom').val();
        $('#transition_zoom_val').html(transition_zoom);
    }
</script>