<?php
$id = $model_info->id ?? '';
$has_image = !empty($model_info->image);
?>

<form id="team-member-form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $id; ?>" />

        <?php if ($has_image): ?>
        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Current Photo</label>
                <div class="col-md-9">
                    <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($model_info->image, 'team'); ?>" style="max-height:100px; border-radius:8px;" />
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Photo</label>
                <div class="col-md-9">
                    <input type="file" name="image" class="form-control" accept="image/*" <?php echo !$id ? 'required' : ''; ?> />
                    <small class="text-muted">Square aspect ratio (1:1) recommended</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Name <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "name", "value" => $model_info->name ?? '', "class" => "form-control", "placeholder" => "Full name", "data-rule-required" => true)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Job Title <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "job_title", "value" => $model_info->job_title ?? '', "class" => "form-control", "placeholder" => "e.g. Principal Architect", "data-rule-required" => true)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Description</label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("name" => "description", "value" => $model_info->description ?? '', "class" => "form-control", "placeholder" => "Brief description...", "rows" => 4)); ?>
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
    $("#team-member-form").on("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: "<?php echo get_uri('landingpage_cms/save_team_member'); ?>",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $("[data-bs-dismiss='modal']").trigger("click");
                    appAlert.success(result.message, {duration: 10000});
                    $("[data-bs-target='#team-tab']").trigger("click");
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
