<?php
session_start();
$id_user = $_SESSION['id_user'];
$twocheckout_redirect_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$twocheckout_redirect_url = str_replace("/backend","/payments/2checkout_verification.php",$twocheckout_redirect_url);
$plans = get_plans($id_user);
$user_info = get_user_info($id_user);
$current_plan = $user_info['id_plan'];
$settings = get_settings();
$app_name = $settings['name'];
$stripe_enabled = $settings['stripe_enabled'];
$stripe_secret_key = $settings['stripe_secret_key'];
$stripe_public_key = $settings['stripe_public_key'];
$paypal_enabled = $settings['paypal_enabled'];
$paypal_client_id = $settings['paypal_client_id'];
$paypal_client_secret = $settings['paypal_client_secret'];
$twocheckout_enabled = $settings['2checkout_enable'];
$twocheckout_merchant = $settings['2checkout_merchant'];
$twocheckout_secret = $settings['2checkout_secret'];
if((empty($stripe_public_key)) || (empty($stripe_secret_key))) {
    $stripe_enabled = 0;
}
if((empty($paypal_client_id)) || (empty($paypal_client_secret))) {
    $paypal_enabled = 0;
}
if((empty($twocheckout_merchant)) || (empty($twocheckout_secret))) {
    $twocheckout_enabled = 0;
}
if($stripe_enabled) {
    $paypal_enabled=0;
    $twocheckout_enabled=0;
} else if($paypal_enabled) {
    $stripe_enabled=0;
    $twocheckout_enabled=0;
} else if($twocheckout_enabled) {
    $stripe_enabled=0;
    $paypal_enabled=0;
}
$expiring_plan = false;
if(!empty($user_info['expire_plan_date']) && (!empty($user_info['id_subscription_stripe'] || !empty($user_info['id_subscription_paypal'] || !empty($user_info['id_subscription_2checkout']))))) {
    $expiring_plan = true;
}
if(!empty($current_plan)) {
    foreach ($plans as $plan) {
        if($plan['id']==$current_plan) {
            $current_frequency = $plan['frequency'];
        }
    }
} else {
    $current_frequency = null;
}
if($paypal_enabled) {
    $recurring_count=0;
    $onetime_count=0;
    $currency_paypal = $plans[0]['currency'];
    foreach ($plans as $plan) {
        if($plan['price']>0) {
            switch($plan['frequency']) {
                case 'recurring':
                case 'month_year':
                    $recurring_count++;
                    break;
                case 'one_time':
                    $onetime_count++;
                    break;
            }
        }
    }
    foreach ($plans as $index=>$plan) {
        if($plan['price']>0) {
            switch($plan['frequency']) {
                case 'recurring':
                case 'month_year':
                    if($recurring_count<$onetime_count) { unset($plans[$index]); }
                    break;
                case 'one_time':
                    if($recurring_count>=$onetime_count) { unset($plans[$index]); }
                    break;
            }
        }
    }
}
$array_content_features = array();
$array_name_features = array();
$query = "SELECT feature,name,content FROM svt_features;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $feature = $row['feature'];
            $content = $row['content'];
            $name = $row['name'];
            if(!empty($content)) {
                $array_content_features[$feature] = $content;
            }
            if(!empty($name)) {
                $array_name_features[$feature] = $name;
            }
        }
    }
}

function feature_description($feature) {
    global $array_content_features;
    if(array_key_exists($feature,$array_content_features)) {
        $content = $array_content_features[$feature];
        return "class='feature_with_description' title='<div class=\"feature_tooltip_content\">$content</div>'";
    } else {
        return "";
    }
}
function feature_name($feature) {
    global $array_name_features;
    if(array_key_exists($feature,$array_name_features)) {
        $name = $array_name_features[$feature];
        return $name;
    } else {
        return "";
    }
}
?>

<?php if($stripe_enabled) : ?>
    <script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>

<?php if($paypal_enabled) : ?>
    <?php if($recurring_count>=$onetime_count) { ?>
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id; ?>&vault=true&intent=subscription&currency=<?php echo $currency_paypal; ?>"></script>
    <?php } else { ?>
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id; ?>&currency=<?php echo $currency_paypal; ?>" data-sdk-integration-source="button-factory"></script>
    <?php } ?>
<?php endif; ?>

<?php if($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) : ?>
    <div class="card bg-warning text-white shadow mb-3">
        <div class="card-body">
            <?php echo _("It is not possible to subscribe on this demo server. This section is shown for demonstration purposes only. Buy the code <a style='color:white;text-decoration:underline;font-weight:bold;' target='_blank' href='https://1.envato.market/Jrja9r'>here</a> "); ?>
        </div>
    </div>
<?php endif; ?>

<?php if($twocheckout_enabled) : ?>
    <script>
        (function (document, src, libName, config) {
            var script             = document.createElement('script');
            script.src             = src;
            script.async           = true;
            var firstScriptElement = document.getElementsByTagName('script')[0];
            script.onload          = function () {
                for (var namespace in config) {
                    if (config.hasOwnProperty(namespace)) {
                        window[libName].setup.setConfig(namespace, config[namespace]);
                    }
                }
                window[libName].register();
            };
            firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
        })(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart',{"app":{
                "merchant":"<?php echo $twocheckout_merchant ?>",
                "iframeLoad":"checkout"
            },
            "return-method": {
                "type": "redirect",
                "url": "<?php echo $twocheckout_redirect_url; ?>"
            },
            "cart":{
                "host":"https:\/\/secure.2checkout.com","customization":"inline"
            }
        });
    </script>
<?php endif; ?>

<div id="pricing_msg" class="text-center mb-3">
    <h3 class="text-primary mb-2"><?php echo _("Choose a pricing plan"); ?></h3>
    <h4><?php echo _("Pick what's right for you"); ?></h4>
</div>
<div class="pricing-columns">
    <div class="row justify-content-center">
        <?php foreach ($plans as $plan) { ?>
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card h-100 noselect" style="<?php echo ($plan['id']==$current_plan) ? 'border: 1px solid #4f73df;' : '' ; ?>">
                    <div class="card-header bg-transparent">
                        <span class="badge badge-primary-soft text-primary badge-pill py-2 px-3 mb-2"><?php echo $plan['name']; ?></span>
                        <?php if($plan['days']>0) {
                            echo '<span class="float-right text-gray-500">'.sprintf(_('expires in %s days'),$plan['days']).'</span>';
                        } ?>
                        <div class="pricing-columns-price">
                            <b>
                                <?php
                                $price = format_currency($plan['currency'],$plan['price']);
                                $price2 = format_currency($plan['currency'],$plan['price2']);
                                if($plan['price']==0) $price=_("Free");
                                if($plan['frequency']=='month_year' && $plan['price']>0 && $plan['price2']>0) {
                                    echo "<span id='price_".$plan['id']."_m'>".$price."</span>";
                                    echo "<span style='display:none;' id='price_".$plan['id']."_y'>".$price2."</span>";
                                } else {
                                    echo $price;
                                }
                                ?>
                            </b>
                            <span><?php
                                $interval_count = $plan['interval_count'];
                                if($plan['price']>0) {
                                    switch($plan['frequency']) {
                                        case 'recurring':
                                            if($interval_count==1) {
                                                $recurring_label = "/ "._("month");
                                            } elseif($interval_count==12) {
                                                $recurring_label = "/ "._("year");
                                            } else {
                                                $recurring_label = "/ ".$interval_count." "._("months");
                                            }
                                            break;
                                        case 'month_year':
                                            $recurring_label = "/ "._("month");
                                            break;
                                        case 'one_time':
                                            $recurring_label="";
                                            break;
                                    }
                                } else {
                                    $recurring_label="";
                                }
                                if($plan['frequency']=='month_year' && $plan['price']>0 && $plan['price2']>0) {
                                    $recurring_label="/&nbsp;&nbsp;";
                                }
                                echo $recurring_label;
                                ?></span>
                            <?php if($plan['frequency']=='month_year' && $plan['price2']>0) : ?>
                                <input id="switcher_<?php echo $plan['id']; ?>" type="checkbox" checked data-toggle="toggle" data-size="mini" data-onstyle="primary" data-offstyle="primary" data-on="<?php echo _("month"); ?>" data-off="<?php echo _("year"); ?>" data-onstyle="success" data-offstyle="danger" data-style="ios">
                                <script>
                                    $('#switcher_<?php echo $plan['id']; ?>').change(function() {
                                        if($(this).prop('checked')) {
                                            $('#price_<?php echo $plan['id']; ?>_m').show();
                                            $('#price_<?php echo $plan['id']; ?>_y').hide();
                                            $('#paypal_button_<?php echo $plan['id']; ?>').css('display','contents');
                                            $('#paypal_button_<?php echo $plan['id']; ?>_y').css('display','none');
                                        } else {
                                            $('#price_<?php echo $plan['id']; ?>_m').hide();
                                            $('#price_<?php echo $plan['id']; ?>_y').show();
                                            $('#paypal_button_<?php echo $plan['id']; ?>').css('display','none');
                                            $('#paypal_button_<?php echo $plan['id']; ?>_y').css('display','contents');
                                        }
                                    })
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="flex:0 0 auto;" class="card-body p-0">
                        <ul class="list-group list-group-flush features_plan">
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['n_virtual_tours']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_virtual_tours']).'</b> '._("Virtual Tours"); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['n_rooms']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_rooms']).'</b> '._("Rooms"); ?> <?php echo ($plan['n_rooms_tour']>0) ? ' (<b>'.$plan['n_rooms_tour'].'</b> '._(" for each tour").')' : ''; ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['n_markers']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_markers']).'</b> '._("Markers"); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['n_pois']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_pois']).'</b> '._("POIs"); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['n_gallery_images']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_gallery_images']).'</b> '._("Gallery Images"); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['max_file_size_upload']==-1) ? '<i class="fas fa-infinity"></i>' : (($plan['max_file_size_upload']>=1000) ? ($plan['max_file_size_upload']/1000)." GB" : $plan['max_file_size_upload']." MB" )).'</b> '._("Panorama Upload Size"); ?>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-check-circle text-primary"></i>
                                <?php echo '<b>'.(($plan['max_storage_space']==-1) ? '<i class="fas fa-infinity"></i>' : (($plan['max_storage_space']>=1000) ? ($plan['max_storage_space']/1000)." GB" : $plan['max_storage_space']." MB" )).'</b> '._("Storage Quota"); ?>
                            </li>
                            <li class="list-group-item text-center">
                                <a class="badge badge-pill badge-primary-soft show_more text-decoration-none" href="#" data-toggle="collapse" data-target=".collapse_all"><?php echo _("show features"); ?> <i style="margin-right:0" class="fas fa-caret-down"></i></a>
                                <a class="badge badge-pill badge-primary-soft show_less text-decoration-none" href="#" data-toggle="collapse" data-target=".collapse_all" style="display: none"><?php echo _("hide features"); ?> <i style="margin-right:0" class="fas fa-caret-up"></i></a>
                            </li>
                            <div class="collapse collapse_all">
                                <li class="list-group-item" style="<?php echo ($plan['enable_info_box']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_info_box']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('info_box'); ?>><?php echo (!empty(feature_name('info_box'))) ? feature_name('info_box') : _("Info Box"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_gallery']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_gallery']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('gallery'); ?>><?php echo (!empty(feature_name('gallery'))) ? feature_name('gallery') : _("Gallery"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_download_slideshow']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_download_slideshow']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('download_slideshow'); ?>><?php echo (!empty(feature_name('download_slideshow'))) ? feature_name('download_slideshow') : _("Download Slideshow"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_maps']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_maps']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('maps'); ?>><?php echo (!empty(feature_name('maps'))) ? feature_name('maps') : _("Maps"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_presentation']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_presentation']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('presentation'); ?>><?php echo (!empty(feature_name('presentation'))) ? feature_name('presentation') : _("Presentation"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_video360']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_video360']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('360_video_tour'); ?>><?php echo (!empty(feature_name('360_video_tour'))) ? feature_name('360_video_tour') : _("360 Video Tour"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_video_projects']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_video_projects']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('video_projects'); ?>><?php echo (!empty(feature_name('video_projects'))) ? feature_name('video_projects') : _("Video Projects"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_dollhouse']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_dollhouse']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('3d_view'); ?>><?php echo (!empty(feature_name('3d_view'))) ? feature_name('3d_view') : _("3D View"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_editor_ui']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_editor_ui']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('editor_ui'); ?>><?php echo (!empty(feature_name('editor_ui'))) ? feature_name('editor_ui') : _("Editor UI"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_measurements']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_measurements']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('measurements'); ?>><?php echo (!empty(feature_name('measurements'))) ? feature_name('measurements') : _("Measurements"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_icons_library']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_icons_library']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('icons_library'); ?>><?php echo (!empty(feature_name('icons_library'))) ? feature_name('icons_library') : _("Icons Library"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_media_library']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_media_library']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('media_library'); ?>><?php echo (!empty(feature_name('media_library'))) ? feature_name('media_library') : _("Media Library"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_music_library']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_music_library']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('music_library'); ?>><?php echo (!empty(feature_name('music_library'))) ? feature_name('music_library') : _("Music Library"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_sound_library']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_sound_library']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('sound_library'); ?>><?php echo (!empty(feature_name('sound_library'))) ? feature_name('sound_library') : _("Sound Library"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_voice_commands']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_voice_commands']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('voice_commands'); ?>><?php echo (!empty(feature_name('voice_commands'))) ? feature_name('voice_commands') : _("Voice Commands"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_statistics']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_statistics']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('statistics'); ?>><?php echo (!empty(feature_name('statistics'))) ? feature_name('statistics') : _("Statistics"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_multilanguage']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_multilanguage']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('multilanguage'); ?>><?php echo (!empty(feature_name('multilanguage'))) ? feature_name('multilanguage') : _("Multi Language"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_auto_translation']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_auto_translation']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('auto_translation'); ?>><?php echo (!empty(feature_name('auto_translation'))) ? feature_name('auto_translation') : _("Automatic Translation"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_shop']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_shop']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('shop'); ?>><?php echo (!empty(feature_name('shop'))) ? feature_name('shop') : _("Shop"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_landing']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_landing']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('landing'); ?>><?php echo (!empty(feature_name('landing'))) ? feature_name('landing') : _("Landing"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_showcase']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_showcase']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('showcase'); ?>><?php echo (!empty(feature_name('showcase'))) ? feature_name('showcase') : _("Showcase"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['create_globes']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['create_globes']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('globe'); ?>><?php echo (!empty(feature_name('globe'))) ? feature_name('globe') : _("Globe"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_logo']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_logo']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('logo'); ?>><?php echo (!empty(feature_name('logo'))) ? feature_name('logo') : _("Your own Logo"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_poweredby']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_poweredby']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('poweredby'); ?>><?php echo (!empty(feature_name('poweredby'))) ? feature_name('poweredby') : _("Powered By Logo / Text"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_nadir_logo']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_nadir_logo']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('nadir'); ?>><?php echo (!empty(feature_name('nadir'))) ? feature_name('nadir') : _("Hide Tripod"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_loading_iv']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_loading_iv']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('loading_iv'); ?>><?php echo (!empty(feature_name('loading_iv'))) ? feature_name('loading_iv') : _("Loading Image/Video"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_custom_html']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_custom_html']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('custom_html'); ?>><?php echo (!empty(feature_name('custom_html'))) ? feature_name('custom_html') : _("Custom HTML"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_song']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_song']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('background_music'); ?>><?php echo (!empty(feature_name('background_music'))) ? feature_name('background_music') : _("Background Music"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_comments']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_comments']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('comments'); ?>><?php echo (!empty(feature_name('comments'))) ? feature_name('comments') : _("Comments"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_auto_rotate']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_auto_rotate']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('auto_rotation'); ?>><?php echo (!empty(feature_name('auto_rotation'))) ? feature_name('auto_rotation') : _("Auto Rotation"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_flyin']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_flyin']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('flyin'); ?>><?php echo (!empty(feature_name('flyin'))) ? feature_name('flyin') : _("Fly-In Animation"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_multires']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_multires']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('multires'); ?>><?php echo (!empty(feature_name('multires'))) ? feature_name('multires') : _("Multi-Resolution"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_live_session']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_live_session']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('live_session'); ?>><?php echo (!empty(feature_name('live_session'))) ? feature_name('live_session') : _("Live Session"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_meeting']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_meeting']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('meeting'); ?>><?php echo (!empty(feature_name('meeting'))) ? feature_name('meeting') : _("Meeting"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_annotations']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_annotations']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('annotations'); ?>><?php echo (!empty(feature_name('annotations'))) ? feature_name('annotations') : _("Annotations"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_panorama_video']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_panorama_video']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('video_360_panorama'); ?>><?php echo (!empty(feature_name('video_360_panorama'))) ? feature_name('video_360_panorama') : _("Video 360 Panorama"); ?></span>
                                </li>
                                <?php if($settings['enable_ai_room']) : ?>
                                <li class="list-group-item" style="<?php echo ($plan['enable_ai_room']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_ai_room']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('ai_panorama'); ?>><?php echo (!empty(feature_name('ai_panorama'))) ? feature_name('ai_panorama') : _("A.I. Panorama"); ?></span> (<?php echo (($plan['n_ai_generate_month']==-1) ? '<i class="fas fa-infinity"></i>' : $plan['n_ai_generate_month']); ?> <?php echo _("per month") ?>)
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item" style="<?php echo ($plan['enable_rooms_multiple']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_rooms_multiple']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('multiple_rooms_view'); ?>><?php echo (!empty(feature_name('multiple_rooms_view'))) ? feature_name('multiple_rooms_view') : _("Multiple Room's Views"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_rooms_protect']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_rooms_protect']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('protect_rooms'); ?>><?php echo (!empty(feature_name('protect_rooms'))) ? feature_name('protect_rooms') : _("Protect Rooms (Passcode, Leads)"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_context_info']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_context_info']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('right_click_content'); ?>><?php echo (!empty(feature_name('right_click_content'))) ? feature_name('right_click_content') : _("Right Click Content"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_chat']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_chat']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('chat'); ?>><?php echo (!empty(feature_name('chat'))) ? feature_name('chat') : _("Facebook / Whatsapp Chat"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_share']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_share']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('share'); ?>><?php echo (!empty(feature_name('share'))) ? feature_name('share') : _("Share"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_forms']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_forms']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('forms'); ?>><?php echo (!empty(feature_name('forms'))) ? feature_name('forms') : _("Forms"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_device_orientation']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_device_orientation']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('device_orientation'); ?>><?php echo (!empty(feature_name('device_orientation'))) ? feature_name('device_orientation') : _("Device Orientation"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_webvr']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_webvr']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('vr'); ?>><?php echo (!empty(feature_name('vr'))) ? feature_name('vr') : _("Virtual Reality"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_expiring_dates']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_expiring_dates']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('expiring_dates'); ?>><?php echo (!empty(feature_name('expiring_dates'))) ? feature_name('expiring_dates') : _("Expiring Dates"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_metatag']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_metatag']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('meta_tags'); ?>><?php echo (!empty(feature_name('meta_tags'))) ? feature_name('meta_tags') : _("Meta Tags"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_password_tour']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_password_tour']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('protect_tour'); ?>><?php echo (!empty(feature_name('protect_tour'))) ? feature_name('protect_tour') : _("Protect tour (Password, Leads)"); ?></span>
                                </li>
                                <li class="list-group-item" style="<?php echo ($plan['enable_export_vt']==0) ? 'opacity:0.5' : ''; ?>">
                                    <i class="far <?php echo ($plan['enable_export_vt']==1) ? 'fa-check-circle text-primary' : 'fa-times-circle text-black-50'; ?>"></i>
                                    <span <?php echo feature_description('download_tour'); ?>><?php echo (!empty(feature_name('download_tour'))) ? feature_name('download_tour') : _("Download Tour"); ?></span>
                                </li>
                                <?php
                                $custom_features = $plan['custom_features'];
                                $custom_features_array = explode("\n", $custom_features);
                                foreach ($custom_features_array as $custom_feature) {
                                    if(!empty($custom_feature)) {
                                        echo '<li class="list-group-item custom_feature">
                                    <i class="far fa-check-circle text-primary"></i>
                                    '.$custom_feature.'
                                    </li>';
                                    }
                                } ?>
                            </div>
                        </ul>
                    </div>
                    <?php if(($plan['id']==$current_plan && !$paypal_enabled) || ($plan['id']==$current_plan && $paypal_enabled && ($user_info['plan_status']=='expiring' || $user_info['plan_status']=='active'))) { ?>
                        <?php if($stripe_enabled && $expiring_plan) { ?>
                        <a onclick="open_modal_reactivate_subscription();" style="color: #4e73df;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" href="#">
                            <?php echo _("Reactivate Subscription"); ?>
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    <?php } else if($paypal_enabled && $user_info['plan_status']=='expiring') { ?>
                        <div style="color: #4e73df;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white">
                            <?php echo _("Current Subscription (Canceled)"); ?>
                            <i class="fa fa-check"></i>
                        </div>
                    <?php } else if($twocheckout_enabled && $user_info['plan_status']=='expiring') { ?>
                        <div style="color: #4e73df;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white">
                            <?php echo _("Current Subscription (Canceled)"); ?>
                            <i class="fa fa-check"></i>
                        </div>
                    <?php } else { ?>
                        <div style="color: #4e73df;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white">
                            <?php echo _("Current Subscription"); ?>
                            <i class="fa fa-check"></i>
                        </div>
                    <?php } ?>
                    <?php } else { ?>
                    <?php if($stripe_enabled) { ?>
                    <?php if($plan['price']==0) { ?>
                    <?php if(!empty($plan['external_url'])) { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" target="_blank" href="<?php echo $plan['external_url']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Find out more"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" href="mailto:<?php echo $settings['contact_email']; ?>?subject=<?php echo $plan['name']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Contact Us"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-envelope"></i>
                            <?php } ?>
                        </a>
                    <?php } ?>
                    <?php } else { ?>
                        <?php if(empty($user_info['id_subscription_stripe'])) { ?>
                        <a onclick="redirect_to_checkout(<?php echo $plan['id']; ?>);return false;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white <?php echo ($expiring_plan) ? 'disabled' : ''; ?>" href="#">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Subscribe"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-shopping-bag"></i>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a onclick="change_plan_proration(<?php echo $plan['id']; ?>);return false;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white <?php echo (($current_frequency=='recurring' || $current_frequency=='month_year') && $plan['frequency']=='one_time') ? 'disabled' : ''; ?> <?php echo ($expiring_plan) ? 'disabled' : ''; ?>" href="#">
                            <?php echo _("Change Subscription"); ?>
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                    <?php } ?>
                    <?php } ?>
                    <?php } else if($paypal_enabled) { ?>
                    <?php if($plan['price']==0) { ?>
                    <?php if(!empty($plan['external_url'])) { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" target="_blank" href="<?php echo $plan['external_url']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Find out more"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" href="mailto:<?php echo $settings['contact_email']; ?>?subject=<?php echo $plan['name']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Contact Us"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-envelope"></i>
                            <?php } ?>
                        </a>
                    <?php } ?>
                    <?php } else { ?>
                    <?php if($plan['frequency']=='recurring' || $plan['frequency']=='month_year') { ?>
                    <?php if(empty($user_info['id_subscription_paypal'])) { ?>
                        <div style="display:contents" id="paypal_button_<?php echo $plan['id']; ?>"></div>
                        <script>
                            paypal.Buttons({
                                style: {
                                    layout: 'vertical',
                                    color: 'blue',
                                    shape: 'rect',
                                    label: 'subscribe',
                                    tagline: false,
                                    height: 49
                                },
                                createSubscription: function(data, actions) {
                                    return actions.subscription.create({
                                        'plan_id': '<?php echo $plan['id_plan_paypal']; ?>',
                                        'application_context': {
                                            'shipping_preference': 'NO_SHIPPING'
                                        }
                                    });
                                },
                                onApprove: function(data, actions) {
                                    save_paypal_subscription_id(<?php echo $id_user; ?>,'subscription',data.subscriptionID);
                                }
                            }).render('#paypal_button_<?php echo $plan['id']; ?>');
                        </script>
                    <?php if($plan['frequency']=='month_year' && $plan['price']>0 && $plan['price2']>0) { ?>
                        <div style="display:none;" id="paypal_button_<?php echo $plan['id']; ?>_y"></div>
                        <script>
                            paypal.Buttons({
                                style: {
                                    layout: 'vertical',
                                    color: 'blue',
                                    shape: 'rect',
                                    label: 'subscribe',
                                    tagline: false,
                                    height: 49
                                },
                                createSubscription: function(data, actions) {
                                    return actions.subscription.create({
                                        'plan_id': '<?php echo $plan['id_plan2_paypal']; ?>',
                                        'application_context': {
                                            'shipping_preference': 'NO_SHIPPING'
                                        }
                                    });
                                },
                                onApprove: function(data, actions) {
                                    save_paypal_subscription_id(<?php echo $id_user; ?>,'subscription',data.subscriptionID);
                                }
                            }).render('#paypal_button_<?php echo $plan['id']; ?>_y');
                        </script>
                    <?php } ?>
                    <?php } else { ?>
                        <a data-toggle="modal" data-target="#modal_change_plan_paypal" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white <?php echo (($current_frequency=='recurring' || $current_frequency=='month_year') && $plan['frequency']=='one_time') ? 'disabled' : ''; ?> <?php echo ($expiring_plan) ? 'disabled' : ''; ?>" href="#">
                            <?php echo _("Change Subscription"); ?>
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                    <?php } ?>
                    <?php } else { ?>
                        <div style="display:contents" id="paypal_button_<?php echo $plan['id']; ?>"></div>
                        <script>
                            paypal.Buttons({
                                style: {
                                    layout: 'vertical',
                                    color: 'blue',
                                    shape: 'rect',
                                    label: 'checkout',
                                    tagline: false,
                                    height: 49
                                },
                                createOrder: function(data, actions) {
                                    return actions.order.create({
                                        purchase_units: [{
                                            "custom_id":"<?php echo $plan['id']; ?>",
                                            "description":"<?php echo $app_name; ?> - <?php echo $plan['name']; ?>",
                                            "amount":{"currency_code":"<?php echo $plan['currency']; ?>","value":<?php echo $plan['price']; ?>},
                                            'application_context': {
                                                'shipping_preference': 'NO_SHIPPING'
                                            }
                                        }]
                                    });
                                },
                                onApprove: function(data, actions) {
                                    return actions.order.capture().then(function(orderData) {
                                        save_paypal_subscription_id(<?php echo $id_user; ?>,'order',orderData.id);
                                    });
                                },
                                onError: function(err) {
                                    console.log(err);
                                }
                            }).render('#paypal_button_<?php echo $plan['id']; ?>');
                        </script>
                    <?php } ?>
                    <?php } ?>
                    <?php } else if($twocheckout_enabled) { ?>
                    <?php if($plan['price']==0) { ?>
                    <?php if(!empty($plan['external_url'])) { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" target="_blank" href="<?php echo $plan['external_url']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Find out more"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" href="mailto:<?php echo $settings['contact_email']; ?>?subject=<?php echo $plan['name']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Contact Us"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-envelope"></i>
                            <?php } ?>
                        </a>
                    <?php } ?>
                    <?php } else { ?>
                        <?php if(empty($user_info['id_subscription_2checkout'])) { ?>
                        <a onclick="open_inline_checkout('<?php echo $plan['id_product_2checkout'] ?>','<?php echo $plan['id_product2_2checkout'] ?>','<?php echo $user_info['email']; ?>','<?php echo $twocheckout_redirect_url; ?>',<?php echo $settings['2checkout_live']; ?>,<?php echo $plan['id']; ?>);return false;" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white avangate_button <?php echo ($expiring_plan) ? 'disabled' : ''; ?>" href="#">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Subscribe"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-shopping-bag"></i>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a onclick="" class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white <?php echo ($current_frequency=='recurring' && $plan['frequency']=='one_time') ? 'disabled' : ''; ?> <?php echo ($expiring_plan) ? 'disabled' : ''; ?>" href="#">
                            <?php echo _("Change Subscription"); ?>
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                    <?php } ?>
                    <?php } ?>
                    <?php } else { ?>
                        <?php if(!empty($plan['external_url'])) { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" target="_blank" href="<?php echo $plan['external_url']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Find out more"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            <?php } ?>
                        </a>
                    <?php } else { ?>
                        <a class="card-footer d-flex align-items-center justify-content-between text-decoration-none bg-primary text-white" href="mailto:<?php echo $settings['contact_email']; ?>?subject=<?php echo $plan['name']; ?>">
                            <?php echo ($plan['button_type']=='custom' && !empty($plan['button_text'])) ? $plan['button_text'] : _("Contact Us"); ?>
                            <?php if(($plan['button_type']=='custom' && !empty($plan['button_icon']))) { ?>
                                <i class="<?php echo $plan['button_icon']; ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-envelope"></i>
                            <?php } ?>
                        </a>
                    <?php } ?>
                    <?php } ?>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php if($stripe_enabled && !$expiring_plan && (!empty($user_info['id_subscription_stripe']))) : ?>
    <div class="row mt-2 mb-4">
        <div class="col-md-12 text-center align-items-center">
            <span onclick="open_modal_delete_plan();" style="cursor: pointer" class="badge badge-red text-white badge-pill py-2 px-3 mt-1 mb-1 ml-1 mr-1"><?php echo _("cancel current subscription"); ?></span>
            <span onclick="redirect_to_setup();" style="cursor: pointer" class="badge badge-primary text-white badge-pill py-2 px-3 mt-1 mb-1 ml-1 mr-1"><?php echo _("modify payment details"); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if($paypal_enabled && !$expiring_plan && (!empty($user_info['id_subscription_paypal']))) : ?>
    <div class="row mt-2 mb-4">
        <div class="col-md-12 text-center align-items-center">
            <span onclick="open_modal_delete_plan_paypal();" style="cursor: pointer" class="badge badge-red text-white badge-pill py-2 px-3 mt-1 mb-1 ml-1 mr-1"><?php echo _("cancel current subscription"); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if($twocheckout_enabled && !$expiring_plan && (!empty($user_info['id_subscription_2checkout']))) : ?>
    <div class="row mt-2 mb-4">
        <div class="col-md-12 text-center align-items-center">
            <span onclick="open_modal_delete_plan_2checkout();" style="cursor: pointer" class="badge badge-red text-white badge-pill py-2 px-3 mt-1 mb-1 ml-1 mr-1"><?php echo _("cancel current subscription"); ?></span>
        </div>
    </div>
<?php endif; ?>

<div id="modal_redirect_checkout" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Redirecting to checkout page ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_redirect_setup" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Redirecting to payment setup page ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_change_plan" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Change Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("Are you sure you want to change your subscription?"); ?>
                    <br><br>
                    <?php echo _("New plan").": "; ?> <strong id="new_plan">--</strong>
                    <br>
                    <?php echo _("Next payment").": "; ?> <strong id="next_payment">--</strong>
                    <br>
                    <?php echo _("Subsequent payments").": "; ?> <strong id="subseq_payments">--</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_change_plan" onclick="" type="button" class="btn btn-success disabled"><i class="fas fa-check"></i> <?php echo _("Yes, Change"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_plan" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Cancel Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("Are you sure you want to cancel your current subscription?"); ?>
                    <br><br>
                    <?php echo _("Actual plan").": "; ?> <strong id="actual_plan">--</strong>
                    <br>
                    <?php echo _("Active until").": "; ?> <strong id="active_until">--</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_plan" onclick="cancel_subscription();" type="button" class="btn btn-danger disabled"><i class="fas fa-power-off"></i> <?php echo _("Yes, Cancel"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_plan_paypal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Cancel Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("Are you sure you want to cancel your current subscription?"); ?>
                    <br><br>
                    <?php echo _("Actual plan").": "; ?> <strong id="actual_plan_paypal">--</strong>
                    <br>
                    <?php echo _("Active until").": "; ?> <strong id="active_until_paypal">--</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_plan_paypal" onclick="cancel_subscription_paypal();" type="button" class="btn btn-danger disabled"><i class="fas fa-power-off"></i> <?php echo _("Yes, Cancel"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_plan_2checkout" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Cancel Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("Are you sure you want to cancel your current subscription?"); ?>
                    <br><br>
                    <?php echo _("Actual plan").": "; ?> <strong id="actual_plan_2checkout">--</strong>
                    <br>
                    <?php echo _("Active until").": "; ?> <strong id="active_until_2checkout">--</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_plan_2checkout" onclick="cancel_subscription_2checkout();" type="button" class="btn btn-danger disabled"><i class="fas fa-power-off"></i> <?php echo _("Yes, Cancel"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_reactivate_plan" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Reactivate Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("Are you sure you want to reactivate your canceled subscription?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_reactivate_plan" onclick="reactivate_subscription();" type="button" class="btn btn-success disabled"><i class="fas fa-reply"></i> <?php echo _("Yes, Reactivate"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_change_plan_paypal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Change Subscription"); ?></h5>
            </div>
            <div class="modal-body">
                <p>
                    <?php echo _("You must first cancel your current subscription to activate a new one"); ?>
                </p>
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
        window.id_user = <?php echo $id_user; ?>;
        var stripe_enabled = <?php echo $stripe_enabled; ?>;
        $(document).ready(function () {
            if(stripe_enabled) {
                window.stripe = Stripe('<?php echo $stripe_public_key; ?>');
            }
            $('.feature_with_description').tooltipster({
                theme: 'tooltipster-white',
                delay: 0,
                hideOnClick: true,
                contentAsHTML: true,
                trackerInterval: 100,
                trackOrigin: true,
                trackTooltip: true
            });
        });
        $('.collapse_all').on('show.bs.collapse', function () {
            $('.show_less').show();
            $('.show_more').hide();
        });
        $('.collapse_all').on('hide.bs.collapse', function () {
            $('.show_less').hide();
            $('.show_more').show();
        });
    })(jQuery); // End of use strict
</script>