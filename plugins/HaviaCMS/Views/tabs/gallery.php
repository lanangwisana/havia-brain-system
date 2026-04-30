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
            <input type="text" name="description" class="form-control form-control-sm" placeholder="Image short description..." style="max-width:250px;" />
            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" style="max-width:250px;" required />
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
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius:10px; overflow:hidden; background:#fff;">
                        <div style="height: 150px; overflow: hidden;">
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($img->image, 'gallery'); ?>" style="width:100%; height:100%; object-fit:cover;" />
                        </div>
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="mb-2" style="min-height: 40px;">
                                <?php if (!empty($img->description)): ?>
                                    <p class="text-muted mb-0" style="font-size:11px; line-height:1.2; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($img->description); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted mb-0" style="font-size:11px;"><em>No description</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex gap-1 p-2">
                            <?php echo modal_anchor(get_uri("landingpage_cms/hero_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit", "data-post-id" => $img->id, "data-post-task" => "gallery_modal")); ?>
                            <?php echo js_anchor('<span data-feather="trash-2" class="icon-14"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm", "data-id" => $img->id, "data-action-url" => get_uri("landingpage_cms/delete_gallery_image"), "data-action" => "delete-confirmation", "data-success-callback" => "reloadGalleryTab")); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    function reloadGalleryTab() {
        $("[data-bs-target='#gallery-tab']").trigger("click");
    }

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
