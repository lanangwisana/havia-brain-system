<?php
$id = $model_info->id ?? '';
$has_image = !empty($model_info->image);
?>

<?php echo form_open(get_uri("landingpage_cms/save_hero_slide"), array("id" => "hero-slide-form", "class" => "general-form", "role" => "form", "enctype" => "multipart/form-data")); ?>
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
                    <input type="file" name="image" class="form-control" 
                           accept="image/jpeg,image/png,image/webp" 
                           <?php echo !$id ? 'data-rule-required="true"' : ''; ?> 
                           data-msg-required="Please select an image for this slide." />
                    <small class="text-muted">Recommended: 1920x1080px, max 5MB</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="heading_h1" class="col-md-3">Heading (H1) <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        "id" => "heading_h1", 
                        "name" => "heading_h1", 
                        "value" => $model_info->heading_h1 ?? '', 
                        "class" => "form-control", 
                        "placeholder" => "Main title text",
                        "data-rule-required" => "true",
                        "data-msg-required" => "This title is needed for the main heading."
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="heading_h2" class="col-md-3">Subheading (H2) <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        "id" => "heading_h2", 
                        "name" => "heading_h2", 
                        "value" => $model_info->heading_h2 ?? '', 
                        "class" => "form-control", 
                        "placeholder" => "Subtitle text",
                        "data-rule-required" => "true",
                        "data-msg-required" => "This subtitle is needed for the link button."
                    )); ?>
                </div>
            </div>
        </div>

        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> Close</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save</button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
$(document).ready(function() {
    $("#hero-slide-form").appForm({
        isModal: true,
        onSuccess: function (result) {
            if (result.success) {
                appAlert.success(result.message, {duration: 10000});
                // Reload the tab content
                $("[data-bs-target='#hero-tab']").trigger("click");
            } else {
                appAlert.error(result.message);
            }
        }
    });
    
    if (typeof feather !== 'undefined') feather.replace();
});
</script>