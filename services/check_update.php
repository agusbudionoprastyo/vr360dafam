<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");

ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
$debug = false;

function check_directory($path) {
    try {
        if (!file_exists(dirname(__FILE__).$path)) {
            mkdir(dirname(__FILE__).$path, 0775);
        }
    } catch (Exception $e) {}
}

function fatal_handler() {
    global $debug;
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        if($debug) {
            echo format_error( $errno, $errstr, $errfile, $errline)."<br>";
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}

if($debug) {
    register_shutdown_function( "fatal_handler" );
}

//CHECKING DIRECTORIES
check_directory('/../backend/assets/');
check_directory('/../backend/tmp_panoramas/');
check_directory('/../viewer/content/');
check_directory('/../viewer/content/thumb/');
check_directory('/../viewer/gallery/');
check_directory('/../viewer/gallery/thumb/');
check_directory('/../viewer/icons/');
check_directory('/../viewer/media/');
check_directory('/../viewer/media/thumb/');
check_directory('/../viewer/maps/');
check_directory('/../viewer/videos/');
check_directory('/../viewer/panoramas/');
check_directory('/../viewer/panoramas/lowres/');
check_directory('/../viewer/panoramas/mobile/');
check_directory('/../viewer/panoramas/multires/');
check_directory('/../viewer/panoramas/original/');
check_directory('/../viewer/panoramas/preview/');
check_directory('/../viewer/panoramas/thumb/');
check_directory('/../viewer/panoramas/thumb_custom/');
check_directory('/../viewer/pointclouds/');
check_directory('/../viewer/objects360/');
check_directory('/../viewer/products/');
check_directory('/../viewer/products/thumb/');
check_directory('/../video360/');
check_directory('/../video/');
check_directory('/../video/assets/');
check_directory('/../video/tmp/');
check_directory('/../video/tmp/frames/');
check_directory('/../video/tmp/videos/');

//UPDATE 1.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'allow_pitch';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `allow_pitch` BOOL NOT NULL DEFAULT '1';");
    }
}

//UPDATE 1.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'song';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `song` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'song_autoplay';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `song_autoplay` tinyint(1) NOT NULL DEFAULT '0';");
    }
}

//UPDATE 1.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'role';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `role` varchar(50) DEFAULT 'customer';");
        $result2 = $mysqli->query("SELECT * FROM svt_users WHERE role='administrator';");
        if ($result2->num_rows==0) {
            $mysqli->query("UPDATE svt_users SET role='administrator' LIMIT 1;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_plan';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_plan` bigint(20) DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'active';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `active` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_plans';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_plans` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(50) DEFAULT NULL,
                                  `n_virtual_tours` int(11) DEFAULT NULL,
                                  `n_rooms` int(11) DEFAULT NULL,
                                  `n_markers` int(11) DEFAULT NULL,
                                  `n_pois` int(11) DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $mysqli->query("INSERT INTO `svt_plans` (`id`, `name`, `n_virtual_tours`, `n_rooms`, `n_markers`, `n_pois`) VALUES(1, 'Unlimited', -1, -1, -1, -1);");
    }
}

//UPDATE 1.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `logo` varchar(50) DEFAULT NULL;");
    }
}

//UPDATE 1.8
$result = $mysqli->query("SHOW TABLES LIKE 'svt_presentations';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_presentations` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `id_room` bigint(20) unsigned DEFAULT NULL,
                              `action` varchar(50) DEFAULT NULL,
                              `params` text,
                              `sleep` int(11) NOT NULL DEFAULT '0',
                              `priority_1` int(11) DEFAULT NULL,
                              `priority_2` int(11) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_virtual_tour` (`id_virtualtour`),
                              KEY `id_room` (`id_room`),
                              CONSTRAINT `svt_presentations_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                              CONSTRAINT `svt_presentations_ibfk_2` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}

//UPDATE 1.9
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'nadir_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `nadir_logo` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'nadir_size';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `nadir_size` varchar(25) NOT NULL DEFAULT 'small';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autorotate_inactivity';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autorotate_inactivity` int(11) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autorotate_speed';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autorotate_speed` int(11) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_icon` varchar(50) NOT NULL DEFAULT 'fas fa-chevron-circle-up';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_show_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_show_room` tinyint(1) NOT NULL DEFAULT '1';");
    }
}

//UPDATE 2.0
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'html') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','html') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'arrows_nav';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `arrows_nav` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'info_box';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `info_box` longtext;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_gallery';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_gallery` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `image` varchar(50) DEFAULT NULL,
                              `priority` int(11) NOT NULL DEFAULT '0',
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_gallery_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}

//UPDATE 2.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'priority';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `priority` int(11) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'password';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `password` varchar(200) DEFAULT NULL;");
    }
}

//UPDATE 2.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'id_map';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `id_map` bigint(20) unsigned DEFAULT NULL AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_maps';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_maps` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned NOT NULL,
                              `map` varchar(200) DEFAULT NULL,
                              `point_color` varchar(25) NOT NULL DEFAULT '#005eff',
                              `name` varchar(200) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_maps_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        $query_m = "SELECT id,map FROM svt_virtualtours WHERE map <> '';";
        $result_m = $mysqli->query($query_m);
        if($result_m) {
            if($result_m->num_rows>0) {
                while($row_m = $result_m->fetch_array(MYSQLI_ASSOC)) {
                    $id_vt = $row_m['id'];
                    $map = $row_m['map'];
                    $result_i = $mysqli->query("INSERT INTO svt_maps(id_virtualtour,map,name) VALUES($id_vt,'$map','Main');");
                    if($result_i) {
                        $id_map = $mysqli->insert_id;
                        $mysqli->query("UPDATE svt_rooms SET id_map=$id_map WHERE id_virtualtour=$id_vt AND map_top IS NOT NULL;");
                    }
                }
            }
        }
        $mysqli->query("ALTER TABLE svt_virtualtours DROP COLUMN `map`;");
    }
}

//UPDATE 2.5
$result = $mysqli->query("SHOW TABLES LIKE 'svt_voice_commands';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_voice_commands` (
                              `id` int(11) NOT NULL DEFAULT '0',
                              `language` varchar(10) NOT NULL DEFAULT 'en-US',
                              `initial_msg` varchar(200) NOT NULL DEFAULT 'Listening ... Say HELP for command list',
                              `listening_msg` varchar(200) NOT NULL DEFAULT 'Listening ...',
                              `next_cmd` varchar(200) NOT NULL DEFAULT 'next',
                              `next_msg` varchar(200) NOT NULL DEFAULT 'Ok, going to next room',
                              `prev_cmd` varchar(200) NOT NULL DEFAULT 'prev',
                              `prev_msg` varchar(200) NOT NULL DEFAULT 'Ok, going to previous room',
                              `left_cmd` varchar(200) NOT NULL DEFAULT 'left',
                              `left_msg` varchar(200) NOT NULL DEFAULT 'Ok, looking left',
                              `right_cmd` varchar(200) NOT NULL DEFAULT 'right',
                              `right_msg` varchar(200) NOT NULL DEFAULT 'Ok, looking right',
                              `up_cmd` varchar(200) NOT NULL DEFAULT 'up',
                              `up_msg` varchar(200) NOT NULL DEFAULT 'Ok, looking up',
                              `down_cmd` varchar(200) NOT NULL DEFAULT 'down',
                              `down_msg` varchar(200) NOT NULL DEFAULT 'Ok, looking down',
                              `help_cmd` varchar(200) NOT NULL DEFAULT 'help',
                              `help_msg_1` varchar(200) NOT NULL DEFAULT 'Say NEXT / PREVIOUS to navigate between rooms',
                              `help_msg_2` varchar(200) NOT NULL DEFAULT 'Say LEFT / RIGHT / UP / DOWN to look around',
                              `error_msg` varchar(200) NOT NULL DEFAULT 'I do not understand, repeat please...',
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $mysqli->query("INSERT IGNORE INTO `svt_voice_commands` (`id`) VALUES(1);");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'voice_commands';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `voice_commands` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `icon` varchar(50) DEFAULT NULL AFTER `type`;");
        $mysqli->query("UPDATE svt_pois SET icon='fas fa-image' WHERE `type`='image';");
        $mysqli->query("UPDATE svt_pois SET icon='fas fa-video' WHERE `type`='video';");
        $mysqli->query("UPDATE svt_pois SET icon='fas fa-link' WHERE `type`='link';");
        $mysqli->query("UPDATE svt_pois SET icon='fas fa-info-circle' WHERE `type`='html';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'html_sc') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','html','html_sc','download') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_color` varchar(25) NOT NULL DEFAULT '#000000' AFTER `markers_icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_background` varchar(25) NOT NULL DEFAULT 'rgba(255,255,255,0.7)' AFTER `markers_icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `color` varchar(25) NOT NULL DEFAULT '#000000' AFTER `icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `background` varchar(25) NOT NULL DEFAULT 'rgba(255,255,255,0.7)' AFTER `icon`;");
    }
}

//UPDATE 2.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'compass';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `compass` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_image';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_image` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'auto_start';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `auto_start` tinyint(1) NOT NULL DEFAULT '1';");
    }
}

//UPDATE 2.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `description` text DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'ga_tracking_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `ga_tracking_id` varchar(25) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'friendly_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `friendly_url` varchar(100) DEFAULT NULL;");
    }
}

//UPDATE 2.8
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'point_size';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `point_size` int(11) NOT NULL DEFAULT '20';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'compress_jpg';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `compress_jpg` int(11) NOT NULL DEFAULT '90';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'active';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `active` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'max_pitch';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `max_pitch` int(11) NOT NULL DEFAULT '90' AFTER `allow_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'min_pitch';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `min_pitch` int(11) NOT NULL DEFAULT '-90' AFTER `allow_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'rotateZ';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `rotateZ` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'rotateX';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `rotateX` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_settings';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_settings` (
                              `id` int(11) NOT NULL DEFAULT '0',
                              `purchase_code` varchar(250) DEFAULT NULL,
                              `license` varchar(250) DEFAULT NULL,
                              `name` varchar(200) DEFAULT 'Simple Virtual Tour',
                              `logo` varchar(50) DEFAULT NULL,
                              `background` varchar(50) DEFAULT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $mysqli->query("INSERT IGNORE INTO `svt_settings` (`id`) VALUES(1);");
    }
}

//UPDATE 2.9
$result = $mysqli->query("SHOW TABLES LIKE 'svt_icons';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_icons` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `image` varchar(50) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_icon_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_color` varchar(25) NOT NULL DEFAULT '#000000' AFTER `markers_show_room`;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_background` varchar(25) NOT NULL DEFAULT 'rgba(255,255,255,0.7)' AFTER `markers_show_room`;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_icon` varchar(50) NOT NULL DEFAULT 'fas fa-info-circle' AFTER `markers_show_room`;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_style` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'style';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `style` tinyint(1) NOT NULL DEFAULT '0' AFTER `type`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `icon` varchar(50) NOT NULL DEFAULT 'fas fa-chevron-circle-up';");
        $mysqli->query("ALTER TABLE svt_markers ADD `color` varchar(25) NOT NULL DEFAULT '#000000';");
        $mysqli->query("ALTER TABLE svt_markers ADD `background` varchar(25) NOT NULL DEFAULT 'rgba(255,255,255,0.7)';");
        $mysqli->query("ALTER TABLE svt_markers ADD `show_room` tinyint(1) NOT NULL DEFAULT '1';");
        $query_v = "SELECT id,markers_icon,markers_color,markers_background,markers_show_room FROM svt_virtualtours;";
        $result_v = $mysqli->query($query_v);
        if($result_v) {
            if($result_v->num_rows>0) {
                while($row_v = $result_v->fetch_array(MYSQLI_ASSOC)) {
                    $id_vt = $row_v['id'];
                    $markers_icon = $row_v['markers_icon'];
                    $markers_color = $row_v['markers_color'];
                    $markers_background = $row_v['markers_background'];
                    $markers_show_room = $row_v['markers_show_room'];
                    $mysqli->query("UPDATE svt_markers SET icon='$markers_icon',color='$markers_color',background='$markers_background',show_room=$markers_show_room 
                                            WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_vt);");
                }
            }
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'size_scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `size_scale` float NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'size_scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `size_scale` float NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'id_icon_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `id_icon_library` bigint(20) unsigned NOT NULL DEFAULT '0' AFTER `icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'id_icon_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `id_icon_library` bigint(20) unsigned NOT NULL DEFAULT '0' AFTER `icon`;");
    }
}

//UPDATE 2.9.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'link_ext') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download') DEFAULT NULL;");
        }
    }
}

//UPDATE 2.9.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'link_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `link_logo` varchar(250) DEFAULT NULL AFTER `logo`;");
    }
}

//UPDATE 3.0
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'max_width_compress';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `max_width_compress` int(11) NOT NULL DEFAULT '8192' AFTER `compress_jpg`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'sameAzimuth';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `sameAzimuth` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'access_count';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `access_count` bigint(20) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'access_count';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `access_count` bigint(20) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_rooms_access_log';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE `svt_rooms_access_log` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_room` bigint(20) unsigned DEFAULT NULL,
                              `time` int(11) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_room` (`id_room`),
                              CONSTRAINT `svt_rooms_access_log_ibfk_1` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'form') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_forms_data';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE `svt_forms_data` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned NOT NULL,
                              `id_room` bigint(20) unsigned DEFAULT NULL,
                              `title` varchar(250) DEFAULT NULL,
                              `field1` text,
                              `field2` text,
                              `field3` text,
                              `field4` text,
                              `field5` text,
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_forms_data_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;");
    }
}

//UPDATE 3.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'auto_show_slider';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `auto_show_slider` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'form_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `form_enable` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'form_icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `form_icon` varchar(50) NOT NULL DEFAULT 'fas fa-file-signature';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'form_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `form_content` text DEFAULT NULL;");
    }
}

//UPDATE 3.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `email` varchar(100) DEFAULT NULL AFTER `username`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'forgot_code';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `forgot_code` varchar(16) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'smtp_server';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_server` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_auth` tinyint(1) NOT NULL DEFAULT '0';");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_username` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_password` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_secure` enum('none','ssl','tls') DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_port` int(11) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_from_email` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_from_name` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_settings ADD `smtp_valid` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'label';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `label` varchar(100) DEFAULT NULL AFTER `icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `title` varchar(100) DEFAULT NULL AFTER `color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `description` text AFTER `title`;");
    }
}

//UPDATE 3.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'visible_list';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `visible_list` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'html_landing';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `html_landing` longtext;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'fb_messenger';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `fb_messenger` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'fb_page_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `fb_page_id` varchar(50) DEFAULT NULL;");
    }
}

//UPDATE 3.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'video360') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_registration';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_registration` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'default_id_plan';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `default_id_plan` bigint(20) unsigned DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'furl_blacklist';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `furl_blacklist` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'days';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `days` int(11) NOT NULL DEFAULT '-1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'registration_date';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'expire_plan_date';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `expire_plan_date` DATETIME DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `type` enum('image','video') DEFAULT 'image' AFTER `name`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'panorama_video';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `panorama_video` varchar(100) DEFAULT NULL AFTER `panorama_image`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_landing';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_landing` tinyint(1) NOT NULL DEFAULT '1';");
    }
}

//UPDATE 3.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_info';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_info` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_gallery';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_gallery` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_icons_toggle';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_icons_toggle` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_presentation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_presentation` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_main_form';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_main_form` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_share';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_share` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_device_orientation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_device_orientation` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_webvr';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_webvr` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_map';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_map` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_fullscreen';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_fullscreen` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_audio';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_audio` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'live_session';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `live_session` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'song';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `song` varchar(50) DEFAULT NULL;");
    }
}

//UPDATE 3.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'annotation_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `annotation_title` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'annotation_description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `annotation_description` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_annotations';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_annotations` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_list_alt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_list_alt` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'list_alt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `list_alt` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'audio') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio') DEFAULT NULL;");
        }
    }
}

//UPDATE 3.7
$result = $mysqli->query("SHOW TABLES LIKE 'svt_poi_gallery';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_poi_gallery` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_poi` bigint(20) unsigned DEFAULT NULL,
                              `image` varchar(50) DEFAULT NULL,
                              `priority` int(11) NOT NULL DEFAULT '0',
                              PRIMARY KEY (`id`),
                              KEY `id_poi` (`id_poi`),
                              CONSTRAINT `svt_poi_gallery_ibfk_1` FOREIGN KEY (`id_poi`) REFERENCES `svt_pois` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'gallery') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'intro_desktop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `intro_desktop` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'intro_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `intro_mobile` varchar(50) DEFAULT NULL;");
    }
}

//UPDATE 3.8
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'start_date';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `start_date` date DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'end_date';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `end_date` date DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'start_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `start_url` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'end_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `end_url` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'north_degree';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `north_degree` int(11) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'target';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `target` enum('_blank','_self') DEFAULT NULL AFTER `content`;");
        $mysqli->query("UPDATE svt_pois SET `target`='_blank' WHERE `type`='link_ext';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'auto_presentation_speed';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `auto_presentation_speed` int(11) NOT NULL DEFAULT '5';");
    }
}

//UPDATE 3.9
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'language';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `language` varchar(10) NOT NULL DEFAULT 'en_US';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'language_domain';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `language_domain` varchar(50) NOT NULL DEFAULT 'default';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_assign_virtualtours';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_assign_virtualtours` (
                                  `id_user` int(11) unsigned DEFAULT NULL,
                                  `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                  UNIQUE KEY `id_user` (`id_user`,`id_virtualtour`),
                                  KEY `id_virtualtour` (`id_virtualtour`),
                                  CONSTRAINT `svt_assign_virtualtours_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                  CONSTRAINT `svt_assign_virtualtours_ibfk_2` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_gallery LIKE 'description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_gallery ADD `description` text AFTER `image`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_gallery LIKE 'title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_gallery ADD `title` varchar(100) DEFAULT NULL AFTER `image`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_poi_gallery LIKE 'description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_poi_gallery ADD `description` text AFTER `image`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_poi_gallery LIKE 'title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_poi_gallery ADD `title` varchar(100) DEFAULT NULL AFTER `image`;");
    }
}

//UPDATE 4.0
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'enable_multires';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `enable_multires` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'multires_status';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `multires_status` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'rotateZ';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `rotateZ` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'rotateX';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `rotateX` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'whatsapp_number';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `whatsapp_number` varchar(25) DEFAULT NULL AFTER `fb_page_id`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'whatsapp_chat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `whatsapp_chat` tinyint(1) NOT NULL DEFAULT '0' AFTER `fb_page_id`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_chat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_chat` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_gallery`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_tooltip_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_tooltip_type` enum('none','text','preview','room_name') NOT NULL DEFAULT 'none' AFTER `markers_show_room`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_tooltip_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_tooltip_type` enum('none','text') NOT NULL DEFAULT 'none' AFTER `pois_style`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_text';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `tooltip_text` varchar(100) DEFAULT NULL AFTER `color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `tooltip_type` enum('none','text','preview','room_name') NOT NULL DEFAULT 'none' AFTER `color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_text';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `tooltip_text` varchar(100) DEFAULT NULL AFTER `color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `tooltip_type` enum('none','text') NOT NULL DEFAULT 'none' AFTER `color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'audio_track_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `audio_track_enable` tinyint(1) NOT NULL DEFAULT '0' AFTER `song`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'transition_loading';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `transition_loading` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'transition_time';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `transition_time` int(11) NOT NULL DEFAULT '250';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'transition_zoom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `transition_zoom` int(11) NOT NULL DEFAULT '20';");
    }
}

//UPDATE 4.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'transition_fadeout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `transition_fadeout` int(11) NOT NULL DEFAULT '400';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'note';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `note` text DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_gallery';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_gallery` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_presentation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_presentation` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'price';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `price` float NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'currency';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `currency` varchar(3) NOT NULL DEFAULT 'USD';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_tooltip_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'preview_square') === false) {
            $mysqli->query("ALTER TABLE svt_virtualtours MODIFY COLUMN `markers_tooltip_type` enum('none','text','preview','preview_square','preview_rect','room_name') NOT NULL DEFAULT 'none';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'preview_square') === false) {
            $mysqli->query("ALTER TABLE svt_markers MODIFY COLUMN `tooltip_type` enum('none','text','preview','preview_square','preview_rect','room_name') NOT NULL DEFAULT 'none';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'contact_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `contact_email` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'change_plan';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `change_plan` tinyint(1) NOT NULL DEFAULT '0';");
    }
}

//UPDATE 4.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_product_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_product_stripe` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_price_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_price_stripe` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'stripe_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `stripe_enabled` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'stripe_secret_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `stripe_secret_key` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'stripe_public_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `stripe_public_key` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_customer_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_customer_stripe` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_subscription_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_subscription_stripe` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'status_subscription_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `status_subscription_stripe` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'language';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `language` varchar(10) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_vt_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_vt_title` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_info`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'priority';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `priority` int(11) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'yaw_room_target';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `yaw_room_target` int(11) DEFAULT NULL AFTER `id_room_target`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'pitch_room_target';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `pitch_room_target` int(11) DEFAULT NULL AFTER `id_room_target`;");
    }
}

//UPDATE 4.2.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'version';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `version` varchar(10) NOT NULL DEFAULT '';");
    }
}

//UPDATE 4.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_text';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'text') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `tooltip_text` text;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_live_session';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_live_session` tinyint(1) NOT NULL DEFAULT '1' AFTER `create_presentation`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'hash';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `hash` varchar(36) DEFAULT NULL AFTER `forgot_code`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'validate_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `validate_email` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_registration`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'h_roll';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `h_roll` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'h_pitch';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `h_pitch` int(11) NOT NULL DEFAULT '0' AFTER `yaw`;");
    }
}

//UPDATE 4.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'passcode_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `passcode_title` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'passcode_description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `passcode_description` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'passcode';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `passcode` varchar(32) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'transition_time';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `transition_time` int(11) NOT NULL DEFAULT '250';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'transition_zoom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `transition_zoom` int(11) NOT NULL DEFAULT '20';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'transition_fadeout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `transition_fadeout` int(11) NOT NULL DEFAULT '400';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'transition_override';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `transition_override` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'flyin';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `flyin` tinyint(1) NOT NULL DEFAULT '0';");
    }
}

//UPDATE 4.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `protect_type` enum('none','passcode','leads') DEFAULT 'none' AFTER `passcode`;");
        $mysqli->query("UPDATE svt_rooms SET protect_type='passcode' WHERE passcode IS NOT NULL;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_leads';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_leads` (
                              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned NOT NULL,
                              `name` varchar(250) DEFAULT NULL,
                              `email` varchar(250) DEFAULT NULL,
                              `phone` varchar(25) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_leads_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'vaov';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `vaov` int(11) NOT NULL DEFAULT '180' AFTER `max_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'haov';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `haov` int(11) NOT NULL DEFAULT '360' AFTER `max_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'max_yaw';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `max_yaw` int(11) NOT NULL DEFAULT '180' AFTER `max_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'min_yaw';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `min_yaw` int(11) NOT NULL DEFAULT '-180' AFTER `max_pitch`;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_rooms_alt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_rooms_alt` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_room` bigint(20) unsigned NOT NULL,
                              `panorama_image` varchar(100) DEFAULT NULL,
                              `multires_status` tinyint(1) NOT NULL DEFAULT '0',
                              PRIMARY KEY (`id`),
                              KEY `id_room` (`id_room`),
                              CONSTRAINT `svt_rooms_alt_ibfk_1` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_vt_title';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $default = $row['Default'];
        if($default==0) {
            $mysqli->query("ALTER TABLE svt_virtualtours MODIFY COLUMN `show_vt_title` tinyint(1) NOT NULL DEFAULT '1';");
        }
    }
}

//UPDATE 4.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'schedule';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `schedule` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'filters';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `filters` varchar(250) DEFAULT NULL;");
    }
}

//UPDATE 4.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'meeting';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meeting` tinyint(1) NOT NULL DEFAULT '0' AFTER `live_session`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'custom_features';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `custom_features` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_chat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_chat` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_voice_commands';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_voice_commands` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_voice_commands';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_voice_commands` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_share';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_share` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_device_orientation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_device_orientation` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_webvr';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_webvr` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_logo` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_nadir_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_nadir_logo` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_song';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_song` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_forms';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_forms` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_logo` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_annotations';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_annotations` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_logo` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_rooms_multiple';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_rooms_multiple` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_rooms_protect';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_rooms_protect` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_info_box';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_info_box` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_maps';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_maps` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_icons_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_icons_library` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_password_tour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_password_tour` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_expiring_dates';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_expiring_dates` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_statistics';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_statistics` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_flyin';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_flyin` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_auto_rotate';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_auto_rotate` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_multires';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_multires` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_meeting';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_meeting` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'max_file_size_upload';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `max_file_size_upload` int(11) NOT NULL DEFAULT '-1';");
    }
}

//UPDATE 4.8
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'blur';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `blur` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_showcases';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_showcases` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `id_user` int(11) unsigned DEFAULT NULL,
                                  `code` varchar(100) DEFAULT NULL,
                                  `name` varchar(250) DEFAULT NULL,
                                  `friendly_url` varchar(100) DEFAULT NULL,
                                  `banner` varchar(100) DEFAULT NULL,
                                  `logo` varchar(100) DEFAULT NULL,
                                  `bg_color` varchar(10) NOT NULL DEFAULT '#EEEEEE',
                                  PRIMARY KEY (`id`),
                                  KEY `id_user` (`id_user`),
                                  CONSTRAINT `svt_showcases_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_showcase_list';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_showcase_list` (
                                  `id_showcase` bigint(20) unsigned DEFAULT NULL,
                                  `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                  KEY `id_showcase` (`id_showcase`),
                                  KEY `id_virtualtour` (`id_virtualtour`),
                                  CONSTRAINT `svt_showcase_list_ibfk_1` FOREIGN KEY (`id_showcase`) REFERENCES `svt_showcases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                  CONSTRAINT `svt_showcase_list_ibfk_2` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_showcase';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_showcase` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'thumb_image';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `svt_rooms` ADD `thumb_image` varchar(100) DEFAULT NULL AFTER `panorama_image`");
    }
}

//UPDATE 4.9
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_google_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_google_enable` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_facebook_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_facebook_enable` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_twitter_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_twitter_enable` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_google_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_google_id` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_google_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_google_secret` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_facebook_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_facebook_id` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_facebook_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_facebook_secret` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_twitter_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_twitter_id` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_twitter_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_twitter_secret` varchar(200) DEFAULT NULL;");
    }
}

//UPDATE 5.0
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'language';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `language` varchar(10) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'external';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `external` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'external_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `external_url` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'help_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `help_url` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_external_vt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_external_vt` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'yaw';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'float') === false) {
            $mysqli->query("ALTER TABLE `svt_pois` MODIFY COLUMN `yaw` float DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'pitch';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'float') === false) {
            $mysqli->query("ALTER TABLE `svt_pois` MODIFY COLUMN `pitch` float DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'yaw';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'float') === false) {
            $mysqli->query("ALTER TABLE `svt_markers` MODIFY COLUMN `yaw` float DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'pitch';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'float') === false) {
            $mysqli->query("ALTER TABLE `svt_markers` MODIFY COLUMN `pitch` float DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'virtual_staging';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `virtual_staging` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'main_view_tooltip';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `main_view_tooltip` varchar(100) NOT NULL DEFAULT '';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'view_tooltip';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `view_tooltip` varchar(100) NOT NULL DEFAULT '';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_activate_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_activate_subject` varchar(250) NOT NULL DEFAULT 'Activation Account';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_activate_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_activate_body` text;");
        $mysqli->query("UPDATE svt_settings SET `mail_activate_body`='<p>Hi %USER_NAME%,<br>thanks for signing up!</p><p><br></p><p>Please click on this link to activate your account:</p><p>%LINK%</p>';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_forgot_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_forgot_subject` varchar(250) NOT NULL DEFAULT 'Forgot Password';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_forgot_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_forgot_body` text;");
        $mysqli->query("UPDATE svt_settings SET `mail_forgot_body`='<p>Hi %USER_NAME%,<br>this is your verification code: %VERIFICATION_CODE%</p><p><br></p><p>Please click on this link to change your password:</p><p>%LINK%</p>';");
    }
}

//UPDATE 5.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'avatar';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `avatar` varchar(50) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'visible';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `visible` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'external_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `external_url` varchar(250) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_categories';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_categories` (
                                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(100) DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'keyboard_mode';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `keyboard_mode` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'first_name';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `first_name` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'last_name';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `last_name` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'company';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `company` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'tax_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `tax_id` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'street';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `street` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'city';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `city` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'postal_code';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `postal_code` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'province';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `province` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'country';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `country` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'tel';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `tel` varchar(100) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'first_name_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `first_name_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'last_name_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `last_name_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'company_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `company_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'tax_id_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `tax_id_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'street_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `street_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'city_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `city_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'postal_code_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `postal_code_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'province_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `province_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'country_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `country_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'tel_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `tel_enable` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'first_name_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `first_name_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'last_name_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `last_name_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'company_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `company_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'tax_id_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `tax_id_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'street_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `street_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'city_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `city_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'postal_code_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `postal_code_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'province_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `province_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'country_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `country_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'tel_mandatory';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `tel_mandatory` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'frequency';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `frequency` enum('one_time','recurring') DEFAULT 'recurring';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'interval_count';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `interval_count` int(11) NOT NULL DEFAULT '1';");
    }
}

//UPDATE 5.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'hfov';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `svt_rooms` ADD `hfov` int(11) NOT NULL DEFAULT '0' AFTER `pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_map_tour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_map_tour` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_map`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'map_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `map_type` enum('floorplan','map') DEFAULT 'floorplan' AFTER `id_virtualtour`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'lon';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `svt_rooms` ADD `lon` varchar(50) DEFAULT NULL AFTER `map_left`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'lat';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `svt_rooms` ADD `lat` varchar(50) DEFAULT NULL AFTER `map_left`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'zoom_to_point';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `zoom_to_point` tinyint(1) NOT NULL DEFAULT '0' AFTER `north_degree`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'zoom_level';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `zoom_level` int(11) NOT NULL DEFAULT '16' AFTER `north_degree`;");
    }
}

//UPDATE 5.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'peerjs_host';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `peerjs_host` varchar(250) NOT NULL DEFAULT 'svtpeerjs.simpledemo.it';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'peerjs_port';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `peerjs_port` int(5) NOT NULL DEFAULT '9000';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'peerjs_path';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `peerjs_path` varchar(250) NOT NULL DEFAULT '/svt';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'jitsi_domain';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `jitsi_domain` varchar(250) NOT NULL DEFAULT 'meet.jit.si';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_street_basemap';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_street_basemap` varchar(250) NOT NULL DEFAULT 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_street_subdomain';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_street_subdomain` varchar(250) NOT NULL DEFAULT 'mt0,mt1,mt2,mt3';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_street_maxzoom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_street_maxzoom` int(2) NOT NULL DEFAULT '20';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_satellite_basemap';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_satellite_basemap` varchar(250) NOT NULL DEFAULT 'https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_satellite_subdomain';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_satellite_subdomain` varchar(250) NOT NULL DEFAULT 'mt0,mt1,mt2,mt3';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'leaflet_satellite_maxzoom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `leaflet_satellite_maxzoom` int(2) NOT NULL DEFAULT '20';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_advertisements';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_advertisements` (
                                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(100) DEFAULT NULL,
                                  `image` varchar(50) DEFAULT NULL,
                                  `link` varchar(250) DEFAULT NULL,
                                  `countdown` int(11) NOT NULL DEFAULT 0,
                                  `id_plans` varchar(100) DEFAULT NULL,
                                  `auto_assign` tinyint(1) NOT NULL DEFAULT 0,
                                  PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_assign_advertisements';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_assign_advertisements` (
                                  `id_advertisement` int(11) unsigned NOT NULL,
                                  `id_virtualtour` bigint(20) unsigned NOT NULL,
                                  KEY `id_advertisement` (`id_advertisement`),
                                  KEY `id_virtualtour` (`id_virtualtour`),
                                  CONSTRAINT `svt_assign_advertisements_ibfk_1` FOREIGN KEY (`id_advertisement`) REFERENCES `svt_advertisements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                  CONSTRAINT `svt_assign_advertisements_ibfk_2` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'css_class';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `css_class` varchar(250) NOT NULL DEFAULT '';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'css_class';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `css_class` varchar(250) NOT NULL DEFAULT '';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcase_list LIKE 'type_viewer';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcase_list ADD `type_viewer` enum('viewer','landing') NOT NULL DEFAULT 'viewer';");
    }
}

//UPDATE 5.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcases LIKE 'header_html';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcases ADD `header_html` longtext DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcases LIKE 'footer_html';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcases ADD `footer_html` longtext DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_id_icon_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_id_icon_library` bigint(20) unsigned NOT NULL DEFAULT 0 AFTER `markers_icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_id_icon_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_id_icon_library` bigint(20) unsigned NOT NULL DEFAULT 0 AFTER `pois_icon`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'transition_effect';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `transition_effect` varchar(25) NOT NULL DEFAULT 'fade' AFTER `transition_fadeout`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'transition_effect';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `transition_effect` varchar(25) NOT NULL DEFAULT 'fade' AFTER `transition_fadeout`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'languages_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `languages_enabled` text;");
    }
}

//UPDATE 5.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'background_reg';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `background_reg` varchar(50) DEFAULT NULL AFTER `background`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'welcome_msg';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `welcome_msg` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'password_meeting';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `password_meeting` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'password_livesession';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `password_livesession` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_sample';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_sample` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'id_vt_sample';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `id_vt_sample` bigint(20) unsigned DEFAULT NULL;");
        $mysqli->query("ALTER TABLE `svt_settings` ADD FOREIGN KEY (`id_vt_sample`) REFERENCES `svt_virtualtours` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'allow_hfov';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `allow_hfov` tinyint(1) NOT NULL DEFAULT '1' AFTER `allow_pitch`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'google_maps') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_visitors';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_visitors` (
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `datetime` datetime DEFAULT NULL,
                              `ip` varchar(50) DEFAULT NULL,
                              UNIQUE KEY `id_virtualtour` (`id_virtualtour`,`ip`),
                              CONSTRAINT `svt_visitors_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'theme_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `theme_color` varchar(25) NOT NULL DEFAULT '#0b5394' AFTER `name`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'edit_virtualtour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_virtualtour` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `create_rooms` tinyint(1) NOT NULL DEFAULT 0;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_rooms` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `delete_rooms` tinyint(1) NOT NULL DEFAULT 0;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `create_markers` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_markers` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `delete_markers` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `create_pois` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_pois` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `delete_pois` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `create_maps` tinyint(1) NOT NULL DEFAULT 0;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_maps` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `delete_maps` tinyint(1) NOT NULL DEFAULT 0;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `info_box` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `presentation` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `gallery` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `icons_library` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `publish` tinyint(1) NOT NULL DEFAULT 0;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `landing` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `forms` tinyint(1) NOT NULL DEFAULT 1;");
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `leads` tinyint(1) NOT NULL DEFAULT 1;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'nav_slider';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `nav_slider` tinyint(1) NOT NULL DEFAULT '0' AFTER `auto_show_slider`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'background_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `background_color` varchar(25) NOT NULL DEFAULT '1,1,1';");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_poi_objects360';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_poi_objects360` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_poi` bigint(20) unsigned DEFAULT NULL,
                              `image` varchar(50) DEFAULT NULL,
                              `priority` int(11) NOT NULL DEFAULT 0,
                              PRIMARY KEY (`id`),
                              KEY `id_poi` (`id_poi`),
                              CONSTRAINT `svt_poi_objects360_ibfk_1` FOREIGN KEY (`id_poi`) REFERENCES `svt_pois` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'object360') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360') DEFAULT NULL;");
        }
    }
}

//UPDATE 5.5.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'id_poi_autoopen';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `id_poi_autoopen` bigint(20) unsigned DEFAULT NULL;");
    }
}

//UPDATE 5.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'preload_panoramas';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `preload_panoramas` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'field6';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `field6` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'field7';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `field7` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'field8';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `field8` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'field9';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `field9` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'field10';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `field10` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'click_anywhere';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `click_anywhere` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'hide_markers';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `hide_markers` tinyint(1) NOT NULL DEFAULT '0';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'embed') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_type` enum('image','video') DEFAULT NULL AFTER `type`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_coords';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_coords` varchar(200) DEFAULT NULL AFTER `embed_type`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_size';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_size` varchar(200) DEFAULT NULL AFTER `embed_coords`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_content` longtext AFTER `embed_size`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_video_muted';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_video_muted` tinyint(1) NOT NULL DEFAULT '1' AFTER `embed_content`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_video_autoplay';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_video_autoplay` tinyint(1) NOT NULL DEFAULT '1' AFTER `embed_video_muted`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'view_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `view_type` tinyint(1) NOT NULL DEFAULT '0' AFTER `css_class`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'box_pos';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `box_pos` varchar(10) DEFAULT 'right' AFTER `view_type`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'expire_plan_date_manual';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `expire_plan_date_manual` DATETIME DEFAULT NULL AFTER `expire_plan_date`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'font_viewer';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `font_viewer` varchar(50) DEFAULT 'Roboto';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'font_backend';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `font_backend` varchar(50) DEFAULT 'Nunito' AFTER `theme_color`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'hfov_mobile_ratio';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `hfov_mobile_ratio` float NOT NULL DEFAULT '1' AFTER `max_hfov`;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_media_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_media_library` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `file` varchar(50) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `id_virtualtour` (`id_virtualtour`),
                              CONSTRAINT `svt_media_library_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_video';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_video` varchar(50) DEFAULT NULL AFTER `background_image`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_video_delay';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_video_delay` int(11) NOT NULL DEFAULT '0' AFTER `background_video`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'hide_loading';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `hide_loading` tinyint(1) NOT NULL DEFAULT '0' AFTER `auto_start`;");
    }
}

//UPDATE 5.6.1
$result = $mysqli->query("SHOW TABLES LIKE 'svt_poi_embedded_gallery';");
if($result) {
    if ($result->num_rows == 0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_poi_embedded_gallery` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `id_poi` bigint(20) unsigned DEFAULT NULL,
                              `image` varchar(50) DEFAULT NULL,
                              `priority` int(11) NOT NULL DEFAULT '0',
                              PRIMARY KEY (`id`),
                              KEY `id_poi` (`id_poi`),
                              CONSTRAINT `svt_poi_embedded_gallery_ibfk_1` FOREIGN KEY (`id_poi`) REFERENCES `svt_pois` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'gallery') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_gallery_autoplay';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_gallery_autoplay` int(11) NOT NULL DEFAULT '0' AFTER `embed_video_autoplay`;");
    }
}

//UPDATE 5.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'width_d';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `width_d` int(11) NOT NULL DEFAULT '300' AFTER `point_size`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'width_m';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `width_m` int(11) NOT NULL DEFAULT '225' AFTER `width_d`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'effect';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `effect` enum('none','snow','rain','fog','fireworks','confetti','sparkle') DEFAULT 'none' AFTER `filters`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_forms_data LIKE 'datetime';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_forms_data ADD `datetime` datetime DEFAULT NULL AFTER `id`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_leads LIKE 'datetime';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_leads ADD `datetime` datetime DEFAULT NULL AFTER `id`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'video_transparent') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_autorotation_toggle';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_autorotation_toggle` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_icons_toggle`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_nav_control';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_nav_control` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_autorotation_toggle`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_export_vt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_export_vt` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_multires`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'media_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `media_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `icons_library`;");
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_music_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_music_library` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                 `file` varchar(50) DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_virtualtour` (`id_virtualtour`),
                                 CONSTRAINT `svt_music_library_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'music_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `music_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `media_library`;");
    }
}

//UPDATE 5.8
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'object3d') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'link') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'text') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link','text') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW TABLES LIKE 'svt_presets';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_presets` (
                              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                              `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                              `name` varchar(100) DEFAULT NULL,
                              `type` varchar(50) DEFAULT NULL,
                              `value` text DEFAULT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_music_library LIKE 'file';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '200') === false) {
            $mysqli->query("ALTER TABLE `svt_music_library` MODIFY `file` varchar(200);");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'quality_viewer';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `quality_viewer` float NOT NULL DEFAULT '1' AFTER `max_width_compress`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'song_bg_volume';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `song_bg_volume` float NOT NULL DEFAULT '0.3';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'song_bg_volume';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `song_bg_volume` float NOT NULL DEFAULT '0.3' AFTER `song`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'selection') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link','text','selection') DEFAULT NULL;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'background';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_pois` MODIFY `background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,0.7)';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'color';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_pois` MODIFY `color` varchar(50) NOT NULL DEFAULT '#000000';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'background';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_markers` MODIFY `background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,0.7)';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'color';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_markers` MODIFY `color` varchar(50) NOT NULL DEFAULT '#000000';");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autoclose_map';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autoclose_map` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_map`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autoclose_list_alt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autoclose_list_alt` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_list_alt`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autoclose_slider';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autoclose_slider` tinyint(1) NOT NULL DEFAULT '0' AFTER `auto_show_slider`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'autoclose_menu';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `autoclose_menu` tinyint(1) NOT NULL DEFAULT '0' AFTER `sameAzimuth`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `embed_type` enum('selection') DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'embed_coords';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `embed_coords` varchar(200) DEFAULT NULL AFTER `embed_type`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'embed_size';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `embed_size` varchar(200) DEFAULT NULL AFTER `embed_coords`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'embed_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `embed_content` longtext AFTER `embed_size`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'small_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `small_logo` varchar(50) DEFAULT NULL AFTER `logo`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'lottie') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie') DEFAULT NULL;");
        }
    }
}

//UPDATE 5.9
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_link_1';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_link_1` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_value_1';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_value_1` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_link_2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_link_2` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_value_2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_value_2` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_link_3';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_link_3` varchar(200) DEFAULT NULL;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'footer_value_3';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `footer_value_3` text;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'password_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `password_title` varchar(500) DEFAULT NULL AFTER `password`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'password_description';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `password_description` text AFTER `password_title`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `logo` varchar(50) DEFAULT NULL AFTER `name`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'transform3d';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `transform3d` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'transform3d';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `transform3d` tinyint(1) NOT NULL DEFAULT '1';");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'welcome_msg';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'longtext') === false) {
            $mysqli->query("ALTER TABLE `svt_settings` MODIFY COLUMN `welcome_msg` longtext;");
        }
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pan_speed';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pan_speed` float NOT NULL DEFAULT '1' AFTER `hfov_mobile_ratio`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pan_speed_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pan_speed_mobile` float NOT NULL DEFAULT '2' AFTER `pan_speed`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'friction';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `friction` float NOT NULL DEFAULT '0.1' AFTER `pan_speed_mobile`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'friction_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `friction_mobile` float NOT NULL DEFAULT '0.4' AFTER `friction`;");
    }
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'lookat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `lookat` tinyint(1) NOT NULL DEFAULT '2';");
    }
}

//UPDATE 6.0
$result = $mysqli->query("SHOW TABLES LIKE 'svt_products';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_products` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                 `name` varchar(100) DEFAULT NULL,
                                 `description` text,
                                 `price` float NOT NULL DEFAULT '0',
                                 `purchase_type` enum('none','cart','link') NOT NULL DEFAULT 'none',
                                 `link` varchar(250) DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_virtualtour` (`id_virtualtour`),
                                 CONSTRAINT `svt_products_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_product_images';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_product_images` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_product` bigint(20) unsigned DEFAULT NULL,
                                 `image` varchar(50) DEFAULT NULL,
                                 `priority` int(11) NOT NULL DEFAULT '0',
                                 PRIMARY KEY (`id`),
                                 KEY `id_product` (`id_product`),
                                 CONSTRAINT `svt_product_images_ibfk_1` FOREIGN KEY (`id_product`) REFERENCES `svt_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'animation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `animation` varchar(50) NOT NULL DEFAULT 'none' AFTER `css_class`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'animation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `animation` varchar(50) NOT NULL DEFAULT 'none' AFTER `css_class`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'product') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie','product') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'snipcart_api_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `snipcart_api_key` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'snipcart_currency';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `snipcart_currency` varchar(3) NOT NULL DEFAULT 'USD';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_shop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_shop` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_export_vt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'shop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `shop` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_wizard';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_wizard` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_external_vt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SELECT * FROM svt_settings WHERE peerjs_host='svtpeerjs.simpledemo.it' AND peerjs_port=443;");
if($result) {
    if ($result->num_rows==1) {
        $mysqli->query("UPDATE svt_settings SET peerjs_port=9000;;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'turn_host';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `turn_host` varchar(250) NOT NULL DEFAULT 'svtpeerjs.simpledemo.it' AFTER `peerjs_path`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'turn_port';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `turn_port` int(5) NOT NULL DEFAULT 5349 AFTER `turn_host`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'turn_username';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `turn_username` varchar(100) NOT NULL DEFAULT 'svt' AFTER `turn_port`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'turn_password';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `turn_password` varchar(100) NOT NULL DEFAULT 'svt' AFTER `turn_username`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_logo';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_logo` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_vt_title`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'target';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '_parent') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `target` enum('_blank','_self','_parent','_top') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'ui_style';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `ui_style` text DEFAULT NULL AFTER `font_viewer`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_advertisements LIKE 'type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_advertisements ADD `type` enum('image','video','iframe') NOT NULL DEFAULT 'image' AFTER `name`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_advertisements LIKE 'video';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_advertisements ADD `video` varchar(50) DEFAULT NULL AFTER `image`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_advertisements LIKE 'youtube';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_advertisements ADD `youtube` varchar(250) DEFAULT NULL AFTER `video`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_advertisements LIKE 'iframe_link';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_advertisements ADD `iframe_link` varchar(250) DEFAULT NULL AFTER `youtube`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.0.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_access_log LIKE 'ip';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_access_log ADD `ip` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_access_log LIKE 'ip';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_access_log ADD `ip` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_access_log_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_access_log_room` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_room` bigint(20) unsigned DEFAULT NULL,
                                 `date_time` datetime DEFAULT NULL,
                                 `ip` varchar(50) DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_room` (`id_room`),
                                 CONSTRAINT `svt_access_log_room_ibfk_1` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_access_log_poi';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_access_log_poi` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_poi` bigint(20) unsigned DEFAULT NULL,
                                 `date_time` datetime DEFAULT NULL,
                                 `ip` varchar(50) DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_poi` (`id_poi`),
                                 CONSTRAINT `svt_access_log_poi_ibfk_1` FOREIGN KEY (`id_poi`) REFERENCES `svt_pois` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_panorama_video';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_panorama_video` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_shop`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_custom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_custom` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_info`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom_content` longtext AFTER `show_custom`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'paypal_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `paypal_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `stripe_public_key`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'paypal_live';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `paypal_live` tinyint(1) NOT NULL DEFAULT '0' AFTER `paypal_enabled`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'paypal_client_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `paypal_client_id` varchar(200) DEFAULT NULL AFTER `paypal_live`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'paypal_client_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `paypal_client_secret` varchar(200) DEFAULT NULL AFTER `paypal_client_id`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'id_product_paypal';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `id_product_paypal` varchar(50) DEFAULT NULL AFTER `paypal_client_secret`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_plan_paypal';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_plan_paypal` varchar(50) DEFAULT NULL AFTER `id_price_stripe`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'status_subscription_paypal';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `status_subscription_paypal` tinyint(1) NOT NULL DEFAULT '0' AFTER `status_subscription_stripe`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_subscription_paypal';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_subscription_paypal` varchar(50) DEFAULT NULL AFTER `id_subscription_stripe`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'edit_virtualtour_ui';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_virtualtour_ui` tinyint(1) NOT NULL DEFAULT '1' AFTER `edit_virtualtour`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_editor_ui_presets';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_editor_ui_presets` (
                                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_user` int(11) unsigned DEFAULT NULL,
                                 `name` varchar(100) DEFAULT NULL,
                                 `public` tinyint(1) NOT NULL DEFAULT '0',
                                 `ui_style` text DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_user` (`id_user`),
                                 CONSTRAINT `svt_editor_ui_presets_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE SET NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'hls') === false) {
            $mysqli->query("ALTER TABLE svt_rooms MODIFY COLUMN `type` enum('image','video','hls') DEFAULT 'image';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'panorama_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `panorama_url` varchar(250) DEFAULT NULL AFTER `panorama_video`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_main_form';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $default = $row['Default'];
        if ($default=='1') {
            $mysqli->query("ALTER TABLE svt_virtualtours MODIFY COLUMN `show_main_form` tinyint(1) NOT NULL DEFAULT '0';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_visitors LIKE 'id_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_visitors ADD `id_room` bigint(20) unsigned DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_visitors LIKE 'yaw';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_visitors ADD `yaw` float DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_visitors LIKE 'pitch';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_visitors ADD `pitch` float DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_visitors LIKE 'color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_visitors ADD `color` varchar(10) NOT NULL DEFAULT '#000000';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_visitors LIKE 'id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("DELETE FROM svt_visitors;");
        $mysqli->query("DROP TABLE svt_visitors;");
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_visitors` (
                                  `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                  `initial_datetime` datetime NOT NULL DEFAULT current_timestamp(),
                                  `datetime` datetime DEFAULT NULL,
                                  `ip` varchar(50) DEFAULT NULL,
                                  `id` varchar(100) DEFAULT NULL,
                                  `id_room` bigint(20) unsigned DEFAULT NULL,
                                  `yaw` float DEFAULT NULL,
                                  `pitch` float DEFAULT NULL,
                                  `color` varchar(10) NOT NULL DEFAULT '#000000',
                                  UNIQUE KEY `id_virtualtour` (`id_virtualtour`,`ip`,`id`),
                                  CONSTRAINT `svt_visitors_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'lottie') === false) {
            $mysqli->query("ALTER TABLE svt_rooms MODIFY COLUMN `type` enum('image','video','hls','lottie') DEFAULT 'image';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'panorama_json';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `panorama_json` varchar(100) DEFAULT NULL AFTER `panorama_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'enable_visitor_rt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `enable_visitor_rt` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'interval_visitor_rt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `interval_visitor_rt` int(11) NOT NULL DEFAULT '1000';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'default_view';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `default_view` enum('street','satellite') DEFAULT 'street' AFTER `map_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_default_lookat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_default_lookat` tinyint(1) NOT NULL DEFAULT '2' AFTER `markers_tooltip_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'info_link';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `info_link` varchar(250) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'info_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `info_type` enum('blank','iframe') DEFAULT 'blank';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'poi';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `poi` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'switch_pano') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie','product','switch_pano') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'multires';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `multires` enum('local','cloud') DEFAULT 'local';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'multires_cloud_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `multires_cloud_url` varchar(250) NOT NULL DEFAULT 'https://simplevirtualtour.it/app/tools/multires_cloud.php';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'dollhouse';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `dollhouse` longtext AFTER `info_box`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_dollhouse';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_dollhouse` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_info`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_dollhouse';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_dollhouse` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_shop`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'zIndex';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `zIndex` int(11) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'params';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `params` text AFTER `content`;");
        $mysqli->query("UPDATE svt_pois SET params='floor' WHERE type='object3d';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'auto_close';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `auto_close` int(11) NOT NULL DEFAULT '0' AFTER `box_pos`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'hfov';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $default = $row['Default'];
        if ($default=='') {
            $mysqli->query("ALTER TABLE svt_rooms MODIFY `hfov` int(11) NOT NULL DEFAULT '0';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'customize_menu';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `customize_menu` text;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'video_chroma') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link','text','selection','video_chroma') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom_html';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom_html` longtext;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'context_info';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `context_info` text;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_context_info';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_context_info` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_info_box`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_video';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_video` varchar(250) DEFAULT NULL AFTER `auto_presentation_speed`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_type` enum('manual','automatic','video') NOT NULL DEFAULT 'manual' AFTER `presentation_video`;");
        $mysqli->query("UPDATE svt_virtualtours SET presentation_type='automatic' WHERE auto_presentation_enable=1;");
        $mysqli->query("ALTER TABLE svt_virtualtours DROP COLUMN auto_presentation_enable;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_screencast';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_screencast` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_screencast';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_screencast` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'url_screencast';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `url_screencast` varchar(250) NOT NULL DEFAULT 'https://studio.snipclip.app/record';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'hover_markers';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `hover_markers` tinyint(1) NOT NULL DEFAULT '0' AFTER `hide_markers`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'edit_3d_view';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `edit_3d_view` tinyint(1) NOT NULL DEFAULT '1' AFTER `edit_virtualtour_ui`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_send_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `protect_send_email` tinyint(1) NOT NULL DEFAULT '0' AFTER `protect_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `protect_email` varchar(250) NOT NULL AFTER `protect_send_email`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'drag_device_orientation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `drag_device_orientation` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_device_orientation`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'max_storage_space';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `max_storage_space` int(11) NOT NULL DEFAULT '-1' AFTER `max_file_size_upload`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'storage_space';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `storage_space` float NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_maps LIKE 'id_room_default';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_maps ADD `id_room_default` bigint(20) unsigned DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_maps ADD CONSTRAINT `svt_maps_svt_rooms_id_fk` FOREIGN KEY (`id_room_default`) REFERENCES `svt_rooms` (`id`) ON DELETE SET NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_notifications';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_notifications` (
                                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_user` int(11) unsigned DEFAULT NULL,
                                 `notify_date` timestamp DEFAULT CURRENT_TIMESTAMP,
                                 `subject` varchar(250) DEFAULT NULL,
                                 `body` text,
                                 `notified` tinyint(1) NOT NULL DEFAULT '0',
                                 PRIMARY KEY (`id`),
                                 KEY `id_user` (`id_user`),
                                 CONSTRAINT `svt_notifications_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE SET NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_email` varchar(100) DEFAULT NULL AFTER `contact_email`;");
        $mysqli->query("UPDATE svt_settings SET notify_email=contact_email;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_registrations';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_registrations` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_plan_expires';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_plan_expires` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_plan_changes';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_plan_changes` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_plan_cancels';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_plan_cancels` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_vt_create';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_vt_create` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'id_vt_template';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `id_vt_template` bigint(20) unsigned DEFAULT NULL AFTER `id_vt_sample`;");
        $mysqli->query("ALTER TABLE svt_settings ADD FOREIGN KEY (`id_vt_template`) REFERENCES `svt_virtualtours` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'object3d') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link','text','selection','video_chroma','object3d') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_params';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_params` text AFTER `params`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'icon_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `icon_type` enum('round','square','round_outline','square_outline') DEFAULT 'round' AFTER `icon`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'icon_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `icon_type` enum('round','square','round_outline','square_outline') DEFAULT 'round' AFTER `icon`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_icon_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_icon_type` enum('round','square','round_outline','square_outline') DEFAULT 'round' AFTER `pois_icon`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_icon_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_icon_type` enum('round','square','round_outline','square_outline') DEFAULT 'round' AFTER `markers_icon`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'expire_tours';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `expire_tours` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
if(!file_exists(dirname(__FILE__)."/../config/demo.inc.php")) {
    $demo_config = <<<STR
<?php
    define('DEMO_SERVER_IP', 'X.X.X.X'); //ip of the server
    define('DEMO_DEVELOPER_IP', 'Y.Y.Y.Y'); //ip of the computer to be excluded from the demo mode 
    define('DEMO_USER_ID', '1'); //ip of the administrator user from svt_users 
STR;
    try {
        file_put_contents(dirname(__FILE__)."/../config/demo.inc.php",$demo_config);
    } catch (Exception $e) {}
}
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'gallery_mode';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `gallery_mode` enum('images','slideshow') NOT NULL DEFAULT 'images' AFTER `show_gallery`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'gallery_params';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `gallery_params` text AFTER `gallery_mode`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'html') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `embed_type` enum('image','video','gallery','video_transparent','link','text','selection','video_chroma','object3d','html') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_video360';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_video360` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_panorama_video`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'video360';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `video360` tinyint(1) NOT NULL DEFAULT '1' AFTER `presentation`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'logo_height';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `logo_height` int(11) NOT NULL DEFAULT '16' AFTER `logo`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_custom2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_custom2` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_custom`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom2_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom2_content` longtext AFTER `custom_content`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_custom3';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_custom3` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_custom2`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom3_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom3_content` longtext AFTER `custom2_content`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'video360';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `video360` enum('local','cloud') DEFAULT 'local' AFTER `multires_cloud_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'video360_cloud_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `video360_cloud_url` varchar(250) NOT NULL DEFAULT 'https://simplevirtualtour.it/app/tools/video360_cloud.php' AFTER `video360`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'slideshow';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `slideshow` enum('local','cloud') DEFAULT 'local' AFTER `video360_cloud_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'slideshow_cloud_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `slideshow_cloud_url` varchar(250) NOT NULL DEFAULT 'https://simplevirtualtour.it/app/tools/slideshow_cloud.php' AFTER `slideshow`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_visibility';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `tooltip_visibility` enum('hover','visible','visible_mobile') NOT NULL DEFAULT 'hover' AFTER `tooltip_text`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_visibility';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `tooltip_visibility` enum('hover','visible','visible_mobile') NOT NULL DEFAULT 'hover' AFTER `tooltip_text`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `tooltip_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1)' AFTER `tooltip_visibility`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `tooltip_color` varchar(50) NOT NULL DEFAULT '#000000' AFTER `tooltip_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `tooltip_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1)' AFTER `tooltip_visibility`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'tooltip_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `tooltip_color` varchar(50) NOT NULL DEFAULT '#000000' AFTER `tooltip_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'tooltip_text';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = strtolower($row['Type']);
        if (strpos($type, 'text') === false) {
            $mysqli->query("ALTER TABLE `svt_markers` MODIFY COLUMN `tooltip_text` text;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_tooltip_visibility';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_tooltip_visibility` enum('hover','visible','visible_mobile') NOT NULL DEFAULT 'hover' AFTER `markers_tooltip_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_tooltip_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_tooltip_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1)' AFTER `markers_tooltip_visibility`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_tooltip_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_tooltip_color` varchar(50) NOT NULL DEFAULT '#000000' AFTER `markers_tooltip_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_tooltip_visibility';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_tooltip_visibility` enum('hover','visible','visible_mobile') NOT NULL DEFAULT 'hover' AFTER `pois_tooltip_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_tooltip_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_tooltip_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1)' AFTER `pois_tooltip_visibility`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_tooltip_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_tooltip_color` varchar(50) NOT NULL DEFAULT '#000000' AFTER `pois_tooltip_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_globes';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_globes` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `id_user` int(11) unsigned DEFAULT NULL,
                                 `code` varchar(100) DEFAULT NULL,
                                 `name` varchar(250) DEFAULT NULL,
                                 `friendly_url` varchar(100) DEFAULT NULL,
                                 `logo` varchar(100) DEFAULT NULL,
                                 `pointer_size` int(11) NOT NULL DEFAULT '15',
                                 `pointer_color` varchar(25) NOT NULL DEFAULT 'rgba(255,255,255,1)',
                                 `pointer_border` varchar(25) NOT NULL DEFAULT 'rgba(0,0,0,1)',
                                 `center_lat` varchar(50) DEFAULT NULL,
                                 `center_lon` varchar(50) DEFAULT NULL,
                                 `center_altitude` int(11) DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `id_user` (`id_user`),
                                 CONSTRAINT `svt_globes_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_globe_list';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_globe_list` (
                                 `id_globe` bigint(20) unsigned DEFAULT NULL,
                                 `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                 `lat` varchar(50) DEFAULT NULL,
                                 `lon` varchar(50) DEFAULT NULL,
                                 KEY `id_globe` (`id_globe`),
                                 KEY `id_virtualtour` (`id_virtualtour`),
                                 CONSTRAINT `svt_globe_list_ibfk_1` FOREIGN KEY (`id_globe`) REFERENCES `svt_globes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                 CONSTRAINT `svt_globe_list_ibfk_2` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'stripe_automatic_tax_rate';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `stripe_automatic_tax_rate` enum('unspecified','inclusive','exclusive') DEFAULT 'unspecified' AFTER `stripe_enabled`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_globes';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_globes` tinyint(1) NOT NULL DEFAULT '1' AFTER `create_showcase`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.5.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'min_altitude';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `min_altitude` int(11) DEFAULT NULL AFTER `center_altitude`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'ar_simulator';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `ar_simulator` tinyint(1) NOT NULL DEFAULT '0' AFTER `external_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_ar_vt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_ar_vt` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_external_vt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'captcha_login';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `captcha_login` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'captcha_register';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `captcha_register` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'ar_camera_align';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `ar_camera_align` tinyint(1) NOT NULL DEFAULT '1' AFTER `ar_simulator`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'zoom_duration';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `zoom_duration` int(11) NOT NULL DEFAULT '2';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'default_view';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `default_view` enum('street','satellite') NOT NULL DEFAULT 'satellite';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_inactivity';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_inactivity` int(11) NOT NULL DEFAULT '0' AFTER `autorotate_inactivity`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'friendly_l_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `friendly_l_url` varchar(100) DEFAULT NULL AFTER `friendly_url`;");
        $mysqli->query("UPDATE svt_virtualtours SET friendly_l_url=friendly_url;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'meta_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_title` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_description` text DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_image` varchar(50) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_title_l` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_description_l` text DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `meta_image_l` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'meta_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `meta_title` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_globes ADD `meta_description` text DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_globes ADD `meta_image` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcases LIKE 'meta_title';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcases ADD `meta_title` varchar(100) DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_showcases ADD `meta_description` text DEFAULT NULL;");
        $mysqli->query("ALTER TABLE svt_showcases ADD `meta_image` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'zoom_to_pointer';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `zoom_to_pointer` tinyint(1) NOT NULL DEFAULT '0' AFTER `friction_mobile`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'keep_original_panorama';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `keep_original_panorama` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_multires`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'pointer_color';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_globes` MODIFY `pointer_color` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1)';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'pointer_border';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_globes` MODIFY `pointer_border` varchar(50) NOT NULL DEFAULT 'rgba(0,0,0,1)';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_background';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_virtualtours` MODIFY `markers_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,0.7)';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_background';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, '50') === false) {
            $mysqli->query("ALTER TABLE `svt_virtualtours` MODIFY `pois_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,0.7)';");
        }
    }
} else { echo $mysqli->error; }

//UPDATE 6.6.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_loop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_loop` tinyint(1) NOT NULL DEFAULT '0' AFTER `presentation_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_stop_click';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_stop_click` tinyint(1) NOT NULL DEFAULT '0' AFTER `presentation_loop`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `scale` tinyint(1) NOT NULL DEFAULT '0' AFTER `rotateZ`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `scale` tinyint(1) NOT NULL DEFAULT '0' AFTER `rotateZ`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'box_maximize';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `box_maximize` tinyint(1) NOT NULL DEFAULT '1' AFTER `box_pos`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'box_max_width';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `box_max_width` int(11) NOT NULL DEFAULT '350' AFTER `box_maximize`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_plan_expiring';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_plan_expiring` tinyint(1) NOT NULL DEFAULT '1' AFTER `notify_plan_expires`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_notifications LIKE 'notify_user';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_notifications ADD `notify_user` tinyint(1) NOT NULL DEFAULT '0' AFTER `body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_expiring_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_expiring_subject` varchar(250) NOT NULL DEFAULT 'Your plan is expiring' AFTER `mail_forgot_body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_expiring_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_expiring_body` text AFTER `mail_plan_expiring_subject`;");
        $mysqli->query("UPDATE svt_settings SET `mail_plan_expiring_body`='<p>Hi %USER_NAME%,<br>your plan %PLAN_NAME% is expiring soon on %EXPIRE_DATE%.</p>';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_expired_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_expired_subject` varchar(250) NOT NULL DEFAULT 'Your plan has expired' AFTER `mail_plan_expiring_body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_expired_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_expired_body` text AFTER `mail_plan_expired_subject`;");
        $mysqli->query("UPDATE svt_settings SET `mail_plan_expired_body`='<p>Hi %USER_NAME%,<br>your plan %PLAN_NAME% just expired on %EXPIRE_DATE%.</p>';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'mobile_panoramas';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `mobile_panoramas` tinyint(1) NOT NULL DEFAULT '1' AFTER `preload_panoramas`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_stop_id_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_stop_id_room` bigint(20) unsigned DEFAULT '0' AFTER `presentation_stop_click`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_changed_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_changed_subject` varchar(250) NOT NULL DEFAULT 'Your plan has changed' AFTER `mail_plan_expired_body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_changed_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_changed_body` text AFTER `mail_plan_changed_subject`;");
        $mysqli->query("UPDATE svt_settings SET `mail_plan_changed_body`='<p>Hi %USER_NAME%,<br>your plan has been changed to %PLAN_NAME%.</p>';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_canceled_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_canceled_subject` varchar(250) NOT NULL DEFAULT 'Your plan has canceled' AFTER `mail_plan_changed_body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_plan_canceled_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_plan_canceled_body` text AFTER `mail_plan_canceled_subject`;");
        $mysqli->query("UPDATE svt_settings SET `mail_plan_canceled_body`='<p>Hi %USER_NAME%,<br>your %PLAN_NAME% plan has been canceled.</p>';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_default_scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_default_scale` tinyint(1) NOT NULL DEFAULT '0' AFTER `markers_default_lookat`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_default_scale';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_default_scale` tinyint(1) NOT NULL DEFAULT '0' AFTER `pois_tooltip_color`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_presentations LIKE 'video_wait_end';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_presentations ADD `video_wait_end` tinyint(1) NOT NULL DEFAULT '0' AFTER `sleep`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'video_end_goto';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `video_end_goto` bigint(20) unsigned DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'pdf') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie','product','switch_pano','pdf') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'vr_button';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `vr_button` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.8
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'enable_views_stat';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `enable_views_stat` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_visitor_rt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'nadir_size';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Default'];
        if (strpos($type, '100px') === false) {
            $mysqli->query("ALTER TABLE svt_virtualtours MODIFY COLUMN `nadir_size` varchar(25) NOT NULL DEFAULT '100px';");
            $mysqli->query("UPDATE svt_virtualtours SET nadir_size='100px' WHERE nadir_size='small';");
            $mysqli->query("UPDATE svt_virtualtours SET nadir_size='200px' WHERE nadir_size='medium';");
            $mysqli->query("UPDATE svt_virtualtours SET nadir_size='400px' WHERE nadir_size='large';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'callout') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie','product','switch_pano','pdf','callout') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_user_add_subject';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_user_add_subject` varchar(250) NOT NULL DEFAULT 'Your account has been created' AFTER `mail_plan_canceled_body`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_user_add_body';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `mail_user_add_body` text AFTER `mail_user_add_subject`;");
        $mysqli->query("UPDATE svt_settings SET `mail_user_add_body`='<p>Hi %USER_NAME%,</p><p>your account has been created!</p><p>Below you will find the details to login.</p><p><br></p><p>Link: %LINK%</p><p>Username: %USER_NAME%</p><p>Password: %PASSWORD%</p>';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'notify_useradd';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `notify_useradd` tinyint(1) NOT NULL DEFAULT '1' AFTER `notify_registrations`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_media_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_media_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_icons_library`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_music_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_music_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_media_library`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_editor_ui';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_editor_ui` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_dollhouse`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_custom_html';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_custom_html` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_editor_ui`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_metatag';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_metatag` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_custom_html`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_loading_iv';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_loading_iv` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_metatag`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'n_rooms_tour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `n_rooms_tour` int(11) NOT NULL DEFAULT '-1' AFTER `n_rooms`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_default_backlink';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_default_backlink` tinyint(1) NOT NULL DEFAULT '0' AFTER `markers_default_lookat`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'zoom_friction';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `zoom_friction` float NOT NULL DEFAULT '0.05' AFTER `friction_mobile`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'zoom_friction_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `zoom_friction_mobile` float NOT NULL DEFAULT '0.05' AFTER `zoom_friction`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_measures';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_measures` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `id_room` bigint(20) unsigned DEFAULT NULL,
                                  `pitch_start` float DEFAULT NULL,
                                  `yaw_start` float DEFAULT NULL,
                                  `pitch_end` float DEFAULT NULL,
                                  `yaw_end` float DEFAULT NULL,
                                  `label` varchar(100) DEFAULT NULL,
                                  `params` text,
                                  PRIMARY KEY (`id`),
                                  KEY `id_room` (`id_room`),
                                  CONSTRAINT `svt_measures_ibfk_1` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'measurements';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `measurements` tinyint(1) NOT NULL DEFAULT '1' AFTER `shop`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_measurements';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_measurements` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_dollhouse`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'embed_video_loop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `embed_video_loop` tinyint(1) NOT NULL DEFAULT '1' AFTER `embed_video_autoplay`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_in_first_page';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_in_first_page` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.8.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'n_gallery_images';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `n_gallery_images` int(11) NOT NULL DEFAULT '-1' AFTER `n_pois`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'intro_desktop_hide';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `intro_desktop_hide` int(11) NOT NULL DEFAULT '5' AFTER `intro_desktop`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'intro_mobile_hide';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `intro_mobile_hide` int(11) NOT NULL DEFAULT '5' AFTER `intro_mobile`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_view_pois';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_view_pois` tinyint(1) NOT NULL DEFAULT '0' AFTER `presentation_stop_id_room`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'presentation_view_measures';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `presentation_view_measures` tinyint(1) NOT NULL DEFAULT '0' AFTER `presentation_view_pois`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'purchase_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'popup') === false) {
            $mysqli->query("ALTER TABLE svt_products MODIFY COLUMN `purchase_type` enum('none','cart','link','popup') NOT NULL DEFAULT 'none';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_measures_toggle';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_measures_toggle` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_icons_toggle`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.9
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_in_first_page_l';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_in_first_page_l` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'show_in_first_page';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `show_in_first_page` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcases LIKE 'show_in_first_page';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcases ADD `show_in_first_page` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'visible';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `visible` tinyint(1) NOT NULL DEFAULT '1' AFTER `visible_list`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'info_box_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `info_box_type` enum('popup','panel') NOT NULL DEFAULT 'popup' AFTER `info_box`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'initial_feedback';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `initial_feedback` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'mouse_follow_feedback';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `mouse_follow_feedback` float NOT NULL DEFAULT '0.5';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'embed_params';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `embed_params` text AFTER `embed_content`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.9.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'auto_open';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `auto_open` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'zIndex';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `zIndex` int(11) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }

//UPDATE 6.9.2
$mysqli->query("UPDATE svt_virtualtours SET info_box_type='popup' WHERE info_box_type !='panel';");
$mysqli->query("UPDATE svt_virtualtours SET code=MD5(id) WHERE code IS NULL;");

//UPDATE 6.9.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_wechat_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_wechat_enable` tinyint(1) NOT NULL DEFAULT '0' AFTER `social_twitter_enable`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_qq_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_qq_enable` tinyint(1) NOT NULL DEFAULT '0' AFTER `social_wechat_enable`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_wechat_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_wechat_id` varchar(200) DEFAULT NULL AFTER `social_twitter_secret`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_wechat_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_wechat_secret` varchar(200) DEFAULT NULL AFTER `social_wechat_id`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_qq_id';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_qq_id` varchar(200) DEFAULT NULL AFTER `social_wechat_secret`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'social_qq_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `social_qq_secret` varchar(200) DEFAULT NULL AFTER `social_qq_id`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'style_login';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `style_login` tinyint(1) NOT NULL DEFAULT '1' AFTER `background_reg`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'style_register';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `style_register` tinyint(1) NOT NULL DEFAULT '1' AFTER `style_login`;");
    }
} else { echo $mysqli->error; }

//UPDATE 6.9.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'google_identifier';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `google_identifier` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'facebook_identifier';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `facebook_identifier` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'twitter_identifier';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `twitter_identifier` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'wechat_identifier';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `wechat_identifier` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'qq_identifier';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `qq_identifier` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_image_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_image_mobile` varchar(50) DEFAULT NULL AFTER `background_image`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_video_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_video_mobile` varchar(50) DEFAULT NULL AFTER `background_image_mobile`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'background_video_delay_mobile';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `background_video_delay_mobile` int(11) NOT NULL DEFAULT '0' AFTER `background_video_delay`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_users_log';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_users_log` (
                                  `id_user` int(11) unsigned DEFAULT NULL,
                                  `date_time` datetime DEFAULT NULL,
                                  `type` varchar(100) DEFAULT NULL,
                                  `params` varchar(100) DEFAULT NULL,
                                  KEY `id_user` (`id_user`),
                                  CONSTRAINT `svt_users_log_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }

//UPDATE 7.0
$result = $mysqli->query("SHOW TABLES LIKE 'svt_job_queue';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_job_queue` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `date_time` datetime DEFAULT NULL,
                                  `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                  `type` varchar(100) DEFAULT NULL,
                                  `params` text,
                                  PRIMARY KEY (`id`),
                                  KEY `id_virtualtour` (`id_virtualtour`),
                                  CONSTRAINT `svt_job_queue_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_gallery LIKE 'rotate';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_gallery ADD `rotate` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_gallery LIKE 'visible';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_gallery ADD `visible` tinyint(1) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'button_icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_products ADD `button_icon` varchar(50) DEFAULT 'fas fa-shopping-cart';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'button_text';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_products ADD `button_text` varchar(100) DEFAULT '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `protect_type` enum('none','password','lead') NOT NULL DEFAULT 'none' AFTER `password_description`;");
        $mysqli->query("UPDATE svt_virtualtours SET protect_type='password' WHERE password IS NOT NULL AND password != '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_send_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `protect_send_email` tinyint(1) NOT NULL DEFAULT '0' AFTER `protect_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `protect_email` varchar(250) DEFAULT NULL AFTER `protect_send_email`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_remember';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `protect_remember` tinyint(1) NOT NULL DEFAULT '1' AFTER `protect_email`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_remember';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `protect_remember` tinyint(1) NOT NULL DEFAULT '1' AFTER `protect_email`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'priority';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `priority` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'lp_duration';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `lp_duration` int(11) NOT NULL DEFAULT '3000' AFTER `virtual_staging`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'lp_fade';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `lp_fade` int(11) NOT NULL DEFAULT '5000' AFTER `lp_duration`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_download_slideshow';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_download_slideshow` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_export_vt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'share_providers';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `share_providers` varchar(250) NOT NULL DEFAULT 'email,whatsapp,facebook,twitter,linkedin,telegram,facebook_messenger,pinterest,reddit,line,viber,vk,qzone,wechat';");
    }
} else { echo $mysqli->error; }

//UPDATE 7.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'visible_multiview_ids';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `visible_multiview_ids` varchar(50) NOT NULL DEFAULT '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'visible_multiview_ids';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `visible_multiview_ids` varchar(50) NOT NULL DEFAULT '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_measures LIKE 'visible_multiview_ids';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_measures ADD `visible_multiview_ids` varchar(50) NOT NULL DEFAULT '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_video_projects';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_video_projects` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `date_time` datetime DEFAULT NULL,
                                   `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                                   `name` varchar(250) DEFAULT NULL,
                                   `resolution_w` int(11) DEFAULT 1920,
                                   `resolution_h` int(11) DEFAULT 1080,
                                   `fade` float DEFAULT 0.3,
                                   `fps` float DEFAULT 30,
                                   `watermark_pos` enum(\'none\',\'bottom_left\',\'top_left\',\'bottom_right\',\'top_right\',\'center\') NOT NULL DEFAULT \'none\',
                                   `watermark_logo` varchar(100) DEFAULT NULL,
                                   `watermark_opacity` float DEFAULT 1.0,
                                   `audio` varchar(100) DEFAULT NULL,
                                   PRIMARY KEY (`id`),
                                   KEY `id_virtualtour` (`id_virtualtour`),
                                   CONSTRAINT `svt_video_projects_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_video_project_slides';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_video_project_slides` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `id_video_project` bigint(20) unsigned DEFAULT NULL,
                                `type` enum(\'logo\',\'text\',\'panorama\',\'video\',\'image\') DEFAULT NULL,
                                `id_room` bigint(20) unsigned DEFAULT NULL,
                                `file` varchar(100) DEFAULT NULL,
                                `font` varchar(100) DEFAULT NULL,
                                `duration` float DEFAULT 3,
                                `params` text,
                                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                                `priority` int(11) NOT NULL DEFAULT 0,
                                PRIMARY KEY (`id`),
                                KEY `id_video_project` (`id_video_project`),
                                KEY `id_room` (`id_room`),
                                CONSTRAINT `svt_video_project_slides_ibfk_1` FOREIGN KEY (`id_video_project`) REFERENCES `svt_video_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                CONSTRAINT `svt_video_project_slides_ibfk_2` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_job_queue LIKE 'id_project';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_job_queue ADD `id_project` bigint(20) unsigned DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'video_project';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `video_project` enum('local','cloud') DEFAULT 'local' AFTER `slideshow_cloud_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'video_project_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `video_project_url` varchar(250) NOT NULL DEFAULT 'https://simplevirtualtour.it/app/tools/video_cloud.php' AFTER `video_project`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'create_video_projects';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `create_video_projects` tinyint(1) NOT NULL DEFAULT '1' AFTER `create_video360`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'video_projects';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `video_projects` tinyint(1) NOT NULL DEFAULT '1' AFTER `video360`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_features';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_features` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `feature` varchar(50) DEFAULT NULL,
                               `name` text,
                               `content` longtext,
                               PRIMARY KEY (`id`),
                               constraint svt_features_feature_uindex unique (feature)
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'loading_background_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `loading_background_color` varchar(50) DEFAULT '#343434' AFTER `hide_loading`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'loading_text_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `loading_text_color` varchar(50) DEFAULT '#ffffff' AFTER `loading_background_color`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'sidebar';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `sidebar` enum('gradient','flat') DEFAULT 'gradient' AFTER `font_backend`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'sidebar_color_1';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `sidebar_color_1` varchar(25) DEFAULT NULL AFTER `sidebar`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'sidebar_color_2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `sidebar_color_2` varchar(25) DEFAULT NULL AFTER `sidebar_color_1`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'sidebar_color_1_dark';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `sidebar_color_1_dark` varchar(25) DEFAULT NULL AFTER `sidebar_color_2`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'sidebar_color_2_dark';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `sidebar_color_2_dark` varchar(25) DEFAULT NULL AFTER `sidebar_color_1_dark`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'theme_color_dark';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `theme_color_dark` varchar(25) DEFAULT NULL AFTER `theme_color`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'dark_mode';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `dark_mode` tinyint(1) NOT NULL DEFAULT '1' AFTER `theme_color_dark`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'button_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `button_type` enum('default','custom') DEFAULT 'default';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'button_text';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `button_text` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'button_icon';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `button_icon` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_category_vt_assoc';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_category_vt_assoc` (
                         `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                         `id_category` int(11) unsigned DEFAULT NULL,
                         KEY `id_virtualtour` (`id_virtualtour`),
                         KEY `id_category` (`id_category`),
                         CONSTRAINT `svt_category_vt_assoc_ibfk_1` FOREIGN KEY (`id_category`) REFERENCES `svt_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                         CONSTRAINT `svt_category_vt_assoc_ibfk_2` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $result_c = $mysqli->query("SELECT id,id_category FROM svt_virtualtours WHERE id_category IS NOT NULL;");
        if($result_c) {
            if($result_c->num_rows>0) {
                while($row_c=$result_c->fetch_array(MYSQLI_ASSOC)) {
                    $id_vt = $row_c['id'];
                    $id_category = $row_c['id_category'];
                    $mysqli->query("INSERT INTO svt_category_vt_assoc(id_virtualtour,id_category) VALUES($id_vt,$id_category);");
                }
            }
        }
        $mysqli->query("UPDATE svt_virtualtours SET id_category=NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'from_hour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `from_hour` time DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'to_hour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `to_hour` time DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'from_hour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `from_hour` time DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'to_hour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `to_hour` time DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'song_loop';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `song_loop` tinyint(1) NOT NULL DEFAULT '1' AFTER `song_bg_volume`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'website_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `website_url` varchar(250) DEFAULT NULL AFTER `help_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'website_name';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `website_name` varchar(250) DEFAULT NULL AFTER `website_url`;");
    }
} else { echo $mysqli->error; }

//UPDATE 7.2
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'custom_currency';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_products ADD `custom_currency` varchar(25) DEFAULT '';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'mouse_zoom';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `mouse_zoom` tinyint(1) NOT NULL DEFAULT '1' AFTER `zoom_to_pointer`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_location';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_location` tinyint(1) NOT NULL DEFAULT '0' AFTER `custom3_content`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'location_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `location_content` longtext AFTER `show_location`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_comments';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_comments` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_location`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'disqus_shortname';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `disqus_shortname` varchar(100) DEFAULT NULL AFTER `show_comments`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'disqus_shortname';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `disqus_shortname` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'disqus_public_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `disqus_public_key` varchar(200) DEFAULT NULL AFTER `disqus_shortname`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'disqus_public_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `disqus_public_key` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_comments';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_comments` tinyint(1) NOT NULL DEFAULT '1' AFTER `create_video_projects`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'disqus_allow_tour';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `disqus_allow_tour` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }

//UPDATE 7.3
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'song_bg_volume';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `song_bg_volume` float NOT NULL DEFAULT '1.0' AFTER `song`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'flyin_duration';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `flyin_duration` int(11) NOT NULL DEFAULT '2000' AFTER `flyin`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'box_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `box_background` varchar(50) NOT NULL DEFAULT 'rgba(255,255,255,1.0)' AFTER `box_max_width`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'box_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `box_color` varchar(50) NOT NULL DEFAULT '#000000' AFTER `box_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'button_background';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_products ADD `button_background` varchar(50) NOT NULL DEFAULT '#000000' AFTER `button_text`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_products LIKE 'button_color';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_products ADD `button_color` varchar(50) NOT NULL DEFAULT '#ffffff' AFTER `button_background`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'font_provider';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `font_provider` enum('google','collabs') DEFAULT 'google';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_product_2checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_product_2checkout` varchar(50) DEFAULT NULL AFTER `id_plan_paypal`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_product2_2checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_product2_2checkout` varchar(50) DEFAULT NULL AFTER `id_product_2checkout`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE '2checkout_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `2checkout_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `id_product_paypal`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE '2checkout_merchant';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `2checkout_merchant` varchar(200) DEFAULT NULL AFTER `2checkout_enabled`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE '2checkout_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `2checkout_secret` varchar(200) DEFAULT NULL AFTER `2checkout_merchant`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_customer_2checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_customer_2checkout` varchar(50) DEFAULT NULL AFTER `id_customer_stripe`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'id_subscription_2checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `id_subscription_2checkout` varchar(50) DEFAULT NULL AFTER `id_subscription_paypal`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'status_subscription_2checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `status_subscription_2checkout` tinyint(1) NOT NULL DEFAULT '0' AFTER `status_subscription_paypal`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'frequency';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'month_year') === false) {
            $mysqli->query("ALTER TABLE svt_plans MODIFY COLUMN `frequency` enum('one_time','recurring','month_year') DEFAULT 'recurring';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'price2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `price2` float NOT NULL DEFAULT '0' AFTER `price`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_plan2_paypal';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_plan2_paypal` varchar(50) DEFAULT NULL AFTER `id_plan_paypal`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'id_price2_stripe';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `id_price2_stripe` varchar(50) DEFAULT NULL AFTER `id_price_stripe`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE '2checkout_live';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `2checkout_live` tinyint(1) NOT NULL DEFAULT '0' AFTER `2checkout_secret`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'days_expire_notification';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `days_expire_notification` int(11) NOT NULL DEFAULT '1';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_ai_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_ai_room` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_ar_vt`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_ai_room';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_ai_room` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_panorama_video`;");
    }
} else { echo $mysqli->error; }

//UPDATE 7.4
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'show_nadir';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `show_nadir` tinyint(1) NOT NULL DEFAULT '1' AFTER `northOffset`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_enabled` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_type` enum('aws','r2','digitalocean') DEFAULT 'aws'");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_vt_auto';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_vt_auto` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_key` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_region';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_region` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_accountid';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_accountid` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_custom_domain';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_custom_domain` varchar(50) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_secret` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'aws_s3_bucket';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `aws_s3_bucket` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'aws_s3';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `aws_s3` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE '2fa_enable';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `2fa_enable` tinyint(1) NOT NULL DEFAULT '1' AFTER `captcha_register`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE '2fa_secretkey';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `2fa_secretkey` varchar(100) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'super_admin';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `super_admin` tinyint(1) NOT NULL DEFAULT '0' AFTER `role`;");
        $mysqli->query("UPDATE svt_users SET `super_admin`=1 WHERE role='administrator';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'globe_ion_token';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `globe_ion_token` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'globe_arcgis_token';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `globe_arcgis_token` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'globe_googlemaps_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `globe_googlemaps_key` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_globes LIKE 'type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_globes ADD `type` enum('default','google') NOT NULL DEFAULT 'default' AFTER `default_view`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'ai_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `ai_key` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'pointclouds') === false) {
            $mysqli->query("ALTER TABLE svt_pois MODIFY COLUMN `type` enum('image','video','link','link_ext','html','html_sc','download','form','video360','audio','gallery','google_maps','object360','embed','object3d','lottie','product','switch_pano','pdf','callout','pointclouds') DEFAULT NULL;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'n_ai_generate_month';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `n_ai_generate_month` int(11) NOT NULL DEFAULT '-1' AFTER `n_gallery_images`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_ai_log';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query('CREATE TABLE IF NOT EXISTS `svt_ai_log` (
                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `id_user` int(11) unsigned DEFAULT NULL,
                        `date_time` datetime DEFAULT NULL,
                        `response` text,
                        PRIMARY KEY (`id`),
                        KEY `id_user` (`id_user`),
                        CONSTRAINT `svt_ai_log_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `svt_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;');
    }
} else { echo $mysqli->error; }

//UPDATE 7.4.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'multires_config';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `multires_config` longtext AFTER `multires_status`;");
        $result = $mysqli->query("SELECT id,panorama_image FROM svt_rooms WHERE multires_status=2 AND (multires_config='' OR multires_config IS NULL);");
        if($result) {
            if($result->num_rows>0) {
                while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                    $id_room = $row['id'];
                    $room_pano = str_replace('.jpg','',$row['panorama_image']);
                    $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                    if(file_exists($multires_config_file)) {
                        $multires_config = file_get_contents($multires_config_file);
                        $multires_config = str_replace("'","\'",$multires_config);
                        $mysqli->query("UPDATE svt_rooms SET multires_config='$multires_config' WHERE id=$id_room;");
                    }
                }
            }
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms_alt LIKE 'multires_config';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms_alt ADD `multires_config` longtext AFTER `multires_status`;");
        $result = $mysqli->query("SELECT id,panorama_image FROM svt_rooms_alt WHERE multires_status=2 AND (multires_config='' OR multires_config IS NULL);");
        if($result) {
            if($result->num_rows>0) {
                while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                    $id_room = $row['id'];
                    $room_pano = str_replace('.jpg','',$row['panorama_image']);
                    $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                    if(file_exists($multires_config_file)) {
                        $multires_config = file_get_contents($multires_config_file);
                        $multires_config = str_replace("'","\'",$multires_config);
                        $mysqli->query("UPDATE svt_rooms_alt SET multires_config='$multires_config' WHERE id=$id_room;");
                    }
                }
            }
        }
    }
} else { echo $mysqli->error; }
$mysqli->query("UPDATE svt_settings SET jitsi_domain='meet.simplevirtualtour.it' WHERE jitsi_domain='meet.jit.si';");

//UPDATE 7.5
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'dollhouse_glb';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `dollhouse_glb` varchar(50) DEFAULT NULL AFTER `dollhouse`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'shop_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `shop_type` enum('snipcart','woocommerce') NOT NULL DEFAULT 'snipcart' AFTER `ui_style`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'woocommerce_store_url';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `woocommerce_store_url` varchar(100) DEFAULT NULL AFTER `snipcart_currency`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'woocommerce_customer_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `woocommerce_customer_key` varchar(100) DEFAULT NULL AFTER `woocommerce_store_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'woocommerce_customer_secret';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `woocommerce_customer_secret` varchar(100) DEFAULT NULL AFTER `woocommerce_customer_key`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'mail_activate_body';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if ($type=='text') {
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_activate_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_forgot_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_plan_canceled_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_plan_changed_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_plan_expired_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_plan_expiring_body` longtext;");
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `mail_user_add_body` longtext;");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcases LIKE 'sort_settings';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcases ADD `sort_settings` text DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_showcase_list LIKE 'priority';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_showcase_list ADD `priority` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'mailchimp') === false) {
            $mysqli->query("ALTER TABLE svt_virtualtours MODIFY COLUMN `protect_type` enum('none','password','lead','mailchimp') NOT NULL DEFAULT 'none';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if ((strpos($type, 'mailchimp') === false) || (strpos($type, 'passcode') === false)) {
            $mysqli->query("ALTER TABLE svt_rooms MODIFY COLUMN `protect_type` enum('none','passcode','leads','mailchimp') NOT NULL DEFAULT 'none';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'protect_mc_form';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `protect_mc_form` longtext AFTER `protect_remember`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_mc_form';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `protect_mc_form` longtext AFTER `protect_remember`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'license2';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `license2` varchar(250) DEFAULT NULL AFTER `license`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'song_once';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_rooms ADD `song_once` tinyint(1) NOT NULL DEFAULT '0' AFTER `song_loop`;");
    }
} else { echo $mysqli->error; }

//UPDATE 7.6
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'font_provider';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'systems') === false) {
            $mysqli->query("ALTER TABLE svt_settings MODIFY COLUMN `font_provider` enum('systems','google','collabs') DEFAULT 'google';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_rooms LIKE 'protect_type';");
if($result) {
    if ($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $type = $row['Type'];
        if (strpos($type, 'leads') === false) {
            $mysqli->query("ALTER TABLE svt_rooms MODIFY COLUMN `protect_type` enum('none','passcode','leads','mailchimp') NOT NULL DEFAULT 'none';");
        }
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'terms_and_conditions';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `terms_and_conditions` longtext AFTER `enable_registration`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_presentations LIKE 'pos';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_presentations ADD `pos` text AFTER `params`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_sound_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_sound_library` (
                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                        `file` varchar(200) DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `id_virtualtour` (`id_virtualtour`),
                        CONSTRAINT `svt_sound_library_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_sound_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_sound_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_music_library`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_assign_virtualtours LIKE 'sound_library';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_assign_virtualtours ADD `sound_library` tinyint(1) NOT NULL DEFAULT '1' AFTER `music_library`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'sound';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `sound` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'sound';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `sound` varchar(200) DEFAULT NULL;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_default_sound';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_default_sound` varchar(200) DEFAULT NULL AFTER `markers_default_scale`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_default_sound';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_default_sound` varchar(200) DEFAULT NULL AFTER `pois_default_scale`;");
    }
} else { echo $mysqli->error; }

//UPDATE 7.7
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'languages_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `languages_enabled` text AFTER `language`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'woocommerce_store_cart';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `woocommerce_store_cart` varchar(50) DEFAULT 'cart/' AFTER `woocommerce_store_url`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'woocommerce_store_checkout';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `woocommerce_store_checkout` varchar(50) DEFAULT 'checkout/' AFTER `woocommerce_store_cart`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_multilanguage';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_multilanguage` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_comments`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_rooms_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_rooms_lang` (
                           `id_room` bigint(20) unsigned DEFAULT NULL,
                           `language` varchar(10) DEFAULT NULL,
                           `name` varchar(50) DEFAULT NULL,
                           `annotation_title` varchar(100) DEFAULT NULL,
                           `annotation_description` text,
                           `passcode_title` varchar(250) DEFAULT NULL,
                           `passcode_description` text,
                           `main_view_tooltip` varchar(100) DEFAULT NULL,
                           UNIQUE KEY `id_room` (`id_room`,`language`),
                           CONSTRAINT `svt_rooms_lang_ibfk_1` FOREIGN KEY (`id_room`) REFERENCES `svt_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_rooms_alt_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_rooms_alt_lang` (
                           `id_room_alt` bigint(20) unsigned NOT NULL,
                           `language` varchar(10) DEFAULT NULL,
                           `view_tooltip` varchar(100) DEFAULT NULL,
                           UNIQUE KEY `id_room_alt` (`id_room_alt`,`language`),
                           CONSTRAINT `svt_rooms_alt_lang_ibfk_1` FOREIGN KEY (`id_room_alt`) REFERENCES `svt_rooms_alt` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_virtualtours_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_virtualtours_lang` (
                      `id_virtualtour` bigint(20) unsigned DEFAULT NULL,
                      `language` varchar(10) DEFAULT NULL,
                      `name` varchar(100) DEFAULT NULL,
                      `info_box` longtext,
                      `form_content` text DEFAULT NULL,
                      `password_title` varchar(500) DEFAULT NULL,
                      `password_description` text,
                      `description` text DEFAULT NULL,
                      `meta_title` varchar(100) DEFAULT NULL,
                      `meta_description` text DEFAULT NULL,
                      UNIQUE KEY `id_virtualtour` (`id_virtualtour`,`language`),
                      CONSTRAINT `svt_virtualtours_lang_ibfk_1` FOREIGN KEY (`id_virtualtour`) REFERENCES `svt_virtualtours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_markers_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_markers_lang` (
                         `id_marker` bigint(20) unsigned DEFAULT NULL,
                         `language` varchar(10) DEFAULT NULL,
                         `tooltip_text` text,
                         UNIQUE KEY `id_marker` (`id_marker`,`language`),
                         CONSTRAINT `svt_markers_lang_ibfk_1` FOREIGN KEY (`id_marker`) REFERENCES `svt_markers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_pois_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_pois_lang` (
                      `id_poi` bigint(20) unsigned DEFAULT NULL,
                      `language` varchar(10) DEFAULT NULL,
                      `embed_content` longtext DEFAULT NULL,
                      `label` varchar(100) DEFAULT NULL,
                      `tooltip_text` text,
                      `title` varchar(100) DEFAULT NULL,
                      `description` text,
                      `content` longtext,
                      `params` text,
                      UNIQUE KEY `id_poi` (`id_poi`,`language`),
                      CONSTRAINT `svt_pois_lang_ibfk_1` FOREIGN KEY (`id_poi`) REFERENCES `svt_pois` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_maps_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_maps_lang` (
                          `id_map` bigint(20) unsigned NOT NULL,
                          `language` varchar(10) DEFAULT NULL,
                          `name` varchar(200) DEFAULT NULL,
                          UNIQUE KEY `id_map` (`id_map`,`language`),
                          CONSTRAINT `svt_maps_lang_ibfk_1` FOREIGN KEY (`id_map`) REFERENCES `svt_maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_gallery_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_gallery_lang` (
                         `id_gallery` bigint(20) unsigned DEFAULT NULL,
                         `language` varchar(10) DEFAULT NULL,
                         `title` varchar(100) DEFAULT NULL,
                         `description` text,
                         UNIQUE KEY `id_gallery` (`id_gallery`,`language`),
                         CONSTRAINT `svt_gallery_lang_ibfk_1` FOREIGN KEY (`id_gallery`) REFERENCES `svt_gallery` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_presentations_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_presentations_lang` (
                           `id_presentation` bigint(20) unsigned DEFAULT NULL,
                           `language` varchar(10) DEFAULT NULL,
                           `params` text,
                           UNIQUE KEY `id_presentation` (`id_presentation`,`language`),
                           CONSTRAINT `svt_presentations_lang_ibfk_1` FOREIGN KEY (`id_presentation`) REFERENCES `svt_presentations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_language';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_language` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_vt_title`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW TABLES LIKE 'svt_products_lang';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("CREATE TABLE IF NOT EXISTS `svt_products_lang` (
                          `id_product` bigint(20) unsigned DEFAULT NULL,
                          `language` varchar(10) DEFAULT NULL,
                          `name` varchar(100) DEFAULT NULL,
                          `description` text,
                          `button_text` varchar(100) DEFAULT '',
                          UNIQUE KEY `id_product` (`id_product`,`language`),
                          CONSTRAINT `svt_products_lang_ibfk_1` FOREIGN KEY (`id_product`) REFERENCES `svt_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'maintenance_backend';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `maintenance_backend` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'maintenance_viewer';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `maintenance_viewer` tinyint(1) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'maintenance_ip';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `maintenance_ip` text;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom4_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom4_content` longtext AFTER `custom3_content`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'custom5_content';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `custom5_content` longtext AFTER `custom4_content`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_custom4';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_custom4` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_custom3`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_custom5';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_custom5` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_custom4`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'show_poweredby';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `show_poweredby` tinyint(1) NOT NULL DEFAULT '1' AFTER `show_logo`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'poweredby_type';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `poweredby_type` enum('text','image') NOT NULL DEFAULT 'image' AFTER `show_poweredby`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'poweredby_image';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `poweredby_image` varchar(50) DEFAULT NULL AFTER `poweredby_type`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'poweredby_text';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `poweredby_text` varchar(200) DEFAULT NULL AFTER `poweredby_image`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'poweredby_link';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `poweredby_link` varchar(200) DEFAULT NULL AFTER `poweredby_text`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_poweredby';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_poweredby` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_multilanguage`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'privacy_policy';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `privacy_policy` longtext AFTER `terms_and_conditions`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_pois LIKE 'exclude_from_apply_all';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_pois ADD `exclude_from_apply_all` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_markers LIKE 'exclude_from_apply_all';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_markers ADD `exclude_from_apply_all` int(11) NOT NULL DEFAULT '0';");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'markers_animation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `markers_animation` varchar(50) NOT NULL DEFAULT 'none' AFTER `markers_default_sound`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'pois_animation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours ADD `pois_animation` varchar(50) NOT NULL DEFAULT 'none' AFTER `pois_default_sound`;");
    }
} else { echo $mysqli->error; }

//UPDATE 7.7.1
$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours_lang LIKE 'list_alt';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_virtualtours_lang ADD `list_alt` text;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'deepl_api_key';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `deepl_api_key` varchar(200) DEFAULT NULL AFTER `ai_key`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'enable_deepl';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `enable_deepl` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_ai_room`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_settings LIKE 'languages_viewer_enabled';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_settings ADD `languages_viewer_enabled` text AFTER `languages_enabled`;");
    }
} else { echo $mysqli->error; }
$result = $mysqli->query("SHOW COLUMNS FROM svt_plans LIKE 'enable_auto_translation';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_plans ADD `enable_auto_translation` tinyint(1) NOT NULL DEFAULT '1' AFTER `enable_multilanguage`;");
    }
} else { echo $mysqli->error; }