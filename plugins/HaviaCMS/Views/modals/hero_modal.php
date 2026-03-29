<?php
$id = $model_info->id ?? '';
$has_image = !empty($model_info->image);
?>

<form id="hero-slide-form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        
        <?php if ($has_image): ?>
        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Current Image</label>
                <div class="col-md-9">
                    <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($model_info->image, 'hero'); ?>" style="max-height:120px; border-radius:8px;" />
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo $has_image ? 'Replace Image' : 'Image'; ?> <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" <?php echo !$id ? 'required' : ''; ?> />
                    <small class="text-muted">Recommended: 1920x1080px, max 2MB</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="heading_h1" class="col-md-3">Heading (H1)</label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "heading_h1", "name" => "heading_h1", "value" => $model_info->heading_h1 ?? '', "class" => "form-control", "placeholder" => "Main title text")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="heading_h2" class="col-md-3">Subheading (H2)</label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "heading_h2", "name" => "heading_h2", "value" => $model_info->heading_h2 ?? '', "class" => "form-control", "placeholder" => "Subtitle text")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sort_order" class="col-md-3">Sort Order</label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "sort_order", "name" => "sort_order", "value" => $model_info->sort_order ?? 0, "class" => "form-control", "type" => "number", "min" => 0)); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> Close</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save</button>
</div>
</form>

<script type="text/javascript">
$(document).ready(function() {
    $("#hero-slide-form").on("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: "<?php echo get_uri('landingpage_cms/save_hero_slide'); ?>",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    closeAjaxModal();
                    appAlert.success(result.message, {duration: 10000});
                    $("[data-bs-target='#hero-tab']").trigger("click");
                } else {
                    appAlert.error(result.message);
                }
            },
            error: function() {
                appAlert.error("An error occurred. Please try again.");
            }
        });
    });
    if (typeof feather !== 'undefined') feather.replace();
});
</script>
