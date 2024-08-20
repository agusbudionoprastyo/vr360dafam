<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require(__DIR__.'/ssp.class.php');
require(__DIR__.'/../../config/config.inc.php');
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$query = "SELECT u.id,u.username as name,a.* FROM svt_users as u
LEFT JOIN svt_assign_virtualtours as a ON a.id_virtualtour=$id_virtualtour AND a.id_user=u.id
WHERE u.role='editor'
ORDER BY u.username";
$table = "( $query ) t";
$primaryKey = 'id';
$columns = array(
    array(
        'db' => 'id',
        'dt' => 'DT_RowId',
        'formatter' => function( $d, $row ) {
            return $d;
        }
    ),
    array( 'db' => 'id_user',  'dt' =>0, 'formatter' => function( $d, $row ) {
        if(!empty($d)) {
            $input = '<input class="assigned_user" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="assigned_user" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'name',  'dt' =>1, 'formatter' => function( $d, $row ) {
        return $d;
    }),
    array( 'db' => 'edit_virtualtour',  'dt' =>2, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_virtualtour" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_virtualtour" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_virtualtour_ui',  'dt' =>3, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_virtualtour_ui" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_virtualtour_ui" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'create_rooms',  'dt' =>4, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions create_rooms" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions create_rooms" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_rooms',  'dt' =>5, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_rooms" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_rooms" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'delete_rooms',  'dt' =>6, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions delete_rooms" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions delete_rooms" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'create_markers',  'dt' =>7, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions create_markers" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions create_markers" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_markers',  'dt' =>8, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_markers" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_markers" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'delete_markers',  'dt' =>9, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions delete_markers" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions delete_markers" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'create_pois',  'dt' =>10, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions create_pois" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions create_pois" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_pois',  'dt' =>11, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_pois" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_pois" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'delete_pois',  'dt' =>12, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions delete_pois" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions delete_pois" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'create_maps',  'dt' =>13, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions create_maps" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions create_maps" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_maps',  'dt' =>14, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_maps" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_maps" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'delete_maps',  'dt' =>15, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions delete_maps" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions delete_maps" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'info_box',  'dt' =>16, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions info_box" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions info_box" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'presentation',  'dt' =>17, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions presentation" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions presentation" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'gallery',  'dt' =>18, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions gallery" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions gallery" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'icons_library',  'dt' =>19, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions icons_library" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions icons_library" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'media_library',  'dt' =>20, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions media_library" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions media_library" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'music_library',  'dt' =>21, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions music_library" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions music_library" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'sound_library',  'dt' =>22, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions sound_library" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions sound_library" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'publish',  'dt' =>23, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions publish" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions publish" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'landing',  'dt' =>24, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions landing" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions landing" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'forms',  'dt' =>25, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions forms" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions forms" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'leads',  'dt' =>26, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions leads" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions leads" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'shop',  'dt' =>27, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions shop" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions shop" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'edit_3d_view',  'dt' =>28, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions edit_3d_view" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions edit_3d_view" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'video360',  'dt' =>29, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions video360" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions video360" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'measurements',  'dt' =>30, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions measurements" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions measurements" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
    array( 'db' => 'video_projects',  'dt' =>31, 'formatter' => function( $d, $row ) {
        if(empty($d)) $d=0;
        if($d==1) {
            $input = '<input class="editor_permissions video_projects" id="'.$row['id'].'" checked type="checkbox">';
        } else {
            $input = '<input class="editor_permissions video_projects" id="'.$row['id'].'" type="checkbox">';
        }
        return $input;
    }),
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns )
);