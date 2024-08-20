<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = get_plan_permission($id_user)['create_landing'];
$settings = get_settings();
$change_plan = $settings['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
$landing = true;
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
    if($editor_permissions['landing']==0) {
        $landing = false;
    }
}
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","",$_SERVER['SCRIPT_NAME']);
$linkl = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","landing/index.php?code=",$_SERVER['SCRIPT_NAME']);
$linkl_f = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","landing/",$_SERVER['SCRIPT_NAME']);
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
?>

<?php include("check_plan.php"); ?>

<?php if(!$landing): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Landing Pages!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row mb-2">
    <div class="col-md-6">
        <button style="pointer-events:none;" id="btn_landing_editor" onclick="switch_landing_mode('editor');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> btn-primary"><i class="fas fa-table"></i> <?php echo _("Editor"); ?></button>
    </div>
    <div class="col-md-6">
        <button id="btn_landing_html" onclick="switch_landing_mode('html');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> btn-outline-primary"><i class="fas fa-code"></i> <?php echo _("HTML"); ?></button>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <iframe id="iframe_landing_editor" frameborder="none" style="width: 100%;" src="vendor/keditor/landing_editor.php?id_vt=<?php echo $id_virtualtour_sel; ?>"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <a href="#collapsePI" class="d-block card-header py-3 collapsed" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapsePI">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-hashtag"></i> <?php echo _("Meta Tag"); ?></h6>
            </a>
            <div class="collapse" id="collapsePI">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="meta_title"><?php echo _("Title"); ?></label>
                                <input oninput="change_meta_title();" onchange="change_meta_title();" type="text" class="form-control" id="meta_title" value="<?php echo $virtual_tour['meta_title_l']; ?>" />
                            </div>
                            <div class="form-group">
                                <label for="meta_description"><?php echo _("Description"); ?></label>
                                <textarea oninput="change_meta_description();" onchange="change_meta_description();" rows="3" class="form-control" id="meta_description"><?php echo $virtual_tour['meta_description_l']; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><?php echo _("Image"); ?></label>
                                <div style="display: none" id="div_delete_image_meta" class="form-group mt-2">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_image_meta('landing',<?php echo $id_virtualtour_sel; ?>);" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
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
                                    <?php if(empty($virtual_tour['meta_image_l'])) {
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
                                        $meta_image = $virtual_tour['meta_image_l'];
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
                                            <?php if(empty($virtual_tour['meta_title_l'])) {
                                                echo $virtual_tour['name'];
                                            } else {
                                                echo $virtual_tour['meta_title_l'];
                                            } ?>
                                        </h2>
                                        <div class="facebook-preview__description">
                                            <?php if(empty($virtual_tour['meta_description_l'])) {
                                                echo $virtual_tour['description'];
                                            } else {
                                                echo $virtual_tour['meta_description_l'];
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
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-share-alt"></i> <?php echo _("Share & Embed"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group <?php echo ($user_info['role']!='administrator') ? 'd-none' : 'd-inline-block' ?>">
                            <label for="show_in_first_page_l"><?php echo _("Show as first page"); ?> (<?php echo $base_url; ?>) <i title="<?php echo _("only visible to administrators"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                            <input <?php echo ($demo) ? 'disabled' : ''; ?> id="show_in_first_page_l" <?php echo ($virtual_tour['show_in_first_page_l']) ? 'checked' : ''; ?> type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="light" data-size="normal" data-on="<?php echo _("Yes"); ?>" data-off="<?php echo _("No"); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="linkl"><i class="fas fa-link"></i> <?php echo _("Link"); ?></label>
                            <div class="input-group mb-0">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="linkl" value="<?php echo $linkl . $virtual_tour['code']; ?>" />
                                <div class="input-group-append">
                                    <a title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $linkl . $virtual_tour['code']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#linkl">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $linkl . $virtual_tour['code']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                        <div style="margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style" data-a2a-url="<?php echo $linkl . $virtual_tour['code']; ?>">
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
                        <script async src="https://static.addtoany.com/menu/page.js"></script>
                        <!--<div id="social_share" style="font-size:12px"></div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="linkl_f"><i class="fas fa-link"></i> <?php echo _("Friendly Link"); ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text noselect" id="basic-addon3"><?php echo $linkl_f; ?></span>
                                </div>
                                <input <?php echo ($demo) ? 'disabled' : ''; ?> type="text" class="form-control bg-white" id="linkl_f" value="<?php echo $virtual_tour['friendly_l_url']; ?>" />
                                <div class="input-group-append <?php echo (empty($virtual_tour['friendly_l_url'])) ? 'disabled' : '' ; ?>">
                                    <a id="link_open" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $linkl_f . $virtual_tour['friendly_l_url']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button id="link_copy" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-text="<?php echo $linkl_f . $virtual_tour['friendly_l_url']; ?>">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button id="link_qr" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $linkl_f . $virtual_tour['friendly_l_url']; ?>');" class="btn btn-secondary">
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
                            <label for="code_f"><i class="fas fa-code"></i> <?php echo _("Embed Code"); ?></label>
                            <div class="input-group">
                                <textarea id="code_f" class="form-control" rows="2"><iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $linkl . $virtual_tour['code']; ?>"></iframe></textarea>
                                <div class="input-group-append">
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#code_f">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        window.link_f = '<?php echo $linkl_f; ?>';
        window.image_meta = '<?php echo $virtual_tour['meta_image_l']; ?>';
        window.image_meta_default = '<?php echo $virtual_tour['background_image']; ?>';
        window.title_meta_default = `<?php echo $virtual_tour['name']; ?>`;
        window.description_meta_default = `<?php echo $virtual_tour['description']; ?>`;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            $('.cpy_btn').tooltip({
                trigger: 'click',
                placement: 'bottom'
            });
            var clipboard = new ClipboardJS('.cpy_btn');
            clipboard.on('success', function(e) {
                setTooltip(e.trigger, window.backend_labels.copied+"!");
                hideTooltip(e.trigger);
            });
            $('#show_in_first_page_l').change(function() {
                if($(this).prop('checked')) {
                    var show_in_first_page = 1;
                } else {
                    var show_in_first_page = 0;
                }
                set_show_in_first_page(show_in_first_page,'landing');
            });
            /*$("#social_share").jsSocials({
                url: '<?php echo $linkl . $virtual_tour['code']; ?>',
                shareIn: "popup",
                showLabel: false,
                showCount: false,
                shares: ["email", "twitter", "facebook", "linkedin", "pinterest", "stumbleupon", "viber", "messenger", "telegram", "line" , "whatsapp"]
            });*/
            if(window.image_meta=='') {
                $('#div_delete_image_meta').hide();
                $('#div_upload_image_meta').show();
            } else {
                $('#div_delete_image_meta').show();
                $('#div_upload_image_meta').hide();
            }
            var container_h = $('#content-wrapper').height() - 230;
            $('.card-body iframe').attr('height',container_h+'px');
        });
        $(window).resize(function () {
            var container_h = $('#content-wrapper').height() - 230;
            $('.card-body iframe').attr('height',container_h+'px');
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
                        save_metadata('landing',window.id_virtualtour);
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
        $('#meta_title,#meta_description').on('input',function(){
            if(timer_meta) {
                clearTimeout(timer_meta);
            }
            timer_meta = setTimeout(function() {
                save_metadata('landing',window.id_virtualtour);
            },400);
        });

        var timer_furl;
        $('#linkl_f').on('input',function(){
            if(timer_furl) {
                clearTimeout(timer_furl);
            }
            timer_furl = setTimeout(function() {
                    change_friendly_url('landing','linkl_f',window.id_virtualtour);
            },400);
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

    window.switch_landing_mode = function(mode) {
        switch(mode) {
            case 'editor':
                $('#btn_landing_editor').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btn_landing_html').addClass('btn-outline-primary').removeClass('btn-primary');
                $('#btn_landing_editor').css('pointer-events','none');
                $('#btn_landing_html').css('pointer-events','initial');
                $('#iframe_landing_editor').contents().find("#landing_editor").show();
                $('#iframe_landing_editor').contents().find("#landing_editor_html").hide();
                document.getElementById('iframe_landing_editor').contentWindow.set_html_to_editor();
                break;
            case 'html':
                $('#btn_landing_editor').addClass('btn-outline-primary').removeClass('btn-primary');
                $('#btn_landing_html').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btn_landing_html').css('pointer-events','none');
                $('#btn_landing_editor').css('pointer-events','initial');
                $('#iframe_landing_editor').contents().find("#landing_editor").hide();
                $('#iframe_landing_editor').contents().find("#landing_editor_html").show();
                $('#iframe_landing_editor').contents().find("#landing_editor_html").css('opacity',1);
                document.getElementById('iframe_landing_editor').contentWindow.get_html_from_editor();
                break;
        }
    }
</script>