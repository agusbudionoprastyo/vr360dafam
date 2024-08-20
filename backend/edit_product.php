<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_product = $_GET['id'];
$product = get_product($id_product,$id_user);
if($product!==false) {
    $virtual_tour = get_virtual_tour($product['id_virtualtour'],$id_user);
    $tmp_languages = get_languages_vt();
    $array_languages = $tmp_languages[0];
    $default_language = $tmp_languages[1];
    $button_text_ph = "";
    switch($product['purchase_type']) {
        case "cart":
            $button_text_ph = _("ADD TO CART");
            break;
        case 'link':
        case 'popup':
            $button_text_ph = _("BUY");
            break;
    }
    $s3_params = check_s3_tour_enabled($product['id_virtualtour']);
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
    $array_input_lang = array();
    $query_lang = "SELECT * FROM svt_products_lang WHERE id_product=$id_product;";
    $result_lang = $mysqli->query($query_lang);
    if($result_lang) {
        if ($result_lang->num_rows > 0) {
            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                $language = $row_lang['language'];
                unset($row_lang['id_product']);
                unset($row_lang['language']);
                $array_input_lang[$language]=$row_lang;
            }
        }
    }
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$product): ?>
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
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> <?php echo _("Details"); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name"><?php echo _("Name"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'name'); ?>
                            <input type="text" class="form-control" id="name" value="<?php echo $product['name']; ?>" />
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <input style="display:none;" type="text" class="form-control input_lang" data-target-id="name" data-lang="<?php echo $lang; ?>" value="<?php echo htmlspecialchars($array_input_lang[$lang]['name']); ?>" />
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="price"><?php echo _("Price"); ?></label><br>
                        <div class="input-group">
                            <input min="0" type="number" class="form-control" id="price" value="<?php echo $product['price']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="custom_currency"><?php echo _("Currency"); ?></label>
                            <input <?php echo ($product['purchase_type']=='cart') ? 'disabled' : ''; ?> type="text" class="form-control" id="custom_currency" value="<?php echo ($product['purchase_type']=='cart') ? $virtual_tour['snipcart_currency'] : $product['custom_currency'];?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="type"><?php echo _("Purchase Type"); ?> <i title="<?php echo _("the cart works only if snipcart is configured in the shop section of the tour"); ?>" class="help_t fas fa-question-circle"></i></label>
                            <select onchange="change_product_type();" class="form-control" id="type">
                                <option <?php echo ($product['purchase_type']=='none') ? 'selected' : ''; ?> id="t_none"><?php echo _("None"); ?></option>
                                <option <?php echo ($product['purchase_type']=='link') ? 'selected' : ''; ?> id="t_link"><?php echo _("Link"); ?></option>
                                <option <?php echo ($product['purchase_type']=='popup') ? 'selected' : ''; ?> id="t_popup"><?php echo _("Popup"); ?></option>
                                <option <?php echo ($product['purchase_type']=='cart') ? 'selected' : ''; ?> id="t_cart"><?php echo _("Cart"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="button_icon"><?php echo _("Button Icon"); ?></label><br>
                            <button class="btn btn-sm btn-primary" type="button" id="GetIconPicker" data-iconpicker-input="input#button_icon" data-iconpicker-preview="i#button_icon_preview"><?php echo _("Select Icon"); ?></button>
                            <input readonly type="hidden" id="button_icon" name="Icon" value="<?php echo $product['button_icon']; ?>" required="" placeholder="" autocomplete="off" spellcheck="false">
                            <div style="vertical-align: middle;" class="icon-preview d-inline-block ml-1" data-toggle="tooltip" title="">
                                <i style="font-size: 24px;" id="button_icon_preview" class="<?php echo $product['button_icon']; ?>"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="button_text"><?php echo _("Button Text"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'button_text'); ?>
                            <input <?php echo ($product['purchase_type']=='none') ? 'disabled' : ''; ?> type="text" class="form-control" id="button_text" placeholder="<?php echo $button_text_ph; ?>" value='<?php echo $product['button_text']; ?>' />
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <input <?php echo ($product['purchase_type']=='none') ? 'disabled' : ''; ?> style="display:none;" type="text" class="form-control input_lang" data-target-id="button_text" data-lang="<?php echo $lang; ?>" value="<?php echo $array_input_lang[$lang]['button_text']; ?>" />
                                <?php endif;
                            } ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="button_background"><?php echo _("Button Background"); ?></label>
                            <input <?php echo ($product['purchase_type']=='none') ? 'disabled' : ''; ?> type="text" class="form-control" id="button_background" value='<?php echo $product['button_background']; ?>' />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="button_color"><?php echo _("Button Color"); ?></label>
                            <input <?php echo ($product['purchase_type']=='none') ? 'disabled' : ''; ?> type="text" class="form-control" id="button_color" value='<?php echo $product['button_color']; ?>' />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="link"><?php echo _("Link"); ?></label>
                            <input <?php echo ($product['purchase_type']!='link' && $product['purchase_type']!='popup') ? 'disabled' : ''; ?> type="text" class="form-control" id="link" value="<?php echo $product['link']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description"><?php echo _("Description"); ?></label><?php echo print_language_input_selector($array_languages,$default_language,'description_product'); ?>
                            <div><div id="description"><?php echo $product['description']; ?></div></div>
                            <?php foreach ($array_languages as $lang) {
                                if($lang!=$default_language) : ?>
                                    <div style="display:none;"><div id="description_<?php echo $lang; ?>" class="input_lang" data-target-id="description" data-lang="<?php echo $lang; ?>"><?php echo $array_input_lang[$lang]['description']; ?></div></div>
                                <?php endif;
                            } ?>
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
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-grip-horizontal"></i> <?php echo _("Images List"); ?> <i style="font-size:12px">(<?php echo _("drag images to change order"); ?>)</i></h6>
            </div>
            <div class="card-body">
                <?php if($create_content) : ?><form action="ajax/upload_product_image.php" class="dropzone mb-3 noselect" id="product-dropzone"></form><?php endif; ?>
                <div id="list_images" class="noselect">
                    <p><?php echo _("Loading images ..."); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal_delete_product" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo _("Delete Product"); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo _("Are you sure you want to delete the product?"); ?>
                </p>
            </div>
            <div class="modal-footer">
                <button <?php echo ($demo) ? 'disabled':''; ?> id="btn_delete_product" onclick="" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo _("Yes, Delete"); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.product_need_save = false;
        window.id_product = <?php echo $id_product; ?>;
        window.description_editor = null;
        window.description_editor_lang = [];
        window.cart_ph = `<?php echo _("ADD TO CART"); ?>`;
        window.buy_ph = `<?php echo _("BUY"); ?>`;
        window.background_button_spectrum = null;
        window.color_button_spectrum = null;
        window.s3_enabled = <?php echo ($s3_enabled) ? 1 : 0; ?>;
        window.s3_url = '<?php echo $s3_url; ?>';
        var DirectionAttribute = Quill.import('attributors/attribute/direction');
        Quill.register(DirectionAttribute,true);
        var AlignClass = Quill.import('attributors/class/align');
        Quill.register(AlignClass,true);
        var BackgroundClass = Quill.import('attributors/class/background');
        Quill.register(BackgroundClass,true);
        var ColorClass = Quill.import('attributors/class/color');
        Quill.register(ColorClass,true);
        var DirectionClass = Quill.import('attributors/class/direction');
        Quill.register(DirectionClass,true);
        var FontClass = Quill.import('attributors/class/font');
        Quill.register(FontClass,true);
        var SizeClass = Quill.import('attributors/class/size');
        Quill.register(SizeClass,true);
        var AlignStyle = Quill.import('attributors/style/align');
        Quill.register(AlignStyle,true);
        var BackgroundStyle = Quill.import('attributors/style/background');
        Quill.register(BackgroundStyle,true);
        var ColorStyle = Quill.import('attributors/style/color');
        Quill.register(ColorStyle,true);
        var DirectionStyle = Quill.import('attributors/style/direction');
        Quill.register(DirectionStyle,true);
        var FontStyle = Quill.import('attributors/style/font');
        Quill.register(FontStyle,true);
        var SizeStyle = Quill.import('attributors/style/size');
        Quill.register(SizeStyle,true);
        var LinkFormats = Quill.import("formats/link");
        Quill.register(LinkFormats,true);
        window.product_images = [];
        Dropzone.autoDiscover = false;
        $(document).ready(function () {
            $('.help_t').tooltip();
            var toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['clean']
            ];
            window.description_editor = new Quill('#description', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow'
            });
            $('.input_lang[data-target-id="description"]').each(function() {
                var lang = $(this).attr('data-lang');
                var id = $(this).attr('id');
                window.description_editor_lang[lang] = new Quill('#'+id, {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    theme: 'snow'
                });
            });
            IconPicker.Init({
                jsonUrl: 'vendor/iconpicker/iconpicker-1.5.0.json',
                searchPlaceholder: '<?php echo _("Search Icon"); ?>',
                showAllButton: '<?php echo _("Show All"); ?>',
                cancelButton: '<?php echo _("Cancel"); ?>',
                noResultsFound: '<?php echo _("No results found."); ?>',
                borderRadius: '20px'
            });
            IconPicker.Run('#GetIconPicker', function(){
                window.product_need_save = true;
            });
            window.background_button_spectrum = $('#button_background').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            window.color_button_spectrum = $('#button_color').spectrum({
                type: "text",
                preferredFormat: "hex",
                showAlpha: false,
                showButtons: false,
                allowEmpty: false,
            });
            get_product_images(id_product);
            if($('#product-dropzone').length) {
                var product_dropzone = new Dropzone("#product-dropzone", {
                    url: "ajax/upload_product_image.php",
                    parallelUploads: 1,
                    maxFilesize: 20,
                    timeout: 120000,
                    dictDefaultMessage: "<?php echo _("Drop files or click here to upload"); ?>",
                    dictFallbackMessage: "<?php echo _("Your browser does not support drag'n'drop file uploads."); ?>",
                    dictFallbackText: "<?php echo _("Please use the fallback form below to upload your files like in the olden days."); ?>",
                    dictFileTooBig: "<?php echo sprintf(_("File is too big (%sMiB). Max filesize: %sMiB."),'{{filesize}}','{{maxFilesize}}'); ?>",
                    dictInvalidFileType: "<?php echo _("You can't upload files of this type."); ?>",
                    dictResponseError: "<?php echo sprintf(_("Server responded with %s code."),'{{statusCode}}'); ?>",
                    dictCancelUpload: "<?php echo _("Cancel upload"); ?>",
                    dictCancelUploadConfirmation: "<?php echo _("Are you sure you want to cancel this upload?"); ?>",
                    dictRemoveFile: "<?php echo _("Remove file"); ?>",
                    dictMaxFilesExceeded: "<?php echo _("You can not upload any more files."); ?>",
                    acceptedFiles: 'image/*'
                });
                product_dropzone.on("addedfile", function(file) {
                    $('#list_images').addClass('disabled');
                });
                product_dropzone.on("success", function(file,rsp) {
                    add_image_to_product(id_product,rsp);
                });
                product_dropzone.on("queuecomplete", function() {
                    $('#list_images').removeClass('disabled');
                    product_dropzone.removeAllFiles();
                });
            }
        });
        $("input[type='text']").change(function(){
            window.product_need_save = true;
        });
        $("input[type='checkbox']").change(function(){
            window.product_need_save = true;
        });
        $("select").change(function(){
            window.product_need_save = true;
        });
        $(window).on('beforeunload', function(){
            if(window.product_need_save) {
                var c=confirm();
                if(c) return true; else return false;
            }
        });
    })(jQuery); // End of use strict
</script>