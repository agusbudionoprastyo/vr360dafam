<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
$user_info = get_user_info($_SESSION['id_user']);
$role = $user_info['role'];
$settings = get_settings();
$voice_commands = get_voice_commands();
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$callback_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","backend/social_auth.php",$_SERVER['SCRIPT_NAME']);
$domain = $_SERVER['SERVER_NAME'];
$cronjob_dir = str_replace("/backend","/services/cron.php",dirname(__FILE__));
$license_tab = 0;
$_SESSION['input_license']=0;
require_once("../config/config.inc.php");
if (defined('HIDE_SVT')) {
    $hide_svt = HIDE_SVT;
} else {
    $hide_svt = false;
}
$s3_tour_count = get_s3_tour_count();
?>

<?php if($role!='administrator' || !$user_info['super_admin']): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
    <script>$('.vt_select_header .btn').remove()</script>
<?php die(); endif; ?>

<?php if($_SESSION['input_license']==1) : ?>
    <div class="card bg-warning text-white shadow mb-3">
        <div class="card-body">
            <?php echo _("Please enter a valid purchase code to continue using the application."); ?>
        </div>
    </div>
<?php endif; ?>

<ul class="nav bg-white nav-pills nav-fill mb-2 <?php echo ($_SESSION['input_license']==1) ? 'd-none' : ''; ?>">
    <?php if(!$demo) : ?>
    <li class="nav-item">
        <a class="nav-link <?php echo ($license_tab==1) ? 'active' : ''; ?>" data-toggle="pill" href="#license_tab"><i class="fas fa-key"></i> <?php echo strtoupper(_("LICENSE")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#requirements_tab"><i class="fas fa-info-circle"></i> <?php echo strtoupper(_("REQUIREMENTS")); ?></a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link <?php echo ($_SESSION['input_license']==0 && $license_tab==0) ? 'active' : ''; ?>" data-toggle="pill" href="#settings_tab"><i class="fas fa-cogs"></i> <?php echo strtoupper(_("GENERAL")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#localization_tab"><i class="fas fa-language"></i> <?php echo strtoupper(_("LOCALIZATION")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#whitelabel_tab"><i class="fas fa-palette"></i> <?php echo strtoupper(_("STYLE")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#style_tab"><i class="fas fa-file-code"></i> <?php echo strtoupper(_("CUSTOM CSS / JS")); ?></a>
    </li>
    <li class="nav-item <?php echo ($hide_svt) ? 'd-none' : ''; ?>">
        <a class="nav-link" data-toggle="pill" href="#tools_tab"><i class="fas fa-tools"></i> <?php echo strtoupper(_("TOOLS")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#storage_tab"><i class="fas fa-hdd"></i> <?php echo strtoupper(_("STORAGE")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#voice_commands_tab"><i class="fas fa-microphone"></i> <?php echo strtoupper(_("VOICE COMMANDS")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#vr_tab"><i class="fas fa-vr-cardboard"></i> VR</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#categories_tab"><i class="fas fa-th-list"></i> <?php echo strtoupper(_("CATEGORIES")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#mail_tab"><i class="fas fa-mail-bulk"></i> <?php echo strtoupper(_("MAIL / NOTIFICATION")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#social_tab"><i class="fas fa-comments"></i> <?php echo strtoupper(_("SOCIAL")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#legal_tab"><i class="fas fa-balance-scale"></i> <?php echo strtoupper(_("LEGAL")); ?></a>
    </li>
    <li id="registration_li" class="nav-item d-none">
        <a class="nav-link" data-toggle="pill" href="#registration_tab"><i class="fas fa-registered"></i> <?php echo strtoupper(_("REGISTRATION")); ?></a>
    </li>
    <li id="payments_li" class="nav-item d-none">
        <a class="nav-link" data-toggle="pill" href="#payments_tab"><i class="far fa-credit-card"></i> <?php echo strtoupper(_("PAYMENTS")); ?></a>
    </li>
    <li id="api_li" class="nav-item">
        <a class="nav-link" href="../api/documentation/index.php" target="_blank"><i class="far fa-code-branch"></i> API</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane <?php echo ($_SESSION['input_license']==1 || $license_tab==1) ? 'active' : ''; ?>" id="license_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-key"></i> <?php echo _("License"); ?> <i style="font-size:12px;color:black;font-weight:normal" class="server_info"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_code"><?php echo _("Purchase Code"); ?> </label>
                                    <input type="text" class="form-control" id="purchase_code" value="*******************" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label><?php echo _("Status"); ?></label><br>
                                <div id="license_status" class="mt-2">
                                    <?php
										echo "<div style=\"color: green;\" class=\"fas fa-circle\"></div> Valid, Extended License (SaaS)";
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="upgrade_extended" class="col-md-12 d-none">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header text-white bg-success">
                        <h6 class="m-0 mt-1 font-weight-bold"><i class="fas fa-shopping-cart"></i> <?php echo _("SaaS Plugin"); ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo _("Unlock Multi Users, Plans (subscriptions), Payments features! After purchasing the plugin enter the purchase code above."); ?></p>
                        <a href="https://1.envato.market/9W0JVQ" target="_blank" class="btn btn-block text-success btn-light"><i class="fas fa-external-link-alt"></i>&nbsp;&nbsp;<?php echo _("BUY THE PLUGIN NOW"); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="storage_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cloud"></i> <?php echo _("Remote Storage"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_enabled"><?php echo _("Enable"); ?> <i title="<?php echo _("enable this remote storage (you need to initialize first)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['aws_s3_enabled']==0)?'disabled':''; ?> type="checkbox" id="aws_s3_enabled" <?php echo ($settings['aws_s3_enabled'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_vt_auto"><?php echo _("Use automatically"); ?> <i title="<?php echo _("newly created tours will automatically be stored on this remote storage"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="aws_s3_vt_auto" <?php echo ($settings['aws_s3_vt_auto'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_type"><?php echo _("Provider"); ?></label>
                                    <select onchange="change_aws_s3_type();" class="form-control" id="aws_s3_type">
                                        <option <?php echo ($settings['aws_s3_type']=='aws') ? 'selected' : ''; ?> id="aws">AWS S3</option>
                                        <option <?php echo ($settings['aws_s3_type']=='r2') ? 'selected' : ''; ?> id="r2">Cloudflare R2</option>
                                        <option <?php echo ($settings['aws_s3_type']=='digitalocean') ? 'selected' : ''; ?> id="digitalocean">Digital Ocean Spaces</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_region"><?php echo _("Region"); ?></label>
                                    <select onchange="change_aws_s3_region();" class="form-control <?php echo ($settings['aws_s3_type']=='aws') ? '' : 'd-none' ; ?>" id="aws_s3_region">
                                        <option <?php echo ($settings['aws_s3_region']=='us-east-2') ? 'selected' : ''; ?> id="us-east-2">US East (Ohio)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='us-east-1') ? 'selected' : ''; ?> id="us-east-1">US East (N. Virginia)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='us-west-1') ? 'selected' : ''; ?> id="us-west-1">US West (N. California)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='us-west-2') ? 'selected' : ''; ?> id="us-west-2">US West (Oregon)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='af-south-1') ? 'selected' : ''; ?> id="af-south-1">Africa (Cape Town)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-east-1') ? 'selected' : ''; ?> id="ap-east-1">Asia Pacific (Hong Kong)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-south-2') ? 'selected' : ''; ?> id="ap-south-2">Asia Pacific (Hyderabad)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-southeast-3') ? 'selected' : ''; ?> id="ap-southeast-3">Asia Pacific (Jakarta)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-southeast-4') ? 'selected' : ''; ?> id="ap-southeast-4">Asia Pacific (Melbourne)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-south-1') ? 'selected' : ''; ?> id="ap-south-1">Asia Pacific (Mumbai)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-northeast-3') ? 'selected' : ''; ?> id="ap-northeast-3">Asia Pacific (Osaka)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-northeast-2') ? 'selected' : ''; ?> id="ap-northeast-2">Asia Pacific (Seoul)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-southeast-1') ? 'selected' : ''; ?> id="ap-southeast-1">Asia Pacific (Singapore)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-southeast-2') ? 'selected' : ''; ?> id="ap-southeast-2">Asia Pacific (Sydney)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ap-northeast-1') ? 'selected' : ''; ?> id="ap-northeast-1">Asia Pacific (Tokyo)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ca-central-1') ? 'selected' : ''; ?> id="ca-central-1">Canada (Central)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='cn-north-1') ? 'selected' : ''; ?> id="cn-north-1">China (Beijing)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='cn-northwest-1') ? 'selected' : ''; ?> id="cn-northwest-1">China (Ningxia)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-central-1') ? 'selected' : ''; ?> id="eu-central-1">Europe (Frankfurt)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-west-1') ? 'selected' : ''; ?> id="eu-west-1">Europe (Ireland)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-west-2') ? 'selected' : ''; ?> id="eu-west-2">Europe (London)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-south-1') ? 'selected' : ''; ?> id="eu-south-1">Europe (Milan)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-west-3') ? 'selected' : ''; ?> id="eu-west-3">Europe (Paris)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-north-1') ? 'selected' : ''; ?> id="eu-north-1">Europe (Stockholm)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-south-2') ? 'selected' : ''; ?> id="eu-south-2">Europe (Spain)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='eu-central-2') ? 'selected' : ''; ?> id="eu-central-2">Europe (Zurich)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='me-south-1') ? 'selected' : ''; ?> id="me-south-1">Middle East (Bahrain)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='me-central-1') ? 'selected' : ''; ?> id="me-central-1">Middle East (UAE)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='sa-east-1') ? 'selected' : ''; ?> id="sa-east-1">South America (São Paulo)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='us-gov-east-1') ? 'selected' : ''; ?> id="us-gov-east-1">AWS GovCloud (US-East)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='us-gov-west-1') ? 'selected' : ''; ?> id="us-gov-west-1">AWS GovCloud (US-West)</option>
                                    </select>
                                    <select disabled onchange="change_aws_s3_region();" class="form-control <?php echo ($settings['aws_s3_type']=='r2') ? '' : 'd-none' ; ?>" id="aws_s3_region_r2">
                                        <option selected id="auto">Auto</option>
                                    </select>
                                    <select onchange="change_aws_s3_region();" class="form-control <?php echo ($settings['aws_s3_type']=='digitalocean') ? '' : 'd-none' ; ?>" id="aws_s3_region_digitalocean">
                                        <option <?php echo ($settings['aws_s3_region']=='nyc3') ? 'selected' : ''; ?> id="nyc3">US NYC3 (New York)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='sfo3') ? 'selected' : ''; ?> id="sfo3">US SFO3 (San Francisco)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='ams3') ? 'selected' : ''; ?> id="ams3">EU AMS3 (Amsterdam)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='fra1') ? 'selected' : ''; ?> id="fra1">EU FRA1 (Frankfurt)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='sgp1') ? 'selected' : ''; ?> id="sgp1">AP SGP1 (Singapore)</option>
                                        <option <?php echo ($settings['aws_s3_region']=='syd1') ? 'selected' : ''; ?> id="syd1">AP SYD1 (Sydney)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_bucket"><?php echo _("Bucket name"); ?> <i title="<?php echo _("the name must be unique and without special characters. Do not use the same bucket name for different installations."); ?>" class="help_t fas fa-info-circle"></i></label>
                                    <input class="form-control" type="text" id="aws_s3_bucket" value="<?php echo $settings['aws_s3_bucket']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_key"><?php echo _("Key"); ?></label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="aws_s3_key" value="<?php echo ($settings['aws_s3_key']!='') ? 'keep_aws_s3_key' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_secret"><?php echo _("Secret"); ?></label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="aws_s3_secret" value="<?php echo ($settings['aws_s3_secret']!='') ? 'keep_aws_s3_secret' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_accountid"><?php echo _("Account ID"); ?></label>
                                    <input <?php echo ($settings['aws_s3_type']!='r2') ? 'disabled' : '' ; ?> class="form-control" type="text" id="aws_s3_accountid" value="<?php echo $settings['aws_s3_accountid']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aws_s3_custom_domain"><?php echo _("Custom domain / CDN"); ?></label>
                                    <input class="form-control" type="text" id="aws_s3_custom_domain" value="<?php echo $settings['aws_s3_custom_domain']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label style="opacity:0;">.</label><br>
                                    <button onclick="aws_s3_initialize();" id="btn_check_aws" class="btn btn-block btn-primary <?php echo ($s3_tour_count==0) ? '' : 'disabled'; ?> <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Initialize"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="opacity:0;">.</label><br>
                                    <span class="<?php echo ($s3_tour_count==0) ? 'd-none' : ''; ?>"><i class="fas fa-info-circle"></i> <?php echo sprintf(_("You have %s tours in remote storage, to change settings you need to move them locally first!"),$s3_tour_count); ?></span>
                                </div>
                            </div>
                        </div>
                        <div id="aws_instructions" class="col-md-12 pl-0 pr-0 <?php echo ($settings['aws_s3_type']=='aws') ? '' : 'd-none' ; ?>">
                            1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://console.aws.amazon.com/console/home?nc2=h_ct&src=header-signin">Amazon AWS <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                            2) <?php echo _("From the profile menu, go to the <b>Security Credentials</b> section."); ?><br>
                            3) <?php echo _("On the <b>Access Keys</b> section click on <b>Create new access key</b> button."); ?><br>
                            4) <?php echo _("Copy the <b>Access Key</b> and <b>Secret Key</b> in the fields above and click <b>Initialize</b>."); ?><br>
                        </div>
                        <div id="r2_instructions" class="col-md-12 pl-0 pr-0 <?php echo ($settings['aws_s3_type']=='r2') ? '' : 'd-none' ; ?>">
                            1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://dash.cloudflare.com/login">Cloudflare <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                            2) <?php echo _("From the menu bar, go to the <b>R2 - Overview</b> section."); ?><br>
                            3) <?php echo _("Copy the <b>ID Account</b> in the field above and click on <b>Manage R2 API Tokens</b>."); ?><br>
                            4) <?php echo _("Click on <b>Create API Token</b>, set Permissions to <b>Edit</b> and click <b>Create</b> button."); ?><br>
                            5) <?php echo _("Copy the <b>Access Key ID</b> and <b>Secret Access Key</b> in the fields above and click <b>Initialize</b>."); ?><br>
                            6) <?php echo _("Once the initialization complete, return to <b>Cloudflare - R2</b> and click into your <b>bucket</b> name."); ?><br>
                            7) <?php echo _("Click the <b>Settings</b> tab and under <b>Public Access</b> click <b>Connect Domain</b> and follow the onscreen instructions."); ?><br>
                            8) <?php echo _("Insert the <b>Custom domain</b> connected in the field above."); ?><br>
                        </div>
                        <div id="digitalocean_instructions" class="col-md-12 pl-0 pr-0 <?php echo ($settings['aws_s3_type']=='digitalocean') ? '' : 'd-none' ; ?>">
                            1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://cloud.digitalocean.com/login">Digital Ocean <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                            2) <?php echo _("From the menu bar, go to the <b>Manage - API</b> section, click on tab <b>Spaces Keys</b> and click on <b>Generate New Key</b>."); ?><br>
                            3) <?php echo _("Copy the <b>Key</b> and <b>Secret</b> in the fields above and click <b>Initialize</b>."); ?><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="tools_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-tools"></i> <?php echo _("Tools"); ?> <i title="<?php echo _("if you want to use these services externally you have to copy the tools folder on the remote server and change the url"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label><?php echo _("Multiresolution"); ?> <i title="<?php echo _("check if your system can generate multi resolution panoramas"); ?>" class="help_t fas fa-question-circle"></i></label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <select onchange="change_multires();" class="form-control" id="multires">
                                        <option <?php echo ($settings['multires']=='local') ? 'selected' : ''; ?> id="local"><?php echo _("Local"); ?></option>
                                        <option <?php echo ($settings['multires']=='cloud') ? 'selected' : ''; ?> id="cloud"><?php echo _("External"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input <?php echo ($settings['multires']=='local') ? 'disabled' : ''; ?> type="text" id="multires_cloud_url" class="form-control" value="<?php echo $settings['multires_cloud_url']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <button id="btn_check_multires_req" onclick="check_multires_req();" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Check Requirements"); ?></button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <label><?php echo _("360 Video Tour"); ?> <i title="<?php echo _("check if your system can generate video 360"); ?>" class="help_t fas fa-question-circle"></i></label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <select onchange="change_video360();" class="form-control" id="video360">
                                        <option <?php echo ($settings['video360']=='local') ? 'selected' : ''; ?> id="local"><?php echo _("Local"); ?></option>
                                        <option <?php echo ($settings['video360']=='cloud') ? 'selected' : ''; ?> id="cloud"><?php echo _("External"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input <?php echo ($settings['video360']=='local') ? 'disabled' : ''; ?> type="text" id="video360_cloud_url" class="form-control" value="<?php echo $settings['video360_cloud_url']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <button id="btn_check_video360_req" onclick="check_video360_req();" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Check Requirements"); ?></button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <label><?php echo _("Slideshow"); ?> <i title="<?php echo _("check if your system can generate slideshow"); ?>" class="help_t fas fa-question-circle"></i></label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <select onchange="change_slideshow();" class="form-control" id="slideshow">
                                        <option <?php echo ($settings['slideshow']=='local') ? 'selected' : ''; ?> id="local"><?php echo _("Local"); ?></option>
                                        <option <?php echo ($settings['slideshow']=='cloud') ? 'selected' : ''; ?> id="cloud"><?php echo _("External"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input <?php echo ($settings['slideshow']=='local') ? 'disabled' : ''; ?> type="text" id="slideshow_cloud_url" class="form-control" value="<?php echo $settings['slideshow_cloud_url']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <button id="btn_check_slideshow_req" onclick="check_slideshow_req();" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Check Requirements"); ?></button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <label><?php echo _("Video Project"); ?> <i title="<?php echo _("check if your system can generate video projects"); ?>" class="help_t fas fa-question-circle"></i></label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <select onchange="change_video_project();" class="form-control" id="video_project">
                                        <option <?php echo ($settings['video_project']=='local') ? 'selected' : ''; ?> id="local"><?php echo _("Local"); ?></option>
                                        <option <?php echo ($settings['video_project']=='cloud') ? 'selected' : ''; ?> id="cloud"><?php echo _("External"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input <?php echo ($settings['video_project']=='local') ? 'disabled' : ''; ?> type="text" id="video_project_url" class="form-control" value="<?php echo $settings['video_project_url']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <button id="btn_check_video_project_req" onclick="check_video_project_req();" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Check Requirements"); ?></button>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-brain"></i> <?php echo _("A.I. Panorama") ?> <i title="<?php echo _("allows to generate room's panorama with Artificial Intelligence (experimental)"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_ai_room"><?php echo _("Enable"); ?></label><br>
                                    <input <?php echo ($settings['enable_ai_room']) ? 'checked':''; ?> type="checkbox" id="enable_ai_room" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="ai_key"><?php echo _("API Key"); ?> (<?php echo sprintf(_("Request it from %s"),'<a class="text-primary" target="_blank" href="https://www.blockadelabs.com">Blockadelabs <i class="fas fa-external-link-square-alt"></i></a>'); ?>)</label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="ai_key" value="<?php echo ($settings['ai_key']!='') ? 'keep_ai_key' : ''; ?>" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-mobile-alt"></i> PWA</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <?php echo _("The resources to make the tours compatible in PWA are generated automatically. If this does not happen, create them manually with this button."); ?>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <button id="btn_generate_favicons" onclick="generate_favicons('manual');" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Generate Manually"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="localization_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-language"></i> <?php echo _("Localization"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="language"><?php echo _("Default Language"); ?></label>
                                    <select class="form-control" id="language">
                                        <option <?php echo ($settings['language']=='ar_SA') ? 'selected':''; ?> id="ar_SA">Arabic (ar_SA)</option>
                                        <option <?php echo ($settings['language']=='zh_CN') ? 'selected':''; ?> id="zh_CN">Chinese simplified (zh_CN)</option>
                                        <option <?php echo ($settings['language']=='zh_HK') ? 'selected':''; ?> id="zh_HK">Chinese traditional (zh_HK)</option>
                                        <option <?php echo ($settings['language']=='zh_TW') ? 'selected':''; ?> id="zh_TW">Chinese traditional (zh_TW)</option>
                                        <option <?php echo ($settings['language']=='cs_CZ') ? 'selected':''; ?> id="cs_CZ">Czech (cs_CZ)</option>
                                        <option <?php echo ($settings['language']=='nl_NL') ? 'selected':''; ?> id="nl_NL">Dutch (nl_NL)</option>
                                        <option <?php echo ($settings['language']=='en_US') ? 'selected':''; ?> id="en_US">English (en_US)</option>
                                        <option <?php echo ($settings['language']=='fil_PH') ? 'selected':''; ?> id="fil_PH">Filipino (fil_PH)</option>
                                        <option <?php echo ($settings['language']=='fr_FR') ? 'selected':''; ?> id="fr_FR">French (fr_FR)</option>
                                        <option <?php echo ($settings['language']=='de_DE') ? 'selected':''; ?> id="de_DE">German (de_DE)</option>
                                        <option <?php echo ($settings['language']=='hi_IN') ? 'selected':''; ?> id="hi_IN">Hindi (hi_IN)</option>
                                        <option <?php echo ($settings['language']=='hu_HU') ? 'selected':''; ?> id="hu_HU">Hungarian (hu_HU)</option>
                                        <option <?php echo ($settings['language']=='rw_RW') ? 'selected':''; ?> id="rw_RW">Kinyarwanda (rw_RW)</option>
                                        <option <?php echo ($settings['language']=='ko_KR') ? 'selected':''; ?> id="ko_KR">Korean (ko_KR)</option>
                                        <option <?php echo ($settings['language']=='id_ID') ? 'selected':''; ?> id="id_ID">Indonesian (id_ID)</option>
                                        <option <?php echo ($settings['language']=='it_IT') ? 'selected':''; ?> id="it_IT">Italian (it_IT)</option>
                                        <option <?php echo ($settings['language']=='ja_JP') ? 'selected':''; ?> id="ja_JP">Japanese (ja_JP)</option>
                                        <option <?php echo ($settings['language']=='fa_IR') ? 'selected':''; ?> id="fa_IR">Persian (fa_IR)</option>
                                        <option <?php echo ($settings['language']=='pl_PL') ? 'selected':''; ?> id="pl_PL">Polish (pl_PL)</option>
                                        <option <?php echo ($settings['language']=='pt_BR') ? 'selected':''; ?> id="pt_BR">Portuguese Brazilian (pt_BR)</option>
                                        <option <?php echo ($settings['language']=='pt_PT') ? 'selected':''; ?> id="pt_PT">Portuguese European (pt_PT)</option>
                                        <option <?php echo ($settings['language']=='es_ES') ? 'selected':''; ?> id="es_ES">Spanish (es_ES)</option>
                                        <option <?php echo ($settings['language']=='ro_RO') ? 'selected':''; ?> id="ro_RO">Romanian (ro_RO)</option>
                                        <option <?php echo ($settings['language']=='ru_RU') ? 'selected':''; ?> id="ru_RU">Russian (ru_RU)</option>
                                        <option <?php echo ($settings['language']=='sv_SE') ? 'selected':''; ?> id="sv_SE">Swedish (sv_SE)</option>
                                        <option <?php echo ($settings['language']=='tg_TJ') ? 'selected':''; ?> id="tg_TJ">Tajik (tg_TJ)</option>
                                        <option <?php echo ($settings['language']=='th_TH') ? 'selected':''; ?> id="th_TH">Thai (th_TH)</option>
                                        <option <?php echo ($settings['language']=='tr_TR') ? 'selected':''; ?> id="tr_TR">Turkish (tr_TR)</option>
                                        <option <?php echo ($settings['language']=='vi_VN') ? 'selected':''; ?> id="vi_VN">Vietnamese (vi_VN)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="language_domain"><?php echo _("Translation Type"); ?></label>
                                    <select class="form-control" id="language_domain">
                                        <option <?php echo ($settings['language_domain']=='default') ? 'selected':''; ?> id="default_lang">Default</option>
                                        <option <?php echo ($settings['language_domain']=='custom') ? 'selected':''; ?> id="custom_lang">Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="languages_enabled"><?php echo _("Languages Enabled (Backend)"); ?></label>
                                    <select style="height: 125px" multiple class="form-control selectpicker" id="languages_enabled" data-actions-box="true" data-selected-text-format="count > 3" data-count-selected-text="{0} <?php echo _("items selected"); ?>" data-deselect-all-text="<?php echo _("Deselect All"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("Nothing selected"); ?>" data-none-results-text="<?php echo _("No results matched"); ?> {0}">
                                        <option <?php echo (check_language_enabled('ar_SA',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_ar_SA">Arabic (ar_SA)</option>
                                        <option <?php echo (check_language_enabled('zh_CN',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_zh_CN">Chinese simplified (zh_CN)</option>
                                        <option <?php echo (check_language_enabled('zh_HK',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_zh_HK">Chinese traditional (zh_HK)</option>
                                        <option <?php echo (check_language_enabled('zh_TW',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_zh_TW">Chinese traditional (zh_TW)</option>
                                        <option <?php echo (check_language_enabled('cs_CZ',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_cs_CZ">Czech (cs_CZ)</option>
                                        <option <?php echo (check_language_enabled('nl_NL',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_nl_NL">Dutch (nl_NL)</option>
                                        <option <?php echo (check_language_enabled('en_US',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_en_US">English (en_US)</option>
                                        <option <?php echo (check_language_enabled('fil_PH',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_fil_PH">Filipino (fil_PH)</option>
                                        <option <?php echo (check_language_enabled('fr_FR',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_fr_FR">French (fr_FR)</option>
                                        <option <?php echo (check_language_enabled('de_DE',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_de_DE">German (de_DE)</option>
                                        <option <?php echo (check_language_enabled('hi_IN',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_hi_IN">Hindi (hi_IN)</option>
                                        <option <?php echo (check_language_enabled('hu_HU',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_hu_HU">Hungarian (hu_HU)</option>
                                        <option <?php echo (check_language_enabled('rw_RW',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_rw_RW">Kinyarwanda (rw_RW)</option>
                                        <option <?php echo (check_language_enabled('ko_KR',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_ko_KR">Korean (ko_KR)</option>
                                        <option <?php echo (check_language_enabled('id_ID',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_id_ID">Indonesian (id_ID)</option>
                                        <option <?php echo (check_language_enabled('it_IT',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_it_IT">Italian (it_IT)</option>
                                        <option <?php echo (check_language_enabled('ja_JP',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_ja_JP">Japanese (ja_JP)</option>
                                        <option <?php echo (check_language_enabled('fa_IR',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_fa_IR">Persian (fa_IR)</option>
                                        <option <?php echo (check_language_enabled('pl_PL',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_pl_PL">Polish (pl_PL)</option>
                                        <option <?php echo (check_language_enabled('pt_BR',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_pt_BR">Portuguese Brazilian (pt_BR)</option>
                                        <option <?php echo (check_language_enabled('pt_PT',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_pt_PT">Portuguese European (pt_PT)</option>
                                        <option <?php echo (check_language_enabled('es_ES',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_es_ES">Spanish (es_ES)</option>
                                        <option <?php echo (check_language_enabled('ro_RO',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_ro_RO">Romanian (ro_RO)</option>
                                        <option <?php echo (check_language_enabled('ru_RU',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_ru_RU">Russian (ru_RU)</option>
                                        <option <?php echo (check_language_enabled('sv_SE',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_sv_SE">Swedish (sv_SE)</option>
                                        <option <?php echo (check_language_enabled('tg_TJ',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_tg_TJ">Tajik (tg_TJ)</option>
                                        <option <?php echo (check_language_enabled('th_TH',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_th_TH">Thai (th_TH)</option>
                                        <option <?php echo (check_language_enabled('tr_TR',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_tr_TR">Turkish (tr_TR)</option>
                                        <option <?php echo (check_language_enabled('vi_VN',$settings['languages_enabled'])) ? 'selected':''; ?> id="ls_vi_VN">Vietnamese (vi_VN)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="languages_viewer_enabled"><?php echo _("Languages Enabled (Tours)"); ?></label>
                                    <select style="height: 125px" multiple class="form-control selectpicker" id="languages_viewer_enabled" data-actions-box="true" data-selected-text-format="count > 3" data-count-selected-text="{0} <?php echo _("items selected"); ?>" data-deselect-all-text="<?php echo _("Deselect All"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("Nothing selected"); ?>" data-none-results-text="<?php echo _("No results matched"); ?> {0}">
                                        <option <?php echo (check_language_enabled_viewer('ar_SA',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_ar_SA">Arabic (ar_SA)</option>
                                        <option <?php echo (check_language_enabled_viewer('zh_CN',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_zh_CN">Chinese simplified (zh_CN)</option>
                                        <option <?php echo (check_language_enabled_viewer('zh_HK',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_zh_HK">Chinese traditional (zh_HK)</option>
                                        <option <?php echo (check_language_enabled_viewer('zh_TW',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_zh_TW">Chinese traditional (zh_TW)</option>
                                        <option <?php echo (check_language_enabled_viewer('cs_CZ',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_cs_CZ">Czech (cs_CZ)</option>
                                        <option <?php echo (check_language_enabled_viewer('nl_NL',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_nl_NL">Dutch (nl_NL)</option>
                                        <option <?php echo (check_language_enabled_viewer('en_US',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_en_US">English (en_US)</option>
                                        <option <?php echo (check_language_enabled_viewer('fil_PH',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_fil_PH">Filipino (fil_PH)</option>
                                        <option <?php echo (check_language_enabled_viewer('fr_FR',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_fr_FR">French (fr_FR)</option>
                                        <option <?php echo (check_language_enabled_viewer('de_DE',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_de_DE">German (de_DE)</option>
                                        <option <?php echo (check_language_enabled_viewer('hi_IN',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_hi_IN">Hindi (hi_IN)</option>
                                        <option <?php echo (check_language_enabled_viewer('hu_HU',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_hu_HU">Hungarian (hu_HU)</option>
                                        <option <?php echo (check_language_enabled_viewer('rw_RW',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_rw_RW">Kinyarwanda (rw_RW)</option>
                                        <option <?php echo (check_language_enabled_viewer('ko_KR',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_ko_KR">Korean (ko_KR)</option>
                                        <option <?php echo (check_language_enabled_viewer('id_ID',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_id_ID">Indonesian (id_ID)</option>
                                        <option <?php echo (check_language_enabled_viewer('it_IT',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_it_IT">Italian (it_IT)</option>
                                        <option <?php echo (check_language_enabled_viewer('ja_JP',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_ja_JP">Japanese (ja_JP)</option>
                                        <option <?php echo (check_language_enabled_viewer('fa_IR',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_fa_IR">Persian (fa_IR)</option>
                                        <option <?php echo (check_language_enabled_viewer('pl_PL',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_pl_PL">Polish (pl_PL)</option>
                                        <option <?php echo (check_language_enabled_viewer('pt_BR',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_pt_BR">Portuguese Brazilian (pt_BR)</option>
                                        <option <?php echo (check_language_enabled_viewer('pt_PT',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_pt_PT">Portuguese European (pt_PT)</option>
                                        <option <?php echo (check_language_enabled_viewer('es_ES',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_es_ES">Spanish (es_ES)</option>
                                        <option <?php echo (check_language_enabled_viewer('ro_RO',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_ro_RO">Romanian (ro_RO)</option>
                                        <option <?php echo (check_language_enabled_viewer('ru_RU',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_ru_RU">Russian (ru_RU)</option>
                                        <option <?php echo (check_language_enabled_viewer('sv_SE',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_sv_SE">Swedish (sv_SE)</option>
                                        <option <?php echo (check_language_enabled_viewer('tg_TJ',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_tg_TJ">Tajik (tg_TJ)</option>
                                        <option <?php echo (check_language_enabled_viewer('th_TH',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_th_TH">Thai (th_TH)</option>
                                        <option <?php echo (check_language_enabled_viewer('tr_TR',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_tr_TR">Turkish (tr_TR)</option>
                                        <option <?php echo (check_language_enabled_viewer('vi_VN',$settings['languages_viewer_enabled'])) ? 'selected':''; ?> id="lv_vi_VN">Vietnamese (vi_VN)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <p>
                                    If you want to edit translation file you need to follow this instructions:<br>
                                    1) NEW CUSTOM TRANSLATION: Copy the file <i>locale/lang_code/LC_MESSAGES/<b>default.po</b></i> to your computer and rename it to <b>custom.po</b><br>
                                    or<br>
                                    1) EXISTING CUSTOM TRANSLATION: Execute this command <b>msgmerge --update locale/lang_code/LC_MESSAGES/custom.po locale/svt.pot</b> to merge the new strings with your existing <b>custom.po</b> translation file<br>
                                    2) Edit the file <b>custom.po</b> with a text editor or with a POEditor like <a target="_blank" href="https://poedit.net/">this one</a><br>
                                    3) Compile and generate the file <b>custom.mo</b> with the POEditor or with this command <b>msgfmt custom.po --output-file=custom.mo</b><br>
                                    4) Copy the files <b>custom.po</b> and <b>custom.mo</b> to <i>locale/lang_code/LC_MESSAGES/</i><br>
                                    5) Change Translation Type to <b>Custom</b><br>
                                </p>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-globe"></i> DeepL <?php echo _("Translation") ?> <i title="<?php echo _("enables the ability to translate of tour contents via translation API"); ?>" class="help_t fas fa-question-circle"></i></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_deepl"><?php echo _("Enable"); ?></label><br>
                                    <input <?php echo ($settings['enable_deepl']) ? 'checked':''; ?> type="checkbox" id="enable_deepl" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="deepl_api_key"><?php echo _("API Key"); ?> (<?php echo sprintf(_("Request it from %s"),'<a class="text-primary" target="_blank" href="https://www.deepl.com/pro-api?cta=header-pro-api">DeepL <i class="fas fa-external-link-square-alt"></i></a>'); ?>)</label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="deepl_api_key" value="<?php echo ($settings['deepl_api_key']!='') ? 'keep_deepl_api_key' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="requirements_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <?php $_SESSION['pass_req']='svt'; ?>
                <iframe id="req_iframe" src="../requirements.php" frameborder="0" style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:100vh;width:100%;position:absolute;top:0px;left:0px;right:0px;bottom:0px" height="100%" width="100%"></iframe>
            </div>
        </div>
    </div>
    <div class="tab-pane <?php echo ($_SESSION['input_license']==0 && $license_tab==0) ? 'active' : ''; ?>" id="settings_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cog"></i> <?php echo _("Misc"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website_url"><?php echo _("Main Website"); ?> - <?php echo _("URL"); ?> <i title="<?php echo _("if set it put a return to website link into the login page"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="text" class="form-control" id="website_url" value="<?php echo $settings['website_url']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website_name"><?php echo _("Main Website"); ?> - <?php echo _("Name"); ?></label>
                                    <input type="text" class="form-control" id="website_name" value="<?php echo htmlspecialchars($settings['website_name']); ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_mail"><?php echo _("Contact E-Mail"); ?></label>
                                    <input type="text" class="form-control" id="contact_mail" value="<?php echo $settings['contact_email']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="help_url"><?php echo _("Help Link"); ?> <i title="<?php echo _("if set it put an help link into top right profile user menu"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="text" class="form-control" id="help_url" value="<?php echo $settings['help_url']; ?>" />
                                </div>
                            </div>
                            <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                            <div class="col-md-6">
                                <label for="share_providers"><?php echo _("Share Providers"); ?></label><br>
                                <select id="share_providers" data-iconBase="fa" data-tickIcon="fa-check" data-actions-box="true" data-selected-text-format="count > 8" data-count-selected-text="{0} <?php echo _("items selected"); ?>" data-deselect-all-text="<?php echo _("Deselect All"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("Nothing selected"); ?>" data-none-results-text="<?php echo _("No results matched"); ?> {0}" class="form-control selectpicker" multiple>
                                    <option data-icon="fas fa-envelope" id="provider_email" <?php echo (in_array('email',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Email"); ?></option>
                                    <option data-icon="fab fa-whatsapp" id="provider_whatsapp" <?php echo (in_array('whatsapp',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Whatsapp"); ?></option>
                                    <option data-icon="fab fa-facebook" id="provider_facebook" <?php echo (in_array('facebook',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Facebook"); ?></option>
                                    <option data-icon="fab fa-twitter" id="provider_twitter" <?php echo (in_array('twitter',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Twitter"); ?></option>
                                    <option data-icon="fab fa-linkedin" id="provider_linkedin" <?php echo (in_array('linkedin',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Linkedin"); ?></option>
                                    <option data-icon="fab fa-telegram" id="provider_telegram" <?php echo (in_array('telegram',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Telegram"); ?></option>
                                    <option data-icon="fab fa-facebook-messenger" id="provider_facebook_messenger" <?php echo (in_array('facebook_messenger',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Facebook Messenger"); ?></option>
                                    <option data-icon="fab fa-pinterest" id="provider_pinterest" <?php echo (in_array('pinterest',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Pinterest"); ?></option>
                                    <option data-icon="fab fa-reddit" id="provider_reddit" <?php echo (in_array('reddit',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Reddit"); ?></option>
                                    <option data-icon="fab fa-line" id="provider_line" <?php echo (in_array('line',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Line"); ?></option>
                                    <option data-icon="fab fa-viber" id="provider_viber" <?php echo (in_array('viber',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Viber"); ?></option>
                                    <option data-icon="fab fa-vk" id="provider_vk" <?php echo (in_array('vk',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("VK"); ?></option>
                                    <option data-icon="fab fa-qq" id="provider_qzone" <?php echo (in_array('qzone',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Qzone"); ?></option>
                                    <option data-icon="fab fa-weixin" id="provider_wechat" <?php echo (in_array('wechat',$array_share_providers) ? 'selected' : ''); ?>><?php echo _("Wechat"); ?></option>
                                </select>
                                <script type="text/javascript">$('#share_providers').selectpicker('render');</script>
                            </div>
                            <div class="col-md-6">
                                <label for="font_provider"><?php echo _("Font Provider"); ?></label><br>
                                <select id="font_provider" class="form-control" onchange="change_font_provider();">
                                    <option id="systems" <?php echo ($settings['font_provider']=='systems') ? 'selected' : ''; ?>>System Fonts</option>
                                    <option id="google" <?php echo ($settings['font_provider']=='google') ? 'selected' : ''; ?>>Google Fonts</option>
                                    <option id="collabs" <?php echo ($settings['font_provider']=='collabs') ? 'selected' : ''; ?>>CoolLabs Fonts</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-group">
                                    <label for="furl_blacklist"><?php echo _("Friendly Urls Blacklist"); ?></label>
                                    <input type="text" class="form-control" id="furl_blacklist" placeholder="<?php echo _("Enter friendly urls separated by comma"); ?>" value="<?php echo $settings['furl_blacklist']; ?>" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shield-alt"></i> <?php echo _("Maintenance Mode"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="maintenance_backend"><?php echo _("Maintenance Backend"); ?> <i title="<?php echo _("set the backend to maintenance mode and only the maintenance IP will be able to access"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['maintenance_backend']) ? 'checked':''; ?> type="checkbox" data-toggle="toggle" data-onstyle="danger" data-offstyle="secondary" data-size="normal" data-on="<?php echo _("Activated"); ?>" data-off="<?php echo _("Deactivated"); ?>" id="maintenance_backend" />
                                </div>
                                <script>$('#maintenance_backend').bootstrapToggle();</script>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="maintenance_viewer"><?php echo _("Maintenance Viewer"); ?> <i title="<?php echo _("set the viewer to maintenance mode (all tours will be inaccessible) and only the maintenance IP will be able to access"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['maintenance_viewer']) ? 'checked':''; ?> type="checkbox" data-toggle="toggle" data-onstyle="danger" data-offstyle="secondary" data-size="normal" data-on="<?php echo _("Activated"); ?>" data-off="<?php echo _("Deactivated"); ?>" id="maintenance_viewer" />
                                </div>
                                <script>$('#maintenance_viewer').bootstrapToggle();</script>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="maintenance_ip"><?php echo _("Maintenance IP"); ?> <i title="<?php echo _("allow certain IP addresses to access even in maintenance mode. Use a comma to separate them."); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div class="input-group mb-3">
                                        <input class="form-control" type="text" id="maintenance_ip" value="<?php echo $settings['maintenance_ip']; ?>" />
                                        <div class="input-group-append">
                                            <button onclick="add_my_ip();" class="btn btn-primary" id="basic-addon2"><?php echo ("Add my IP"); ?></button>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-spell-check"></i> <?php echo _("Security"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="captcha_register"><?php echo _("Captcha Register"); ?></label><br>
                                    <input <?php echo ($settings['captcha_register']) ? 'checked':''; ?> type="checkbox" id="captcha_register" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="captcha_login"><?php echo _("Captcha Login"); ?></label><br>
                                    <input <?php echo ($settings['captcha_login']) ? 'checked':''; ?> type="checkbox" id="captcha_login" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="2fa_enable"><?php echo _("Two-Factor Authentication"); ?></label><br>
                                    <input <?php echo ($settings['2fa_enable']) ? 'checked':''; ?> type="checkbox" id="2fa_enable" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list-ol"></i> <?php echo _("Features"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_external_vt"><?php echo _("Enable External Tours"); ?> <i title="<?php echo _("allows to set in the tour a link of one made with other systems"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['enable_external_vt']) ? 'checked':''; ?> type="checkbox" id="enable_external_vt" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_ar_vt"><?php echo _("Enable AR Tours"); ?> <i title="<?php echo _("allows to create a tour with a room in augmented reality, placing POIs on it and these will be viewed with a mobile device through the camera"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['enable_ar_vt']) ? 'checked':''; ?> type="checkbox" id="enable_ar_vt" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_wizard"><?php echo _("Enable Tour Creation Wizard"); ?></label><br>
                                    <input <?php echo ($settings['enable_wizard']) ? 'checked':''; ?> type="checkbox" id="enable_wizard" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-map"></i> <?php echo _("Map"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leaflet_street_basemap">Leaflet <?php echo _("Street Url"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_street_basemap" value="<?php echo $settings['leaflet_street_basemap']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="leaflet_street_subdomain">Leaflet <?php echo _("Street Subdomain"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_street_subdomain" value="<?php echo $settings['leaflet_street_subdomain']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="leaflet_street_maxzoom">Leaflet <?php echo _("Street Max Zoom"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_street_maxzoom" value="<?php echo $settings['leaflet_street_maxzoom']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leaflet_satellite_basemap">Leaflet <?php echo _("Satellite Url"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_satellite_basemap" value="<?php echo $settings['leaflet_satellite_basemap']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="leaflet_satellite_subdomain">Leaflet <?php echo _("Satellite Subdomain"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_satellite_subdomain" value="<?php echo $settings['leaflet_satellite_subdomain']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="leaflet_satellite_maxzoom">Leaflet <?php echo _("Satellite Max Zoom"); ?></label>
                                    <input type="text" class="form-control" id="leaflet_satellite_maxzoom" value="<?php echo $settings['leaflet_satellite_maxzoom']; ?>" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-globe"></i> <?php echo _("Globe"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="globe_ion_token">Cesium ION <?php echo _("Token"); ?> <a class="text-primary" href="https://ion.cesium.com/tokens?page=1" target="_blank"><i class="fas fa-external-link-square-alt"></i></a></label>
                                    <input autocomplete="new-password" type="password" class="form-control" id="globe_ion_token" value="<?php echo ($settings['globe_ion_token']!='') ? 'keep_globe_ion_token' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="globe_arcgis_token">ArcGIS <?php echo _("Token"); ?></label> <a class="text-primary" href="https://developers.arcgis.com/dashboard/" target="_blank"><i class="fas fa-external-link-square-alt"></i></a></label>
                                    <input autocomplete="new-password" type="password" class="form-control" id="globe_arcgis_token" value="<?php echo ($settings['globe_arcgis_token']!='') ? 'keep_globe_arcgis_token' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="globe_googlemaps_key">Google Maps <?php echo _("API Key"); ?> <a class="text-primary" href="https://console.cloud.google.com/apis/credentials" target="_blank"><i class="fas fa-external-link-square-alt"></i></a></label>
                                    <input autocomplete="new-password" type="password" class="form-control" id="globe_googlemaps_key" value="<?php echo ($settings['globe_googlemaps_key']!='') ? 'keep_globe_googlemaps_key' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-4 <?php echo ($hide_svt) ? 'd-none' : ''; ?>">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-handshake"></i> <?php echo _("Live Session"); ?> / <?php echo _("Meeting"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="peerjs_host">Peerjs <?php echo _("Server Host"); ?></label>
                                    <input type="text" class="form-control" id="peerjs_host" value="<?php echo $settings['peerjs_host']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="peerjs_port">Peerjs <?php echo _("Server Port"); ?></label>
                                    <input type="text" class="form-control" id="peerjs_port" value="<?php echo $settings['peerjs_port']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="peerjs_path">Peerjs <?php echo _("Server Path"); ?></label>
                                    <input type="text" class="form-control" id="peerjs_path" value="<?php echo $settings['peerjs_path']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="turn_host">TURN/STUN <?php echo _("Host"); ?></label>
                                    <input type="text" class="form-control" id="turn_host" value="<?php echo $settings['turn_host']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="turn_port">TURN/STUN <?php echo _("Port"); ?></label>
                                    <input type="text" class="form-control" id="turn_port" value="<?php echo $settings['turn_port']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="turn_username">TURN <?php echo _("Username"); ?></label>
                                    <input type="text" class="form-control" id="turn_username" value="<?php echo $settings['turn_username']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="turn_password">TURN <?php echo _("Password"); ?></label>
                                    <input type="text" class="form-control" id="turn_password" value="<?php echo $settings['turn_password']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="jitsi_domain">Jitsi <?php echo _("Server Domain"); ?></label>
                                    <input type="text" class="form-control" id="jitsi_domain" value="<?php echo $settings['jitsi_domain']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                To create your servers please refer to these 3 links: <a class="text-primary" href="https://github.com/peers/peerjs-server" target="_blank">PeerJs Server <i class="fas fa-external-link-square-alt"></i></a> - <a class="text-primary" href="https://ourcodeworld.com/articles/read/1175/how-to-create-and-configure-your-own-stun-turn-server-with-coturn-in-ubuntu-18-04" target="_blank">TURN/STUN Server <i class="fas fa-external-link-square-alt"></i></a> - <a class="text-primary" href="https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-quickstart/" target="_blank">Jitsi Meet <i class="fas fa-external-link-square-alt"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-folder-plus"></i> <?php echo _("Template"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="id_vt_template"><?php echo _("Virtual Tour"); ?> <i title="<?php echo _("virtual tour used as template when create a new one"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select class="form-control" id="id_vt_template">
                                        <option <?php echo (empty($settings['id_vt_template'])) ? 'selected' : ''; ?> id="0"><?php echo _("None"); ?></option>
                                        <?php echo get_virtual_tours_options($settings['id_vt_template']); ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-import"></i> <?php echo _("Sample"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="enable_sample"><?php echo _("Enable"); ?></label><br>
                                    <input <?php echo ($settings['enable_sample']) ? 'checked':''; ?> type="checkbox" id="enable_sample" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_vt_sample"><?php echo _("Virtual Tour"); ?> <i title="<?php echo _("virtual tour used as sample data"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select class="form-control" id="id_vt_sample">
                                        <option <?php echo (empty($settings['id_vt_sample'])) ? 'selected' : ''; ?> id="0"><?php echo _("Included (SVT demo)"); ?></option>
                                        <?php echo get_virtual_tours_options($settings['id_vt_sample']); ?>
                                    </select>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-directions"></i> <?php echo _("Presentation"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="enable_screencast"><?php echo _("Enable Screencast"); ?></label><br>
                                    <input <?php echo ($settings['enable_screencast']) ? 'checked':''; ?> type="checkbox" id="enable_screencast" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="url_screencast"><?php echo _("Url Screencast App"); ?> <i title="<?php echo _("link to the screencast web app that allows you to record your screen"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <input type="text" class="form-control" id="url_screencast" value="<?php echo $settings['url_screencast']; ?>" />
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-comments"></i> <?php echo _("Comments"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="disqus_shortname">Disqus <?php echo _("Shortname"); ?></label>
                                    <input type="text" class="form-control" id="disqus_shortname" value="<?php echo $settings['disqus_shortname']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="disqus_public_key">Disqus <?php echo _("Public API Key"); ?></label>
                                    <input autocomplete="new-password" class="form-control" type="password" id="disqus_public_key" value="<?php echo ($settings['disqus_public_key']!='') ? 'keep_disqus_public_key' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="disqus_allow_tour"><?php echo _("Allow editing in tours"); ?> <i title="<?php echo _("you can setup different disqus accounts/sites for different tours"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input <?php echo ($settings['disqus_allow_tour']) ? 'checked':''; ?> type="checkbox" id="disqus_allow_tour" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 pl-0 pr-0">
                            1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://disqus.com/profile/signup/">Disqus <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                            2) <?php echo sprintf(_("Create a new site %s and copy the <b>shortname</b> in the field above."),'<a class="text-primary" target="_blank" href="https://disqus.com/admin/create/">'._("here").' <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                            2) <?php echo sprintf(_("Register a new API application %s, add your site domain as trusted domain and copy the generated <b>public key</b> in the field above."),'<a class="text-primary" target="_blank" href="https://disqus.com/api/applications/register/">'._("here").' <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="whitelabel_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-sketch"></i> <?php echo _("Branding"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name"><?php echo _("Application Name"); ?></label>
                                    <input type="text" class="form-control" id="name" value="<?php echo $settings['name']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sidebar"><?php echo _("Sidebar"); ?></label>
                                    <select onchange="change_sidebar();" class="form-control" id="sidebar">
                                        <option <?php echo ($settings['sidebar']=='gradient') ? 'selected' : ''; ?> id="gradient"><?php echo _("Gradient"); ?></option>
                                        <option <?php echo ($settings['sidebar']=='flat') ? 'selected' : ''; ?> id="flat"><?php echo _("Flat"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="dark_mode"><?php echo _("Dark Mode"); ?></label><br>
                                    <select class="form-control" id="dark_mode">
                                        <option <?php echo ($settings['dark_mode']==0) ? 'selected' : ''; ?> id="0"><?php echo _("Disabled"); ?></option>
                                        <option <?php echo ($settings['dark_mode']==1) ? 'selected' : ''; ?> id="1"><?php echo _("Enabled"); ?></option>
                                        <option <?php echo ($settings['dark_mode']==2) ? 'selected' : ''; ?> id="2"><?php echo _("Forced (only dark mode)"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="font_backend"><?php echo _("Font"); ?></label><br>
                                    <input type="text" class="form-control <?php echo ($settings['font_provider']=='systems') ? 'd-none' : ''; ?>" id="font_backend" value="<?php echo $settings['font_backend']; ?>" />
                                    <select onchange="apply_system_font();" class="form-control <?php echo ($settings['font_provider']=='systems') ? '' : 'd-none'; ?>" id="font_backend_system">
                                        <option id="Arial" <?php echo ($settings['font_backend'] === 'Arial') ? 'selected' : ''; ?>>Arial</option>
                                        <option id="Helvetica" <?php echo ($settings['font_backend'] === 'Helvetica') ? 'selected' : ''; ?>>Helvetica</option>
                                        <option id="Tahoma" <?php echo ($settings['font_backend'] === 'Tahoma') ? 'selected' : ''; ?>>Tahoma</option>
                                        <option id="Verdana" <?php echo ($settings['font_backend'] === 'Verdana') ? 'selected' : ''; ?>>Verdana</option>
                                        <option id="Geneva" <?php echo ($settings['font_backend'] === 'Geneva') ? 'selected' : ''; ?>>Geneva</option>
                                        <option id="Calibri" <?php echo ($settings['font_backend'] === 'Calibri') ? 'selected' : ''; ?>>Calibri</option>
                                        <option id="Trebuchet MS" <?php echo ($settings['font_backend'] === 'Trebuchet MS') ? 'selected' : ''; ?>>Trebuchet MS</option>
                                        <option id="Times New Roman" <?php echo ($settings['font_backend'] === 'Times New Roman') ? 'selected' : ''; ?>>Times New Roman</option>
                                        <option id="Georgia" <?php echo ($settings['font_backend'] === 'Georgia') ? 'selected' : ''; ?>>Georgia</option>
                                        <option id="Garamond" <?php echo ($settings['font_backend'] === 'Garamond') ? 'selected' : ''; ?>>Garamond</option>
                                        <option id="Book Antiqua" <?php echo ($settings['font_backend'] === 'Book Antiqua') ? 'selected' : ''; ?>>Book Antiqua</option>
                                        <option id="Palatino Linotype" <?php echo ($settings['font_backend'] === 'Palatino Linotype') ? 'selected' : ''; ?>>Palatino Linotype</option>
                                        <option id="Courier New" <?php echo ($settings['font_backend'] === 'Courier New') ? 'selected' : ''; ?>>Courier New</option>
                                        <option id="Lucida Console" <?php echo ($settings['font_backend'] === 'Lucida Console') ? 'selected' : ''; ?>>Lucida Console</option>
                                        <option id="Monaco" <?php echo ($settings['font_backend'] === 'Monaco') ? 'selected' : ''; ?>>Monaco</option>
                                        <option id="Comic Sans MS" <?php echo ($settings['font_backend'] === 'Comic Sans MS') ? 'selected' : ''; ?>>Comic Sans MS</option>
                                        <option id="Bradley Hand" <?php echo ($settings['font_backend'] === 'Bradley Hand') ? 'selected' : ''; ?>>Bradley Hand</option>
                                        <option id="Impact" <?php echo ($settings['font_backend'] === 'Impact') ? 'selected' : ''; ?>>Impact</option>
                                        <option id="Chalkduster" <?php echo ($settings['font_backend'] === 'Chalkduster') ? 'selected' : ''; ?>>Chalkduster</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="theme_color"><?php echo _("Theme Color"); ?>&nbsp;&nbsp;<i class="fas fa-sun"></i></label>
                                    <input type="text" class="form-control" id="theme_color" value="<?php echo $settings['theme_color']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sidebar_color_1"><?php echo _("Sidebar Color 1"); ?>&nbsp;&nbsp;<i class="fas fa-sun"></i></label>
                                    <input type="text" class="form-control" id="sidebar_color_1" value="<?php echo $settings['sidebar_color_1']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sidebar_color_2"><?php echo _("Sidebar Color 2"); ?>&nbsp;&nbsp;<i class="fas fa-sun"></i></label>
                                    <input type="text" class="form-control <?php echo ($settings['sidebar']=='flat') ? 'disabled' : ''; ?>" id="sidebar_color_2" value="<?php echo $settings['sidebar_color_2']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="theme_color_dark"><?php echo _("Theme Color"); ?>&nbsp;&nbsp;<i class="fas fa-moon"></i></label>
                                    <input type="text" class="form-control" id="theme_color_dark" value="<?php echo $settings['theme_color_dark']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sidebar_color_1_dark"><?php echo _("Sidebar Color 1"); ?>&nbsp;&nbsp;<i class="fas fa-moon"></i></label>
                                    <input type="text" class="form-control" id="sidebar_color_1_dark" value="<?php echo $settings['sidebar_color_1_dark']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sidebar_color_2_dark"><?php echo _("Sidebar Color 2"); ?>&nbsp;&nbsp;<i class="fas fa-moon"></i></label>
                                    <input type="text" class="form-control <?php echo ($settings['sidebar']=='flat') ? 'disabled' : ''; ?>" id="sidebar_color_2_dark" value="<?php echo $settings['sidebar_color_2_dark']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="welcome_msg"><?php echo _("Welcome Message"); ?> <i title="<?php echo _("leave empty for default welcome message"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <div id="welcome_msg"><?php echo $settings['welcome_msg']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label><?php echo _("Logo"); ?></label>
                                <div style="background-color:#4e73df;display:none;width:calc(100% - 24px);margin:0 auto;" id="div_image_logo" class="col-md-12 text-center">
                                    <img style="width:100%;max-width:300px" src="assets/<?php echo $settings['logo']; ?>" />
                                </div>
                                <div style="display: none" id="div_delete_logo" class="col-md-12 mt-4">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_b_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                </div>
                                <div style="display: none" id="div_upload_logo">
                                    <form id="frm" action="ajax/upload_b_logo_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile" name="txtFile" />
                                                        <label class="custom-file-label text-left" for="txtFile"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Logo Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div id="progress_l" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label><?php echo _("Logo Small"); ?></label>
                                <div style="background-color:#4e73df;display:none;width:calc(100% - 24px);margin:0 auto;" id="div_image_logo_s" class="col-md-12 text-center">
                                    <img style="width:100%;max-width:100px;" src="assets/<?php echo $settings['small_logo']; ?>" />
                                </div>
                                <div style="display: none" id="div_delete_logo_s" class="col-md-12 mt-4">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_b_logo_s();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                </div>
                                <div style="display: none" id="div_upload_logo_s">
                                    <form id="frm_s" action="ajax/upload_b_logo_image.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="txtFile_s" name="txtFile_s" />
                                                        <label class="custom-file-label text-left" for="txtFile_s"><?php echo _("Choose file"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_s" value="<?php echo _("Upload Logo Image"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div id="progress_l_s" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_s" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_s"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _("Login Theme"); ?></label>
                                    <select class="form-control" id="style_login">
                                        <option <?php echo ($settings['style_login']==1) ? 'selected' : ''; ?> id="1"><?php echo _("Style 1 (image alongside the form)"); ?></option>
                                        <option <?php echo ($settings['style_login']==2) ? 'selected' : ''; ?> id="2"><?php echo _("Style 2 (image as background)"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _("Register Theme"); ?></label>
                                    <select class="form-control" id="style_register">
                                        <option <?php echo ($settings['style_register']==1) ? 'selected' : ''; ?> id="1"><?php echo _("Style 1 (image alongside the form)"); ?></option>
                                        <option <?php echo ($settings['style_register']==2) ? 'selected' : ''; ?> id="2"><?php echo _("Style 2 (image as background)"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label><?php echo _("Login image"); ?></label>
                                    </div>
                                    <div style="display: none" id="div_image_bg" class="col-md-12">
                                        <img src="assets/<?php echo $settings['background']; ?>" />
                                    </div>
                                    <div style="display: none" id="div_delete_bg" class="col-md-12 mt-4">
                                        <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_b_bg();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                    </div>
                                    <div style="display: none" id="div_upload_bg" class="col-md-12">
                                        <form id="frm_b" action="ajax/upload_b_background_image.php" method="POST" enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_b" name="txtFile_b" />
                                                            <label class="custom-file-label text-left" for="txtFile_b"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b" value="<?php echo _("Upload Login Image"); ?>" />
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="preview text-center">
                                                        <div id="progress_bl" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                            <div class="progress-bar" id="progressBar_b" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                                0%
                                                            </div>
                                                        </div>
                                                        <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label><?php echo _("Registration image"); ?></label>
                                    </div>
                                    <div style="display: none" id="div_image_bg_reg" class="col-md-12">
                                        <img src="assets/<?php echo $settings['background_reg']; ?>" />
                                    </div>
                                    <div style="display: none" id="div_delete_bg_reg" class="col-md-12 mt-4">
                                        <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_b_bg_reg();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                                    </div>
                                    <div style="display: none" id="div_upload_bg_reg" class="col-md-12">
                                        <form id="frm_b_reg" action="ajax/upload_b_background_image.php" method="POST" enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_b_reg" name="txtFile_b_reg" />
                                                            <label class="custom-file-label text-left" for="txtFile_b_reg"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b_reg" value="<?php echo _("Upload Registration Image"); ?>" />
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="preview text-center">
                                                        <div id="progress_br" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                            <div class="progress-bar" id="progressBar_b_reg" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                                0%
                                                            </div>
                                                        </div>
                                                        <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_b_reg"></div>
                                                    </div>
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
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-ellipsis-h"></i> <?php echo _("Footer"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_link_1"><?php echo _("Name Item 1"); ?></label><br>
                                    <input type="text" class="form-control" id="footer_link_1" value="<?php echo $settings['footer_link_1']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_value_1"><?php echo _("Content Item 1"); ?> <i title="<?php echo _("insert a textual content or a link to an external site"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div id="footer_value_1"><?php echo $settings['footer_value_1']; ?></div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_link_2"><?php echo _("Name Item 2"); ?></label><br>
                                    <input type="text" class="form-control" id="footer_link_2" value="<?php echo $settings['footer_link_2']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_value_2"><?php echo _("Content Item 2"); ?> <i title="<?php echo _("insert a textual content or a link to an external site"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div id="footer_value_2"><?php echo $settings['footer_value_2']; ?></div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_link_3"><?php echo _("Name Item 3"); ?></label><br>
                                    <input type="text" class="form-control" id="footer_link_3" value="<?php echo $settings['footer_link_3']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_value_3"><?php echo _("Content Item 3"); ?> <i title="<?php echo _("insert a textual content or a link to an external site"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <div id="footer_value_3"><?php echo $settings['footer_value_3']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="style_tab">
        <div class="row mt-2 mb-4">
            <div class="col-md-6">
                <button id="btn_custom_viewer" onclick="switch_custom_mode('viewer');" class="btn btn-block btn-primary"><?php echo _("Viewer"); ?></button>
            </div>
            <div class="col-md-6">
                <button id="btn_custom_backend" onclick="switch_custom_mode('backend');" class="btn btn-block btn-outline-primary"><?php echo _("Backend"); ?></button>
            </div>
        </div>
        <div id="custom_viewer_div">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow mb-12">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-css3-alt"></i> <?php echo _("Custom CSS"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <select onchange="change_editor_css();" class="form-control" id="css_name">
                                        <option id="css_custom"><?php echo _("General (affects all virtual tours)"); ?></option>
                                        <?php echo get_virtual_tours_options_css(); ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_css" id="custom"><?php echo get_editor_css_content('custom'); ?></div>
                                    <?php echo get_virtual_tours_editors_css(); ?>
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
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-js-square"></i> <?php echo _("Custom JS"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <select onchange="change_editor_js();" class="form-control" id="js_name">
                                        <option id="js_custom"><?php echo _("General (affects all virtual tours)"); ?></option>
                                        <?php echo get_virtual_tours_options_js(); ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_js" id="custom_js"><?php echo htmlspecialchars(get_editor_js_content('custom')); ?></div>
                                    <?php echo get_virtual_tours_editors_js(); ?>
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
                            <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-file-code"></i> <?php echo _("Custom Head Elements"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <select onchange="change_editor_head();" class="form-control" id="head_name">
                                        <option id="head_custom"><?php echo _("General (affects all virtual tours)"); ?></option>
                                        <?php echo get_virtual_tours_options_head(); ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_head" id="custom_head"><?php echo htmlspecialchars(get_editor_head_content('custom')); ?></div>
                                    <?php echo get_virtual_tours_editors_head(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-none" id="custom_backend_div">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow mb-12">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-css3-alt"></i> <?php echo _("Custom CSS"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_css" id="custom_b"><?php echo htmlspecialchars(get_editor_css_content('custom_b')); ?></div>
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
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-js-square"></i> <?php echo _("Custom JS"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_js" id="custom_b_js"><?php echo htmlspecialchars(get_editor_js_content('custom_b')); ?></div>
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
                            <h6 class="m-0 font-weight-bold text-primary"><i class="far fa-file-code"></i> <?php echo _("Custom Head Elements"); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div style="position: relative;width: 100%;height: 400px;" class="editors_head" id="custom_b_head"><?php echo htmlspecialchars(get_editor_head_content('custom_b')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="vr_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-vr-cardboard"></i> <?php echo _("Virtual Reality"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vr_button"><?php echo _("Web VR Button"); ?></label>
                                    <select class="form-control" id="vr_button">
                                        <option <?php echo ($settings['vr_button']==0) ? 'selected':''; ?> id="0"><?php echo _("Built-in version"); ?></option>
                                        <option <?php echo ($settings['vr_button']==1) ? 'selected':''; ?> id="1"><?php echo _("Standalone version (Headset VR compatible)"); ?></option>
                                    </select>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-icons"></i> <?php echo _("Icons (Standalone version)"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("Marker"); ?></label><br>
                                    <?php echo print_vr_icon_block('marker'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Image"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_image'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Video"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_video'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Video 360"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_video360'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Text"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_html'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Audio"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_audio'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("POI Object 3D"); ?></label><br>
                                    <?php echo print_vr_icon_block('poi_object3d'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2 text-center">
                                <div class="form-group">
                                    <label><?php echo _("Close"); ?></label><br>
                                    <?php echo print_vr_icon_block('close'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="mail_tab">
        <?php
        if($settings['smtp_valid']) {
            $smtp_valid = "<i style='color: green' class=\"fas fa-circle\"></i> "._("Valid");
        } else {
            $smtp_valid = "<i style='color: red' class=\"fas fa-circle\"></i> "._("Invalid");
        }
        ?>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary float-left"><i class="fas fa-envelope"></i> <?php echo _("Mail Server Settings"); ?></h6> <span id="validate_mail" class="float-right"><?php echo $smtp_valid; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="smtp_server"><?php echo _("SMTP Server"); ?></label>
                                    <input type="text" class="form-control" id="smtp_server" value="<?php echo $settings['smtp_server']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="smtp_port"><?php echo _("SMTP Port"); ?></label>
                                    <input type="number" class="form-control" id="smtp_port" value="<?php echo $settings['smtp_port']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="smtp_secure"><?php echo _("SMTP Secure"); ?></label>
                                    <select class="form-control" id="smtp_secure">
                                        <option <?php echo ($settings['smtp_secure']=='none') ? 'selected':''; ?> id="none"><?php echo _("None"); ?></option>
                                        <option <?php echo ($settings['smtp_secure']=='ssl') ? 'selected':''; ?> id="ssl">SSL</option>
                                        <option <?php echo ($settings['smtp_secure']=='tls') ? 'selected':''; ?> id="tls">TLS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="smtp_auth"><?php echo _("SMTP Auth"); ?></label><br>
                                    <input <?php echo ($settings['smtp_auth']) ? 'checked':''; ?> type="checkbox" id="smtp_auth" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_username"><?php echo _("SMTP Auth - Username"); ?></label>
                                    <input type="text" class="form-control" id="smtp_username" value="<?php echo $settings['smtp_username']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_password"><?php echo _("SMTP Auth - Password"); ?></label>
                                    <input type="password" class="form-control" id="smtp_password" value="<?php echo ($settings['smtp_password']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_from_email"><?php echo _("From E-Mail"); ?></label>
                                    <input type="text" class="form-control" id="smtp_from_email" value="<?php echo $settings['smtp_from_email']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_from_name"><?php echo _("From Name"); ?></label>
                                    <input type="text" class="form-control" id="smtp_from_name" value="<?php echo $settings['smtp_from_name']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button id="btn_validate_mail" onclick="save_settings(true);" class="btn btn-primary btn-block <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("VALIDATE MAIL SETTINGS"); ?></button>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bell"></i> <?php echo _("Notifications"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="cronjob"><?php echo _("Cron Job"); ?> <i title="<?php echo _("you have to run this php script as a cronjob every minute on your server for notifications requiring it to work"); ?>" class="help_t fas fa-exclamation-circle"></i></label>
                                    <input readonly type="text" class="form-control" id="cronjob" value="<?php echo $cronjob_dir; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="notify_email"><?php echo _("Admin E-Mail"); ?></label>
                                    <input type="text" class="form-control" id="notify_email" value="<?php echo $settings['notify_email']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="days_expire_notification"><?php echo _("Days before expiration notification"); ?></label>
                                    <input type="number" min="1" class="form-control" id="days_expire_notification" value="<?php echo $settings['days_expire_notification']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <table id="table_notifications" class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th scope="col"><?php echo _("Type"); ?></th>
                                        <th scope="col"><?php echo _("Require Cron"); ?></th>
                                        <th scope="col"><?php echo _("Notify Admin"); ?></th>
                                        <th scope="col"><?php echo _("Notify Customer"); ?></th>
                                        <th scope="col"><?php echo _("Enabled"); ?></th>
                                    </tr>
                                    </thead>
                                    <tr>
                                        <td><?php echo _("A new user is registered"); ?></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><input id="notify_registrations" type="checkbox" <?php echo ($settings['notify_registrations']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("User added by admin"); ?></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><input id="notify_useradd" type="checkbox" <?php echo ($settings['notify_useradd']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("The Plan is expiring"); ?></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><input id="notify_plan_expiring" type="checkbox" <?php echo ($settings['notify_plan_expiring']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("The Plan has expired"); ?></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><input id="notify_plan_expires" type="checkbox" <?php echo ($settings['notify_plan_expires']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("The Plan has changed"); ?></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><input id="notify_plan_changes" type="checkbox" <?php echo ($settings['notify_plan_changes']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("The Plan has canceled"); ?></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><input id="notify_plan_cancels" type="checkbox" <?php echo ($settings['notify_plan_cancels']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _("A Tour is created"); ?></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><i class="fas fa-check"></i></td>
                                        <td><i class="fas fa-times"></i></td>
                                        <td><input id="notify_vt_create" type="checkbox" <?php echo ($settings['notify_vt_create']) ? 'checked' : ''; ?> /></td>
                                    </tr>
                                    <tbody>
                                    </tbody>
                                </table>
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
                        <h6 class="m-0 font-weight-bold text-primary float-left"><i class="fas fa-envelope-open-text"></i> <?php echo _("Mail Texts"); ?></h6></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_activate_subject"><?php echo _("Activation mail - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_activate_subject" value="<?php echo $settings['mail_activate_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_activate_body"><?php echo _("Activation mail - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?>  %USER_NAME% , %LINK%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_activate_body"><?php echo $settings['mail_activate_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_user_add_subject"><?php echo _("A new user is registered - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_user_add_subject" value="<?php echo $settings['mail_user_add_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_user_add_body"><?php echo _("A new user is registered- Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?>  %USER_NAME% , %PASSWORD , %LINK%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_user_add_body"><?php echo $settings['mail_user_add_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_forgot_subject"><?php echo _("Forgot password mail - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_forgot_subject" value="<?php echo $settings['mail_forgot_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_forgot_body"><?php echo _("Forgot password mail - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?> %USER_NAME% , %LINK% , %VERFIFICATION_CODE%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_forgot_body"><?php echo $settings['mail_forgot_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_expiring_subject"><?php echo _("The Plan is expiring - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_plan_expiring_subject" value="<?php echo $settings['mail_plan_expiring_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_expiring_body"><?php echo _("The Plan is expiring - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?> %USER_NAME% , %PLAN_NAME% , %EXPIRE_DATE%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_plan_expiring_body"><?php echo $settings['mail_plan_expiring_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_expired_subject"><?php echo _("The Plan has expired - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_plan_expired_subject" value="<?php echo $settings['mail_plan_expired_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_expired_body"><?php echo _("The Plan has expired - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?> %USER_NAME% , %PLAN_NAME% , %EXPIRE_DATE%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_plan_expired_body"><?php echo $settings['mail_plan_expired_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_changed_subject"><?php echo _("The Plan has changed - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_plan_changed_subject" value="<?php echo $settings['mail_plan_changed_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_changed_body"><?php echo _("The Plan has changed - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?> %USER_NAME% , %PLAN_NAME%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_plan_changed_body"><?php echo $settings['mail_plan_changed_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_canceled_subject"><?php echo _("The Plan has canceled - Subject"); ?></label>
                                        <input type="text" class="form-control" id="mail_plan_canceled_subject" value="<?php echo $settings['mail_plan_canceled_subject']; ?>" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="mail_plan_canceled_body"><?php echo _("The Plan has canceled - Body"); ?> <i title="<?php echo _("variables in the mail text: "); ?> %USER_NAME% , %PLAN_NAME%" class="help_t fas fa-exclamation-circle"></i></label>
                                        <div id="mail_plan_canceled_body"><?php echo $settings['mail_plan_canceled_body']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="social_tab">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow mb-12">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-comments"></i> <?php echo _("Social Integration"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p><?php echo _("To make the integration with social providers work, you need to create login applications and retrieve credentials in their respective developer panels. Where required, enter the following parameters to enable the integrations."); ?></p>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _("Callback Url"); ?></label>
                                    <input type="text" readonly class="form-control" value="<?php echo $callback_url; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _("Whitelist Domain"); ?></label>
                                    <input type="text" readonly class="form-control" value="<?php echo $domain; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_google_enable"><?php echo _("Google - Enable"); ?></label><br>
                                    <input <?php echo ($settings['social_google_enable']) ? 'checked':''; ?> type="checkbox" id="social_google_enable" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_google_id"><?php echo _("Google - Id"); ?></label>
                                    <input type="password" class="form-control" id="social_google_id" value="<?php echo ($settings['social_google_id']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_google_secret"><?php echo _("Google - Secret"); ?></label>
                                    <input type="password" class="form-control" id="social_google_secret" value="<?php echo ($settings['social_google_secret']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_facebook_enable"><?php echo _("Facebook - Enable"); ?></label><br>
                                    <input <?php echo ($settings['social_facebook_enable']) ? 'checked':''; ?> type="checkbox" id="social_facebook_enable" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_facebook_id"><?php echo _("Facebook - Id"); ?></label>
                                    <input type="password" class="form-control" id="social_facebook_id" value="<?php echo ($settings['social_facebook_id']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_facebook_secret"><?php echo _("Facebook - Secret"); ?></label>
                                    <input type="password" class="form-control" id="social_facebook_secret" value="<?php echo ($settings['social_facebook_secret']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_twitter_enable"><?php echo _("Twitter - Enable"); ?></label><br>
                                    <input <?php echo ($settings['social_twitter_enable']) ? 'checked':''; ?> type="checkbox" id="social_twitter_enable" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_twitter_id"><?php echo _("Twitter - Id"); ?></label>
                                    <input type="password" class="form-control" id="social_twitter_id" value="<?php echo ($settings['social_twitter_id']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_twitter_secret"><?php echo _("Twitter - Secret"); ?></label>
                                    <input type="password" class="form-control" id="social_twitter_secret" value="<?php echo ($settings['social_twitter_secret']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_wechat_enable"><?php echo _("WeChat - Enable"); ?></label><br>
                                    <input <?php echo ($settings['social_wechat_enable']) ? 'checked':''; ?> type="checkbox" id="social_wechat_enable" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_wechat_id"><?php echo _("WeChat - Id"); ?></label>
                                    <input type="password" class="form-control" id="social_wechat_id" value="<?php echo ($settings['social_wechat_id']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_wechat_secret"><?php echo _("WeChat - Secret"); ?></label>
                                    <input type="password" class="form-control" id="social_wechat_secret" value="<?php echo ($settings['social_wechat_secret']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_qq_enable"><?php echo _("QQ - Enable"); ?></label><br>
                                    <input <?php echo ($settings['social_qq_enable']) ? 'checked':''; ?> type="checkbox" id="social_qq_enable" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_qq_id"><?php echo _("QQ - Id"); ?></label>
                                    <input type="password" class="form-control" id="social_qq_id" value="<?php echo ($settings['social_qq_id']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="social_qq_secret"><?php echo _("QQ - Secret"); ?></label>
                                    <input type="password" class="form-control" id="social_qq_secret" value="<?php echo ($settings['social_qq_secret']!='') ? 'keep_password' : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="registration_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-registered"></i> <?php echo _("Registration Settings"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="enable_registration"><?php echo _("Enable"); ?> <i title="<?php echo _("enables registration form for users"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="enable_registration" <?php echo ($settings['enable_registration'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group <?php echo ($settings['smtp_valid']) ? '' : 'disabled'; ?>">
                                    <label for="validate_email"><?php echo _("Validate Email"); ?> <i title="<?php echo _("send an email to new users to validate their account"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="validate_email" <?php echo ($settings['validate_email'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="change_plan"><?php echo _("Change Plan"); ?> <i title="<?php echo _("enables change plan for users"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                    <input type="checkbox" id="change_plan" <?php echo ($settings['change_plan'])?'checked':''; ?> />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="default_plan"><?php echo _("Default Plan"); ?> <i title="<?php echo _("default plan assigned to new registered users"); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select class="form-control" id="default_plan">
                                        <?php echo get_plans_options($settings['default_id_plan']); ?>
                                    </select>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-circle"></i> <?php echo _("Personal Informations"); ?></h6>
                    </div>
                    <div class="card-body">
                        <table id="table_fields" class="table table-bordered">
                            <thead>
                            <tr>
                                <th scope="col"><?php echo _("Field"); ?></th>
                                <th scope="col"><?php echo _("Enable"); ?></th>
                                <th scope="col"><?php echo _("Mandatory"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo _("First Name"); ?></td>
                                    <td><input id="first_name_enable" type="checkbox" <?php echo ($settings['first_name_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="first_name_mandatory" type="checkbox" <?php echo ($settings['first_name_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Last Name"); ?></td>
                                    <td><input id="last_name_enable" type="checkbox" <?php echo ($settings['last_name_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="last_name_mandatory" type="checkbox" <?php echo ($settings['last_name_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Company"); ?></td>
                                    <td><input id="company_enable" type="checkbox" <?php echo ($settings['company_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="company_mandatory" type="checkbox" <?php echo ($settings['company_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Tax Id"); ?></td>
                                    <td><input id="tax_id_enable" type="checkbox" <?php echo ($settings['tax_id_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="tax_id_mandatory" type="checkbox" <?php echo ($settings['tax_id_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Address"); ?></td>
                                    <td><input id="street_enable" type="checkbox" <?php echo ($settings['street_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="street_mandatory" type="checkbox" <?php echo ($settings['street_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("City"); ?></td>
                                    <td><input id="city_enable" type="checkbox" <?php echo ($settings['city_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="city_mandatory" type="checkbox" <?php echo ($settings['city_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("State / Province / Region"); ?></td>
                                    <td><input id="province_enable" type="checkbox" <?php echo ($settings['province_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="province_mandatory" type="checkbox" <?php echo ($settings['province_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Zip / Postal Code"); ?></td>
                                    <td><input id="postal_code_enable" type="checkbox" <?php echo ($settings['postal_code_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="postal_code_mandatory" type="checkbox" <?php echo ($settings['postal_code_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Country"); ?></td>
                                    <td><input id="country_enable" type="checkbox" <?php echo ($settings['country_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="country_mandatory" type="checkbox" <?php echo ($settings['country_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                                <tr>
                                    <td><?php echo _("Telephone"); ?></td>
                                    <td><input id="tel_enable" type="checkbox" <?php echo ($settings['tel_enable']) ? 'checked' : '' ; ?> /></td>
                                    <td><input id="tel_mandatory" type="checkbox" <?php echo ($settings['tel_mandatory']) ? 'checked' : '' ; ?> /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="legal_tab">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-contract"></i> <?php echo _("Terms and Conditions"); ?> <i title="<?php echo _("visible in registration form and on backend footer"); ?>" class="help_t fas fa-question-circle"></i></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div id="terms_and_conditions"><?php echo $settings['terms_and_conditions']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-secret"></i> <?php echo _("Privacy Policy"); ?> <i title="<?php echo _("visible in lead/form protections and on backend footer"); ?>" class="help_t fas fa-question-circle"></i></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div id="privacy_policy"><?php echo $settings['privacy_policy']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="payments_tab">
        <div class="col-md-12 mb-4">
            <div class="card shadow mb-12">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-stripe-s"></i> Stripe</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="stripe_enabled"><?php echo _("Enable"); ?> <i title="<?php echo _("enable this payment method (you need to initialize first)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <input <?php echo ($settings['stripe_enabled']==0)?'disabled':''; ?> type="checkbox" id="stripe_enabled" <?php echo ($settings['stripe_enabled'])?'checked':''; ?> />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="stripe_automatic_tax_rate"><?php echo _("Automatic Tax"); ?> <i title="<?php echo _("Calculate and collect sales tax, VAT, and GST automatically. You need to configure it under Stripe Dashboard  - Tax Settings"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <select class="form-control" id="stripe_automatic_tax_rate">
                                    <option <?php echo ($settings['stripe_automatic_tax_rate']=='unspecified') ? 'selected':''; ?> id="unspecified"><?php echo _("Disabled"); ?></option>
                                    <option <?php echo ($settings['stripe_automatic_tax_rate']=='inclusive') ? 'selected':''; ?> id="inclusive"><?php echo _("Prices with taxes included"); ?></option>
                                    <option <?php echo ($settings['stripe_automatic_tax_rate']=='exclusive') ? 'selected':''; ?> id="exclusive"><?php echo _("Prices with taxes excluded"); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="stripe_public_key"><?php echo _("Publishable key"); ?></label>
                                <input autocomplete="new-password" class="form-control" type="password" id="stripe_public_key" value="<?php echo ($settings['stripe_public_key']!='') ? 'keep_stripe_public_key' : ''; ?>" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="stripe_secret_key"><?php echo _("Secret Key"); ?></label>
                                <input autocomplete="new-password" class="form-control" type="password" id="stripe_secret_key" value="<?php echo ($settings['stripe_secret_key']!='') ? 'keep_stripe_secret_key' : ''; ?>" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label style="opacity:0;">.</label><br>
                                <button onclick="stripe_initialize(0,1);" id="btn_check_stripe" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Initialize"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 pl-0 pr-0">
                        1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://dashboard.stripe.com/login">Stripe <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                        2) <?php echo sprintf(_("From the menu bar, go to the <b>Developers</b> section of the Stripe dashboard and click on %s."),'<a class="text-primary" target="_blank" href="https://dashboard.stripe.com/account/apikeys">'._("API Keys").' <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                        3) <?php echo _("Copy the <b>Publishable key</b> and <b>Secret key</b> in the fields above and click <b>Initialize</b>"); ?><br>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mb-4">
            <div class="card shadow mb-12">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-paypal"></i> PayPal</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="paypal_enabled"><?php echo _("Enable"); ?> <i title="<?php echo _("enable this payment method (you need to initialize first)"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <input <?php echo ($settings['paypal_enabled']==0)?'disabled':''; ?> type="checkbox" id="paypal_enabled" <?php echo ($settings['paypal_enabled'])?'checked':''; ?> />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="paypal_live"><?php echo _("Live"); ?> <i title="<?php echo _("if not selected, use the paypal sandbox for testing"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                                <input type="checkbox" id="paypal_live" <?php echo ($settings['paypal_live'])?'checked':''; ?> />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="paypal_client_id"><?php echo _("Client Id"); ?></label>
                                <input autocomplete="new-password" class="form-control" type="password" id="paypal_client_id" value="<?php echo ($settings['paypal_client_id']!='') ? 'keep_paypal_client_id' : ''; ?>" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="paypal_client_secret"><?php echo _("Secret"); ?></label>
                                <input autocomplete="new-password" class="form-control" type="password" id="paypal_client_secret" value="<?php echo ($settings['paypal_client_secret']!='') ? 'keep_paypal_client_secret' : ''; ?>" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label style="opacity:0;">.</label><br>
                                <button onclick="paypal_initialize(0,1);" id="btn_check_paypal" class="btn btn-block btn-primary <?php echo ($demo) ? 'disabled_d':''; ?>"><?php echo _("Initialize"); ?>&nbsp;&nbsp;<i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 pl-0 pr-0">
                        1) <?php echo sprintf(_("Login into your %s account."),'<a class="text-primary" target="_blank" href="https://developer.paypal.com/developer/applications">PayPal <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                        2) <?php echo sprintf(_("Go to the %s section, click on <b>Live</b> button and then on the <b>Create app</b> button."),'<a class="text-primary" target="_blank" href="https://developer.paypal.com/developer/applications">'._("Apps & Credentials").' <i class="fas fa-external-link-square-alt"></i></a>'); ?><br>
                        3) <?php echo _("Copy the <b>Client ID</b> and <b>Secret</b> in the fields above and click <b>Initialize</b>"); ?><br>
                        <i><?php echo _("if you change from sandbox to live check that you have entered your live API credentials, check Live and Initialize again"); ?></i><br>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="voice_commands_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-language"></i> <?php echo _("Language"); ?></h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo _("Voice commands works with all browsers that implement the Speech Recognition interface of the Web Speech API."); ?></p>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" class="form-control" id="language_vc" placeholder="<?php echo _("Enter language code"); ?>" value="<?php echo $voice_commands['language']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <a href="#" data-toggle="modal" data-target="#modal_languages">
                                    <?php echo _("Languages Supported"); ?>
                                </a>
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
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-microphone"></i> <?php echo _("Commands"); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="initial_msg"><?php echo _("Welcome message"); ?></label>
                                    <input type="text" class="form-control" id="initial_msg" value="<?php echo $voice_commands['initial_msg']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="listening_msg"><?php echo _("Listening message"); ?></label>
                                    <input type="text" class="form-control" id="listening_msg" value="<?php echo $voice_commands['listening_msg']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="error_msg"><?php echo _("Error message"); ?></label>
                                    <input type="text" class="form-control" id="error_msg" value="<?php echo $voice_commands['error_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="help_cmd"><b><?php echo _("Help command"); ?></b> (<?php echo _("show help message"); ?>)</label>
                                    <input type="text" class="form-control" id="help_cmd" value="<?php echo $voice_commands['help_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="help_msg_1"><?php echo _("Help response"); ?> 1</label>
                                    <input type="text" class="form-control" id="help_msg_1" value="<?php echo $voice_commands['help_msg_1']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="help_msg_2"><?php echo _("Help response"); ?> 2</label>
                                    <input type="text" class="form-control" id="help_msg_2" value="<?php echo $voice_commands['help_msg_2']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="next_cmd"><b><?php echo _("Next command"); ?></b> (<?php echo _("go to next room"); ?>)</label>
                                    <input type="text" class="form-control" id="next_cmd" value="<?php echo $voice_commands['next_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="next_msg"><?php echo _("Next response"); ?></label>
                                    <input type="text" class="form-control" id="next_msg" value="<?php echo $voice_commands['next_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prev_cmd"><b><?php echo _("Previous command"); ?></b> (<?php echo _("go to previous room"); ?>)</label>
                                    <input type="text" class="form-control" id="prev_cmd" value="<?php echo $voice_commands['prev_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="prev_msg"><?php echo _("Previous response"); ?></label>
                                    <input type="text" class="form-control" id="prev_msg" value="<?php echo $voice_commands['prev_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="left_cmd"><b><?php echo _("Left command"); ?></b> (<?php echo _("looking left"); ?>)</label>
                                    <input type="text" class="form-control" id="left_cmd" value="<?php echo $voice_commands['left_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="left_msg"><?php echo _("Left response"); ?></label>
                                    <input type="text" class="form-control" id="left_msg" value="<?php echo $voice_commands['left_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="right_cmd"><b><?php echo _("Right command"); ?></b> (<?php echo _("looking right"); ?>)</label>
                                    <input type="text" class="form-control" id="right_cmd" value="<?php echo $voice_commands['right_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="right_msg"><?php echo _("Right response"); ?></label>
                                    <input type="text" class="form-control" id="right_msg" value="<?php echo $voice_commands['right_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="up_cmd"><b><?php echo _("Up command"); ?></b> (<?php echo _("looking up"); ?>)</label>
                                    <input type="text" class="form-control" id="up_cmd" value="<?php echo $voice_commands['up_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="up_msg"><?php echo _("Up response"); ?></label>
                                    <input type="text" class="form-control" id="up_msg" value="<?php echo $voice_commands['up_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="down_cmd"><b><?php echo _("Down command"); ?></b> (<?php echo _("looking down"); ?>)</label>
                                    <input type="text" class="form-control" id="down_cmd" value="<?php echo $voice_commands['down_cmd']; ?>" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="down_msg"><?php echo _("Down response"); ?></label>
                                    <input type="text" class="form-control" id="down_msg" value="<?php echo $voice_commands['down_msg']; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="categories_tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-th-list"></i> <?php echo _("Categories"); ?></h6>
                    </div>
                    <div class="card-body">
                        <table id="table_categories" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col"><?php echo _("Name"); ?></th>
                                    <th scope="col"><?php echo _("Actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                            <tr>
                                <td></td>
                                <td><input id='cat_new' type='text' class='form-control' value=''></td>
                                <td><button id="btn_add_category" onclick="add_category_s()" class="btn btn-sm btn-success <?php echo ($demo) ? 'disabled_d':''; ?>"><i class="fas fa-plus"></i> <?php echo _("new category"); ?></button></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_category" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Category"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete this category?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_category" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_stripe_init" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Initializing and synchronizing changes ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_aws_s3_init" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Initializing remote storage ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_pwa" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Generating PWA assets..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_languages" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Supported Languages"); ?></h5>
            </div>
            <div class="modal-body">
                Afrikaans <b>af</b> -
                Basque <b>eu</b> -
                Bulgarian <b>bg</b> -
                Catalan <b>ca</b> -
                Arabic (Egypt) <b>ar-EG</b> -
                Arabic (Jordan) <b>ar-JO</b> -
                Arabic (Kuwait) <b>ar-KW</b> -
                Arabic (Lebanon) <b>ar-LB</b> -
                Arabic (Qatar) <b>ar-QA</b> -
                Arabic (UAE) <b>ar-AE</b> -
                Arabic (Morocco) <b>ar-MA</b> -
                Arabic (Iraq) <b>ar-IQ</b> -
                Arabic (Algeria) <b>ar-DZ</b> -
                Arabic (Bahrain) <b>ar-BH</b> -
                Arabic (Lybia) <b>ar-LY</b> -
                Arabic (Oman) <b>ar-OM</b> -
                Arabic (Saudi Arabia) <b>ar-SA</b> -
                Arabic (Tunisia) <b>ar-TN</b> -
                Arabic (Yemen) <b>ar-YE</b> -
                Czech <b>cs</b> -
                Dutch <b>nl-NL</b> -
                English (Australia) <b>en-AU</b> -
                English (Canada) <b>en-CA</b> -
                English (India) <b>en-IN</b> -
                English (New Zealand) <b>en-NZ</b> -
                English (South Africa) <b>en-ZA</b> -
                English(UK) <b>en-GB</b> -
                English(US) <b>en-US</b> -
                Finnish <b>fi</b> -
                French <b>fr-FR</b> -
                Galician <b>gl</b> -
                German <b>de-DE</b> -
                Greek <b>el-GR</b> -
                Hebrew <b>he</b> -
                Hungarian <b>hu</b> -
                Icelandic <b>is</b> -
                Italian <b>it-IT</b> -
                Indonesian <b>id</b> -
                Japanese <b>ja</b> -
                Korean <b>ko</b> -
                Latin <b>la</b> -
                Mandarin Chinese <b>zh-CN</b> -
                Traditional Taiwan <b>zh-TW</b> -
                Simplified China <b>zh-CN </b> -
                Simplified Hong Kong <b>zh-HK</b> -
                Yue Chinese (Traditional Hong Kong) <b>zh-yue</b> -
                Malaysian <b>ms-MY</b> -
                Norwegian <b>no-NO</b> -
                Polish <b>pl</b> -
                Portuguese <b>pt-PT</b> -
                Portuguese (Brasil) <b>pt-br</b> -
                Romanian <b>ro-RO</b> -
                Russian <b>ru</b> -
                Serbian <b>sr-SP</b> -
                Slovak <b>sk</b> -
                Spanish (Argentina) <b>es-AR</b> -
                Spanish (Bolivia) <b>es-BO</b> -
                Spanish (Chile) <b>es-CL</b> -
                Spanish (Colombia) <b>es-CO</b> -
                Spanish (Costa Rica) <b>es-CR</b> -
                Spanish (Dominican Republic) <b>es-DO</b> -
                Spanish (Ecuador) <b>es-EC</b> -
                Spanish (El Salvador) <b>es-SV</b> -
                Spanish (Guatemala) <b>es-GT</b> -
                Spanish (Honduras) <b>es-HN</b> -
                Spanish (Mexico) <b>es-MX</b> -
                Spanish (Nicaragua) <b>es-NI</b> -
                Spanish (Panama) <b>es-PA</b> -
                Spanish (Paraguay) <b>es-PY</b> -
                Spanish (Peru) <b>es-PE</b> -
                Spanish (Puerto Rico) <b>es-PR</b> -
                Spanish (Spain) <b>es-ES</b> -
                Spanish (US) <b>es-US</b> -
                Spanish (Uruguay) <b>es-UY</b> -
                Spanish (Venezuela) <b>es-VE</b> -
                Swedish <b>sv-SE</b> -
                Turkish <b>tr</b> -
                Vietnamise <b>vi-VN</b> -
                Zulu <b>zu</b>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_check_multires_req" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Multiresolution Requirements"); ?></h5>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_check_video360_req" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("360 Video Tour Requirements"); ?></h5>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_check_slideshow_req" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Slideshow Requirements"); ?></h5>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_check_video_project_req" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Video Project Requirements"); ?></h5>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<?php $z0 = ''; if (array_key_exists('SERVER_ADDR', $_SERVER)) { $z0 = $_SERVER['SERVER_ADDR']; if (!filter_var($z0, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $z0 = gethostbyname($_SERVER['SERVER_NAME']); } } elseif (array_key_exists('LOCAL_ADDR', $_SERVER)) { $z0 = $_SERVER['LOCAL_ADDR']; } elseif (array_key_exists('SERVER_NAME', $_SERVER)) { $z0 = gethostbyname($_SERVER['SERVER_NAME']); } else { if (stristr(PHP_OS, 'WIN')) { $z0 = gethostbyname(php_uname('n')); } else { $b1 = shell_exec('/sbin/ifconfig eth0'); preg_match('/addr:([\d\.]+)/', $b1, $e2); $z0 = $e2[1]; } } $j3 = $_SERVER['SERVER_NAME'];echo"<script>window.server_name = '$j3'; window.server_ip = '$z0';</script>";?>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.settings_need_save = false;
        window.input_license = <?php echo $_SESSION['input_license']; ?>;
        window.b_logo_image = '<?php echo $settings['logo']; ?>';
        window.b_logo_s_image = '<?php echo $settings['small_logo']; ?>';
        window.b_background_image = '<?php echo $settings['background']; ?>';
        window.b_background_reg_image = '<?php echo $settings['background_reg']; ?>';
        window.current_language = '<?php echo $settings['language']; ?>';
        window.editors_css = [];
        window.editors_js = [];
        window.editors_head = [];
        window.welcome_msg_editor = null;
        window.terms_and_conditions_editor = null;
        window.privacy_policy_editor = null;
        window.mail_activate_body_editor = null;
        window.mail_user_add_body_editor = null;
        window.mail_forgot_body_editor = null;
        window.mail_plan_expiring_body_editor = null;
        window.mail_plan_expired_body_editor = null;
        window.mail_plan_changed_body_editor = null;
        window.mail_plan_canceled_body_editor = null;
        window.theme_color_spectrum = null;
        window.sidebar_color_1_spectrum = null;
        window.sidebar_color_2_spectrum = null;
        window.theme_color_dark_spectrum = null;
        window.sidebar_color_1_dark_spectrum = null;
        window.sidebar_color_2_dark_spectrum = null;
        window.footer_value_1 = null;
        window.footer_value_2 = null;
        window.footer_value_3 = null;
        Quill.register("modules/htmlEditButton", htmlEditButton);
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
            $('.server_info').html("("+window.server_name+' - '+window.server_ip+")");
            var div = document.getElementById("req_iframe");
            div.onload = function() {
                div.style.height =
                    div.contentWindow.document.body.scrollHeight-500 + 'px';
            }
            $('#font_backend').fontpicker({
                variants:false,
                localFonts: {},
                nrRecents: 0,
                onSelect: function (font) {
                    var font_family = font.fontFamily;
                    var font_provider = $('#font_provider option:selected').attr('id');
                    switch (font_provider) {
                        case 'google':
                            $('#font_backend_link').attr('href','https://fonts.googleapis.com/css?family='+font_family);
                            break;
                        case 'collabs':
                            $('#font_backend_link').attr('href','https://api.fonts.coollabs.io/css2?family='+font_family+'&display=swap');
                            break;
                    }
                    $('#style_css').html("*:not(i):not(.fas):not(.far):not(.fab):not(.leader-line):not(.leader-line *):not(.vjs-icon-placeholder):not(.vjs-play-progress) { font-family:'"+font_family+"',sans-serif; }");
                }
            });
            var font_provider = $('#font_provider option:selected').attr('id');
            if(font_provider=='systems') {
                $('.font-picker').addClass('d-none');
            } else {
                $('.font-picker').css('width','100%');
            }
            bsCustomFileInput.init();
            $('.help_t').tooltip();
            if(window.b_logo_image=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            if(window.b_logo_s_image=='') {
                $('#div_delete_logo_s').hide();
                $('#div_image_logo_s').hide();
                $('#div_upload_logo_s').show();
            } else {
                $('#div_delete_logo_s').show();
                $('#div_image_logo_s').show();
                $('#div_upload_logo_s').hide();
            }
            if(window.b_background_image=='') {
                $('#div_delete_bg').hide();
                $('#div_image_bg').hide();
                $('#div_upload_bg').show();
            } else {
                $('#div_delete_bg').show();
                $('#div_image_bg').show();
                $('#div_upload_bg').hide();
            }
            if(window.b_background_reg_image=='') {
                $('#div_delete_bg_reg').hide();
                $('#div_image_bg_reg').hide();
                $('#div_upload_bg_reg').show();
            } else {
                $('#div_delete_bg_reg').show();
                $('#div_image_bg_reg').show();
                $('#div_upload_bg_reg').hide();
            }
            $(".editors_css").each(function() {
                var id = $(this).attr('id');
                window.editors_css[id] = ace.edit(id);
                window.editors_css[id].session.setUseWorker(false);
                window.editors_css[id].session.setMode("ace/mode/css");
                window.editors_css[id].setOption('enableLiveAutocompletion',true);
                window.editors_css[id].setShowPrintMargin(false);
                if($('body').hasClass('dark_mode')) {
                    window.editors_css[id].setTheme("ace/theme/one_dark");
                }
            });
            $(".editors_js").each(function() {
                var id = $(this).attr('id');
                window.editors_js[id] = ace.edit(id);
                window.editors_js[id].session.setUseWorker(false);
                window.editors_js[id].session.setMode("ace/mode/javascript");
                window.editors_js[id].setOption('enableLiveAutocompletion',true);
                window.editors_js[id].setShowPrintMargin(false);
                if($('body').hasClass('dark_mode')) {
                    window.editors_js[id].setTheme("ace/theme/one_dark");
                }
            });
            $(".editors_head").each(function() {
                var id = $(this).attr('id');
                window.editors_head[id] = ace.edit(id);
                window.editors_head[id].session.setUseWorker(false);
                window.editors_head[id].session.setMode("ace/mode/html");
                window.editors_head[id].setOption('enableLiveAutocompletion',true);
                window.editors_head[id].setShowPrintMargin(false);
                if($('body').hasClass('dark_mode')) {
                    window.editors_head[id].setTheme("ace/theme/one_dark");
                }
            });
            if($('#license_status').html().includes("Extended")) {
                $('#registration_li').removeClass('d-none');
                $('#payments_li').removeClass('d-none');
            }
            if($('#license_status').html().includes("Regular")) {
                $('#upgrade_extended').removeClass('d-none');
            }
            var toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['clean']
            ];
            var toolbarOptions_wm = [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],['link'],['image'],
                ['clean']
            ];
            var toolbarHtml = {
                debug: false,
                msg: `<?php echo _("Edit the content in HTML format"); ?>`,
                okText: `<?php echo _("Ok"); ?>`,
                cancelText: `<?php echo _("Cancel"); ?>`,
                buttonHTML: '<i class="fas fa-code"></i>',
                buttonTitle: `<?php echo _("Show HTML Source"); ?>`,
                syntax: true,
                prependSelector: null,
                editorModules: {}
            };
            window.welcome_msg_editor = new Quill('#welcome_msg', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.terms_and_conditions_editor = new Quill('#terms_and_conditions', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.privacy_policy_editor = new Quill('#privacy_policy', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_activate_body_editor = new Quill('#mail_activate_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_user_add_body_editor = new Quill('#mail_user_add_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_forgot_body_editor = new Quill('#mail_forgot_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_plan_expiring_body_editor = new Quill('#mail_plan_expiring_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_plan_expired_body_editor = new Quill('#mail_plan_expired_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_plan_changed_body_editor = new Quill('#mail_plan_changed_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.mail_plan_canceled_body_editor = new Quill('#mail_plan_canceled_body', {
                modules: {
                    toolbar: toolbarOptions_wm,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.footer_value_1 = new Quill('#footer_value_1', {
                modules: {
                    toolbar: toolbarOptions,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.footer_value_2 = new Quill('#footer_value_2', {
                modules: {
                    toolbar: toolbarOptions,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.footer_value_3 = new Quill('#footer_value_3', {
                modules: {
                    toolbar: toolbarOptions,
                    htmlEditButton: toolbarHtml
                },
                theme: 'snow'
            });
            window.theme_color_spectrum = $('#theme_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: false,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    var hex = color.toHexString();
                    var sidebar_color_1 = $('#sidebar_color_1').val();
                    var sidebar_color_2 = $('#sidebar_color_2').val();
                    var theme_color_dark = $('#theme_color_dark').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    var sidebar_color_2_dark = $('#sidebar_color_2_dark').val();
                    set_session_theme_color(hex,sidebar_color_1,sidebar_color_2,theme_color_dark,sidebar_color_1_dark,sidebar_color_2_dark);
                }
            });
            window.sidebar_color_1_spectrum = $('#sidebar_color_1').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: true,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(color!=null) {
                        var hex = color.toHexString();
                    } else {
                        var hex = '';
                    }
                    var theme_color = $('#theme_color').val();
                    var sidebar_color_2 = $('#sidebar_color_2').val();
                    var theme_color_dark = $('#theme_color_dark').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    var sidebar_color_2_dark = $('#sidebar_color_2_dark').val();
                    set_session_theme_color(theme_color,hex,sidebar_color_2,theme_color_dark,sidebar_color_1_dark,sidebar_color_2_dark);
                }
            });
            window.sidebar_color_2_spectrum = $('#sidebar_color_2').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: true,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(color!=null) {
                        var hex = color.toHexString();
                    } else {
                        var hex = '';
                    }
                    var theme_color = $('#theme_color').val();
                    var sidebar_color_1 = $('#sidebar_color_1').val();
                    var theme_color_dark = $('#theme_color_dark').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    var sidebar_color_2_dark = $('#sidebar_color_2_dark').val();
                    set_session_theme_color(theme_color,sidebar_color_1,hex,theme_color_dark,sidebar_color_1_dark,sidebar_color_2_dark);
                }
            });
            window.theme_color_dark_spectrum = $('#theme_color_dark').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: true,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(color!=null) {
                        var hex = color.toHexString();
                    } else {
                        var hex = '';
                    }
                    var theme_color = $('#theme_color').val();
                    var sidebar_color_1 = $('#sidebar_color_1').val();
                    var sidebar_color_2 = $('#sidebar_color_2').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    var sidebar_color_2_dark = $('#sidebar_color_2_dark').val();
                    set_session_theme_color(theme_color,sidebar_color_1,sidebar_color_2,hex,sidebar_color_1_dark,sidebar_color_2_dark);
                }
            });
            window.sidebar_color_1_dark_spectrum = $('#sidebar_color_1_dark').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: true,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(color!=null) {
                        var hex = color.toHexString();
                    } else {
                        var hex = '';
                    }
                    var theme_color = $('#theme_color').val();
                    var sidebar_color_1 = $('#sidebar_color_1').val();
                    var sidebar_color_2 = $('#sidebar_color_2').val();
                    var theme_color_dark = $('#theme_color_dark').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    var sidebar_color_2_dark = $('#sidebar_color_2_dark').val();
                    set_session_theme_color(theme_color,sidebar_color_1,sidebar_color_2,theme_color_dark,hex,sidebar_color_2_dark);
                }
            });
            window.sidebar_color_2_dark_spectrum = $('#sidebar_color_2_dark').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: true,
                allowEmpty: true,
                cancelText: "<?php echo _("Cancel"); ?>",
                chooseText: "<?php echo _("Choose"); ?>",
                change: function(color) {
                    if(color!=null) {
                        var hex = color.toHexString();
                    } else {
                        var hex = '';
                    }
                    var theme_color = $('#theme_color').val();
                    var sidebar_color_1 = $('#sidebar_color_1').val();
                    var sidebar_color_2 = $('#sidebar_color_2').val();
                    var theme_color_dark = $('#theme_color_dark').val();
                    var sidebar_color_1_dark = $('#sidebar_color_1_dark').val();
                    set_session_theme_color(theme_color,sidebar_color_1,sidebar_color_2,theme_color_dark,sidebar_color_1_dark,hex);
                }
            });
            get_categories();
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
                        window.settings_need_save = true;
                        window.b_logo_image = evt.target.responseText;
                        $('#div_image_logo img').attr('src','assets/'+window.b_logo_image);
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
                $('#progress_l').hide();
            }else{
                $('#progress_l').show();
            }
        }

        function show_error(error){
            $('#progress_l').hide();
            $('#error').show();
            $('#error').html(error);
        }

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
                        window.settings_need_save = true;
                        window.b_logo_s_image = evt.target.responseText;
                        $('#div_image_logo_s img').attr('src','assets/'+window.b_logo_s_image);
                        $('#div_delete_logo_s').show();
                        $('#div_image_logo_s').show();
                        $('#div_upload_logo_s').hide();
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
                $('#progress_l_s').hide();
            }else{
                $('#progress_l_s').show();
            }
        }

        function show_error_s(error){
            $('#progress_l_s').hide();
            $('#error_s').show();
            $('#error_s').html(error);
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
                upadte_progressbar_b(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.settings_need_save = true;
                        window.b_background_image = evt.target.responseText;
                        $('#div_image_bg img').attr('src','assets/'+window.b_background_image);
                        $('#div_delete_bg').show();
                        $('#div_image_bg').show();
                        $('#div_upload_bg').hide();
                    }
                }
                upadte_progressbar_b(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b('upload failed');
                upadte_progressbar_b(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b('upload aborted');
                upadte_progressbar_b(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_b(value){
            $('#progressBar_b').css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_bl').hide();
            }else{
                $('#progress_bl').show();
            }
        }

        function show_error_b(error){
            $('#progress_bl').hide();
            $('#error_b').show();
            $('#error_b').html(error);
        }

        $('body').on('submit','#frm_b_reg',function(e){
            e.preventDefault();
            $('#error_b_reg').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_b_reg[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_b_reg' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_b_reg(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_b_reg(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        window.settings_need_save = true;
                        window.b_background_reg_image = evt.target.responseText;
                        $('#div_image_bg_reg img').attr('src','assets/'+window.b_background_reg_image);
                        $('#div_delete_bg_reg').show();
                        $('#div_image_bg_reg').show();
                        $('#div_upload_bg_reg').hide();
                    }
                }
                upadte_progressbar_b_reg(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_b_reg('upload failed');
                upadte_progressbar_b_reg(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_b_reg('upload aborted');
                upadte_progressbar_b_reg(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_b_reg(value){
            $('#progressBar_b_reg').css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_br').hide();
            }else{
                $('#progress_br').show();
            }
        }

        function show_error_b_reg(error){
            $('#progress_br').hide();
            $('#error_b_reg').show();
            $('#error_b_reg').html(error);
        }

        window.delete_image_vr = function(type) {
            $('#div_upload_vr_'+type).show();
            $('#div_delete_vr_'+type).hide();
            $.ajax({
                url: "ajax/delete_vr_icon.php",
                type: "POST",
                data: {
                    type: type
                },
                async: false,
                success: function (json) {
                    $('#image_vr_'+type).attr('src','../vr/img/'+type+'.png');
                }
            });
        }

        window.upadte_progressbar_vr_icon = function(value,id){
            $('#progressBar_vr_'+id).css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_vr_'+id).hide();
            }else{
                $('#progress_vr_'+id).show();
            }
        }

        window.show_error_vr_icon = function(error,id){
            $('#progress_vr_'+id).hide();
            $('#error_vr_'+id).show();
            $('#error_vr_'+id).html(error);
        }

        $("input").change(function(){
            window.settings_need_save = true;
        });

        $(window).on('beforeunload', function(){
            if(window.settings_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });

    })(jQuery); // End of use strict

    function add_my_ip() {
        var myIP = "<?php echo $_SERVER['REMOTE_ADDR']; ?>";
        var maintenanceIPInput = document.getElementById("maintenance_ip");
        var currentValue = maintenanceIPInput.value;
        if (currentValue.indexOf(myIP) === -1) {
            if (currentValue === "") {
                maintenanceIPInput.value = myIP;
            } else {
                maintenanceIPInput.value += "," + myIP;
            }
        }
    }

    function change_sidebar() {
        var sidebar = $('#sidebar option:selected').attr('id');
        switch (sidebar) {
            case 'flat':
                $('#accordionSidebar').removeClass('bg-gradient-primary').addClass('bg-flat-primary');
                $('#sidebar_color_2').addClass('disabled');
                $('#sidebar_color_2_dark').addClass('disabled');
                break;
            case 'gradient':
                $('#accordionSidebar').removeClass('bg-flat-primary').addClass('bg-gradient-primary');
                $('#sidebar_color_2').removeClass('disabled');
                $('#sidebar_color_2_dark').removeClass('disabled');
                break;
        }
    }
</script>
<?php
function print_vr_icon_block($type) {
    global $demo;
    if (file_exists(dirname(__FILE__).'/../vr/img/custom/'.$type.'.png')) {
        $custom = true;
        $image_url = "../vr/img/custom/$type.png?v=".time();
    } else {
        $custom = false;
        $image_url = "../vr/img/$type.png";
    }
    $script = <<<SCRIPT
<script>
  $('body').on('submit','#frm_{$type}',function(e){
        e.preventDefault();
        $('#error_{$type}').hide();
        var url = $(this).attr('action');
        var frm = $(this);
        var data = new FormData();
        if(frm.find('#txtFile_vr_{$type}[type="file"]').length === 1 ){
            data.append('file', frm.find( '#txtFile_vr_{$type}' )[0].files[0]);
        }
        var ajax  = new XMLHttpRequest();
        ajax.upload.addEventListener('progress',function(evt){
            var percentage = (evt.loaded/evt.total)*100;
            upadte_progressbar_vr_icon(Math.round(percentage),'{$type}');
        },false);
        ajax.addEventListener('load',function(evt){
            if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                show_error_vr_icon(evt.target.responseText,'{$type}');
            } else {
                if(evt.target.responseText!='') {
                    $('#div_upload_vr_{$type}').hide();
                    $('#div_delete_vr_{$type}').show();
                    $('#image_vr_{$type}').attr('src','../vr/img/custom/'+evt.target.responseText+'?v='+Date.now());
                }
            }
            upadte_progressbar_vr_icon(0,'{$type}');
            frm[0].reset();
        },false);
        ajax.addEventListener('error',function(evt){
            show_error_vr_icon('upload failed','{$type}');
            upadte_progressbar_vr_icon(0,'{$type}');
        },false);
        ajax.addEventListener('abort',function(evt){
            show_error_vr_icon('upload aborted','{$type}');
            upadte_progressbar_vr_icon(0,'{$type}');
        },false);
        ajax.open('POST',url);
        ajax.send(data);
        return false;
    });
</script>
SCRIPT;
    return '<img id="image_vr_'.$type.'" style="width:100%;margin:0 auto;max-width:100px;" src="'.$image_url.'" />
            <div style="display: '.(($custom) ? 'block':'none').'" id="div_delete_vr_'.$type.'" class="col-md-12 mt-4">
                <button '.(($demo) ? 'disabled':'').' onclick="delete_image_vr(\''.$type.'\');" class="btn btn-block btn-danger">'._("Remove Custom Icon").'</button>
            </div>
            <div style="display: '.(($custom) ? 'none':'block').'" id="div_upload_vr_'.$type.'" class="mt-3">
                <form id="frm_'.$type.'" action="ajax/upload_vr_icon.php?type='.$type.'" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="txtFile_vr_'.$type.'" name="txtFile_vr_'.$type.'" />
                                    <label class="custom-file-label text-left" for="txtFile_vr_'.$type.'">'._("Choose file").'</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input '.(($demo) ? 'disabled':'').' type="submit" class="btn btn-block btn-success" id="btnUpload_vr_'.$type.'" value="'._("Upload Icon").'" />
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="preview text-center">
                                <div id="progress_vr_'.$type.'" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                    <div class="progress-bar" id="progressBar_vr_'.$type.'" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                        0%
                                    </div>
                                </div>
                                <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_vr_'.$type.'"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>'.$script;
}
?>