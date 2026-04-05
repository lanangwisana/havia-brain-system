<?php
$id = $model_info->id ?? '';
$has_image = !empty($model_info->image);
$type = $model_info->type ?? 'corporate';
?>

<form id="testimonial-form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="type" value="<?php echo $type; ?>" />

        <?php if ($has_image): ?>
        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Current Image</label>
                <div class="col-md-9">
                    <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($model_info->image, 'testimonials'); ?>" style="max-height:60px;" />
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Image</label>
                <div class="col-md-9">
                    <input type="file" name="image" class="form-control" accept="image/*" />
                    <small class="text-muted"><?php echo $type === 'corporate' ? 'Company logo' : 'Profile photo'; ?></small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Name (H1) <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "name", "value" => $model_info->name ?? '', "class" => "form-control", "placeholder" => $type === 'corporate' ? 'Company name' : 'Person name', "data-rule-required" => true)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Subtitle (H2)</label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "subtitle", "value" => $model_info->subtitle ?? '', "class" => "form-control", "placeholder" => $type === 'corporate' ? 'Location / Industry' : 'Title / Role')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Quote <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("name" => "description", "value" => $model_info->description ?? '', "class" => "form-control", "style" => "height:80px;", "placeholder" => "Testimonial text...", "data-rule-required" => true)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Sort Order</label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "sort_order", "value" => $model_info->sort_order ?? 0, "class" => "form-control", "type" => "number", "min" => 0)); ?>
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
    $("#testimonial-form").on("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: "<?php echo get_uri('landingpage_cms/save_testimonial'); ?>",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $("[data-bs-dismiss='modal']").trigger("click");
                    appAlert.success(result.message, {duration: 10000});
                    $("[data-bs-target='#trust-tab']").trigger("click");
                } else {
                    appAlert.error(result.message);
                }
            },
            error: function() {
                appAlert.error("An error occurred.");
            }
        });
    });
    if (typeof feather !== 'undefined') feather.replace();
});
</script>
