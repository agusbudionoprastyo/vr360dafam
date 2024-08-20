<?php
session_start();
$id_room = $_GET['id'];
$room = get_room($id_room,$_SESSION['id_user']);
$panorama_image = $room['panorama_image'];
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
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
if($s3_enabled) {
    $panorama_url = $s3_url."viewer/panoramas/$panorama_image";
} else {
    $panorama_url = "../viewer/panoramas/$panorama_image";
}
?>

<?php if(!$room): ?>
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

<script language="javascript" src="js/jquery.canvasAreaDraw.js"></script>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div id="loading">
                    <i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Loading panorama image ..."); ?>
                </div>
                <div id="div_blur" style="display: none">
                    <textarea style="display: none" class="canvas-area" disabled data-image-url="<?php echo $panorama_url; ?>"></textarea>
                    <button id="btn_reset" class="btn btn-warning btn-sm mb-1 disabled"><i class="fas fa-trash"></i> <?php echo _("CLEAR DRAW"); ?></button>
                    <button id="btn_zoom_in" class="btn btn-primary btn-sm mb-1"><i class="fas fa-search-plus"></i> <?php echo _("ZOOM IN"); ?></button>
                    <button id="btn_zoom_out" class="btn btn-primary btn-sm mb-1"><i class="fas fa-search-minus"></i> <?php echo _("ZOOM OUT"); ?></button>
                    <button onclick="apply_blur();" id="btn_apply" class="btn btn-success btn-sm float-right ml-1 mb-1 disabled"><i class="fas fa-check"></i> <?php echo _("APPLY BLUR"); ?></button>
                    <button data-toggle="modal" data-target="#modal_revert_original" id="btn_revert_original" class="btn btn-danger btn-sm float-right ml-1 mb-1 <?php echo ($room['blur']==0) ? 'disabled' : ''; ?>"><i class="fas fa-history"></i> <?php echo _("REVERT TO ORIGINAL"); ?></button>
                    <a href="index.php?p=edit_room&id=<?php echo $id_room; ?>" class="btn btn-primary btn-sm float-right mb-1"><i class="fas fa-chevron-left"></i> <?php echo _("BACK TO ROOM"); ?></a>
                    <div style="width:100%;height:80vh;overflow:scroll;">
                        <canvas id="canvas_draw"></canvas>
                    </div>
                    <div id="msg_drawing" style="position:absolute;width:auto;top:100px;left:50%;transform:translate(-50%);z-index:10;background-color:white;padding:0px 5px;border-radius:10px;text-align:center">
                        <?php echo _("Click on the image to start drawing the shape of blur"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_apply_blur" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Creating blur effect in progress, please wait ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="modal_revert_original" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("REVERT TO ORIGINAL"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to restore the original image?"); ?></p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_apply_revert_original" onclick="revert_original();" type="button" class="btn btn-danger"><i class="fas fa-reply"></i> <?php echo _("Yes, Revert"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_room = <?php echo $id_room; ?>;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        var panorama_load = new Image();
        if(window.s3_enabled==1) {
            var panorama_url = window.s3_url+'viewer/panoramas/<?php echo $panorama_image; ?>';
        } else {
            var panorama_url = '../viewer/panoramas/<?php echo $panorama_image; ?>';
        }
        $(panorama_load).on('load',function () {
            $('.canvas-area[data-image-url]').canvasAreaDraw();
            $('#loading').hide();
            $('#div_blur').show();
            if (sessionStorage.hasOwnProperty("top_scroll")) {
                setTimeout(function () {
                    var top_scroll = sessionStorage.getItem("top_scroll");
                    var left_scroll = sessionStorage.getItem("left_scroll");
                    var transform = sessionStorage.getItem("transform");
                    $('#canvas_draw').parent().scrollTop(top_scroll);
                    $('#canvas_draw').parent().scrollLeft(left_scroll);
                    $('#canvas_draw').css('transform',transform);
                    sessionStorage.clear();
                },100);
            } else {
                setTimeout(function () {
                    var w = $('#canvas_draw').width()-($('#div_blur').width());
                    var h = $('#canvas_draw').height()-($('#div_blur').height());
                    $('#canvas_draw').parent().scrollTop(h/2);
                    $('#canvas_draw').parent().scrollLeft(w/2);
                },100);
            }
        }).attr("src",panorama_url);
    })(jQuery); // End of use strict

    function apply_blur() {
        var top_scroll = $('#canvas_draw').parent().scrollTop();
        var left_scroll = $('#canvas_draw').parent().scrollLeft();
        var transform = $('#canvas_draw').css('transform');
        sessionStorage.setItem("top_scroll", top_scroll);
        sessionStorage.setItem("left_scroll", left_scroll);
        sessionStorage.setItem("transform", transform);
        $('#btn_apply').addClass('disabled');
        $('#modal_apply_blur').modal("show");
        var points = $('.canvas-area').val();
        $.ajax({
            url: "ajax/apply_blur.php",
            type: "POST",
            data: {
                points: points,
                panorama_image: '<?php echo $panorama_image; ?>'
            },
            async: true,
            success: function (json) {
                $('#btn_apply').removeClass('disabled');
                $('#modal_apply_blur').modal("hide");
                location.reload();
            }
        });
    }

    function revert_original() {
        var top_scroll = $('#canvas_draw').parent().scrollTop();
        var left_scroll = $('#canvas_draw').parent().scrollLeft();
        var transform = $('#canvas_draw').css('transform');
        sessionStorage.setItem("top_scroll", top_scroll);
        sessionStorage.setItem("left_scroll", left_scroll);
        sessionStorage.setItem("transform", transform);
        $('#modal_revert_original button').addClass('disabled');
        $('#btn_apply_revert_original').html('<i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i>');
        $.ajax({
            url: "ajax/revert_original.php",
            type: "POST",
            data: {
                id_room: id_room,
                panorama_image: '<?php echo $panorama_image; ?>'
            },
            async: true,
            success: function (json) {
                $('#modal_revert_original button').removeClass('disabled');
                $('#modal_revert_original').modal("hide");
                location.reload();
            }
        });
    }
</script>