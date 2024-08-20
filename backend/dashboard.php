<?php
session_start();
$id_user = $_SESSION['id_user'];
$settings = get_settings();
if(!empty($settings['welcome_msg'])) {
    $welcome_msg = $settings['welcome_msg'];
} else {
    $welcome_msg = sprintf(_('Welcome to %s configuration panel, where you can create your virtual tours in a few simple steps.'),$settings['name']);
}
$can_create = check_plan('virtual_tour',$id_user,$_SESSION['id_virtualtour_sel']);
$virtual_tours = get_virtual_tours($id_user,"no");
$count_virtual_tours = count($virtual_tours);
if(get_customers_count()>0 && $user_info['role']=='administrator') {
    $col_d="6";
    $users_stats = true;
} else {
    $col_d="12";
    $users_stats = false;
}
?>

<?php include("check_plan.php"); ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-12">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-smile"></i> <?php echo _("Welcome"); ?></h6>
            </div>
            <div class="card-body welcome_message">
                <span><?php echo $welcome_msg ?></span>
            </div>
        </div>
    </div>
    <?php if($settings['enable_wizard'] && $can_create && ($user_info['role']!='editor') && ($create_content)) : ?>
    <div class="col-md-12 mb-4">
        <a href="index.php?p=dashboard&wstep=0" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled' : ''; ?>"><i class="fas fa-magic"></i>&nbsp;&nbsp;<?php echo _("START TOUR CREATION WIZARD"); ?></a>
    </div>
    <?php endif; ?>
</div>
<div class="row mb-1 dashboard_stats">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow h-100 p-1 noselect">
            <a style="text-decoration:none;" target="_self" href="index.php?p=virtual_tours">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold blu_color text-uppercase mb-1"><?php echo _("Virtual Tours"); ?></div>
                            <div id="num_virtual_tours" class="h5 mb-0 font-weight-bold text-gray-800">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=rooms">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?php echo _("Rooms"); ?></div>
                            <div id="num_rooms" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_rooms" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-vector-square fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=markers">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo _("Markers"); ?></div>
                            <div id="num_markers" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_markers" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-caret-square-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=pois">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo _("POIs"); ?></div>
                            <div id="num_pois" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_pois" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullseye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=measurements">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo _("Measurements"); ?></div>
                            <div id="num_measures" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_measures" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ruler-combined fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=video360">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo _("360 Video Tour"); ?></div>
                            <div id="num_video360" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_video360" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow h-100 p-1 noselect" <?php echo ($count_virtual_tours==0) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
            <a style="text-decoration:none;" target="_self" href="index.php?p=video">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo _("Video Projects"); ?></div>
                            <div id="num_video_projects" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_video_projects" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-film fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow h-100 p-1 noselect" style="cursor:default;pointer-events:none">
            <a style="text-decoration:none;" target="_self" href="#">
                <div class="card-body p-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo _("Slideshows"); ?></div>
                            <div id="num_slideshows" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                            <div id="num_vt_slideshows" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("tours"); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-xl-4 col-md-12 mb-3">
        <div class="card border-left-dark shadow h-100 p-1 noselect" style="cursor: default">
            <div class="card-body p-2">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1"><?php echo _("Disk Space Used"); ?></div>
                        <div id="disk_space_used" class="h5 mb-0 font-weight-bold text-gray-800">
                            <button style="line-height:1;opacity:0" onclick="get_disk_space_stats(null,null);" class="btn btn-sm btn-primary p-1"><i class="fab fa-digital-ocean"></i> <?php echo _("analyze"); ?></button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card border-left-secondary shadow h-100 p-1 noselect" style="cursor: default">
            <div class="card-body p-2">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1"><?php echo _("Total Visitors"); ?></div>
                        <div id="total_visitors" class="h5 mb-0 font-weight-bold text-gray-800">--</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card border-left-dark shadow h-100 p-1 noselect" style="cursor: default">
            <div class="card-body p-2">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1"><?php echo _("Online Visitors"); ?></div>
                        <div id="total_online_visitors" class="h5 mb-0 font-weight-bold text-gray-800">--</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-eye fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-<?php echo $col_d; ?> dashboard_visitors">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-line"></i> <?php echo _("Visitors"); ?></h6>
            </div>
            <div id="list_visitors" class="card-body">
                <i style="display: none;" class="fas fa-circle-notch fa-spin"></i>
                <p class="mb-0" id="no_vt_msg" style="display:none;"><?php echo sprintf(_('No virtual tours created yet. Go to %s and create a new one!'),'<a href="index.php?p=virtual_tours">'._("Virtual Tours").'</a>'); ?></p>
            </div>
        </div>
    </div>
    <?php if($users_stats) : ?>
        <div class="col-md-6 dashboard_customers">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-star"></i> <?php echo _("Subscriptions"); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 col-md-12 col-sm-12 mb-2">
                            <div class="card h-100 p-1 noselect">
                                <a style="text-decoration:none;" target="_self" href="index.php?p=users">
                                    <div class="card-body p-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?php echo _("Active Customers"); ?></div>
                                                <div id="num_customers_active" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                                                <div id="num_customers_total" style="font-size:12px;" class="mb-0 d-inline-block text-gray-800"> / <span>--</span> <?php echo _("users"); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 col-sm-12 mb-2">
                            <div class="card h-100 p-1 noselect">
                                <div class="card-body p-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1"><?php echo _("Last registered on"); ?></div>
                                            <div id="last_registered" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-12 col-sm-12 mb-2">
                            <div class="card h-100 p-1 noselect">
                                <div class="card-body p-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo _("On Free / Trial plans"); ?></div>
                                            <div id="free_plans" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fab fa-creative-commons-zero fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 col-sm-12 mb mb-2">
                            <div class="card h-100 p-1 noselect">
                                <div class="card-body p-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?php echo _("Monthly Recurring Revenue"); ?></div>
                                            <div id="recurring_month" class="h5 mb-0 d-inline-block font-weight-bold text-gray-800">--</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div id="subscriptions_list" class="col-md-12">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.disk_space_ajax = null;
        $(document).ready(function () {
            get_dashboard_stats(null);
            if($('.dashboard_customers').length) {
                get_customers_stats();
            }
            setInterval(function () {
                get_dashboard_stats(null);
                if($('.dashboard_customers').length) {
                    get_customers_stats();
                }
            },10 * 1000);
        });
    })(jQuery); // End of use strict
</script>