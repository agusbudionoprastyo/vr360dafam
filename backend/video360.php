<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$_SESSION['id_user']);
if($virtual_tour!==false) {
    $video360 = true;
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
    $can_create = get_plan_permission($id_user)['create_video360'];
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['video360']==0) {
            $video360 = false;
        }
    }
    $settings = get_settings();
    $video360_type = $settings['video360'];
} else {
    $video360 = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$video360): ?>
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
            <?php echo _("You cannot create 360 videos on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create 360 videos!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if($create_content) : ?>
<div class="row">
    <div class="col-md-12 mb-3">
        <button onclick="create_video360();" id="btn_create_video360" class="btn btn-block btn-success disabled"><i class="fas fa-plus"></i>&nbsp;&nbsp;<?php echo _("CREATE 360 VIDEO"); ?></button>
    </div>
</div>
<?php endif; ?>

<div id="div_exist_video360" class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-body py-3">
                <div class="row">
                    <?php
                    $count = 0;
                    if($s3_enabled) {
                        $path = "s3://$s3_bucket_name/video360/$id_virtualtour_sel/";
                    } else {
                        $path = dirname(__FILE__).'/../video360/'.$id_virtualtour_sel.'/';
                    }
                    if(file_exists($path)) {
                        $dir = new DirectoryIterator($path);
                        foreach ($dir as $fileinfo) {
                            if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
                                $file_name = $fileinfo->getBasename();
                                if($s3_enabled) {
                                    $file_path = $path.$file_name;
                                } else {
                                    $file_path = $fileinfo->getRealPath();
                                }
                                $file_ext = $fileinfo->getExtension();
                                if ($file_ext != 'mp4') continue;
                                $file_name_we = basename($file_path,".mp4");
                                $count++;
                                echo "<div class='col-md-3 mb-2'>";
                                echo '<video style="width:100%;height:200px" crossorigin="anonymous" preload="none" controls playsinline webkit-playsinline class="video-js vjs-default-skin vjs-big-play-centered" id="video360_'.$count.'">';
                                if($s3_enabled) {
                                    echo '<source src="'.$s3_url.'video360/'.$id_virtualtour_sel.'/'.$file_name.'" type="video/mp4">';
                                } else {
                                    echo '<source src="../video360/'.$id_virtualtour_sel.'/'.$file_name.'" type="video/mp4">';
                                }
                                echo '</video>';
                                if($s3_enabled) {
                                    echo "<button onclick=\"download_file('".$s3_url."video360/$id_virtualtour_sel/$file_name');\" class='btn btn-block btn-sm btn-primary mt-1 ".(($demo) ? 'disabled' : '')."'><i class='fas fa-download'></i>&nbsp;&nbsp;"._("DOWNLOAD")."</button>";
                                } else {
                                    echo "<button onclick=\"download_file('../video360/$id_virtualtour_sel/$file_name');\" class='btn btn-block btn-sm btn-primary mt-1 ".(($demo) ? 'disabled' : '')."'><i class='fas fa-download'></i>&nbsp;&nbsp;"._("DOWNLOAD")."</button>";
                                }
                                $file_description_yt = str_replace(".mp4",".txt",$file_path);
                                if(file_exists($file_description_yt)) {
                                    $yt_disabled = "";
                                } else {
                                    $yt_disabled = "disabled";
                                }
                                echo "<button data-toggle='modal' data-target='#modal_".$file_name_we."' class='btn btn-block btn-sm btn-outline-secondary mt-1 ".$yt_disabled."'><i class='fab fa-youtube'></i>&nbsp;&nbsp;"._("DESCRIPTION CHAPTERS")."</button>";
                                echo "<button onclick=\"delete_video360($id_virtualtour_sel,'$file_name');\" class='btn btn-block btn-sm btn-danger mt-1 ".(($demo) ? 'disabled' : '')."'><i class='fas fa-trash'></i>&nbsp;&nbsp;"._("DELETE")."</button>";
                                echo "</div>";
                                echo '<div id="modal_'.$file_name_we.'" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-body">
                                                    <textarea readonly style="background: white" rows="10" class="form-control textarea_chapters">'.file_get_contents($file_description_yt).'</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> '._("Close").'</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                            }
                        }
                    }
                    $result = $mysqli->query("SELECT id,TIMESTAMPDIFF(MINUTE,date_time,NOW()) as diff_time FROM svt_job_queue WHERE id_virtualtour=$id_virtualtour_sel AND type='360_video';");
                    if($result) {
                        if($result->num_rows>0) {
                            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                                $id_job = $row['id'];
                                $diff_time = $row['diff_time'];
                                echo "<div class='col-md-3 mb-2'>";
                                echo "<div class='background_job' data-id='$id_job'><i class='fas fa-spin fa-circle-notch' aria-hidden='true'></i><i style='display:none;' class='fas fa-check' aria-hidden='true'></i></div>";
                                echo "<button style='pointer-events:none' class='btn btn-block btn-sm btn-primary mt-1 btn_progress_$id_job'><i class='fas fa-hammer'></i>&nbsp;&nbsp;"._("IN PROGRESS")." ...</button>";
                                echo "<button style='display:none;' onclick='window.location.reload();' class='btn btn-block btn-sm btn-success mt-1 btn_reload_$id_job'><i class='fas fa-redo-alt'></i>&nbsp;&nbsp;"._("RELOAD THE PAGE")."</button>";
                                if($diff_time>5) {
                                    echo "<button onclick='abort_job_queue($id_job);' class='btn btn-block btn-sm btn-danger mt-1'><i class='fas fa-times'></i>&nbsp;&nbsp;"._("ABORT")."</button>";
                                }
                                echo "</div>";
                                $count++;
                            }
                        }
                    }
                    if($count==0) {
                        echo "<div class='col-md-12'>"._("No 360 video generated for this virtual tour.")."</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="div_create_video360" class="row" style="display:none;">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <button onclick="select_room_video360('all');" class="btn btn-sm btn-block btn-primary"><?php echo _("Select All"); ?></button>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button onclick="select_room_video360('none');" class="btn btn-sm btn-block btn-outline-secondary"><?php echo _("Select None"); ?></button>
                    </div>
                    <div class="col-md-12 text-center" style="font-size:14px;">
                        <i class="fas fa-exclamation-circle"></i>&nbsp;&nbsp;<?php echo _("click to select / deselect a room - drag to change its order - enter the duration in seconds"); ?>
                    </div>
                </div>
                <div id="list_room_video360" class="row m-0 d-block text-center">
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="video360_resolution"><?php echo _("Resolution"); ?></label>
                            <select id="video360_resolution" class="form-control form-control-sm">
                                <option id="1440x720">720p</option>
                                <option id="2160x1080">1080p</option>
                                <option id="2880x1440">1440p</option>
                                <option selected id="4320x2160">2160p</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="video360_resolution"><?php echo _("Duration Slide")." (s)"; ?></label>
                        <div class="input-group">
                            <input id="video360_slide_duration" type="number" min="1" class="form-control form-control-sm" value="10" ">
                            <div class="input-group-append">
                                <button onclick="select_duration_video360();" class="btn btn-sm btn-outline-primary" type="button"><?php echo _("Set to All"); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="video360_duration"><?php echo _("Video Duration"); ?></label><br>
                            <b id="video360_duration">00:00:00</b>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="s_audio"><?php echo _("Audio"); ?> <i title="<?php echo _("audio file must be uploaded into music library."); ?>" class="help_t fas fa-question-circle"></i></label>
                            <select onchange="change_slideshow_audio()" class="form-control form-control-sm" id="s_audio">
                                <option selected id="0"><?php echo _("No Audio"); ?></option>
                                <?php echo get_option_exist_song($_SESSION['id_user'],$id_virtualtour_sel,null); ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: none" id="div_player_s_audio" class="col-md-12">
                        <div class="form-group">
                            <audio style="width: 100%" controls>
                                <source src="" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <button id="btn_sync_audio" onclick="sync_with_audio_video360();" class="btn btn-block btn-sm btn-outline-primary disabled" type="button"><?php echo _("Sync with Audio"); ?></button>
                        </div>
                    </div>
                    <div class="col-md-12 mb-2">
                        <button id="btn_generate_video360" onclick="generate_video360()" class="btn btn-sm btn-block btn-primary disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><i class="fas fa-arrow-right"></i>&nbsp;&nbsp;<?php echo _("GENERATE"); ?></button>
                    </div>
                    <div class="col-md-12">
                        <button onclick="close_video360()" class="btn btn-sm btn-block btn-danger"><i class="fas fa-times"></i>&nbsp;&nbsp;<?php echo _("CANCEL"); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_generate_video360" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Generate 360 video"); ?></h5>
            </div>
            <div class="modal-body">
                <span><i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Generation in progress, please wait ... Do not close this window!"); ?></span><br><br>
                <button onclick="continue_w_video360();" id="btn_continue_w" class="btn btn-xs btn-block btn-primary d-none"><?php echo _("If you don't want to wait click here to continue working (a new tab will be opened)"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.video360_type = '<?php echo $video360_type; ?>';
        window.audio_duration = 0;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            get_rooms_video360(window.id_virtualtour);
            var count_job = 0;
            var array_jobs = [];
            $('.background_job').each(function() {
                var id = $(this).attr('data-id');
                array_jobs.push(id);
                count_job=count_job+1;
            });
            if(count_job>0) {
                setInterval(function() {
                    get_job_queue(window.id_virtualtour,0,'360_video',array_jobs);
                },5000);
            }
            setTimeout(function () {
                $('.video-js').each(function () {
                   $(this)[0].load();
                   var id = $(this).attr('id');
                   var video = videojs(id,{
                       autoplay: false,
                       controlBar: {
                           pictureInPictureToggle: false
                       }
                   });
                   video.vr({projection: 'equirectangular',sphereDetail: 256});
                });
            },200);
        });
    })(jQuery); // End of use strict
</script>