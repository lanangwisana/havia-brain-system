<div class="card-body">
    <!-- CATEGORY MANAGEMENT -->
    <div class="mb-4">
        <h5 class="mb-1">Project Categories</h5>
        <small class="text-muted d-block mb-1">Default category "All" is always included automatically. Manage additional categories below.</small>
        <small class="text-danger d-block mb-3" style="font-size: 11px;"><span data-feather="info" class="icon-12"></span> Note: Maximum 9 projects per category recommended for optimal display.</small>
        
        <div class="d-flex flex-wrap gap-2 mb-3" id="category-list">
            <span class="badge bg-secondary py-2 px-3">All (default)</span>
            <?php foreach ($categories as $cat): ?>
                <span class="badge bg-primary py-2 px-3 d-flex align-items-center gap-1">
                    <?php echo htmlspecialchars($cat->name); ?>
                    <?php if (!$cat->is_default): ?>
                    <a href="javascript:void(0);" class="text-white ms-1 delete-category" data-id="<?php echo $cat->id; ?>" data-action-url="<?php echo get_uri('landingpage_cms/delete_category'); ?>" title="Remove" style="font-size:14px; line-height:1;">&times;</a>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <form id="add-category-form" class="d-flex gap-2" style="max-width:400px;">
            <?php echo csrf_field(); ?>
            <input type="text" name="name" class="form-control form-control-sm" placeholder="New category name..." required />
            <button type="submit" class="btn btn-primary btn-sm text-nowrap"><span data-feather="plus" class="icon-16"></span> Add</button>
        </form>
    </div>

    <hr/>

    <!-- PROJECTS -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Projects</h5>
        <?php echo modal_anchor(get_uri("landingpage_cms/project_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add Project', array("class" => "btn btn-primary btn-sm", "title" => "Add Project")); ?>
    </div>

    <?php if (empty($projects)): ?>
        <p class="text-muted text-center py-5"><em>No projects yet. Click "Add Project" to start.</em></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width:80px;">Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Year</th>
                        <th>Client</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project):
                        $cat_name = '';
                        foreach ($categories as $c) {
                            if ($c->id == $project->category_id) {
                                $cat_name = $c->name;
                                break;
                            }
                        }
                        $first_image = !empty($project->project_images) ? $project->project_images[0] : null;
                    ?>
                    <tr>
                        <td>
                            <?php if ($first_image): ?>
                                <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($first_image->image_path, 'projects'); ?>" style="width:60px; height:45px; object-fit:cover; border-radius:4px;" />
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="width:60px; height:45px; border-radius:4px;">
                                    <span class="text-muted" style="font-size:10px;">—</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($project->title); ?></strong></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($cat_name); ?></span></td>
                        <td><?php echo htmlspecialchars($project->location); ?></td>
                        <td><?php echo htmlspecialchars($project->year); ?></td>
                        <td><?php echo htmlspecialchars($project->client); ?></td>
                        <td>
                            <?php echo modal_anchor(get_uri("landingpage_cms/project_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Project", "data-post-id" => $project->id)); ?>
                            <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm project-delete-btn", "data-id" => $project->id, "data-action-url" => get_uri("landingpage_cms/delete_project"))); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    function reloadPortfolioTab() {
        $("[data-bs-target='#portfolio-tab']").trigger("click");
    }

    $(document).ready(function () {
        // Project delete handler
        $(document).on("click", ".project-delete-btn", function(e) {
            e.preventDefault();
            deleteConfirmationHandler(e, function(result, $target) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 5000});
                    reloadPortfolioTab();
                } else {
                    appAlert.error(result.message);
                }
            });
        });

        // Add category
        $("#add-category-form").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "<?php echo get_uri('landingpage_cms/save_category'); ?>",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message, {duration: 5000});
                        $("[data-bs-target='#portfolio-tab']").trigger("click");
                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });

        // Delete category
        $(document).on("click", ".delete-category", function(e) {
            e.preventDefault();
            var $btn = $(this);
            deleteConfirmationHandler(e, function(result, $target) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 5000});
                    $("[data-bs-target='#portfolio-tab']").trigger("click");
                } else {
                    appAlert.error(result.message);
                }
            });
        });

        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
