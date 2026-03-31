<?php
$id = $model_info->id ?? '';
$categories_dropdown = array("" => "-- Select Category --");
foreach ($categories as $cat) {
    $categories_dropdown[$cat->id] = $cat->name;
}
?>

<form id="project-form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $id; ?>" />

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Category <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("category_id", $categories_dropdown, $model_info->category_id ?? '', "class='form-control' data-rule-required='true'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Title <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "title", "value" => $model_info->title ?? '', "class" => "form-control", "placeholder" => "Project title", "data-rule-required" => true)); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><div class="row">
                    <label class="col-md-6">Location</label>
                    <div class="col-md-6"><?php echo form_input(array("name" => "location", "value" => $model_info->location ?? '', "class" => "form-control", "placeholder" => "City")); ?></div>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><div class="row">
                    <label class="col-md-6">Year</label>
                    <div class="col-md-6"><?php echo form_input(array("name" => "year", "value" => $model_info->year ?? '', "class" => "form-control", "placeholder" => "2024")); ?></div>
                </div></div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Client</label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "client", "value" => $model_info->client ?? '', "class" => "form-control", "placeholder" => "Client name")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Scope</label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "scope", "value" => $model_info->scope ?? '', "class" => "form-control", "placeholder" => "Architecture Design, Interior, Masterplan (comma separated)")); ?>
                    <small class="text-muted">Separate multiple scopes with commas</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Description</label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("name" => "description", "value" => $model_info->description ?? '', "class" => "form-control", "style" => "height:80px;", "placeholder" => "Project description / story")); ?>
                </div>
            </div>
        </div>

        <hr/>
        <h6 class="mb-3">Project Images (max 3)</h6>
        
        <?php for ($i = 1; $i <= 3; $i++): 
            $existing_img = isset($project_images[$i - 1]) ? $project_images[$i - 1] : null;
        ?>
        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Image <?php echo $i; ?></label>
                <div class="col-md-9">
                    <?php if ($existing_img): ?>
                        <div class="mb-2">
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($existing_img->image_path, 'projects'); ?>" style="max-height:60px; border-radius:4px;" />
                            <input type="hidden" name="existing_image_id_<?php echo $i; ?>" value="<?php echo $existing_img->id; ?>" />
                        </div>
                    <?php endif; ?>
                    <input type="file" name="project_image_<?php echo $i; ?>" class="form-control" accept="image/*" />
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> Close</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save Project</button>
</div>
</form>

<script type="text/javascript">
$(document).ready(function() {
    $("#project-form").on("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: "<?php echo get_uri('landingpage_cms/save_project'); ?>",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    closeAjaxModal();
                    appAlert.success(result.message, {duration: 10000});
                    $("[data-bs-target='#portfolio-tab']").trigger("click");
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
