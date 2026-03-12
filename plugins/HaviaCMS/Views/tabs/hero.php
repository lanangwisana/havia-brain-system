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
                    "value" => get_setting('landingpage_hero_label') ? get_setting('landingpage_hero_label') : 'Architecture Studio, Indonesia',
                    "class" => "form-control",
                    "placeholder" => "e.g. Architecture Studio, Indonesia"
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
                    "value" => get_setting('landingpage_hero_h1') ? get_setting('landingpage_hero_h1') : 'Creating Space.',
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
                    "value" => get_setting('landingpage_hero_h2') ? get_setting('landingpage_hero_h2') : 'Facing the Future.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_h3" class=" col-md-2">Heading 3</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_h3",
                    "name" => "landingpage_hero_h3",
                    "value" => get_setting('landingpage_hero_h3') ? get_setting('landingpage_hero_h3') : 'Designing a Good Life.',
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
                    "value" => get_setting('landingpage_hero_p') ? get_setting('landingpage_hero_p') : 'Havia Studio berkomitmen penuh membantu, melayani, dan mewujudkan desain sesuai kebutuhan dan harapan.',
                    "class" => "form-control",
                    "style" => "height: 100px;"
                ));
                ?>
            </div>
        </div>
    </div>
    <hr/>
    <p class="text-muted mb-3"><strong>Button Text</strong></p>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_hero_btn1" class=" col-md-2">Primary Button</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_btn1",
                    "name" => "landingpage_hero_btn1",
                    "value" => get_setting('landingpage_hero_btn1') ? get_setting('landingpage_hero_btn1') : 'Contact Us',
                    "class" => "form-control"
                ));
                ?>
            </div>
            <label for="landingpage_hero_btn2" class=" col-md-2">Secondary Button</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_hero_btn2",
                    "name" => "landingpage_hero_btn2",
                    "value" => get_setting('landingpage_hero_btn2') ? get_setting('landingpage_hero_btn2') : 'View Portfolio',
                    "class" => "form-control"
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
