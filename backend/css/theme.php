<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
header('Content-Type: text/css');
session_start();
$color = $_SESSION['theme_color'];
if(empty($color)) {
    exit;
}
$sidebar_color_1 = $_SESSION['sidebar_color_1'];
$sidebar_color_2 = $_SESSION['sidebar_color_2'];
$color1 = $color;
$color2 = adjustBrightness($color1,'0.1');
$color3 = adjustBrightness($color1,'0.2');
$color4 = adjustBrightness($color1,'0.3');

if(empty($sidebar_color_1)) {
    $sidebar_color_1 = $color;
}
if(empty($sidebar_color_2)) {
    $sidebar_color_2 = $color4;
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
    --color1: <?php echo $color1; ?>;
    --color2: <?php echo $color2; ?>;
    --color3: <?php echo $color3; ?>;
    --color4: <?php echo $color4; ?>;
    --sidebar-color-1: <?php echo $sidebar_color_1; ?>;
    --sidebar-color-2: <?php echo $sidebar_color_2; ?>;
}
#div_image_logo_s, #div_image_logo {
    background-color: var(--color1) !important;
}
.div_vt:hover, .div_room:hover, .div_map:hover {
    border-left: .25rem solid var(--color1) !important;
}
.div_vt:hover .vt_content div:first-of-type, .div_room:hover .room_content div:first-of-type, .div_map:hover .map_content div:first-of-type {
    border: 2px solid var(--color1) !important;
}
.bg-gradient-primary {
    background: linear-gradient(150deg, var(--sidebar-color-1), var(--sidebar-color-2)) !important;
}
.bg-flat-primary {
    background: var(--sidebar-color-1) !important;
}
.nav-pills .nav-link.active, .nav-pills .show>.nav-link {
    background-color: var(--color1) !important;
}
#div_poi_select_style .dropdown-item:focus, #div_poi_select_style .dropdown-item:hover {
    color: white !important;
    background-color: var(--color1) !important;
}
.text-primary {
    color: var(--color1) !important;
}
.badge-primary-soft {
    background-color: var(--color3) !important;
    color: white !important;
}
.bg-primary {
    background-color: var(--color2) !important;
}
a.bg-primary:focus, a.bg-primary:hover, button.bg-primary:focus, button.bg-primary:hover {
    background-color: var(--color1) !important;
}
.bg-primary-soft {
    background-color: var(--color4) !important;
    color: white !important;
}
.badge-primary {
    background-color: var(--color1) !important;
}
.btn-primary {
    color: #ffffff;
    background-color: var(--color1);
    border-color: var(--color1);
}
.btn-primary:hover {
    background-color: var(--color4);
    border-color: var(--color4);
}
.btn-outline-primary {
    color: var(--color1);
    background-color: #ffffff;
    border-color: var(--color1);
}
.btn-outline-primary:hover, .btn-outline-primary:active {
    color: #ffffff;
    background-color: var(--color1) !important;
}
#div_poi_select_style button:hover {
    background-color: var(--color1);
    border-color: var(--color1);
}
.btn-primary.disabled, .btn-primary:disabled {
    background-color: var(--color4);
    border-color: var(--color4);
}
.btn-primary:not(:disabled):not(.disabled).active, .btn-primary:not(:disabled):not(.disabled):active, .show>.btn-primary.dropdown-toggle {
    background-color: var(--color4);
    border-color: var(--color4);
}
.sidebar .nav-item .collapse .collapse-inner .collapse-item.active, .sidebar .nav-item .collapsing .collapse-inner .collapse-item.active {
    color: var(--color1);
}
a {
    color: var(--color1);
}
a:hover {
    color: var(--color3);
}
@supports (-webkit-appearance: none) or (-moz-appearance: none) {
    input[type='checkbox'],
    input[type='radio'] {
        --active: var(--color1) !important;
        --active-inner: #fff;
        --focus: 2px rgba(0, 0, 0, .3) !important;
        --border: var(--color3) !important;
        --border-hover: var(--color3) !important;
        --background: #fff;
        --disabled: #F6F8FF;
        --disabled-inner: #E1E6F9;
        -webkit-appearance: none;
        -moz-appearance: none;
        height: 21px;
        outline: none;
        display: inline-block;
        vertical-align: top;
        position: relative;
        margin: 0;
        cursor: pointer;
        border: 1px solid var(--bc, var(--border));
        background: var(--b, var(--background));
        -webkit-transition: background .3s, border-color .3s, box-shadow .2s;
        transition: background .3s, border-color .3s, box-shadow .2s;
    }
    input[type='checkbox']:after,
    input[type='radio']:after {
        content: '';
        display: block;
        left: 0;
        top: 0;
        position: absolute;
        -webkit-transition: opacity var(--d-o, 0.2s), -webkit-transform var(--d-t, 0.3s) var(--d-t-e, ease);
        transition: opacity var(--d-o, 0.2s), -webkit-transform var(--d-t, 0.3s) var(--d-t-e, ease);
        transition: transform var(--d-t, 0.3s) var(--d-t-e, ease), opacity var(--d-o, 0.2s);
        transition: transform var(--d-t, 0.3s) var(--d-t-e, ease), opacity var(--d-o, 0.2s), -webkit-transform var(--d-t, 0.3s) var(--d-t-e, ease);
    }
    input[type='checkbox']:checked,
    input[type='radio']:checked {
        --b: var(--active);
        --bc: var(--active);
        --d-o: .3s;
        --d-t: .6s;
        --d-t-e: cubic-bezier(.2, .85, .32, 1.2);
    }
    input[type='checkbox']:disabled,
    input[type='radio']:disabled {
        --b: var(--disabled);
        cursor: not-allowed;
        opacity: .9;
    }
    input[type='checkbox']:disabled:checked,
    input[type='radio']:disabled:checked {
        --b: var(--disabled-inner);
        --bc: var(--border);
    }
    input[type='checkbox']:disabled + label,
    input[type='radio']:disabled + label {
        cursor: not-allowed;
    }
    input[type='checkbox']:hover:not(:checked):not(:disabled),
    input[type='radio']:hover:not(:checked):not(:disabled) {
        --bc: var(--border-hover);
    }
    input[type='checkbox']:focus,
    input[type='radio']:focus {
        box-shadow: 0 0 0 var(--focus);
    }
    input[type='checkbox']:not(.switch),
    input[type='radio']:not(.switch) {
        width: 21px;
    }
    input[type='checkbox']:not(.switch):after,
    input[type='radio']:not(.switch):after {
        opacity: var(--o, 0);
    }
    input[type='checkbox']:not(.switch):checked,
    input[type='radio']:not(.switch):checked {
        --o: 1;
    }
    input[type='checkbox'] + label,
    input[type='radio'] + label {
        font-size: 14px;
        line-height: 21px;
        display: inline-block;
        vertical-align: top;
        cursor: pointer;
        margin-left: 4px;
    }

    input[type='checkbox']:not(.switch) {
        border-radius: 7px;
    }
    input[type='checkbox']:not(.switch):after {
        width: 5px;
        height: 9px;
        border: 2px solid var(--active-inner);
        border-top: 0;
        border-left: 0;
        left: 7px;
        top: 4px;
        -webkit-transform: rotate(var(--r, 20deg));
        transform: rotate(var(--r, 20deg));
    }
    input[type='checkbox']:not(.switch):checked {
        --r: 43deg;
    }
    input[type='checkbox'].switch {
        width: 38px;
        border-radius: 11px;
    }
    input[type='checkbox'].switch:after {
        left: 2px;
        top: 2px;
        border-radius: 50%;
        width: 15px;
        height: 15px;
        background: var(--ab, var(--border));
        -webkit-transform: translateX(var(--x, 0));
        transform: translateX(var(--x, 0));
    }
    input[type='checkbox'].switch:checked {
        --ab: var(--active-inner);
        --x: 17px;
    }
    input[type='checkbox'].switch:disabled:not(:checked):after {
        opacity: .6;
    }

    input[type='radio'] {
        border-radius: 50%;
    }
    input[type='radio']:after {
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: var(--active-inner);
        opacity: 0;
        -webkit-transform: scale(var(--s, 0.7));
        transform: scale(var(--s, 0.7));
    }
    input[type='radio']:checked {
        --s: .5;
    }
}

input[type='range'] {
    -webkit-appearance: none;
    background-color: #ddd;
    height: 15px;
    overflow: hidden;
    width: 100%;
}
input[type='range']::-webkit-slider-runnable-track {
    -webkit-appearance: none;
    height: 15px;
}
input[type='range']::-webkit-slider-thumb {
    -webkit-appearance: none;
    background: var(--color1);
    border-radius: 50%;
    box-shadow: -3010px 0 0 3000px var(--color4);
    cursor: pointer;
    height: 15px;
    width: 15px;
    border: 0;
}
input[type='range']::-moz-range-thumb {
    background: var(--color1);
    border-radius: 50%;
    box-shadow: -3010px 0 0 3000px var(--color4);
    cursor: pointer;
    height: 15px;
    width: 15px;
    border: 0;
}
input[type="range"]::-moz-range-track {
    background-color: #ddd;
}
input[type="range"]::-moz-range-progress {
    background-color: var(--color4);
    height: 15px
}
input[type="range"]::-ms-fill-upper {
    background-color: #ddd;
}
input[type="range"]::-ms-fill-lower {
    background-color: var(--color4);
}
.input-highlight {
    border-color: var(--color4) !important;
    box-shadow: inset 0 1px 1px var(--color4), 0 0 8px var(--color4) !important;
}
.form-control:focus {
    border-color: var(--color4) !important;
    box-shadow: inset 0 1px 1px var(--color4), 0 0 8px var(--color4) !important;
}
.slick-prev:before {
    color: var(--color1);
}
.slick-next:before {
    color: var(--color1);
}
.dropdown-item.active, .dropdown-item:active {
    background-color: var(--color1);
}
.rooms_slider .room_quick_btn {
    background-color: var(--color1) !important;
}
.selected_room {
    color: var(--color1) !important;
}
.selected_room .room_image {
    border: 2px solid var(--color1) !important;
}
.selected_room .room_quick_btn {
    border: 2px solid var(--color1) !important;
}
.rooms_slider .room_quick_add:hover {
    background-color: var(--color3) !important;
}
.rooms_slider .room_add:hover {
    background-color: var(--color3) !important;
}
.highlight {
    background-color: var(--color3) !important;
}
#users_table tbody tr.even:hover, #users_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#plans_table tbody tr.even:hover, #plans_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#showcases_table tbody tr.even:hover, #showcases_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#globes_table tbody tr.even:hover, #globes_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#advertisements_table tbody tr.even:hover, #advertisements_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#assign_vt_table tbody tr.even:hover, #assign_vt_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#assign_editors_table tbody tr.even:hover, #assign_editors_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#products_table tbody tr.even:hover, #products_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
#videos_table tbody tr.even:hover, #videos_table tbody tr.odd:hover {
    background-color: var(--color3) !important;
}
.page-item.active .page-link {
    background-color: var(--color1);
    border-color: var(--color3);
}
.page-link {
    color: var(--color3);
}
.page-link:hover {
    color: var(--color1);
}
.ui_title_box {
    background-color: var(--color1);
}
.poi_edit_label, .measure_edit_label, .marker_edit_label {
    background-color: var(--color1);
}
.pace .pace-progress {
    background: var(--color1);
}
.list-group-item.active {
    color: #fff;
    background-color: var(--color1);
    border-color: var(--color2);
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