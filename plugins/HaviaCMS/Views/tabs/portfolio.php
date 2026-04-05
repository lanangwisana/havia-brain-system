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
                    <a href="javascript:void(0);" class="text-white ms-1 delete-category" data-id="<?php echo $cat->id; ?>" data-action-url="<?php echo get_uri('landingpage_cms/delete_category'); ?>" title="Remove" style="font-size:14px; line-height:1;">&times;</a>
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
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0">Projects</h5>
            
            <!-- Category Filter -->
            <select id="category-filter" class="form-select form-select-sm" style="width: 200px;">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): 
                    $count = 0;
                    foreach ($projects as $p) { if ($p->category_id == $cat->id) $count++; }
                ?>
                    <option value="<?php echo $cat->id; ?>"><?php echo htmlspecialchars($cat->name); ?> (<?php echo $count; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php echo modal_anchor(get_uri("landingpage_cms/project_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add Project', array("class" => "btn btn-primary btn-sm", "title" => "Add Project")); ?>
    </div>

    <?php if (empty($projects)): ?>
        <p class="text-muted text-center py-5"><em>No projects yet. Click "Add Project" to start.</em></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="projects-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th style="width:80px;">Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Year</th>
                        <th>Created At</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($projects as $project):
                        $cat_name = '';
                        foreach ($categories as $c) {
                            if ($c->id == $project->category_id) {
                                $cat_name = $c->name;
                                break;
                            }
                        }
                        $first_image = !empty($project->project_images) ? $project->project_images[0] : null;
                    ?>
                    <tr class="project-row" data-category-id="<?php echo $project->category_id; ?>">
                        <td class="row-no"><?php echo $no++; ?></td>
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
                        <td class="text-muted" style="font-size:12px;">
                            <?php echo !empty($project->created_at) ? date('d M Y - H:i', strtotime($project->created_at)) : '-'; ?>
                        </td>
                        <td>
                            <?php echo modal_anchor(get_uri("landingpage_cms/project_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Project", "data-post-id" => $project->id)); ?>
                            <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm project-delete-btn", "data-id" => $project->id, "data-action-url" => get_uri("landingpage_cms/delete_project"))); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3" id="projects-pagination-wrapper">
            <small class="text-muted" id="projects-pagination-info"></small>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="projects-pagination"></ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    function reloadPortfolioTab() {
        $("[data-bs-target='#portfolio-tab']").trigger("click");
    }

    $(document).ready(function () {
        // ============================================================
        // PROJECTS TABLE PAGINATION & FILTERING
        // ============================================================
        var projectsPerPage = 10;
        var currentPage = 1;

        function renderProjectsPagination() {
            var filterVal = $("#category-filter").val();
            var $allRows = $("#projects-table tbody tr");
            
            // 1. First, identify visible rows based on filter
            var $visibleRows = (filterVal === 'all') 
                ? $allRows 
                : $allRows.filter('[data-category-id="' + filterVal + '"]');

            var totalItems = $visibleRows.length;
            var totalPages = Math.ceil(totalItems / projectsPerPage);
            
            // Adjust current page if out of bounds after filter
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            var $pagination = $("#projects-pagination");
            var $info = $("#projects-pagination-info");
            $pagination.empty();

            // Hide/Show Rows
            $allRows.hide();
            
            // Update continuous numbering for visible rows
            $visibleRows.each(function(index) {
                $(this).find('.row-no').text(index + 1);
            });

            if (totalItems === 0) {
                $("#projects-pagination-wrapper").hide();
                return;
            }

            // Slice rows for the current page
            $visibleRows.slice((currentPage - 1) * projectsPerPage, currentPage * projectsPerPage).show();

            // Render Pagination Markers
            if (totalPages <= 1) {
                $("#projects-pagination-wrapper").hide();
                return;
            }

            $("#projects-pagination-wrapper").show();
            var startItem = (currentPage - 1) * projectsPerPage + 1;
            var endItem = Math.min(currentPage * projectsPerPage, totalItems);
            $info.text("Showing " + startItem + " - " + endItem + " of " + totalItems + " filtered projects");

            // Previous
            $pagination.append('<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0);" data-page="' + (currentPage - 1) + '">&laquo;</a></li>');

            // Pages
            for (var i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    $pagination.append('<li class="page-item ' + (i === currentPage ? 'active' : '') + '"><a class="page-link" href="javascript:void(0);" data-page="' + i + '">' + i + '</a></li>');
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    $pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
            }

            // Next
            $pagination.append('<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0);" data-page="' + (currentPage + 1) + '">&raquo;</a></li>');
        }

        // Category Filter Change
        $(document).on("change", "#category-filter", function() {
            currentPage = 1;
            renderProjectsPagination();
        });

        // Pagination Click
        $(document).on("click", "#projects-pagination .page-link", function(e) {
            e.preventDefault();
            var page = parseInt($(this).data("page"));
            if (page >= 1) {
                currentPage = page;
                renderProjectsPagination();
                document.getElementById('projects-table').scrollIntoView({behavior: "smooth", block: "nearest"});
            }
        });

        // Initialize
        if ($("#projects-table").length) {
            renderProjectsPagination();
        }

        // ============================================================
        // EXISTING HANDLERS
        // ============================================================
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
                        reloadPortfolioTab();
                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });

        $(document).on("click", ".delete-category", function(e) {
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

        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
