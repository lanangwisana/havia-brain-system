<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "whatsapp-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_whatsapp_phone" class=" col-md-2">WhatsApp Number</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_whatsapp_phone",
                    "name" => "landingpage_whatsapp_phone",
                    "value" => get_setting('landingpage_whatsapp_phone') ? get_setting('landingpage_whatsapp_phone') : '628112430121',
                    "class" => "form-control",
                    "placeholder" => "e.g. 628112430121 (use country code without +)"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_whatsapp_label" class=" col-md-2">Button Label</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_whatsapp_label",
                    "name" => "landingpage_whatsapp_label",
                    "value" => get_setting('landingpage_whatsapp_label') ? get_setting('landingpage_whatsapp_label') : 'Chat WhatsApp',
                    "class" => "form-control",
                    "placeholder" => "e.g. Chat WhatsApp"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_whatsapp_message" class=" col-md-2">Default Message</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_whatsapp_message",
                    "name" => "landingpage_whatsapp_message",
                    "value" => get_setting('landingpage_whatsapp_message') ? get_setting('landingpage_whatsapp_message') : 'Hello Havia Studio, I am interested in your services.',
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
        $("#whatsapp-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>