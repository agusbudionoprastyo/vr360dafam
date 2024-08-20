<?php
session_start();
$id_user = $_SESSION['id_user'];
$can_create = get_plan_permission($id_user)['create_globes'];
$change_plan = get_settings()['change_plan'];
if($change_plan) {
    $msg_change_plan = "<a class='text-white' href='index.php?p=change_plan'><b>"._("Click here to change your plan")."</b></a>";
} else {
    $msg_change_plan = "";
}
?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to create Globes!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php include("check_plan.php"); ?>

<div class="row">
    <?php if($user_info['plan_status']!='expired') : ?>
    <div class="col-md-12">
        <div class="card mb-4 py-3 border-left-success">
            <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                <form autocomplete="off">
                    <div id="modal_new_globe" class="row align-items-end">
                        <div class="col-md-12 col-lg-6">
                            <div class="form-group mb-0">
                                <label class="mb-0" for="name"><?php echo _("Name"); ?></label>
                                <input type="text" class="form-control" id="name" />
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6 mt-3 mt-lg-0 text-lg-right text-center">
                            <div class="form-group mb-0">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_globe();" type="button"  class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered table-hover" id="globes_table" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php echo _("Name"); ?></th>
                        <th><?php echo _("Author"); ?></th>
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
            $('#globes_table').DataTable({
                "responsive": true,
                "scrollX": true,
                "processing": true,
                "searching": true,
                "serverSide": true,
                "ajax": "ajax/get_globes.php",
                "drawCallback": function( settings ) {
                    $('#globes_table').DataTable().columns.adjust();
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
            $('#globes_table tbody').on('click', 'td', function () {
                var globe_id = $(this).parent().attr("id");
                location.href = 'index.php?p=edit_globe&id='+globe_id;
            });
        });
    })(jQuery);
</script>