<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "hero-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_label" class=" col-md-2">Small Label</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_label",
                    "name" => "landingpage_hero_label",
                    "value" => get_setting('landingpage_hero_label') ? get_setting('landingpage_hero_label') : 'Architecture & Build Studio, Bandung',
                    "class" => "form-control",
                    "placeholder" => "e.g. Architecture & Build Studio, Bandung"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_h1" class=" col-md-2">Heading 1</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_h1",
                    "name" => "landingpage_hero_h1",
                    "value" => get_setting('landingpage_hero_h1') ? get_setting('landingpage_hero_h1') : 'Designing Space.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_h2" class=" col-md-2">Heading 2 (Italic)</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_h2",
                    "name" => "landingpage_hero_h2",
                    "value" => get_setting('landingpage_hero_h2') ? get_setting('landingpage_hero_h2') : 'Building Legacy.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_p" class=" col-md-2">Paragraph</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_hero_p",
                    "name" => "landingpage_hero_p",
                    "value" => get_setting('landingpage_hero_p') ? get_setting('landingpage_hero_p') : 'We craft refined architectural concepts and execute them with precision—bringing residential and commercial visions to life.',
                    "class" => "form-control",
                    "style" => "height: 100px;"
                ));
                ?>
            </div>
        </div>
    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save</button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#hero-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
