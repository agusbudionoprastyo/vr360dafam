<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$virtualtour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtualtour!==false) {
    $json_dollhouse = $virtualtour['dollhouse'];
    $can_create = get_plan_permission($id_user)['enable_dollhouse'];
    $dollhouse = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($_SESSION['id_user'],$id_virtualtour_sel);
        if($editor_permissions['edit_3d_view']==0) {
            $dollhouse=false;
        }
    }
    $show_in_ui = $virtualtour['show_dollhouse'];
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
    $rooms = json_encode(get_rooms_3d_view($id_virtualtour_sel,$s3_enabled,$s3_bucket_name));
    $array_rooms = array();
    foreach (json_decode($rooms,true) as $room) {
        array_push($array_rooms,$room['id']);
    }
    if(!empty($json_dollhouse)) {
        $dollhouse_array = json_decode($json_dollhouse, true);
        $rooms_to_delete = array();
        foreach ($dollhouse_array['rooms'] as $key => $room) {
            $id_room = $room['id'];
            if(!in_array($id_room,$array_rooms)) {
                array_push($rooms_to_delete,$key);
            }
        }
        foreach ($rooms_to_delete as $room_to_delete) {
            if (isset($dollhouse_array['rooms'][$room_to_delete])) {
                unset($dollhouse_array['rooms'][$room_to_delete]);
            }
        }
        $dollhouse_array['rooms'] = array_values($dollhouse_array['rooms']);
        $json_dollhouse = json_encode($dollhouse_array);
    }
} else {
    $dollhouse = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$dollhouse): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php if($virtualtour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot edit 3D View on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create 3D Views!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <div class="col-md-12 editor_3d_container">
        <div class="card shadow mb-2">
            <div id="editor_3d_div" style="background: white;" class="card-body p-0 position-relative">
                <div class="modal_fs_container">
                    <div style="display:none;" class="modal-backdrop show"></div>
                    <div id="modal_add_room_dollhouse" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo _("Add Room"); ?></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label><?php echo _("Select Room"); ?></label>
                                                <select onchange="change_preview_room_image(null);" data-live-search="true" id="room_select" class="form-control"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-12 text-center">
                                            <img style="display: none" class="preview_room_target" src="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button onclick="add_room_dollhouse();" type="button" class="btn btn-success <?php echo ($demo) ? 'disabled':''; ?>"><i class="fas fa-plus"></i> <?php echo _("Add"); ?></button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modal_add_room_custom" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo _("Add Room"); ?></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php echo _("You need to add the room pointer positions directly inside the GLB file. To do this you can use Blender, add an <b>empty arrow object</b> and set its rotation values to <b>X</b>:0, <b>Y</b>:90, <b>Z</b>: the direction from where the photo was taken with yaw set to 0 (the center of the panorama).<br>Within the <b>custom properties</b> of this object create one of type string called <b>id_room</b> and set the id of the room in it. 
<b>Export</b> the GLB file selecting in the settings to include the custom properties."); ?>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <b><?php echo _("Room id List"); ?></b> (<i><?php echo _("click to copy the id"); ?></i>)<br>
                                            <?php foreach (json_decode($rooms,true) as $room) {
                                                $id_room = $room['id'];
                                                $name_room = $room['name'];
                                                if($s3_enabled) {
                                                    $image_room = $s3_url."viewer/panoramas/".$room['panorama_3d'];
                                                } else {
                                                    $image_room = "../viewer/panoramas/".$room['panorama_3d'];
                                                }
                                                $html_image = htmlspecialchars("<img style='width:300px;height:150px;' src='$image_room'>");
                                                echo "<span title='$html_image' style='cursor:pointer;' data-clipboard-text='$id_room' data-id_room='$id_room' class='btn_pointer_room_id badge badge-secondary'>$id_room - $name_room</span>&nbsp;&nbsp;&nbsp;&nbsp;";
                                            } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modal_delete_custom_glb" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo _("Delete Custom 3D Model"); ?></h5>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo _("Are you sure you want to delete the custom 3D model?"); ?>
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button <?php echo ($demo) ? 'disabled':''; ?> onclick="delete_glb_dollhouse();" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modal_upload_custom_glb" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo _("Custom 3D Model"); ?></h5>
                                </div>
                                <div class="modal-body">
                                    <form id="frm_g_edit" action="ajax/upload_glb_dollhouse.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?php echo "GLB "._("File"); ?></label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="txtFile_g_edit" name="txtFile_g_edit" />
                                                            <label class="custom-file-label text-left" for="txtFile_g_edit"><?php echo _("Choose file"); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <input <?php echo ($demo || $disabled_upload) ? 'disabled':''; ?> type="submit" class="btn btn-sm btn-block btn-success" id="btnUpload_g_edit" value="<?php echo _("Upload File"); ?>" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="preview text-center">
                                                    <div class="progress mb-3 mb-sm-3 mb-lg-0 mb-xl-0" style="height: 2.35rem;display: none">
                                                        <div class="progress-bar" id="progressBar_g_edit" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
                                                            0%
                                                        </div>
                                                    </div>
                                                    <div style="display: none;padding: .38rem;" class="alert alert-danger" id="error_g_edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="editor_toolbar" class="row p-1">
                    <div class="col-md-12">
                        <?php if(empty($virtualtour['dollhouse_glb'])) { ?>
                        <?php if($create_content) : ?><button id="btn_add_room" data-toggle="modal" data-target="#modal_add_room_dollhouse" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus-square"></i> <?php echo _("Add Room"); ?></button><?php endif; ?>
                        <button id="btn_remove_room" onclick="remove_room_dollhouse();" class="btn btn-sm btn-outline-secondary disabled"><i class="fas fa-minus-square"></i> <?php echo _("Remove Room"); ?></button>
                        <button onclick="show_levels_gui();" class="btn btn-sm btn-outline-secondary"><i class="fas fa-layer-group"></i> <?php echo _("Levels"); ?></button>
                        <?php } else { ?>
                        <button data-toggle="modal" data-target="#modal_add_room_custom" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus-square"></i> <?php echo _("Add Room"); ?></button>
                        <?php }?>
                        <button onclick="show_settings_gui();" class="btn btn-sm btn-outline-secondary"><i class="fas fa-cog"></i> <?php echo _("Settings"); ?></button>
                        <?php if(empty($virtualtour['dollhouse_glb'])) { ?>
                            <button id="btn_download_glb" onclick="exportGLB();" class="btn btn-sm btn-outline-secondary disabled <?php echo ($demo) ? 'disabled_d':''; ?>"><i class="fas fa-download"></i> <?php echo _("Download 3D Model"); ?></button>
                            <button id="btn_custom_glb" data-toggle="modal" data-target="#modal_upload_custom_glb" class="btn btn-sm btn-outline-secondary"><i class="fas fa-cube"></i> <?php echo _("Use Custom 3D Model"); ?></button>
                        <?php } else { ?>
                            <button id="btn_custom_glb" data-toggle="modal" data-target="#modal_delete_custom_glb" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i> <?php echo _("Remove Custom 3D Model"); ?></button>
                        <?php } ?>
                        <a id="save_btn" href="#" onclick="save_dollhouse();" class="btn btn-sm btn-success btn-icon-split float-right <?php echo ($demo) ? 'disabled':''; ?>">
                            <span class="icon text-white-50">
                              <i class="far fa-circle"></i>
                            </span>
                            <span class="text"><?php echo _("SAVE"); ?></span>
                        </a>
                        <button onclick="toggle_fullscreen_div('editor_3d_div');" title="<?php echo _("TOGGLE FULLSCREEN"); ?>" id="btn_toggle_fullscreen" style="margin-right:5px" class="btn btn-sm btn-primary float-right"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
                <div class="row p-0 m-0">
                    <div style="min-height: 80px" class="col-md-12 p-0 m-0">
                        <div id="loading_custom_glb">
                            <i class="fas fa-circle-notch fa-spin"></i>&nbsp;&nbsp;<?php echo _("Loading Custom 3D Model ..."); ?>
                        </div>
                        <div id="gui_dollhouse"></div>
                        <div id="container_dollhouse"></div>
                        <?php if(empty($virtualtour['dollhouse_glb'])) : ?>
                        <select style="display: block" onchange="select_level_dollhouse();" class="select_level_dollhouse">
                            <option selected id="all"><?php echo _("All"); ?></option>
                            <option id="0"><?php echo _("Level"); ?> 0</option>
                            <option id="1"><?php echo _("Level"); ?> 1</option>
                            <option id="2"><?php echo _("Level"); ?> 2</option>
                            <option id="3"><?php echo _("Level"); ?> 3</option>
                            <option id="4"><?php echo _("Level"); ?> 4</option>
                            <option id="5"><?php echo _("Level"); ?> 5</option>
                        </select>
                        <?php endif; ?>
                        <div class="info_dollhouse">
                            <b><?php echo _("Orbit"); ?></b> - <?php echo _("Left mouse"); ?><br><b><?php echo _("Zoom"); ?></b> - <?php echo _("Middle mouse or mousewheel"); ?><br><b><?php echo _("Pan"); ?></b> - <?php echo _("Right mouse or left mouse + ctrl/meta/shiftKey"); ?><br><b><?php echo _("Select room"); ?></b> - <?php echo _("Double click"); ?>
                        </div>
                        <i onclick="toggle_dollhouse_help();" class="help_dollhouse fas fa-question-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .lil-gui {
        --width: 300px;
    }
    .lil-gui.root {
        position: absolute;
        top: 0;
        right: 0;
        max-height: 100%;
    }
</style>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = <?php echo $id_virtualtour_sel; ?>;
        window.tour_name = `<?php echo $virtualtour['name']; ?>`
        window.dollhouse_need_save = false;
        window.is_fullscreen = false;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        var json_dollhouse = `<?php echo $json_dollhouse; ?>`;
        var dollhouse_glb = '<?php echo $virtualtour['dollhouse_glb']; ?>';
        var json_rooms = `<?php echo $rooms; ?>`;
        var container_dollhouse, camera_dollhouse, scene_dollhouse,  renderer_dollhouse, controls_dollhouse, transforms_dollhouse, domEvents_dollhouse, camera_pos_dollhouse, group_rooms_dollhouse = [];
        var array_rooms = [], rooms_dollhouse = [], levels_dollhouse = [], settings_dollhouse = [], textures_dollhouse = [], meshes_dollhouse = [], geometries_dollhouse=[], pointers_c_dollhouse=[], pointers_t_dollhouse=[];
        var GUI = lil.GUI, gui_dollhouse, gui_levels, gui_settings, current_index=0, gui_parameters = [], gridHelper;
        var timeout_redraw;
        var params_gui_dollhouse = {
            offsetX: 0,
            offsetY: 0,
            offsetZ: 0,
            centerX: 0,
            centerY: 0,
            centerZ: 0,
            rotation: 0,
            x: 0,
            z: 0,
            width: 0,
            height: 0,
            depth: 0,
            cube_face_top: true,
            cube_face_bottom: true,
            cube_face_left: true,
            cube_face_right: true,
            cube_face_front: true,
            cube_face_back: true,
            level: 0,
            level_0_name: 'Level 0',
            level_0_y_pos: 0,
            level_1_name: 'Level 1',
            level_1_y_pos: 270,
            level_2_name: 'Level 2',
            level_2_y_pos: 540,
            level_3_name: 'Level 3',
            level_3_y_pos: 810,
            level_4_name: 'Level 4',
            level_4_y_pos: 1080,
            level_5_name: 'Level 5',
            level_5_y_pos: 1350,
            zoom_in: 1200,
            zoom_out: 800,
            pointer_visible: true,
            pointer_x: 0,
            pointer_z: 0,
            pointer_color: '#ffffff',
            pointer_color_active: '#000000',
            background_color: '#000000',
            background_opacity: 0.85,
            autorotate_speed: 0.5,
            autorotate_inactivity: 3000,
            measures: 'm',
            level_measures: 'm',
            camera_position: function() { get_camera_position(); },
            change_transform_mode: function() { set_transform_mode(); },
        };

        $(document).ready(function () {
            bsCustomFileInput.init();
            var clipboard = new ClipboardJS('.btn_pointer_room_id');
            clipboard.on('success', function(e) {
                $(e.trigger).addClass('badge-primary');
                setTimeout(function() {
                    $(e.trigger).removeClass('badge-primary');
                },400);
            });
            $('.btn_pointer_room_id').tooltipster({
                theme: 'tooltipster-white',
                delay: 0,
                hideOnClick: true,
                contentAsHTML: true,
                trackerInterval: 100,
                trackOrigin: true,
                trackTooltip: true
            });
            $('.help_t').tooltip();
            $('#btn_toggle_fullscreen').tooltipster({
                delay: 10,
                hideOnClick: true,
                position: 'left'
            });
            var container_h = $('#content-wrapper').height() - 190;
            $('#container_dollhouse').css('height',container_h+'px');
            if(dollhouse_glb!='') {
                initialize_dollhouse_glb();
            } else {
                initialize_dollhouse();
            }
        });

        $(window).resize(function () {
            if(window.is_fullscreen) {
                var b_h = $('#editor_toolbar').height();
                b_h = screen.height - b_h;
                var b_w = screen.width;
                $('#container_dollhouse').css('height',b_h+'px');
            } else {
                var container_h = $('#content-wrapper').height() - 190;
                $('#container_dollhouse').css('height',container_h+'px');
            }
        });

        window.change_preview_room_image = function (id_room_sel) {
            if(id_room_sel==null) {
                id_room_sel = $('#room_select option:selected').attr('id');
            }
            jQuery.each(array_rooms, function(index, room) {
                var id_room = room.id;
                if(id_room==id_room_sel) {
                    var room_image = room.panorama_image;
                    if(window.s3_enabled==1) {
                        $('.preview_room_target').attr('src',window.s3_url+'viewer/panoramas/thumb/'+room_image);
                    } else {
                        $('.preview_room_target').attr('src','../viewer/panoramas/thumb/'+room_image);
                    }
                    $('.preview_room_target').show();
                    return;
                }
            });
        }

        function set_transform_mode() {
            var mode = transforms_dollhouse.getMode();
            switch(mode) {
                case 'translate':
                    gui_parameters['change_transform_mode'].updateDisplay();
                    gui_parameters['change_transform_mode'].name(`<?php echo _("Scale"); ?>`);
                    transforms_dollhouse.setMode('scale');
                    break;
                case 'scale':
                    gui_parameters['change_transform_mode'].updateDisplay();
                    gui_parameters['change_transform_mode'].name(`<?php echo _("Move"); ?>`);
                    transforms_dollhouse.setMode('translate');
                    break;
            }
        }

        function populate_room_select() {
            try {
                $('#room_select').selectpicker('destroy');
            } catch (e) {}
            var select_room_options = '';
            for(var i=0;i<array_rooms.length;i++) {
                var exist = false;
                for(var k=0;k<rooms_dollhouse.length;k++) {
                    if(rooms_dollhouse[k].id==array_rooms[i].id) {
                        if(rooms_dollhouse[k].removed==0) exist = true;
                    }
                }
                if(!exist) select_room_options += '<option id="'+array_rooms[i].id+'">'+array_rooms[i].name+'</option>';
            }
            if(select_room_options=='') {
                $('#btn_add_room').addClass('disabled');
            }
            $('#room_select').html(select_room_options).promise().done(function () {
                $('#room_select').selectpicker('refresh');
                change_preview_room_image(null);
            });
        }

        function initialize_dollhouse() {
            array_rooms = JSON.parse(json_rooms);
            for(var k=0;k<array_rooms.length;k++) {
                array_rooms[k].id = parseInt(array_rooms[k].id);
            }
            var rooms_to_remove = [];
            if(json_dollhouse!='') {
                var array_dollhouse = JSON.parse(json_dollhouse);
                camera_pos_dollhouse = array_dollhouse.camera;
                rooms_dollhouse = array_dollhouse.rooms;
                levels_dollhouse = array_dollhouse.levels;
                settings_dollhouse = array_dollhouse.settings;
                for(var k=0;k<rooms_dollhouse.length;k++) {
                    rooms_dollhouse[k].id = parseInt(rooms_dollhouse[k].id);
                    var rooms_exist = false;
                    for(var l=0;l<array_rooms.length;l++) {
                        if(array_rooms[l].id==rooms_dollhouse[k].id) {
                            rooms_exist = true;
                        }
                    }
                    if(!rooms_exist) {
                        rooms_to_remove.push(k);
                    } else {
                        rooms_dollhouse[k].removed=0;
                        if(rooms_dollhouse[k].pointer_visible === undefined) {
                            rooms_dollhouse[k].pointer_visible=true;
                        }
                        if(rooms_dollhouse[k].cube_face_top === undefined) {
                            rooms_dollhouse[k].cube_face_top=true;
                        }
                        if(rooms_dollhouse[k].cube_face_bottom === undefined) {
                            rooms_dollhouse[k].cube_face_bottom=true;
                        }
                        if(rooms_dollhouse[k].cube_face_left === undefined) {
                            rooms_dollhouse[k].cube_face_left=true;
                        }
                        if(rooms_dollhouse[k].cube_face_right === undefined) {
                            rooms_dollhouse[k].cube_face_right=true;
                        }
                        if(rooms_dollhouse[k].cube_face_front === undefined) {
                            rooms_dollhouse[k].cube_face_front=true;
                        }
                        if(rooms_dollhouse[k].cube_face_back === undefined) {
                            rooms_dollhouse[k].cube_face_back=true;
                        }
                    }
                }
                for(var m=0;m<rooms_to_remove.length;m++) {
                    rooms_dollhouse.splice(rooms_to_remove[m],1);
                }
            }
            for(var i=0; i<=5; i++) {
                if(levels_dollhouse[i]===undefined) {
                    levels_dollhouse[i] = {};
                    levels_dollhouse[i].id = i;
                    levels_dollhouse[i].name = params_gui_dollhouse['level_'+i+'_name'];
                    levels_dollhouse[i].y_pos = params_gui_dollhouse['level_'+i+'_y_pos'];
                }
            }
            if(settings_dollhouse.length==0 || settings_dollhouse===undefined) {
                settings_dollhouse={};
                settings_dollhouse.zoom_in = 1200;
                settings_dollhouse.zoom_out = 800;
                settings_dollhouse.pointer_color = 'ffffff';
                settings_dollhouse.pointer_color_active = '000000';
                settings_dollhouse.background_color = '000000';
                settings_dollhouse.background_opacity = 0.85;
                settings_dollhouse.autorotate_speed = 0.5;
                settings_dollhouse.autorotate_inactivity = 3000;
            }
            if(settings_dollhouse.autorotate_speed === undefined) {
                settings_dollhouse.autorotate_speed = 0.5;
                settings_dollhouse.autorotate_inactivity = 3000;
            }
            if(settings_dollhouse.measures === undefined) {
                settings_dollhouse.measures = 'm';
            }
            if(settings_dollhouse.level_measures === undefined) {
                settings_dollhouse.level_measures = 'm';
            }
            populate_room_select();
            container_dollhouse = document.getElementById('container_dollhouse');
            camera_dollhouse = new THREE.PerspectiveCamera( 75, container_dollhouse.offsetWidth / container_dollhouse.offsetHeight, 5, 100000 );
            camera_dollhouse.position.z = 500;
            camera_dollhouse.position.y = 1200;
            scene_dollhouse = new THREE.Scene();
            renderer_dollhouse = new THREE.WebGLRenderer({
                alpha: true,
                antialias: true
            });
            var background_color = Number("0x"+settings_dollhouse.background_color);
            var background_opacity = settings_dollhouse.background_opacity;
            renderer_dollhouse.setClearColor(new THREE.Color(background_color));
            renderer_dollhouse.setClearAlpha(background_opacity);
            renderer_dollhouse.setPixelRatio(1);
            renderer_dollhouse.setSize( container_dollhouse.offsetWidth, container_dollhouse.offsetHeight );
            container_dollhouse.appendChild( renderer_dollhouse.domElement );
            controls_dollhouse = new THREE.OrbitControls(camera_dollhouse, renderer_dollhouse.domElement);
            controls_dollhouse.enableDamping = true;
            controls_dollhouse.dampingFactor = 0.1;
            controls_dollhouse.minDistance = 100;
            controls_dollhouse.maxDistance = 5000;
            transforms_dollhouse = new THREE.TransformControls( camera_dollhouse, renderer_dollhouse.domElement );
            transforms_dollhouse.showY = false;
            transforms_dollhouse.addEventListener( 'dragging-changed', function ( event ) {
                controls_dollhouse.enabled = ! event.value;
                change_transform_position();
            });
            transforms_dollhouse.addEventListener( 'change', function ( event ) {
                change_transform_position();
            });
            transforms_dollhouse.addEventListener( 'mouseDown', function ( event ) {
                clearTimeout(timeout_redraw);
            });
            transforms_dollhouse.addEventListener( 'mouseUp', function ( event ) {
                var mode = transforms_dollhouse.getMode();
                if(mode == 'scale') {
                    redraw_room();
                }
            });
            domEvents_dollhouse = new THREEx.DomEvents(camera_dollhouse, renderer_dollhouse.domElement);
            var gridHelper = new THREE.GridHelper( 20000, 100 );
            scene_dollhouse.add(gridHelper);
            window.addEventListener( 'resize', onWindowResize_dollhouse, false );
            if(json_dollhouse!='') {
                setTimeout(function () {
                    loading_dollhouse();
                },100);
            }
            animate_dollhouse();
            var exporter = new THREE.GLTFExporter();
            window.exportGLB = function() {
                gridHelper.visible = false;
                transforms_dollhouse.detach();
                for(var i=0; i<meshes_dollhouse.length; i++) {
                    if(rooms_dollhouse[meshes_dollhouse[i].userData.index].removed==0) {
                        meshes_dollhouse[i].material[0].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[1].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[2].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[3].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[4].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[5].color.setHex(0x7777777);
                    } else {
                        meshes_dollhouse[i].visible=false;
                    }
                }
                for(var i=0; i<pointers_c_dollhouse.length; i++) {
                    pointers_c_dollhouse[i].visible=false;
                    pointers_t_dollhouse[i].visible=false;
                }
                exporter.parse(scene_dollhouse, function (result) {
                    gridHelper.visible = true;
                    if ( result instanceof ArrayBuffer ) {
                        saveArrayBuffer( result, window.tour_name+'.glb' );
                    } else {
                        const output = JSON.stringify( result, null, 2 );
                        saveString( output, window.tour_name+'.gltf' );
                    }
                },function() {},{'binary':true});
            }

            const link = document.createElement( 'a' );
            link.style.display = 'none';
            document.body.appendChild( link );

            function save_glb( blob, filename ) {
                link.href = URL.createObjectURL( blob );
                link.download = filename;
                link.click();
            }

            function saveString( text, filename ) {
                save_glb( new Blob( [ text ], { type: 'text/plain' } ), filename );
            }

            function saveArrayBuffer( buffer, filename ) {
                save_glb( new Blob( [ buffer ], { type: 'application/octet-stream' } ), filename );
            }
        }

        function initialize_dollhouse_glb() {
            array_rooms = JSON.parse(json_rooms);
            for(var k=0;k<array_rooms.length;k++) {
                array_rooms[k].id = parseInt(array_rooms[k].id);
            }
            if(json_dollhouse!='') {
                var array_dollhouse = JSON.parse(json_dollhouse);
                camera_pos_dollhouse = array_dollhouse.camera;
                rooms_dollhouse = array_dollhouse.rooms;
                levels_dollhouse = array_dollhouse.levels;
                settings_dollhouse = array_dollhouse.settings;
            }
            if(settings_dollhouse.length==0 || settings_dollhouse===undefined) {
                settings_dollhouse={};
                settings_dollhouse.zoom_in = 1200;
                settings_dollhouse.zoom_out = 800;
                settings_dollhouse.pointer_color = 'ffffff';
                settings_dollhouse.pointer_color_active = '000000';
                settings_dollhouse.background_color = '000000';
                settings_dollhouse.background_opacity = 0.85;
                settings_dollhouse.autorotate_speed = 0.5;
                settings_dollhouse.autorotate_inactivity = 3000;
            }
            if(settings_dollhouse.autorotate_speed === undefined) {
                settings_dollhouse.autorotate_speed = 0.5;
                settings_dollhouse.autorotate_inactivity = 3000;
            }
            if(settings_dollhouse.measures === undefined) {
                settings_dollhouse.measures = 'm';
            }
            if(settings_dollhouse.level_measures === undefined) {
                settings_dollhouse.level_measures = 'm';
            }
            container_dollhouse = document.getElementById('container_dollhouse');
            camera_dollhouse = new THREE.PerspectiveCamera( 75, container_dollhouse.offsetWidth / container_dollhouse.offsetHeight, 5, 100000 );
            camera_dollhouse.position.z = 500;
            camera_dollhouse.position.y = 1200;
            scene_dollhouse = new THREE.Scene();
            renderer_dollhouse = new THREE.WebGLRenderer({
                alpha: true,
                antialias: true
            });
            transforms_dollhouse = new THREE.TransformControls( camera_dollhouse, renderer_dollhouse.domElement );
            var background_color = Number("0x"+settings_dollhouse.background_color);
            var background_opacity = settings_dollhouse.background_opacity;
            renderer_dollhouse.setClearColor(new THREE.Color(background_color));
            renderer_dollhouse.setClearAlpha(background_opacity);
            renderer_dollhouse.setPixelRatio(1);
            renderer_dollhouse.setSize( container_dollhouse.offsetWidth, container_dollhouse.offsetHeight );
            container_dollhouse.appendChild( renderer_dollhouse.domElement );
            controls_dollhouse = new THREE.OrbitControls(camera_dollhouse, renderer_dollhouse.domElement);
            controls_dollhouse.enableDamping = true;
            controls_dollhouse.dampingFactor = 0.1;
            controls_dollhouse.minDistance = 100;
            controls_dollhouse.maxDistance = 5000;
            domEvents_dollhouse = new THREEx.DomEvents(camera_dollhouse, renderer_dollhouse.domElement);
            gridHelper = new THREE.GridHelper( 20000, 100 );
            scene_dollhouse.add(gridHelper);
            window.addEventListener( 'resize', onWindowResize_dollhouse, false );
            if(dollhouse_glb!='') {
                setTimeout(function () {
                    loading_dollhouse_glb();
                },100);
            }
            animate_dollhouse();
        }

        function redraw_room() {
            clearTimeout(timeout_redraw);
            timeout_redraw = setTimeout(function () {
                var level = rooms_dollhouse[current_index].level;
                group_rooms_dollhouse[level].remove(meshes_dollhouse[current_index]);
                transforms_dollhouse.detach();
                for(var i=0; i<pointers_c_dollhouse.length; i++) {
                    if(pointers_c_dollhouse[i].userData.id==meshes_dollhouse[current_index].userData.id) {
                        removeObject(pointers_c_dollhouse[i]);
                        removeObject(pointers_t_dollhouse[i]);
                        pointers_c_dollhouse.splice(i, 1);
                        pointers_t_dollhouse.splice(i, 1);
                    }
                }
                removeObject(meshes_dollhouse[current_index]);
                domEvents_dollhouse.removeEventListener(meshes_dollhouse[current_index], 'dblclick');
                textures_dollhouse[current_index].dispose();
                draw_room_dollhouse(current_index,true,true,rooms_dollhouse[current_index].cube_width,rooms_dollhouse[current_index].cube_height,rooms_dollhouse[current_index].cube_depth);
            },1000);
        }

        window.get_camera_position = function() {
            camera_pos_dollhouse = {
                cameraPosition: camera_dollhouse.position,
                targetPosition: controls_dollhouse.target
            };
        }

        function loading_dollhouse_glb() {
            $('#loading_custom_glb').show();
            const ambientLight = new THREE.AmbientLight(0xFFFFFF);
            ambientLight.intensity = 2;
            scene_dollhouse.add( ambientLight );
            renderer_dollhouse.physicallyCorrectLights = true;
            renderer_dollhouse.gammaOutput = true;
            renderer_dollhouse.outputColorSpace = THREE.SRGBColorSpace;
            load_dollhouse_glb(((window.s3_enabled) ? window.s3_url+'viewer/' : '../viewer/')+'content/'+dollhouse_glb);
            if(camera_pos_dollhouse!='' && camera_pos_dollhouse!==undefined) {
                camera_dollhouse.position.copy(camera_pos_dollhouse.cameraPosition);
                controls_dollhouse.target.copy(camera_pos_dollhouse.targetPosition);
            }
        }

        function load_dollhouse_glb(glb_file) {
            const loader = new THREE.GLTFLoader();

            const onLoad = (gltf) => {
                const model = gltf.scene;
                model.scale.set(100, 100, 100);
                gltf.scene.children.forEach((child) => {
                    child.traverse((n) => {
                        if(n.userData.id_room!==undefined) {
                            var id_room = parseInt(n.userData.id_room);
                            $('.btn_pointer_room_id[data-id_room="'+id_room+'"]').addClass('badge-success').removeClass('badge-secondary');
                            var room_tmp = [];
                            room_tmp.id = id_room;
                            room_tmp.level = 0;
                            room_tmp.cube_width = 0;
                            room_tmp.cube_height = 0;
                            room_tmp.cube_depth = 0;
                            room_tmp.rotation = ((-n.rotation.y*(180/Math.PI))+180);
                            room_tmp.x_pos = n.position.x*100;
                            room_tmp.y_pos = n.position.y*100;
                            room_tmp.z_pos = n.position.z*100;
                            room_tmp.pointer_offset_x = 0;
                            room_tmp.pointer_offset_y = 0;
                            room_tmp.yaw = 0;
                            rooms_dollhouse.push(room_tmp);
                            create_pointer_dollhouse(id_room,0,n.position.x*100,n.position.y*100,n.position.z*100,'',0,0,0);
                        }
                    });
                });
                scene_dollhouse.add(model);
                $('#loading_custom_glb').hide();
            };

            const onProgress = progress => {};

            loader.load(
                glb_file,
                gltf => onLoad(gltf),
                onProgress
            );
        }

        function loading_dollhouse() {
            for(var i=0; i<=5; i++) {
                if( group_rooms_dollhouse[i] === undefined ) {
                    group_rooms_dollhouse[i] = new THREE.Group();
                }
            }
            for(var i=0;i<rooms_dollhouse.length;i++) {
                var panorama = '';
                for(var k=0;k<array_rooms.length;k++) {
                    if(array_rooms[k].id==rooms_dollhouse[i].id) {
                        panorama = array_rooms[k].panorama_3d;
                        rooms_dollhouse[i].panorama = panorama;
                    }
                }
                if(panorama=='') continue;
                draw_room_dollhouse(i,true,false,rooms_dollhouse[i].cube_width,rooms_dollhouse[i].cube_height,rooms_dollhouse[i].cube_depth);
            }
            for(var i=0; i<group_rooms_dollhouse.length; i++) {
                scene_dollhouse.add(group_rooms_dollhouse[i]);
            }
            if(camera_pos_dollhouse!='' && camera_pos_dollhouse!==undefined) {
                camera_dollhouse.position.copy(camera_pos_dollhouse.cameraPosition);
                controls_dollhouse.target.copy(camera_pos_dollhouse.targetPosition);
            } else {
                var center = computeGroupCenter_dollhouse(group_rooms_dollhouse);
                try {
                    controls_dollhouse.target.set(center.x, center.y, center.z);
                } catch (e) {}
            }
            $('#btn_download_glb').removeClass('disabled');
        }

        function updateUvTransform() {
            window.dollhouse_need_save = true;
            var index = current_index;
            var current_width = rooms_dollhouse[index].cube_width;
            var current_height = rooms_dollhouse[index].cube_height;
            var current_depth = rooms_dollhouse[index].cube_depth;
            rooms_dollhouse[index].cube_width = parseFloat(params_gui_dollhouse.width);
            rooms_dollhouse[index].cube_height = parseFloat(params_gui_dollhouse.height);
            rooms_dollhouse[index].cube_depth = parseFloat(params_gui_dollhouse.depth);
            rooms_dollhouse[index].x_pos = parseFloat(params_gui_dollhouse.x);
            rooms_dollhouse[index].z_pos = parseFloat(params_gui_dollhouse.z);
            rooms_dollhouse[index].center_x = parseFloat(params_gui_dollhouse.centerX);
            rooms_dollhouse[index].center_y = parseFloat(params_gui_dollhouse.centerY);
            rooms_dollhouse[index].center_z = parseFloat(params_gui_dollhouse.centerZ);
            rooms_dollhouse[index].rx_offset = parseFloat(params_gui_dollhouse.offsetX);
            rooms_dollhouse[index].ry_offset = parseFloat(params_gui_dollhouse.offsetY);
            rooms_dollhouse[index].rz_offset = parseFloat(params_gui_dollhouse.offsetZ);
            rooms_dollhouse[index].rotation = parseFloat(params_gui_dollhouse.rotation);
            rooms_dollhouse[index].pointer_visible = params_gui_dollhouse.pointer_visible;
            rooms_dollhouse[index].pointer_offset_x = parseFloat(params_gui_dollhouse.pointer_x);
            rooms_dollhouse[index].pointer_offset_z = parseFloat(params_gui_dollhouse.pointer_z);
            rooms_dollhouse[index].level = params_gui_dollhouse.level;
            rooms_dollhouse[index].cube_face_top = params_gui_dollhouse.cube_face_top;
            rooms_dollhouse[index].cube_face_bottom = params_gui_dollhouse.cube_face_bottom;
            rooms_dollhouse[index].cube_face_left = params_gui_dollhouse.cube_face_left;
            rooms_dollhouse[index].cube_face_right = params_gui_dollhouse.cube_face_right;
            rooms_dollhouse[index].cube_face_front = params_gui_dollhouse.cube_face_front;
            rooms_dollhouse[index].cube_face_back = params_gui_dollhouse.cube_face_back;
            geometries_dollhouse[index].attributes.position.needsUpdate = true;
            geometries_dollhouse[index].attributes.uv.needsUpdate = true;
            draw_room_dollhouse(index,false,false,current_width,current_height,current_depth);
            var x_pointer = parseFloat(meshes_dollhouse[current_index].position.x);
            var y_pointer = 0;
            for(var i=0; i<levels_dollhouse.length;i++) {
                if(params_gui_dollhouse.level == levels_dollhouse[i].id) {
                    y_pointer = levels_dollhouse[i].y_pos;
                }
            }
            var z_pointer = parseFloat(meshes_dollhouse[current_index].position.z);
            var pointer_offset_x = parseFloat(params_gui_dollhouse.pointer_x);
            var pointer_offset_z = parseFloat(params_gui_dollhouse.pointer_z);
            pointer_offset_x = pointer_offset_x * (rooms_dollhouse[index].cube_width/2);
            pointer_offset_z = pointer_offset_z * (rooms_dollhouse[index].cube_depth/2);
            move_pointer_dollhouse(rooms_dollhouse[current_index].id,x_pointer,y_pointer,z_pointer,pointer_offset_x,pointer_offset_z,rooms_dollhouse[index].pointer_visible);
            geometries_dollhouse[index].attributes.position.needsUpdate = false;
            geometries_dollhouse[index].attributes.uv.needsUpdate = false;
        }

        function updateLevels() {
            window.dollhouse_need_save = true;
            for(var i=0; i<levels_dollhouse.length;i++) {
                levels_dollhouse[i].name = params_gui_dollhouse['level_'+i+'_name'];
                levels_dollhouse[i].y_pos = params_gui_dollhouse['level_'+i+'_y_pos'];
            }
            for(var i=0; i<rooms_dollhouse.length;i++) {
                var current_width = rooms_dollhouse[i].cube_width;
                var current_height = rooms_dollhouse[i].cube_height;
                var current_depth = rooms_dollhouse[i].cube_depth;
                geometries_dollhouse[i].attributes.position.needsUpdate = true;
                geometries_dollhouse[i].attributes.uv.needsUpdate = true;
                draw_room_dollhouse(i,false,false,current_width,current_height,current_depth);
                geometries_dollhouse[i].attributes.position.needsUpdate = false;
                geometries_dollhouse[i].attributes.uv.needsUpdate = false;
            }
        }

        function updateSettings() {
            window.dollhouse_need_save = true;
            settings_dollhouse.zoom_in = params_gui_dollhouse.zoom_in;
            settings_dollhouse.zoom_out = params_gui_dollhouse.zoom_out;
            settings_dollhouse.pointer_color = params_gui_dollhouse.pointer_color.replace("#","");
            settings_dollhouse.pointer_color_active = params_gui_dollhouse.pointer_color_active.replace("#","");
            settings_dollhouse.background_color = params_gui_dollhouse.background_color.replace("#","");
            settings_dollhouse.background_opacity = params_gui_dollhouse.background_opacity;
            settings_dollhouse.autorotate_speed = params_gui_dollhouse.autorotate_speed;
            settings_dollhouse.autorotate_inactivity = params_gui_dollhouse.autorotate_inactivity;
            settings_dollhouse.measures = params_gui_dollhouse.measures;
            settings_dollhouse.level_measures = params_gui_dollhouse.level_measures;
            for(var i=0; i<pointers_c_dollhouse.length; i++) {
                if(dollhouse_glb!='') {
                    if(pointers_c_dollhouse[i].userData.id==array_rooms[0].id) {
                        pointers_c_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color_active);
                        pointers_t_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color_active);
                    } else {
                        pointers_c_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color);
                        pointers_t_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color);
                    }
                } else {
                    if(pointers_c_dollhouse[i].userData.id==meshes_dollhouse[0].userData.id) {
                        pointers_c_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color_active);
                        pointers_t_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color_active);
                    } else {
                        pointers_c_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color);
                        pointers_t_dollhouse[i].material.color.setHex('0x'+settings_dollhouse.pointer_color);
                    }
                }
            }
            var background_color = Number("0x"+settings_dollhouse.background_color);
            var background_opacity = settings_dollhouse.background_opacity;
            renderer_dollhouse.setClearColor(new THREE.Color(background_color));
            renderer_dollhouse.setClearAlpha(background_opacity);
        }

        function initGui(index) {
            current_index = index;
            params_gui_dollhouse.x = rooms_dollhouse[index].x_pos;
            params_gui_dollhouse.z = rooms_dollhouse[index].z_pos;
            params_gui_dollhouse.centerX = rooms_dollhouse[index].center_x;
            params_gui_dollhouse.centerY = rooms_dollhouse[index].center_y;
            params_gui_dollhouse.centerZ = rooms_dollhouse[index].center_z;
            params_gui_dollhouse.offsetX = rooms_dollhouse[index].rx_offset;
            params_gui_dollhouse.offsetY = rooms_dollhouse[index].ry_offset;
            params_gui_dollhouse.offsetZ = rooms_dollhouse[index].rz_offset;
            params_gui_dollhouse.rotation = rooms_dollhouse[index].rotation;
            params_gui_dollhouse.width = rooms_dollhouse[index].cube_width;
            params_gui_dollhouse.depth = rooms_dollhouse[index].cube_depth;
            params_gui_dollhouse.height = rooms_dollhouse[index].cube_height;
            params_gui_dollhouse.pointer_visible = rooms_dollhouse[index].pointer_visible;
            params_gui_dollhouse.pointer_x = rooms_dollhouse[index].pointer_offset_x;
            params_gui_dollhouse.pointer_z = rooms_dollhouse[index].pointer_offset_z;
            params_gui_dollhouse.level = rooms_dollhouse[index].level;
            params_gui_dollhouse.cube_face_top = rooms_dollhouse[index].cube_face_top;
            params_gui_dollhouse.cube_face_bottom = rooms_dollhouse[index].cube_face_bottom;
            params_gui_dollhouse.cube_face_left = rooms_dollhouse[index].cube_face_left;
            params_gui_dollhouse.cube_face_right = rooms_dollhouse[index].cube_face_right;
            params_gui_dollhouse.cube_face_front = rooms_dollhouse[index].cube_face_front;
            params_gui_dollhouse.cube_face_back = rooms_dollhouse[index].cube_face_back;
            var room_name = '';
            for(var k=0;k<array_rooms.length;k++) {
                if(array_rooms[k].id==rooms_dollhouse[index].id) {
                    room_name =  array_rooms[k].name;
                }
            }
            try {
                gui_dollhouse.destroy();
            } catch (e) {}
            try {
                gui_levels.destroy();
            } catch (e) {}
            try {
                gui_settings.destroy();
            } catch (e) {}
            gui_dollhouse = new GUI({title: `<?php echo _("Controls"); ?> - `+room_name, container: document.getElementById('gui_dollhouse')});
            var gui_position_folder = gui_dollhouse.addFolder( `<?php echo _("Room"); ?>` );
            var mode = transforms_dollhouse.getMode();
            switch(mode) {
                case 'translate':
                    gui_parameters['change_transform_mode'] = gui_position_folder.add( params_gui_dollhouse, 'change_transform_mode' ).name( `<?php echo _("Move"); ?>` );
                    break;
                case 'scale':
                    gui_parameters['change_transform_mode'] = gui_position_folder.add( params_gui_dollhouse, 'change_transform_mode' ).name( `<?php echo _("Scale"); ?>` );
                    break;
            }
            gui_parameters['width'] = gui_position_folder.add( params_gui_dollhouse, 'width' ).name( `<?php echo _("Width"); ?> (cm)` ).onChange( updateUvTransform ).listen();
            gui_parameters['depth'] = gui_position_folder.add( params_gui_dollhouse, 'depth' ).name( `<?php echo _("Depth"); ?> (cm)` ).onChange( updateUvTransform ).listen();
            gui_position_folder.add( params_gui_dollhouse, 'height' ).name(`<?php echo _("Height"); ?> (cm)` ).onChange( updateUvTransform ).listen();
            gui_parameters['x'] = gui_position_folder.add( params_gui_dollhouse, 'x' ).name( `<?php echo _("Position"); ?> - X` ).onChange( updateUvTransform ).listen();
            gui_parameters['z'] = gui_position_folder.add( params_gui_dollhouse, 'z' ).name( `<?php echo _("Position"); ?> - Z` ).onChange( updateUvTransform ).listen();
            var levels = {};
            for(var i=0; i<levels_dollhouse.length;i++) {
                levels[levels_dollhouse[i].name] = levels_dollhouse[i].id;
            }
            gui_parameters['levels'] = gui_position_folder.add( params_gui_dollhouse, 'level', levels ).name( `<?php echo _("Level"); ?>` ).onChange( updateUvTransform );
            var gui_texture_folder = gui_dollhouse.addFolder( `<?php echo _("Panorama"); ?>` );
            gui_texture_folder.add( params_gui_dollhouse, 'centerX', -1, 1 ).name( `<?php echo _("Center"); ?> - X` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'centerY', -1, 1 ).name( `<?php echo _("Center"); ?> - Y` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'centerZ', -1, 1 ).name( `<?php echo _("Center"); ?> - Z` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'offsetX', -1, 1 ).name( `<?php echo _("Scale"); ?> - X` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'offsetY', -1, 1 ).name( `<?php echo _("Scale"); ?> - Y` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'offsetZ', -1, 1 ).name( `<?php echo _("Scale"); ?> - Z` ).onChange( updateUvTransform );
            gui_texture_folder.add( params_gui_dollhouse, 'rotation', -1, 1 ).name( `<?php echo _("Rotation"); ?>` ).onChange( updateUvTransform );
            var gui_visibility_folder = gui_dollhouse.addFolder( `<?php echo _("Visibility"); ?>` );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_front' ).name( `<?php echo _("Front"); ?>` ).onChange( updateUvTransform );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_back' ).name( `<?php echo _("Back"); ?>` ).onChange( updateUvTransform );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_left' ).name( `<?php echo _("Left"); ?>` ).onChange( updateUvTransform );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_right' ).name( `<?php echo _("Right"); ?>` ).onChange( updateUvTransform );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_top' ).name( `<?php echo _("Top"); ?>` ).onChange( updateUvTransform );
            gui_visibility_folder.add( params_gui_dollhouse, 'cube_face_bottom' ).name( `<?php echo _("Bottom"); ?>` ).onChange( updateUvTransform );
            var gui_pointer_folder = gui_dollhouse.addFolder( `<?php echo _("Pointer"); ?>` );
            gui_pointer_folder.add( params_gui_dollhouse, 'pointer_visible' ).name( `<?php echo _("Visible"); ?>` ).onChange( updateUvTransform );
            gui_pointer_folder.add( params_gui_dollhouse, 'pointer_x', -1, 1 ).name( `<?php echo _("Offset"); ?> - X` ).onChange( updateUvTransform );
            gui_pointer_folder.add( params_gui_dollhouse, 'pointer_z', -1, 1 ).name( `<?php echo _("Offset"); ?> - Z` ).onChange( updateUvTransform );
        }

        window.show_settings_gui = function () {
            transforms_dollhouse.detach();
            $('.select_level_dollhouse option[id="all"]').prop('selected', true);
            select_level_dollhouse();
            $('.select_level_dollhouse').prop('disabled',true);
            for(var i=0; i<meshes_dollhouse.length; i++) {
                meshes_dollhouse[i].material[0].color.setHex(0xFFFFFF);
                meshes_dollhouse[i].material[1].color.setHex(0xFFFFFF);
                meshes_dollhouse[i].material[2].color.setHex(0xFFFFFF);
                meshes_dollhouse[i].material[3].color.setHex(0xFFFFFF);
                meshes_dollhouse[i].material[4].color.setHex(0xFFFFFF);
                meshes_dollhouse[i].material[5].color.setHex(0xFFFFFF);
            }
            for(var i=0; i<pointers_c_dollhouse.length; i++) {
                pointers_c_dollhouse[i].visible=true;
                pointers_t_dollhouse[i].visible=true;
            }
            initGui_settings();
        }

        function initGui_settings() {
            try {
                gui_dollhouse.destroy();
            } catch (e) {}
            try {
                gui_levels.destroy();
            } catch (e) {}
            try {
                gui_settings.destroy();
            } catch (e) {}
            gui_settings = new GUI({title: `<?php echo _("Settings"); ?>`, container: document.getElementById('gui_dollhouse')});
            if(settings_dollhouse.zoom_in!==undefined) params_gui_dollhouse.zoom_in = settings_dollhouse.zoom_in;
            if(settings_dollhouse.zoom_out!==undefined) params_gui_dollhouse.zoom_out = settings_dollhouse.zoom_out;
            if(settings_dollhouse.pointer_color!==undefined) params_gui_dollhouse.pointer_color = '#'+settings_dollhouse.pointer_color;
            if(settings_dollhouse.pointer_color_active!==undefined) params_gui_dollhouse.pointer_color_active = '#'+settings_dollhouse.pointer_color_active;
            if(settings_dollhouse.background_color!==undefined) params_gui_dollhouse.background_color = '#'+settings_dollhouse.background_color;
            if(settings_dollhouse.background_opacity!==undefined) params_gui_dollhouse.background_opacity = settings_dollhouse.background_opacity;
            if(settings_dollhouse.autorotate_speed!==undefined) params_gui_dollhouse.autorotate_speed = settings_dollhouse.autorotate_speed;
            if(settings_dollhouse.autorotate_inactivity!==undefined) params_gui_dollhouse.autorotate_inactivity = settings_dollhouse.autorotate_inactivity;
            if(settings_dollhouse.measures!==undefined) params_gui_dollhouse.measures = settings_dollhouse.measures;
            if(settings_dollhouse.level_measures!==undefined) params_gui_dollhouse.level_measures = settings_dollhouse.level_measures;
            gui_settings.add( params_gui_dollhouse, 'zoom_in' ).name( `<?php echo _("Zoom In"); ?> (ms)` ).onChange( updateSettings );
            gui_settings.add( params_gui_dollhouse, 'zoom_out' ).name( `<?php echo _("Zoom Out"); ?> (ms)` ).onChange( updateSettings );
            gui_settings.addColor( params_gui_dollhouse, 'pointer_color' ).name( `<?php echo _("Pointer Color"); ?> - <?php echo _("Main"); ?>` ).onChange( updateSettings );
            gui_settings.addColor( params_gui_dollhouse, 'pointer_color_active' ).name( `<?php echo _("Pointer Color"); ?> - <?php echo _("Active"); ?>` ).onChange( updateSettings );
            gui_settings.addColor( params_gui_dollhouse, 'background_color' ).name( `<?php echo _("Background Color"); ?>` ).onChange( updateSettings );
            gui_settings.add( params_gui_dollhouse, 'background_opacity', 0, 1 ).name( `<?php echo _("Background Opacity"); ?>` ).onChange( updateSettings );
            gui_settings.add( params_gui_dollhouse, 'autorotate_speed', 0, 2, 0.1 ).name( `<?php echo _("Autorotate Speed"); ?>` ).onChange( updateSettings );
            gui_settings.add( params_gui_dollhouse, 'autorotate_inactivity', 0, 10000, 100 ).name( `<?php echo _("Autorotate Inactivity"); ?> (s)` ).onChange( updateSettings );
            if(dollhouse_glb=='') {
                gui_settings.add( params_gui_dollhouse, 'measures', { '<?php echo _("None"); ?>': '', '<?php echo _("Meters"); ?>': 'm', '<?php echo _("Feets"); ?>': 'ft' } ).name( `<?php echo _("Room - Measures"); ?>` ).onChange( updateSettings );
                gui_settings.add( params_gui_dollhouse, 'level_measures', { '<?php echo _("None"); ?>': '', '<?php echo _("Meters"); ?>': 'm', '<?php echo _("Feets"); ?>': 'ft' } ).name( `<?php echo _("Levels - Measures"); ?>` ).onChange( updateSettings );
            }
            gui_settings.add( params_gui_dollhouse, 'camera_position' ).name( `<?php echo _("Set Default Camera Position"); ?>` );
        }

        window.show_levels_gui = function () {
            transforms_dollhouse.detach();
            for(var i=0; i<meshes_dollhouse.length; i++) {
                if(rooms_dollhouse[meshes_dollhouse[i].userData.index].removed==0) {
                    meshes_dollhouse[i].material[0].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[1].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[2].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[3].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[4].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[5].color.setHex(0x7777777);
                } else {
                    meshes_dollhouse[i].visible=false;
                }
            }
            for(var i=0; i<pointers_c_dollhouse.length; i++) {
                pointers_c_dollhouse[i].visible=false;
                pointers_t_dollhouse[i].visible=false;
            }
            initGui_levels();
        }

        function initGui_levels() {
            try {
                gui_dollhouse.destroy();
            } catch (e) {}
            try {
                gui_levels.destroy();
            } catch (e) {}
            try {
                gui_settings.destroy();
            } catch (e) {}
            gui_levels = new GUI({title: `<?php echo _("Levels"); ?>`, container: document.getElementById('gui_dollhouse')});
            for(var i=0; i<=5; i++) {
                if(levels_dollhouse[i]!==undefined) {
                    params_gui_dollhouse['level_'+i+'_name'] = levels_dollhouse[i].name;
                    params_gui_dollhouse['level_'+i+'_y_pos'] = levels_dollhouse[i].y_pos;
                }
                gui_levels.add( params_gui_dollhouse, 'level_'+i+'_name' ).name( `<?php echo _("Level"); ?> `+i+` - <?php echo _("Name"); ?>` ).onChange( updateLevels );
                gui_levels.add( params_gui_dollhouse, 'level_'+i+'_y_pos' ).name( `<?php echo _("Level"); ?> `+i+` - <?php echo _("Altitude"); ?>` ).onChange( updateLevels );
            }
        }

        function change_transform_position() {
            var mode = transforms_dollhouse.getMode();
            try {
                var x = parseFloat(meshes_dollhouse[current_index].position.x);
                var z = parseFloat(meshes_dollhouse[current_index].position.z);
                if(mode=='scale') {
                    var boundingBox = new THREE.Box3().setFromObject(meshes_dollhouse[current_index]);
                    var tmp_x = boundingBox.max.x-boundingBox.min.x;
                    var tmp_z = boundingBox.max.z-boundingBox.min.z;
                    var width = parseFloat(tmp_x).toFixed(0);
                    var depth = parseFloat(tmp_z).toFixed(0);
                    var scale_x = meshes_dollhouse[current_index].scale.x;
                    var scale_z = meshes_dollhouse[current_index].scale.z;
                } else {
                    var width = parseFloat(params_gui_dollhouse.width);
                    var depth = parseFloat(params_gui_dollhouse.depth);
                }
                var pointer_offset_x = parseFloat(params_gui_dollhouse.pointer_x);
                var pointer_offset_z = parseFloat(params_gui_dollhouse.pointer_z);
                var x_pointer = x;
                var y_pointer = 0;
                for(var i=0; i<levels_dollhouse.length;i++) {
                    if(params_gui_dollhouse.level == levels_dollhouse[i].id) {
                        y_pointer = levels_dollhouse[i].y_pos;
                    }
                }
                var z_pointer = z;
                x = (x - (width/2)).toFixed(0);
                z = (z - (depth/2)).toFixed(0);
                params_gui_dollhouse.x = x;
                params_gui_dollhouse.z = z;
                if(mode=='scale') {
                    params_gui_dollhouse.width = width;
                    rooms_dollhouse[current_index].cube_width = width;
                    params_gui_dollhouse.depth = depth;
                    rooms_dollhouse[current_index].cube_depth = depth;
                }
                gui_parameters['x'].updateDisplay();
                gui_parameters['z'].updateDisplay();
                gui_parameters['x'].setValue(x);
                gui_parameters['z'].setValue(z);
                if(mode=='scale') {
                    gui_parameters['width'].updateDisplay();
                    gui_parameters['width'].setValue(width);
                    gui_parameters['depth'].updateDisplay();
                    gui_parameters['depth'].setValue(depth);
                }
                pointer_offset_x = pointer_offset_x * (width/2);
                pointer_offset_z = pointer_offset_z * (depth/2);
                move_pointer_dollhouse(rooms_dollhouse[current_index].id,x_pointer,y_pointer,z_pointer,pointer_offset_x,pointer_offset_z,rooms_dollhouse[current_index].pointer_visible);
                $('.lil-gui input').blur();
            } catch (e) {
                console.log(e);
            }
            update_dollhouse();
        }

        window.remove_room_dollhouse = function() {
            var r = confirm(window.backend_labels.delete_sure_msg);
            if (r == true) {
                window.dollhouse_need_save = true;
                rooms_dollhouse[current_index].removed = 1;
                var level = rooms_dollhouse[current_index].level;
                group_rooms_dollhouse[level].remove(meshes_dollhouse[current_index]);
                transforms_dollhouse.detach();
                for(var i=0; i<pointers_c_dollhouse.length; i++) {
                    if(pointers_c_dollhouse[i].userData.id==meshes_dollhouse[current_index].userData.id) {
                        removeObject(pointers_c_dollhouse[i]);
                        removeObject(pointers_t_dollhouse[i]);
                        pointers_c_dollhouse.splice(i, 1);
                        pointers_t_dollhouse.splice(i, 1);
                    }
                }
                removeObject(meshes_dollhouse[current_index]);
                domEvents_dollhouse.removeEventListener(meshes_dollhouse[current_index], 'dblclick');
                textures_dollhouse[current_index].dispose();
                gui_dollhouse.destroy();
                $('#btn_remove_room').addClass('disabled');
                populate_room_select();
                $('#btn_add_room').removeClass('disabled');
            }
        }

        function removeObject(object) {
            if (!(object instanceof THREE.Object3D)) return false;
            object.geometry.dispose();
            if (object.material instanceof Array) {
                object.material.forEach(material => material.dispose());
            } else {
                object.material.dispose();
            }
            object.removeFromParent();
            return true;
        }

        window.add_room_dollhouse = function () {
            var id = $('#room_select option:selected').attr('id');
            var x_pos = get_max_position_x();
            var new_room_dollhouse = {};
            new_room_dollhouse['id'] = parseInt(id);
            new_room_dollhouse['level'] = 0;
            new_room_dollhouse['cube_width'] = 300;
            new_room_dollhouse['cube_height'] = 270;
            new_room_dollhouse['cube_depth'] = 300;
            new_room_dollhouse['rx_offset'] = 0;
            new_room_dollhouse['ry_offset'] = 0;
            new_room_dollhouse['rz_offset'] = 0;
            new_room_dollhouse['x_pos'] = x_pos;
            new_room_dollhouse['z_pos'] = 0;
            new_room_dollhouse['rotation'] = 0;
            new_room_dollhouse['center_x'] = 0;
            new_room_dollhouse['center_y'] = 0;
            new_room_dollhouse['center_z'] = 0;
            new_room_dollhouse['pointer_visible'] = true;
            new_room_dollhouse['pointer_offset_x'] = 0;
            new_room_dollhouse['pointer_offset_z'] = 0;
            new_room_dollhouse['cube_face_top'] = true;
            new_room_dollhouse['cube_face_bottom'] = true;
            new_room_dollhouse['cube_face_left'] = true;
            new_room_dollhouse['cube_face_right'] = true;
            new_room_dollhouse['cube_face_front'] = true;
            new_room_dollhouse['cube_face_back'] = true;
            new_room_dollhouse['removed'] = 0;
            var panorama = '';
            for(var k=0;k<array_rooms.length;k++) {
                if(array_rooms[k].id==new_room_dollhouse['id']) {
                    panorama = array_rooms[k].panorama_3d;
                    new_room_dollhouse['panorama'] = panorama;
                }
            }
            rooms_dollhouse.push(new_room_dollhouse);
            if( group_rooms_dollhouse[0] === undefined ) {
                group_rooms_dollhouse[0] = new THREE.Group();
                scene_dollhouse.add(group_rooms_dollhouse[0]);
            }
            var index = rooms_dollhouse.length-1;
            draw_room_dollhouse(index,true,true,new_room_dollhouse['cube_width'],new_room_dollhouse['cube_height'],new_room_dollhouse['cube_depth']);
            $('#modal_add_room_dollhouse').modal('hide');
            populate_room_select();
            var targetPosition = new THREE.Vector3(x_pos,0,0);
            controls_dollhouse.target.copy(targetPosition);
            transforms_dollhouse.setMode('translate');
            setTimeout(function() {
                gui_parameters['change_transform_mode'].name(`<?php echo _("Move"); ?>`);
                gui_parameters['change_transform_mode'].updateDisplay();
            },100);
            activate_room_dollhouse(index);
            window.dollhouse_need_save = true;
        }

        function draw_room_dollhouse(index,add,force,current_width,current_height,current_depth) {
            var id = rooms_dollhouse[index].id;
            var level = rooms_dollhouse[index].level;
            var name = rooms_dollhouse[index].name;
            var cube_width = rooms_dollhouse[index].cube_width;
            var cube_height = rooms_dollhouse[index].cube_height;
            var cube_depth = rooms_dollhouse[index].cube_depth;
            var rx_offset = rooms_dollhouse[index].rx_offset;
            var ry_offset = rooms_dollhouse[index].ry_offset;
            var rz_offset = rooms_dollhouse[index].rz_offset;
            var x_pos = rooms_dollhouse[index].x_pos;
            var y_pos = 0;
            for(var i=0; i<levels_dollhouse.length;i++) {
                if(level == levels_dollhouse[i].id) {
                    y_pos = levels_dollhouse[i].y_pos;
                }
            }
            var z_pos = rooms_dollhouse[index].z_pos;
            var rotation = parseFloat(rooms_dollhouse[index].rotation);
            var center_x = rooms_dollhouse[index].center_x;
            var center_y = rooms_dollhouse[index].center_y;
            var center_z = rooms_dollhouse[index].center_z;
            var pointer_visible = rooms_dollhouse[index].pointer_visible;
            var pointer_offset_x = rooms_dollhouse[index].pointer_offset_x;
            var pointer_offset_z = rooms_dollhouse[index].pointer_offset_z;
            var cube_face_top = rooms_dollhouse[index].cube_face_top;
            var cube_face_bottom = rooms_dollhouse[index].cube_face_bottom;
            var cube_face_left = rooms_dollhouse[index].cube_face_left;
            var cube_face_right = rooms_dollhouse[index].cube_face_right;
            var cube_face_front = rooms_dollhouse[index].cube_face_front;
            var cube_face_back = rooms_dollhouse[index].cube_face_back;

            pointer_offset_x = pointer_offset_x * (cube_width/2);
            pointer_offset_z = pointer_offset_z * (cube_depth/2);

            var panorama = rooms_dollhouse[index].panorama;

            center_x = cube_width * center_x;
            center_y = cube_height * center_y;
            center_z = cube_depth * center_z;
            rx_offset = cube_width * rx_offset;
            ry_offset = cube_height * ry_offset;
            rz_offset = cube_depth * rz_offset;

            var x_pos_s = x_pos + (cube_width/2);
            var y_pos_s = y_pos + (cube_height/2);
            var z_pos_s = z_pos + (cube_depth/2);

            if(add) {
                if(window.s3_enabled==1) {
                    var panorama_texture = window.s3_url+'viewer/panoramas/'+panorama;
                } else {
                    var panorama_texture = '../viewer/panoramas/'+panorama;
                }
                textures_dollhouse[index] = new THREE.TextureLoader().load( panorama_texture, function () {
                    create_pointer_dollhouse(id,index,x_pos_s,y_pos,z_pos_s,name,level,pointer_offset_x,pointer_offset_z);
                    if(force) {
                        activate_room_dollhouse(index);
                    }
                });
                textures_dollhouse[index].wrapS = THREE.RepeatWrapping;
                textures_dollhouse[index].magFilter = THREE.NearestFilter;
                textures_dollhouse[index].minFilter = THREE.NearestFilter;
                var MaxAnisotropy = renderer_dollhouse.capabilities.getMaxAnisotropy()/2;
                if(MaxAnisotropy<1) MaxAnisotropy=1;
                textures_dollhouse[index].anisotropy = MaxAnisotropy;
            }
            textures_dollhouse[index].offset.x = rotation;

            if(add) {
                geometries_dollhouse[index] = new THREE.BoxBufferGeometry(cube_width, cube_height, cube_depth, 600, 20, 20).toNonIndexed();
                geometries_dollhouse[index].scale(-1, 1, 1);
            } else {
                if((cube_width!=current_width) || (cube_height!=current_height) || (cube_depth!=current_depth)) {
                    var scale_x = cube_width / current_width;
                    var scale_y = cube_height / current_height;
                    var scale_z = cube_depth / current_depth;
                    geometries_dollhouse[index].scale(scale_x, scale_y, scale_z);
                }
            }

            var positions = geometries_dollhouse[index].attributes.position.array;
            var uvs = geometries_dollhouse[index].attributes.uv.array;

            var rx = (cube_width/2) + rx_offset;
            var ry = (cube_height/2) + ry_offset;
            var rz = (cube_depth/2) + rz_offset;

            for ( var i = 0, l = positions.length / 3; i < l; i ++ ) {
                var x = (positions[ i * 3 + 0 ]+center_x)/rx;
                var y = (positions[ i * 3 + 1 ]+center_y)/ry;
                var z = (positions[ i * 3 + 2 ]+center_z)/rz;
                var a = Math.sqrt(1.0 / (x * x + y * y + z * z));
                var phi, theta;
                phi = Math.asin(a * y);
                theta = Math.atan2(a * x, a * z);
                if((x==0) && (z<0)) {
                    var p = Math.floor(i / 3);
                    if ((positions[p * 3 * 3] < 0) || (positions[(p + 1) * 3 * 3] < 0) || (positions[(p + 2) * 3 * 3] < 0)) {
                        theta = -Math.PI;
                    }
                }
                var uvx = 1 - (theta+Math.PI)/Math.PI/2;
                var uvy = (phi+Math.PI/2)/Math.PI;
                uvs[i * 2] = uvx;
                uvs[i * 2 + 1] = uvy;
            }
            if(add) {
                if(cube_face_front) {
                    var material1 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material1 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                if(cube_face_back) {
                    var material2 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material2 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                if(cube_face_top) {
                    var material3 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material3 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                if(cube_face_bottom) {
                    var material4 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material4 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                if(cube_face_left) {
                    var material5 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material5 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                if(cube_face_right) {
                    var material6 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 1 } );
                } else {
                    var material6 = new THREE.MeshBasicMaterial( { color:0x7777777, map: textures_dollhouse[index], transparent: true, opacity: 0, depthWrite: false } );
                }
                meshes_dollhouse[index] = new THREE.Mesh( geometries_dollhouse[index], [material1,material2,material3,material4,material5, material6] );
                meshes_dollhouse[index].userData = { type:'room',level:level, index:index, id:id, width: cube_width, height: cube_height, depth: cube_depth, pointer_visible: pointer_visible};
            } else {
                if(cube_face_front) {
                    meshes_dollhouse[index].material[0].opacity = 1;
                    meshes_dollhouse[index].material[0].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[0].opacity = 0;
                    meshes_dollhouse[index].material[0].depthWrite = false;
                }
                if(cube_face_back) {
                    meshes_dollhouse[index].material[1].opacity = 1;
                    meshes_dollhouse[index].material[1].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[1].opacity = 0;
                    meshes_dollhouse[index].material[1].depthWrite = false;
                }
                if(cube_face_top) {
                    meshes_dollhouse[index].material[2].opacity = 1;
                    meshes_dollhouse[index].material[2].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[2].opacity = 0;
                    meshes_dollhouse[index].material[2].depthWrite = false;
                }
                if(cube_face_bottom) {
                    meshes_dollhouse[index].material[3].opacity = 1;
                    meshes_dollhouse[index].material[3].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[3].opacity = 0;
                    meshes_dollhouse[index].material[3].depthWrite = false;
                }
                if(cube_face_left) {
                    meshes_dollhouse[index].material[4].opacity = 1;
                    meshes_dollhouse[index].material[4].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[4].opacity = 0;
                    meshes_dollhouse[index].material[4].depthWrite = false;
                }
                if(cube_face_right) {
                    meshes_dollhouse[index].material[5].opacity = 1;
                    meshes_dollhouse[index].material[5].depthWrite = true;
                } else {
                    meshes_dollhouse[index].material[5].opacity = 0;
                    meshes_dollhouse[index].material[5].depthWrite = false;
                }
            }
            meshes_dollhouse[index].position.set(x_pos_s, y_pos_s, z_pos_s);
            if(add) {
                group_rooms_dollhouse[level].add(meshes_dollhouse[index]);
                domEvents_dollhouse.addEventListener(meshes_dollhouse[index], 'dblclick', function(){
                    activate_room_dollhouse(index);
                    select_level_dollhouse();
                }, false);
            } else {
                try { group_rooms_dollhouse[0].remove(meshes_dollhouse[index]); } catch (e) {}
                try { group_rooms_dollhouse[1].remove(meshes_dollhouse[index]); } catch (e) {}
                try { group_rooms_dollhouse[2].remove(meshes_dollhouse[index]); } catch (e) {}
                try { group_rooms_dollhouse[3].remove(meshes_dollhouse[index]); } catch (e) {}
                try { group_rooms_dollhouse[4].remove(meshes_dollhouse[index]); } catch (e) {}
                try { group_rooms_dollhouse[5].remove(meshes_dollhouse[index]); } catch (e) {}
                group_rooms_dollhouse[level].add(meshes_dollhouse[index]);
            }
        }

        function activate_room_dollhouse(index) {
            for(var i=0; i<meshes_dollhouse.length; i++) {
                try {
                    if(rooms_dollhouse[meshes_dollhouse[i].userData.index].removed==0) {
                        meshes_dollhouse[i].material[0].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[1].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[2].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[3].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[4].color.setHex(0x7777777);
                        meshes_dollhouse[i].material[5].color.setHex(0x7777777);
                    } else {
                        meshes_dollhouse[i].visible=false;
                    }
                } catch (e) {}
            }
            for(var i=0; i<pointers_c_dollhouse.length; i++) {
                if(pointers_c_dollhouse[i].userData.id==meshes_dollhouse[index].userData.id) {
                    if(meshes_dollhouse[index].userData.pointer_visible) {
                        pointers_c_dollhouse[i].visible=true;
                        pointers_t_dollhouse[i].visible=true;
                    }
                } else {
                    pointers_c_dollhouse[i].visible=false;
                    pointers_t_dollhouse[i].visible=false;
                }
            }
            meshes_dollhouse[index].material[0].color.setHex(0xFFFFFF);
            meshes_dollhouse[index].material[1].color.setHex(0xFFFFFF);
            meshes_dollhouse[index].material[2].color.setHex(0xFFFFFF);
            meshes_dollhouse[index].material[3].color.setHex(0xFFFFFF);
            meshes_dollhouse[index].material[4].color.setHex(0xFFFFFF);
            meshes_dollhouse[index].material[5].color.setHex(0xFFFFFF);
            transforms_dollhouse.attach(meshes_dollhouse[index]);
            scene_dollhouse.add(transforms_dollhouse);
            initGui(index);
            change_transform_position();
            $('.select_level_dollhouse').prop('disabled',false);
            $('#btn_remove_room').removeClass('disabled');
        }

        function create_pointer_dollhouse(id,index,pos_x,pos_y,pos_z,name,level,pointer_offset_x,pointer_offset_z) {
            if(id==array_rooms[0].id) {
                var pointer_color = new THREE.Color().setHex('0x'+settings_dollhouse.pointer_color_active);
            } else {
                var pointer_color = new THREE.Color().setHex('0x'+settings_dollhouse.pointer_color);

            }
            var geometry = new THREE.TorusGeometry( 20, 2, 2, 32 );
            var material = new THREE.MeshBasicMaterial( { color: pointer_color, transparent: false, opacity: 0.6 } );
            var torus = new THREE.Mesh(geometry, material);
            torus.position.set(pos_x+pointer_offset_x, pos_y+2, pos_z+pointer_offset_z);
            torus.rotation.x = Math.PI / 2;
            torus.userData = { type:'pointer',level:level, id:id};
            var geometry = new THREE.CircleGeometry( 20, 32 );
            var material = new THREE.MeshBasicMaterial( { color: pointer_color, transparent: true, opacity: 0.2, side: THREE.DoubleSide } );
            var circle = new THREE.Mesh(geometry, material);
            circle.renderOrder = 1;
            circle.position.set(pos_x+pointer_offset_x, pos_y+2, pos_z+pointer_offset_z);
            circle.rotation.x = -Math.PI / 2;
            circle.userData = { type:'pointer',level:level, id:id};
            torus.name = "pointer_t_"+id;
            circle.name = "pointer_c_"+id;
            if(dollhouse_glb!='') {
                circle.visible = true;
                torus.visible = true;
            } else {
                circle.visible = false;
                torus.visible = false;
            }
            pointers_t_dollhouse.push(torus);
            pointers_c_dollhouse.push(circle);
            if(dollhouse_glb!='') {
                scene_dollhouse.add(circle);
                scene_dollhouse.add(torus);
            } else {
                group_rooms_dollhouse[level].add(circle);
                group_rooms_dollhouse[level].add(torus);
            }
        }

        function move_pointer_dollhouse(id,pos_x,pos_y,pos_z,pointer_offset_x,pointer_offset_z,visible) {
            for(var i=0;i<pointers_c_dollhouse.length;i++) {
                if(id==pointers_c_dollhouse[i].userData.id) {
                    pointers_c_dollhouse[i].visible = visible;
                    pointers_t_dollhouse[i].visible = visible;
                    pointers_c_dollhouse[i].position.set(pos_x+pointer_offset_x, pos_y+2, pos_z+pointer_offset_z);
                    pointers_t_dollhouse[i].position.set(pos_x+pointer_offset_x, pos_y+2, pos_z+pointer_offset_z);
                }
            }
        }

        function animate_dollhouse() {
            requestAnimationFrame( animate_dollhouse );
            update_dollhouse();
        }

        function update_dollhouse() {
            controls_dollhouse.update();
            renderer_dollhouse.render( scene_dollhouse, camera_dollhouse );
        }

        function onWindowResize_dollhouse() {
            if(window.is_fullscreen) {
                var b_h = $('#editor_toolbar').height();
                b_h = screen.height - b_h;
                var b_w = screen.width;
                camera_dollhouse.aspect = b_w / b_h;
                camera_dollhouse.updateProjectionMatrix();
                renderer_dollhouse.setSize( b_w, b_h );
            } else {
                camera_dollhouse.aspect = container_dollhouse.offsetWidth / container_dollhouse.offsetHeight;
                camera_dollhouse.updateProjectionMatrix();
                renderer_dollhouse.setSize( container_dollhouse.offsetWidth, container_dollhouse.offsetHeight );
            }
        }

        var computeGroupCenter_dollhouse = (function () {
            var childBox = new THREE.Box3();
            var groupBox = new THREE.Box3();
            var invMatrixWorld = new THREE.Matrix4();
            return function (group, optionalTarget) {
                for(var i=0; i<group.length; i++) {
                    if (!optionalTarget) optionalTarget = new THREE.Vector3();
                    group[i].traverse(function (child) {
                        if (child instanceof THREE.Mesh) {
                            if (!child.geometry.boundingBox) {
                                child.geometry.computeBoundingBox();
                                childBox.copy(child.geometry.boundingBox);
                                child.updateMatrixWorld(true);
                                childBox.applyMatrix4(child.matrixWorld);
                                groupBox.min.min(childBox.min);
                                groupBox.max.max(childBox.max);
                            }
                        }
                    });
                    invMatrixWorld.copy(group[i].matrixWorld).invert();
                    groupBox.applyMatrix4(invMatrixWorld);
                    groupBox.getCenter(optionalTarget);
                }
                return optionalTarget;
            }
        })();

        function get_max_position_x() {
            var max_x = 0;
            for(var i=0; i<rooms_dollhouse.length;i++) {
                if(rooms_dollhouse[i].removed==0) {
                    var x = rooms_dollhouse[i].x_pos-rooms_dollhouse[i].cube_width;
                    if(x<max_x) {
                        max_x=x;
                    }
                }
            }
            return max_x;
        }

        window.save_dollhouse = function () {
            $('#save_btn .icon i').removeClass('far fa-circle').addClass('fas fa-circle-notch fa-spin');
            $('#save_btn').addClass("disabled");
            if(dollhouse_glb!='') {
                if(json_dollhouse!='') {
                    var array_dollhouse = JSON.parse(json_dollhouse);
                    rooms_dollhouse = array_dollhouse.rooms;
                    levels_dollhouse = array_dollhouse.levels;
                }
            } else {
                var i = rooms_dollhouse.length;
                while (i--) {
                    if(rooms_dollhouse[i].removed==1) {
                        rooms_dollhouse.splice(i,1);
                    }
                }
            }
            var dollhouse_json = JSON.stringify({
                levels: levels_dollhouse,
                rooms: rooms_dollhouse,
                camera: camera_pos_dollhouse,
                settings: settings_dollhouse
            });
            $.ajax({
                url: "ajax/save_dollhouse.php",
                type: "POST",
                data: {
                    id_virtualtour: window.id_virtualtour,
                    dollhouse_json: dollhouse_json
                },
                async: true,
                success: function (json) {
                    var rsp = JSON.parse(json);
                    if(rsp.status=="ok") {
                        window.dollhouse_need_save = false;
                        $('#save_btn .icon i').removeClass('fas fa-circle-notch fa-spin').addClass('fas fa-check');
                        setTimeout(function () {
                            $('#save_btn .icon i').removeClass('fas fa-check').addClass('far fa-circle');
                            $('#save_btn').removeClass("disabled");
                        },1000);
                    } else {
                        $('#save_btn .icon i').removeClass('fas fa-circle-notch fa-spin').addClass('fas fa-times');
                        $('#save_btn').removeClass('btn-success').addClass('btn-danger');
                        setTimeout(function () {
                            $('#save_btn .icon i').removeClass('fas fa-times').addClass('far fa-circle');
                            $('#save_btn').removeClass('btn-danger').addClass('btn-success');
                            $('#save_btn').removeClass("disabled");
                        },1000);
                    }
                }
            });
        }

        window.delete_glb_dollhouse = function() {
            $('#modal_delete_custom_glb button').addClass('disabled');
            $.ajax({
                url: "ajax/delete_glb_dollhouse.php",
                type: "POST",
                async: true,
                success: function (json) {
                    location.reload();
                },
                error: function() {
                    $('#modal_delete_custom_glb button').removeClass('disabled');
                }
            });
        }

        window.toggle_dollhouse_help = function() {
            if($('.info_dollhouse').is(':visible')) {
                $('.info_dollhouse').fadeOut();
            } else {
                $('.info_dollhouse').fadeIn();
            }
        }

        window.select_level_dollhouse = function () {
            try {
                transforms_dollhouse.detach();
            } catch (e) {}
            try {
                gui_dollhouse.destroy();
            } catch (e) {}
            var level = $('.select_level_dollhouse option:selected').attr('id');
            for(var i=0; i<meshes_dollhouse.length; i++) {
                try { domEvents_dollhouse.removeEventListener(meshes_dollhouse[i], 'dblclick'); } catch (e) {}
            }
            if(level=='all') {
                for(var i=0; i<group_rooms_dollhouse.length; i++) {
                    setOpacity_group_dollhouse(group_rooms_dollhouse,i,1);
                }
            } else {
                for(var i=0; i<group_rooms_dollhouse.length; i++) {
                    if(i==parseInt(level)) {
                        setOpacity_group_dollhouse(group_rooms_dollhouse,i,1);
                    } else {
                        setOpacity_group_dollhouse(group_rooms_dollhouse,i,0.1);
                    }
                }
            }
            for(var i=0; i<meshes_dollhouse.length; i++) {
                if(rooms_dollhouse[meshes_dollhouse[i].userData.index].removed==0) {
                    meshes_dollhouse[i].material[0].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[1].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[2].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[3].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[4].color.setHex(0x7777777);
                    meshes_dollhouse[i].material[5].color.setHex(0x7777777);
                } else {
                    meshes_dollhouse[i].visible=false;
                }
            }
            for(var i=0; i<pointers_c_dollhouse.length; i++) {
                pointers_c_dollhouse[i].visible = false;
                pointers_t_dollhouse[i].visible = false;
            }
        }

        function setOpacity_group_dollhouse(group,level,opacity) {
            group[level].children.forEach(function(child){
                if(child.userData.type=='room') {
                    if(rooms_dollhouse[child.userData.index].removed==0) {
                        if(rooms_dollhouse[child.userData.index].cube_face_front) {
                            child.material[0].opacity = opacity;
                        } else {
                            child.material[0].opacity = 0;
                        }
                        if(rooms_dollhouse[child.userData.index].cube_face_back) {
                            child.material[1].opacity = opacity;
                        } else {
                            child.material[1].opacity = 0;
                        }
                        if(rooms_dollhouse[child.userData.index].cube_face_top) {
                            child.material[2].opacity = opacity;
                        } else {
                            child.material[2].opacity = 0;
                        }
                        if(rooms_dollhouse[child.userData.index].cube_face_bottom) {
                            child.material[3].opacity = opacity;
                        } else {
                            child.material[3].opacity = 0;
                        }
                        if(rooms_dollhouse[child.userData.index].cube_face_left) {
                            child.material[4].opacity = opacity;
                        } else {
                            child.material[4].opacity = 0;
                        }
                        if(rooms_dollhouse[child.userData.index].cube_face_right) {
                            child.material[5].opacity = opacity;
                        } else {
                            child.material[5].opacity = 0;
                        }
                        if(opacity<1) {
                            child.material[0].depthWrite = false;
                            child.material[1].depthWrite = false;
                            child.material[2].depthWrite = false;
                            child.material[3].depthWrite = false;
                            child.material[4].depthWrite = false;
                            child.material[5].depthWrite = false;
                        } else {
                            try {
                                domEvents_dollhouse.addEventListener(child, 'dblclick', function(){
                                    activate_room_dollhouse(child.userData.index);
                                }, false);
                            } catch (e) {}
                            if(rooms_dollhouse[child.userData.index].cube_face_front) {
                                child.material[0].depthWrite = true;
                            } else {
                                child.material[0].depthWrite = false;
                            }
                            if(rooms_dollhouse[child.userData.index].cube_face_back) {
                                child.material[1].depthWrite = true;
                            } else {
                                child.material[1].depthWrite = false;
                            }
                            if(rooms_dollhouse[child.userData.index].cube_face_top) {
                                child.material[2].depthWrite = true;
                            } else {
                                child.material[2].depthWrite = false;
                            }
                            if(rooms_dollhouse[child.userData.index].cube_face_bottom) {
                                child.material[3].depthWrite = true;
                            } else {
                                child.material[3].depthWrite = false;
                            }
                            if(rooms_dollhouse[child.userData.index].cube_face_left) {
                                child.material[4].depthWrite = true;
                            } else {
                                child.material[4].depthWrite = false;
                            }
                            if(rooms_dollhouse[child.userData.index].cube_face_right) {
                                child.material[5].depthWrite = true;
                            } else {
                                child.material[5].depthWrite = false;
                            }
                        }
                    } else {
                        meshes_dollhouse[child.userData.index].visible=false;
                    }
                }
            });
        };

        $(window).on('beforeunload', function(){
            if(window.dollhouse_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict

    $('body').on('submit','#frm_g_edit',function(e){
        e.preventDefault();
        $('.btn_close').addClass('disabled');
        $('#error_g_edit').hide();
        var url = $(this).attr('action');
        var frm = $(this);
        var data = new FormData();
        if(frm.find('#txtFile_g_edit[type="file"]').length === 1 ){
            data.append('file', frm.find( '#txtFile_g_edit' )[0].files[0]);
        }
        var ajax  = new XMLHttpRequest();
        ajax.upload.addEventListener('progress',function(evt){
            var percentage = (evt.loaded/evt.total)*100;
            upadte_progressbar_g_edit(Math.round(percentage));
        },false);
        ajax.addEventListener('load',function(evt){
            if(evt.target.responseText.toLowerCase().indexOf('error')>=0){
                show_error_g_edit(evt.target.responseText);
            } else {
                if(evt.target.responseText!='') {
                    location.reload();
                }
            }
            $('.btn_close').removeClass('disabled');
            upadte_progressbar_g_edit(0);
            frm[0].reset();
        },false);
        ajax.addEventListener('error',function(evt){
            show_error_g_edit('upload failed');
            upadte_progressbar_g_edit(0);
        },false);
        ajax.addEventListener('abort',function(evt){
            show_error_g_edit('upload aborted');
            upadte_progressbar_g_edit(0);
        },false);
        ajax.open('POST',url);
        ajax.send(data);
        return false;
    });

    function upadte_progressbar_g_edit(value){
        $('#progressBar_g_edit').css('width',value+'%').html(value+'%');
        if(value==0){
            $('.progress').hide();
        }else{
            $('.progress').show();
        }
    }

    function show_error_g_edit(error){
        $('.btn_confirm').removeClass('disabled');
        $('.btn_close').removeClass('disabled');
        $('.progress').hide();
        $('#error_g_edit').show();
        $('#error_g_edit').html(error);
    }

    $(document).on('shown.bs.modal', '.modal', function () {
        if(window.is_fullscreen) {
            $('.modal_fs_container .modal-backdrop').show();
        }
    });

    $(document).on('hide.bs.modal', '.modal', function () {
        $('.modal_fs_container .modal-backdrop').hide();
    });

    if (document.addEventListener) {
        document.addEventListener('fullscreenchange', exitHandler, false);
        document.addEventListener('mozfullscreenchange', exitHandler, false);
        document.addEventListener('MSFullscreenChange', exitHandler, false);
        document.addEventListener('webkitfullscreenchange', exitHandler, false);
    }

    function exitHandler() {
        if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
            window.is_fullscreen = false;
            $('.modal_fs_container .modal-backdrop').hide();
            $(window).trigger('resize');
        }
    }
</script>