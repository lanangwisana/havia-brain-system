<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "about-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_accent" class=" col-md-2">Accent Title</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_about_accent",
                    "name" => "landingpage_about_accent",
                    "value" => get_setting('landingpage_about_accent') ? get_setting('landingpage_about_accent') : 'HAVIA STUDIO',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_h2" class=" col-md-2">Heading 2</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_about_h2",
                    "name" => "landingpage_about_h2",
                    "value" => get_setting('landingpage_about_h2') ? get_setting('landingpage_about_h2') : 'We design for the present, build for the future.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_p1" class=" col-md-2">Paragraph 1</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_about_p1",
                    "name" => "landingpage_about_p1",
                    "value" => get_setting('landingpage_about_p1') ? get_setting('landingpage_about_p1') : 'Havia Studio is an Indonesian architecture firm based in Bandung. Established with a commitment to contemporary design and structural excellence.',
                    "class" => "form-control",
                    "style" => "height: 80px;"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_about_p2" class=" col-md-2">Paragraph 2</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_about_p2",
                    "name" => "landingpage_about_p2",
                    "value" => get_setting('landingpage_about_p2') ? get_setting('landingpage_about_p2') : 'We prioritize the dialogue between space, materiality, and environment—ensuring every project becomes a timeless addition to the landscape.',
                    "class" => "form-control",
                    "style" => "height: 80px;"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="row mt15">
        <div class="col-md-6">
            <div class="form-group">
                <div class="row">
                    <label for="landingpage_about_stat1_val" class=" col-md-4">Stat 1 Value</label>
                    <div class=" col-md-8">
                        <?php
                        echo form_input(array(
                            "id" => "landingpage_about_stat1_val",
                            "name" => "landingpage_about_stat1_val",
                            "value" => get_setting('landingpage_about_stat1_val') ? get_setting('landingpage_about_stat1_val') : '10+',
                            "class" => "form-control"
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="landingpage_about_stat1_label" class=" col-md-4">Stat 1 Label</label>
                    <div class=" col-md-8">
                        <?php
                        echo form_input(array(
                            "id" => "landingpage_about_stat1_label",
                            "name" => "landingpage_about_stat1_label",
                            "value" => get_setting('landingpage_about_stat1_label') ? get_setting('landingpage_about_stat1_label') : 'Years of Experience',
                            "class" => "form-control"
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="row">
                    <label for="landingpage_about_stat2_val" class=" col-md-4">Stat 2 Value</label>
                    <div class=" col-md-8">
                        <?php
                        echo form_input(array(
                            "id" => "landingpage_about_stat2_val",
                            "name" => "landingpage_about_stat2_val",
                            "value" => get_setting('landingpage_about_stat2_val') ? get_setting('landingpage_about_stat2_val') : '50+',
                            "class" => "form-control"
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="landingpage_about_stat2_label" class=" col-md-4">Stat 2 Label</label>
                    <div class=" col-md-8">
                        <?php
                        echo form_input(array(
                            "id" => "landingpage_about_stat2_label",
                            "name" => "landingpage_about_stat2_label",
                            "value" => get_setting('landingpage_about_stat2_label') ? get_setting('landingpage_about_stat2_label') : 'Completed Projects',
                            "class" => "form-control"
                        ));
                        ?>
                    </div>
                </div>
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
        $("#about-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
