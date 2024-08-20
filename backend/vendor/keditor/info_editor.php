<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once('../../functions.php');
$id_virtualtour = $_GET['id_vt'];
$virtual_tour = get_virtual_tour($id_virtualtour,$_SESSION['id_user']);
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
$array_input_lang = array();
$query_lang = "SELECT language,info_box FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour";
$result_lang = $mysqli->query($query_lang);
if($result_lang) {
    if ($result_lang->num_rows > 0) {
        while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
            $language = $row_lang['language'];
            unset($row_lang['id_virtualtour']);
            unset($row_lang['language']);
            $array_input_lang[$language]=$row_lang['info_box'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" type="text/css" href="plugins/bootstrap-3.4.1/css/bootstrap.min.css" data-type="keditor-style" />
        <link rel="stylesheet" type="text/css" href="plugins/font-awesome-4.7.0/css/font-awesome.min.css" data-type="keditor-style" />
        <link rel="stylesheet" type="text/css" href="css/keditor.css" data-type="keditor-style" />
        <link rel="stylesheet" type="text/css" href="css/keditor-components.css" data-type="keditor-style" />
        <link rel="stylesheet" type="text/css" href="css/editor.css?v=3" />
    </head>
    <style>
        .keditor-snippet-component[data-type='component-vt'] {
            display: none !important;
        }
        #info_editor_html, .info_editor_html_lang {
            width: 100%;
            height: 100%;
            text-align: left;
            padding: 5px;
            overflow-y: scroll;
            opacity: 0;
        }
    </style>
    <body style="overflow: hidden">
        <div id="info_loading" class="row">
            <div class="col-md-12">
                <i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Loading info content ..."); ?>
            </div>
        </div>
        <div id="info_saving" class="row" style="display: none">
            <div class="col-md-12">
                <i class="fas fa-spin fa-circle-notch" aria-hidden="true"></i> <?php echo _("Saving info content ... Do not close this window!"); ?>
            </div>
        </div>
        <div id="info_editor" style="display: none" data-keditor="html">
            <div id="content-area">
                <?php echo $virtual_tour['info_box']; ?>
            </div>
        </div>
        <?php foreach ($array_languages as $lang) {
            if($lang!=$default_language) : ?>
            <div class="info_editor_lang" id="info_editor_<?php echo $lang; ?>" style="display: none" data-keditor="html">
                <div data-lang="<?php echo $lang; ?>" class="content-area-lang">
                    <?php echo $array_input_lang[$lang]; ?>
                </div>
            </div>
        <?php endif;
        } ?>
        <div id="info_editor_html">
            <?php echo htmlspecialchars($virtual_tour['info_box']); ?>
        </div>
        <?php foreach ($array_languages as $lang) {
            if($lang!=$default_language) : ?>
                <div class="info_editor_html_lang" data-lang="<?php echo $lang; ?>" style="display: none" id="info_editor_html_<?php echo $lang; ?>">
                    <?php echo htmlspecialchars($array_input_lang[$lang]); ?>
                </div>
            <?php endif;
        } ?>
        <script type="text/javascript" src="plugins/jquery-1.11.3/jquery-1.11.3.min.js"></script>
        <script type="text/javascript" src="plugins/bootstrap-3.4.1/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="plugins/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
        <script type="text/javascript" src="plugins/ckeditor-4.11.4/ckeditor.js"></script>
        <script type="text/javascript" src="plugins/formBuilder-2.5.3/form-builder.min.js"></script>
        <script type="text/javascript" src="plugins/formBuilder-2.5.3/form-render.min.js"></script>
        <script type="text/javascript" src="js/keditor.js"></script>
        <script type="text/javascript" src="js/keditor-components.js"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/ace.js?v=3" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/mode-css.js?v=3" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/mode-javascript.js?v=3" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/mode-html.js?v=3" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/ext-language_tools.js?v=3" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/ace-editor/theme-one_dark.min.js?v=5" charset="utf-8"></script>
        <script type="text/javascript" src="../../vendor/beautify/beautify.min.js"></script>
        <script type="text/javascript" src="../../vendor/beautify/beautify-css.min.js"></script>
        <script type="text/javascript" src="../../vendor/beautify/beautify-html.min.js"></script>
        <script type="text/javascript" src="../../js/function.js?v=<?php echo time(); ?>"></script>
        <script type="text/javascript" data-keditor="script">
            window.wizard_step = -1;
            $(function () {
                var id_virtualtour = '<?php echo $id_virtualtour; ?>';
                $('#content-area').keditor({
                    onSave: function () {
                        $('#info_editor').fadeOut(function () {
                            $('#info_saving').fadeIn(function () {
                                var html = $('#content-area').keditor('getContent');
                                save_info(id_virtualtour,html,'');
                            },0);
                        },0);
                    },
                    onReady: function() {
                        $('#info_loading').hide();
                        $('#info_editor').show();
                    },
                    containerSettingEnabled: true,
                    containerSettingInitFunction: function (form, keditor) {
                        form.append(
                            '<div class="form-horizontal">' +
                            '   <div class="form-group">' +
                            '       <div class="col-sm-12">' +
                            '           <label>Background color</label>' +
                            '           <input type="text" class="form-control txt-bg-color" />' +
                            '       </div>' +
                            '   </div>' +
                            '</div>'
                        );
                        form.find('.txt-bg-color').on('change', function () {
                            var container = keditor.getSettingContainer();
                            var row = container.find('.row');
                            if (container.hasClass('keditor-sub-container')) {
                                // Do nothing
                            } else {
                                row = row.filter(function () {
                                    return $(this).parents('.keditor-container').length === 1;
                                });
                            }
                            row.css('background-color', this.value);
                        });
                    },
                    containerSettingShowFunction: function (form, container, keditor) {
                        var row = container.find('.row');
                        var backgroundColor = row.prop('style').backgroundColor || '';
                        form.find('.txt-bg-color').val(backgroundColor);
                    },
                    containerSettingHideFunction: function (form, keditor) {
                        form.find('.txt-bg-color').val('');
                    }
                });
                $('.content-area-lang').each(function() {
                    var lang = $(this).attr('data-lang');
                    $('.content-area-lang[data-lang="'+lang+'"]').keditor({
                        onSave: function () {
                            $('#info_editor_'+lang).fadeOut(function () {
                                $('#info_saving').fadeIn(function () {
                                    var html = $('.content-area-lang[data-lang="'+lang+'"]').keditor('getContent');
                                    save_info(id_virtualtour,html,lang);
                                },0);
                            },0);
                        },
                        onReady: function() {

                        },
                        containerSettingEnabled: true,
                        containerSettingInitFunction: function (form, keditor) {
                            form.append(
                                '<div class="form-horizontal">' +
                                '   <div class="form-group">' +
                                '       <div class="col-sm-12">' +
                                '           <label>Background color</label>' +
                                '           <input type="text" class="form-control txt-bg-color" />' +
                                '       </div>' +
                                '   </div>' +
                                '</div>'
                            );
                            form.find('.txt-bg-color').on('change', function () {
                                var container = keditor.getSettingContainer();
                                var row = container.find('.row');
                                if (container.hasClass('keditor-sub-container')) {
                                    // Do nothing
                                } else {
                                    row = row.filter(function () {
                                        return $(this).parents('.keditor-container').length === 1;
                                    });
                                }
                                row.css('background-color', this.value);
                            });
                        },
                        containerSettingShowFunction: function (form, container, keditor) {
                            var row = container.find('.row');
                            var backgroundColor = row.prop('style').backgroundColor || '';
                            form.find('.txt-bg-color').val(backgroundColor);
                        },
                        containerSettingHideFunction: function (form, keditor) {
                            form.find('.txt-bg-color').val('');
                        }
                    });
                });
            });
        </script>
        <script>
            window.info_editor_html = null;
            window.info_editor_html_lang = [];
            $(document).ready(function () {
                window.info_editor_html = ace.edit('info_editor_html');
                window.info_editor_html.session.setMode("ace/mode/html");
                window.info_editor_html.setOption('enableLiveAutocompletion',true);
                window.info_editor_html.setShowPrintMargin(false);
                if($('body', window.parent.document).hasClass('dark_mode')) {
                    window.info_editor_html.setTheme("ace/theme/one_dark");
                }
                $('.info_editor_html_lang').each(function() {
                    var id = $(this).attr('id');
                    var lang = $(this).attr('data-lang');
                    window.info_editor_html_lang[lang] = ace.edit(id);
                    window.info_editor_html_lang[lang].session.setMode("ace/mode/html");
                    window.info_editor_html_lang[lang].setOption('enableLiveAutocompletion',true);
                    window.info_editor_html_lang[lang].setShowPrintMargin(false);
                    if($('body', window.parent.document).hasClass('dark_mode')) {
                        window.info_editor_html_lang[lang].setTheme("ace/theme/one_dark");
                    }
                });
            });
            window.get_html_from_editor = function(lang) {
                if(lang=='') {
                    var html = $('#content-area').keditor('getContent');
                    html = html_beautify(html);
                    window.info_editor_html.session.setValue(html);
                } else {
                    var html = $('.content-area-lang[data-lang="'+lang+'"]').keditor('getContent');
                    html = html_beautify(html);
                    window.info_editor_html_lang[lang].session.setValue(html);
                }
            }
            window.set_html_to_editor = function(lang) {
                if(lang=='') {
                    var html = window.info_editor_html.session.getValue();
                    if(html!='') {
                        $('#content-area').keditor('setContent',html);
                    }
                } else {
                    var html = window.info_editor_html_lang[lang].session.getValue();
                    if(html!='') {
                        $('.content-area-lang[data-lang="'+lang+'"]').keditor('setContent',html);
                    }
                }
            }
        </script>
    </body>
</html>
