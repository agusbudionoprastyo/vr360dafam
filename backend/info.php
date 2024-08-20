<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = get_plan_permission($id_user)['enable_info_box'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $info_box = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['info_box']==0) {
            $info_box = false;
        }
    }
    $show_in_ui = $virtual_tour['show_info'];
} else {
    $info_box = false;
}
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
if(count($array_languages)>1) {
    $show_language = true;
    $col = 4;
} else {
    $show_language = false;
    $col = 6;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$info_box): ?>
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
            <?php echo _("You cannot create Info Box on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Info Box!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row mb-2">
    <?php if($show_language) : ?>
    <div class="col-md-4">
        <label class="mt-2"><?php echo _("Language"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'info_box'); ?>
    </div>
    <?php endif; ?>
    <div class="col-md-<?php echo $col; ?>">
        <button style="pointer-events:none;" id="btn_info_editor" onclick="switch_info_mode('editor');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> btn-primary"><?php echo _("Editor"); ?></button>
    </div>
    <div class="col-md-<?php echo $col; ?>">
        <button id="btn_info_html" onclick="switch_info_mode('html');" class="btn btn-block <?php echo ($demo) ? 'disabled' : ''; ?> btn-outline-primary"><?php echo _("HTML"); ?></button>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <iframe id="iframe_info_editor" frameborder="none" style="width: 100%;" src="vendor/keditor/info_editor.php?v=2&id_vt=<?php echo $id_virtualtour_sel; ?>"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.active_lang = '';
        window.info_mode = 'editor';
        $(document).ready(function () {
            $('.help_t').tooltip();
            var container_h = $('#content-wrapper').height() - 220;
            $('.card-body iframe').attr('height',container_h+'px');
        });
        $(window).resize(function () {
            var container_h = $('#content-wrapper').height() - 220;
            $('.card-body iframe').attr('height',container_h+'px');
        });
    })(jQuery); // End of use strict

    window.switch_info_mode = function(mode) {
        window.info_mode = mode;
        switch(mode) {
            case 'editor':
                $('.lang_input_switcher').removeClass('disabled');
                $('#btn_info_editor').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btn_info_html').addClass('btn-outline-primary').removeClass('btn-primary');
                $('#btn_info_editor').css('pointer-events','none');
                $('#btn_info_html').css('pointer-events','initial');
                if(window.active_lang=='') {
                    $('#iframe_info_editor').contents().find("#info_editor").show();
                    $('#iframe_info_editor').contents().find("#info_editor_html").hide();
                } else {
                    $('#iframe_info_editor').contents().find("#info_editor_"+window.active_lang).show();
                    $('#iframe_info_editor').contents().find("#info_editor_html_"+window.active_lang).hide();
                }
                document.getElementById('iframe_info_editor').contentWindow.set_html_to_editor(window.active_lang);
                break;
            case 'html':
                $('.lang_input_switcher').addClass('disabled');
                $('#btn_info_editor').addClass('btn-outline-primary').removeClass('btn-primary');
                $('#btn_info_html').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btn_info_html').css('pointer-events','none');
                $('#btn_info_editor').css('pointer-events','initial');
                if(window.active_lang=='') {
                    $('#iframe_info_editor').contents().find("#info_editor").hide();
                    $('#iframe_info_editor').contents().find("#info_editor_html").show();
                    $('#iframe_info_editor').contents().find("#info_editor_html").css('opacity',1);
                } else {
                    $('#iframe_info_editor').contents().find("#info_editor_"+window.active_lang).hide();
                    $('#iframe_info_editor').contents().find("#info_editor_html_"+window.active_lang).show();
                    $('#iframe_info_editor').contents().find("#info_editor_html_"+window.active_lang).css('opacity',1);
                }
                document.getElementById('iframe_info_editor').contentWindow.get_html_from_editor(window.active_lang);
                break;
        }
    }
</script>