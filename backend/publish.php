<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$role = get_user_role($id_user);
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $tmp_languages = get_languages_vt();
    $array_languages = $tmp_languages[0];
    $default_language = $tmp_languages[1];
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
    if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
    $base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","",$_SERVER['SCRIPT_NAME']);
    $link = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","viewer/index.php?code=",$_SERVER['SCRIPT_NAME']);
    $link_vr = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","vr/index.php?code=",$_SERVER['SCRIPT_NAME']);
    $link_f = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","viewer/",$_SERVER['SCRIPT_NAME']);
    $link_f_vr = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","vr/",$_SERVER['SCRIPT_NAME']);
    $plan_permissions = get_plan_permission($id_user);
    $publish = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['publish']==0) {
            $publish = false;
        }
    }
    $first_room = get_fisrt_room($id_virtualtour_sel);
    if(!empty($virtual_tour['password'])) {
        $virtual_tour['password']="keep_password";
    }
    $settings = get_settings();
    $array_input_lang = array();
    $query_lang = "SELECT language,meta_title,meta_description FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour_sel;";
    $result_lang = $mysqli->query($query_lang);
    if($result_lang) {
        if ($result_lang->num_rows > 0) {
            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                $language = $row_lang['language'];
                unset($row_lang['language']);
                $array_input_lang[$language]=$row_lang;
            }
        }
    }
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$publish): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <a href="#collapsePI" class="d-block card-header py-3 collapsed <?php echo (!$plan_permissions['enable_password_tour']) ? 'disabled' : '' ; ?>" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-lock"></i> <?php echo _("Protection"); ?></h6>
            </a>
            <div class="collapse" id="collapsePI">
                <div class="card-body <?php echo (!$plan_permissions['enable_password_tour']) ? 'disabled' : '' ; ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="protect_type"><?php echo _("Type"); ?></label><br>
                                <select onchange="change_type_protection();" id="protect_type" class="form-control">
                                    <option <?php echo ($virtual_tour['protect_type']=='none') ? 'selected':''; ?> id="none"><?php echo _("None"); ?></option>
                                    <option <?php echo ($virtual_tour['protect_type']=='password') ? 'selected':''; ?> id="password"><?php echo _("Password"); ?></option>
                                    <option <?php echo ($virtual_tour['protect_type']=='lead') ? 'selected':''; ?> id="lead"><?php echo _("Leads"); ?></option>
                                    <option <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'selected':''; ?> id="mailchimp"><?php echo _("Mailchimp Signup Form"); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="protect_remember"><?php echo _("Remember"); ?> <i title="<?php echo _("if the correct information is entered, do not request it at the next access"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <input <?php echo ($virtual_tour['protect_type']=='none') ? 'disabled' : ''; ?> type="checkbox" id="protect_remember" <?php echo ($virtual_tour['protect_remember']) ? 'checked' : ''; ?> />
                            </div>
                        </div>
                        <div class="col-md-6 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                            <div class="form-group">
                                <label for="vt_password_title"><?php echo _("Title"); ?></label>
                                <input <?php echo ($virtual_tour['protect_type']=='none') ? 'disabled' : ''; ?> type="text" class="form-control" id="vt_password_title" value="<?php echo $virtual_tour['password_title']; ?>" />
                            </div>
                        </div>
                        <div class="col-md-12 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? '':'d-none'; ?>">
                            <div class="form-group">
                                <label for="protect_mc_form"><?php echo _("Embedded Form Code"); ?> <i title="<?php echo _("Mailchimp -> Audience -> Signup Forms -> Embedded forms"); ?>" class="help_t fas fa-question-circle"></i></label>
                                <textarea class="form-control" id="protect_mc_form" rows="4"><?php echo $virtual_tour['protect_mc_form']; ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-12 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                            <div class="form-group">
                                <label for="vt_password_description"><?php echo _("Description"); ?></label>
                                <textarea <?php echo ($virtual_tour['protect_type']=='none') ? 'disabled' : ''; ?> class="form-control" id="vt_password_description" rows="2"><?php echo $virtual_tour['password_description']; ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-4 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'd-none':''; ?>">
                            <div class="form-group">
                                <label for="vt_password"><?php echo _("Password"); ?></label>
                                <input <?php echo ($virtual_tour['protect_type']!='password') ? 'disabled' : ''; ?> autocomplete="new-password" type="password" class="form-control" id="vt_password" value="<?php echo $virtual_tour['password']; ?>" />
                            </div>
                        </div>
                        <div class="col-md-2 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'd-none':''; ?> <?php echo (!$settings['smtp_valid']) ? 'd-none' : ''; ?>">
                            <div class="form-group">
                                <label for="protect_send_email"><?php echo _("Send Notification"); ?> <i title="<?php echo _("sends a notification to the specified email when the lead form is submitted"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <input <?php echo ($virtual_tour['protect_type']!='lead') ? 'disabled' : ''; ?> type="checkbox" id="protect_send_email" <?php echo ($virtual_tour['protect_send_email']) ? 'checked' : ''; ?> />
                            </div>
                        </div>
                        <div class="col-md-6 <?php echo ($virtual_tour['protect_type']=='mailchimp') ? 'd-none':''; ?> <?php echo (!$settings['smtp_valid']) ? 'd-none' : ''; ?>">
                            <div class="form-group">
                                <label for="protect_email"><?php echo _("E-Mail"); ?></label>
                                <input <?php echo ($virtual_tour['protect_type']!='lead') ? 'disabled' : ''; ?> type="text" class="form-control" id="protect_email" value="<?php echo $virtual_tour['protect_email']; ?>" />
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button id="btn_protect" onclick="set_password_vt();" class="btn btn-sm btn-success btn-block <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SAVE"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <a href="#collapsePI_2" class="d-block card-header py-3 collapsed <?php echo (!$plan_permissions['enable_expiring_dates']) ? 'disabled' : '' ; ?>" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-calendar-alt"></i> <?php echo _("Expiring Dates"); ?></i></h6>
            </a>
            <div class="collapse" id="collapsePI_2">
                <div class="card-body <?php echo (!$plan_permissions['enable_expiring_dates']) ? 'disabled' : '' ; ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date"><?php echo _("Start Date"); ?></label>
                                <input type="date" class="form-control" id="start_date" value="<?php echo $virtual_tour['start_date']; ?>" />
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="start_url"><?php echo _("Redirect URL if < Start Date"); ?></label>
                                <input type="text" class="form-control" id="start_url" value="<?php echo $virtual_tour['start_url']; ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date"><?php echo _("End Date"); ?></label>
                                <input type="date" class="form-control" id="end_date" value="<?php echo $virtual_tour['end_date']; ?>" />
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="end_url"><?php echo _("Redirect URL if > End Date"); ?></label>
                                <input type="text" class="form-control" id="end_url" value="<?php echo $virtual_tour['end_url']; ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button id="btn_expires" onclick="set_expiring_dates()" class="btn btn-sm btn-primary btn-block <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("SET EXPIRING DATES"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <a href="#collapsePI2" class="d-block card-header py-3 collapsed <?php echo (!$plan_permissions['enable_metatag']) ? 'disabled' : '' ; ?>" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-hashtag"></i> <?php echo _("Meta Tag"); ?></h6>
            </a>
            <div class="collapse" id="collapsePI2">
                <div class="card-body <?php echo (!$plan_permissions['enable_metatag']) ? 'disabled' : '' ; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="meta_title"><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'meta_title'); ?>
                                <input oninput="change_meta_title();" onchange="change_meta_title();" type="text" class="form-control" id="meta_title" value="<?php echo $virtual_tour['meta_title']; ?>" />
                                <?php foreach ($array_languages as $lang) {
                                    if($lang!=$default_language) : ?>
                                        <input style="display:none;" oninput="change_meta_title();" onchange="change_meta_title();" type="text" class="form-control input_lang" data-target-id="meta_title" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['meta_title']; ?>" />
                                    <?php endif;
                                } ?>
                            </div>
                            <div class="form-group">
                                <label for="meta_description"><?php echo _("Description"); ?></label></label><?php echo print_language_input_selector($array_languages,$default_language,'meta_description'); ?>
                                <textarea oninput="change_meta_description();" onchange="change_meta_description();" rows="3" class="form-control" id="meta_description"><?php echo $virtual_tour['meta_description']; ?></textarea>
                                <?php foreach ($array_languages as $lang) {
                                    if($lang!=$default_language) : ?>
                                        <textarea style="display:none;" oninput="change_meta_description();" onchange="change_meta_description();" rows="3" class="form-control input_lang" data-target-id="meta_description" data-lang="<?php echo $lang; ?>"><?php echo $array_input_lang[$lang]['meta_description']; ?></textarea>
                                    <?php endif;
                                } ?>
                            </div>
                            <div class="form-group">
                                <label><?php echo _("Image"); ?></label>
                                <div style="display: none" id="div_delete_image_meta" class="form-group mt-2">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_image_meta('virtual_tour',<?php echo $id_virtualtour_sel; ?>);" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
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
                                    <?php if(empty($virtual_tour['meta_image'])) {
                                        if(empty($virtual_tour['background_image'])) {
                                            $meta_image = '';
                                            $meta_path = '';
                                        } else {
                                            $meta_image = $virtual_tour['background_image'];
                                            if($s3_enabled) {
                                                $meta_path = $s3_url."viewer/content/$meta_image";
                                            } else {
                                                $meta_path = "../viewer/content/$meta_image";
                                            }
                                        }
                                    } else {
                                        $meta_image = $virtual_tour['meta_image'];
                                        if($s3_enabled) {
                                            $meta_path = $s3_url."viewer/content/$meta_image";
                                        } else {
                                            $meta_path = "../viewer/content/$meta_image";
                                        }
                                    } ?>
                                    <img class="facebook-preview__image <?php echo (empty($meta_image)) ? 'd-none' : ''; ?>" src="<?php echo $meta_path; ?>" alt="">
                                    <div class="facebook-preview__content">
                                        <div class="facebook-preview__url">
                                            <?php echo $_SERVER['SERVER_NAME']; ?>
                                        </div>
                                        <h2 class="facebook-preview__title">
                                            <?php if(empty($virtual_tour['meta_title'])) {
                                                echo $virtual_tour['name'];
                                            } else {
                                                echo $virtual_tour['meta_title'];
                                            } ?>
                                        </h2>
                                        <div class="facebook-preview__description">
                                            <?php if(empty($virtual_tour['meta_description'])) {
                                                echo $virtual_tour['description'];
                                            } else {
                                                echo $virtual_tour['meta_description'];
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
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-share-alt"></i> <?php echo _("Share & Embed"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mr-4 d-inline-block">
                            <label for="status"><?php echo _("Status"); ?></label><br>
                            <input <?php echo ($demo) ? 'disabled' : ''; ?> id="status" <?php echo ($virtual_tour['active']) ? 'checked' : ''; ?> type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" data-size="normal" data-on="<?php echo _("Activated"); ?>" data-off="<?php echo _("Deactivated"); ?>">
                        </div>
                        <div class="form-group <?php echo ($user_info['role']!='administrator') ? 'd-none' : 'd-inline-block' ?>">
                            <label for="show_in_first_page"><?php echo _("Show as first page"); ?> (<?php echo $base_url; ?>) <i title="<?php echo _("only visible to administrators"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                            <input <?php echo ($demo) ? 'disabled' : ''; ?> id="show_in_first_page" <?php echo ($virtual_tour['show_in_first_page']) ? 'checked' : ''; ?> type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="light" data-size="normal" data-on="<?php echo _("Yes"); ?>" data-off="<?php echo _("No"); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="link"><i class="fas fa-link"></i> <?php echo _("Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'link'); ?>
                            <div class="input-group mb-0">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="link" value="<?php echo $link . $virtual_tour['code']; ?>" />
                                <?php foreach ($array_languages as $lang) {
                                    if($lang!=$default_language) : ?>
                                        <input id="link_<?php echo $lang; ?>" style="display:none;" readonly type="text" class="form-control input_lang bg-white mb-0 pb-0" data-target-id="link" data-lang="<?php echo $lang; ?>" value="<?php echo $link . $virtual_tour['code'] . "&lang=$lang"; ?>" />
                                    <?php endif;
                                } ?>
                                <div class="input-group-append">
                                    <a id="open_link" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link . $virtual_tour['code']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <a style="display:none;" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success input_lang" data-target-id="open_link" data-lang="<?php echo $lang; ?>" href="<?php echo $link . $virtual_tour['code'] . "&lang=$lang"; ?>" target="_blank">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif;
                                    } ?>
                                    <button id="copy_link" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#link">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <button style="display:none;" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn input_lang" data-target-id="copy_link" data-lang="<?php echo $lang; ?>" data-clipboard-target="#link_<?php echo $lang; ?>">
                                                <i class="far fa-clipboard"></i>
                                            </button>
                                        <?php endif;
                                    } ?>
                                    <button id="qrcode_link" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link . $virtual_tour['code']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <button style="display:none;" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link . $virtual_tour['code'] . "&lang=$lang"; ?>');" class="btn btn-secondary input_lang" data-target-id="qrcode_link" data-lang="<?php echo $lang; ?>">
                                                <i class="fas fa-qrcode"></i>
                                            </button>
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                        <div id="share_link" style="margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style" data-a2a-url="<?php echo $link . $virtual_tour['code']; ?>">
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
                                <div style="display:none;margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style input_lang" data-a2a-url="<?php echo $link . $virtual_tour['code'] . "&lang=$lang"; ?>" data-target-id="share_link" data-lang="<?php echo $lang; ?>">
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
                        <!--<div id="social_share" style="font-size:12px"></div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="link_f"><i class="fas fa-link"></i> <?php echo _("Friendly Link"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'link_f'); ?>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text noselect" id="basic-addon3"><?php echo $link_f; ?></span>
                                </div>
                                <input <?php echo ($demo) ? 'disabled' : ''; ?> type="text" class="form-control bg-white" id="link_f" value="<?php echo $virtual_tour['friendly_url']; ?>" />
                                <?php foreach ($array_languages as $lang) {
                                    if($lang!=$default_language) : ?>
                                        <input style="display:none" readonly type="text" class="form-control input_lang bg-white" data-target-id="link_f" data-lang="<?php echo $lang; ?>" value="<?php echo (!empty($virtual_tour['friendly_url'])) ? $virtual_tour['friendly_url']."@".$lang : ''; ?>" />
                                    <?php endif;
                                } ?>
                                <div class="input-group-append <?php echo (empty($virtual_tour['friendly_url'])) ? 'disabled' : '' ; ?>">
                                    <a id="link_open" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link_f . $virtual_tour['friendly_url']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <a style="display:none;" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success input_lang" data-target-id="open_link_f" data-lang="<?php echo $lang; ?>" href="<?php echo $link_f.$virtual_tour['friendly_url']."@".$lang; ?>" target="_blank">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif;
                                    } ?>
                                    <button id="link_copy" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-text="<?php echo $link_f . $virtual_tour['friendly_url']; ?>">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <button style="display:none;" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn input_lang" data-target-id="copy_link_f" data-lang="<?php echo $lang; ?>" data-clipboard-text="<?php echo $link_f.$virtual_tour['friendly_url']."@".$lang; ?>">
                                                <i class="far fa-clipboard"></i>
                                            </button>
                                        <?php endif;
                                    } ?>
                                    <button id="link_qr" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_f.$virtual_tour['friendly_url']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <button style="display:none;" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_f.$virtual_tour['friendly_url']."@".$lang; ?>');" class="btn btn-secondary input_lang" data-target-id="qrcode_link_f" data-lang="<?php echo $lang; ?>">
                                                <i class="fas fa-qrcode"></i>
                                            </button>
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="code"><i class="fas fa-code"></i> <?php echo _("Embed Code"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'code'); ?>
                            <div class="input-group">
                                <textarea id="code" class="form-control" rows="3"><iframe id="vt_iframe_<?php echo $virtual_tour['code']; ?>" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $link . $virtual_tour['code']; ?>"></iframe></textarea>
                                <?php foreach ($array_languages as $lang) {
                                    if($lang!=$default_language) : ?>
                                        <textarea style="display:none;" id="code_<?php echo $lang; ?>" class="form-control input_lang" data-target-id="code" data-lang="<?php echo $lang; ?>" rows="3"><iframe id="vt_iframe_<?php echo $virtual_tour['code']; ?>" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $link.$virtual_tour['code']."&lang=".$lang; ?>"></iframe></textarea>
                                    <?php endif;
                                } ?>
                                <div class="input-group-append">
                                    <button id="copy_code" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#code">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <button style="display:none;" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn input_lang" data-target-id="copy_code" data-lang="<?php echo $lang; ?>" data-clipboard-target="#code_<?php echo $lang; ?>">
                                                <i class="far fa-clipboard"></i>
                                            </button>
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if($virtual_tour['external']==0) : ?>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-vr-cardboard"></i> <?php echo _("Virtual Reality"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="link_vr"><i class="fas fa-link"></i> <?php echo _("Link"); ?></label>
                            <div class="input-group">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="link_vr" value="<?php echo $link_vr . $virtual_tour['code']; ?>" />
                                <div class="input-group-append">
                                    <a title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link_vr . $virtual_tour['code']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#link_vr">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_vr . $virtual_tour['code']; ?>');" class="btn btn-secondary">
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
                            <label for="link_f_vr"><i class="fas fa-link"></i> <?php echo _("Friendly Link"); ?></label>
                            <div class="input-group <?php echo (empty($virtual_tour['friendly_url'])) ? 'disabled' : '' ; ?>">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="link_f_vr" value="<?php echo $link_f_vr . $virtual_tour['friendly_url']; ?>" />
                                <div class="input-group-append">
                                    <a id="link_open_vr" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link_f_vr . $virtual_tour['friendly_url']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button id="link_copy_vr" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#link_f_vr"">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button id="link_qr_vr" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_f_vr . $virtual_tour['friendly_url']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div id="api_sample_div" class="col-md-12 <?php echo ($user_info['role']!='administrator') ? 'd-none' : ''; ?>">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-laptop-code"></i> <?php echo _("Integration Sample Code"); ?> <span style="font-size: 12px"><?php echo _("(only visible to administrators)"); ?></span></h6>
            </div>
            <div class="card-body">
                <div id="api_sample" style="position: relative;width: 100%;height: 400px;"><?php echo htmlentities('<html>
<head>
    <title>API Sample</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
</head>
<body>
<!-- viewer embed code !-->
<iframe id="vt_iframe_'.$virtual_tour['code'].'" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.$link.$virtual_tour['code'].'"></iframe>
<br><br>
<button disabled onclick="goto_room('.$first_room['id'].');">GO TO '.strtoupper($first_room['name']).'</button> <!-- replace the id of the room !-->
<button disabled onclick="goto_next_room();">GO TO NEXT ROOM</button>
<button disabled onclick="goto_prev_room();">GO TO PREV ROOM</button>
<br><br>
<input placeholder="latitude" id="latitude" type="text"> <input placeholder="longitude" id="longitude" type="text">
<button disabled onclick="goto_room_coordinates();">GO TO COORDINATES</button>
<script>
    var id_iframe = "vt_iframe_'.$virtual_tour['code'].'";
    var iframe_svt = document.getElementById(id_iframe).contentWindow;
    window.addEventListener("message", function(evt) {
        if(evt.data.payload=="initialized") {
            //Tour initialized -> put your code here
            var buttons = document.querySelectorAll("button");
            for (var i = 0; i < buttons.length; ++i) {
                buttons[i].disabled = false;
            }
        }
    }, false);
    function goto_room(id_room) {
        //function to go to the room via its id
        iframe_svt.postMessage({"payload":"goto_room","id_room":id_room}, "*");
    }
    function goto_next_room() {
        //function to go to the next room
        iframe_svt.postMessage({"payload":"goto_next_room"}, "*");
    }
    function goto_prev_room() {
        //function to go to the previous room
        iframe_svt.postMessage({"payload":"goto_prev_room"}, "*");
    }
    function goto_room_coordinates() {
        //function to go to nearest room based on given coordinates
        var lat = document.getElementById("latitude").value;
        var lon = document.getElementById("longitude").value;
        iframe_svt.postMessage({"payload":"goto_room_coordinates","coordinates":[lat,lon]}, "*");
    }
</script>
</body>
</html>'); ?></div>
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

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.link_f = '<?php echo $link_f; ?>';
        window.link_f_vr = '<?php echo $link_f_vr; ?>';
        window.image_meta = '<?php echo $virtual_tour['meta_image']; ?>';
        window.image_meta_default = '<?php echo $virtual_tour['background_image']; ?>';
        window.title_meta_default = `<?php echo $virtual_tour['name']; ?>`;
        window.description_meta_default = `<?php echo $virtual_tour['description']; ?>`;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            bsCustomFileInput.init();
            $('.cpy_btn').tooltip({
                trigger: 'click',
                placement: 'bottom'
            });
            if(window.image_meta=='') {
                $('#div_delete_image_meta').hide();
                $('#div_upload_image_meta').show();
            } else {
                $('#div_delete_image_meta').show();
                $('#div_upload_image_meta').hide();
            }
            var clipboard = new ClipboardJS('.cpy_btn');
            clipboard.on('success', function(e) {
                setTooltip(e.trigger, window.backend_labels.copied+"!");
                hideTooltip(e.trigger);
            });
            var api_sample = ace.edit('api_sample');
            api_sample.session.setMode("ace/mode/html");
            api_sample.setOption('enableLiveAutocompletion',true);
            api_sample.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                api_sample.setTheme("ace/theme/one_dark");
            }
            /*$("#social_share").jsSocials({
                url: '<?php echo $link . $virtual_tour['code']; ?>',
                shareIn: "popup",
                showLabel: false,
                showCount: false,
                shares: ["email", "twitter", "facebook", "linkedin", "pinterest", "stumbleupon", "viber", "messenger", "telegram", "line" , "whatsapp"]
            });*/
            $('#status').change(function() {
                if($(this).prop('checked')) {
                    var status = 1;
                } else {
                    var status = 0;
                }
                set_status_vt(status);
            });
            $('#show_in_first_page').change(function() {
                if($(this).prop('checked')) {
                    var show_in_first_page = 1;
                } else {
                    var show_in_first_page = 0;
                }
                set_show_in_first_page(show_in_first_page,'vt');
            });
            var timer_furl;
            $('#link_f').on('input',function(){
                $('.input_lang[data-target-id="link_f"]').each(function() {
                    var lang = $(this).attr('data-lang');
                    if($('#link_f').val()=='') {
                        $(this).val('');
                        var link_f = '';
                    } else {
                        $(this).val($('#link_f').val()+"@"+lang);
                        var link_f = window.link_f+$('#link_f').val()+"@"+lang;
                    }
                    $('.input_lang[data-target-id="open_link_f"][data-lang="'+lang+'"]').attr('href',link_f);
                    $('.input_lang[data-target-id="copy_link_f"][data-lang="'+lang+'"]').attr('data-clipboard-text',link_f);
                    $('.input_lang[data-target-id="qrcode_link_f"][data-lang="'+lang+'"]').attr('onclick','open_qr_code_modal(\''+link_f+'\')');
                });
                if(timer_furl) {
                    clearTimeout(timer_furl);
                }
                timer_furl = setTimeout(function() {
                    change_friendly_url('virtual_tour','link_f',window.id_virtualtour);
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
                            if(window.s3_enabled==1) {
                                $('.facebook-preview__image').attr('src',window.s3_url+'viewer/content/'+window.image_meta);
                            } else {
                                $('.facebook-preview__image').attr('src','../viewer/content/'+window.image_meta);
                            }
                            $('.facebook-preview__image').removeClass('d-none');
                            $('#div_delete_image_meta').show();
                            $('#div_upload_image_meta').hide();
                            save_metadata('virtual_tour',window.id_virtualtour);
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
            $('#meta_title,#meta_description,.input_lang[data-target-id="meta_title"],.input_lang[data-target-id="meta_description"]').on('input',function(){
                if(timer_meta) {
                    clearTimeout(timer_meta);
                }
                timer_meta = setTimeout(function() {
                    save_metadata('virtual_tour',window.id_virtualtour);
                },400);
            });
        });
    })(jQuery); // End of use strict

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