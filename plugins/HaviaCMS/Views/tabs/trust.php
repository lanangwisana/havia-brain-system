<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "trust-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_h2" class=" col-md-2">Heading 2</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_trust_h2",
                    "name" => "landingpage_trust_h2",
                    "value" => get_setting('landingpage_trust_h2') ? get_setting('landingpage_trust_h2') : 'Trusted by Visionaries.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_p" class=" col-md-2">Description</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_trust_p",
                    "name" => "landingpage_trust_p",
                    "value" => get_setting('landingpage_trust_p') ? get_setting('landingpage_trust_p') : 'We collaborate with developers, private homeowners, and corporate clients to translate complex requirements into exceptional structures.',
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
        $("#trust-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
