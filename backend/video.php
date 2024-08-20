<?php
session_start();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$can_create = true;
$can_create = get_plan_permission($id_user)['create_video_projects'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
if($virtual_tour!==false) {
    $videos = true;
    if($user_info['role']=='editor') {
        $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
        if($editor_permissions['video_projects']==0) {
            $videos = false;
        }
    }
} else {
    $videos = false;
}
?>

<?php include("check_plan.php"); ?>

<?php if(!$videos): ?>
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
            <?php echo _("You cannot manage video projects on an external virtual tour!"); ?>
        </div>
    </div>
<?php exit; endif; ?>

<?php if(!$can_create) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo sprintf(_('Your "%s" plan not allow to manage Video Projects!'),$user_info['plan'])." ".$msg_change_plan; ?>
        </div>
    </div>
<?php exit; endif; ?>

<div class="row">
    <?php if($create_content) : ?>
    <div class="col-md-12">
        <div class="card mb-4 py-3 border-left-success">
            <div class="card-body" style="padding-top: 0;padding-bottom: 0;">
                <form autocomplete="off">
                    <div id="modal_new_video" class="row align-items-end">
                        <div class="col-md-6 col-lg-6">
                            <div class="form-group mb-0">
                                <label class="mb-0" for="name"><?php echo _("Name"); ?></label>
                                <input type="text" class="form-control" id="name" />
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6 mt-3 mt-lg-0 text-lg-right text-center">
                            <div class="form-group mb-0">
                                <button <?php echo ($demo) ? 'disabled':''; ?> onclick="add_video();" type="button"  class="btn btn-success"><i class="fas fa-plus"></i> <?php echo _("Create"); ?></button>
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
                <table class="table table-bordered table-hover" id="videos_table" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php echo _("Name"); ?></th>
                        <th><?php echo _("Date"); ?></th>
                        <th><?php echo _("Slides"); ?></th>
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
        $(document).ready(function () {
            $('.help_t').tooltip();
            $('#videos_table').DataTable({
                "order": [[ 1, "desc" ]],
                "responsive": true,
                "scrollX": true,
                "processing": true,
                "searching": true,
                "serverSide": true,
                "ajax": "ajax/get_videos.php?id_vt="+id_virtualtour,
                "drawCallback": function( settings ) {
                    $('#videos_table').DataTable().columns.adjust();
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
            $('#videos_table tbody').on('click', 'td', function () {
                var project_id = $(this).parent().attr("id");
                location.href = 'index.php?p=edit_video&id='+project_id;
            });
        });
    })(jQuery); // End of use strict
</script>