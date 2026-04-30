<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "contact-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <p class="text-muted mb-3"><strong>Brand & Tagline</strong></p>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_p" class=" col-md-2">Brand Tagline</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_p",
                    "name" => "landingpage_contact_p",
                    "value" => get_setting('landingpage_contact_p') ? get_setting('landingpage_contact_p') : 'Partner terpercaya untuk merancang dan membangun masa depan Anda.',
                    "class" => "form-control",
                    "style" => "height: 60px;"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr/>
    <p class="text-muted mb-3"><strong>Social Media</strong></p>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_instagram" class=" col-md-2">Instagram URL</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_instagram",
                    "name" => "landingpage_contact_instagram",
                    "value" => get_setting('landingpage_contact_instagram') ? get_setting('landingpage_contact_instagram') : 'https://www.instagram.com/studiohavia/',
                    "class" => "form-control",
                    "placeholder" => "https://www.instagram.com/studiohavia/"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_linkedin" class=" col-md-2">LinkedIn URL</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_linkedin",
                    "name" => "landingpage_contact_linkedin",
                    "value" => get_setting('landingpage_contact_linkedin') ? get_setting('landingpage_contact_linkedin') : 'https://www.linkedin.com/company/havia-studio/',
                    "class" => "form-control",
                    "placeholder" => "https://www.linkedin.com/company/havia-studio/"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr/>
    <p class="text-muted mb-3"><strong>Alamat & Kontak</strong></p>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_address" class=" col-md-2">Alamat Lengkap</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_contact_address",
                    "name" => "landingpage_contact_address",
                    "value" => get_setting('landingpage_contact_address') ? get_setting('landingpage_contact_address') : 'Jl. Sulaksana Baru III No.20, Cicaheum, Kec. Kiaracondong, Kota Bandung',
                    "class" => "form-control",
                    "style" => "height: 60px;"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_maps_url" class=" col-md-2">Google Maps URL</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_maps_url",
                    "name" => "landingpage_contact_maps_url",
                    "value" => get_setting('landingpage_contact_maps_url') ? get_setting('landingpage_contact_maps_url') : 'https://maps.app.goo.gl/WgGBhZU66tGKscuA7',
                    "class" => "form-control",
                    "placeholder" => "https://maps.app.goo.gl/..."
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_phone" class=" col-md-2">Telepon</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_phone",
                    "name" => "landingpage_contact_phone",
                    "value" => get_setting('landingpage_contact_phone') ? get_setting('landingpage_contact_phone') : '+62 811 2430 121',
                    "class" => "form-control"
                ));
                ?>
            </div>
            <label for="landingpage_contact_email" class=" col-md-2">Email</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_email",
                    "name" => "landingpage_contact_email",
                    "value" => get_setting('landingpage_contact_email') ? get_setting('landingpage_contact_email') : 'haviastudio@gmail.com',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr/>
    <p class="text-muted mb-3"><strong>Jam Operasional</strong></p>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_hours_weekday" class=" col-md-2">Hari Kerja</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_hours_weekday",
                    "name" => "landingpage_contact_hours_weekday",
                    "value" => get_setting('landingpage_contact_hours_weekday') ? get_setting('landingpage_contact_hours_weekday') : 'Senin - Jumat / 08:00 - 17:00',
                    "class" => "form-control",
                    "placeholder" => "Senin - Jumat / 08:00 - 17:00"
                ));
                ?>
            </div>
            <label for="landingpage_contact_hours_weekend" class=" col-md-2">Akhir Pekan</label>
            <div class=" col-md-4">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_hours_weekend",
                    "name" => "landingpage_contact_hours_weekend",
                    "value" => get_setting('landingpage_contact_hours_weekend') ? get_setting('landingpage_contact_hours_weekend') : 'Sabtu - Minggu / Tutup',
                    "class" => "form-control",
                    "placeholder" => "Sabtu - Minggu / Tutup"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr/>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_contact_copyright" class=" col-md-2">Copyright Text</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_contact_copyright",
                    "name" => "landingpage_contact_copyright",
                    "value" => get_setting('landingpage_contact_copyright') ? get_setting('landingpage_contact_copyright') : '© 2026 Havia Studio. All rights reserved.',
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
        $("#contact-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>