<?php
$dashboard_active = "";
$virtual_tours_active = "";
$ui_editor_active = "";
$dollhouse_active = "";
$rooms_active = "";
$markers_active = "";
$pois_active = "";
$maps_active = "";
$products_active = "";
$info_active = "";
$gallery_active = "";
$icons_active = "";
$media_library_active = "";
$music_library_active = "";
$sound_library_active = "";
$presentation_active = "";
$video360_active = "";
$video_project_active = "";
$forms_data_active = "";
$leads_active = "";
$statistics_active = "";
$statistics_all_active = "";
$landing_active = "";
$showcase_active = "";
$globes_active = "";
$preview_active = "";
$publish_active = "";
$settings_active = "";
$updater_active = "";
$users_active = "";
$plans_active = "";
$ads_active = "";
$measurements_active = "";
$collapse_vt_show = "";
$collapse_vt_active = "";
$collapse_vt_expanded = "false";
$collapse_vt_c = "collapsed";
$collapse_media_show = "";
$collapse_media_active = "";
$collapse_media_expanded = "false";
$collapse_media_c = "collapsed";
$collapse_publish_show = "";
$collapse_publish_active = "";
$collapse_publish_expanded = "false";
$collapse_publish_c = "collapsed";
$collapse_data_show = "";
$collapse_data_active = "";
$collapse_data_expanded = "false";
$collapse_data_c = "collapsed";
$collapse_stats_show = "";
$collapse_stats_active = "";
$collapse_stats_expanded = "false";
$collapse_stats_c = "collapsed";
switch ($page) {
    case 'dashboard':
        $dashboard_active = "active";
        break;
    case 'edit_virtual_tour':
    case 'virtual_tours':
        $virtual_tours_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'edit_virtual_tour_ui':
        $ui_editor_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'dollhouse':
        $dollhouse_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'edit_room':
    case 'rooms':
    case 'rooms_bulk':
    case 'edit_blur':
        $rooms_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'markers':
        $markers_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'pois':
        $pois_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'measurements':
        $measurements_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'maps':
    case 'maps_bulk':
    case 'edit_map':
        $maps_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'products':
    case 'edit_product':
        $products_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'info':
        $info_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'gallery':
        $gallery_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
    case 'icons_library':
        $icons_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
    case 'media_library':
        $media_library_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
    case 'music_library':
        $music_library_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
    case 'sound_library':
        $sound_library_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
    case 'presentation':
        $presentation_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'video360':
        $video360_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'video':
    case 'edit_video':
        $video_project_active = "active";
        $collapse_vt_show = "show";
        $collapse_vt_active = "active";
        $collapse_vt_expanded = "true";
        $collapse_vt_c = "";
        break;
    case 'forms_data':
        $forms_data_active = "active";
        $collapse_data_show = "show";
        $collapse_data_active = "active";
        $collapse_data_expanded = "true";
        $collapse_data_c = "";
        break;
    case 'leads':
        $leads_active = "active";
        $collapse_data_show = "show";
        $collapse_data_active = "active";
        $collapse_data_expanded = "true";
        $collapse_data_c = "";
        break;
    case 'statistics':
        $statistics_active = "active";
        $collapse_stats_show = "show";
        $collapse_stats_active = "active";
        $collapse_stats_expanded = "true";
        $collapse_stats_c = "";
        break;
    case 'statistics_all':
        $statistics_all_active = "active";
        $collapse_stats_show = "show";
        $collapse_stats_active = "active";
        $collapse_stats_expanded = "true";
        $collapse_stats_c = "";
        break;
    case 'landing':
        $landing_active = "active";
        $collapse_publish_show = "show";
        $collapse_publish_active = "active";
        $collapse_publish_expanded = "true";
        $collapse_publish_c = "";
        break;
    case 'showcases':
    case 'edit_showcase':
        $showcase_active = "active";
        $collapse_publish_show = "show";
        $collapse_publish_active = "active";
        $collapse_publish_expanded = "true";
        $collapse_publish_c = "";
        break;
    case 'globes':
    case 'edit_globe':
        $globes_active = "active";
        $collapse_publish_show = "show";
        $collapse_publish_active = "active";
        $collapse_publish_expanded = "true";
        $collapse_publish_c = "";
        break;
    case 'preview':
        $preview_active = "active";
        break;
    case 'publish':
        $publish_active = "active";
        $collapse_publish_show = "show";
        $collapse_publish_active = "active";
        $collapse_publish_expanded = "true";
        $collapse_publish_c = "";
        break;
    case 'settings':
        $settings_active = "active";
        break;
    case 'updater':
        $updater_active = "active";
        break;
    case 'users':
    case "edit_user":
        $users_active = "active";
        break;
    case 'plans':
        $plans_active = "active";
        break;
    case 'advertisements':
    case 'edit_advertisement':
        $ads_active = "active";
        $collapse_media_show = "show";
        $collapse_media_active = "active";
        $collapse_media_expanded = "true";
        $collapse_media_c = "";
        break;
}
$virtual_tours = get_virtual_tours($id_user,'no');
$count_virtual_tours = count($virtual_tours);
if($count_virtual_tours==0) {
    $vt_disabled = "disabled";
} else {
    $vt_disabled = "";
}
$vt_link_add='';
$room_link_add='';
$markers_link_add='';
$pois_link_add='';
if(isset($_GET['wstep'])) {
    $vt_link_add = "&wstep=2";
    $markers_link_add = "&wstep=18";
    $pois_link_add = "&wstep=25";
    if($_GET['wstep']==10) {
        $room_link_add = "&wstep=12";
    } else {
        $room_link_add = "&wstep=6";
    }
}
$statistics_visible = "";
$statistics_tour_visible = "";
$statistics_all_visible = "";
$virtual_tours_visible = "";
$list_tours_visible = "";
$ui_editor_visible = "";
$model_3d_editor_visible = "";
$rooms_visible = "";
$markers_visible = "";
$pois_visible = "";
$maps_visible = "";
$products_visible = "";
$info_visible = "";
$presentation_visible = "";
$video360_visible = "";
$video_project_visible = "";
$media_visible = "";
$gallery_visible = "";
$icons_visible = "";
$media_library_visible = "";
$music_library_visible = "";
$sound_library_visible = "";
$publish_visible = "";
$links_visible = "";
$landing_visible = "";
$showcase_visible = "";
$globes_visible = "";
$collected_data_visible = "";
$forms_visibile = "";
$leads_visible = "";
$preview_visible = "";
$measurements_visible = "";
$user_info = get_user_info($id_user);
if($user_info['role']!='administrator') {
    if ($user_info['id_plan'] != 0) {
        $plan = get_plan($user_info['id_plan']);
        $customize_menu_json = $plan['customize_menu'];
        if (!empty($customize_menu_json)) {
            $customize_menu = json_decode($customize_menu_json, true);
            if(isset($customize_menu['statistics_tour'])) {
                if ($customize_menu['statistics_tour'] == 0) $statistics_tour_visible = "hidden_menu";
                if ($customize_menu['statistics_all'] == 0) $statistics_all_visible = "hidden_menu";
            }
            if ($customize_menu['statistics'] == 0) $statistics_visible = "hidden_menu";
            if ($customize_menu['virtual_tours'] == 0) $virtual_tours_visible = "hidden_menu";
            if ($customize_menu['list_tours'] == 0) $list_tours_visible = "hidden_menu";
            if ($customize_menu['editor_ui'] == 0) $ui_editor_visible = "hidden_menu";
            if ($customize_menu['editor_3d'] == 0) $model_3d_editor_visible = "hidden_menu";
            if ($customize_menu['rooms'] == 0) $rooms_visible = "hidden_menu";
            if ($customize_menu['markers'] == 0) $markers_visible = "hidden_menu";
            if ($customize_menu['pois'] == 0) $pois_visible = "hidden_menu";
            if ($customize_menu['maps'] == 0) $maps_visible = "hidden_menu";
            if ($customize_menu['products'] == 0) $products_visible = "hidden_menu";
            if ($customize_menu['info_box'] == 0) $info_visible = "hidden_menu";
            if ($customize_menu['presentation'] == 0) $presentation_visible = "hidden_menu";
            if ($customize_menu['video360'] == 0) $video360_visible = "hidden_menu";
            if ($customize_menu['video_project'] == 0) $video_project_visible = "hidden_menu";
            if ($customize_menu['media'] == 0) $media_visible = "hidden_menu";
            if ($customize_menu['gallery'] == 0) $gallery_visible = "hidden_menu";
            if ($customize_menu['icons_library'] == 0) $icons_visible = "hidden_menu";
            if ($customize_menu['media_library'] == 0) $media_library_visible = "hidden_menu";
            if ($customize_menu['music_library'] == 0) $music_library_visible = "hidden_menu";
            if ($customize_menu['sound_library'] == 0) $sound_library_visible = "hidden_menu";
            if ($customize_menu['publish'] == 0) $publish_visible = "hidden_menu";
            if ($customize_menu['links'] == 0) $links_visible = "hidden_menu";
            if ($customize_menu['landing'] == 0) $landing_visible = "hidden_menu";
            if ($customize_menu['showcases'] == 0) $showcase_visible = "hidden_menu";
            if ($customize_menu['globes'] == 0) $globes_visible = "hidden_menu";
            if ($customize_menu['collected_data'] == 0) $collected_data_visible = "hidden_menu";
            if ($customize_menu['forms'] == 0) $forms_visibile = "hidden_menu";
            if ($customize_menu['leads'] == 0) $leads_visible = "hidden_menu";
            if ($customize_menu['preview'] == 0) $preview_visible = "hidden_menu";
            if ($customize_menu['measurements'] == 0) $measurements_visible = "hidden_menu";
        }
    }
}

$divider_1_visible = "hidden_menu";
$divider_2_visible = "hidden_menu";
if($virtual_tours_visible=="" || $media_visible=="") {
    $divider_1_visible = "";
}
if($publish_visible=="" || $collected_data_visible=="" || $preview_visible=="") {
    $divider_2_visible = "";
}

?>

<ul class="navbar-nav <?php echo ($settings['sidebar']=='flat') ? 'bg-flat-primary' : 'bg-gradient-primary'; ?> sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php?p=dashboard">
        <div class="sidebar-brand-icon">
            <?php if(!empty($settings['logo']) && !empty($settings['small_logo'])) { ?>
                <img id="sidebar_logo" style="max-height:45px;max-width:120px;width:auto;height:auto;" src="assets/<?php echo $settings['logo']; ?>" />
                <img id="sidebar_logo_small" style="display:none;max-height:45px;max-width:80px;width:auto;height:auto;" src="assets/<?php echo $settings['small_logo']; ?>" />
            <?php } else if(!empty($settings['logo']) && empty($settings['small_logo'])) { ?>
                <img id="sidebar_logo" style="max-height:45px;max-width:80px;width:auto;height:auto;" src="assets/<?php echo $settings['logo']; ?>" />
            <?php } else if(empty($settings['logo']) && !empty($settings['small_logo'])) { ?>
                <img id="sidebar_logo" style="max-height:45px;max-width:80px;width:auto;height:auto;" src="assets/<?php echo $settings['small_logo']; ?>" />
            <?php } else { ?>
                <?php echo strtoupper($settings['name']); ?>
            <?php } ?>
        </div>
    </a>
    <hr class="sidebar-divider my-0">
    <?php if($demo) : ?>
    <li class="nav-item">
        <div style="cursor:default;border-radius:0;width:100%;" class="card bg-danger text-white p-1 text-center border-0 noselect d-inline-block">
            <?php echo _("DEMO MODE"); ?> <i title="<?php echo _("ADD / SAVE features are disabled."); ?>" class="help_d fas fa-question-circle"></i>
        </div>
    </li>
    <script>
        $('.help_d').tooltipster({
            delay: 0,
            position: 'right'
        });
    </script>
    <?php endif; ?>
    <li class="nav-item <?php echo $dashboard_active; ?>">
        <a class="nav-link" href="index.php?p=dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span><?php echo _("Dashboard"); ?></span></a>
    </li>
    <li class="nav-item <?php echo $collapse_stats_active; ?> <?php echo $statistics_visible; ?>">
        <a class="nav-link <?php echo $collapse_stats_c; ?>" href="#" data-toggle="collapse" data-target="#collapse_stats" aria-expanded="<?php echo $collapse_stats_expanded; ?>" aria-controls="collapse_stats">
            <i class="fas fa-fw fa-chart-area"></i>
            <span><?php echo _("Statistics"); ?></span>
        </a>
        <div id="collapse_stats" class="collapse <?php echo $collapse_stats_show; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
            <div class="glass_effect_sidebar py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $statistics_active; ?> <?php echo $statistics_tour_visible; ?>" href="index.php?p=statistics"><i class="fas fa-fw fa-route"></i> <span><?php echo _("Tour"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $statistics_all_active; ?> <?php echo $statistics_all_visible; ?>" href="index.php?p=statistics_all"><i class="fas fa-fw fa-globe"></i> <span><?php echo _("Overall"); ?></span></a>
            </div>
        </div>
    </li>
    <hr class="sidebar-divider <?php echo $divider_1_visible; ?>">
    <li class="nav-item <?php echo $collapse_vt_active; ?> <?php echo $virtual_tours_visible; ?>">
        <a class="nav-link <?php echo $collapse_vt_c; ?>" id="virtual_tours_menu_item" href="#" data-toggle="collapse" data-target="#collapse_vt" aria-expanded="<?php echo $collapse_vt_expanded; ?>" aria-controls="collapse_vt">
            <i class="fas fa-fw fa-route"></i>
            <span><?php echo _("Virtual Tours"); ?></span>
        </a>
        <div id="collapse_vt" class="collapse <?php echo $collapse_vt_show; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
            <div class="glass_effect_sidebar py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo $virtual_tours_active; ?> <?php echo $list_tours_visible; ?>" id="list_tour_menu_item" href="index.php?p=virtual_tours<?php echo $vt_link_add; ?>"><i class="fas fa-fw fa-list"></i> <span><?php echo _("List Tours"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $rooms_active; ?> <?php echo $rooms_visible; ?>" id="room_menu_item" href="index.php?p=rooms<?php echo $room_link_add; ?>"><i class="fas fa-fw fa-vector-square"></i> <span><?php echo _("Rooms"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $markers_active; ?> <?php echo $markers_visible; ?>" id="markers_menu_item" href="index.php?p=markers<?php echo $markers_link_add; ?>"><i class="fas fa-fw fa-caret-square-up"></i> <span><?php echo _("Markers"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $pois_active; ?> <?php echo $pois_visible; ?>" id="pois_menu_item" href="index.php?p=pois<?php echo $pois_link_add; ?>"><i class="fas fa-fw fa-bullseye"></i> <span><?php echo _("POIs"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $maps_active; ?> <?php echo $maps_visible; ?>" href="index.php?p=maps"><i class="fas fa-fw fa-map-marked-alt"></i> <span><?php echo _("Maps"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $ui_editor_active; ?> <?php echo $ui_editor_visible; ?>" id="ui_editor_menu_item" href="index.php?p=edit_virtual_tour_ui"><i class="fas fa-fw fa-swatchbook"></i> <span><?php echo _("Editor UI"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $dollhouse_active; ?> <?php echo $model_3d_editor_visible; ?>" href="index.php?p=dollhouse"><i class="fas fa-fw fa-cube"></i> <span><?php echo _("3D View"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $measurements_active; ?> <?php echo $measurements_visible; ?>" href="index.php?p=measurements"><i class="fas fa-fw fa-ruler-combined"></i> <span><?php echo _("Measurements"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $products_active; ?> <?php echo $products_visible; ?>" href="index.php?p=products"><i class="fas fa-fw fa-shopping-cart"></i> <span><?php echo _("Products"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $info_active; ?> <?php echo $info_visible; ?>" href="index.php?p=info"><i class="fas fa-fw fa-info-circle"></i> <span><?php echo _("Info Box"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $presentation_active; ?> <?php echo $presentation_visible; ?>" href="index.php?p=presentation"><i class="fas fa-fw fa-directions"></i> <span><?php echo _("Presentation"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $video360_active; ?> <?php echo $video360_visible; ?>" href="index.php?p=video360"><i class="fas fa-fw fa-video"></i> <span><?php echo _("360 Video Tour"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $video_project_active; ?> <?php echo $video_project_visible; ?>" href="index.php?p=video"><i class="fas fa-fw fa-film"></i> <span><?php echo _("Video Projects"); ?></span></a>
            </div>
        </div>
    </li>
    <li class="nav-item <?php echo $collapse_media_active; ?> <?php echo $media_visible; ?>">
        <a class="nav-link <?php echo $collapse_media_c; ?>" href="#" data-toggle="collapse" data-target="#collapse_media" aria-expanded="<?php echo $collapse_media_expanded; ?>" aria-controls="collapse_media">
            <i class="fas fa-fw fa-desktop"></i>
            <span><?php echo _("Media"); ?></span>
        </a>
        <div id="collapse_media" class="collapse <?php echo $collapse_media_show; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
            <div class="glass_effect_sidebar py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $gallery_active; ?> <?php echo $gallery_visible; ?>" href="index.php?p=gallery"><i class="fas fa-fw fa-images"></i> <span><?php echo _("Gallery"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $icons_active; ?> <?php echo $icons_visible; ?>" href="index.php?p=icons_library"><i class="fas fa-fw fa-icons"></i> <span><?php echo _("Icons Library"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $media_library_active; ?> <?php echo $media_library_visible; ?>" href="index.php?p=media_library"><i class="fas fa-fw fa-photo-video"></i> <span><?php echo _("Media Library"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $music_library_active; ?> <?php echo $music_library_visible; ?>" href="index.php?p=music_library"><i class="fas fa-fw fa-music"></i> <span><?php echo _("Music Library"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $sound_library_active; ?> <?php echo $sound_library_visible; ?>" href="index.php?p=sound_library"><i class="fas fa-fw fa-volume-up"></i> <span><?php echo _("Sound Library"); ?></span></a>
                <?php if($user_info['role']=='administrator') : ?>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $ads_active; ?>" href="index.php?p=advertisements"><i class="fas fa-fw fa-ad"></i> <span><?php echo _("Advertisements"); ?></span></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <hr class="sidebar-divider <?php echo $divider_2_visible; ?>">
    <li class="nav-item <?php echo $collapse_publish_active; ?> <?php echo $publish_visible; ?>">
        <a class="nav-link <?php echo $collapse_publish_c; ?>" href="#" data-toggle="collapse" data-target="#collapse_publish" aria-expanded="<?php echo $collapse_publish_expanded; ?>" aria-controls="collapse_publish">
            <i class="fas fa-fw fa-paper-plane"></i>
            <span><?php echo _("Publish"); ?></span>
        </a>
        <div id="collapse_publish" class="collapse <?php echo $collapse_publish_show; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
            <div class="glass_effect_sidebar py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $publish_active; ?> <?php echo $links_visible; ?>" href="index.php?p=publish"><i class="fas fa-fw fa-route"></i> <span><?php echo _("Tour"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $landing_active; ?> <?php echo $landing_visible; ?>" href="index.php?p=landing"><i class="fas fa-fw fa-file-alt"></i> <span><?php echo _("Landing"); ?></span></a>
                <?php if($user_info['role']!='editor') : ?>
                    <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $showcase_active; ?> <?php echo $showcase_visible; ?>" href="index.php?p=showcases"><i class="fas fa-fw fa-object-group"></i> <span><?php echo _("Showcases"); ?></span></a>
                    <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $globes_active; ?> <?php echo $globes_visible; ?>" href="index.php?p=globes"><i class="fas fa-fw fa-globe-americas"></i> <span><?php echo _("Globes"); ?></span></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <li class="nav-item <?php echo $collapse_data_active; ?> <?php echo $collected_data_visible; ?>">
        <a class="nav-link <?php echo $collapse_data_c; ?>" href="#" data-toggle="collapse" data-target="#collapse_data" aria-expanded="<?php echo $collapse_data_expanded; ?>" aria-controls="collapse_data">
            <i class="fas fa-fw fa-server"></i>
            <span><?php echo _("Collected Data"); ?></span>
        </a>
        <div id="collapse_data" class="collapse <?php echo $collapse_data_show; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
            <div class="glass_effect_sidebar py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $forms_data_active; ?> <?php echo $forms_visibile; ?>" href="index.php?p=forms_data"><i class="fas fa-fw fa-database"></i> <span><?php echo _("Forms"); ?></span></a>
                <a class="collapse-item <?php echo $vt_disabled; ?> <?php echo $leads_active; ?> <?php echo $leads_visible; ?>" href="index.php?p=leads"><i class="fas fa-fw fa-user-tag"></i> <span><?php echo _("Leads"); ?></span></a>
            </div>
        </div>
    </li>
    <li class="nav-item <?php echo $preview_active; ?> <?php echo $preview_visible; ?>">
        <a class="nav-link <?php echo $vt_disabled; ?>" id="preview_menu_item" href="index.php?p=preview">
            <i class="fas fa-fw fa-eye"></i>
            <span><?php echo _("Preview"); ?></span></a>
    </li>
    <?php if($user_info['role']=='administrator' && $user_info['super_admin']) : ?>
    <hr class="sidebar-divider d-none d-md-block">
    <li class="nav-item <?php echo $settings_active; ?>">
        <a class="nav-link" href="index.php?p=settings">
            <i class="fas fa-fw fa-cogs"></i>
            <span><?php echo _("Settings"); ?></span></a>
    </li>
    <li class="nav-item <?php echo $updater_active; ?>">
        <a class="nav-link" href="index.php?p=updater">
            <i class="fas fa-fw fa-download"></i>
            <span><?php echo _("Upgrade"); ?> <?php echo (version_compare($version,$latest_version)==-1) ? '<i style="color:white;font-size:11px;" class="fas fa-exclamation-circle"></i>' : ''; ?></span></a>
    </li>
    <li class="nav-item <?php echo $users_active; ?>">
        <a class="nav-link" href="index.php?p=users">
            <i class="fas fa-fw fa-users"></i>
            <span><?php echo _("Users"); ?></span></a>
    </li>
    <li class="nav-item <?php echo $plans_active; ?>">
        <a class="nav-link" href="index.php?p=plans">
            <i class="fas fa-fw fa-crown"></i>
            <span><?php echo _("Plans"); ?></span></a>
    </li>
    <?php endif; ?>
    <?php if($user_info['role']=='administrator' && !$user_info['super_admin']) : ?>
    <hr class="sidebar-divider d-none d-md-block">
    <li class="nav-item <?php echo $users_active; ?>">
        <a class="nav-link" href="index.php?p=users">
            <i class="fas fa-fw fa-users"></i>
            <span><?php echo _("Users"); ?></span></a>
    </li>
    <?php endif; ?>
    <input id="lc_pc" type="hidden" value="<?php echo $settings['lc_pc']; ?>" />
    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>

<script>
    if ($(window).width() < 768) {
        $(".nav-item.active .collapse").removeClass('show');
        if(window.wizard_step==-1) {
            $('.page-top').addClass('sidebar-toggled');
            $('#accordionSidebar').addClass('toggled');
        } else {
            sessionStorage.setItem("sidebar_accord", 1);
        }
        if($('#sidebar_logo_small').length) {
            $('#sidebar_logo').hide();
            $('#sidebar_logo_small').show();
        }
    }
    $(window).resize(function() {
        if ($(window).width() < 768) {
            if($('#sidebar_logo_small').length) {
                $('#sidebar_logo').hide();
                $('#sidebar_logo_small').show();
            }
        } else {
            if($('#accordionSidebar').hasClass('toggled')) {
                if($('#sidebar_logo_small').length) {
                    $('#sidebar_logo').hide();
                    $('#sidebar_logo_small').show();
                }
            } else {
                if($('#sidebar_logo_small').length) {
                    $('#sidebar_logo').show();
                    $('#sidebar_logo_small').hide();
                }
            }
        }
    });
    $("#sidebarToggle").click(function(){
        if($('#accordionSidebar').hasClass('toggled')) {
            sessionStorage.setItem("sidebar_accord", 1);
            $(".nav-item.active .collapse").addClass('show');
            if($('#sidebar_logo_small').length) {
                $('#sidebar_logo').show();
                $('#sidebar_logo_small').hide();
            }
        } else {
            sessionStorage.setItem("sidebar_accord", 0);
            $(".collapse").removeClass('show');
            if($('#sidebar_logo_small').length) {
                $('#sidebar_logo').hide();
                $('#sidebar_logo_small').show();
            }
        }
    });
    if ("sidebar_accord" in sessionStorage) {
        if(sessionStorage.getItem('sidebar_accord') == 0) {
            $('#accordionSidebar').addClass('toggled');
            $(".collapse").removeClass('show');
            if($('#sidebar_logo_small').length) {
                $('#sidebar_logo').hide();
                $('#sidebar_logo_small').show();
            }
        } else {
            if ($(window).width() >= 768) {
                $(".nav-item.active .collapse").addClass('show');
                if($('#sidebar_logo_small').length) {
                    $('#sidebar_logo').show();
                    $('#sidebar_logo_small').hide();
                }
            } else {
                if($('#sidebar_logo_small').length) {
                    $('#sidebar_logo').hide();
                    $('#sidebar_logo_small').show();
                }
            }
        }
    }
</script>