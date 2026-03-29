<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Hero Slides</h5>
            <small class="text-muted">Manage hero section images and text (max 5 slides)</small>
        </div>
        <?php
        $slide_count = count($slides ?? []);
        if ($slide_count < 5):
        ?>
        <?php echo modal_anchor(get_uri("landingpage_cms/hero_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add Slide', array("class" => "btn btn-primary btn-sm", "title" => "Add Hero Slide")); ?>
        <?php endif; ?>
    </div>

    <div class="row" id="hero-slides-list">
        <?php if (empty($slides)): ?>
            <div class="col-12">
                <p class="text-muted text-center py-5"><em>No hero slides yet. Click "Add Slide" to get started.</em></p>
            </div>
        <?php else: ?>
            <?php foreach ($slides as $slide): ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <?php if ($slide->image): ?>
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($slide->image, 'hero'); ?>" class="card-img-top" style="height:180px; object-fit:cover;" alt="Hero Slide" />
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                                <span class="text-muted">No image</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-3">
                            <p class="mb-1 font-weight-bold small"><?php echo htmlspecialchars($slide->heading_h1 ?? ''); ?></p>
                            <p class="mb-0 text-muted small"><em><?php echo htmlspecialchars($slide->heading_h2 ?? ''); ?></em></p>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex gap-1 p-2">
                            <?php echo modal_anchor(get_uri("landingpage_cms/hero_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Slide", "data-post-id" => $slide->id)); ?>
                            <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm", "data-id" => $slide->id, "data-action-url" => get_uri("landingpage_cms/delete_hero_slide"), "data-action" => "delete-confirmation")); ?>
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
