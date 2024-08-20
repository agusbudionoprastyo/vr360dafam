<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$id_video = $_GET['id'];
$video = get_video($id_video,$id_user,$id_virtualtour);
if($video!==false) {
    $virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
    $s3_params = check_s3_tour_enabled($id_virtualtour);
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
    $in_progress_video = "";
    $video_exist = false;
    $result = $mysqli->query("SELECT id,TIMESTAMPDIFF(MINUTE,date_time,NOW()) as diff_time FROM svt_job_queue WHERE id_virtualtour=$id_virtualtour AND id_project=$id_video AND type='video' LIMIT 1;");
    if($result) {
        if($result->num_rows==1) {
            $video_exist = true;
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_job = $row['id'];
            $diff_time = $row['diff_time'];
            $in_progress_video .= "<div class='background_job' data-id='$id_job'><i class='fas fa-spin fa-circle-notch' aria-hidden='true'></i><i style='display:none;' class='fas fa-check' aria-hidden='true'></i></div>";
            $in_progress_video .= "<button style='pointer-events:none' class='btn btn-block btn-sm btn-primary mt-1 btn_progress_$id_job'><i class='fas fa-hammer'></i>&nbsp;&nbsp;"._("IN PROGRESS")." ...</button>";
            $in_progress_video .= "<button style='display:none;' onclick='window.location.reload();' class='btn btn-block btn-sm btn-success mt-1 btn_reload_$id_job'><i class='fas fa-redo-alt'></i>&nbsp;&nbsp;"._("RELOAD THE PAGE")."</button>";
            if($diff_time>5) {
                $in_progress_video .= "<button onclick='abort_job_queue($id_job);' class='btn btn-block btn-sm btn-danger mt-1'><i class='fas fa-times'></i>&nbsp;&nbsp;"._("ABORT")."</button>";
            }
        } else {
            if($s3_enabled) {
                $path_video = "s3://$s3_bucket_name/video/$id_virtualtour".'_'.$id_video.'.mp4';
            } else {
                $path_video = '../video/'.$id_virtualtour.'_'.$id_video.'.mp4';
            }
            if(file_exists($path_video)) {
                $video_exist = true;
            } else {
                $video_exist = false;
            }
        }
    }
    if($s3_enabled) {
        $path_assets = "s3://$s3_bucket_name/video/assets/$id_virtualtour/";
        if(!file_exists($path_assets)) {
            mkdir($path_assets,777);
        }
    } else {
        $path = realpath(dirname(__FILE__) . '/..');
        $path_assets = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR;
        if(!file_exists($path_assets)) {
            mkdir($path_assets,0755,true);
        }
    }
    $watermark_logo = "";
    if(empty($video['watermark_logo'])) {
        if(!empty($virtual_tour['logo'])) {
            if($s3_enabled) {
                $watermark_logo = $s3_url."viewer/content/".$virtual_tour['logo'];
            } else {
                $watermark_logo = "../viewer/content/".$virtual_tour['logo'];
            }
        }
    } else {
        if($s3_enabled) {
            $watermark_logo = $s3_url."video/assets/$id_virtualtour/".$video['watermark_logo'];
        } else {
            $watermark_logo = "../video/assets/$id_virtualtour/".$video['watermark_logo'];
        }
    }
    $rooms = get_rooms($id_virtualtour);
    $settings = get_settings();
    $video_type = $settings['video_project'];
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$video): ?>
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
    <div class="col-lg-<?php echo ($video_exist) ? '8' : '12'; ?> col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> <?php echo _("Details"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-<?php echo ($video_exist) ? '8' : '3'; ?>">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label>
                            <input type="text" class="form-control" id="name" value="<?php echo $video['name']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="fade"><?php echo _("Fade"); ?> <i title="<?php echo _("fade duration between slides."); ?>" class="help_t fas fa-question-circle"></i></label>
                            <div class="input-group">
                                <input type="number" min="0" step="0.1" class="form-control" id="fade" value="<?php echo $video['fade']; ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="resolution"><?php echo _("Resolution"); ?></label>
                            <select id="resolution" class="form-control">
                                <option <?php echo ($video['resolution_h']==720) ? 'selected' : ''; ?> id="1280x720">720p</option>
                                <option <?php echo ($video['resolution_h']==1080) ? 'selected' : ''; ?> id="1920x1080">1080p</option>
                                <option <?php echo ($video['resolution_h']==1440) ? 'selected' : ''; ?> id="2560x1440">1440p</option>
                                <option <?php echo ($video['resolution_h']==2160) ? 'selected' : ''; ?> id="3840x2160">2160p</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="fps"><?php echo _("FPS"); ?> <i title="<?php echo _("frames per second"); ?>" class="help_t fas fa-question-circle"></i></label>
                            <select id="fps" class="form-control">
                                <option <?php echo ($video['fps']==24) ? 'selected' : ''; ?> id="24">24</option>
                                <option <?php echo ($video['fps']==25) ? 'selected' : ''; ?> id="25">25</option>
                                <option <?php echo ($video['fps']==29.97) ? 'selected' : ''; ?> id="29.97">29.97</option>
                                <option <?php echo ($video['fps']==30) ? 'selected' : ''; ?> id="30">30</option>
                                <option <?php echo ($video['fps']==60) ? 'selected' : ''; ?> id="60">60</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="audio"><?php echo _("Audio"); ?> <i title="<?php echo _("audio file must be uploaded into music library."); ?>" class="help_t fas fa-question-circle"></i></label>
                            <div class="input-group">
                                <select onchange="change_audio();" class="form-control" id="audio">
                                    <option <?php echo ($video['audio']=='') ? 'selected' : ''; ?> id="0"><?php echo _("No Audio"); ?></option>
                                    <?php echo get_option_exist_song($_SESSION['id_user'],$video['id_virtualtour'],$video['audio']); ?>
                                </select>
                                <div class="input-group-append">
                                    <button onclick="play_audio();" class="btn btn-primary <?php echo ($video['audio']=='') ? 'disabled' : ''; ?>" type="button"><i id="play_audio_icon" style="color: white" class="fas fa-play"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="watermark_pos"><?php echo _("Watermark"); ?></label>
                            <div class="input-group">
                                <select class="form-control" id="watermark_pos">
                                    <option <?php echo ($video['watermark_pos']=='none') ? 'selected' : ''; ?> id="none"><?php echo _("None"); ?></option>
                                    <option <?php echo ($video['watermark_pos']=='top_left') ? 'selected' : ''; ?> id="top_left"><?php echo _("Top Left"); ?></option>
                                    <option <?php echo ($video['watermark_pos']=='top_right') ? 'selected' : ''; ?> id="top_right"><?php echo _("Top Right"); ?></option>
                                    <option <?php echo ($video['watermark_pos']=='bottom_left') ? 'selected' : ''; ?> id="bottom_left"><?php echo _("Bottom Left"); ?></option>
                                    <option <?php echo ($video['watermark_pos']=='bottom_right') ? 'selected' : ''; ?> id="bottom_right"><?php echo _("Bottom Right"); ?></option>
                                    <option <?php echo ($video['watermark_pos']=='center') ? 'selected' : ''; ?> id="center"><?php echo _("Center"); ?></option>
                                </select>
                                <div class="input-group-append">
                                    <button data-toggle="modal" data-target="#modal_watermark" class="btn btn-primary" type="button"><i style="color: white" class="fas fa-cog"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo ($video_exist) ? '4' : '3'; ?>">
                        <div class="form-group">
                            <label for="watermark_opacity"><?php echo _("Watermark Opacity"); ?></label>
                            <input min="0" max="1" step="0.1" id="watermark_opacity" type="range" class="form-control-range" value="<?php echo $video['watermark_opacity']; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12" style="display:<?php echo ($video_exist) ? 'block' : 'none'; ?>" >
        <div class="card shadow mb-4">
            <div class="card-body p-2">
                <?php if(!empty($in_progress_video)) {
                    echo $in_progress_video;
                } else { ?>
                    <video id="video" style="width:100%;max-height:300px" playsinlinedo preload="auto" controls controlsList="nodownload">
                        <source src="<?php echo ($s3_enabled) ? $s3_url."video/".$id_virtualtour."_".$id_video.".mp4" : "../video/".$id_virtualtour."_".$id_video.".mp4"; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <button onclick="download_file('<?php echo ($s3_enabled) ? $s3_url."video/".$id_virtualtour."_".$id_video.".mp4" : "../video/".$id_virtualtour."_".$id_video.".mp4"; ?>');" class="btn btn-sm btn-block btn-primary <?php echo ($demo) ? 'disabled' : ''; ?>"><i class="fas fa-download"></i>&nbsp;&nbsp;<?php echo _("DOWNLOAD"); ?></button>
                    <button onclick="delete_video(<?php echo $id_video; ?>);" class="btn btn-sm btn-block btn-danger <?php echo ($demo) ? 'disabled' : ''; ?>"><i class="fas fa-trash"></i>&nbsp;&nbsp;<?php echo _("DELETE"); ?></button>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div style="line-height:38px;" class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-primary d-inline-block"><i class="fas fa-th-large"></i> <?php echo _("Timeline"); ?>&nbsp;&nbsp;&nbsp;<span id="video_estimated_duration" style="font-weight:normal;font-size:14px;color:black;"><?php echo _("Estimated duration"); ?>&nbsp;&nbsp;<span id="total_duration">--:--:--</span></span></h6>
                <button id="btn_generate_video" onclick="save_video(<?php echo $id_video ?>,true);" class="btn btn-success float-right d-inline-block disabled <?php echo ($demo) ? 'disabled_d' : ''; ?> <?php echo (!empty($in_progress_video)) ? 'disabled_d' : ''; ?>"><i class="fas fa-arrow-right"></i>&nbsp;&nbsp;<?php echo _("GENERATE"); ?></button>
            </div>
            <div class="card-body">
                <div id="list_video_slides" class="row m-0 d-block">
                    <p><?php echo _("Loading video slides ..."); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_watermark" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Watermark"); ?></h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div style="background-color:#4e73df;width:calc(100% - 24px);margin:0 auto;" id="div_image_logo" class="col-md-12 mb-3 text-center <?php echo (empty($watermark_logo)) ? 'd-none' : ''; ?>">
                        <img style="width:100%;max-width:300px" src="<?php echo $watermark_logo ?>" />
                    </div>
                    <div style="display: none" id="div_delete_logo" class="col-md-12">
                        <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_watermark_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                    </div>
                    <div style="display: none" id="div_upload_logo">
                        <form id="frm" action="ajax/upload_watermark_logo.php" method="POST" enctype="multipart/form-data">
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
                                        <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload" value="<?php echo _("Upload Image"); ?>" />
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_slide_logo" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-certificate"></i>&nbsp;&nbsp;<?php echo _("Logo + Text"); ?></h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="slide_logo_duration"><?php echo _("Duration"); ?></label>
                                    <div class="input-group">
                                        <input type="number" min="0.1" step="0.1" class="form-control" id="slide_logo_duration" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">s</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slide_logo_background_color"><?php echo _("Background Color"); ?></label>
                                    <input type="text" class="form-control" id="slide_logo_background_color" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slide_logo_font_color"><?php echo _("Font Color"); ?></label>
                                    <input type="text" class="form-control" id="slide_logo_font_color" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slide_logo_bottom_padding"><?php echo _("Bottom Padding"); ?></label>
                                    <input type="number" min="0" step="1" class="form-control" id="slide_logo_bottom_padding" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slide_logo_font_size"><?php echo _("Font Size"); ?></label>
                                    <input onchange="change_font_size_slide_logo();" type="number" min="1" step="1" class="form-control" id="slide_logo_font_size" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 px-0">
                        <div class="col-md-12">
                            <label><?php echo _("Logo"); ?></label><br>
                            <div style="background-color:#4e73df;width:100%;margin:0 auto;" id="div_image_slide_logo" class="col-md-12 mb-2 text-center">
                                <img style="width:100%;max-height:80px;object-fit:contain" src="" />
                            </div>
                            <div style="display: none" id="div_delete_slide_logo" class="col-md-12 mb-2 px-0">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_slide_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE"); ?></button>
                            </div>
                            <div id="div_upload_slide_logo" class="mb-2">
                                <form id="frm_sl" action="ajax/upload_slide_logo.php" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="txtFile_sl" name="txtFile_sl" />
                                                    <label class="custom-file-label text-left" for="txtFile_sl"><?php echo _("Choose file"); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_sl" value="<?php echo _("Upload Logo"); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="preview text-center">
                                                <div id="progress_sl" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                    <div class="progress-bar" id="progressBar_sl" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                        0%
                                                    </div>
                                                </div>
                                                <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_sl"></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="slide_logo_text"><?php echo _("Text"); ?></label>
                            <textarea rows="2" class="form-control text-center" id="slide_logo_text"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_slide_logo" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_slide_text" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-heading"></i>&nbsp;&nbsp;<?php echo _("Text"); ?></h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_text_duration"><?php echo _("Duration"); ?></label>
                            <div class="input-group">
                                <input type="number" min="0.1" step="0.1" class="form-control" id="slide_text_duration" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_text_background_color"><?php echo _("Background Color"); ?></label>
                            <input type="text" class="form-control" id="slide_text_background_color" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_text_font_color"><?php echo _("Font Color"); ?></label>
                            <input type="text" class="form-control" id="slide_text_font_color" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slide_text_font_size"><?php echo _("Font Size"); ?></label>
                            <input onchange="change_font_size_slide_text();" type="number" min="1" step="1" class="form-control" id="slide_text_font_size" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="slide_text_text"><?php echo _("Text"); ?></label>
                            <textarea rows="2" class="form-control text-center" id="slide_text_text"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_slide_text" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_slide_image" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-image"></i>&nbsp;&nbsp;<?php echo _("Image"); ?></h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="slide_image_duration"><?php echo _("Duration"); ?></label>
                            <div class="input-group">
                                <input type="number" min="0.1" step="0.1" class="form-control" id="slide_image_duration" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label><?php echo _("Image"); ?></label><br>
                        <div style="width:100%;padding-bottom:56.25%;position:relative;background-color:black;" id="div_image_slide_image" class="col-md-12 mb-2 text-center">
                            <img style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain" src="" />
                        </div>
                        <div style="display: none" id="div_delete_slide_image" class="col-md-12 mb-2 px-0">
                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_slide_image();" class="btn btn-block btn-danger"><?php echo _("DELETE"); ?></button>
                        </div>
                        <div id="div_upload_slide_image">
                            <form id="frm_si" action="ajax/upload_slide_image.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile_si" name="txtFile_si" />
                                                <label class="custom-file-label text-left" for="txtFile_si"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_si" value="<?php echo _("Upload Image"); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="preview text-center">
                                            <div id="progress_si" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                <div class="progress-bar" id="progressBar_si" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                    0%
                                                </div>
                                            </div>
                                            <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_si"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_slide_image" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_slide_video" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-video"></i>&nbsp;&nbsp;<?php echo _("Video"); ?></h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label><?php echo _("Video"); ?></label><br>
                        <div style="width:100%;padding-bottom:56.25%;position:relative;background-color:black;" id="div_image_slide_video" class="col-md-12 mb-2 text-center">
                            <video muted controls playsinline style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain" src=""></video>
                        </div>
                        <div style="display: none" id="div_delete_slide_video" class="col-md-12 mb-2 px-0">
                            <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_slide_video();" class="btn btn-block btn-danger"><?php echo _("DELETE"); ?></button>
                        </div>
                        <div id="div_upload_slide_video">
                            <form id="frm_sv" action="ajax/upload_slide_video.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile_sv" name="txtFile_sv" />
                                                <label class="custom-file-label text-left" for="txtFile_sv"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_sv" value="<?php echo _("Upload Video"); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="preview text-center">
                                            <div id="progress_sv" class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                <div class="progress-bar" id="progressBar_sv" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                    0%
                                                </div>
                                            </div>
                                            <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_sv"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_slide_video" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_slide_panorama" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-drum-steelpan"></i>&nbsp;&nbsp;<?php echo _("Panorama"); ?></h6>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="slide_panorama_duration"><?php echo _("Duration"); ?></label>
                            <div class="input-group">
                                <input type="number" min="0.1" step="0.1" class="form-control" id="slide_panorama_duration" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div id="room" class="form-group">
                            <label><?php echo _("Room"); ?></label>
                            <select data-live-search="true" onchange="change_room_panorama_pos();" id="room_panorama" class="form-control">
                                <?php
                                foreach ($rooms as $room) {
                                    if($room['type']=='image') {
                                        echo "<option data-panorama='".$room['panorama_image']."' value='".$room['id']."' id='".$room['id']."'>".$room['name']."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div style="width:100%;padding-bottom:56.25%;position:relative;background-color:black;">
                            <div style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;pointer-events:none;" id="panorama_pos_slide"></div>
                            <i onclick="play_panorama_preview();" id="play_slide_panorama_btn" class="fas fa-play-circle disabled"></i>
                            <button onclick="set_start_panorama_pos();" id="panorama_slide_start_btn" class="btn btn-sm btn-white disabled"><?php echo _("START"); ?></button>
                            <button onclick="set_end_panorama_pos();" id="panorama_slide_end_btn" class="btn btn-sm btn-white disabled"><?php echo _("END"); ?></button>
                            <div style="display:none;" id="drag_set_msg"><?php echo _("drag the view to change the position and clck set"); ?></div>
                        </div>
                        <div class="text-center">yaw <b id="slide_yaw">0</b>&nbsp;&nbsp;pitch <b id="slide_pitch">0</b>&nbsp;&nbsp;hfov <b id="slide_hfov">0</b></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_save_slide_panorama" onclick="" type="button" class="btn btn-success"><i class="fas fa-save"></i> <?php echo _("Save"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_new_slide" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="new_slide('logo');" class="btn btn-block btn-primary mb-2"><i class="fas fa-certificate"></i>&nbsp;&nbsp;<?php echo _("Logo + Text"); ?></button>
                        <button onclick="new_slide('text');" class="btn btn-block btn-primary mb-2"><i class="fas fa-heading"></i>&nbsp;&nbsp;<?php echo _("Text"); ?></button>
                        <button onclick="new_slide('panorama');" class="btn btn-block btn-primary mb-2"><i class="fas fa-drum-steelpan"></i>&nbsp;&nbsp;<?php echo _("Panorama"); ?></button>
                        <button onclick="new_slide('image');" class="btn btn-block btn-primary mb-2"><i class="fas fa-image"></i>&nbsp;&nbsp;<?php echo _("Image"); ?></button>
                        <button onclick="new_slide('video');" class="btn btn-block btn-primary mb-2"><i class="fas fa-video"></i>&nbsp;&nbsp;<?php echo _("Video"); ?></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_video_project" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Video Project"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the video project?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_video_project" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modal_generate_video" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Generate video project"); ?></h5>
            </div>
            <div class="modal-body">
                <span><i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Generation in progress, please wait ... Do not close this window!"); ?></span><br><br>
                <button onclick="continue_w_video();" id="btn_continue_w" class="btn btn-xs btn-block btn-primary d-none"><?php echo _("If you don't want to wait click here to continue working (a new tab will be opened)"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.video_need_save = false;
        window.id_virtualtour = <?php echo $id_virtualtour; ?>;
        window.id_video = <?php echo $id_video; ?>;
        window.watermark_logo = '<?php echo $video['watermark_logo']; ?>';
        window.vt_logo = '<?php echo $virtual_tour['logo']; ?>';
        window.video_type = '<?php echo $video_type; ?>';
        window.array_slides = [];
        window.slide_logo_background_color_spectrum = null;
        window.slide_logo_font_color_spectrum = null;
        window.slide_text_background_color_spectrum = null;
        window.slide_text_font_color_spectrum = null;
        window.id_video_slide_sel = 0;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            bsCustomFileInput.init();
            get_video_slides(id_virtualtour,id_video);
            if(window.watermark_logo=='') {
                $('#div_delete_logo').hide();
                if(window.vt_logo=='') {
                    $('#div_image_logo').hide();
                }
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            var count_job = 0;
            var array_jobs = [];
            $('.background_job').each(function() {
                var id = $(this).attr('data-id');
                array_jobs.push(id);
                count_job=count_job+1;
            });
            if(count_job>0) {
                setInterval(function() {
                    get_job_queue(window.id_virtualtour,window.id_video,'video',array_jobs);
                },5000);
            }
            window.slide_logo_background_color_spectrum = $('#slide_logo_background_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            $("#slide_logo_background_color").on('move.spectrum', function(e, color) {
                $('#div_image_slide_logo').css('background-color',color.toRgbString());
                $('#slide_logo_text').css('background-color',color.toRgbString());
            });
            window.slide_logo_font_color_spectrum = $('#slide_logo_font_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            $("#slide_logo_font_color").on('move.spectrum', function(e, color) {
                $('#slide_logo_text').css('color',color.toRgbString());
            });
            window.slide_text_background_color_spectrum = $('#slide_text_background_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            $("#slide_text_background_color").on('move.spectrum', function(e, color) {
                $('#slide_text_text').css('background-color',color.toRgbString());
            });
            window.slide_text_font_color_spectrum = $('#slide_text_font_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            $("#slide_text_font_color").on('move.spectrum', function(e, color) {
                $('#slide_text_text').css('color',color.toRgbString());
            });
        });
        $("#video").one("play", function() {
            this.currentTime = 0;
            $(this).attr("preload", "auto");
        });
        $("input[type='text']").change(function(){
            window.video_need_save = true;
        });
        $("input[type='checkbox']").change(function(){
            window.video_need_save = true;
        });
        $("select").change(function(){
            window.video_need_save = true;
        });
        $(window).on('beforeunload', function(){
            if(window.video_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
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
                        window.video_need_save = true;
                        window.watermark_logo = evt.target.responseText;
                        if(window.s3_enabled==1) {
                            $('#div_image_logo img').attr('src',window.s3_url+'video/assets/'+window.id_virtualtour+'/'+window.watermark_logo);
                        } else {
                            $('#div_image_logo img').attr('src','../video/assets/'+window.id_virtualtour+'/'+window.watermark_logo);
                        }
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

        $('body').on('submit','#frm_sl',function(e){
            e.preventDefault();
            $('#error_sl').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_sl[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_sl' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_sl(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_sl(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if(window.s3_enabled==1) {
                            $('#div_image_slide_logo img').attr('src',window.s3_url+'video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        } else {
                            $('#div_image_slide_logo img').attr('src','../video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        }
                        $('#div_delete_slide_logo').show();
                        $('#div_image_slide_logo').show();
                        $('#div_upload_slide_logo').hide();
                        $('#txtFile_sl').parent().removeClass("error-highlight");
                    }
                }
                upadte_progressbar_sl(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_sl('upload failed');
                upadte_progressbar_sl(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_sl('upload aborted');
                upadte_progressbar_sl(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_sl(value){
            $('#progressBar_sl').css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_sl').hide();
            }else{
                $('#progress_sl').show();
            }
        }

        function show_error_sl(error){
            $('#progress_sl').hide();
            $('#error_sl').show();
            $('#error_sl').html(error);
        }

        $('body').on('submit','#frm_si',function(e){
            e.preventDefault();
            $('#error_si').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_si[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_si' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_si(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_si(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if(window.s3_enabled==1) {
                            $('#div_image_slide_image img').attr('src',window.s3_url+'video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        } else {
                            $('#div_image_slide_image img').attr('src','../video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        }
                        $('#div_delete_slide_image').show();
                        $('#div_image_slide_image').show();
                        $('#div_upload_slide_image').hide();
                        $('#txtFile_si').parent().removeClass("error-highlight");
                    }
                }
                upadte_progressbar_si(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_si('upload failed');
                upadte_progressbar_sl(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_si('upload aborted');
                upadte_progressbar_si(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_si(value){
            $('#progressBar_si').css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_si').hide();
            }else{
                $('#progress_si').show();
            }
        }

        function show_error_si(error){
            $('#progress_si').hide();
            $('#error_si').show();
            $('#error_si').html(error);
        }

        $('body').on('submit','#frm_sv',function(e){
            e.preventDefault();
            $('#error_sv').hide();
            var url = $(this).attr('action');
            var frm = $(this);
            var data = new FormData();
            if(frm.find('#txtFile_sv[type="file"]').length === 1 ){
                data.append('file', frm.find( '#txtFile_sv' )[0].files[0]);
            }
            var ajax  = new XMLHttpRequest();
            ajax.upload.addEventListener('progress',function(evt){
                var percentage = (evt.loaded/evt.total)*100;
                upadte_progressbar_sv(Math.round(percentage));
            },false);
            ajax.addEventListener('load',function(evt){
                if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                    show_error_sv(evt.target.responseText);
                } else {
                    if(evt.target.responseText!='') {
                        if(window.s3_enabled==1) {
                            $('#div_image_slide_video video').attr('src',window.s3_url+'video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        } else {
                            $('#div_image_slide_video video').attr('src','../video/assets/'+window.id_virtualtour+'/'+evt.target.responseText);
                        }
                        $('#div_delete_slide_video').show();
                        $('#div_image_slide_video').show();
                        $('#div_upload_slide_video').hide();
                        $('#txtFile_sv').parent().removeClass("error-highlight");
                    }
                }
                upadte_progressbar_sv(0);
                frm[0].reset();
            },false);
            ajax.addEventListener('error',function(evt){
                show_error_sv('upload failed');
                upadte_progressbar_sv(0);
            },false);
            ajax.addEventListener('abort',function(evt){
                show_error_sv('upload aborted');
                upadte_progressbar_sv(0);
            },false);
            ajax.open('POST',url);
            ajax.send(data);
            return false;
        });

        function upadte_progressbar_sv(value){
            $('#progressBar_sv').css('width',value+'%').html(value+'%');
            if(value==0){
                $('#progress_sv').hide();
            }else{
                $('#progress_sv').show();
            }
        }

        function show_error_sv(error){
            $('#progress_sv').hide();
            $('#error_sv').show();
            $('#error_sv').html(error);
        }
    })(jQuery); // End of use strict

    var audio = new Audio();
    function play_audio() {
        var audio_src = $('#audio option:selected').attr('id');
        if(window.s3_enabled==1) {
            audio.src = window.s3_url+"viewer/content/"+audio_src;
        } else {
            audio.src = "../viewer/content/"+audio_src;
        }
        audio.play();
        $('#play_audio_icon').removeClass('fa-play').addClass('fa-pause');
        $('#play_audio_icon').parent().attr('onclick','pause_audio();');
    }
    function pause_audio() {
        audio.pause();
        $('#play_audio_icon').removeClass('fa-pause').addClass('fa-play');
        $('#play_audio_icon').parent().attr('onclick','play_audio();');
    }
    function change_audio() {
        try {
            audio.pause();
        } catch (e) {}
        $('#play_audio_icon').removeClass('fa-pause').addClass('fa-play');
        $('#play_audio_icon').parent().attr('onclick','play_audio();');
        var audio_src = $('#audio option:selected').attr('id');
        if(audio_src=="0") {
            $('#play_audio_icon').parent().addClass('disabled');
        } else {
            $('#play_audio_icon').parent().removeClass('disabled');
        }
    }
</script>