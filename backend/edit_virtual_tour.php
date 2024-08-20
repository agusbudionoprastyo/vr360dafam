<?php
session_start();
$id_virtual_tour = $_GET['id'];
$virtual_tour = get_virtual_tour($id_virtual_tour,$_SESSION['id_user']);
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
$plan_permissions = get_plan_permission($_SESSION['id_user']);
if($virtual_tour['external']==1) {
    $hide_external = "d-none";
    $show_external = "";
    $col2 = "6";
    $tab = "active";
} else {
    $hide_external = "";
    $show_external = "d-none";
    $col2 = "4";
    $tab = "fade";
}
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($_SESSION['id_user'],$id_virtual_tour);
    if($editor_permissions['edit_virtualtour']==0) {
        $virtual_tour=false;
    }
}
$icons_library = get_plan_permission($_SESSION['id_user'])['enable_icons_library'];
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($_SESSION['id_user'],$id_virtual_tour);
    if($editor_permissions['icons_library']==0) {
        $icons_library = 0;
    }
}
$shop = get_plan_permission($_SESSION['id_user'])['enable_shop'];
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($_SESSION['id_user'],$id_virtual_tour);
    if($editor_permissions['shop']==0) {
        $shop = 0;
    }
}
$custom_html = get_plan_permission($_SESSION['id_user'])['enable_custom_html'];
$loading_iv = get_plan_permission($_SESSION['id_user'])['enable_loading_iv'];
$first_panorama = get_first_room_panorama($id_virtual_tour);
$first_panorama_image = $first_panorama['panorama_image'];
?>

<?php if(!$virtual_tour): ?>
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

<?php
$_SESSION['id_virtualtour_sel'] = $id_virtual_tour;
$_SESSION['name_virtualtour_sel'] = $virtual_tour['name'];
$change_plan = get_settings()['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
$user_role = get_user_role($_SESSION['id_user']);
$users = get_users($virtual_tour['id_user']);
$show_in_ui_audio = $virtual_tour['show_audio'];
$show_in_ui_logo = $virtual_tour['show_logo'];
$show_in_ui_poweredby = $virtual_tour['show_poweredby'];
$show_in_ui_form = $virtual_tour['show_main_form'];

$s3_params = check_s3_tour_enabled($id_virtual_tour);
$s3_enabled = false;
$s3_url = "";
$path_base_url = "../viewer/";
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $path_base_url = $s3_url."viewer/";
        $s3_enabled = true;
    }
}

$url_background_image = "";
$url_background_image_mobile = "";
$url_background_video = "";
$url_background_video_mobile = "";
$url_logo = "";
$url_poweredby = "";
$url_nadir_logo = "";
$url_song = "";
$url_intro_desktop = "";
$url_intro_mobile = "";
if(!empty($virtual_tour['background_image'])) {
    $url_background_image = $path_base_url."content/".$virtual_tour['background_image'];
}
if(!empty($virtual_tour['background_image_mobile'])) {
    $url_background_image_mobile = $path_base_url."content/".$virtual_tour['background_image_mobile'];
}
if(!empty($virtual_tour['background_video'])) {
    $url_background_video = $path_base_url."content/".$virtual_tour['background_video']."#t=2";
}
if(!empty($virtual_tour['background_video_mobile'])) {
    $url_background_video_mobile = $path_base_url."content/".$virtual_tour['background_video_mobile']."#t=2";
}
if(!empty($virtual_tour['logo'])) {
    $url_logo = $path_base_url."content/".$virtual_tour['logo'];
}
if(!empty($virtual_tour['poweredby_image'])) {
    $url_poweredby = $path_base_url."content/".$virtual_tour['poweredby_image'];
}
if(!empty($virtual_tour['nadir_logo'])) {
    $url_nadir_logo = $path_base_url."content/".$virtual_tour['nadir_logo'];
}
if(!empty($virtual_tour['song'])) {
    $url_song = $path_base_url."content/".$virtual_tour['song'];
}
if(!empty($virtual_tour['intro_desktop'])) {
    $url_intro_desktop = $path_base_url."content/".$virtual_tour['intro_desktop'];
}
if(!empty($virtual_tour['intro_mobile'])) {
    $url_intro_mobile = $path_base_url."content/".$virtual_tour['intro_mobile'];
}
$form_content = $virtual_tour['form_content'];
if(!empty($form_content)) {
    $form_content = json_decode($form_content,true);
}
$array_input_lang = array();
$query_lang = "SELECT * FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtual_tour;";
$result_lang = $mysqli->query($query_lang);
if($result_lang) {
    if ($result_lang->num_rows > 0) {
        while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
            $language = $row_lang['language'];
            if(!empty($row_lang['form_content'])) {
                $row_lang['form_content']=json_decode($row_lang['form_content'],true);
                for($i=0;$i<=10;$i++) {
                    if($i==0) {
                        if($row_lang['form_content'][0]['title']==$form_content[0]['title']) {
                            $row_lang['form_content'][0]['title']="";
                        }
                        if($row_lang['form_content'][0]['button']==$form_content[0]['button']) {
                            $row_lang['form_content'][0]['button']="";
                        }
                        if($row_lang['form_content'][0]['response']==$form_content[0]['response']) {
                            $row_lang['form_content'][0]['response']="";
                        }
                        if($row_lang['form_content'][0]['description']==$form_content[0]['description']) {
                            $row_lang['form_content'][0]['description']="";
                        }
                    } else {
                        if($row_lang['form_content'][$i]['label']==$form_content[$i]['label']) {
                            $row_lang['form_content'][$i]['label']="";
                        }
                    }
                }
            } else {
                $row_lang['form_content']=array();
            }
            unset($row_lang['id_virtualtour']);
            unset($row_lang['language']);
            $array_input_lang[$language]=$row_lang;
        }
    }
}
?>

<link rel="stylesheet" href="../viewer/css/pannellum.css"/>
<script type="text/javascript" src="../viewer/js/libpannellum.js"></script>
<script type="text/javascript" src="../viewer/js/pannellum.js"></script>
<style>
    .pnlm-control {
        opacity: 1;
    }
</style>

<?php include("check_plan.php"); ?>

<ul class="nav bg-white nav-pills nav-fill mb-2 <?php echo $hide_external; ?>">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="pill" href="#settings_tab"><i class="fas fa-cogs"></i> <?php echo strtoupper(_("SETTINGS")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#content_tab"><i class="fas fa-photo-video"></i> <?php echo strtoupper(_("CONTENTS")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#loading_tab"><i class="fas fa-spinner"></i> <?php echo strtoupper(_("LOADING")); ?></a>
    </li>
    <li class="nav-item">
        <a onclick="initialize_hfov();" class="nav-link" data-toggle="pill" href="#hfov_tab"><i class="fas fa-binoculars"></i> <?php echo strtoupper(_("HFOV / INTERACTION")); ?></a>
    </li>
    <?php if($plan_permissions['enable_forms']==1): ?>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#form_tab"><i class="fas fa-file-signature"></i> <?php echo strtoupper(_("FORM")); ?></a>
    </li>
    <?php endif; ?>
    <?php if($shop==1): ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#shop_tab"><i class="fas fa-shopping-cart"></i> <?php echo strtoupper(_("SHOP")); ?></a>
        </li>
    <?php endif; ?>
    <?php if($user_info['role']=='administrator'): ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#note_tab"><i class="far fa-sticky-note"></i> <?php echo strtoupper(_("NOTE")); ?></a>
        </li>
        <li class="nav-item">
            <a onclick="click_editors();" class="nav-link" data-toggle="pill" href="#editors_tab"><i class="fas fa-users-cog"></i> <?php echo strtoupper(_("EDITORS")); ?></a>
        </li>
    <?php endif; ?>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="settings_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cog"></i> <?php echo _("General"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Name"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'name'); ?>
                                    <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($virtual_tour['name']); ?>" />
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                        <input style="display:none;" type="text" class="form-control input_lang" data-target-id="name" data-lang="<?php echo $lang; ?>" value="<?php echo htmlspecialchars($array_input_lang[$lang]['name']); ?>" />
                                    <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="author"><?php echo _("Author"); ?></label>
                                    <input type="text" class="form-control" id="author" value="<?php echo htmlspecialchars($virtual_tour['author']); ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user"><?php echo _("User"); ?> <i title="<?php echo _("owner of the virtual tour"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select id="user" class="form-control" <?php echo ($user_role=='administrator' && $users['count']>1) ? '' : 'disabled' ?> >
                                        <?php echo $users['options']; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category"><?php echo _("Category"); ?></label><br>
                                    <div class="input-group">
                                        <select multiple id="category" data-selected-text-format="count > 3" data-count-selected-text="{0} <?php echo _("items selected"); ?>" data-none-selected-text="<?php echo _("Nothing selected"); ?>" class="form-control selectpicker">
                                            <?php echo get_categories_option($id_virtual_tour); ?>
                                        </select>
                                        <script type="text/javascript">$('#category').selectpicker('render');</script>
                                        <div class="input-group-append">
                                            <button data-toggle="modal" data-target="#modal_add_category" class="btn btn-primary btn-xs <?php echo (($user_info['role']!='administrator') || ($demo)) ? 'disabled' : ''; ?>" type="button"><i style="color: white" class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description"><?php echo _("Description"); ?> <i title="<?php echo _("description used as preview for share"); ?>" class="help_t fas fa-question-circle"></i></label><?php echo print_language_input_selector($array_languages,$default_language,'description'); ?><br>
                                    <input type="text" class="form-control" id="description" value="<?php echo htmlspecialchars($virtual_tour['description']); ?>" />
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control input_lang" data-target-id="description" data-lang="<?php echo $lang; ?>" value="<?php echo htmlspecialchars($array_input_lang[$lang]['description']); ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-6 <?php echo $show_external; ?>">
                                <div class="form-group">
                                    <label for="external_url"><?php echo _("External Link"); ?> <i title="<?php echo _("link that will be displayed when the virtual tour opens (must be compatible for embedding)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="text" class="form-control" id="external_url" value="<?php echo $virtual_tour['external_url']; ?>" />
                                </div>
                            </div>
                            <div id="ga_tracking_id_div" class="col-md-3">
                                <div class="form-group">
                                    <label for="ga_tracking_id"><?php echo _("Google Analytics Tracking ID"); ?> <i title="<?php echo _("Google Analytics Tracking ID (G-XXXXXXXXX). Note: Use the Friendly URL in Google Analytics's property url setting."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="text" class="form-control" id="ga_tracking_id" value="<?php echo $virtual_tour['ga_tracking_id']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="enable_views_stat"><?php echo _("Total Views"); ?> <i title="<?php echo _("enables viewing of total views directly on the tour"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="enable_views_stat" <?php echo ($virtual_tour['enable_views_stat']==1) ? 'checked':''; ?>>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="enable_visitor_rt"><?php echo _("Visitors"); ?> <i title="<?php echo _("enables viewing of online visitors directly on the tour"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="enable_visitor_rt" <?php echo ($virtual_tour['enable_visitor_rt']==1) ? 'checked':''; ?>>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="interval_visitor_rt"><?php echo _("Visitors update"); ?> <i title="<?php echo _("visitor update time in milliseconds (0 = real time). Low values can increase server utilization."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="interval_visitor_rt" value="<?php echo $virtual_tour['interval_visitor_rt']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?> <?php echo ($virtual_tour['ar_simulator']) ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="ar_camera_align"><?php echo _("AR Camera alignment"); ?> <i title="<?php echo _("enables the initial step to align the camera with the environment"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="ar_camera_align" <?php echo ($virtual_tour['ar_camera_align']==1) ? 'checked':''; ?>>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo ($virtual_tour['external']==0) ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label for="language"><?php echo _("Default Language"); ?></label>
                                    <select class="form-control" id="language">
                                        <option <?php echo ($virtual_tour['language']=='') ? 'selected':''; ?> id=""><?php echo _("System Language"); ?> (<?php echo $settings['language']; ?>)</option>
                                        <?php if (check_language_enabled('ar_SA',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='ar_SA') ? 'selected':''; ?> id="ar_SA">Arabic (ar_SA)</option><?php endif; ?>
                                        <?php if (check_language_enabled('zh_CN',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='zh_CN') ? 'selected':''; ?> id="zh_CN">Chinese simplified (zh_CN)</option><?php endif; ?>
                                        <?php if (check_language_enabled('zh_HK',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='zh_HK') ? 'selected':''; ?> id="zh_HK">Chinese traditional (zh_HK)</option><?php endif; ?>
                                        <?php if (check_language_enabled('zh_TW',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='zh_TW') ? 'selected':''; ?> id="zh_TW">Chinese traditional (zh_TW)</option><?php endif; ?>
                                        <?php if (check_language_enabled('cs_CZ',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='cs_CZ') ? 'selected':''; ?> id="cs_CZ">Czech (cs_CZ)</option><?php endif; ?>
                                        <?php if (check_language_enabled('nl_NL',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='nl_NL') ? 'selected':''; ?> id="nl_NL">Dutch (nl_NL)</option><?php endif; ?>
                                        <?php if (check_language_enabled('en_US',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='en_US') ? 'selected':''; ?> id="en_US">English (en_US)</option><?php endif; ?>
                                        <?php if (check_language_enabled('fil_PH',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='fil_PH') ? 'selected':''; ?> id="fil_PH">Filipino (fil_PH)</option><?php endif; ?>
                                        <?php if (check_language_enabled('fr_FR',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='fr_FR') ? 'selected':''; ?> id="fr_FR">French (fr_FR)</option><?php endif; ?>
                                        <?php if (check_language_enabled('de_DE',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='de_DE') ? 'selected':''; ?> id="de_DE">German (de_DE)</option><?php endif; ?>
                                        <?php if (check_language_enabled('hi_IN',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='hi_IN') ? 'selected':''; ?> id="hi_IN">Hindi (hi_IN)</option><?php endif; ?>
                                        <?php if (check_language_enabled('hu_HU',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='hu_HU') ? 'selected':''; ?> id="hu_HU">Hungarian (hu_HU)</option><?php endif; ?>
                                        <?php if (check_language_enabled('kw_KW',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='kw_KW') ? 'selected':''; ?> id="kw_KW">Kinyarwanda (kw_KW)</option><?php endif; ?>
                                        <?php if (check_language_enabled('ko_KR',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='ko_KR') ? 'selected':''; ?> id="ko_KR">Korean (ko_KR)</option><?php endif; ?>
                                        <?php if (check_language_enabled('id_ID',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='id_ID') ? 'selected':''; ?> id="id_ID">Indonesian (id_ID)</option><?php endif; ?>
                                        <?php if (check_language_enabled('it_IT',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='it_IT') ? 'selected':''; ?> id="it_IT">Italian (it_IT)</option><?php endif; ?>
                                        <?php if (check_language_enabled('ja_JP',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='ja_JP') ? 'selected':''; ?> id="ja_JP">Japanese (ja_JP)</option><?php endif; ?>
                                        <?php if (check_language_enabled('fa_IR',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='fa_IR') ? 'selected':''; ?> id="fa_IR">Persian (fa_IR)</option><?php endif; ?>
                                        <?php if (check_language_enabled('pl_PL',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='pl_PL') ? 'selected':''; ?> id="pl_PL">Polish (pl_PL)</option><?php endif; ?>
                                        <?php if (check_language_enabled('pt_BR',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='pt_BR') ? 'selected':''; ?> id="pt_BR">Portuguese Brazilian (pt_BR)</option><?php endif; ?>
                                        <?php if (check_language_enabled('pt_PT',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='pt_PT') ? 'selected':''; ?> id="pt_PT">Portuguese European (pt_PT)</option><?php endif; ?>
                                        <?php if (check_language_enabled('es_ES',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='es_ES') ? 'selected':''; ?> id="es_ES">Spanish (es_ES)</option><?php endif; ?>
                                        <?php if (check_language_enabled('ro_RO',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='ro_RO') ? 'selected':''; ?> id="ro_RO">Romanian (ro_RO)</option><?php endif; ?>
                                        <?php if (check_language_enabled('ru_RU',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='ru_RU') ? 'selected':''; ?> id="ru_RU">Russian (ru_RU)</option><?php endif; ?>
                                        <?php if (check_language_enabled('sv_SE',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='sv_SE') ? 'selected':''; ?> id="sv_SE">Swedish (sv_SE)</option><?php endif; ?>
                                        <?php if (check_language_enabled('tg_TJ',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='tg_TJ') ? 'selected':''; ?> id="tg_TJ">Tajik (tg_TJ)</option><?php endif; ?>
                                        <?php if (check_language_enabled('th_TH',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='th_TH') ? 'selected':''; ?> id="th_TH">Thai (th_TH)</option><?php endif; ?>
                                        <?php if (check_language_enabled('tr_TR',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='tr_TR') ? 'selected':''; ?> id="tr_TR">Turkish (tr_TR)</option><?php endif; ?>
                                        <?php if (check_language_enabled('vi_VN',$settings['languages_enabled'])) : ?><option <?php echo ($virtual_tour['language']=='vi_VN') ? 'selected':''; ?> id="vi_VN">Vietnamese (vi_VN)</option><?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row <?php echo $hide_external; ?> <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-retweet"></i> <?php echo _("Transition"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transition_effect"><?php echo _("Transition Effect"); ?> <i title="<?php echo _("transition animation effect"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <select id="transition_effect" class="form-control">
                                        <option <?php echo ($virtual_tour['transition_effect']=='blind') ? 'selected':''; ?> id="blind">Blind</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='bounce') ? 'selected':''; ?> id="bounce">Bounce</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='clip') ? 'selected':''; ?> id="clip">Clip</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='drop') ? 'selected':''; ?> id="drop">Drop</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='fade') ? 'selected':''; ?> id="fade">Fade</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='puff') ? 'selected':''; ?> id="puff">Puff</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='pulsate') ? 'selected':''; ?> id="pulsate">Pulsate</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='scale') ? 'selected':''; ?> id="scale">Scale</option>
                                        <option <?php echo ($virtual_tour['transition_effect']=='shake') ? 'selected':''; ?> id="shake">Shake</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transition_fadeout"><?php echo _("Transition Duration"); ?> <i title="<?php echo _("transition duration in milliseconds"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="transition_fadeout" value="<?php echo $virtual_tour['transition_fadeout']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transition_time"><?php echo _("Transition Zoom Time"); ?> <i title="<?php echo _("transition time before entering the next room in milliseconds"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="transition_time" value="<?php echo $virtual_tour['transition_time']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_zoom"><?php echo _("Transition Zoom Level"); ?> (<span id="transition_zoom_val"><?php echo $virtual_tour['transition_zoom']; ?></span>) <i title="<?php echo _("transition zoom level before entering the next room"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_transition_zoom();" type="range" min="0" max="100" class="form-control-range" id="transition_zoom" value="<?php echo $virtual_tour['transition_zoom']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="transition_loading"><?php echo _("Transition Loading icon"); ?> <i title="<?php echo _("shows the loading icon before loading rooms"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="transition_loading" <?php echo ($virtual_tour['transition_loading']==1) ? 'checked':''; ?>>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sameAzimuth"><?php echo _("Same Azimuth"); ?> <i title="<?php echo _("maintain the same direction with regard to north while navigate between rooms (you must set the north position in all rooms)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="sameAzimuth" <?php echo ($virtual_tour['sameAzimuth'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="markers_default_backlink"><?php echo _("Default Add Back Markers"); ?> <i title="<?php echo _("default 'Add Marker to go back' setting when adding a new marker"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="markers_default_backlink" <?php echo ($virtual_tour['markers_default_backlink'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="click_anywhere"><?php echo _("Click Anywhere"); ?> <i title="<?php echo _("allows you to click near the marker to go to the corresponding room"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input onchange="change_click_anywhere();" type="checkbox" id="click_anywhere" <?php echo ($virtual_tour['click_anywhere'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hide_markers"><?php echo _("Hide Markers"); ?> <i title="<?php echo _("hide all the markers (only when click anywhere is enabled)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input onchange="change_click_anywhere();" <?php echo (!$virtual_tour['click_anywhere'])?'disabled':''; ?> type="checkbox" id="hide_markers" <?php echo ($virtual_tour['hide_markers'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hover_markers"><?php echo _("Hover Markers"); ?> <i title="<?php echo _("shows hidden markers when approaching them with the mouse"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo (!$virtual_tour['click_anywhere'] || !$virtual_tour['hide_markers'])?'disabled':''; ?> type="checkbox" id="hover_markers" <?php echo ($virtual_tour['hover_markers'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div style="margin-bottom: 5px;" class="form-group">
                                    <label for="markers_default_lookat"><?php echo _("Default Markers LookAt"); ?> <i title="<?php echo _("default 'lookat' setting when adding a new marker"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select id="markers_default_lookat" class="form-control">
                                        <option <?php echo ($virtual_tour['markers_default_lookat']==0) ? 'selected' : ''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                        <option <?php echo ($virtual_tour['markers_default_lookat']==1) ? 'selected' : ''; ?> id="1"><?php echo _("Horizontal only"); ?></option>
                                        <option <?php echo ($virtual_tour['markers_default_lookat']==2) ? 'selected' : ''; ?> id="2"><?php echo _("Horizontal and Vertical"); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row <?php echo $hide_external; ?> <?php echo ($virtual_tour['ar_simulator']) ? 'd-none' : ''; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-keyboard"></i> <?php echo _("Controls"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="keyboard_mode"><?php echo _("Keyboard Mode"); ?></label><br>
                                    <select onchange="change_keyboard_mode();" id="keyboard_mode" class="form-control">
                                        <option <?php echo ($virtual_tour['keyboard_mode']==0) ? 'selected':''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                        <option <?php echo ($virtual_tour['keyboard_mode']==1) ? 'selected':''; ?> id="1"><?php echo _("Enabled, mode 1"); ?></option>
                                        <option <?php echo ($virtual_tour['keyboard_mode']==2) ? 'selected':''; ?> id="2"><?php echo _("Enabled, mode 2"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label style="opacity:0">.</label><br>
                                <div class="<?php echo ($virtual_tour['keyboard_mode']==0) ? '':'d-none'; ?>" id="keyboard_msg_0"><?php echo _("Keyboard controls are disabled."); ?></div>
                                <div class="<?php echo ($virtual_tour['keyboard_mode']==1) ? '':'d-none'; ?>" id="keyboard_msg_1"><i class="fas fa-arrow-left"></i> <i class="fas fa-arrow-up"></i> <i class="fas fa-arrow-down"></i> <i class="fas fa-arrow-right"></i> <b>WASD</b> <?php echo _("to look around"); ?>&nbsp;&nbsp;&nbsp;<b>SPACE</b> <?php echo _("to click"); ?>&nbsp;&nbsp;&nbsp;<b>Z</b> <?php echo _("to go previous room"); ?>&nbsp;&nbsp;&nbsp;<b>X</b> <?php echo _("to go next room"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-minus"></i> <i class="fas fa-plus"></i> <?php echo _("to zoom in/out"); ?></div>
                                <div class="<?php echo ($virtual_tour['keyboard_mode']==2) ? '':'d-none'; ?>" id="keyboard_msg_2"><i class="fas fa-arrow-left"></i> <i class="fas fa-arrow-right"></i> <b>WASD</b> <?php echo _("to look around"); ?>&nbsp;&nbsp;&nbsp;<b>SPACE</b> <?php echo _("to click"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-arrow-down"></i> <?php echo _("to go previous room"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-arrow-up"></i> <?php echo _("to go next room"); ?>&nbsp;&nbsp;&nbsp;<i class="fas fa-minus"></i> <i class="fas fa-plus"></i> <?php echo _("to zoom in/out"); ?></div>
                            </div>
                            <div class="col-md-12 <?php echo (!$plan_permissions['enable_context_info']) ? 'd-none' : '' ; ?>">
                                <div class="form-group">
                                    <label for="context_info"><?php echo _("Right Click Content"); ?> <i title="<?php echo _("content displayed when the right button is pressed. leave empty for disable"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <div id="context_info"><?php echo $virtual_tour['context_info']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row <?php echo $hide_external; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bolt"></i> <?php echo _("Performance"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quality_viewer"><?php echo _("Viewer quality"); ?> <i title="<?php echo _("lower values means faster view (poor quality), higher value means slow view (high quality)."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input min="0.3" max="1.1" step="0.1" type="range" class="form-control-range" id="quality_viewer" value="<?php echo $virtual_tour['quality_viewer']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="compress_jpg"><?php echo _("Compress images quality"); ?> <i title="<?php echo _("10 to 100: lower values means faster loading (poor quality), higher value means slow loading (high quality). 100 to disable compression."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input min="10" max="100" type="number" class="form-control" id="compress_jpg" value="<?php echo $virtual_tour['compress_jpg']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="max_width_compress"><?php echo _("Max width panorama"); ?> <i title="<?php echo _("maximum width in pixels of panoramic images. if they exceed this width the images will be resized. 0 to disable resize."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="max_width_compress" value="<?php echo $virtual_tour['max_width_compress']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">px</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="preload_panoramas"><?php echo _("Preload panoramas"); ?> <i title="<?php echo _("preload all panorama images for faster loading between rooms"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="preload_panoramas" <?php echo ($virtual_tour['preload_panoramas'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mobile_panoramas"><?php echo _("Mobile panoramas"); ?> <i title="<?php echo _("uses a version of the panorama image optimized for mobile devices"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="mobile_panoramas" <?php echo ($virtual_tour['mobile_panoramas'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="enable_multires"><?php echo _("Enable multi resolution"); ?> <i title="<?php echo _("splits the panorama image into multiple sectors and loads them in parallel to reduce loading times"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo (!$plan_permissions['enable_multires']) ? 'disabled' : '' ; ?> type="checkbox" id="enable_multires" <?php echo ($virtual_tour['enable_multires'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo _("Regenerate panoramas"); ?> <i title="<?php echo _("force regenerate all panoramas images"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <button id="btn_regenerate_panoramas" onclick="regenerate_panoramas();" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled':''; ?>"><?php echo _("Regenerate All"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row <?php echo $hide_external; ?> <?php echo ($user_info['role']!='administrator') ? 'd-none' : ''; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-eraser"></i> <?php echo _("Clean Up"); ?> <i style="font-size:12px;"><?php echo _("(only visible to administrators)"); ?></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="keep_original_panorama"><?php echo _("Keep original panoramas"); ?> <i title="<?php echo _("keep a copy of the original uploaded file. N.B. if you don't keep them you won't be able to regenerate the panoramas"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="keep_original_panorama" <?php echo ($virtual_tour['keep_original_panorama'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _("Recoverable disk space"); ?> <i title="<?php echo _("Disk space recovered after deleting original panoramas"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div id="disk_space_original" class="h5 mb-0 font-weight-bold text-gray-800">
                                        <button style="line-height:1;opacity:1" onclick="get_disk_space_original();" class="btn btn-sm btn-primary p-1"><i class="fab fa-digital-ocean"></i> <?php echo _("analyze"); ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _("Original panoramas"); ?> <i title="<?php echo _("deletes all the original uploaded file. N.B. if you don't keep them you won't be able to regenerate the panoramas"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <button id="btn_delete_original_panoramas" onclick="delete_original_panoramas();" class="btn btn-block btn-danger <?php echo ($demo) ? 'disabled':''; ?>"><?php echo _("Delete All"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane <?php echo $tab; ?>" id="loading_tab">
        <div class="row <?php echo ($virtual_tour['external']==1) ? 'd-block' : ''; ?>">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-spinner"></i> <?php echo _("Settings"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="auto_start"><?php echo _("Auto start"); ?> <i title="<?php echo _("start the virtual tour automatically on loading"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <select id="auto_start" class="form-control form-control-sm">
                                        <option <?php echo ($virtual_tour['auto_start']==0)?'selected':''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                        <option <?php echo ($virtual_tour['auto_start']==1)?'selected':''; ?> id="1"><?php echo _("Enabled"); ?></option>
                                        <option <?php echo ($virtual_tour['auto_start']==2)?'selected':''; ?> id="2"><?php echo _("Enabled (Disabled when embedded)"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="hide_loading"><?php echo _("Loading Info"); ?>  <i title="<?php echo _("display logo, name and progress bar during initial loading"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="hide_loading" <?php echo ($virtual_tour['hide_loading'])?'':'checked'; ?> />
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="flyin"><?php echo _("Fly-In"); ?> <i title="<?php echo _("start the fly-in animation at the first entrance to the virtual tour"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo (!$plan_permissions['enable_flyin']) ? 'disabled' : '' ; ?> type="checkbox" id="flyin" <?php echo ($virtual_tour['flyin'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="flyin_duration"><?php echo _("Fly-In Duration"); ?> <i title="<?php echo _("duration in milliseconds of the fly-in animation."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group input-group-sm">
                                        <input type="number" min="0" class="form-control form-control-sm" id="flyin_duration" value="<?php echo $virtual_tour['flyin_duration']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-palette"></i> <?php echo _("Colors"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="loading_background_color"><?php echo _("Loading Background Color"); ?></label><br>
                                    <input type="text" class="form-control" id="loading_background_color" value="<?php echo $virtual_tour['loading_background_color']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="loading_text_color"><?php echo _("Loading Text Color"); ?></label><br>
                                    <input type="text" class="form-control" id="loading_text_color" value="<?php echo $virtual_tour['loading_text_color']; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="<?php echo ($virtual_tour['external']==1) ? 'float-left' : ''; ?> <?php echo ($loading_iv==0) ? 'd-none' : ''; ?> col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Background Image (Desktop)"); ?> <i title="<?php echo _("image displayed as background during initial loading and used as preview image for share"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_bg" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_bg"><?php echo _("Exist Background"); ?></label>
                                    <select onchange="change_exist_bg();" class="form-control" id="exist_bg">
                                        <option selected id="0"><?php echo _("Upload new Background"); ?></option>
                                        <?php echo get_option_exist_background_logo($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_bg" class="col-md-12">
                                <img src="<?php echo $url_background_image; ?>" />
                            </div>
                            <div style="display: none" id="div_delete_bg" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_bg();" class="btn btn-block btn-danger"><?php echo _("REMOVE IMAGE"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_bg">
                                <?php if($upload_content) : ?>
                                    <form id="frm_b" action="ajax/upload_background_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_b" name="txtFile_b" />
                                                        <label class="custom-file-label" for="txtFile_b"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b" value="<?php echo _("Upload Background Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_b mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_b" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b"></div>
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
            <div class="<?php echo ($virtual_tour['external']==1) ? 'float-left' : ''; ?> <?php echo ($loading_iv==0) ? 'd-none' : ''; ?> col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Background Image (Mobile)"); ?> <i title="<?php echo _("image displayed as background during initial loading on a mobile"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_bg_m" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_bg_m"><?php echo _("Exist Background"); ?></label>
                                    <select onchange="change_exist_bg_m();" class="form-control" id="exist_bg_m">
                                        <option selected id="0"><?php echo _("Upload new Background"); ?></option>
                                        <?php echo get_option_exist_background_m_logo($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none;" id="div_image_bg_m" class="col-md-12">
                                <img src="<?php echo $url_background_image_mobile; ?>" />
                            </div>
                            <div style="display: none" id="div_delete_bg_m" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_bg_m();" class="btn btn-block btn-danger"><?php echo _("REMOVE IMAGE"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_bg_m">
                                <?php if($upload_content) : ?>
                                    <form id="frm_b_m" action="ajax/upload_background_image_m.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_b_m" name="txtFile_b_m" />
                                                        <label class="custom-file-label" for="txtFile_b_m"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b_m" value="<?php echo _("Upload Background Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_b_m mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_b_m" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b_m"></div>
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
            <div class="col-md-6 <?php echo ($loading_iv==0) ? 'd-none' : ''; ?> <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-video"></i> <?php echo _("Background Video (Desktop)"); ?> <i title="<?php echo _("video displayed as background during initial loading"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_video_bg" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_video_bg"><?php echo _("Exist Background"); ?></label>
                                    <select onchange="change_exist_video_bg();" class="form-control" id="exist_video_bg">
                                        <option selected id="0"><?php echo _("Upload new Background"); ?></option>
                                        <?php echo get_option_exist_background_video($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_video_bg" class="col-md-12">
                                <video muted><source src="<?php echo $url_background_video; ?>" type="video/mp4"></video>
                            </div>
                            <div style="display: none" id="div_video_params" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="background_video_delay"><?php echo _("Video display time (seconds)"); ?> <i title="<?php echo _("set to 0 to wait for the end of the video, otherwise set the seconds for which the video should be displayed"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($virtual_tour['background_video']=='') ? 'disabled' : '' ; ?> class="form-control" type="number" min="0" id="background_video_delay" value="<?php echo $virtual_tour['background_video_delay'];?>" />
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_video_bg" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_video_bg();" class="btn btn-block btn-danger"><?php echo _("REMOVE VIDEO"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_video_bg">
                                <?php if($upload_content) : ?>
                                    <form id="frm_b_v" action="ajax/upload_background_video.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_b_v" name="txtFile_b_v" />
                                                        <label class="custom-file-label" for="txtFile_b_v"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b_v" value="<?php echo _("Upload Background Video"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_b_v mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_b_v" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b_v"></div>
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
            <div class="col-md-6 <?php echo ($loading_iv==0) ? 'd-none' : ''; ?> <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-video"></i> <?php echo _("Background Video (Mobile)"); ?> <i title="<?php echo _("video displayed as background during initial loading on mobile"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_video_bg_m" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_video_bg_m"><?php echo _("Exist Background"); ?></label>
                                    <select onchange="change_exist_video_bg_m();" class="form-control" id="exist_video_bg_m">
                                        <option selected id="0"><?php echo _("Upload new Background"); ?></option>
                                        <?php echo get_option_exist_background_m_video($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_video_bg_m" class="col-md-12">
                                <video muted><source src="<?php echo $url_background_video_mobile; ?>" type="video/mp4"></video>
                            </div>
                            <div style="display: none" id="div_video_m_params" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="background_video_delay_m"><?php echo _("Video display time (seconds)"); ?> <i title="<?php echo _("set to 0 to wait for the end of the video, otherwise set the seconds for which the video should be displayed"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($virtual_tour['background_video_mobile']=='') ? 'disabled' : '' ; ?> class="form-control" type="number" min="0" id="background_video_delay_m" value="<?php echo $virtual_tour['background_video_delay_mobile'];?>" />
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_video_bg_m" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_video_bg_m();" class="btn btn-block btn-danger"><?php echo _("REMOVE VIDEO"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_video_bg_m">
                                <?php if($upload_content) : ?>
                                    <form id="frm_b_v_m" action="ajax/upload_background_video.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_b_v_m" name="txtFile_b_v_m" />
                                                        <label class="custom-file-label" for="txtFile_b_v_m"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b_v_m" value="<?php echo _("Upload Background Video"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_b_v_m mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_b_v_m" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b_v_m"></div>
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
    <div class="tab-pane <?php echo $tab; ?>" id="content_tab">
        <div class="row <?php echo ($virtual_tour['external']==1) ? 'd-block' : ''; ?>">
            <div class="<?php echo ($virtual_tour['external']==1) ? 'float-left' : ''; ?> col-md-<?php echo $col2; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Logo"); ?> <i title="<?php echo _("logo displayed on top of the tour"); ?>" class="help_t fas fa-question-circle"></i> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_logo>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_logo==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_logo==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_logo']) ? 'disabled' : '' ; ?>">
                        <div class="row">
                            <div id="div_exist_logo" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_logo"><?php echo _("Exist Logo"); ?></label>
                                    <select onchange="change_exist_logo();" class="form-control" id="exist_logo">
                                        <option selected id="0"><?php echo _("Upload new Logo"); ?></option>
                                        <?php echo get_option_exist_logo($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_logo" class="col-md-12">
                                <img style="width: 100%" src="<?php echo $url_logo; ?>" />
                            </div>
                            <div style="display: none" id="div_link_logo" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="link_logo"><?php echo _("Hyperlink"); ?></label>
                                    <input id="link_logo" type="text" class="form-control" value="<?php echo $virtual_tour['link_logo']; ?>">
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_logo" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_logo();" class="btn btn-block btn-danger"><?php echo _("REMOVE LOGO"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_logo">
                                <?php if($upload_content) : ?>
                                    <form id="frm_l" action="ajax/upload_logo_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_l" name="txtFile_l" />
                                                        <label class="custom-file-label" for="txtFile_l"><?php echo _("Choose file"); ?></label>
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
                                                    <div class="progress progress_l mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
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
            <div class="col-md-4 <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div style="padding: 10px 20px" class="card-header">
                        <h6 style="vertical-align:bottom;" class="m-0 font-weight-bold text-primary d-inline-block"><i class="far fa-registered"></i> <?php echo _("Powered By"); ?> <i title="<?php echo _("powered by - logo / text displayed on bottom of the tour"); ?>" class="help_t fas fa-question-circle"></i> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_poweredby>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_poweredby==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_poweredby==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                        <select onchange="change_poweredby_type();" id="poweredby_type" style="width:100px" class="form-control form-control-sm d-inline-block float-right">
                            <option <?php echo ($virtual_tour['poweredby_type']=='image') ? 'selected' : ''; ?> id="image"><?php echo _("Logo"); ?></option>
                            <option <?php echo ($virtual_tour['poweredby_type']=='text') ? 'selected' : ''; ?> id="text"><?php echo _("Text"); ?></option>
                        </select>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_poweredby']) ? 'disabled' : '' ; ?>">
                        <div style="display: <?php echo ($virtual_tour['poweredby_type']=='text') ? 'block' : 'none'; ?> " id="poweredby_type_text" class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="poweredby_text"><?php echo _("Text"); ?></label>
                                    <input id="poweredby_text" type="text" class="form-control" value="<?php echo $virtual_tour['poweredby_text']; ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="poweredby_link_text"><?php echo _("Hyperlink"); ?></label>
                                    <input id="poweredby_link_text" type="text" class="form-control" value="<?php echo $virtual_tour['poweredby_link']; ?>">
                                </div>
                            </div>
                        </div>
                        <div style="display: <?php echo ($virtual_tour['poweredby_type']=='image') ? 'block' : 'none'; ?> " id="poweredby_type_image" class="row">
                            <div id="div_exist_poweredby" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_poweredby"><?php echo _("Exist Logo"); ?></label>
                                    <select onchange="change_exist_poweredby();" class="form-control" id="exist_poweredby">
                                        <option selected id="0"><?php echo _("Upload new Logo"); ?></option>
                                        <?php echo get_option_exist_poweredby($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_poweredby" class="col-md-12">
                                <img style="width: 100%" src="<?php echo $url_poweredby; ?>" />
                            </div>
                            <div style="display: none" id="div_link_poweredby" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="poweredby_link_image"><?php echo _("Hyperlink"); ?></label>
                                    <input id="poweredby_link_image" type="text" class="form-control" value="<?php echo $virtual_tour['poweredby_link']; ?>">
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_poweredby" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_poweredby();" class="btn btn-block btn-danger"><?php echo _("REMOVE LOGO"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_poweredby">
                                <?php if($upload_content) : ?>
                                    <form id="frm_pw" action="ajax/upload_logo_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_pw" name="txtFile_pw" />
                                                        <label class="custom-file-label" for="txtFile_pw"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_pw" value="<?php echo _("Upload Logo Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_pw mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_pw" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_pw"></div>
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
            <div class="col-md-4 <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Nadir Logo"); ?> <i title="<?php echo _("logo used to hide tripod on panorama image"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_nadir_logo']) ? 'disabled' : '' ; ?>">
                        <div class="row">
                            <div id="div_exist_nadir_logo" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_nadir_logo"><?php echo _("Exist Nadir Logo"); ?></label>
                                    <select onchange="change_exist_nadir_logo();" class="form-control" id="exist_nadir_logo">
                                        <option selected id="0"><?php echo _("Upload new Nadir Logo"); ?></option>
                                        <?php echo get_option_exist_nadir_logo($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_nadir_logo" class="col-md-12 text-center">
                                <img style="width: 100%;max-width: 150px" src="<?php echo $url_nadir_logo; ?>" />
                            </div>
                            <div style="display: none" id="div_delete_nadir_logo" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_nadir_logo();" class="btn btn-block btn-danger"><?php echo _("REMOVE LOGO"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_nadir_logo">
                                <?php if($upload_content) : ?>
                                    <form id="frm_n" action="ajax/upload_logo_nadir_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_n" name="txtFile_n" />
                                                        <label class="custom-file-label" for="txtFile_n"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_n" value="<?php echo _("Upload Logo Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_n mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_n" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_n"></div>
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
            <div class="col-md-4 <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-music"></i> <?php echo _("Song"); ?> <i title="<?php echo _("background song during navigation of virtual tour"); ?>" class="help_t fas fa-question-circle"></i> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_audio>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_audio==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_audio==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                    </div>
                    <div class="card-body <?php echo (!$plan_permissions['enable_song']) ? 'disabled' : '' ; ?>">
                        <div id="div_exist_song" class="col-md-12">
                            <div class="form-group">
                                <label for="exist_song"><?php echo _("Exist Song"); ?></label>
                                <select onchange="change_exist_song();" class="form-control" id="exist_song">
                                    <option selected id="0"><?php echo _("Upload new Song"); ?></option>
                                    <?php echo get_option_exist_song($_SESSION['id_user'],$id_virtual_tour,null); ?>
                                </select>
                            </div>
                        </div>
                        <div style="display: none" id="div_player_song" class="col-md-12 text-center">
                            <audio controls>
                                <source src="<?php echo $url_song; ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        <div style="display: none" id="div_delete_song" class="mt-2">
                            <div class="col-md-12">
                                <button onclick="delete_song();return false;" id="btn_delete_song" class="btn btn-block btn-danger"><?php echo _("REMOVE SONG"); ?></button>
                            </div>
                        </div>
                        <div style="display: none" id="div_upload_song" class="col-md-12">
                            <?php if($upload_content) : ?>
                                <form id="frm" action="ajax/upload_song.php" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                                    <label class="custom-file-label" for="txtFile"><?php echo _("Choose file"); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Song (MP3)"); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="preview text-center">
                                                <div class="progress progress_s mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Intro (Desktop)"); ?> <i title="<?php echo _("image displayed on desktop at first load"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_introd" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_introd"><?php echo _("Exist Image"); ?></label>
                                    <select onchange="change_exist_introd();" class="form-control" id="exist_introd">
                                        <option selected id="0"><?php echo _("Upload new Image"); ?></option>
                                        <?php echo get_option_exist_introd($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_introd" class="col-md-12">
                                <img style="width: 100%" src="<?php echo $url_intro_desktop; ?>" />
                            </div>
                            <div style="display: none" id="div_hide_introd" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="intro_desktop_hide"><?php echo _("Auto hide after"); ?> <i title="<?php echo _("set to 0 to not hide it"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="intro_desktop_hide" value="<?php echo $virtual_tour['intro_desktop_hide']; ?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_introd" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_introd();" class="btn btn-block btn-danger"><?php echo _("REMOVE IMAGE"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_introd">
                                <?php if($upload_content) : ?>
                                    <form id="frm_id" action="ajax/upload_intro_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_id" name="txtFile_id" />
                                                        <label class="custom-file-label" for="txtFile_id"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_id" value="<?php echo _("Upload Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_id mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_id" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_id"></div>
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
            <div class="col-md-4 <?php echo $hide_external; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-image"></i> <?php echo _("Intro (Mobile)"); ?> <i title="<?php echo _("image displayed on mobile at first load"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div id="div_exist_introm" class="col-md-12">
                                <div class="form-group">
                                    <label for="exist_introm"><?php echo _("Exist Image"); ?></label>
                                    <select onchange="change_exist_introm();" class="form-control" id="exist_introm">
                                        <option selected id="0"><?php echo _("Upload new Image"); ?></option>
                                        <?php echo get_option_exist_introm($_SESSION['id_user'],$s3_enabled,$s3_url); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_image_introm" class="col-md-12">
                                <img style="width: 100%" src="<?php echo $url_intro_mobile; ?>" />
                            </div>
                            <div style="display: none" id="div_hide_introm" class="col-md-12 mt-2">
                                <div class="form-group">
                                    <label for="intro_mobile_hide"><?php echo _("Auto hide after"); ?> <i title="<?php echo _("set to 0 to not hide it"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <div class="input-group">
                                        <input type="number" min="0" class="form-control" id="intro_mobile_hide" value="<?php echo $virtual_tour['intro_mobile_hide']; ?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="display: none" id="div_delete_introm" class="col-md-12 mt-2">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_introm();" class="btn btn-block btn-danger"><?php echo _("REMOVE IMAGE"); ?></button>
                            </div>
                            <div style="display: none" class="col-md-12" id="div_upload_introm">
                                <?php if($upload_content) : ?>
                                    <form id="frm_im" action="ajax/upload_intro_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_im" name="txtFile_im" />
                                                        <label class="custom-file-label" for="txtFile_im"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_im" value="<?php echo _("Upload Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress progress_im mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_im" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_im"></div>
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
            <div class="col-md-12 <?php echo $hide_external; ?> <?php echo ($custom_html==0) ? 'd-none' : ''; ?>">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-html5"></i> <?php echo _("Custom HTML"); ?> <i title="<?php echo _("html code that will be displayed within the tour"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div style="width:100%;" class="form-group">
                                <div id="custom_vt_html"><?php echo htmlspecialchars(str_replace('\"','"',str_replace(array("<br />","<br>","<br/>"),"\n",$virtual_tour['custom_html']))); ?></div>
                                <div class="mt-1 text-right">
                                    <button onclick="open_modal_media_library('all','html_vt');return false;" class="btn btn-sm btn-primary"><?php echo _("Media Library"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="form_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-signature"></i> <?php echo _("Main Form"); ?> <i style="font-size:12px;vertical-align:middle;color:<?php echo ($show_in_ui_form>0)?'green':'orange'; ?>" <?php echo ($show_in_ui_form==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':''; ?> class="<?php echo ($show_in_ui_form==0)?'help_t':''; ?> show_in_ui fas fa-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px" for="form_title"><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_title'); ?>
                                    <input id="form_title" type="text" class="form-control form-control-sm" value="<?php echo $form_content[0]['title']; ?>">
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_title" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['form_content'][0]['title']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px" for="form_button"><?php echo _("Button send"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_button'); ?>
                                    <input id="form_button" type="text" class="form-control form-control-sm" value="<?php echo $form_content[0]['button']; ?>">
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_button" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['form_content'][0]['button']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px" for="form_response"><?php echo _("Reply message"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_response'); ?>
                                    <input id="form_response" type="text" class="form-control form-control-sm" value="<?php echo $form_content[0]['response']; ?>">
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_response" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['form_content'][0]['response']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px" for="form_description"><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_description'); ?>
                                    <input id="form_description" type="text" class="form-control form-control-sm" value="<?php echo $form_content[0]['description']; ?>">
                                    <?php foreach ($array_languages as $lang) {
                                        if($lang!=$default_language) : ?>
                                            <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_description" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['form_content'][0]['description']; ?>" />
                                        <?php endif;
                                    } ?>
                                </div>
                            </div>
                            <div class="col-md-4 <?php echo (empty($settings['privacy_policy'])) ? 'd-none':''; ?>">
                                <div class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Show Privacy Policy"); ?></label><br>
                                    <input <?php echo ($form_content[0]['privacy_policy'])?'checked':''; ?> id="form_privacy_policy" type="checkbox">
                                </div>
                            </div>
                            <div class="col-md-4 <?php echo (!$settings['smtp_valid']) ? 'd-none':''; ?>">
                                <div class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Send Notification"); ?></label><br>
                                    <input <?php echo ($form_content[0]['send_email'])?'checked':''; ?> id="form_send_email" type="checkbox">
                                </div>
                            </div>
                            <div class="col-md-4 <?php echo (!$settings['smtp_valid']) ? 'd-none':''; ?>">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px" for="form_email"><?php echo _("E-Mail"); ?></label>
                                    <input id="form_email" type="email" class="form-control form-control-sm" value="<?php echo $form_content[0]['email']; ?>">
                                </div>
                            </div>
                        </div>
                        <hr style="margin: 3px">
                        <div class="row">
                            <div class="col-md-3">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Background Color"); ?></label>
                                    <input id="form_background_m" type="text" class="form-control form-control-sm" value="<?php echo (isset($form_content[0]['background'])) ? $form_content[0]['background'] : 'rgba(255,255,255,1.0)'; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Text Color"); ?></label>
                                    <input id="form_color_m" type="text" class="form-control form-control-sm" value="<?php echo (isset($form_content[0]['color'])) ? $form_content[0]['color'] : '#000000'; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Button - Background Color"); ?></label>
                                    <input id="form_background_button" type="text" class="form-control form-control-sm" value="<?php echo (isset($form_content[0]['background_button'])) ? $form_content[0]['background_button'] : '#000000'; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div style="margin-bottom: 3px" class="form-group">
                                    <label style="margin-bottom: 1px"><?php echo _("Button - Text Color"); ?></label>
                                    <input id="form_color_button" type="text" class="form-control form-control-sm" value="<?php echo (isset($form_content[0]['color_button'])) ? $form_content[0]['color_button'] : '#ffffff'; ?>">
                                </div>
                            </div>
                        </div>
                        <hr style="margin: 3px">
                        <?php for($i=1;$i<=10;$i++) { ?>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="margin-bottom: 1px">F.<?php echo $i; ?> <?php echo _("Enable"); ?></label><br>
                                        <input <?php echo ($form_content[$i]['enabled'])?'checked':''; ?> id="form_field_<?php echo $i; ?>" type="checkbox">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="margin-bottom: 1px">F.<?php echo $i; ?> <?php echo _("Required"); ?></label><br>
                                        <input <?php echo ($form_content[$i]['required'])?'checked':''; ?> id="form_field_required_<?php echo $i; ?>" type="checkbox">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div style="margin-bottom: 3px" class="form-group">
                                        <label style="margin-bottom: 1px">F.<?php echo $i; ?> <?php echo _("Type"); ?></label><br>
                                        <select onchange="change_form_field_type(<?php echo $i; ?>);" id="form_field_type_<?php echo $i; ?>" class="form-control form-control-sm">
                                            <option <?php echo ($form_content[$i]['type']=='text')?'selected':''; ?> id="text" value="text"><?php echo _("Text"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='number')?'selected':''; ?> id="number" value="number"><?php echo _("Number"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='tel')?'selected':''; ?> id="tel" value="tel"><?php echo _("Phone"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='email')?'selected':''; ?> id="email" value="email"><?php echo _("E-Mail"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='select')?'selected':''; ?> id="select" value="select"><?php echo _("Select"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='checkbox')?'selected':''; ?> id="checkbox" value="checkbox"><?php echo _("Checkbox"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='date')?'selected':''; ?> id="date" value="date"><?php echo _("Date"); ?></option>
                                            <option <?php echo ($form_content[$i]['type']=='time')?'selected':''; ?> id="time" value="time"><?php echo _("Time"); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div style="margin-bottom: 3px" class="form-group">
                                        <label style="margin-bottom: 1px">F.<?php echo $i; ?> <?php echo _("Label"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'form_field_label_'.$i); ?><br>
                                        <input id="form_field_label_<?php echo $i; ?>" type="text" class="form-control form-control-sm" value="<?php echo $form_content[$i]['label']; ?>">
                                        <?php foreach ($array_languages as $lang) {
                                            if($lang!=$default_language) : ?>
                                                <input style="display:none;" type="text" class="form-control form-control-sm input_lang" data-target-id="form_field_label_<?php echo $i; ?>" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['form_content'][$i]['label']; ?>" />
                                            <?php endif;
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="hfov_tab">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-ruler-horizontal"></i> <?php echo _("Field of View"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Default"); ?> <i title="<?php echo _("sets the panorama’s starting horizontal field of view in degrees."); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input disabled type="number" min="20" max="140" class="form-control" id="hfov" value="<?php echo $virtual_tour['hfov']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Min"); ?> <i title="<?php echo _("sets the minimum pitch the viewer edge can be at, in degrees."); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input disabled type="number" min="20" max="140" class="form-control" id="min_hfov" value="<?php echo $virtual_tour['min_hfov']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Max"); ?> <i title="<?php echo _("sets the maximum pitch the viewer edge can be at, in degrees."); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input disabled type="number" min="20" max="140" class="form-control" id="max_hfov" value="<?php echo $virtual_tour['max_hfov']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hfov_mobile_ratio"><?php echo _("HFOV Mobile Ratio"); ?> (<span id="hfov_mobile_ratio_val"><?php echo $virtual_tour['hfov_mobile_ratio']; ?></span>) <i title="<?php echo _("a lower ratio indicates a wider view on the mobile, while a higher value indicates a narrower view"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_hfov_mobile_ratio();" type="range" min="0.5" max="1.5" step="0.1" class="form-control-range" id="hfov_mobile_ratio" value="<?php echo $virtual_tour['hfov_mobile_ratio']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zoom_friction"><?php echo _("Zoom Speed"); ?> (<span id="zoom_friction_val"><?php echo $virtual_tour['zoom_friction']; ?></span>) <i title="<?php echo _("controls the zoom speed. higher values mean faster zoom."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_zoom_friction();" type="range" min="0.01" max="0.2" step="0.01" class="form-control-range" id="zoom_friction" value="<?php echo $virtual_tour['zoom_friction']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zoom_friction_mobile"><?php echo _("Zoom Speed Mobile"); ?> (<span id="zoom_friction_mobile_val"><?php echo $virtual_tour['zoom_friction_mobile']; ?></span>) <i title="<?php echo _("controls the zoom speed. higher values mean faster zoom."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_zoom_friction_mobile();" type="range" min="0.01" max="0.2" step="0.01" class="form-control-range" id="zoom_friction_mobile" value="<?php echo $virtual_tour['zoom_friction_mobile']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mouse_zoom"><?php echo _("Zoom"); ?></label><br>
                                    <select id="mouse_zoom" class="form-control form-control-sm">
                                        <option <?php echo ($virtual_tour['mouse_zoom']==0) ? 'selected':''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                        <option <?php echo ($virtual_tour['mouse_zoom']==1) ? 'selected':''; ?> id="1"><?php echo _("Enabled"); ?></option>
                                        <option <?php echo ($virtual_tour['mouse_zoom']==2) ? 'selected':''; ?> id="2"><?php echo _("Enabled (Disabled when embedded)"); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-hand-point-up"></i> <?php echo _("Interaction"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pan_speed"><?php echo _("Pan Speed"); ?> (<span id="pan_speed_val"><?php echo $virtual_tour['pan_speed']; ?></span>) <i title="<?php echo _("adjusts panning speed from touch inputs: a lower value indicates a slower pan speed, while a higher value indicates a faster pan speed"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_pan_speed();" type="range" min="0.1" max="3" step="0.1" class="form-control-range" id="pan_speed" value="<?php echo $virtual_tour['pan_speed']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pan_speed_mobile"><?php echo _("Pan Speed Mobile"); ?> (<span id="pan_speed_mobile_val"><?php echo $virtual_tour['pan_speed_mobile']; ?></span>) <i title="<?php echo _("adjusts panning speed from touch inputs: a lower value indicates a slower pan speed, while a higher value indicates a faster pan speed"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_pan_speed_mobile();" type="range" min="0.1" max="3" step="0.1" class="form-control-range" id="pan_speed_mobile" value="<?php echo $virtual_tour['pan_speed_mobile']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="friction"><?php echo _("Friction"); ?> (<span id="friction_val"><?php echo $virtual_tour['friction']; ?></span>) <i title="<?php echo _("controls the friction that slows down the viewer motion after it is dragged and released. higher values mean the motion stops faster."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_friction();" type="range" min="0.1" max="1" step="0.1" class="form-control-range" id="friction" value="<?php echo $virtual_tour['friction']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="friction_mobile"><?php echo _("Friction Mobile"); ?> (<span id="friction_mobile_val"><?php echo $virtual_tour['friction_mobile']; ?></span>) <i title="<?php echo _("controls the friction that slows down the viewer motion after it is dragged and released. higher values mean the motion stops faster."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_friction_mobile();" type="range" min="0.1" max="1" step="0.1" class="form-control-range" id="friction_mobile" value="<?php echo $virtual_tour['friction_mobile']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mouse_follow_feedback"><?php echo _("Feedback Mouse Movements"); ?> (<span id="mouse_follow_feedback_val"><?php echo $virtual_tour['mouse_follow_feedback']; ?></span>) <i title="<?php echo _("sensitivity of the movement of the panorama following the mouse (0 to disable)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input oninput="change_mouse_follow_feedback();" type="range" min="0.0" max="3.0" step="0.1" class="form-control-range" id="mouse_follow_feedback" value="<?php echo $virtual_tour['mouse_follow_feedback']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zoom_to_pointer"><?php echo _("Zoom to Pointer"); ?> <i title="<?php echo _("sets the zoom center on the mouse pointer when using the scroll wheel"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input onclick="change_zoom_to_pointer();" type="checkbox" id="zoom_to_pointer" <?php echo ($virtual_tour['zoom_to_pointer']==1) ? 'checked':''; ?>>
                                </div>
                            </div>
                            <div class="col-md-6 <?php echo $hide_external; ?>">
                                <div class="form-group">
                                    <label for="initial_feedback"><?php echo _("Initial Feedback Animation"); ?> <i title="<?php echo _("an animation at the start of the tour for feedback on the dragging of the panorama (0 = disabled)."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group input-group-sm">
                                        <input type="number" min="0" class="form-control form-control-sm" id="initial_feedback" value="<?php echo $virtual_tour['initial_feedback']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">ms</span>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-binoculars"></i> <?php echo _("Preview"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 text-center">
                                <label><?php echo _("Desktop"); ?></label>
                                <div style="width:100%;max-width:622px;height:350px;margin:0 auto;" id="panorama"></div>
                                <div class="mt-2" style="width: 100%;">
                                    <?php echo _("Current HFOV"); ?> <b><span id="hvof_debug"><?php echo $virtual_tour['hfov']; ?></span></b><br>
                                    <i><?php echo _("use the mouse wheel or the controls to zoom"); ?></i>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <label><?php echo _("Mobile"); ?></label>
                                <div style="width:100%;max-width:200px;height:350px;margin:0 auto;" id="panorama_mobile"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($user_info['role']!='administrator') ? 'd-none' : ''; ?>" id="note_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-sticky-note"></i> <?php echo _("Note (only visible to administrators)"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <textarea class="form-control" id="note" rows="10"><?php echo $virtual_tour['note']; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($user_info['role']!='administrator') ? 'd-none' : ''; ?>" id="editors_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-users-cog"></i> <?php echo _("Assigned Editors (only visible to administrators)"); ?>
                            <span id="btn_unassign_all" onclick="unassign_all_editor_to_tour();" class="badge badge-danger float-right ml-2 <?php echo ($demo) ? 'disabled_d':''; ?> disabled"><?php echo _("Unassign all editors"); ?></span>
                            <span id="btn_assign_all" onclick="assign_all_editor_to_tour();" class="badge badge-primary float-right <?php echo ($demo) ? 'disabled_d':''; ?> disabled"><?php echo _("Assign all editors / permissions"); ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered table-hover" id="assign_editors_table" width="100%" cellspacing="0">
                                    <thead>
                                    <tr>
                                        <th><?php echo _("Assign"); ?></th>
                                        <th style="min-width: 350px"><?php echo _("User"); ?></th>
                                        <th><?php echo _("Edit Tour"); ?></th>
                                        <th><?php echo _("Editor UI"); ?></th>
                                        <th><?php echo _("Create Rooms"); ?></th>
                                        <th><?php echo _("Edit Rooms"); ?></th>
                                        <th><?php echo _("Delete Rooms"); ?></th>
                                        <th><?php echo _("Create Markers"); ?></th>
                                        <th><?php echo _("Edit Markers"); ?></th>
                                        <th><?php echo _("Delete Markers"); ?></th>
                                        <th><?php echo _("Create POIs"); ?></th>
                                        <th><?php echo _("Edit POIs"); ?></th>
                                        <th><?php echo _("Delete POIs"); ?></th>
                                        <th><?php echo _("Create Maps"); ?></th>
                                        <th><?php echo _("Edit Maps"); ?></th>
                                        <th><?php echo _("Delete Maps"); ?></th>
                                        <th><?php echo _("Info Box"); ?></th>
                                        <th><?php echo _("Presentation"); ?></th>
                                        <th><?php echo _("Gallery"); ?></th>
                                        <th><?php echo _("Icons Library"); ?></th>
                                        <th><?php echo _("Media Library"); ?></th>
                                        <th><?php echo _("Music Library"); ?></th>
                                        <th><?php echo _("Sound Library"); ?></th>
                                        <th><?php echo _("Publish"); ?></th>
                                        <th><?php echo _("Landing"); ?></th>
                                        <th><?php echo _("Forms"); ?></th>
                                        <th><?php echo _("Leads"); ?></th>
                                        <th><?php echo _("Shop"); ?></th>
                                        <th><?php echo _("3D View"); ?></th>
                                        <th><?php echo _("360 Video"); ?></th>
                                        <th><?php echo _("Measurements"); ?></th>
                                        <th><?php echo _("Video Projects"); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody <?php echo ($demo) ? 'style="pointer-events:none"' : ''; ?>>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($shop==0) ? 'd-none' : ''; ?>" id="shop_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-shopping-cart"></i> <?php echo _("Settings"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="shop_type"><?php echo _("Shop Type"); ?></label>
                                    <select onchange="change_shop_type();" class="form-control" id="shop_type">
                                        <option <?php echo ($virtual_tour['shop_type']=='snipcart') ? 'selected' : ''; ?> id="snipcart">Snipcart</option>
                                        <option <?php echo ($virtual_tour['shop_type']=='woocommerce') ? 'selected' : ''; ?> id="woocommerce">Woocommerce</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="woocommerce_store_url"><?php echo _("Store Url"); ?></label>
                                    <input id="woocommerce_store_url" type="text" class="form-control" value="<?php echo $virtual_tour['woocommerce_store_url']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="woocommerce_store_cart"><?php echo _("Cart Page"); ?></label>
                                    <input id="woocommerce_store_cart" type="text" class="form-control" value="<?php echo $virtual_tour['woocommerce_store_cart']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="woocommerce_store_checkout"><?php echo _("Checkout Page"); ?></label>
                                    <input id="woocommerce_store_checkout" type="text" class="form-control" value="<?php echo $virtual_tour['woocommerce_store_checkout']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="woocommerce_customer_key"><?php echo _("Customer Key"); ?></label>
                                    <input autocomplete="new-password" id="woocommerce_customer_key" type="password" class="form-control" value="<?php echo ($virtual_tour['woocommerce_customer_key']!='') ? 'keep_woocommerce_customer_key' : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                <div class="form-group">
                                    <label for="woocommerce_customer_secret"><?php echo _("Customer Secret"); ?></label>
                                    <input autocomplete="new-password" id="woocommerce_customer_secret" type="password" class="form-control" value="<?php echo ($virtual_tour['woocommerce_customer_secret']!='') ? 'keep_woocommerce_customer_secret' : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 snipcart_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label for="snipcart_api_key"><?php echo _("Public Key"); ?></label>
                                    <input autocomplete="new-password" id="snipcart_api_key" type="password" class="form-control" value="<?php echo ($virtual_tour['snipcart_api_key']!='') ? 'keep_snipcart_public_key' : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4 snipcart_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? 'd-none' : ''; ?>">
                                <div class="form-group">
                                    <label for="snipcart_currency"><?php echo _("Currency"); ?></label>
                                    <select class="form-control" id="snipcart_currency">
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='AED') ? 'selected' : ''; ?> id="AED">AED</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='ARS') ? 'selected' : ''; ?> id="ARS">ARS</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='AUD') ? 'selected' : ''; ?> id="AUD">AUD</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='BRL') ? 'selected' : ''; ?> id="BRL">BRL</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='CAD') ? 'selected' : ''; ?> id="CAD">CAD</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='CAD') ? 'selected' : ''; ?> id="CLP">CLP</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='CHF') ? 'selected' : ''; ?> id="CHF">CHF</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='CNY') ? 'selected' : ''; ?> id="CNY">CNY</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='CZK') ? 'selected' : ''; ?> id="CZK">CZK</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='EUR') ? 'selected' : ''; ?> id="EUR">EUR</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='GBP') ? 'selected' : ''; ?> id="GBP">GBP</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='HKD') ? 'selected' : ''; ?> id="HKD">HKD</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='IDR') ? 'selected' : ''; ?> id="IDR">IDR</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='ILS') ? 'selected' : ''; ?> id="ILS">ILS</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='INR') ? 'selected' : ''; ?> id="INR">INR</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='JPY') ? 'selected' : ''; ?> id="JPY">JPY</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='MXN') ? 'selected' : ''; ?> id="MXN">MXN</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='MYR') ? 'selected' : ''; ?> id="MYR">MYR</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='NGN') ? 'selected' : ''; ?> id="NGN">NGN</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='PHP') ? 'selected' : ''; ?> id="PHP">PHP</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='PYG') ? 'selected' : ''; ?> id="PYG">PYG</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='PLN') ? 'selected' : ''; ?> id="PLN">PLN</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='RUB') ? 'selected' : ''; ?> id="RUB">RUB</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='RWF') ? 'selected' : ''; ?> id="RWF">RWF</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='SEK') ? 'selected' : ''; ?> id="SEK">SEK</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='SGD') ? 'selected' : ''; ?> id="SGD">SGD</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='TJS') ? 'selected' : ''; ?> id="TJS">TJS</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='THB') ? 'selected' : ''; ?> id="THB">THB</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='TRY') ? 'selected' : ''; ?> id="TRY">TRY</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='USD') ? 'selected' : ''; ?> id="USD">USD</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='VND') ? 'selected' : ''; ?> id="VND">VND</option>
                                        <option <?php echo ($virtual_tour['snipcart_currency']=='ZAR') ? 'selected' : ''; ?> id="ZAR">ZAR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 snipcart_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? 'd-none' : ''; ?>">
                                <span class="text-primary">1) <?php echo _("Create an account on"); ?></span> <a target="_blank" href="https://app.snipcart.com/register">Snipcart <i class="fas fa-external-link-square-alt"></i></a><br>
                                <i><?php echo sprintf(_("Pay attention that you can make 2 configurations, one for <b>test</b> and one for <b>live</b>, by changing the selector on the %s dashboard at the top"),'snipcart'); ?></i><br>
                                <span class="text-primary">2) <?php echo _("Fill your business information"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/account/settings"><i class="fas fa-external-link-square-alt"></i></a><br>
                                <span class="text-primary">3) <?php echo _("Configure your domain"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/account/domains"><i class="fas fa-external-link-square-alt"></i></a><br>
                                - <?php echo sprintf(_("Add your domain <b>%s</b> in the <b>Domain</b> field of the section <b>DEFAULT WEBSITE DOMAIN</b>"),$_SERVER['SERVER_NAME']); ?><br>
                                <span class="text-primary">4) <?php echo _("Configure Regional Settings"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/settings/regional"><i class="fas fa-external-link-square-alt"></i></a><br>
                                - <?php echo _("Add all the currencies in the section <b>SUPPORTED CURRENCIES</b>"); ?><br>
                                - <?php echo _("Enable the countries they can buy on your site in the section <b>ENABLED COUNTRIES</b>"); ?><br>
                                <span class="text-primary">5) <?php echo _("Configure Taxes"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/taxes"><i class="fas fa-external-link-square-alt"></i></a><br>
                                - <?php echo _("Click on <b>Create New Tax</b> a make sure to check <b>Included in price</b>"); ?><br>
                                <span class="text-primary">6) <?php echo _("Configure Checkout & Cart"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/settings/cart-and-checkout"><i class="fas fa-external-link-square-alt"></i></a><br>
                                - <?php echo _("You can decide whether to register your customers or not by changing the option <b>Allow Guests Only</b>"); ?><br>
                                <span class="text-primary">7) <?php echo _("Connect a payment gateway"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/account/gateway"><i class="fas fa-external-link-square-alt"></i></a><br>
                                <span class="text-primary">8) <?php echo _("Get Api Key"); ?></span> <a target="_blank" href="https://app.snipcart.com/dashboard/account/credentials"><i class="fas fa-external-link-square-alt"></i></a><br>
                                - <?php echo _("Retrieve your <b>public test or live API key</b> and enter it above"); ?>
                            </div>
                            <div class="col-md-12 woocommerce_setting <?php echo ($virtual_tour['shop_type']=='woocommerce') ? '' : 'd-none'; ?>">
                                1) <?php echo sprintf(_("Install %s plugin on your woocommerce site"),'<a target="_blank" href="https://wordpress.org/plugins/cart-rest-api-for-woocommerce/">CoCart <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                                2) <?php echo _("Login into your woordpress administrator panel"); ?><br>
                                3) <?php echo _("Create a new API Key under <b>WooCommerce</b> - <b>Settings</b> - <b>Advanced</b> - <b>REST API</b>"); ?><br>
                                4) <?php echo _("Retrieve your <b>Consumer key and secret</b> and enter them above"); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_regenerate_multires" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Multi resolution regenerate"); ?></h5>
            </div>
            <div class="modal-body">
                <span style="color: green;" class="ok_msg"><?php echo _("Success. Multi resolution panoramas will be regenerated in background."); ?></span>
                <span style="color: red" class="error_msg"><?php echo _("An error has occured."); ?></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_regenerate_panoramas" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Regenerate panoramas"); ?></h5>
            </div>
            <div class="modal-body">
                <span><i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Regeneration in progress, please wait ... Do not close this window!"); ?></span>
            </div>
        </div>
    </div>
</div>

<div id="modal_add_category" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Add Category"); ?></h5>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="category_name" />
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_category();" type="button" class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
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
            <div class="modal-body">
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
        window.id_virtualtour = <?php echo $id_virtual_tour; ?>;
        var hfov = '<?php echo $virtual_tour['hfov']; ?>';
        var min_hfov = '<?php echo $virtual_tour['min_hfov']; ?>';
        var max_hfov = '<?php echo $virtual_tour['max_hfov']; ?>';
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        window.song = '<?php echo $virtual_tour['song']; ?>';
        window.logo = '<?php echo $virtual_tour['logo']; ?>';
        window.poweredby_image = '<?php echo $virtual_tour['poweredby_image']; ?>';
        window.nadir_logo = '<?php echo $virtual_tour['nadir_logo']; ?>';
        window.background_image = '<?php echo $virtual_tour['background_image']; ?>';
        window.background_video = '<?php echo $virtual_tour['background_video']; ?>';
        window.background_image_mobile = '<?php echo $virtual_tour['background_image_mobile']; ?>';
        window.background_video_mobile = '<?php echo $virtual_tour['background_video_mobile']; ?>';
        window.intro_desktop = '<?php echo $virtual_tour['intro_desktop']; ?>';
        window.intro_mobile = '<?php echo $virtual_tour['intro_mobile']; ?>';
        window.hfov_mobile_ratio = <?php echo $virtual_tour['hfov_mobile_ratio']; ?>;
        window.pan_speed = <?php echo $virtual_tour['pan_speed']; ?>;
        window.pan_speed_mobile = <?php echo $virtual_tour['pan_speed_mobile']; ?>;
        window.friction = <?php echo $virtual_tour['friction']; ?>;
        window.friction_mobile = <?php echo $virtual_tour['friction_mobile']; ?>;
        window.zoom_friction = <?php echo $virtual_tour['zoom_friction']; ?>;
        window.zoom_friction_mobile = <?php echo $virtual_tour['zoom_friction_mobile']; ?>;
        window.zoom_to_pointer = <?php echo $virtual_tour['zoom_to_pointer']; ?>;
        window.first_panorama_image = '<?php echo $first_panorama_image; ?>';
        window.first_panorama_multires = <?php echo $first_panorama['multires']; ?>;
        window.first_panorama_multires_config = '<?php echo $first_panorama['multires_config']; ?>';
        window.custom_vt_html = null;
        var viewer = null;
        var viewer_mobile = null;
        var ratio_hfov = 1;
        var viewer_initialized = false, viewer_mobile_initialized = false;
        window.vt_need_save = false;
        window.external = <?php echo $virtual_tour['external']; ?>;
        window.multires = '<?php echo $settings['multires']; ?>';
        window.context_info_editor = null;
        window.mouse_follow_feedback = <?php echo $virtual_tour['mouse_follow_feedback']; ?>;
        var current_viewer;
        var current_viewer_pitch=0, current_viewer_yaw=0, x_mouseup=0, y_mouseup=0;
        var viewer_mov_follow_mouse = true, viewer_mov_pos_change = false, timeout_mov_follow_mouse, timeout_clear_mov_pos_change;
        var table_editors = null;
        window.loading_background_color_spectrum = null;
        window.loading_text_color_spectrum = null;
        window.form_background_spectrum = null;
        window.form_color_spectrum = null;
        window.form_background_button_spectrum = null;
        window.form_color_button_spectrum = null;
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
        Quill.register(SizeStyle,true);
        var LinkFormats = Quill.import("formats/link");
        Quill.register(LinkFormats,true);
        $(document).ready(function () {
            window.loading_background_color_spectrum = $('#loading_background_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false
            });
            window.loading_text_color_spectrum = $('#loading_text_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false
            });
            window.form_background_spectrum = $('#form_background_m').spectrum({
                type: "text",
                preferredFormat: "rgb",
                showAlpha: true,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#modal_main_form'
            });
            window.form_color_spectrum = $('#form_color_m').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#modal_main_form'
            });
            window.form_background_button_spectrum = $('#form_background_button').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#modal_main_form'
            });
            window.form_color_button_spectrum = $('#form_color_button').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
                appendTo: '#modal_main_form'
            });
            window.custom_vt_html = ace.edit('custom_vt_html');
            window.custom_vt_html.session.setMode("ace/mode/html");
            window.custom_vt_html.setOption('enableLiveAutocompletion',true);
            window.custom_vt_html.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                window.custom_vt_html.setTheme("ace/theme/one_dark");
            }
            bsCustomFileInput.init();
            $('.help_t').tooltip();
            if(logo=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
                $('#div_exist_logo').show();
                $('#div_link_logo').hide();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
                $('#div_exist_logo').hide();
                $('#div_link_logo').show();
            }
            if(poweredby_image=='') {
                $('#div_delete_poweredby').hide();
                $('#div_image_poweredby').hide();
                $('#div_upload_poweredby').show();
                $('#div_exist_poweredby').show();
                $('#div_link_poweredby').hide();
            } else {
                $('#div_delete_poweredby').show();
                $('#div_image_poweredby').show();
                $('#div_upload_poweredby').hide();
                $('#div_exist_poweredby').hide();
                $('#div_link_poweredby').show();
            }
            if(nadir_logo=='') {
                $('#div_delete_nadir_logo').hide();
                $('#div_image_nadir_logo').hide();
                $('#div_upload_nadir_logo').show();
                $('#div_exist_nadir_logo').show();
            } else {
                $('#div_delete_nadir_logo').show();
                $('#div_image_nadir_logo').show();
                $('#div_upload_nadir_logo').hide();
                $('#div_exist_nadir_logo').hide();
            }
            if(background_image=='') {
                $('#div_delete_bg').hide();
                $('#div_image_bg').hide();
                $('#div_upload_bg').show();
                $('#div_exist_bg').show();
            } else {
                $('#div_delete_bg').show();
                $('#div_image_bg').show();
                $('#div_upload_bg').hide();
                $('#div_exist_bg').hide();
            }
            if(background_image_mobile=='') {
                $('#div_delete_bg_m').hide();
                $('#div_image_bg_m').hide();
                $('#div_upload_bg_m').show();
                $('#div_exist_bg_m').show();
            } else {
                $('#div_delete_bg_m').show();
                $('#div_image_bg_m').show();
                $('#div_upload_bg_m').hide();
                $('#div_exist_bg_m').hide();
            }
            if(background_video=='') {
                $('#div_delete_video_bg').hide();
                $('#div_video_bg').hide();
                $('#div_video_params').hide();
                $('#div_upload_video_bg').show();
                $('#div_exist_video_bg').show();
            } else {
                $('#div_delete_video_bg').show();
                $('#div_video_bg').show();
                $('#div_video_params').show();
                $('#div_upload_video_bg').hide();
                $('#div_exist_video_bg').hide();
            }
            if(background_video_mobile=='') {
                $('#div_delete_video_bg_m').hide();
                $('#div_video_bg_m').hide();
                $('#div_video_m_params').hide();
                $('#div_upload_video_bg_m').show();
                $('#div_exist_video_bg_m').show();
            } else {
                $('#div_delete_video_bg_m').show();
                $('#div_video_bg_m').show();
                $('#div_video_m_params').show();
                $('#div_upload_video_bg_m').hide();
                $('#div_exist_video_bg_m').hide();
            }
            if(song=='') {
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
            if(intro_desktop=='') {
                $('#div_delete_introd').hide();
                $('#div_image_introd').hide();
                $('#div_upload_introd').show();
                $('#div_exist_introd').show();
                $('#div_hide_introd').hide();
            } else {
                $('#div_delete_introd').show();
                $('#div_image_introd').show();
                $('#div_upload_introd').hide();
                $('#div_exist_introd').hide();
                $('#div_hide_introd').show();
            }
            if(intro_mobile=='') {
                $('#div_delete_introm').hide();
                $('#div_image_introm').hide();
                $('#div_upload_introm').show();
                $('#div_exist_introm').show();
                $('#div_hide_introm').hide();
            } else {
                $('#div_delete_introm').show();
                $('#div_image_introm').show();
                $('#div_upload_introm').hide();
                $('#div_exist_introm').hide();
                $('#div_hide_introm').show();
            }
            $('#exist_bg').selectator({
                useSearch: false
            });
            $('#exist_video_bg').selectator({
                useSearch: false
            });
            $('#exist_bg_m').selectator({
                useSearch: false
            });
            $('#exist_video_bg_m').selectator({
                useSearch: false
            });
            $('#exist_logo').selectator({
                useSearch: false
            });
            $('#exist_nadir_logo').selectator({
                useSearch: false
            });
            $('#exist_introd').selectator({
                useSearch: false
            });
            $('#exist_introm').selectator({
                useSearch: false
            });
            $('#exist_song').selectator({
                useSearch: false
            });
            var toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],['link'],
                ['clean']
            ];
            window.context_info_editor = new Quill('#context_info', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow'
            });
            const input1 = document.getElementById('poweredby_link_image');
            const input2 = document.getElementById('poweredby_link_text');
            input1.addEventListener('input', function() {
                input2.value = input1.value;
            });
            input2.addEventListener('input', function() {
                input1.value = input2.value;
            });
        });

        window.click_editors = function() {
            if(table_editors==null) {
                table_editors = $('#assign_editors_table').DataTable({
                    "order": [[ 1, "asc" ]],
                    "responsive": true,
                    "scrollX": true,
                    "processing": true,
                    "searching": true,
                    "serverSide": true,
                    "ajax": {
                        url: "ajax/get_assigned_editors.php",
                        type: "POST",
                        data: {
                            id_virtualtour: window.id_virtualtour
                        }
                    },
                    "drawCallback": function() {
                        $('#assign_editors_table').DataTable().columns.adjust();
                        $('.assigned_user').change(function() {
                            var checked = this.checked;
                            if(checked) checked=1; else checked=0;
                            var id_user = $(this).attr('id');
                            assign_user_editor(id_user,checked);
                            $('.assigned_user').each(function () {
                                var checked = this.checked;
                                var id_user = $(this).attr('id');
                                if(checked) {
                                    $('.editor_permissions[id='+id_user+']').prop('disabled',false);
                                } else {
                                    $('.editor_permissions[id='+id_user+']').prop('disabled',true);
                                }
                            });
                        });
                        $('.editor_permissions').change(function() {
                            var checked = this.checked;
                            if(checked) checked=1; else checked=0;
                            var id_user = $(this).attr('id');
                            var field = $(this).attr('class');
                            field = field.replace('editor_permissions ','');
                            set_permission_user_editor(id_user,field,checked);
                        });
                        $('#assign_editors_table tr').on('click',function () {
                            $('#assign_editors_table tr').removeClass('highlight');
                            $(this).addClass('highlight');
                        });
                        $('.assigned_user').each(function () {
                            var checked = this.checked;
                            var id_user = $(this).attr('id');
                            if(checked) {
                                $('.editor_permissions[id='+id_user+']').prop('disabled',false);
                            } else {
                                $('.editor_permissions[id='+id_user+']').prop('disabled',true);
                            }
                        });
                        $('#btn_assign_all').removeClass('disabled');
                        $('#btn_unassign_all').removeClass('disabled');
                        setTimeout(function() {
                            $(window).trigger('resize');
                        },250);
                    },
                    "language": {
                        "decimal":        "",
                        "emptyTable":     "<?php echo _("No data available in table"); ?>",
                        "info":           "<?php echo sprintf(_("Showing %s to %s of %s entries"),'_START_','_END_','_TOTAL_'); ?>",
                        "infoEmpty":      "<?php echo _("Showing 0 to 0 of 0 entries"); ?>",
                        "infoFiltered":   "<?php echo sprintf(_("(filtered from %s total entries)"),'_MAX_'); ?>",
                        "infoPostFix":    "",
                        "thousands":      ",",
                        "lengthMenu":     "<?php echo sprintf(_("Show %s entries"),'_MENU_'); ?>",
                        "loadingRecords": "<?php echo _("Loading"); ?>...",
                        "processing":     "<?php echo _("Processing"); ?>...",
                        "search":         "<?php echo _("Search"); ?>:",
                        "zeroRecords":    "<?php echo _("No matching records found"); ?>",
                        "paginate": {
                            "first":      "<?php echo _("First"); ?>",
                            "last":       "<?php echo _("Last"); ?>",
                            "next":       "<?php echo _("Next"); ?>",
                            "previous":   "<?php echo _("Previous"); ?>"
                        },
                        "aria": {
                            "sortAscending":  ": <?php echo _("activate to sort column ascending"); ?>",
                            "sortDescending": ": <?php echo _("activate to sort column descending"); ?>"
                        }
                    }
                });
            } else {
                setTimeout(function() {
                    table_editors.ajax.reload();
                    $(window).trigger('resize');
                },250);
            }
        }

        window.initialize_hfov = function() {
            if(viewer==null) {
                $('#hfov_tab').css('opacity',0);
                $('#hfov_tab').show();
                if(window.first_panorama_image=='') {
                    var panorama_image = "img/test.jpg";
                } else {
                    if(window.s3_enabled==1) {
                        var panorama_image = window.s3_url+"viewer/panoramas/"+window.first_panorama_image+'?s3=1';
                    } else {
                        var panorama_image = "../viewer/panoramas/"+window.first_panorama_image;
                    }
                }
                var multires = parseInt(window.first_panorama_multires);
                if(multires) {
                    var multires_config = JSON.parse(window.first_panorama_multires_config);
                    viewer = pannellum.viewer('panorama', {
                        "type": "multires",
                        "multiRes": multires_config,
                        "multiResMinHfov": true,
                        "backgroundColor": [1,1,1],
                        "autoLoad": true,
                        "showFullscreenCtrl": false,
                        "showControls": true,
                        "hfov": parseInt(hfov),
                        "minHfov": parseInt(min_hfov),
                        "maxHfov": parseInt(max_hfov),
                        "friction": window.friction,
                        "zoom_friction": window.zoom_friction,
                        "touchPanSpeedCoeffFactor": window.pan_speed,
                        "strings": {
                            "loadingLabel": "<?php echo _("Loading"); ?>...",
                        },
                    });
                    setTimeout(function () {
                        viewer_initialized = true;
                        $('#hfov').prop("disabled",false);
                        $('#min_hfov').prop("disabled",false);
                        $('#max_hfov').prop("disabled",false);
                        var hfov = parseInt($('#hfov').val());
                        viewer.setHfov(hfov,false);
                        adjust_ratio_hfov_vt();
                        $('#hfov_tab').css('opacity',1);
                        $('#hfov_tab').hide();
                        var hfov = viewer.getHfov();
                        var hfov_t = hfov * ratio_hfov;
                        hfov_t = Math.round(hfov_t);
                        $('#hvof_debug').html(hfov_t);
                        register_viewer_listeners(viewer);
                    },200);
                } else {
                    viewer = pannellum.viewer('panorama', {
                        "type": "equirectangular",
                        "panorama": panorama_image,
                        "autoLoad": true,
                        "showFullscreenCtrl": false,
                        "showControls": true,
                        "hfov": parseInt(hfov),
                        "minHfov": parseInt(min_hfov),
                        "maxHfov": parseInt(max_hfov),
                        "friction": window.friction,
                        "zoom_friction": window.zoom_friction,
                        "touchPanSpeedCoeffFactor": window.pan_speed,
                        "strings": {
                            "loadingLabel": "<?php echo _("Loading"); ?>...",
                        },
                    });
                    viewer.on('load', function () {
                        viewer_initialized = true;
                        $('#hfov').prop("disabled",false);
                        $('#min_hfov').prop("disabled",false);
                        $('#max_hfov').prop("disabled",false);
                        var hfov = parseInt($('#hfov').val());
                        viewer.setHfov(hfov,false);
                        adjust_ratio_hfov_vt();
                        $('#hfov_tab').css('opacity',1);
                        $('#hfov_tab').hide();
                        var hfov = viewer.getHfov();
                        var hfov_t = hfov * ratio_hfov;
                        hfov_t = Math.round(hfov_t);
                        $('#hvof_debug').html(hfov_t);
                        register_viewer_listeners(viewer);
                    });
                }
                viewer.on('zoomchange', function () {
                    var hfov = viewer.getHfov();
                    var hfov_t = hfov;
                    hfov_t = Math.round(hfov_t);
                    $('#hvof_debug').html(hfov_t);
                    var c_hfov = parseInt($('#hfov').val());
                    var c_min_hfov = parseInt($('#min_hfov').val());
                    var c_max_hfov = parseInt($('#max_hfov').val());
                    if(c_hfov==hfov_t) {
                        $('#hfov').addClass("input-highlight");
                    } else {
                        $('#hfov').removeClass("input-highlight");
                    }
                    if(c_min_hfov==hfov_t) {
                        $('#min_hfov').addClass("input-highlight");
                        $("#min_hfov").blur();
                    } else {
                        $('#min_hfov').removeClass("input-highlight");
                    }
                    if(c_max_hfov==hfov_t) {
                        $('#max_hfov').addClass("input-highlight");
                        $("#max_hfov").blur();
                    } else {
                        $('#max_hfov').removeClass("input-highlight");
                    }
                });
                if(multires) {
                    var multires_config = JSON.parse(window.first_panorama_multires_config);
                    viewer_mobile = pannellum.viewer('panorama_mobile', {
                        "type": "multires",
                        "multiRes": multires_config,
                        "multiResMinHfov": true,
                        "backgroundColor": [1, 1, 1],
                        "autoLoad": true,
                        "showFullscreenCtrl": false,
                        "showControls": true,
                        "hfov": parseInt(hfov),
                        "minHfov": parseInt(min_hfov),
                        "maxHfov": parseInt(max_hfov),
                        "friction": window.friction,
                        "zoom_friction": window.zoom_friction,
                        "touchPanSpeedCoeffFactor": window.pan_speed,
                        "strings": {
                            "loadingLabel": "<?php echo _("Loading"); ?>...",
                        },
                    });
                    setTimeout(function () {
                        viewer_mobile_initialized = true;
                        adjust_ratio_hfov_vt_mobile();
                    },200);
                } else {
                    viewer_mobile = pannellum.viewer('panorama_mobile', {
                        "type": "equirectangular",
                        "panorama": panorama_image,
                        "autoLoad": true,
                        "showFullscreenCtrl": false,
                        "showControls": true,
                        "hfov": parseInt(hfov),
                        "minHfov": parseInt(min_hfov),
                        "maxHfov": parseInt(max_hfov),
                        "friction": window.friction_mobile,
                        "zoom_friction": window.zoom_friction_mobile,
                        "touchPanSpeedCoeffFactor": window.pan_speed_mobile,
                        "strings": {
                            "loadingLabel": "<?php echo _("Loading"); ?>...",
                        },
                    });
                    viewer_mobile.on('load', function () {
                        viewer_mobile_initialized = true;
                        adjust_ratio_hfov_vt_mobile();
                    });
                }
            }
        }

        function register_viewer_listeners(viewer) {
            current_viewer=viewer;
            setTimeout(function() {
                document.getElementById('panorama').removeEventListener('mousemove', mouse_move_el);
                document.getElementById('panorama').addEventListener('mousemove', mouse_move_el);
                current_viewer_pitch = parseFloat(viewer.getPitch());
                current_viewer_yaw = parseFloat(viewer.getYaw());
                viewer.on('mousedown',function() {
                    viewer_mov_follow_mouse = false;
                    viewer_mov_pos_change = false;
                });
                viewer.on('animatefinished', function(event) {
                    if(!viewer_mov_pos_change) {
                        clearTimeout(timeout_mov_follow_mouse);
                        current_viewer_pitch = parseFloat(event.pitch);
                        current_viewer_yaw = parseFloat(event.yaw);
                        timeout_mov_follow_mouse = setTimeout(function() {
                            if(mouse_follow_feedback!=0) {
                                viewer_mov_follow_mouse = true;
                            }
                        },20);
                    }
                });
                viewer.on('mouseup', viewer_click_listener);
            },250);
        }

        function viewer_click_listener(event) {
            x_mouseup = event.x;
            y_mouseup = event.y;
            current_viewer_pitch = parseFloat(current_viewer.getPitch());
            current_viewer_yaw = parseFloat(current_viewer.getYaw());
        }

        function mouse_move_el(event) {
            if(viewer_mov_follow_mouse) viewer_move_pos_listener(event);
        }

        function viewer_move_pos_listener(event) {
            var hfov = parseFloat(current_viewer.getHfov());
            var w = document.getElementById('panorama').offsetWidth;
            var h = document.getElementById('panorama').offsetHeight;
            var x = event.x;
            var y = event.y;
            if(x_mouseup==0) x_mouseup = x;
            if(y_mouseup==0) y_mouseup = y;
            var x_c = ((x-x_mouseup)/(w/2))*mouse_follow_feedback*(hfov/100);
            var y_c = ((y-y_mouseup)/(h/2))*mouse_follow_feedback*(hfov/100);
            var look_yaw = current_viewer_yaw+(x_c);
            var look_pitch = current_viewer_pitch-(y_c);
            viewer_mov_pos_change = true;
            clearTimeout(timeout_clear_mov_pos_change);
            current_viewer.lookAt(look_pitch,look_yaw,hfov,false,function() {
                timeout_clear_mov_pos_change = setTimeout(function() {
                    viewer_mov_pos_change = false;
                },100);
            });
        }

        $('#hfov,#min_hfov,#max_hfov').on('input',function (event) {
            window.vt_need_save = true;
            var hfov = parseInt($('#hfov').val());
            var min_hfov = parseInt($('#min_hfov').val());
            var max_hfov = parseInt($('#max_hfov').val());
            if(hfov<min_hfov) {
                hfov = min_hfov;
                $('#hfov').val(hfov);
            }
            if(hfov>max_hfov) {
                hfov = max_hfov;
                $('#hfov').val(hfov);
            }
            if(min_hfov<20) {
                min_hfov=20;
                $('#min_hfov').val(min_hfov);
            }
            if(max_hfov>140) {
                max_hfov=140;
                $('#max_hfov').val(max_hfov);
            }
            viewer.setHfovBounds([min_hfov,max_hfov]);
            viewer_mobile.setHfovBounds([min_hfov,max_hfov]);
            switch(event.currentTarget.id) {
                case 'hfov':
                    viewer.setHfov(hfov,false);
                    viewer_mobile.setHfov(hfov,false);
                    break;
                case 'min_hfov':
                    viewer.setHfov(min_hfov,false);
                    viewer_mobile.setHfov(min_hfov,false);
                    break;
                case 'max_hfov':
                    viewer.setHfov(max_hfov,false);
                    viewer_mobile.setHfov(max_hfov,false);
                    break;
            }
            adjust_ratio_hfov_vt();
            adjust_ratio_hfov_vt_mobile();
        });

        function adjust_ratio_hfov_vt() {
            var c_w = parseFloat($('#panorama').css('width').replace('px',''));
            var c_h = parseFloat($('#panorama').css('height').replace('px',''));
            var ratio_panorama = c_w / c_h;
            ratio_hfov = 1.7771428571428571 / ratio_panorama;
            var hfov = parseInt($('#hfov').val());
            var min_hfov = parseInt($('#min_hfov').val());
            var max_hfov = parseInt($('#max_hfov').val());
            min_hfov = min_hfov / ratio_hfov;
            max_hfov = max_hfov / ratio_hfov;
            hfov = hfov / ratio_hfov;
            viewer.setHfovBounds([min_hfov,max_hfov]);
            viewer.setHfov(hfov,false);
        }

        function adjust_ratio_hfov_vt_mobile() {
            var c_w = parseFloat($('#panorama_mobile').css('width').replace('px',''));
            var c_h = parseFloat($('#panorama_mobile').css('height').replace('px',''));
            var ratio_panorama = c_w / c_h;
            ratio_hfov = window.hfov_mobile_ratio / ratio_panorama;
            var hfov = parseInt($('#hfov').val());
            var min_hfov = parseInt($('#min_hfov').val());
            var max_hfov = parseInt($('#max_hfov').val());
            min_hfov = min_hfov / ratio_hfov;
            max_hfov = max_hfov / ratio_hfov;
            hfov = hfov / ratio_hfov;
            viewer_mobile.setHfovBounds([min_hfov,max_hfov]);
            viewer_mobile.setHfov(hfov,false);
        }

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
                update_progressbar(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.song = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_player_song audio').attr('src',window.s3_url+'viewer/content/'+window.song);
                        } else {
                            $('#div_player_song audio').attr('src','../viewer/content/'+window.song);
                        }
                        $('#div_delete_song').show();
                        $('#div_player_song').show();
                        $('#div_upload_song').hide();
                        $('#div_exist_song').hide();
                    }
                }
                update_progressbar(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error('upload failed');
                update_progressbar(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error('upload aborted');
                update_progressbar(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar(value){
            $('#progressBar').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_s').hide();
            }else{
                $('.progress_s').show();
            }
        }

        function show_error(error){
            $('.progress_s').hide();
            $('#error').show();
            $('#error').html(error);
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
                update_progressbar_l(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_l(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.logo = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_logo img').attr('src',window.s3_url+'viewer/content/'+window.logo);
                        } else {
                            $('#div_image_logo img').attr('src','../viewer/content/'+window.logo);
                        }
                        $('#div_delete_logo').show();
                        $('#div_image_logo').show();
                        $('#div_link_logo').show();
                        $('#div_upload_logo').hide();
                        $('#div_exist_logo').hide();
                    }
                }
                update_progressbar_l(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_l('upload failed');
                update_progressbar_l(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_l('upload aborted');
                update_progressbar_l(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_l(value){
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
        }

        $('body').on('submit','#frm_pw',function(e){
            e.preventDefault();
            $('#error_pw').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_pw[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_pw' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_pw(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_pw(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.poweredby_image = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_poweredby img').attr('src',window.s3_url+'viewer/content/'+window.poweredby_image);
                        } else {
                            $('#div_image_poweredby img').attr('src','../viewer/content/'+window.poweredby_image);
                        }
                        $('#div_delete_poweredby').show();
                        $('#div_image_poweredby').show();
                        $('#div_link_poweredby').show();
                        $('#div_upload_poweredby').hide();
                        $('#div_exist_poweredby').hide();
                    }
                }
                update_progressbar_pw(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_pw('upload failed');
                update_progressbar_pw(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_pw('upload aborted');
                update_progressbar_pw(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_pw(value){
            $('#progressBar_pw').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_pw').hide();
            }else{
                $('.progress_pw').show();
            }
        }

        function show_error_pw(error){
            $('.progress_pw').hide();
            $('#error_pw').show();
            $('#error_pw').html(error);
        }

        $('body').on('submit','#frm_n',function(e){
            e.preventDefault();
            $('#error_n').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_n[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_n' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_n(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_n(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.nadir_logo = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_nadir_logo img').attr('src',window.s3_url+'viewer/content/'+window.nadir_logo);
                        } else {
                            $('#div_image_nadir_logo img').attr('src','../viewer/content/'+window.nadir_logo);
                        }
                        $('#div_delete_nadir_logo').show();
                        $('#div_image_nadir_logo').show();
                        $('#div_upload_nadir_logo').hide();
                        $('#div_exist_nadir_logo').hide();
                    }
                }
                update_progressbar_n(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_n('upload failed');
                update_progressbar_n(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_n('upload aborted');
                update_progressbar_n(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_n(value){
            $('#progressBar_n').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_n').hide();
            }else{
                $('.progress_n').show();
            }
        }

        function show_error_n(error){
            $('.progress_n').hide();
            $('#error_n').show();
            $('#error_n').html(error);
        }

        $('body').on('submit','#frm_b',function(e){
            e.preventDefault();
            $('#error_b').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_b[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_b' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_b(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.background_image = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_bg img').attr('src',window.s3_url+'viewer/content/'+window.background_image);
                        } else {
                            $('#div_image_bg img').attr('src','../viewer/content/'+window.background_image);
                        }
                        $('#div_delete_bg').show();
                        $('#div_image_bg').show();
                        $('#div_upload_bg').hide();
                        $('#div_exist_bg').hide();
                    }
                }
                update_progressbar_b(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b('upload failed');
                update_progressbar_b(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b('upload aborted');
                update_progressbar_b(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_b(value){
            $('#progressBar_b').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_b').hide();
            }else{
                $('.progress_b').show();
            }
        }

        function show_error_b(error){
            $('.progress_b').hide();
            $('#error_b').show();
            $('#error_b').html(error);
        }

        $('body').on('submit','#frm_b_m',function(e){
            e.preventDefault();
            $('#error_b_m').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_b_m[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_b_m' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_b_m(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b_m(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.background_image_mobile = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_bg_m img').attr('src',window.s3_url+'viewer/content/'+window.background_image_mobile);
                        } else {
                            $('#div_image_bg_m img').attr('src','../viewer/content/'+window.background_image_mobile);
                        }
                        $('#div_delete_bg_m').show();
                        $('#div_image_bg_m').show();
                        $('#div_upload_bg_m').hide();
                        $('#div_exist_bg_m').hide();
                    }
                }
                update_progressbar_b_m(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b_m('upload failed');
                update_progressbar_b_m(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b_m('upload aborted');
                update_progressbar_b_m(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_b_m(value){
            $('#progressBar_b_m').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_b_m').hide();
            }else{
                $('.progress_b_m').show();
            }
        }

        function show_error_b_m(error){
            $('.progress_b_m').hide();
            $('#error_b_m').show();
            $('#error_b_m').html(error);
        }

        $('body').on('submit','#frm_b_v',function(e){
            e.preventDefault();
            $('#error_b_v').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_b_v[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_b_v' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_b_v(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b_v(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.background_video = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_video_bg video source').attr('src',window.s3_url+'viewer/content/'+window.background_video+'#t=2');
                        } else {
                            $('#div_video_bg video source').attr('src','../viewer/content/'+window.background_video+'#t=2');
                        }
                        $('#div_video_bg video').get(0).load();
                        $('#div_delete_video_bg').show();
                        $('#div_video_bg').show();
                        $('#div_video_params').show();
                        $('#div_upload_video_bg').hide();
                        $('#div_exist_video_bg').hide();
                        $('#background_video_delay').prop('disabled',false);
                    }
                }
                update_progressbar_b_v(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b_v('upload failed');
                update_progressbar_b_v(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b_v('upload aborted');
                update_progressbar_b_v(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_b_v(value){
            $('#progressBar_b_v').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_b_v').hide();
            }else{
                $('.progress_b_v').show();
            }
        }

        function show_error_b_v(error){
            $('.progress_b_v').hide();
            $('#error_b_v').show();
            $('#error_b_v').html(error);
        }

        $('body').on('submit','#frm_b_v_m',function(e){
            e.preventDefault();
            $('#error_b_v_m').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_b_v_m[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_b_v_m' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_b_v_m(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b_v_m(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.background_video_mobile = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_video_bg_m video source').attr('src',window.s3_url+'viewer/content/'+window.background_video_mobile+'#t=2');
                        } else {
                            $('#div_video_bg_m video source').attr('src','../viewer/content/'+window.background_video_mobile+'#t=2');
                        }
                        $('#div_video_bg_m video').get(0).load();
                        $('#div_delete_video_bg_m').show();
                        $('#div_video_bg_m').show();
                        $('#div_video_m_params').show();
                        $('#div_upload_video_bg_m').hide();
                        $('#div_exist_video_bg_m').hide();
                        $('#background_video_delay_m').prop('disabled',false);
                    }
                }
                update_progressbar_b_v_m(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b_v_m('upload failed');
                update_progressbar_b_v_m(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b_v_m('upload aborted');
                update_progressbar_b_v_m(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_b_v_m(value){
            $('#progressBar_b_v_m').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_b_v_m').hide();
            }else{
                $('.progress_b_v_m').show();
            }
        }

        function show_error_b_v_m(error){
            $('.progress_b_v_m').hide();
            $('#error_b_v_m').show();
            $('#error_b_v_m').html(error);
        }

        $('body').on('submit','#frm_id',function(e){
            e.preventDefault();
            $('#error_id').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_id[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_id' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                update_progressbar_id(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_id(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.intro_desktop = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_introd img').attr('src',window.s3_url+'viewer/content/'+window.intro_desktop);
                        } else {
                            $('#div_image_introd img').attr('src','../viewer/content/'+window.intro_desktop);
                        }
                        $('#div_delete_introd').show();
                        $('#div_image_introd').show();
                        $('#div_upload_introd').hide();
                        $('#div_exist_introd').hide();
                    }
                }
                update_progressbar_id(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_id('upload failed');
                update_progressbar_id(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_id('upload aborted');
                update_progressbar_id(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_id(value){
            $('#progressBar_id').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_id').hide();
            }else{
                $('.progress_id').show();
            }
        }

        function show_error_id(error){
            $('.progress_id').hide();
            $('#error_id').show();
            $('#error_id').html(error);
        }

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
                update_progressbar_im(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_im(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.vt_need_save = true;
                        window.intro_mobile = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_introm img').attr('src',window.s3_url+'viewer/content/'+window.intro_mobile);
                        } else {
                            $('#div_image_introm img').attr('src','../viewer/content/'+window.intro_mobile);
                        }
                        $('#div_delete_introm').show();
                        $('#div_image_introm').show();
                        $('#div_upload_introm').hide();
                        $('#div_exist_introm').hide();
                    }
                }
                update_progressbar_im(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_im('upload failed');
                update_progressbar_im(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_im('upload aborted');
                update_progressbar_im(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function update_progressbar_im(value){
            $('#progressBar_im').css('width',value+'%').html(value+'%');
            if(value==0){
                $('.progress_im').hide();
            }else{
                $('.progress_im').show();
            }
        }

        function show_error_im(error){
            $('.progress_im').hide();
            $('#error_im').show();
            $('#error_im').html(error);
        }

        $(window).resize(function() {
            if(viewer_initialized) {
                adjust_ratio_hfov_vt();
            }
            if(viewer_mobile_initialized) {
                adjust_ratio_hfov_vt_mobile();
            }
        });

        $("input").change(function(){
            window.vt_need_save = true;
        });

        $("select").change(function(){
            window.vt_need_save = true;
        });

        $(window).on('beforeunload', function(){
            if(window.vt_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });

        window.change_hfov_mobile_ratio = function() {
            var hfov_mobile_ratio = $('#hfov_mobile_ratio').val();
            $('#hfov_mobile_ratio_val').html(hfov_mobile_ratio);
            window.hfov_mobile_ratio = parseFloat(hfov_mobile_ratio);
            adjust_ratio_hfov_vt_mobile();
        }

        window.change_pan_speed = function () {
            var pan_speed = $('#pan_speed').val();
            $('#pan_speed_val').html(pan_speed);
            viewer.setTouchPanSpeedCoeffFactor(parseFloat(pan_speed));
        }

        window.change_pan_speed_mobile = function () {
            var pan_speed_mobile = $('#pan_speed_mobile').val();
            $('#pan_speed_mobile_val').html(pan_speed_mobile);
            viewer_mobile.setTouchPanSpeedCoeffFactor(parseFloat(pan_speed_mobile));
        }

        window.change_friction = function () {
            var friction = $('#friction').val();
            $('#friction_val').html(friction);
            viewer.setFriction(parseFloat(friction));
        }

        window.change_friction_mobile = function () {
            var friction_mobile = $('#friction_mobile').val();
            $('#friction_mobile_val').html(friction_mobile);
            viewer_mobile.setFriction(parseFloat(friction_mobile));
        }

        window.change_zoom_friction = function () {
            var friction = $('#zoom_friction').val();
            $('#zoom_friction_val').html(friction);
            viewer.setZoomFriction(parseFloat(friction));
        }

        window.change_zoom_friction_mobile = function () {
            var friction_mobile = $('#zoom_friction_mobile').val();
            $('#zoom_friction_mobile_val').html(friction_mobile);
            viewer_mobile.setZoomFriction(parseFloat(friction_mobile));
        }

        window.change_mouse_follow_feedback = function () {
            var mouse_follow_feedback = $('#mouse_follow_feedback').val();
            $('#mouse_follow_feedback_val').html(mouse_follow_feedback);
            window.mouse_follow_feedback = mouse_follow_feedback;
        }

        window.change_zoom_to_pointer = function () {
            window.zoom_to_pointer = $('#zoom_to_pointer').is(':checked');
        }
    })(jQuery); // End of use strict

    function change_click_anywhere() {
        if($('#click_anywhere').is(':checked')) {
            $('#hide_markers').prop('disabled',false);
            if($('#hide_markers').is(':checked')) {
                $('#hover_markers').prop('disabled',false);
            } else {
                $('#hover_markers').prop('disabled',true);
            }
        } else {
            $('#hide_markers').prop('disabled',true);
            $('#hover_markers').prop('disabled',true);
        }
    }

    function change_transition_zoom() {
        var transition_zoom = $('#transition_zoom').val();
        $('#transition_zoom_val').html(transition_zoom);
    }
</script>