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
                    "value" => get_setting('landingpage_contact_h2') ? get_setting('landingpage_contact_h2') : 'Lets talk about your project',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_p" class=" col-md-2">Sub Heading</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_p",
                    "name" => "landingpage_contact_p",
                    "value" => get_setting('landingpage_contact_p') ? get_setting('landingpage_contact_p') : 'At Havia Studio, we believe that great architecture begins with a conversation. Fill out the form, and our lead architect will get back to you within 24 hours.',
                    "class" => "form-control",
                    "style" => "height: 80px;"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_email" class=" col-md-2">Studio Email</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_email",
                    "name" => "landingpage_contact_email",
                    "value" => get_setting('landingpage_contact_email') ? get_setting('landingpage_contact_email') : 'hello@haviastudio.com',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_phone" class=" col-md-2">Studio Phone</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_phone",
                    "name" => "landingpage_contact_phone",
                    "value" => get_setting('landingpage_contact_phone') ? get_setting('landingpage_contact_phone') : '+62 821-2678-4333',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_address" class=" col-md-2">Studio Address</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_address",
                    "name" => "landingpage_contact_address",
                    "value" => get_setting('landingpage_contact_address') ? get_setting('landingpage_contact_address') : "Kawasan Summarecon, Ruko Magna Commercial No. 80\nKota Bandung, Jawa Barat 40295",
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
