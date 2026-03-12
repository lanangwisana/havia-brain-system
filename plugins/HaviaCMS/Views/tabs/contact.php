<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "contact-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_h2" class=" col-md-2">Heading 2</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_h2",
                    "name" => "landingpage_contact_h2",
                    "value" => get_setting('landingpage_contact_h2') ? get_setting('landingpage_contact_h2') : "Let's build something exceptional.",
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_p" class=" col-md-2">Description</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_p",
                    "name" => "landingpage_contact_p",
                    "value" => get_setting('landingpage_contact_p') ? get_setting('landingpage_contact_p') : 'Ready to start your next project? Get in touch with our team for a consultation.',
                    "class" => "form-control",
                    "style" => "height: 80px;"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_email" class=" col-md-2">Contact Email</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_email",
                    "name" => "landingpage_contact_email",
                    "value" => get_setting('landingpage_contact_email') ? get_setting('landingpage_contact_email') : 'hello@havia.studio',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_phone" class=" col-md-2">Contact Phone</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_phone",
                    "name" => "landingpage_contact_phone",
                    "value" => get_setting('landingpage_contact_phone') ? get_setting('landingpage_contact_phone') : '+62 812 XXXX XXXX',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_address" class=" col-md-2">Address</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_address",
                    "name" => "landingpage_contact_address",
                    "value" => get_setting('landingpage_contact_address') ? get_setting('landingpage_contact_address') : 'Bandung, Indonesia',
                    "class" => "form-control",
                    "style" => "height: 80px;"
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
        $("#contact-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
