<?php
session_start();
$role = get_user_role($_SESSION['id_user']);
?>

<?php if($role!='administrator'): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php include("check_plan.php"); ?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 py-3 border-left-success">
            <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                <form autocomplete="off">
                    <div id="modal_new_advertisement" class="row align-items-end">
                        <div class="col-md-12 col-lg-6">
                            <div class="form-group mb-0">
                                <label class="mb-0" for="name"><?php echo _("Name"); ?></label>
                                <input type="text" class="form-control" id="name" />
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6 mt-3 mt-lg-0 text-lg-right text-center">
                            <div class="form-group mb-0">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_advertisement();" type="button"  class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered table-hover" id="advertisements_table" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php echo _("Name"); ?></th>
                        <th><?php echo _("Type"); ?></th>
                        <th><?php echo _("Auto Assign"); ?></th>
                        <th><?php echo _("N. Virtual Tours"); ?></th>
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
        "use strict";
        $(document).ready(function () {
            $('#advertisements_table').DataTable({
                "responsive": true,
                "scrollX": true,
                "processing": true,
                "searching": true,
                "serverSide": true,
                "ajax": "ajax/get_advertisements.php",
                "drawCallback": function( settings ) {
                    $('#advertisements_table').DataTable().columns.adjust();
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
            $('#advertisements_table tbody').on('click', 'td', function () {
                var ads_id = $(this).parent().attr("id");
                location.href = 'index.php?p=edit_advertisement&id='+ads_id;
            });
        });
    })(jQuery);
</script>