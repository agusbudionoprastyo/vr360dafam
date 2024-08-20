<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(!file_exists("../config/config.inc.php")) {
    header("Location: ../install/start.php");
}
require_once("functions.php");
if(check_maintenance_mode('backend')) {
    if(file_exists("../error_pages/custom/maintenance_backend.html")) {
        include("../error_pages/custom/maintenance_backend.html");
    } else {
        include("../error_pages/default/maintenance_backend.html");
    }
    exit;
}
$session_id = session_id();
$_SESSION['svt_si_l']=$session_id;
if(isset($_SESSION['HYBRIDAUTH::STORAGE'])) {
    unset($_SESSION['HYBRIDAUTH::STORAGE']);
}
$settings = get_settings();
if(isset($_GET['lang'])) {
    if(check_language_enabled($_GET['lang'],$settings['languages_enabled'])) {
        $lang = $_GET['lang'];
        $_SESSION['lang']=$lang;
        header("Location: login.php");
        exit;
    }
}
set_language($settings['language'],$settings['language_domain']);
$v = time();
$modal_register = 0;
if(isset($_SESSION['modal_register'])) {
    $modal_register = $_SESSION['modal_register'];
    unset($_SESSION['modal_register']);
}
$verification_code = "";
$email = "";
if(isset($_GET['forgot'])) {
    if(isset($_GET['verification_code'])) {
        $verification_code = $_GET['verification_code'];
    }
    if(isset($_GET['email'])) {
        $email = $_GET['email'];
    }
    $forgot = true;
} else {
    $forgot = false;
}
if(empty($_SESSION['lang'])) {
    $lang = $settings['language'];
} else {
    $lang = $_SESSION['lang'];
}
$_SESSION['theme_color']=$settings['theme_color'];
if(empty($settings['logo']) && !empty($settings['small_logo'])) {
    $settings['logo'] = $settings['small_logo'];
}
if(isset($_GET['token'])) {
    $id_user=encrypt_decrypt('decrypt',$_GET['token'],date('Ymd'));
    if(!empty($id_user)) {
        $_SESSION['id_user']=$id_user;
        header("Location: index.php");
    }
}
$style_login = $settings['style_login'];
$website_url = $settings['website_url'];
if(isset($_SESSION['id_user_2fa'])) {
    $id_user_2fa = $_SESSION['id_user_2fa'];
} else {
    $id_user_2fa = 0;
}
$autologin = 0;
$remember_me = false;
if (isset($_COOKIE['svt_username']) && isset($_COOKIE['svt_password'])) {
    $username = encrypt_decrypt('decrypt',$_COOKIE['svt_username'],'svt');
    $password = encrypt_decrypt('decrypt',$_COOKIE['svt_password'],'svt');
    if(!empty($username) && !empty($password)) {
        $remember_me = true;
        if(isset($_COOKIE['svt_login'])) {
            $autologin = 1;
        }
    }
} else {
    $username = "";
    $password = "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="author" content="">
    <title><?php echo $settings['name']; ?></title>
    <?php echo print_favicons_backend($settings['logo']); ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/all.min.css?v=5.15.4">
    <?php switch ($settings['font_provider']) {
        case 'google': ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel='stylesheet' type="text/css" crossorigin="anonymous" href="https://fonts.googleapis.com/css2?family=<?php echo $settings['font_backend']; ?>">
            <?php break;
        case 'collabs': ?>
            <link rel="preconnect" href="https://api.fonts.coollabs.io" crossorigin>
            <link rel="stylesheet" type="text/css" href="https://api.fonts.coollabs.io/css2?family=<?php echo $settings['font_backend']; ?>&display=swap">
            <?php break;
    } ?>
    <link rel="stylesheet" type="text/css" href="css/sb-admin-2.min.css">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/tooltipster/css/tooltipster.bundle.min.css" />
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-error.min.css?v=2" />
    <link rel="stylesheet" type="text/css" href="css/theme.php?v=<?php echo $v; ?>">
    <link rel="stylesheet" type="text/css" href="css/theme_dark.php?v=<?php echo $v; ?>">
    <link rel="stylesheet" type="text/css" href="css/custom.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" type="text/css" href="css/dark_mode.css?v=<?php echo $v; ?>">
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_b.css')) : ?>
        <link rel="stylesheet" type="text/css" href="css/custom_b.css?v=<?php echo $v; ?>">
    <?php endif; ?>
</head>

<body class="bg_login <?php echo ($settings['background']!='' && $style_login==2) ? 'bg_image' : 'bg-gradient-primary' ; ?>" style="<?php echo ($settings['background']!='' && $style_login==2) ? 'background-image: url(assets/'.$settings['background'].') !important;'  : '' ; ?>">
<script>
    var dark_mode_setting = <?php echo $settings['dark_mode']; ?>;
    var dark_mode = '0';
    if(dark_mode_setting==1) {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            dark_mode = '1';
        }
        if (localStorage.getItem("dark_mode") === null) {
            localStorage.setItem("dark_mode",dark_mode);
        } else {
            dark_mode = localStorage.getItem('dark_mode');
        }
        if(dark_mode=='1') document.body.classList.add("dark_mode");
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            dark_mode = e.matches ? '1' : '0';
            if(dark_mode=='1') {
                document.body.classList.add("dark_mode");
                localStorage.setItem("dark_mode",'1');
            } else {
                document.body.classList.remove("dark_mode");
                localStorage.setItem("dark_mode",'0');
            }
        });
    }
</script>
<style>
    *{ font-family: '<?php echo $settings['font_backend']; ?>', sans-serif; }
</style>
<div class="container">
    <div class="row">
        <?php if(!empty($settings['logo'])) { ?>
            <div class="col-md-12 text-white mt-4 text-center">
                <img style="max-height:100px;max-width:200px;width:auto;height:auto" src="assets/<?php echo $settings['logo']; ?>" />
            </div>
        <?php } else { ?>
            <div class="col-md-12 text-white mt-4 text-center title_name_login">
                <h3 class="mb-0"><?php echo strtoupper($settings['name']); ?></h3>
            </div>
        <?php } ?>
    </div>
    <div class="row justify-content-center mt-3">
        <div class="<?php echo ($style_login==1 && $settings['background']!='') ? 'col-xl-10 col-lg-12 col-md-9' : 'col-xl-6 col-lg-8 col-md-9'; ?>" style="<?php echo ($style_login==2) ? 'max-width:540px' : ''; ?>">
            <div class="card o-hidden border-0 shadow-lg my-2 <?php echo ($style_login==2) ? 'glass_effect' : ''; ?>">
                <div class="card-body p-0">
                    <div class="row" style="min-height: 530px;">
                        <div style="<?php echo ($settings['background']!='' && $style_login==1) ? 'background-image: url(assets/'.$settings['background'].');'  : '' ; ?>" class="d-none bg-login-image <?php echo ($style_login==1 && $settings['background']!='') ? 'col-lg-6 d-lg-block' : ''; ?>"></div>
                        <div class="<?php echo ($style_login==1 && $settings['background']!='') ? 'col-lg-6' : 'col-md-12'; ?> pl-0">
                            <div class="p-5">
                                <li class="nav-item dropdown no-arrow lang_switcher_login" style="<?php echo ($style_login==2 || $settings['background']=='') ? 'left:10px' : ''; ?>">
                                    <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?php echo ($settings['languages_count']==1) ? 'style="cursor:default;pointer-events:none;"' : ''; ?> >
                                        <img style="height: 14px;" src="img/flags_lang/<?php echo $lang; ?>.png?v=2" />
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-left shadow" aria-labelledby="langDropdown">
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
                                <?php if($forgot) { ?>
                                    <div class="row <?php echo (!empty($verification_code)) ? 'disabled' : ''; ?>">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="email_f"><?php echo _("E-mail"); ?></label>
                                                <input type="email" class="form-control" id="email_f" value="<?php echo $email; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button id="btn_forgot_code" onclick="send_verification_code();" class="btn btn-block btn-primary"><?php echo _("Send Verification Code"); ?></button>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="forgot_code"><?php echo _("Verification code"); ?></label>
                                                <input type="text" class="form-control" id="forgot_code" value="<?php echo $verification_code; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="password_f"><?php echo _("New password"); ?></label>
                                                <div class="position-relative">
                                                    <input autocomplete="new-password" type="password" class="form-control" id="password_f" />
                                                    <i onclick="show_hide_password('password_f');" style="position:absolute;top:50%;right:15px;transform:translateY(-50%);cursor:pointer;" class="fa fa-eye-slash" aria-hidden="true"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="repeat_password_f"><?php echo _("Repeat password"); ?></label>
                                                <div class="position-relative">
                                                    <input autocomplete="new-password" type="password" class="form-control" id="repeat_password_f" />
                                                    <i onclick="show_hide_password('repeat_password_f');" style="position:absolute;top:50%;right:15px;transform:translateY(-50%);cursor:pointer;" class="fa fa-eye-slash" aria-hidden="true"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button id="btn_change_password" onclick="change_password_forgot()" class="btn btn-block btn-success"><?php echo _("Change password"); ?></button>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <a class="small" href="login.php"><?php echo _("Back to Login"); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                <div class="text-center">
                                    <h4 id="title_login" class="h4 text-gray-900 mb-0"><?php echo _("Welcome!"); ?></h4>
                                    <p id="description_login" class="mb-3"><?php echo _("Login to your account here"); ?></p>
                                </div>
                                <form class="user user_login">
                                    <div class="form-group">
                                        <input tabindex="1" autofocus type="text" class="form-control form-control-user" id="username_l" aria-describedby="emailHelp" value="<?php echo $username; ?>" placeholder="<?php echo _("Username or E-mail"); ?>">
                                    </div>
                                    <div class="form-group position-relative">
                                        <input tabindex="2" type="password" class="form-control form-control-user" id="password_l" value="<?php echo $password; ?>" placeholder="<?php echo _("Password"); ?>">
                                        <i onclick="show_hide_password('password_l');" style="position:absolute;top:50%;right:15px;transform:translateY(-50%);cursor:pointer;" class="fa fa-eye-slash" aria-hidden="true"></i>
                                    </div>
                                    <div class="form-group text-center">
                                        <div class="form-check">
                                            <input tabindex="3" type="checkbox" id="remember_l" <?php echo ($remember_me) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="remember_l">
                                                <?php echo _("Remember me"); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php if($settings['captcha_login']) : ?>
                                    <div class="form-group row">
                                        <div class="col-sm-12 text-center">
                                            <canvas id="captcha_canvas"></canvas>
                                            <input tabindex="4" autofill="off" autocomplete="off" id="captcha_code" class="form-control form-control-user" type="text" placeholder="<?php echo _("Type the code above"); ?>" required />
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <a tabindex="5" href="#" id="btn_login" onclick="login();return false;" class="btn btn-primary btn-user btn-block">
                                        <?php echo _("Login"); ?>
                                    </a>
                                    <div class="text-center">
                                        <?php if($settings['social_google_enable'] || $settings['social_facebook_enable'] || $settings['social_twitter_enable'] || $settings['social_wechat_enable'] || $settings['social_qq_enable']) { ?>
                                            <div style="font-size:14px;" class="strike mt-2 mb-2"><span><?php echo _("or"); ?></span></div>
                                        <?php } ?>
                                        <?php if($settings['social_google_enable']) : ?>
                                            <a onclick="go_to_social('Google');return false;" href="#" class="btn btn-circle btn-google btn-user">
                                                <i class="fab fa-google fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($settings['social_facebook_enable']) : ?>
                                            <a onclick="go_to_social('Facebook');return false;" href="#" class="btn btn-circle btn-facebook btn-user">
                                                <i class="fab fa-facebook-f fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($settings['social_twitter_enable']) : ?>
                                            <a onclick="go_to_social('Twitter');return false;" href="#" class="btn btn-circle btn-twitter btn-user">
                                                <i class="fab fa-twitter fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($settings['social_wechat_enable']) : ?>
                                            <a onclick="go_to_social('WeChat');return false;" href="#" class="btn btn-circle btn-wechat btn-user">
                                                <i class="fab fa-weixin fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($settings['social_qq_enable']) : ?>
                                            <a onclick="go_to_social('QQ');return false;" href="#" class="btn btn-circle btn-qq btn-user">
                                                <i class="fab fa-qq fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <?php if($settings['smtp_valid']) : ?>
                                    <div class="text-center">
                                        <a class="small" href="login.php?forgot=1"><?php echo _("Forgot Password?"); ?></a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($settings['enable_registration']) : ?>
                                        <div class="text-center mt-3">
                                            <a class="small btn btn-outline-primary" href="register.php"><?php echo _("Create an Account"); ?> &rarr;</a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($settings['website_url'])) : ?>
                                        <br>
                                        <div class="text-center">
                                            <a class="small" href="<?php echo $settings['website_url']; ?>"><i style="font-size:12px;" class="fas fa-chevron-left"></i>&nbsp;&nbsp;<?php echo sprintf(_('back to %s'),(!empty($settings['website_name'])) ? $settings['website_name'] : _('main site')); ?></a>
                                        </div>
                                    <?php endif; ?>
                                </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal_check_login_2fa" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Two-Factor Authentication"); ?></h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <span><?php echo _("Please enter the confirmation code that you see on your authenticator app."); ?></span>
                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="form-group">
                                <input type="number" class="form-control text-center" id="code_check_2fa" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn_login_2fa" onclick="check_login_2fa();" type="button" class="btn btn-success"><i class="fas fa-unlock"></i> <?php echo _("Authenticate"); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div id="modal_register" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Create Account"); ?></h5>
                </div>
                <div class="modal-body">
                    <p><?php echo sprintf(_("An account linked to %s was not found, do you want to register a new one?"),(empty($_SESSION['email_log'])) ? $_SESSION['username_log'] : $_SESSION['email_log']); ?></p>
                </div>
                <div class="modal-footer">
                    <button onclick="session_register()" type="button" class="btn btn-success"><i class="fas fa-user-plus"></i> <?php echo _("Yes, Register"); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js?v=3.7.1"></script>
<script src="js/jquery-captcha.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.js"></script>
<script type="text/javascript" src="../viewer/vendor/tooltipster/js/tooltipster.bundle.min.js"></script>
<script>
    window.login_labels = {
        "check_msg":`<?php echo _("Check your inbox for the verification code."); ?>`,
        "error_msg":`<?php echo _("Error, retry later."); ?>`,
        "password_success":`<?php echo _("Password successfully changed!"); ?>`,
        "invalid_username":`<?php echo _("You have entered an invalid username or email"); ?>`,
        "invalid_password":`<?php echo _("You have entered an invalid password"); ?>`,
        "account_locked":`<?php echo _("Your account is locked"); ?>`,
    };
</script>
<script src="js/function.js?v=<?php echo $v; ?>"></script>

<script>
    window.wizard_step = -1;
    window.captcha = null;
    var modal_register = <?php echo $modal_register; ?>;
    var id_user_2fa = <?php echo $id_user_2fa; ?>;
    window.autologin = <?php echo $autologin; ?>;
    (function($) {
        "use strict"; // Start of use strict
        if(modal_register==1) {
            $('#modal_register').modal("show");
        } else if(id_user_2fa!=0) {
            $('#modal_check_login_2fa').modal('show');
            setTimeout(function() {
                $('#code_check_2fa').focus();
            },300);
        } else if(autologin==1) {
            login();
        }
        if($('#captcha_code').length) {
            window.captcha = new Captcha($('#captcha_canvas'));
        }
        $(document).keyup(function(event) {
            if($('#modal_check_login_2fa').hasClass('show')) {
                if (event.key == "Enter") {
                    event.preventDefault();
                    $("#btn_login_2fa").trigger('click');
                }
            } else if(!$('#modal_forgot').hasClass('show')) {
                if (event.key == "Enter") {
                    event.preventDefault();
                    $("#btn_login").trigger('click');
                }
            }
        });
        window.go_to_social = function (provider) {
            if($('#captcha_code').length) {
                if($('#captcha_code').val()!='') {
                    $('#captcha_code').removeClass("error-highlight");
                    var valid_captcha = window.captcha.valid($('input[id="captcha_code"]').val());
                    if(valid_captcha) {
                        location.href = 'social_auth.php?provider='+provider+'&reg=0';
                    } else {
                        $('#captcha_code').addClass("error-highlight");
                    }
                } else {
                    $('#captcha_code').addClass("error-highlight");
                }
            } else {
                location.href = 'social_auth.php?provider='+provider+'&reg=0';
            }
        }
    })(jQuery); // End of use strict

    function show_hide_password(id) {
        if($('#'+id).attr("type") == "text"){
            $('#'+id).attr('type', 'password');
            $('#'+id).parent().find('i').addClass( "fa-eye-slash" );
            $('#'+id).parent().find('i').removeClass( "fa-eye" );
        }else if($('#'+id).attr("type") == "password"){
            $('#'+id).attr('type', 'text');
            $('#'+id).parent().find('i').removeClass( "fa-eye-slash" );
            $('#'+id).parent().find('i').addClass( "fa-eye" );
        }
    }
</script>

</body>
</html>
