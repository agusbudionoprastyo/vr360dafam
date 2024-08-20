<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
$s3_params = check_s3_tour_enabled($id_virtualtour_sel);
$s3_enabled = false;
$s3_url = "";
if(!empty($s3_params)) {
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
?>

<?php include("check_plan.php"); ?>

<div class="row">
    <div class="col-md-12">
        <?php if($virtual_tour['external']==0) : ?>
        <div id="toolbar_preview" style="display: none;" class="card shadow mb-0 noselect">
            <div class="card-body p-1 pb-0 text-center">
                <div class="text-center toolbar_preview_loading px-1">
                    <i class="fas fa-spin fa-circle-notch"></i>&nbsp;&nbsp;<span><?php echo _("initializing"); ?>... </span>
                </div>
                <div class="toolbar_preview_buttons">
                    <img id="preview_room_image" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" /><span class="btn btn-sm btn-light no-click" id="preview_room_name"></span>&nbsp;&nbsp;&nbsp;
                    <a id="btn_preview_edit_room" href="#" target="_blank" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> <?php echo _("edit room"); ?>
                    </a>
                    <a id="btn_preview_markers" href="#" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-caret-square-up"></i> <?php echo _("markers"); ?> <span class="badge badge-light">0</span>
                    </a>
                    <a id="btn_preview_pois" href="#" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-bullseye"></i> <?php echo _("pois"); ?> <span class="badge badge-light">0</span>
                    </a>
                    <a id="btn_preview_measures" href="#" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-ruler-combined"></i> <?php echo _("measures"); ?> <span class="badge badge-light">0</span>
                    </a>&nbsp;&nbsp;
                    <button id="btn_preview_reload" class="btn btn-sm btn-secondary" onclick="">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="card shadow mb-2">
            <div class="card-body p-0">
                <p style="display: none;padding: 15px 15px 0;" id="msg_no_room"><?php echo sprintf(_('No rooms created for this Virtual Tour. Go to %s and create a new one!'),'<a href="index.php?p=rooms">'._("Rooms").'</a>'); ?></p>
                <div style="display: none;margin-bottom:-10px;" id="iframe_div"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        $(document).ready(function () {
            if($('#toolbar_preview').length) {
                var container_h = $('#content-wrapper').height() - 195;
            } else {
                var container_h = $('#content-wrapper').height() - 155;
            }
            preview_vt(window.id_virtualtour,container_h,0);
        });
        $(window).resize(function () {
            if($('#toolbar_preview').length) {
                var container_h = $('#content-wrapper').height() - 195;
            } else {
                var container_h = $('#content-wrapper').height() - 155;
            }
            $('#iframe_div iframe').attr('height',container_h+'px');
        });
        window.addEventListener('message', function(event) {
            if(event.data.payload==='change_room') {
                $('.toolbar_preview_loading').addClass('hidden');
                $('.toolbar_preview_buttons').show();
                var id_room = event.data.id_room;
                var name_room = event.data.name_room;
                var image_room = event.data.image_room.replace('panoramas/','panoramas/thumb/');
                image_room = image_room.replace('mobile/','');
                $('#preview_room_name').html(name_room);
                $('#preview_room_image').attr('src',((window.s3_enabled) ? window.s3_url : '../')+'viewer/'+image_room);
                $('#btn_preview_edit_room').attr('href','?p=edit_room&id='+id_room);
                $('#btn_preview_markers').attr('href','?p=markers&id_room='+id_room);
                $('#btn_preview_pois').attr('href','?p=pois&id_room='+id_room);
                $('#btn_preview_measures').attr('href','?p=measurements&id_room='+id_room);
                $('#btn_preview_markers .badge').html(event.data.count_marker);
                $('#btn_preview_pois .badge').html(event.data.count_poi);
                $('#btn_preview_measures .badge').html(event.data.count_measure);
                if($('#toolbar_preview').length) {
                    var container_h = $('#content-wrapper').height() - 195;
                } else {
                    var container_h = $('#content-wrapper').height() - 155;
                }
                $('#btn_preview_reload').attr('onclick','preview_vt('+window.id_virtualtour+','+container_h+','+id_room+')');
            }
        });
    })(jQuery); // End of use strict
</script>