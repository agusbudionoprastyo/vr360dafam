<?php
switch($user_info['plan_status']) {
    case 'active':
        $icon_plan = "<i class='fa fa-circle mt-1' style='color: green'></i>";
        break;
    case 'expiring':
        $icon_plan = "<i class='fa fa-circle mt-1' style='color: darkorange'></i>";
        break;
    case 'expired':
    case 'invalid_payment':
        $icon_plan = "<i class='fa fa-circle mt-1' style='color: red'></i>";
        break;
}
$settings = get_settings();
if(empty($_SESSION['lang'])) {
    $lang = $settings['language'];
} else {
    $lang = $_SESSION['lang'];
}
$sub_header = '';
switch ($page) {
    case 'dashboard':
        $menu_title = '<i class="fas fa-fw fa-tachometer-alt text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Dashboard").'</span>';
        break;
    case 'edit_virtual_tour':
        $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_virtualtour();return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a></div>';
        $menu_title = '<i class="fas fa-fw fa-route text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Tour").'</span>';
        break;
    case 'virtual_tours':
        $menu_title = '<i class="fas fa-fw fa-list text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("List Tours").'</span>';
        break;
    case 'edit_virtual_tour_ui':
        $sub_header = print_virtualtour_selector('no');
        $menu_title = '<i class="fas fa-fw fa-swatchbook text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Editor UI").'</span>';
        break;
    case 'dollhouse':
        $sub_header = print_virtualtour_selector('dollhouse');
        $virtualtour = get_virtual_tour($_SESSION['id_virtualtour_sel'],$_SESSION['id_user']);
        $show_in_ui = $virtualtour['show_dollhouse'];
        $show_ui_icon = '<i style="font-size:10px;vertical-align:middle;color:'.(($show_in_ui>0)?'green':'orange').'" '.(($show_in_ui==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':'').' class="'.(($show_in_ui==0)?'help_t':'').' fas fa-circle"></i>';
        $menu_title = '<i class="fas fa-fw fa-cube text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("3D View").'</span> '.$show_ui_icon;
        break;
    case 'edit_room':
        $id_room = $_GET['id'];
        $next_prev_room = get_next_prev_room_id($id_room,$_SESSION['id_virtualtour_sel']);
        $id_next_room = $next_prev_room[0];
        $id_prev_room = $next_prev_room[1];
        $btn_nav_rooms = '<a title="'._("EDIT PREVIOUS ROOM").'" href="index.php?p=edit_room&id='.$id_prev_room.'" class="btn btn-sm tooltip_arrows btn-primary btn-icon-split '.(($id_next_room==$id_prev_room) ? 'disabled':'').'">
        <span class="icon text-white-50">
          <i class="fas fa-angle-left"></i>
        </span>
        </a>
        <a title="'._("EDIT NEXT ROOM").'" href="index.php?p=edit_room&id='.$id_next_room.'" class="btn btn-sm tooltip_arrows btn-primary btn-icon-split '.(($id_next_room==$id_prev_room) ? 'disabled':'').'">
        <span class="icon text-white-50">
          <i class="fas fa-angle-right"></i>
        </span>
        </a>';
        if($user_info['role']=="editor") {
            $editor_permissions = get_editor_permissions($_SESSION['id_user'],$_SESSION['id_virtualtour_sel']);
            if($editor_permissions['delete_rooms']==1) {
                $delete_permission = true;
            } else {
                $delete_permission = false;
            }
        } else {
            $delete_permission = true;
        }
        $sub_header = '<div class="vt_select_header"><div class="justify-content-end">'.$btn_nav_rooms.'<a style="margin-left: 5px" id="save_btn" href="#" onclick="save_room(null,0);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a>&nbsp;&nbsp;&nbsp;<button data-toggle="modal" data-target="#modal_delete_room" type="button" class="btn btn-sm btn-danger '.(($delete_permission) ? '' : 'd-none').'">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-vector-square text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Room").'</span>';
        break;
    case 'rooms':
    case 'rooms_bulk':
        $sub_header = print_virtualtour_selector('rooms');
        $menu_title = '<i class="fas fa-fw fa-vector-square text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Rooms").'</span>';
        break;
    case 'edit_blur':
        $menu_title = '<i class="fas fa-fw fa-fire-extinguisher text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Blur").'</span>';
        break;
    case 'markers':
        $sub_header = print_virtualtour_selector('markers');
        $menu_title = '<i class="fas fa-fw fa-caret-square-up text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Markers").'</span>';
        break;
    case 'measurements':
        $sub_header = print_virtualtour_selector('measures');
        $menu_title = '<i class="fas fa-fw fa-ruler-combined text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Measurements").'</span>';
        break;
    case 'pois':
        $sub_header = print_virtualtour_selector('pois');
        $menu_title = '<i class="fas fa-fw fa-bullseye text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("POIs").'</span>';
        break;
    case 'maps':
    case 'maps_bulk':
        $sub_header = print_virtualtour_selector('maps');
        $menu_title = '<i class="fas fa-fw fa-map-marked-alt text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Maps").'</span>';
        break;
    case 'edit_map':
        $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_map_settings(false);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a></div>';
        $menu_title = '<i class="fas fa-fw fa-map-marked-alt text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Map").'</span>';
        break;
    case 'products':
        $sub_header = print_virtualtour_selector('products');
        $menu_title = '<i class="fas fa-fw fa-shopping-cart text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Products").'</span>';
        break;
    case 'edit_product':
        $id_product = $_GET['id'];
        $sub_header = '<div class="vt_select_header"><div><a id="save_btn" href="#" onclick="save_product('.$id_product.');return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a><button '.(($demo) ? 'disabled':'').' onclick="modal_delete_product('.$id_product.');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-shopping-cart text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Product").'</span>';
        break;
    case 'info':
        $sub_header = print_virtualtour_selector('info_box');
        $virtualtour = get_virtual_tour($_SESSION['id_virtualtour_sel'],$_SESSION['id_user']);
        $show_in_ui = $virtualtour['show_info'];
        $show_ui_icon = '<i style="font-size:10px;vertical-align:middle;color:'.(($show_in_ui>0)?'green':'orange').'" '.(($show_in_ui==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':'').' class="'.(($show_in_ui==0)?'help_t':'').' fas fa-circle"></i>';
        $menu_title = '<i class="fas fa-fw fa-info-circle text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Info Box").'</span> '.$show_ui_icon;
        break;
    case 'gallery':
        $sub_header = print_virtualtour_selector('gallery');
        $virtualtour = get_virtual_tour($_SESSION['id_virtualtour_sel'],$_SESSION['id_user']);
        $show_in_ui = $virtualtour['show_gallery'];
        $show_ui_icon = '<i style="font-size:10px;vertical-align:middle;color:'.(($show_in_ui>0)?'green':'orange').'" '.(($show_in_ui==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':'').' class="'.(($show_in_ui==0)?'help_t':'').' fas fa-circle"></i>';
        $menu_title = '<i class="fas fa-fw fa-images text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Gallery").'</span> '.$show_ui_icon;
        break;
    case 'icons_library':
        $sub_header = print_virtualtour_selector('icons_library');
        $menu_title = '<i class="fas fa-fw fa-icons text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Icons Library").'</span>';
        break;
    case 'media_library':
        $sub_header = print_virtualtour_selector('media_library');
        $menu_title = '<i class="fas fa-fw fa-photo-video text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Media Library").'</span>';
        break;
    case 'music_library':
        $sub_header = print_virtualtour_selector('music_library');
        $menu_title = '<i class="fas fa-fw fa-music text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Music Library").'</span>';
        break;
    case 'sound_library':
        $sub_header = print_virtualtour_selector('sound_library');
        $menu_title = '<i class="fas fa-fw fa-volume-up text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Sound Library").'</span>';
        break;
    case 'presentation':
        $sub_header = print_virtualtour_selector('no');
        $virtualtour = get_virtual_tour($_SESSION['id_virtualtour_sel'],$_SESSION['id_user']);
        $show_in_ui = $virtualtour['show_presentation'];
        $show_ui_icon = '<i style="font-size:10px;vertical-align:middle;color:'.(($show_in_ui>0)?'green':'orange').'" '.(($show_in_ui==0)?'title="'._("Not visible in the tour, enable it in the Editor UI").'"':'').' class="'.(($show_in_ui==0)?'help_t':'').' fas fa-circle"></i>';
        $menu_title = '<i class="fas fa-fw fa-directions text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Presentation").'</span> '.$show_ui_icon;
        break;
    case 'video360':
        $sub_header = print_virtualtour_selector('video_360');
        $menu_title = '<i class="fas fa-fw fa-video text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("360 Video Tour").'</span>';
        break;
    case 'forms_data':
        $sub_header = print_virtualtour_selector('forms');
        $menu_title = '<i class="fas fa-fw fa-database text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Forms").'</span>';
        break;
    case 'leads':
        $sub_header = print_virtualtour_selector('leads');
        $menu_title = '<i class="fas fa-fw fa-user-tag text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Leads").'</span>';
        break;
    case 'statistics':
        $sub_header = print_virtualtour_selector('no');
        $menu_title = '<i class="fas fa-fw fa-chart-area text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Statistics").' - '._("Tour").'</span>';
        break;
    case 'statistics_all':
        $menu_title = '<i class="fas fa-fw fa-chart-area text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Statistics").' - '._("Overall").'</span>';
        break;
    case 'landing':
        $sub_header = print_virtualtour_selector('landing');
        $menu_title = '<i class="fas fa-fw fa-file-alt text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Publish").' - '._("Landing").'</span>';
        break;
    case 'showcases':
        $menu_title = '<i class="fas fa-fw fa-object-group text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Publish").' - '._("Showcases").'</span>';
        break;
    case 'edit_showcase':
        $id_showcase = $_GET['id'];
        $sub_header = '<div class="vt_select_header"><div><a id="save_btn" href="#" onclick="save_showcase('.$id_showcase.');return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a><button '.(($demo) ? 'disabled':'').' onclick="modal_delete_showcase('.$id_showcase.');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-object-group text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Showcase").'</span>';
        break;
    case 'globes':
        $menu_title = '<i class="fas fa-fw fa-globe-americas text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Publish").' - '._("Globes").'</span>';
        break;
    case 'edit_globe':
        $id_globe = $_GET['id'];
        $sub_header = '<div class="vt_select_header"><div><a id="save_btn" href="#" onclick="save_globe('.$id_globe.',false);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a><button '.(($demo) ? 'disabled':'').' onclick="modal_delete_globe('.$id_globe.');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-globe-americas text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Globe").'</span>';
        break;
    case 'preview':
        $sub_header = print_virtualtour_selector('no');
        $menu_title = '<i class="fas fa-fw fa-eye text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Preview").'</span>';
        break;
    case 'publish':
        $sub_header = print_virtualtour_selector('no');
        $menu_title = '<i class="fas fa-fw fa-route text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Publish").' - '._("Tour"). '</span>';
        break;
    case 'settings':
        $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_settings(false);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a></div>';
        $menu_title = '<i class="fas fa-fw fa-cogs text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Settings").'</span>';
        break;
    case 'updater':
        $menu_title = '<i class="fas fa-fw fa-download text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Upgrade").'</span>';
        break;
    case 'users':
        $menu_title = '<i class="fas fa-fw fa-users text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Users").'</span>';
        break;
    case "edit_user":
        $id_user_edit = $_GET['id'];
        $btn_del_user = '';
        if($_SESSION['id_user']!=$id_user_edit) {
            $id_user_crypt = xor_obfuscator($id_user_edit);
            $btn_del_user = '<button '.(($demo) ? 'disabled':'').' onclick="modal_delete_user(\''.$id_user_crypt.'\');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button>';
        }
        $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_user('.$id_user_edit.');return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a>'.$btn_del_user.'</div>';
        $menu_title = '<i class="fas fa-fw fa-users text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit User").'</span>';
        break;
    case 'plans':
        $menu_title = '<i class="fas fa-fw fa-crown text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Plans").'</span>';
        break;
    case 'advertisements':
        $menu_title = '<i class="fas fa-fw fa-ad text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Advertisements").'</span>';
        break;
    case 'edit_advertisement':
        $id_advertisement = $_GET['id'];
        $sub_header = '<div class="vt_select_header"><div><a id="save_btn" href="#" onclick="save_advertisement('.$id_advertisement.');return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a><button '.(($demo) ? 'disabled':'').' onclick="modal_delete_advertisement('.$id_advertisement.');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-ad text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Advertisement").'</span>';
        break;
    case 'edit_profile':
        $id_user_edit = $_SESSION['id_user'];
        $to_complete = check_profile_to_complete($id_user_edit);
        if(!$to_complete) {
            $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_profile(false);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a></div>';
        }
        $menu_title = '<i class="fas fa-fw fa-user text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Profile").'</span>';
        break;
    case 'video':
        $sub_header = print_virtualtour_selector('video_projects');
        $menu_title = '<i class="fas fa-fw fa-film text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Video Projects").'</span>';
        break;
    case 'edit_video':
        $id_video = $_GET['id'];
        $sub_header = '<div class="vt_select_header"><div><a id="save_btn" href="#" onclick="save_video('.$id_video.',false);return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a><button '.(($demo) ? 'disabled':'').' onclick="modal_delete_video_project('.$id_video.');" class="btn btn-sm btn-danger ml-2">'._("DELETE").'</button></div></div>';
        $menu_title = '<i class="fas fa-fw fa-film text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Edit Video Project").'</span>';
        break;
    case 'features':
        $sub_header = '<div class="vt_select_header"><a id="save_btn" href="#" onclick="save_features();return false;" class="btn btn-sm btn-success btn-icon-split '.(($demo) ? 'disabled':'').'"><span class="icon text-white-50"><i class="far fa-circle"></i></span><span class="text">'._("SAVE").'</span></a></div>';
        $menu_title = '<i class="fas fa-fw fa-tasks text-gray-700"></i> <span class="mb-0 text-gray-800">'. _("Features").'</span>';
        break;
}
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar static-top <?php echo (empty($sub_header) ? 'shadow' : '') ?>">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-0">
        <i class="fa fa-bars"></i>
    </button>
    <div class="header_menu_title noselect ml-1"><?php echo $menu_title; ?></div>
    <ul class="navbar-nav ml-auto">
        <?php if($user_info['role']!='editor') : ?>
            <?php if($user_info['id_plan']!=0) : ?>
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="planDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="nav-link text-gray-600 small"><span class="px-2 py-1" style="margin-top:1px;border:1px solid #c4c4c4;border-radius:20px"><?php echo $icon_plan; ?>&nbsp;&nbsp;<?php echo $user_info['plan']; ?></span></span>
                    </a>
                    <div style="cursor: default;" class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="planDropdown">
                        <div style="pointer-events:none;" class="dropdown-item">
                            <?php echo _("Status"); ?>:&nbsp;<?php
                            switch($user_info['plan_status']) {
                                case 'active':
                                    echo "<span style='color:green'><b>"._("Active")."</b></span>";
                                    break;
                                case 'expiring':
                                    $expires_msg = "";
                                    if($user_info['expire_plan_date']) {
                                        $diff_days = dateDiffInDays(date('Y-m-d',strtotime($user_info['expire_plan_date'])),date('Y-m-d',strtotime('today')));
                                        $expires_msg = sprintf(_("- expires in %s days"),abs($diff_days));
                                    }
                                    echo "<span style='color:darkorange'><b>"._("Active")."</b></span> $expires_msg";
                                    break;
                                case 'expired':
                                    echo "<span style='color:red'><b>"._("Expired")."</b></span>";
                                    break;
                                case 'invalid_payment':
                                    echo "<span style='color:red'><b>"._("Invalid payment")."</b></span>";
                                    break;
                            }
                            ?>
                        </div>
                        <div style="pointer-events:none;" class="dropdown-item">
                            <?php
                            if($plan_info['n_virtual_tours']>0) {
                                $perc_tours = number_format(calculatePercentage($user_stats['count_virtual_tours'],$plan_info['n_virtual_tours']),0);
                                if($perc_tours>=75 && $perc_tours<100) {
                                    $perc_tours_bg = "warning";
                                } else if($perc_tours>=100) {
                                    $perc_tours = 100;
                                    $perc_tours_bg = "danger";
                                } else {
                                    $perc_tours_bg = "success";
                                }
                            } else {
                                $perc_tours = 100;
                                $perc_tours_bg = "success";
                            }
                            if($plan_info['n_rooms']>0) {
                                $perc_rooms = number_format(calculatePercentage($user_stats['count_rooms'],$plan_info['n_rooms']),0);
                                if($perc_rooms>=75 && $perc_rooms<100) {
                                    $perc_rooms_bg = "warning";
                                } else if($perc_rooms>=100) {
                                    $perc_rooms = 100;
                                    $perc_rooms_bg = "danger";
                                } else {
                                    $perc_rooms_bg = "success";
                                }
                            } else {
                                $perc_rooms = 100;
                                $perc_rooms_bg = "success";
                            }
                            if($plan_info['n_markers']>0) {
                                $perc_markers = number_format(calculatePercentage($user_stats['count_markers'],$plan_info['n_markers']),0);
                                if($perc_markers>=75 && $perc_markers<100) {
                                    $perc_markers_bg = "warning";
                                } else if($perc_markers>=100) {
                                    $perc_markers = 100;
                                    $perc_markers_bg = "danger";
                                } else {
                                    $perc_markers_bg = "success";
                                }
                            } else {
                                $perc_markers = 100;
                                $perc_markers_bg = "success";
                            }
                            if($plan_info['n_pois']>0) {
                                $perc_pois = number_format(calculatePercentage($user_stats['count_pois'],$plan_info['n_pois']),0);
                                if($perc_pois>=75 && $perc_pois<100) {
                                    $perc_pois_bg = "warning";
                                } else if($perc_pois>=100) {
                                    $perc_pois = 100;
                                    $perc_pois_bg = "danger";
                                } else {
                                    $perc_pois_bg = "success";
                                }
                            } else {
                                $perc_pois = 100;
                                $perc_pois_bg = "success";
                            }
                            if($plan_info['max_storage_space']>0) {
                                $perc_size = number_format(calculatePercentage($user_info['storage_space'],$plan_info['max_storage_space']),0);
                                if($perc_size>=75 && $perc_size<100) {
                                    $perc_size_bg = "warning";
                                } else if($perc_size>=100) {
                                    $perc_size = 100;
                                    $perc_size_bg = "danger";
                                } else {
                                    $perc_size_bg = "success";
                                }
                                if($user_info['storage_space']>=1000) {
                                    $actual_storage = ($user_info['storage_space']/1000)." GB";
                                } else {
                                    $actual_storage = $user_info['storage_space']." MB";
                                }
                                if($plan_info['max_storage_space']>=1000) {
                                    $max_storage = ($plan_info['max_storage_space']/1000)." GB";
                                } else {
                                    $max_storage = $plan_info['max_storage_space']." MB";
                                }
                            }
                            ?>
                            <div id="progress_plan_vt" class="progress mb-1 position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                <div style="width:<?php echo $perc_tours; ?>%" class="progress-bar d-inline-block bg-<?php echo $perc_tours_bg; ?>" role="progressbar" aria-valuenow="<?php echo $perc_tours; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("Virtual Tours"); ?>: <?php echo $user_stats['count_virtual_tours']." "._("of")."&nbsp;".(($plan_info['n_virtual_tours']<0) ? '<i style="vertical-align: middle;margin-top: 2px;" class="fas fa-infinity"></i>' : '<b>'.$plan_info['n_virtual_tours']).'</b>'; ?></div>
                            </div>
                            <div id="progress_plan_room" class="progress mb-1 position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                <div style="width:<?php echo $perc_rooms; ?>%" class="progress-bar d-inline-block bg-<?php echo $perc_rooms_bg; ?>" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("Rooms"); ?>: <?php echo $user_stats['count_rooms']." "._("of")."&nbsp;".(($plan_info['n_rooms']<0) ? '<i style="vertical-align: middle;margin-top: 2px;" class="fas fa-infinity"></i>' : '<b>'.$plan_info['n_rooms']).'</b>'; ?></div>
                            </div>
                            <div id="progress_plan_marker" class="progress mb-1 position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                <div style="width:<?php echo $perc_markers; ?>%" class="progress-bar d-inline-block bg-<?php echo $perc_markers_bg; ?>" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("Markers"); ?>: <?php echo $user_stats['count_markers']." "._("of")."&nbsp;".(($plan_info['n_markers']<0) ? '<i style="vertical-align: middle;margin-top: 2px;" class="fas fa-infinity"></i>' : '<b>'.$plan_info['n_markers']).'</b>'; ?></div>
                            </div>
                            <div id="progress_plan_poi" class="progress position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                <div style="width:<?php echo $perc_pois; ?>%" class="progress-bar d-inline-block bg-<?php echo $perc_pois_bg; ?>" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("POIs"); ?>: <?php echo $user_stats['count_pois']." "._("of")."&nbsp;".(($plan_info['n_pois']<0) ? '<i style="vertical-align: middle;margin-top: 2px;" class="fas fa-infinity"></i>' : '<b>'.$plan_info['n_pois']).'</b>'; ?></div>
                            </div>
                            <?php if($plan_info['max_storage_space']>0) : ?>
                                <div id="progress_plan_size" class="progress mt-1 position-relative" style="background-color:#b0b0b0;line-height:16px;">
                                    <div style="width:<?php echo $perc_size; ?>%" class="progress-bar d-inline-block bg-<?php echo $perc_size_bg; ?>" role="progressbar" aria-valuenow="<?php echo $perc_size; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    <div class="justify-content-center d-flex position-absolute w-100 text-white"><?php echo _("Storage Quota"); ?>: <?php echo $actual_storage."&nbsp;/&nbsp;".'<b>'.$max_storage.'</b>'; ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if($settings['change_plan']) { ?>
                            <div class="dropdown-item change_plan_dropdown" style="background-color:white;pointer-events:none;">
                                <a href="index.php?p=change_plan" style="pointer-events:initial" class="btn btn-primary btn-block btn-sm">
                                    <span class="align-middle"><?php echo strtoupper(_("change plan")); ?></span>&nbsp;&nbsp;&nbsp;<i class="fas fa-random align-middle"></i>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </li>
            <?php endif; ?>
        <?php endif; ?>
        <li class="nav-item dropdown no-arrow lang_switcher">
            <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?php echo ($settings['languages_count']==1) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
                <img style="height: 14px;" src="img/flags_lang/<?php echo $lang; ?>.png?v=2" />
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="langDropdown">
                <?php if(check_language_enabled('ar_SA',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('ar_SA');" class="<?php echo ($lang=='ar_SA') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/ar_SA.png?v=2" /> <span class="ml-2">العربية</span></span> <?php endif; ?>
                <?php if(check_language_enabled('zh_CN',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('zh_CN');" class="<?php echo ($lang=='zh_CN') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/zh_CN.png?v=2" /> <span class="ml-2">简体中文</span></span> <?php endif; ?>
                <?php if(check_language_enabled('zh_HK',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('zh_HK');" class="<?php echo ($lang=='zh_HK') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/zh_HK.png?v=2" /> <span class="ml-2">繁體中文（香港）</span></span> <?php endif; ?>
                <?php if(check_language_enabled('zh_TW',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('zh_TW');" class="<?php echo ($lang=='zh_TW') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/zh_TW.png?v=2" /> <span class="ml-2">繁體中文（台灣）</span></span> <?php endif; ?>
                <?php if(check_language_enabled('cs_CZ',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('cs_CZ');" class="<?php echo ($lang=='cs_CZ') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/cs_CZ.png?v=2" /> <span class="ml-2">Čeština</span></span> <?php endif; ?>
                <?php if(check_language_enabled('nl_NL',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('nl_NL');" class="<?php echo ($lang=='nl_NL') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/nl_NL.png?v=2" /> <span class="ml-2">Nederlands</span></span> <?php endif; ?>
                <?php if(check_language_enabled('en_US',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('en_US');" class="<?php echo ($lang=='en_US') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/en_US.png?v=2" /> <span class="ml-2">English</span></span> <?php endif; ?>
                <?php if(check_language_enabled('fil_PH',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('fil_PH');" class="<?php echo ($lang=='fil_PH') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/fil_PH.png?v=2" /> <span class="ml-2">Filipino</span></span> <?php endif; ?>
                <?php if(check_language_enabled('fr_FR',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('fr_FR');" class="<?php echo ($lang=='fr_FR') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/fr_FR.png?v=2" /> <span class="ml-2">Français</span></span> <?php endif; ?>
                <?php if(check_language_enabled('de_DE',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('de_DE');" class="<?php echo ($lang=='de_DE') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/de_DE.png?v=2" /> <span class="ml-2">Deutsch</span></span> <?php endif; ?>
                <?php if(check_language_enabled('hi_IN',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('hi_IN');" class="<?php echo ($lang=='hi_IN') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/hi_IN.png?v=2" /> <span class="ml-2">हिंदी</span></span> <?php endif; ?>
                <?php if(check_language_enabled('hu_HU',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('hu_HU');" class="<?php echo ($lang=='hu_HU') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/hu_HU.png?v=2" /> <span class="ml-2">Magyar</span></span> <?php endif; ?>
                <?php if(check_language_enabled('rw_RW',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('rw_RW');" class="<?php echo ($lang=='rw_RW') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/rw_RW.png?v=2" /> <span class="ml-2">Kinyarwanda</span></span> <?php endif; ?>
                <?php if(check_language_enabled('ko_KR',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('ko_KR');" class="<?php echo ($lang=='ko_KR') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/ko_KR.png?v=2" /> <span class="ml-2">한국어</span></span> <?php endif; ?>
                <?php if(check_language_enabled('id_ID',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('id_ID');" class="<?php echo ($lang=='id_ID') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/id_ID.png?v=2" /> <span class="ml-2">Bahasa Indonesia</span></span> <?php endif; ?>
                <?php if(check_language_enabled('it_IT',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('it_IT');" class="<?php echo ($lang=='it_IT') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/it_IT.png?v=2" /> <span class="ml-2">Italiano</span></span> <?php endif; ?>
                <?php if(check_language_enabled('ja_JP',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('ja_JP');" class="<?php echo ($lang=='ja_JP') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/ja_JP.png?v=2" /> <span class="ml-2">日本語</span></span> <?php endif; ?>
                <?php if(check_language_enabled('fa_IR',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('fa_IR');" class="<?php echo ($lang=='fa_IR') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/fa_IR.png?v=2" /> <span class="ml-2">فارسی</span></span> <?php endif; ?>
                <?php if(check_language_enabled('pl_PL',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('pl_PL');" class="<?php echo ($lang=='pl_PL') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/pl_PL.png?v=2" /> <span class="ml-2">Polski</span></span> <?php endif; ?>
                <?php if(check_language_enabled('pt_BR',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('pt_BR');" class="<?php echo ($lang=='pt_BR') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/pt_BR.png?v=2" /> <span class="ml-2">Português Brasileiro</span></span> <?php endif; ?>
                <?php if(check_language_enabled('pt_PT',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('pt_PT');" class="<?php echo ($lang=='pt_PT') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/pt_PT.png?v=2" /> <span class="ml-2">Português Europeu</span></span> <?php endif; ?>
                <?php if(check_language_enabled('es_ES',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('es_ES');" class="<?php echo ($lang=='es_ES') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/es_ES.png?v=2" /> <span class="ml-2">Español</span></span> <?php endif; ?>
                <?php if(check_language_enabled('ro_RO',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('ro_RO');" class="<?php echo ($lang=='ro_RO') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/ro_RO.png?v=2" /> <span class="ml-2">Română</span></span> <?php endif; ?>
                <?php if(check_language_enabled('ru_RU',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('ru_RU');" class="<?php echo ($lang=='ru_RU') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/ru_RU.png?v=2" /> <span class="ml-2">Русский</span></span> <?php endif; ?>
                <?php if(check_language_enabled('sv_SE',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('sv_SE');" class="<?php echo ($lang=='sv_SE') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/sv_SE.png?v=2" /> <span class="ml-2">Svenska</span></span> <?php endif; ?>
                <?php if(check_language_enabled('tg_TJ',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('tg_TJ');" class="<?php echo ($lang=='tg_TJ') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/tg_TJ.png?v=2" /> <span class="ml-2">Тоҷикӣ</span></span> <?php endif; ?>
                <?php if(check_language_enabled('th_TH',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('th_TH');" class="<?php echo ($lang=='th_TH') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/th_TH.png?v=2" /> <span class="ml-2">ไทย</span></span> <?php endif; ?>
                <?php if(check_language_enabled('tr_TR',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('tr_TR');" class="<?php echo ($lang=='tr_TR') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/tr_TR.png?v=2" /> <span class="ml-2">Türkçe</span></span> <?php endif; ?>
                <?php if(check_language_enabled('vi_VN',$settings['languages_enabled'])) : ?> <span style="cursor: pointer;" onclick="switch_language('vi_VN');" class="<?php echo ($lang=='vi_VN') ? 'lang_active' : ''; ?> noselect dropdown-item align-middle"><img class="mb-1" src="img/flags_lang/vi_VN.png?v=2" /> <span class="ml-2">Tiếng Việt</span></span> <?php endif; ?>
            </div>
        </li>
        <?php if($settings['dark_mode']==1) : ?>
        <li class="nav-item dropdown no-arrow dark_mode_switcher ml-2">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i onclick="switch_dark_mode();" id='btn_light_mode' style="opacity:0;" class='btn_mode_switcher fas fa-sun'></i>
                <script>
                    if (localStorage.getItem("dark_mode") === null) {
                        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            $('.btn_mode_switcher').attr('id','btn_dark_mode');
                            $('.btn_mode_switcher').removeClass('fa-sun').addClass('fa-moon');
                        }
                    } else {
                        if(localStorage.getItem('dark_mode')==1) {
                            $('.btn_mode_switcher').attr('id','btn_dark_mode');
                            $('.btn_mode_switcher').removeClass('fa-sun').addClass('fa-moon');
                        }
                    }
                    $('.btn_mode_switcher').css('opacity',1);
                </script>
            </a>
        </li>
        <?php endif; ?>
        <div class="topbar-divider d-none d-sm-block"></div>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $user_info['username']; ?></span>&nbsp;
                <img class="img-profile rounded-circle" src="<?php echo $user_info['avatar']; ?>">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <?php if(!empty($settings['help_url'])) : ?>
                    <a class="dropdown-item" target="_blank" href="<?php echo $settings['help_url']; ?>">
                        <i class="fas fa-question fa-sm fa-fw mr-2 text-gray-400"></i>
                        <?php echo _("Help"); ?>
                    </a>
                <?php endif; ?>
                <a class="dropdown-item" href="index.php?p=edit_profile">
                    <i class="fas fa-lock fa-sm fa-fw mr-2 text-gray-400"></i>
                    <?php echo _("Edit profile"); ?>
                </a>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    <?php echo _("Logout"); ?>
                </a>
            </div>
        </li>
    </ul>
</nav>
<?php echo $sub_header; ?>
<div class="mb-3"></div>
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Ready to Leave?"); ?></h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body"><?php echo _("Select Logout below if you are ready to end your current session."); ?></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
                <button class="btn btn-primary" onclick="logout();"><?php echo _("Logout"); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
    $("#sidebarToggleTop").click(function(){
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
    $('#virtualtour_selector').on('shown.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        var w_b = $('.vt_selector_btn').outerWidth();
        var w_d = $('.bootstrap-select.show .dropdown-menu').outerWidth();
        var left = (((w_d - w_b)/2)-20)*-1;
        $('.bootstrap-select .dropdown-menu').css('opacity',0);
        setTimeout(function () {
            $('.bootstrap-select .dropdown-menu').css('left',left+'px');
            $('.bootstrap-select .dropdown-menu').css('opacity',1);
        },100);
    });
    $('#virtualtour_selector').on('rendered.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        $('#loading_header').hide();
        setTimeout(function() {
            $('#virtualtour_selector').css('opacity',1);
        },50);
        setTimeout(function() {
            $('.vt_select_header .quick_action').css('opacity',1);
        },100);
    });
    $(window).resize(function () {
        if($('.bootstrap-select .dropdown-menu').is(':visible')) {
            $('.bootstrap-select .dropdown-menu').css('opacity',0);
            setTimeout(function() {
                var w_b = $('.vt_selector_btn').outerWidth();
                var w_d = $('.bootstrap-select.show .dropdown-menu').outerWidth();
                var left = (((w_d - w_b)/2)-20)*-1;
                $('.bootstrap-select .dropdown-menu').css('left',left+'px');
                $('.bootstrap-select .dropdown-menu').css('opacity',1);
            },10);
        }
    });
</script>