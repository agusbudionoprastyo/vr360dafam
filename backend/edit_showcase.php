<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_showcase = $_GET['id'];
$showcase = get_showcase($id_showcase,$id_user);
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","",$_SERVER['SCRIPT_NAME']);
$link = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","showcase/index.php?code=",$_SERVER['SCRIPT_NAME']);
$link_f = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("backend/index.php","showcase/",$_SERVER['SCRIPT_NAME']);
$settings = get_settings();
?>

<?php if(!$showcase): ?>
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

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> <?php echo _("Details"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label>
                            <input type="text" class="form-control" id="name" value="<?php echo $showcase['name']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="bg_color"><?php echo _("Background Color"); ?></label>
                            <input type="text" class="form-control" id="bg_color" value="<?php echo $showcase['bg_color']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_settings"><?php echo _("Sorting Mode"); ?></label>
                            <?php
                            $sort_settings = $showcase['sort_settings'];
                            if(!empty($sort_settings)) {
                                $sort_settings = json_decode($sort_settings,true);
                            } else {
                                $sort_settings = array();
                                $sort_settings['date']=1;
                                $sort_settings['relevance']=1;
                                $sort_settings['name']=1;
                                $sort_settings['category']=1;
                                $sort_settings['author']=1;
                                $sort_settings['views']=1;
                                $sort_settings['default']='date|asc';
                            }
                            ?>
                            <select id="sort_settings" data-iconBase="fa" data-tickIcon="fa-check" data-actions-box="true" data-selected-text-format="count > 4" data-count-selected-text="{0} <?php echo _("items selected"); ?>" data-deselect-all-text="<?php echo _("Deselect All"); ?>" data-select-all-text="<?php echo _("Select All"); ?>" data-none-selected-text="<?php echo _("Nothing selected"); ?>" data-none-results-text="<?php echo _("No results matched"); ?> {0}" class="form-control selectpicker" multiple>
                                <option id="date" <?php echo ($sort_settings['date']==1) ? 'selected' : ''; ?>><?php echo _("Date"); ?></option>
                                <option id="relevance" <?php echo ($sort_settings['relevance']==1) ? 'selected' : ''; ?>><?php echo _("Relevance (Custom Sort)"); ?></option>
                                <option id="name" <?php echo ($sort_settings['name']==1) ? 'selected' : ''; ?>><?php echo _("Name"); ?></option>
                                <option id="category" <?php echo ($sort_settings['category']==1) ? 'selected' : ''; ?>><?php echo _("Category"); ?></option>
                                <option id="author" <?php echo ($sort_settings['author']==1) ? 'selected' : ''; ?>><?php echo _("Author"); ?></option>
                                <option id="views" <?php echo ($sort_settings['views']==1) ? 'selected' : ''; ?>><?php echo _("Views"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_default"><?php echo _("Default Sorting"); ?></label>
                            <select id="sort_default" class="form-control">
                                <option id="date|asc" <?php echo ($sort_settings['default']=='date|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Date"); ?></option>
                                <option id="date|desc" <?php echo ($sort_settings['default']=='date|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Date"); ?></option>
                                <option id="relevance|asc" <?php echo ($sort_settings['default']=='relevance|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Relevance (Custom Sort)"); ?></option>
                                <option id="relevance|desc" <?php echo ($sort_settings['default']=='relevance|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Relevance (Custom Sort)"); ?></option>
                                <option id="name|asc" <?php echo ($sort_settings['default']=='name|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Name"); ?></option>
                                <option id="name|desc" <?php echo ($sort_settings['default']=='name|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Name"); ?></option>
                                <option id="category|asc" <?php echo ($sort_settings['default']=='category|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Category"); ?></option>
                                <option id="category|desc" <?php echo ($sort_settings['default']=='category|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Category"); ?></option>
                                <option id="author|asc" <?php echo ($sort_settings['default']=='author|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Author"); ?></option>
                                <option id="author|desc" <?php echo ($sort_settings['default']=='author|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Author"); ?></option>
                                <option id="views|asc" <?php echo ($sort_settings['default']=='views|asc') ? 'selected' : ''; ?>>↓ <?php echo _("Views"); ?></option>
                                <option id="views|desc" <?php echo ($sort_settings['default']=='views|desc') ? 'selected' : ''; ?>>↑ <?php echo _("Views"); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="header_html"><?php echo _("Custom HTML Header"); ?></label><br>
                            <div id="header_html"><?php echo htmlspecialchars(str_replace('\"','"',str_replace(array("<br />","<br>","<br/>"),"\n",$showcase['header_html']))); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="footer_html"><?php echo _("Custom HTML Footer"); ?></label><br>
                            <div id="footer_html"><?php echo htmlspecialchars(str_replace('\"','"',str_replace(array("<br />","<br>","<br/>"),"\n",$showcase['footer_html']))); ?></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label><?php echo _("Logo"); ?></label>
                        <div style="background-color:#4e73df;display: none" id="div_image_logo" class="col-md-12">
                            <img style="width: 100%" src="../viewer/content/<?php echo $showcase['logo']; ?>" />
                        </div>
                        <div style="display: none" id="div_delete_logo" class="col-md-12 mt-4">
                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_s_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                        </div>
                        <div style="display: none" id="div_upload_logo">
                            <form id="frm" action="ajax/upload_s_logo_image.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="file" class="form-control" id="txtFile" name="txtFile" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Logo Image"); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="preview text-center">
                                            <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
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
                    <div class="col-md-6">
                        <label><?php echo _("Banner image"); ?></label>
                        <div style="display: none" id="div_image_banner" class="col-md-12">
                            <img style="width: 100%" src="../viewer/content/<?php echo $showcase['banner']; ?>" />
                        </div>
                        <div style="display: none" id="div_delete_banner" class="col-md-12 mt-4">
                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_s_banner();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                        </div>
                        <div style="display: none" id="div_upload_banner">
                            <form id="frm_b" action="ajax/upload_s_banner_image.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="file" class="form-control" id="txtFile_b" name="txtFile_b" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_b" value="<?php echo _("Upload Banner Image"); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="preview text-center">
                                            <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
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
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header pt-3 pb-2">
                <h6 class="m-0 font-weight-bold text-primary float-left d-inline-block"><i class="fas fa-route"></i> <?php echo _("Assigned Virtual Tours"); ?><br><span style="font-size:12px;font-weight:normal;vertical-align:text-top;" class="ml-3">* V=<?php echo _("Viewer"); ?>, L=<?php echo _("Landing"); ?></span></h6>
                <span class="float-right d-inline-block">
                    <input class="form-control form-control-sm" id="search_vt" type="search" placeholder="<?php echo _("Search"); ?>" />
                </span>
                <span class="float-right d-inline-block">
                    <button id="btn_start_sort" onclick="sort_vt_showcase();" class="btn btn-sm btn-primary mr-3"><i class="fas fa-sort-amount-down-alt"></i> <?php echo _("Sort List"); ?></button>
                    <button id="btn_stop_sort" style="display:none;" onclick="exit_sort_vt_showcase();" class="btn btn-sm btn-success mr-3"><i class="fas fa-sort-amount-down-alt"></i> <?php echo _("Confirm"); ?></button>
                </span>
            </div>
            <div class="card-body">
                <div class="row list_s_vt ui-sortable">
                    <?php echo get_showcase_virtualtours($id_user,$id_showcase); ?>
                </div>
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
                                <input oninput="change_meta_title();" onchange="change_meta_title();" type="text" class="form-control" id="meta_title" value="<?php echo $showcase['meta_title']; ?>" />
                            </div>
                            <div class="form-group">
                                <label for="meta_description"><?php echo _("Description"); ?></label>
                                <textarea oninput="change_meta_description();" onchange="change_meta_description();" rows="3" class="form-control" id="meta_description"><?php echo $showcase['meta_description']; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><?php echo _("Image"); ?></label>
                                <div style="display: none" id="div_delete_image_meta" class="form-group mt-2">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_image_meta('showcase',<?php echo $id_showcase; ?>);" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
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
                                    <?php if(empty($showcase['meta_image'])) {
                                        if(empty($virtual_tour['background_image'])) {
                                            $meta_image = '';
                                        } else {
                                            $meta_image = $showcase['background_image'];
                                        }
                                    } else {
                                        $meta_image = $showcase['meta_image'];
                                    } ?>
                                    <img class="facebook-preview__image <?php echo (empty($meta_image)) ? 'd-none' : ''; ?>" src="../viewer/content/<?php echo $meta_image; ?>" alt="">
                                    <div class="facebook-preview__content">
                                        <div class="facebook-preview__url">
                                            <?php echo $_SERVER['SERVER_NAME']; ?>
                                        </div>
                                        <h2 class="facebook-preview__title">
                                            <?php if(empty($showcase['meta_title'])) {
                                                echo $showcase['name'];
                                            } else {
                                                echo $showcase['meta_title'];
                                            } ?>
                                        </h2>
                                        <div class="facebook-preview__description">
                                            <?php if(!empty($showcase['meta_description'])) {
                                                echo $showcase['meta_description'];
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
                            <label for="show_in_first_page"><?php echo _("Show as first page"); ?> (<?php echo $base_url; ?>) <i title="<?php echo _("only visible to administrators"); ?>" class="help_t fas fa-question-circle"></i></label><br>
                            <input <?php echo ($demo) ? 'disabled' : ''; ?> id="show_in_first_page" <?php echo ($showcase['show_in_first_page']) ? 'checked' : ''; ?> type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="light" data-size="normal" data-on="<?php echo _("Yes"); ?>" data-off="<?php echo _("No"); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="link"><i class="fas fa-link"></i> <?php echo _("Link"); ?></label>
                            <div class="input-group mb-0">
                                <input readonly type="text" class="form-control bg-white mb-0 pb-0" id="link" value="<?php echo $link . $showcase['code']; ?>" />
                                <div class="input-group-append">
                                    <a title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link . $showcase['code']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#link">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link . $showcase['code']; ?>');" class="btn btn-secondary">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <?php $array_share_providers = explode(",",$settings['share_providers']); ?>
                        <div style="margin-top: 10px" class="a2a_kit a2a_kit_size_32 a2a_default_style" data-a2a-url="<?php echo $link . $showcase['code']; ?>">
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
                            <label for="link_f"><i class="fas fa-link"></i> <?php echo _("Friendly Link"); ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text noselect" id="basic-addon3"><?php echo $link_f; ?></span>
                                </div>
                                <input <?php echo ($demo) ? 'disabled' : ''; ?> type="text" class="form-control bg-white" id="link_f" value="<?php echo $showcase['friendly_url']; ?>" />
                                <div class="input-group-append <?php echo (empty($showcase['friendly_url'])) ? 'disabled' : '' ; ?>">
                                    <a id="link_open" title="<?php echo _("OPEN LINK"); ?>" class="btn btn-success" href="<?php echo $link_f . $showcase['friendly_url']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button id="link_copy" title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-text="<?php echo $link_f . $showcase['friendly_url'];; ?>">
                                        <i class="far fa-clipboard"></i>
                                    </button>
                                    <button id="link_qr" title="<?php echo _("QR CODE"); ?>" onclick="open_qr_code_modal('<?php echo $link_f . $showcase['friendly_url']; ?>');" class="btn btn-secondary">
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
                            <label for="code"><i class="fas fa-code"></i> <?php echo _("Embed Code"); ?></label>
                            <div class="input-group">
                                <textarea id="code" class="form-control" rows="2"><iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="600px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $link . $showcase['code']; ?>"></iframe></textarea>
                                <div class="input-group-append">
                                    <button title="<?php echo _("COPY TO CLIPBOARD"); ?>" class="btn btn-primary cpy_btn" data-clipboard-target="#code">
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

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow mb-12">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fab fa-css3-alt"></i> <?php echo _("Custom CSS"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div style="position: relative;width: 100%;height: 400px;" class="editors_css" id="custom_s"><?php echo get_editor_css_content_s('custom_'.$showcase['code']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_showcase" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Showcase"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the showcase?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_showcase" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
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
        window.showcase_need_save = false;
        window.id_showcase = <?php echo $id_showcase; ?>;
        window.bg_color_spectrum = null;
        window.link_f = '<?php echo $link_f; ?>';
        window.s_logo_image = '<?php echo $showcase['logo']; ?>';
        window.s_banner_image = '<?php echo $showcase['banner']; ?>';
        window.editor_css = null;
        window.editor_html_h = null;
        window.editor_html_f = null;
        window.image_meta = '<?php echo $showcase['meta_image']; ?>';
        window.image_meta_default = '<?php echo $showcase['banner']; ?>';
        window.title_meta_default = `<?php echo $showcase['name']; ?>`;
        window.description_meta_default = ``;
        $(document).ready(function () {
            $(".list_s_vt").sortable({
                itemSelector: ".vt_block",
                handle: '.move_vt',
                cursor: "grabbing",
                update: function(event, ui) {
                    fix_vt_priority();
                }
            });
            $(".list_s_vt").sortable('disable');
            window.editor_css = ace.edit('custom_s');
            window.editor_css.session.setMode("ace/mode/css");
            window.editor_css.setOption('enableLiveAutocompletion',true);
            window.editor_css.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                window.editor_css.setTheme("ace/theme/one_dark");
            }
            window.editor_html_h = ace.edit('header_html');
            window.editor_html_h.session.setMode("ace/mode/html");
            window.editor_html_h.session.setUseWrapMode(true);
            window.editor_html_h.setOption('enableLiveAutocompletion',true);
            window.editor_html_h.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                window.editor_html_h.setTheme("ace/theme/one_dark");
            }
            window.editor_html_f = ace.edit('footer_html');
            window.editor_html_f.session.setMode("ace/mode/html");
            window.editor_html_f.session.setUseWrapMode(true);
            window.editor_html_f.setOption('enableLiveAutocompletion',true);
            window.editor_html_f.setShowPrintMargin(false);
            if($('body').hasClass('dark_mode')) {
                window.editor_html_f.setTheme("ace/theme/one_dark");
            }
            window.bg_color_spectrum = $('#bg_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false
            });
            $('.cpy_btn').tooltip({
                trigger: 'click',
                placement: 'bottom'
            });
            var clipboard = new ClipboardJS('.cpy_btn');
            clipboard.on('success', function(e) {
                setTooltip(e.trigger, window.backend_labels.copied+"!");
                hideTooltip(e.trigger);
            });
            $('#show_in_first_page').change(function() {
                if($(this).prop('checked')) {
                    var show_in_first_page = 1;
                } else {
                    var show_in_first_page = 0;
                }
                set_show_in_first_page(show_in_first_page,'showcase');
            });
            /*$("#social_share").jsSocials({
                url: '<?php echo $link . $showcase['code']; ?>',
                shareIn: "popup",
                showLabel: false,
                showCount: false,
                shares: ["email", "twitter", "facebook", "linkedin", "pinterest", "stumbleupon", "viber", "messenger", "telegram", "line" , "whatsapp"]
            });*/
            if(window.s_logo_image=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            if(window.s_banner_image=='') {
                $('#div_delete_banner').hide();
                $('#div_image_banner').hide();
                $('#div_upload_banner').show();
            } else {
                $('#div_delete_banner').show();
                $('#div_image_banner').show();
                $('#div_upload_banner').hide();
            }
            if(window.image_meta=='') {
                $('#div_delete_image_meta').hide();
                $('#div_upload_image_meta').show();
            } else {
                $('#div_delete_image_meta').show();
                $('#div_upload_image_meta').hide();
            }
            var timer_furl;
            $('#link_f').on('input',function(){
                if(timer_furl) {
                    clearTimeout(timer_furl);
                }
                timer_furl = setTimeout(function() {
                    change_friendly_url('showcase','link_f',window.id_showcase);
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
                            $('.facebook-preview__image').attr('src','../viewer/content/'+window.image_meta);
                            $('.facebook-preview__image').removeClass('d-none');
                            $('#div_delete_image_meta').show();
                            $('#div_upload_image_meta').hide();
                            save_metadata('showcase',window.id_showcase);
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
                    save_metadata('showcase',window.id_showcase);
                },400);
            });
        });
        $("input[type='text']").change(function(){
            if($(this).attr('id')!='link_f' && $(this).attr('id')!='meta_title' && $(this).attr('id')!='meta_description') {
                window.showcase_need_save = true;
            }
        });
        $("input[type='checkbox']").change(function(){
            window.showcase_need_save = true;
        });
        $("select").change(function(){
            window.showcase_need_save = true;
        });
        $(window).on('beforeunload', function(){
            if(window.showcase_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict

    $("#search_vt").on("keyup input", function() {
        var value = $(this).val().toLowerCase();
        $(".list_s_vt div").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
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
                    window.showcase_need_save = true;
                    window.s_logo_image = evt.target.responseText;
                    $('#div_image_logo img').attr('src','../viewer/content/'+window.s_logo_image);
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
            $('.progress').hide();
        }else{
            $('.progress').show();
        }
    }

    function show_error(error){
        $('.progress').hide();
        $('#error').show();
        $('#error').html(error);
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
                    window.showcase_need_save = true;
                    window.s_banner_image = evt.target.responseText;
                    $('#div_image_banner img').attr('src','../viewer/content/'+window.s_banner_image);
                    $('#div_delete_banner').show();
                    $('#div_image_banner').show();
                    $('#div_upload_banner').hide();
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
            $('.progress').hide();
        }else{
            $('.progress').show();
        }
    }

    function show_error_b(error){
        $('.progress').hide();
        $('#error_b').show();
        $('#error_b').html(error);
    }

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

    function fix_vt_priority() {
        var priority = 1;
        $('.vt_block').each(function() {
            if($(this).is(':visible')) {
                $(this).find('.vt_priority').val(priority);
                priority++;
            }
        });
    }

    function sort_vt_showcase() {
        $("#search_vt").val('');
        $('#search_vt').prop('disabled',true);
        $("#search_vt").trigger('input');
        $('.vt_block').each(function() {
            if(!$(this).find('input[type="checkbox"]').is(':checked')) {
                $(this).hide();
            }
        });
        $('.vt_block select').hide();
        $('.vt_block input[type="checkbox"]').hide();
        $('.vt_block input[type="checkbox"]').prop('disabled',true);
        $('.vt_block .vt_priority').show();
        $('.vt_block .move_vt').show();
        $(".list_s_vt").sortable('enable');
        fix_vt_priority();
        $('#btn_start_sort').hide();
        $('#btn_stop_sort').show();
    }

    function exit_sort_vt_showcase() {
        $('#search_vt').prop('disabled',false);
        $('.vt_block').show();
        $('.vt_block select').show();
        $('.vt_block input[type="checkbox"]').show();
        $('.vt_block input[type="checkbox"]').prop('disabled',false);
        $('.vt_block .vt_priority').hide();
        $('.vt_block .move_vt').hide();
        $(".list_s_vt").sortable('disable');
        $('#btn_start_sort').show();
        $('#btn_stop_sort').hide();
    }
</script>