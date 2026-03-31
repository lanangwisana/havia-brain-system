<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Life at Havia</h5>
            <small class="text-muted">Gallery images for the about page (max 12 images, aspect ratio 4:3 recommended)</small>
        </div>
        <?php
        $img_count = count($images ?? []);
        if ($img_count < 12):
        ?>
        <form id="gallery-upload-form" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
            <?php echo csrf_field(); ?>
            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" style="max-width:250px;" />
            <button type="submit" class="btn btn-primary btn-sm"><span data-feather="upload" class="icon-16"></span> Upload</button>
        </form>
        <?php else: ?>
        <span class="badge bg-warning">Maximum reached (12/12)</span>
        <?php endif; ?>
    </div>

    <p class="text-muted small mb-3"><?php echo $img_count; ?>/12 images uploaded</p>

    <div class="row" id="gallery-list">
        <?php if (empty($images)): ?>
            <div class="col-12">
                <p class="text-muted text-center py-5"><em>No gallery images yet. Upload your first image.</em></p>
            </div>
        <?php else: ?>
            <?php foreach ($images as $img): ?>
                <div class="col-md-3 col-6 mb-3">
                    <div class="position-relative" style="border-radius:8px; overflow:hidden;">
                        <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($img->image, 'gallery'); ?>" style="width:100%; height:140px; object-fit:cover;" />
                        <div class="position-absolute" style="top:5px; right:5px;">
                            <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-xs", "style" => "padding:2px 6px; border-radius:50%;", "data-id" => $img->id, "data-action-url" => get_uri("landingpage_cms/delete_gallery_image"), "data-action" => "delete-confirmation")); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#gallery-upload-form").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "<?php echo get_uri('landingpage_cms/save_gallery_image'); ?>",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message, {duration: 5000});
                        $("[data-bs-target='#gallery-tab']").trigger("click");
                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
