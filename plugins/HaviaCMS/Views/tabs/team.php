<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Our Team</h5>
            <small class="text-muted">Manage team members displayed on the "Meet Our Team" page (unlimited members)</small>
        </div>
        <?php echo modal_anchor(get_uri("landingpage_cms/team_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add Member', array("class" => "btn btn-primary btn-sm", "title" => "Add Team Member")); ?>
    </div>

    <div class="row" id="team-list">
        <?php if (empty($members)): ?>
            <div class="col-12">
                <p class="text-muted text-center py-5"><em>No team members yet. Click "Add Member" to start.</em></p>
            </div>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <?php if ($member->image): ?>
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($member->image, 'team'); ?>" class="card-img-top" style="height:160px; object-fit:cover;" />
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:160px;">
                                <span class="text-muted" data-feather="user" style="width:40px;height:40px;"></span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2 text-center">
                            <p class="mb-0 small font-weight-bold"><?php echo htmlspecialchars($member->name); ?></p>
                            <p class="mb-0 text-muted" style="font-size:11px;"><?php echo htmlspecialchars($member->job_title); ?></p>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex gap-1 p-2 justify-content-center">
                            <?php echo modal_anchor(get_uri("landingpage_cms/team_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Member", "data-post-id" => $member->id)); ?>
                            <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm", "data-id" => $member->id, "data-action-url" => get_uri("landingpage_cms/delete_team_member"), "data-action" => "delete-confirmation")); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
