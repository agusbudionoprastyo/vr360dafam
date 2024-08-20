<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
ini_set('session.gc_probability', 0);
include("vendor/hybridauth/autoload.php");
use Hybridauth\Hybridauth;
use Hybridauth\Storage\Session;
require_once("functions.php");

if(isset($_SESSION['provider'])) {
    $provider = $_SESSION['provider'];
    unset($_SESSION['provider']);
} else {
    $provider = $_GET['provider'];
    $_SESSION['provider'] = $provider;
}

if(isset($_SESSION['reg'])) {
    $reg = $_SESSION['reg'];
    unset($_SESSION['reg']);
} else {
    $reg = $_GET['reg'];
    $_SESSION['reg'] = $reg;
}

if(isset($_SESSION['edit_p'])) {
    $edit_p = $_SESSION['edit_p'];
    unset($_SESSION['edit_p']);
} else {
    if(isset($_GET['edit_p'])) {
        $edit_p = $_SESSION['id_user'];
        $_SESSION['edit_p'] = $_SESSION['id_user'];
    } else {
        $edit_p = 0;
        $_SESSION['edit_p'] = 0;
    }
}

if(isset($_GET['signout_p'])) {
    if($_GET['signout_p']==1) {
        $field_identifier = strtolower($provider)."_identifier";
        $mysqli->query("UPDATE svt_users SET $field_identifier=NULL WHERE id=".$_SESSION['id_user']);
        unset($_SESSION['edit_p']);
        ob_end_clean();
        session_write_close();
        header("Location:index.php?p=edit_profile");
        exit;
    }
}

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$link_callback = $protocol ."://". $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

$settings = get_settings();
$config = [
    'callback' => $link_callback,
    'providers' => [
        'Twitter' => ['enabled' => $settings['social_twitter_enable'], 'keys' => ['key' => $settings['social_twitter_id'], 'secret' => $settings['social_twitter_secret']]],
        'Google' => ['enabled' => $settings['social_google_enable'], 'keys' => ['id' => $settings['social_google_id'], 'secret' => $settings['social_google_secret']]],
        'Facebook' => ['enabled' => $settings['social_facebook_enable'], 'keys' => ['id' => $settings['social_facebook_id'], 'secret' => $settings['social_facebook_secret']]],
        'WeChat' => ['enabled' => $settings['social_wechat_enable'], 'keys' => ['id' => $settings['social_wechat_id'], 'secret' => $settings['social_wechat_secret']]],
        'QQ' => ['enabled' => $settings['social_qq_enable'], 'keys' => ['id' => $settings['social_qq_id'], 'secret' => $settings['social_qq_secret']]],
    ]
];

$email = '';
$first_name = '';
$last_name = '';
$user_name = '';
$identifier = '';

try {
    $hybridauth = new Hybridauth($config);
    $storage = new Session();
    $storage->set('reg', $reg);
    $storage->set('edit_p', $edit_p);
    if (isset($_GET['provider'])) {
        $storage->set('provider', $_GET['provider']);
    }
    if ($provider = $storage->get('provider')) {
        $hybridauth->authenticate($provider);
        $storage->set('provider', null);
        $adapter = $hybridauth->getAdapter($provider);
        $userProfile = $adapter->getUserProfile();
        $identifier = $userProfile->identifier;
        $first_name = $userProfile->firstName;
        $last_name = $userProfile->lastName;
        $email = $userProfile->email;
        $user_name = $userProfile->displayName;
    }
} catch(Exception $e) {
    echo $e->getMessage();
    die();
}

try {
    $reg = $storage->get('reg');
} catch (Exception $e) {}
try {
    $edit_p = $storage->get('edit_p');
} catch (Exception $e) {}
$field_identifier = strtolower($provider)."_identifier";

//$twofa_enabled = $settings['2fa_enable'];
$twofa_enabled = false;

if($edit_p==0) {
    if(!empty($email)) {
        $query_login = "SELECT * FROM svt_users WHERE email='$email' LIMIT 1;";
        $query = "SELECT id,active FROM svt_users WHERE $field_identifier='$identifier' LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $query_login = "SELECT * FROM svt_users WHERE id=".$row['id']." LIMIT 1;";
            }
        }
        $result = $mysqli->query($query_login);
        if($result) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                if($row['active']) {
                    $mysqli->query("UPDATE svt_users SET $field_identifier='$identifier' WHERE $field_identifier IS NULL AND id=".$row['id']);
                    unset($_SESSION['social_identifier']);
                    unset($_SESSION['social_provider']);
                    if($twofa_enabled && !empty($row['2fa_secretkey'])) {
                        $_SESSION['id_user_2fa'] = $row['id'];
                        session_write_close();
                        ob_end_clean();
                        header("Location:index.php");
                        exit;
                    } else {
                        $_SESSION['id_user'] = $row['id'];
                        $browser = parse_user_agent();
                        set_user_log($_SESSION['id_user'],'login',$_SERVER['REMOTE_ADDR']." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', time()));
                        session_write_close();
                        ob_end_clean();
                        header("Location:index.php");
                        exit;
                    }
                }
            } else {
                if($reg==1) {
                    if($last_name=="") {
                        $_SESSION['username_reg'] = strtolower(str_replace(" ","",$first_name));
                    } else {
                        $_SESSION['username_reg'] = strtolower(str_replace(" ","",$first_name).".".str_replace(" ","",$last_name));
                    }
                    $_SESSION['email_reg'] = $email;
                    $_SESSION['password_reg'] = randomPassword();
                    $_SESSION['social_identifier'] = $identifier;
                    $_SESSION['social_provider'] = $provider;
                    session_write_close();
                    ob_end_clean();
                    header("Location:register.php");
                    exit;
                } else {
                    if($settings['enable_registration']) {
                        if($last_name=="") {
                            $_SESSION['username_log'] = strtolower(str_replace(" ","",$first_name));
                        } else {
                            $_SESSION['username_log'] = strtolower(str_replace(" ","",$first_name).".".str_replace(" ","",$last_name));
                        }
                        $_SESSION['email_log'] = $email;
                        $_SESSION['password_log'] = randomPassword();
                        $_SESSION['social_identifier'] = $identifier;
                        $_SESSION['social_provider'] = $provider;
                        $_SESSION['modal_register'] = 1;
                    }
                }
            }
        }
    } elseif($provider=='WeChat' || $provider=='QQ') {
        $query_login = "SELECT * FROM svt_users WHERE username='$user_name' LIMIT 1;";
        $query = "SELECT id,active FROM svt_users WHERE $field_identifier='$identifier' LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $query_login = "SELECT * FROM svt_users WHERE id=".$row['id']." LIMIT 1;";
            }
        }
        $result = $mysqli->query($query_login);
        if($result) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                if($row['active']) {
                    $mysqli->query("UPDATE svt_users SET $field_identifier='$identifier' WHERE $field_identifier IS NULL AND id=".$row['id']);
                    unset($_SESSION['social_identifier']);
                    unset($_SESSION['social_provider']);
                    if($twofa_enabled && !empty($row['2fa_secretkey'])) {
                        $_SESSION['id_user_2fa'] = $row['id'];
                        session_write_close();
                        ob_end_clean();
                        header("Location:index.php");
                        exit;
                    } else {
                        $_SESSION['id_user'] = $row['id'];
                        $browser = parse_user_agent();
                        set_user_log($_SESSION['id_user'], 'login', $_SERVER['REMOTE_ADDR'] . " - " . $browser['browser'] . " " . $browser['version'] . " - " . $browser['platform'], date('Y-m-d H:i:s', time()));
                        session_write_close();
                        ob_end_clean();
                        header("Location:index.php");
                        exit;
                    }
                }
            } else {
                if($reg==1) {
                    $_SESSION['username_reg'] = $user_name;
                    $_SESSION['email_reg'] = '';
                    $_SESSION['password_reg'] = randomPassword();
                    $_SESSION['social_identifier'] = $identifier;
                    $_SESSION['social_provider'] = $provider;
                    session_write_close();
                    ob_end_clean();
                    header("Location:register.php");
                    exit;
                } else {
                    if($settings['enable_registration']) {
                        $_SESSION['username_log'] = $user_name;
                        $_SESSION['email_log'] = '';
                        $_SESSION['password_log'] = randomPassword();
                        $_SESSION['social_identifier'] = $identifier;
                        $_SESSION['social_provider'] = $provider;
                        $_SESSION['modal_register'] = 1;
                    }
                }
            }
        }
    }
    ob_end_clean();
    session_write_close();
    header("Location:login.php");
    exit;
} else {
    $mysqli->query("UPDATE svt_users SET $field_identifier='$identifier' WHERE $field_identifier IS NULL AND id=".$edit_p);
    unset($_SESSION['edit_p']);
    ob_end_clean();
    session_write_close();
    header("Location:index.php?p=edit_profile");
    exit;
}

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}