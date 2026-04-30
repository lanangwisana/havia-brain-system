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

        <?php 
        $media_type = !empty($model_info->youtube_link) ? 'youtube' : 'image';
        ?>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Media Type</label>
                <div class="col-md-9">
                    <?php
                    $options = [
                        'image' => 'Image / Logo',
                        'youtube' => 'YouTube Video'
                    ];
                    echo form_dropdown('media_type', $options, $media_type, 'id="media_type" class="form-control" style="appearance:auto; -webkit-appearance:menulist; -moz-appearance:menulist; cursor:pointer; padding-right:30px;"'); 
                    ?>
                </div>
            </div>
        </div>

        <?php if ($has_image): ?>
        <div class="form-group" id="media_current_image_group">
            <div class="row">
                <label class="col-md-3">Current Media</label>
                <div class="col-md-9">
                    <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($model_info->image, 'testimonials'); ?>" style="max-height:60px;" />
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group" id="media_image_group">
            <div class="row">
                <label class="col-md-3">Upload Image / Logo</label>
                <div class="col-md-9">
                    <input type="file" name="image" class="form-control" accept="image/*" />
                    <small class="text-muted">For YouTube, this will be displayed as the company logo.</small>
                </div>
            </div>
        </div>

        <div class="form-group" id="media_youtube_group" style="<?php echo $media_type === 'youtube' ? '' : 'display:none;'; ?>">
            <div class="row">
                <label class="col-md-3">YouTube Link</label>
                <div class="col-md-9">
                    <?php echo form_input(array("name" => "youtube_link", "value" => $model_info->youtube_link ?? '', "class" => "form-control", "placeholder" => "e.g. https://www.youtube.com/watch?v=...")); ?>
                    <small class="text-muted">The testimonial will feature a playable video with the logo above.</small>
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

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> Close</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save</button>
</div>
</form>

<script type="text/javascript">
$(document).ready(function() {
    $("#media_type").change(function() {
        if ($(this).val() === 'youtube') {
            $("#media_youtube_group").show();
        } else {
            $("#media_youtube_group").hide();
        }
    });

    $("#testimonial-form").on("submit", function(e) {
        e.preventDefault();

        // Prevent youtube_link from being saved if image is selected as media_type
        if ($("#media_type").val() === 'image') {
            $("input[name='youtube_link']").val("");
        }

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
                    $("#trust-tab").html("");
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
