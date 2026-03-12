<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "trust-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_accent" class=" col-md-2">Accent Label</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_trust_accent",
                    "name" => "landingpage_trust_accent",
                    "value" => get_setting('landingpage_trust_accent') ? get_setting('landingpage_trust_accent') : 'Testimonial',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_h2" class=" col-md-2">Heading</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_trust_h2",
                    "name" => "landingpage_trust_h2",
                    "value" => get_setting('landingpage_trust_h2') ? get_setting('landingpage_trust_h2') : 'Trusted By Clients',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr/>
    <p class="text-muted mb-3"><strong>Testimonials</strong> — Tambah/edit testimoni klien</p>

    <div class="form-group">
        <div class="row">
            <label class="col-md-2">Testimonial Items</label>
            <div class="col-md-10">
                <div id="testimonial-items-container" class="mb-3"></div>
                <button type="button" id="add-testimonial-item" class="btn btn-default btn-sm"><span data-feather="plus-circle" class="icon-16"></span> Add Testimonial</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="landingpage_trust_testimonials_json" name="landingpage_trust_testimonials_json" value="<?php echo htmlspecialchars(get_setting("landingpage_trust_testimonials_json") ?: "[]", ENT_QUOTES); ?>" />

    <hr/>
    <p class="text-muted mb-3"><strong>Client Logo Carousel</strong></p>

    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_client_heading" class=" col-md-2">Client Heading</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_trust_client_heading",
                    "name" => "landingpage_trust_client_heading",
                    "value" => get_setting('landingpage_trust_client_heading') ? get_setting('landingpage_trust_client_heading') : 'Our Clients',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label class="col-md-2">Client Logos</label>
            <div class="col-md-10">
                <div id="client-items-container" class="mb-3"></div>
                <button type="button" id="add-client-item" class="btn btn-default btn-sm"><span data-feather="plus-circle" class="icon-16"></span> Add Client Logo</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="landingpage_trust_clients_json" name="landingpage_trust_clients_json" value="<?php echo htmlspecialchars(get_setting("landingpage_trust_clients_json") ?: "[]", ENT_QUOTES); ?>" />

    <hr/>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_trust_footer_text" class=" col-md-2">Footer Text</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_trust_footer_text",
                    "name" => "landingpage_trust_footer_text",
                    "value" => get_setting('landingpage_trust_footer_text') ? get_setting('landingpage_trust_footer_text') : '— And still counting —',
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

<!-- Testimonial Item Template -->
<div id="testimonial-item-template" style="display:none;">
    <div class="testimonial-item-row bg-light p-3 mb-3 border rounded shadow-sm" style="position:relative;">
        <button type="button" class="btn btn-sm btn-danger remove-testimonial" style="position:absolute; top:10px; right:10px; border-radius:50%; padding:0 6px;">&times;</button>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="small font-weight-bold">Client/Company Name</label>
                <input type="text" class="form-control item-name" placeholder="e.g. Edelweiss Hospital" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Location</label>
                <input type="text" class="form-control item-role" placeholder="e.g. Bandung" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Logo/Image Path</label>
                <input type="text" class="form-control item-image" placeholder="/logo-client-1.png" />
            </div>
            <div class="col-md-12 mb-2">
                <label class="small font-weight-bold">Quote / Testimonial</label>
                <textarea class="form-control item-quote" rows="2" placeholder="Testimonial quote..."></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Client Logo Item Template -->
<div id="client-item-template" style="display:none;">
    <div class="client-item-row bg-light p-2 mb-2 border rounded d-flex align-items-center gap-2" style="position:relative;">
        <input type="text" class="form-control form-control-sm item-logo-path" placeholder="/logo-client-1.png" style="flex:1;" />
        <button type="button" class="btn btn-sm btn-danger remove-client" style="border-radius:50%; padding:0 6px;">&times;</button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        // ========== TESTIMONIALS REPEATER ==========
        var $tContainer = $("#testimonial-items-container");
        var tTemplate = $("#testimonial-item-template").html();
        var $tJson = $("#landingpage_trust_testimonials_json");

        var tItems = [];
        try { var v = $tJson.val(); if (v && v !== "[]") tItems = JSON.parse(v); } catch(e) {}

        function renderTestimonials() {
            $tContainer.empty();
            if (tItems.length === 0) {
                $tContainer.append('<p class="text-muted" style="font-style:italic;">No testimonials. Click "Add Testimonial" to start.</p>');
                return;
            }
            tItems.forEach(function(item, i) {
                var $row = $(tTemplate);
                $row.find(".item-name").val(item.name || "");
                $row.find(".item-role").val(item.role || "");
                $row.find(".item-image").val(item.image || "");
                $row.find(".item-quote").val(item.quote || "");
                $row.attr("data-index", i);
                $tContainer.append($row);
            });
        }

        function syncTestimonials() {
            var arr = [];
            $tContainer.find(".testimonial-item-row").each(function(i) {
                var $r = $(this);
                arr.push({
                    id: i + 1,
                    name: $r.find(".item-name").val(),
                    role: $r.find(".item-role").val(),
                    image: $r.find(".item-image").val(),
                    quote: $r.find(".item-quote").val()
                });
            });
            $tJson.val(JSON.stringify(arr));
        }

        renderTestimonials();

        $("#add-testimonial-item").click(function() {
            tItems.push({ name: "", role: "", image: "", quote: "" });
            renderTestimonials();
            syncTestimonials();
        });

        $tContainer.on("click", ".remove-testimonial", function() {
            var idx = $(this).closest(".testimonial-item-row").data("index");
            tItems.splice(idx, 1);
            renderTestimonials();
            syncTestimonials();
        });

        $tContainer.on("input change", "input, textarea", function() { syncTestimonials(); });

        // ========== CLIENT LOGOS REPEATER ==========
        var $cContainer = $("#client-items-container");
        var cTemplate = $("#client-item-template").html();
        var $cJson = $("#landingpage_trust_clients_json");

        var cItems = [];
        try { var cv = $cJson.val(); if (cv && cv !== "[]") cItems = JSON.parse(cv); } catch(e) {}

        function renderClients() {
            $cContainer.empty();
            if (cItems.length === 0) {
                $cContainer.append('<p class="text-muted" style="font-style:italic;">No client logos. Click "Add Client Logo" to start.</p>');
                return;
            }
            cItems.forEach(function(item, i) {
                var $row = $(cTemplate);
                var logoVal = (typeof item === "string") ? item : (item.image || "");
                $row.find(".item-logo-path").val(logoVal);
                $row.attr("data-index", i);
                $cContainer.append($row);
            });
        }

        function syncClients() {
            var arr = [];
            $cContainer.find(".client-item-row").each(function() {
                arr.push($(this).find(".item-logo-path").val());
            });
            $cJson.val(JSON.stringify(arr));
        }

        renderClients();

        $("#add-client-item").click(function() {
            cItems.push("");
            renderClients();
            syncClients();
        });

        $cContainer.on("click", ".remove-client", function() {
            var idx = $(this).closest(".client-item-row").data("index");
            cItems.splice(idx, 1);
            renderClients();
            syncClients();
        });

        $cContainer.on("input change", "input", function() { syncClients(); });

        // ========== FORM SUBMIT ==========
        $("#trust-settings-form").appForm({
            isModal: false,
            onBeforePost: function() {
                syncTestimonials();
                syncClients();
            },
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });

        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
