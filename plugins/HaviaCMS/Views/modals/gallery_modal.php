<?php echo form_open(get_uri("landingpage_cms/save_gallery_image"), array("id" => "gallery-image-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        
        <div class="form-group">
            <div class="col-md-12 text-center mb-3">
                <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($model_info->image, 'gallery'); ?>" style="max-height: 200px; border-radius: 8px;" />
            </div>
        </div>

        <div class="form-group">
            <label for="description" class=" col-md-3">Description</label>
            <div class=" col-md-9">
                <?php
                echo form_textarea(array(
                    "id" => "description",
                    "name" => "description",
                    "value" => $model_info->description,
                    "class" => "form-control",
                    "placeholder" => "Image short description...",
                    "style" => "height:100px;"
                ));
                ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="sort_order" class=" col-md-3">Sort Order</label>
            <div class=" col-md-9">
                <?php
                echo form_input(array(
                    "id" => "sort_order",
                    "name" => "sort_order",
                    "value" => $model_info->sort_order,
                    "class" => "form-control",
                    "type" => "number",
                ));
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="image" class=" col-md-3">Change Image</label>
            <div class=" col-md-9">
                <input type="file" name="image" class="form-control" accept="image/*" />
                <small class="text-muted">Leave empty to keep current image.</small>
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
    $(document).ready(function () {
        $("#gallery-image-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 5000});
                    reloadGalleryTab();
                } else {
                    appAlert.error(result.message);
                }
            }
        });
        if (typeof feather !== "undefined") feather.replace();
    });
</script>
