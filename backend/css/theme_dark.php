<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
header('Content-Type: text/css');
session_start();
$color = $_SESSION['theme_color_dark'];
if(empty($color)) {
    $color = $_SESSION['theme_color'];
    if(empty($color)) {
        exit;
    }
}
$sidebar_color_1 = $_SESSION['sidebar_color_1_dark'];
$sidebar_color_2 = $_SESSION['sidebar_color_2_dark'];
$color1 = $color;
$color2 = adjustBrightness($color1,'-0.1');
$color3 = adjustBrightness($color1,'-0.2');
$color4 = adjustBrightness($color1,'-0.3');

if(empty($sidebar_color_1)) {
    $sidebar_color_1 = $_SESSION['sidebar_color_1'];
    if(empty($sidebar_color_1)) {
        $sidebar_color_1 = $color;
    }
}
if(empty($sidebar_color_2)) {
    $sidebar_color_2 = $_SESSION['sidebar_color_2'];
    if(empty($sidebar_color_2)) {
        $sidebar_color_2 = $color4;
    }
}

function adjustBrightness($hexCode, $adjustPercent) {
    $hexCode = ltrim($hexCode, '#');
    if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    }
    $hexCode = array_map('hexdec', str_split($hexCode, 2));
    foreach ($hexCode as & $color) {
        $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
        $adjustAmount = ceil($adjustableLimit * $adjustPercent);
        $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }
    return '#' . implode($hexCode);
}
ob_end_clean();
?>

:root {
    --dark-color1: <?php echo $color1; ?>;
    --dark-color2: <?php echo $color2; ?>;
    --dark-color3: <?php echo $color3; ?>;
    --dark-color4: <?php echo $color4; ?>;
    --sidebar-dark-color-1: <?php echo $sidebar_color_1; ?>;
    --sidebar-dark-color-2: <?php echo $sidebar_color_2; ?>;
}
.dark_mode #div_image_logo_s, .dark_mode #div_image_logo {
    background-color: var(--dark-color1) !important;
}
.dark_mode .div_vt:hover, .dark_mode .div_room:hover, .dark_mode .div_map:hover {
    border-left: .25rem solid var(--dark-color1) !important;
}
.dark_mode .div_vt:hover .vt_content div:first-of-type, .dark_mode .div_room:hover .room_content div:first-of-type, .dark_mode .div_map:hover .map_content div:first-of-type {
    border: 2px solid var(--dark-color1) !important;
}
.dark_mode .bg-gradient-primary {
    background: linear-gradient(150deg, var(--sidebar-dark-color-1), var(--sidebar-dark-color-2)) !important;
}
.dark_mode .bg-flat-primary {
    background: var(--sidebar-dark-color-1) !important;
}
.dark_mode .nav-pills .nav-link.active, .dark_mode .nav-pills .show>.nav-link {
    background-color: var(--dark-color1) !important;
}
.dark_mode #div_poi_select_style .dropdown-item:focus, .dark_mode #div_poi_select_style .dropdown-item:hover {
    color: white !important;
    background-color: var(--dark-color1) !important;
}
.dark_mode .text-primary {
    color: var(--dark-color1) !important;
}
.dark_mode .badge-primary-soft {
    background-color: var(--dark-color3) !important;
    color: white !important;
}
.dark_mode .bg-primary {
    background-color: var(--dark-color2) !important;
}
.dark_mode a.bg-primary:focus, .dark_mode a.bg-primary:hover, .dark_mode button.bg-primary:focus, .dark_mode button.bg-primary:hover {
    background-color: var(--dark-color1) !important;
}
.dark_mode .bg-primary-soft {
    background-color: var(--dark-color4) !important;
    color: white !important;
}
.dark_mode .badge-primary {
    background-color: var(--dark-color1) !important;
}
.dark_mode .btn-primary {
    color: #ffffff;
    background-color: var(--dark-color1);
    border-color: var(--dark-color1);
}
.dark_mode .btn-primary:hover {
    background-color: var(--dark-color4);
    border-color: var(--dark-color4);
}
.dark_mode .btn-outline-primary {
    color: var(--dark-color1);
    background-color: #ffffff;
    border-color: var(--dark-color1);
}
.dark_mode .btn-outline-primary:hover, .dark_mode .btn-outline-primary:active {
    color: #ffffff;
    background-color: var(--dark-color1) !important;
}
.dark_mode #div_poi_select_style button:hover {
    background-color: var(--dark-color1);
    border-color: var(--dark-color1);
}
.dark_mode .btn-primary.disabled, .dark_mode .btn-primary:disabled {
    background-color: var(--dark-color4);
    border-color: var(--dark-color4);
}
.dark_mode .btn-primary:not(:disabled):not(.disabled).active, .dark_mode .btn-primary:not(:disabled):not(.disabled):active, .dark_mode .show>.btn-primary.dropdown-toggle {
    background-color: var(--dark-color4);
    border-color: var(--dark-color4);
}
.dark_mode .sidebar .nav-item .collapse .collapse-inner .collapse-item.active, .dark_mode .sidebar .nav-item .collapsing .collapse-inner .collapse-item.active {
    color: var(--dark-color1);
}
.dark_mode a {
    color: var(--dark-color1);
}
.dark_mode a:hover {
    color: var(--dark-color3);
}
@supports (-webkit-appearance: none) or (-moz-appearance: none) {
    .dark_mode input[type='checkbox'],
    .dark_mode input[type='radio'] {
        --active: var(--dark-color1) !important;
        --active-inner: #fff;
        --focus: 2px rgba(0, 0, 0, .3) !important;
        --border: var(--dark-color3) !important;
        --border-hover: var(--dark-color3) !important;
        --background: #fff;
        --disabled: #F6F8FF;
        --disabled-inner: #E1E6F9;
        border: 1px solid var(--bc, var(--border));
        background: var(--b, var(--background));
    }
}
.dark_mode input[type='range'] {
    -webkit-appearance: none;
    background-color: #ddd;
    height: 15px;
    overflow: hidden;
    width: 100%;
}
.dark_mode input[type='range']::-webkit-slider-runnable-track {
    -webkit-appearance: none;
    height: 15px;
}
.dark_mode input[type='range']::-webkit-slider-thumb {
    -webkit-appearance: none;
    background: var(--dark-color1);
    border-radius: 50%;
    box-shadow: -3010px 0 0 3000px var(--dark-color4);
    cursor: pointer;
    height: 15px;
    width: 15px;
    border: 0;
}
.dark_mode input[type='range']::-moz-range-thumb {
    background: var(--dark-color1);
    border-radius: 50%;
    box-shadow: -3010px 0 0 3000px var(--dark-color4);
    cursor: pointer;
    height: 15px;
    width: 15px;
    border: 0;
}
.dark_mode input[type="range"]::-moz-range-track {
    background-color: #ddd;
}
.dark_mode input[type="range"]::-moz-range-progress {
    background-color: var(--dark-color4);
    height: 15px
}
.dark_mode input[type="range"]::-ms-fill-upper {
    background-color: #ddd;
}
.dark_mode input[type="range"]::-ms-fill-lower {
    background-color: var(--dark-color4);
}
.dark_mode .input-highlight {
    border-color: var(--dark-color4) !important;
    box-shadow: inset 0 1px 1px var(--dark-color4), 0 0 8px var(--dark-color4) !important;
}
.dark_mode .form-control:focus {
    border-color: var(--dark-color4) !important;
    box-shadow: inset 0 1px 1px var(--dark-color4), 0 0 8px var(--dark-color4) !important;
}
.dark_mode .slick-prev:before {
    color: var(--dark-color1);
}
.dark_mode .slick-next:before {
    color: var(--dark-color1);
}
.dark_mode .dropdown-item.active, .dark_mode .dropdown-item:active {
    background-color: var(--dark-color1) !important;
}
.dark_mode .rooms_slider .room_quick_btn {
    background-color: var(--dark-color1) !important;
}
.dark_mode .selected_room {
    color: var(--dark-color1) !important;
}
.dark_mode .selected_room .room_image {
    border: 2px solid var(--dark-color1) !important;
}
.dark_mode .selected_room .room_quick_btn {
    border: 2px solid var(--dark-color1) !important;
}
.dark_mode .rooms_slider .room_quick_add:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode .rooms_slider .room_add:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode .highlight {
    background-color: var(--dark-color3) !important;
}
.dark_mode #users_table tbody tr.even:hover, .dark_mode #users_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #plans_table tbody tr.even:hover, .dark_mode #plans_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #showcases_table tbody tr.even:hover, .dark_mode #showcases_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #globes_table tbody tr.even:hover, .dark_mode #globes_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #advertisements_table tbody tr.even:hover, .dark_mode #advertisements_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #assign_vt_table tbody tr.even:hover, .dark_mode #assign_vt_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #assign_editors_table tbody tr.even:hover, .dark_mode #assign_editors_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #products_table tbody tr.even:hover, .dark_mode #products_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode #videos_table tbody tr.even:hover, .dark_mode #videos_table tbody tr.odd:hover {
    background-color: var(--dark-color3) !important;
}
.dark_mode .page-item.active .page-link {
    background-color: var(--dark-color1) !important;
    border-color: var(--dark-color3) !important;
}
.dark_mode .page-link {
    color: var(--dark-color3);
}
.dark_mode .page-link:hover {
    color: var(--dark-color1);
}
.dark_mode .ui_title_box {
    background-color: var(--dark-color1);
}
.dark_mode .poi_edit_label, .measure_edit_label, .marker_edit_label {
    background-color: var(--dark-color1);
}
.dark_mode .nav-tabs .nav-link.active {
    background-color: var(--dark-color1);
}
.pace .pace-progress {
    background: var(--dark-color1);
}
.list-group-item.active {
    color: #fff;
    background-color: var(--dark-color2) !important;
    border-color: var(--dark-color3);
}

@keyframes gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

