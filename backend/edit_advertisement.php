<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_advertisement = $_GET['id'];
$ads = get_advertisement($id_advertisement,$id_user);
?>

<?php if(!$ads): ?>
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
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> <?php echo _("Details"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label>
                            <input type="text" class="form-control" id="name" value="<?php echo $ads['name']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="link"><?php echo _("Call to Action Link"); ?></label>
                            <input type="text" class="form-control" id="link" value="<?php echo $ads['link']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="countdown"><?php echo _("Skippable after"); ?> <i id="skippable_tip_video" title="<?php echo _("enter -1 to wait for the video to end"); ?>" class="help_t fas fa-question-circle <?php echo ($ads['type']=='video') ? '' : 'd-none'; ?>"></i></label>
                            <div class="input-group">
                                <input min="-1" type="number" class="form-control" id="countdown" value="<?php echo $ads['countdown']; ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type"><?php echo _("Type"); ?></label>
                            <select onchange="change_adv_type();" class="form-control" id="type">
                                <option <?php echo ($ads['type']=='image') ? 'selected' : ''; ?> id="t_image"><?php echo _("Image"); ?></option>
                                <option <?php echo ($ads['type']=='video') ? 'selected' : ''; ?> id="t_video"><?php echo _("Mp4 Video"); ?></option>
                                <option <?php echo ($ads['type']=='iframe') ? 'selected' : ''; ?> id="t_iframe"><?php echo _("Embedded Link"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="auto_assign"><?php echo _("Automatically assigns to new virtual tours"); ?></label><br>
                            <input type="checkbox" id="auto_assign" <?php echo ($ads['auto_assign']) ? 'checked' : ''; ?> />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-images"></i> <?php echo _("Content"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 adv_type adv_type_t_iframe <?php echo ($ads['type']=='iframe') ? '' : 'd-none'; ?>">
                        <div class="form-group">
                            <label><?php echo _("Embedded Link"); ?></label>
                            <input type="text" class="form-control" id="iframe_link" value="<?php echo $ads['iframe_link']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-12 adv_type adv_type_t_image <?php echo ($ads['type']=='image') ? '' : 'd-none'; ?>">
                        <label><?php echo _("Image"); ?></label>
                        <div class="row">
                            <div style="display: none" id="div_image_logo" class="col-md-12">
                                <img style="width: 100%" src="../viewer/content/<?php echo $ads['image']; ?>" />
                            </div>
                            <div style="display: none" id="div_delete_logo" class="col-md-12 mt-3">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_ad_logo();" class="btn btn-block btn-danger"><?php echo _("DELETE IMAGE"); ?></button>
                            </div>
                        </div>
                        <div style="display: none" id="div_upload_logo">
                            <form id="frm" action="ajax/upload_announce_image.php" method="POST" enctype="multipart/form-data">
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
                    <div class="col-md-12 adv_type adv_type_t_video <?php echo ($ads['type']=='video') ? '' : 'd-none'; ?>">
                        <label><?php echo _("Mp4 Video"); ?></label>
                        <div class="row">
                            <div style="display: none" id="div_video" class="col-md-12">
                                <video style="width:100%;" controls>
                                    <source src="../viewer/content/<?php echo $ads['video']; ?>" type="video/mp4">
                                </video>
                            </div>
                            <div style="display: none" id="div_delete_video" class="col-md-12 mt-3">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_ad_video();" class="btn btn-block btn-danger"><?php echo _("DELETE VIDEO"); ?></button>
                            </div>
                        </div>
                        <div style="display: none" id="div_upload_video">
                            <form id="frm_v" action="ajax/upload_announce_video.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="txtFile_v" name="txtFile_v" />
                                                <label class="custom-file-label text-left" for="txtFile_v"><?php echo _("Choose file"); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input <?php echo ($demo) ? 'disabled':''; ?> type="submit" class="btn btn-block btn-success" id="btnUpload_v" value="<?php echo _("Upload Video"); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="preview text-center">
                                            <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                <div class="progress-bar" id="progressBar_v" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                    0%
                                                </div>
                                            </div>
                                            <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_v"></div>
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
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-crown"></i> <?php echo _("Assigned Plans"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row list_p_vt">
                    <?php echo get_advertisement_plans($id_advertisement); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary d-inline-block"><i class="fas fa-route"></i> <?php echo _("Assigned Virtual Tours"); ?></h6>
                <span class="float-right d-inline-block"><input class="form-control form-control-sm" id="search_vt" type="search" placeholder="<?php echo _("Search"); ?>" /></span>
            </div>
            <div class="card-body">
                <div class="row list_s_vt">
                    <?php echo get_advertisement_virtualtours($id_advertisement); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_advertisement" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Advertisement"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the advertisement?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_advertisement" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.advertisement_need_save = false;
        window.id_advertisement = <?php echo $id_advertisement; ?>;
        window.image_advertisement = '<?php echo $ads['image']; ?>';
        window.video_advertisement = '<?php echo $ads['video']; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            bsCustomFileInput.init();
            if(window.image_advertisement=='') {
                $('#div_delete_logo').hide();
                $('#div_image_logo').hide();
                $('#div_upload_logo').show();
            } else {
                $('#div_delete_logo').show();
                $('#div_image_logo').show();
                $('#div_upload_logo').hide();
            }
            if(window.video_advertisement=='') {
                $('#div_delete_video').hide();
                $('#div_video').hide();
                $('#div_upload_video').show();
            } else {
                $('#div_delete_video').show();
                $('#div_video').show();
                $('#div_upload_video').hide();
            }
        });
        $("input[type='text']").change(function(){
            window.advertisement_need_save = true;
        });
        $("input[type='checkbox']").change(function(){
            window.advertisement_need_save = true;
        });
        $("select").change(function(){
            window.advertisement_need_save = true;
        });
        $(window).on('beforeunload', function(){
            if(window.advertisement_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict

    window.change_adv_type = function () {
        var type = $('#type option:selected').attr('id');
        $('.adv_type').addClass('d-none');
        $('.adv_type_'+type).removeClass('d-none');
        if(type=='t_video') {
            $('#skippable_tip_video').removeClass('d-none');
        } else {
            $('#skippable_tip_video').addClass('d-none');
        }
    }

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
                    window.advertisement_need_save = true;
                    window.image_advertisement = evt.target.responseText;
                    $('#div_image_logo img').attr('src','../viewer/content/'+window.image_advertisement);
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

    $('body').on('submit','#frm_v',function(e){
        e.preventDefault();
        $('#error_v').hide();
        var url = $(this).attr('action');
        var frm = $(this);
        var data = new FormData();
        if(frm.find('#txtFile_v[type="file"]').length === 1 ){
            data.append('file', frm.find( '#txtFile_v' )[0].files[0]);
        }
        var ajax  = new XMLHttpRequest();
        ajax.upload.addEventListener('progress',function(evt){
            var percentage = (evt.loaded/evt.total)*100;
            upadte_progressbar_v(Math.round(percentage));
        },false);
        ajax.addEventListener('load',function(evt){
            if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                show_error_v(evt.target.responseText);
            } else {
                if(evt.target.responseText!='') {
                    window.advertisement_need_save = true;
                    window.video_advertisement = evt.target.responseText;
                    $('#div_video video').attr('src', '../viewer/content/'+window.video_advertisement);
                    $('#div_delete_video').show();
                    $('#div_video').show();
                    $('#div_upload_video').hide();
                }
            }
            upadte_progressbar_v(0);
            frm[0].reset();
        },false);
        ajax.addEventListener('error',function(evt){
            show_error_v('upload failed');
            upadte_progressbar_v(0);
        },false);
        ajax.addEventListener('abort',function(evt){
            show_error_v('upload aborted');
            upadte_progressbar_v(0);
        },false);
        ajax.open('POST',url);
        ajax.send(data);
        return false;
    });

    function upadte_progressbar_v(value){
        $('#progressBar_v').css('width',value+'%').html(value+'%');
        if(value==0){
            $('.progress').hide();
        }else{
            $('.progress').show();
        }
    }

    function show_error_v(error){
        $('.progress').hide();
        $('#error_v').show();
        $('#error_v').html(error);
    }
</script>