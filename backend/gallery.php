<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$count_gallery_images_create = check_plan_gallery_images_count($id_user,$id_virtualtour_sel);
$plan_permissions = get_plan_permission($id_user);
$can_create = $plan_permissions['create_gallery'];
$can_download_slieshow = $plan_permissions['enable_download_slideshow'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $tmp_languages = get_languages_vt();
    $array_languages = $tmp_languages[0];
    $default_language = $tmp_languages[1];
    $gallery = true;
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
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['gallery']==0) {
            $gallery = false;
        }
    }
    $show_in_ui = $virtual_tour['show_gallery'];
    $in_progress_slideshow = "";
    $result = $mysqli->query("SELECT id,TIMESTAMPDIFF(MINUTE,date_time,NOW()) as diff_time FROM svt_job_queue WHERE id_virtualtour=$id_virtualtour_sel AND type='slideshow' LIMIT 1;");
    if($result) {
        if($result->num_rows==1) {
            $slideshow = true;
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_job = $row['id'];
            $diff_time = $row['diff_time'];
            $in_progress_slideshow .= "<div class='background_job' data-id='$id_job'><i class='fas fa-spin fa-circle-notch' aria-hidden='true'></i><i style='display:none;' class='fas fa-check' aria-hidden='true'></i></div>";
            $in_progress_slideshow .= "<button style='pointer-events:none' class='btn btn-block btn-sm btn-primary mt-1 btn_progress_$id_job'><i class='fas fa-hammer'></i>&nbsp;&nbsp;"._("IN PROGRESS")." ...</button>";
            $in_progress_slideshow .= "<button style='display:none;' onclick='window.location.reload();' class='btn btn-block btn-sm btn-success mt-1 btn_reload_$id_job'><i class='fas fa-redo-alt'></i>&nbsp;&nbsp;"._("RELOAD THE PAGE")."</button>";
            if($diff_time>5) {
                $in_progress_slideshow .= "<button onclick='abort_job_queue($id_job);' class='btn btn-block btn-sm btn-danger mt-1'><i class='fas fa-times'></i>&nbsp;&nbsp;"._("ABORT")."</button>";
            }
        } else {
            if($s3_enabled) {
                $path_slideshow = "s3://$s3_bucket_name/viewer/gallery/".$id_virtualtour_sel."_slideshow.mp4";
            } else {
                $path_slideshow = '../viewer/gallery/'.$id_virtualtour_sel.'_slideshow.mp4';
            }
            if(file_exists($path_slideshow)) {
                $slideshow = true;
            } else {
                $slideshow = false;
            }
        }
    }
    if(!empty($virtual_tour['gallery_params'])) {
        $gallery_params = json_decode($virtual_tour['gallery_params'],true);
        if(!isset($gallery_params['watermark_opacity'])) {
            $gallery_params['watermark_opacity']=1;
        }
        if(!isset($gallery_params['gallery_transition'])) {
            $gallery_params['gallery_transition']='swipe';
        }
        if(!isset($gallery_params['gallery_thumbs'])) {
            $gallery_params['gallery_thumbs']='bottomOverMedia';
        }
        if(!isset($gallery_params['gallery_autoplay'])) {
            $gallery_params['gallery_autoplay']=false;
        }
        if(!isset($gallery_params['gallery_slide_duration'])) {
            $gallery_params['gallery_slide_duration']=4;
        }
    } else {
        $gallery_params = array();
        $gallery_params['width']=1920;
        $gallery_params['height']=1080;
        $gallery_params['slide_duration']=5;
        $gallery_params['fade_duration']=1;
        $gallery_params['zoom_rate']=0.1;
        $gallery_params['watermark']='none';
        $gallery_params['watermark_opacity']=1;
        $gallery_params['audio']='none';
        $gallery_params['gallery_transition']='swipe';
        $gallery_params['gallery_thumbs']='bottomOverMedia';
        $gallery_params['gallery_autoplay']=false;
        $gallery_params['gallery_slide_duration']=4;
    }
    $settings = get_settings();
    $slideshow_type = $settings['slideshow'];
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$gallery): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot create Gallery on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Gallery!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(($user_info['plan_status']=='active') || ($user_info['plan_status']=='expiring')) { ?>
    <?php if($count_gallery_images_create>=0) : ?>
        <div class="card bg-warning text-white shadow mb-3">
            <div class="card-body">
                <?php echo sprintf(_('You have %s remaining uploads!'),$count_gallery_images_create); ?>
            </div>
        </div>
    <?php endif; ?>
<?php } else { $count_gallery_images_create=0; } ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-grip-horizontal"></i> <?php echo _("Images List"); ?> <i style="font-size:12px">(<?php echo _("drag images to change order"); ?>)</i></h6>
            </div>
            <div class="card-body">
                <?php if($create_content) : ?><form action="ajax/upload_gallery_image.php" class="dropzone mb-3 noselect <?php echo ($demo || $disabled_upload || $count_gallery_images_create==0) ? 'disabled' : ''; ?>" id="gallery-dropzone"></form><?php endif; ?>
                <div id="list_images" class="noselect">
                    <p><?php echo _("Loading images ..."); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="gallery_mode_div" class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-th"></i> <?php echo _("Gallery Mode"); ?> <i title="<?php echo _("type of gallery visible in the tour"); ?>" class="help_t fas fa-question-circle"></i></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <button id="btn_gallerymode_images" onclick="switch_gallery_mode('images');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> <?php echo ($virtual_tour['gallery_mode']=='images') ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-images"></i> <?php echo _("Gallery of Images"); ?></button>
                    </div>
                    <div class="col-md-6">
                        <button id="btn_gallerymode_slideshow" onclick="switch_gallery_mode('slideshow');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> <?php echo ($virtual_tour['gallery_mode']=='slideshow') ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-file-video"></i> <?php echo _("Video Slideshow"); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="gallery_div" class="row <?php echo ($virtual_tour['gallery_mode']=='slideshow') ? 'd-none' : ''; ?>">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-images"></i> <?php echo _("Gallery of Images"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="g_transition"><?php echo _("Transition"); ?></label>
                            <select class="form-control" id="g_transition">
                                <option <?php echo ($gallery_params['gallery_transition']=='slideAppear') ? 'selected' : ''; ?> id="slideAppear"><?php echo _("Slide Appear"); ?></option>
                                <option <?php echo ($gallery_params['gallery_transition']=='swipe') ? 'selected' : ''; ?> id="swipe"><?php echo _("Swipe"); ?></option>
                                <option <?php echo ($gallery_params['gallery_transition']=='swipe2') ? 'selected' : ''; ?> id="swipe2"><?php echo _("Swipe 2"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="g_thumbs"><?php echo _("Thumbnails"); ?></label>
                            <select class="form-control" id="g_thumbs">
                                <option <?php echo ($gallery_params['gallery_thumbs']=='none') ? 'selected' : ''; ?> id="none"><?php echo _("None"); ?></option>
                                <option <?php echo ($gallery_params['gallery_thumbs']=='bottomOverMedia') ? 'selected' : ''; ?> id="bottomOverMedia"><?php echo _("Bottom Over Media"); ?></option>
                                <option <?php echo ($gallery_params['gallery_thumbs']=='bottom') ? 'selected' : ''; ?> id="bottom"><?php echo _("Bottom"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="g_autoplay"><?php echo _("Autoplay"); ?></label><br>
                            <input onchange="change_gallery_autoplay();" type="checkbox" id="g_autoplay" <?php echo ($gallery_params['gallery_autoplay']) ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="g_slide_duration"><?php echo _("Slide Duration"); ?></label>
                            <div class="input-group <?php echo (!$gallery_params['gallery_autoplay']) ? 'disabled' : ''; ?>">
                                <input type="number" min="0.1" step="0.1" class="form-control" id="g_slide_duration" value="<?php echo $gallery_params['gallery_slide_duration']; ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label style="opacity:0">.</label><br>
                        <a id="save_gallery_btn" href="#" onclick="save_gallery();return false;" class="btn btn-success btn-block <?php echo ($demo) ? 'disabled_d' : ''; ?>"><i class="fas fa-save"></i>&nbsp;&nbsp;<?php echo _("SAVE"); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="slideshow_div" class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-video"></i> <?php echo _("Video Slideshow"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-<?php echo ($slideshow) ? '8' : '12'; ?>">
                        <div class="row">
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_width"><?php echo _("Width"); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="s_width" value="<?php echo $gallery_params['width']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">px</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_height"><?php echo _("Height"); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="s_height" value="<?php echo $gallery_params['height']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">px</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_slide_duration"><?php echo _("Slide Duration"); ?></label>
                                    <div class="input-group">
                                        <input type="number" min="1" step="0.1" class="form-control" id="s_slide_duration" value="<?php echo $gallery_params['slide_duration']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_fade_duration"><?php echo _("Fade Duration"); ?></label>
                                    <div class="input-group">
                                        <input type="number" min="0" step="0.1" class="form-control" id="s_fade_duration" value="<?php echo $gallery_params['fade_duration']; ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_zoom_rate"><?php echo _("Zoom Rate"); ?></label>
                                    <input min="0" max="1" step="0.1" id="s_zoom_rate" type="range" class="form-control-range" value="<?php echo $gallery_params['zoom_rate']; ?>">
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_watermark"><?php echo _("Watermark"); ?> <i title="<?php echo _("it uses the logo of the tour."); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select class="form-control" id="s_watermark">
                                        <option <?php echo ($gallery_params['watermark']=='none') ? 'selected' : ''; ?> id="none"><?php echo _("None"); ?></option>
                                        <option <?php echo ($gallery_params['watermark']=='top_left') ? 'selected' : ''; ?> id="top_left"><?php echo _("Top Left"); ?></option>
                                        <option <?php echo ($gallery_params['watermark']=='top_right') ? 'selected' : ''; ?> id="top_right"><?php echo _("Top Right"); ?></option>
                                        <option <?php echo ($gallery_params['watermark']=='bottom_left') ? 'selected' : ''; ?> id="bottom_left"><?php echo _("Bottom Left"); ?></option>
                                        <option <?php echo ($gallery_params['watermark']=='bottom_right') ? 'selected' : ''; ?> id="bottom_right"><?php echo _("Bottom Right"); ?></option>
                                        <option <?php echo ($gallery_params['watermark']=='center') ? 'selected' : ''; ?> id="center"><?php echo _("Center"); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_watermark_opacity"><?php echo _("Watermark Opacity"); ?></label>
                                    <input min="0" max="1" step="0.1" id="s_watermark_opacity" type="range" class="form-control-range" value="<?php echo $gallery_params['watermark_opacity']; ?>">
                                </div>
                            </div>
                            <div class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label for="s_audio"><?php echo _("Audio"); ?> <i title="<?php echo _("audio file must be uploaded into music library."); ?>" class="help_t fas fa-question-circle"></i></label>
                                    <select onchange="change_slideshow_audio()" class="form-control" id="s_audio">
                                        <option <?php echo ($gallery_params['audio']=='0') ? 'selected' : ''; ?> id="0"><?php echo _("No Audio"); ?></option>
                                        <?php echo get_option_exist_song($_SESSION['id_user'],$id_virtualtour_sel,$gallery_params['audio']); ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: none" id="div_player_s_audio" class="col-md-<?php echo ($slideshow) ? '4' : '3'; ?>">
                                <div class="form-group">
                                    <label><button id="btn_sync_audio" onclick="sync_with_audio_slideshow();" class="btn btn-sm btn-outline-primary disabled" type="button"><?php echo _("Sync with Audio"); ?></button></label><br>
                                    <audio controls>
                                        <source src="" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button id="btn_generate_slideshow" onclick="generate_slideshow();" class="btn btn-block btn-primary disabled <?php echo ($demo) ? 'disabled_d' : ''; ?> <?php echo (!empty($in_progress_slideshow)) ? 'disabled_d' : ''; ?>"><i class="fas fa-arrow-right"></i>&nbsp;&nbsp;<?php echo _("GENERATE"); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" style="display:<?php echo ($slideshow) ? 'block' : 'none'; ?>">
                        <?php if(!empty($in_progress_slideshow)) {
                            echo $in_progress_slideshow;
                        } else { ?>
                            <video id="slidewhow_video" style="width:100%;" controls controlsList="nodownload">
                                <source src="" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <?php if($can_download_slieshow) : ?>
                            <button onclick="download_file('<?php echo ($s3_enabled) ? $s3_url."viewer/gallery/".$id_virtualtour_sel."_slideshow.mp4" : "../viewer/gallery/".$id_virtualtour_sel."_slideshow.mp4"; ?>');" class="btn btn-sm btn-block btn-primary <?php echo ($demo) ? 'disabled' : ''; ?>"><i class="fas fa-download"></i>&nbsp;&nbsp;<?php echo _("DOWNLOAD"); ?></button>
                            <?php endif; ?>
                            <button onclick="delete_slideshow(<?php echo $id_virtualtour_sel; ?>);" class="btn btn-sm btn-block btn-danger <?php echo ($demo) ? 'disabled' : ''; ?>"><i class="fas fa-trash"></i>&nbsp;&nbsp;<?php echo _("DELETE"); ?></button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_caption" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="title"><?php echo _("Title"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'title'); ?>
                            <input type="text" class="form-control" id="title" />
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <input style="display:none;" type="text" class="form-control input_lang" data-target-id="title" data-lang="<?php echo $lang; ?>" value="" />
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description"><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'description'); ?>
                            <textarea id="description" class="form-control" rows="3"></textarea>
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <textarea rows="3" style="display:none;" class="form-control input_lang" data-target-id="description" data-lang="<?php echo $lang; ?>"></textarea>
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_caption" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_generate_slideshow" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Generate slideshow"); ?></h5>
            </div>
            <div class="modal-body">
                <span><i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Generation in progress, please wait ... Do not close this window!"); ?></span><br><br>
                <button onclick="continue_w_slideshow();" id="btn_continue_w" class="btn btn-xs btn-block btn-primary d-none"><?php echo _("If you don't want to wait click here to continue working (a new tab will be opened)"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.gallery_images = [];
        window.slideshow_type = '<?php echo $slideshow_type; ?>';
        window.audio_duration = 0;
        window.count_gallery_images_create = <?php echo $count_gallery_images_create; ?>;
        Dropzone.autoDiscover = false;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            $('#slidewhow_video').bind('contextmenu',function() { return false; });
            $('.help_t').tooltip();
            get_gallery_images(id_virtualtour);
            var count_job = 0;
            var array_jobs = [];
            $('.background_job').each(function() {
                var id = $(this).attr('data-id');
                array_jobs.push(id);
                count_job=count_job+1;
            });
            if(count_job>0) {
                setInterval(function() {
                    get_job_queue(window.id_virtualtour,0,'slideshow',array_jobs);
                },5000);
            }
            setTimeout(function () {
                if(window.s3_enabled==1) {
                    $('#slidewhow_video').attr('src',window.s3_url+'viewer/gallery/'+id_virtualtour+'_slideshow.mp4');
                } else {
                    $('#slidewhow_video').attr('src','../viewer/gallery/'+id_virtualtour+'_slideshow.mp4');
                }
                change_slideshow_audio();
            },400);
            var gallery_dropzone = new Dropzone("#gallery-dropzone", {
                    url: "ajax/upload_gallery_image.php",
                    parallelUploads: 1,
                    maxFilesize: 20,
                    timeout: 120000,
                    dictDefaultMessage: "<?php echo _("Drop files or click here to upload"); ?>",
                    dictFallbackMessage: "<?php echo _("Your browser does not support drag'n'drop file uploads."); ?>",
                    dictFallbackText: "<?php echo _("Please use the fallback form below to upload your files like in the olden days."); ?>",
                    dictFileTooBig: "<?php echo sprintf(_("File is too big (%sMiB). Max filesize: %sMiB."),'{{filesize}}','{{maxFilesize}}'); ?>",
                    dictInvalidFileType: "<?php echo _("You can't upload files of this type."); ?>",
                    dictResponseError: "<?php echo sprintf(_("Server responded with %s code."),'{{statusCode}}'); ?>",
                    dictCancelUpload: "<?php echo _("Cancel upload"); ?>",
                    dictCancelUploadConfirmation: "<?php echo _("Are you sure you want to cancel this upload?"); ?>",
                    dictRemoveFile: "<?php echo _("Remove file"); ?>",
                    dictMaxFilesExceeded: "<?php echo _("You can not upload any more files."); ?>",
                    acceptedFiles: 'image/*',
                    <?php if($count_gallery_images_create>=0) : ?>
                    maxFiles: <?php echo $count_gallery_images_create; ?>,
                    <?php endif; ?>
                });
            gallery_dropzone.on("addedfile", function(file) {
                $('#list_images').addClass('disabled');
                $('#gallery_mode_div').addClass('disabled');
            });
            gallery_dropzone.on("success", function(file,rsp) {
                add_image_to_gallery(id_virtualtour,rsp);
            });
            gallery_dropzone.on("queuecomplete", function() {
                if(window.count_gallery_images_create==-1) {
                    $('#list_images').removeClass('disabled');
                    $('#gallery_mode_div').removeClass('disabled');
                    gallery_dropzone.removeAllFiles();
                } else {
                    setTimeout(function() {
                        location.href='index.php?p=gallery';
                    },1000);
                }
            });
        });
    })(jQuery); // End of use strict

    function change_gallery_autoplay() {
        if($('#g_autoplay').is(':checked')) {
            $('#g_slide_duration').parent().removeClass('disabled');
        } else {
            $('#g_slide_duration').parent().addClass('disabled');
        }
    }
</script>