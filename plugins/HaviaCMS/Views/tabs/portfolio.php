<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "portfolio-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_accent" class=" col-md-2">Accent Title</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_portfolio_accent",
                    "name" => "landingpage_portfolio_accent",
                    "value" => get_setting('landingpage_portfolio_accent') ? get_setting('landingpage_portfolio_accent') : 'SELECTED WORKS',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_h2" class=" col-md-2">Heading 2</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_portfolio_h2",
                    "name" => "landingpage_portfolio_h2",
                    "value" => get_setting('landingpage_portfolio_h2') ? get_setting('landingpage_portfolio_h2') : 'Exploring the Intersection of Form & Utility.',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_categories" class=" col-md-2">Categories (CSV)</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_portfolio_categories",
                    "name" => "landingpage_portfolio_categories",
                    "value" => get_setting('landingpage_portfolio_categories') ? get_setting('landingpage_portfolio_categories') : 'All,Residential,Commercial,Interior',
                    "class" => "form-control",
                    "placeholder" => "e.g. All,Residential,Commercial"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_json" class=" col-md-2">Portfolio Items (JSON)</label>
            <div class=" col-md-10">
                <?php
                echo form_textarea(array(
                    "id" => "landingpage_portfolio_json",
                    "name" => "landingpage_portfolio_json",
                    "value" => get_setting('landingpage_portfolio_json') ? get_setting('landingpage_portfolio_json') : '[{"title":"Casa de Rosa","subtitle":"Private Residence","category":"Residential","img":"/havia-project-1.jpg"},{"title":"The Minimalist","subtitle":"Small Office","category":"Commercial","img":"/havia-project-2.jpg"},{"title":"Modern Aesthetic Clinic","subtitle":"Healthcare Interior","category":"Interior","img":"/havia-project-3.jpg"}]',
                    "class" => "form-control",
                    "style" => "height: 200px;"
                ));
                ?>
                <span class="text-off" style="font-size: 11px;">Format: [{"title":"..", "subtitle":"..", "category":"..", "img":".."}]</span>
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
        $("#portfolio-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
