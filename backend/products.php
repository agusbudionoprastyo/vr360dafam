<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = true;
$can_create = get_plan_permission($id_user)['enable_shop'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $products = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['shop']==0) {
            $products = false;
        }
    }
    $shop_type = $virtual_tour['shop_type'];
    if($shop_type=='woocommerce') {
        if(empty($virtual_tour['woocommerce_store_url']) || empty($virtual_tour['woocommerce_customer_key']) || empty($virtual_tour['woocommerce_customer_secret'])) {
            $shop_type = 'snipcart';
        }
    }
} else {
    $products = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$products): ?>
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
            <?php echo _("You cannot manage products on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to manage Products!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <?php if($shop_type=='woocommerce') : ?>
    <div class="col-md-12">
        <div class="card bg-info text-white shadow mb-4">
            <div class="card-body">
                <?php echo sprintf(_('These products are managed by your Woocommerce site: <b>%s</b>'),$virtual_tour['woocommerce_store_url']); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if($create_content && $shop_type=='snipcart') : ?>
    <div class="col-md-12">
        <div class="card mb-4 py-3 border-left-success">
            <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                <form autocomplete="off">
                    <div id="modal_new_product" class="row align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group mb-0">
                                <label class="mb-0" for="name"><?php echo _("Name"); ?></label>
                                <input type="text" class="form-control" id="name" />
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="mb-0" for="price"><?php echo _("Price"); ?></label><br>
                            <div class="input-group mb-0">
                                <input min="0" type="number" class="form-control" id="price" value="0" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><?php echo $virtual_tour['snipcart_currency'] ?>&nbsp;&nbsp;<i style="margin-bottom:1px;" title="<?php echo _("you can change the currency in the shop tab of the tour settings"); ?>" class="help_t fas fa-question-circle"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6 mt-3 mt-lg-0 text-lg-right text-center">
                            <div class="form-group mb-0">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_product();" type="button"  class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered table-hover <?php echo ($shop_type=='woocommerce') ? 'd-none' : ''; ?>" id="products_table" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php echo _("Product"); ?></th>
                        <th><?php echo _("Price"); ?></th>
                        <th><?php echo _("Type"); ?></th>
                        <th><?php echo _("Button"); ?></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
                <div class="<?php echo ($shop_type=='woocommerce') ? '' : 'd-none'; ?>" id="loading_products_wc">
                    <i class="fas fa-circle-notch fa-spin"></i>&nbsp;&nbsp;<?php echo _("Loading products ..."); ?>
                </div>
                <table class="table table-bordered d-none" id="products_wc_table" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php echo _("Product"); ?></th>
                        <th><?php echo _("Type"); ?></th>
                        <th><?php echo _("SKU"); ?></th>
                        <th><?php echo _("Price"); ?></th>
                        <th><?php echo _("Status"); ?></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_user = '<?php echo $id_user; ?>';
        window.id_virtualtour = '<?php echo $id_virtualtour_sel; ?>';
        window.shop_type = '<?php echo $shop_type; ?>';
        $(document).ready(function () {
            $('.help_t').tooltip();
            switch(window.shop_type) {
                case 'snipcart':
                    $('#products_table').DataTable({
                        "order": [[ 0, "desc" ]],
                        "responsive": true,
                        "scrollX": true,
                        "processing": true,
                        "searching": true,
                        "serverSide": true,
                        "saveState": true,
                        "ajax": "ajax/get_products.php?id_vt="+id_virtualtour,
                        "drawCallback": function( settings ) {
                            $('#products_table').DataTable().columns.adjust();
                        },
                        "language": {
                            "decimal":        "",
                            "emptyTable":     "<?php echo _("No data available in table"); ?>",
                            "info":           "<?php echo sprintf(_("Showing %s to %s of %s entries"),'_START_','_END_','_TOTAL_'); ?>",
                            "infoEmpty":      "<?php echo _("Showing 0 to 0 of 0 entries"); ?>",
                            "infoFiltered":   "<?php echo sprintf(_("(filtered from %s total entries)"),'_MAX_'); ?>",
                            "infoPostFix":    "",
                            "thousands":      ",",
                            "lengthMenu":     "<?php echo sprintf(_("Show %s entries"),'_MENU_'); ?>",
                            "loadingRecords": "<?php echo _("Loading"); ?>...",
                            "processing":     "<?php echo _("Processing"); ?>...",
                            "search":         "<?php echo _("Search"); ?>:",
                            "zeroRecords":    "<?php echo _("No matching records found"); ?>",
                            "paginate": {
                                "first":      "<?php echo _("First"); ?>",
                                "last":       "<?php echo _("Last"); ?>",
                                "next":       "<?php echo _("Next"); ?>",
                                "previous":   "<?php echo _("Previous"); ?>"
                            },
                            "aria": {
                                "sortAscending":  ": <?php echo _("activate to sort column ascending"); ?>",
                                "sortDescending": ": <?php echo _("activate to sort column descending"); ?>"
                            }
                        }
                    });
                    $('#products_table tbody').on('click', 'td', function () {
                        var product_id = $(this).parent().attr("id");
                        location.href = 'index.php?p=edit_product&id='+product_id;
                    });
                    break;
                case 'woocommerce':
                    $.ajax({
                        url: "ajax/get_products_wc.php",
                        type: "POST",
                        data: {
                            id_vt: window.id_virtualtour
                        },
                        async: true,
                        success: function (json) {
                            var rsp = JSON.parse(json);
                            $('#loading_products_wc').addClass('d-none');
                            $('#products_wc_table').removeClass('d-none');
                            if(rsp.status=='ok') {
                                $('#products_wc_table tbody').html(rsp.products).promise().done(function() {
                                    $('#products_wc_table').DataTable({
                                        "order": [[ 0, "asc" ]],
                                        "responsive": true,
                                        "scrollX": true,
                                        "processing": true,
                                        "searching": true,
                                        "saveState": true,
                                        "drawCallback": function( settings ) {
                                            $('#products_wc_table').DataTable().columns.adjust();
                                        },
                                        "language": {
                                            "decimal":        "",
                                            "emptyTable":     "<?php echo _("No data available in table"); ?>",
                                            "info":           "<?php echo sprintf(_("Showing %s to %s of %s entries"),'_START_','_END_','_TOTAL_'); ?>",
                                            "infoEmpty":      "<?php echo _("Showing 0 to 0 of 0 entries"); ?>",
                                            "infoFiltered":   "<?php echo sprintf(_("(filtered from %s total entries)"),'_MAX_'); ?>",
                                            "infoPostFix":    "",
                                            "thousands":      ",",
                                            "lengthMenu":     "<?php echo sprintf(_("Show %s entries"),'_MENU_'); ?>",
                                            "loadingRecords": "<?php echo _("Loading"); ?>...",
                                            "processing":     "<?php echo _("Processing"); ?>...",
                                            "search":         "<?php echo _("Search"); ?>:",
                                            "zeroRecords":    "<?php echo _("No matching records found"); ?>",
                                            "paginate": {
                                                "first":      "<?php echo _("First"); ?>",
                                                "last":       "<?php echo _("Last"); ?>",
                                                "next":       "<?php echo _("Next"); ?>",
                                                "previous":   "<?php echo _("Previous"); ?>"
                                            },
                                            "aria": {
                                                "sortAscending":  ": <?php echo _("activate to sort column ascending"); ?>",
                                                "sortDescending": ": <?php echo _("activate to sort column descending"); ?>"
                                            }
                                        }
                                    });
                                });
                            } else {
                                $('#products_wc_table tbody').html('<tr><td colspan="2">'+rsp.msg+'</td></tr>');
                            }
                        }
                    });
                    break;
            }
        });
    })(jQuery); // End of use strict
</script>