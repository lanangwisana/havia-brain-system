<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "about-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <h5 class="mb-3">About Section</h5>
    <small class="text-muted d-block mb-3">This info is shared between the homepage about section and the "Meet Our Team" page header.</small>

    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_accent" class="col-md-2">Heading (H1)</label>
            <div class="col-md-10">
                <?php echo form_input(array("id" => "landingpage_about_accent", "name" => "landingpage_about_accent", "value" => get_setting('landingpage_about_accent') ?: 'About Havia', "class" => "form-control")); ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_h2" class="col-md-2">Subheading (H2)</label>
            <div class="col-md-10">
                <?php echo form_input(array("id" => "landingpage_about_h2", "name" => "landingpage_about_h2", "value" => get_setting('landingpage_about_h2') ?: 'Architecture Rooted in Clarity and Craft.', "class" => "form-control")); ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_p1" class="col-md-2">Description 1</label>
            <div class="col-md-10">
                <?php echo form_textarea(array("id" => "landingpage_about_p1", "name" => "landingpage_about_p1", "value" => get_setting('landingpage_about_p1') ?: '', "class" => "form-control", "style" => "height: 80px;")); ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_p2" class="col-md-2">Description 2</label>
            <div class="col-md-10">
                <?php echo form_textarea(array("id" => "landingpage_about_p2", "name" => "landingpage_about_p2", "value" => get_setting('landingpage_about_p2') ?: '', "class" => "form-control", "style" => "height: 80px;")); ?>
            </div>
        </div>
    </div>

    <div class="row mt15">
        <div class="col-md-6">
            <div class="form-group"><div class="row">
                <label class="col-md-4">Stat 1 Value</label>
                <div class="col-md-8"><?php echo form_input(array("name" => "landingpage_about_stat1_val", "value" => get_setting('landingpage_about_stat1_val') ?: '120+', "class" => "form-control")); ?></div>
            </div></div>
            <div class="form-group"><div class="row">
                <label class="col-md-4">Stat 1 Label</label>
                <div class="col-md-8"><?php echo form_input(array("name" => "landingpage_about_stat1_label", "value" => get_setting('landingpage_about_stat1_label') ?: 'Projects Completed', "class" => "form-control")); ?></div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="form-group"><div class="row">
                <label class="col-md-4">Stat 2 Value</label>
                <div class="col-md-8"><?php echo form_input(array("name" => "landingpage_about_stat2_val", "value" => get_setting('landingpage_about_stat2_val') ?: '10', "class" => "form-control")); ?></div>
            </div></div>
            <div class="form-group"><div class="row">
                <label class="col-md-4">Stat 2 Label</label>
                <div class="col-md-8"><?php echo form_input(array("name" => "landingpage_about_stat2_label", "value" => get_setting('landingpage_about_stat2_label') ?: 'Years of Practice', "class" => "form-control")); ?></div>
            </div></div>
        </div>
    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save Text</button>
</div>
<?php echo form_close(); ?>

<hr/>

<!-- About Image Upload -->
<div class="card-body">
    <h6 class="mb-3">About Image (max 1)</h6>
    <?php
    $current_image = get_setting('landingpage_about_image');
    if ($current_image): ?>
        <div class="mb-3">
            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($current_image, 'about'); ?>" style="max-height:200px; border-radius:8px;" />
        </div>
    <?php endif; ?>

    <form id="about-image-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="d-flex align-items-end gap-2">
            <input type="file" name="about_image" class="form-control" accept="image/*" style="max-width:400px;" />
            <button type="submit" class="btn btn-primary btn-sm"><span data-feather="upload" class="icon-16"></span> Upload</button>
        </div>
    </form>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#about-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });

        $("#about-image-form").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "<?php echo get_uri('landingpage_cms/save_about_image'); ?>",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message, {duration: 10000});
                        $("[data-bs-target='#about-tab']").trigger("click");
                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });

        if (typeof feather !== 'undefined') feather.replace();
    });
</script>