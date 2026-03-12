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
                    "value" => get_setting('landingpage_portfolio_accent') ? get_setting('landingpage_portfolio_accent') : 'Portfolio',
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
                    "value" => get_setting('landingpage_portfolio_h2') ? get_setting('landingpage_portfolio_h2') : 'Selected Works',
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
                    "value" => get_setting('landingpage_portfolio_categories') ? get_setting('landingpage_portfolio_categories') : 'All,Private,Public,Masterplan',
                    "class" => "form-control",
                    "placeholder" => "e.g. All,Private,Public,Masterplan"
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_download_text" class=" col-md-2">Download Button</label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "landingpage_portfolio_download_text",
                    "name" => "landingpage_portfolio_download_text",
                    "value" => get_setting('landingpage_portfolio_download_text') ? get_setting('landingpage_portfolio_download_text') : 'Download Portfolio',
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>

    <hr />
    
    <div class="form-group">
        <div class="row">
            <label class="col-md-2">Portfolio Items</label>
            <div class="col-md-10">
                <div id="portfolio-items-container" class="mb-3">
                    <!-- Items will be rendered here -->
                </div>
                <button type="button" id="add-portfolio-item" class="btn btn-default btn-sm"><span data-feather="plus-circle" class="icon-16"></span> Add Item</button>
            </div>
        </div>
    </div>

    <!-- Hidden JSON Field -->
    <input type="hidden" id="landingpage_portfolio_json" name="landingpage_portfolio_json" value="<?php echo htmlspecialchars(get_setting("landingpage_portfolio_json") ?: "[]", ENT_QUOTES); ?>" />
</div>

<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save All Changes</button>
</div>
<?php echo form_close(); ?>

<!-- Template for list items -->
<div id="portfolio-item-template" style="display:none;">
    <div class="portfolio-item-row bg-light p-3 mb-3 border rounded shadow-sm" style="position:relative;">
        <button type="button" class="btn btn-sm btn-danger remove-item" style="position:absolute; top:10px; right:10px; border-radius:50%; padding:0 6px;">&times;</button>
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Title</label>
                <input type="text" class="form-control item-title" placeholder="Project Title" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Location</label>
                <input type="text" class="form-control item-location" placeholder="e.g. Bandung" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Category</label>
                <input type="text" class="form-control item-category" placeholder="e.g. Private" />
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold">Year</label>
                <input type="text" class="form-control item-year" placeholder="2024" />
            </div>
            <div class="col-md-4 mb-2">
                <label class="small font-weight-bold">Main Image URL</label>
                <input type="text" class="form-control item-image" placeholder="/havia-project-5.jpg" />
            </div>
            <div class="col-md-4 mb-2">
                <label class="small font-weight-bold">Client</label>
                <input type="text" class="form-control item-client" placeholder="Client Name" />
            </div>
            <div class="col-md-4 mb-2">
                <label class="small font-weight-bold">Scope (comma separated)</label>
                <input type="text" class="form-control item-scope" placeholder="Architecture, Interior" />
            </div>
            <div class="col-md-8 mb-2">
                <label class="small font-weight-bold">Story</label>
                <textarea class="form-control item-story" rows="2" placeholder="Brief description..."></textarea>
            </div>
            <div class="col-md-4 mb-2">
                <label class="small font-weight-bold">Gallery Images (comma separated URLs)</label>
                <textarea class="form-control item-images" rows="2" placeholder="/img1.jpg, /img2.jpg"></textarea>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $container = $("#portfolio-items-container");
        var $template = $("#portfolio-item-template").html();
        var $jsonField = $("#landingpage_portfolio_json");

        // Parse initial items
        var items = [];
        try {
            var val = $jsonField.val();
            if (val && val !== "[]") {
                items = JSON.parse(val);
            }
        } catch (e) {
            console.error("Failed to parse portfolio JSON", e);
        }

        function renderItems() {
            $container.empty();
            if (items.length === 0) {
                $container.append('<p class="text-muted" style="font-style:italic;">No items added yet. Click "Add Item" to start.</p>');
                return;
            }

            items.forEach(function(item, index) {
                var $row = $($template);
                $row.find(".item-title").val(item.title || "");
                $row.find(".item-location").val(item.location || "");
                $row.find(".item-category").val(item.category || "");
                $row.find(".item-image").val(item.image || "");
                $row.find(".item-year").val(item.year || "");
                $row.find(".item-client").val(item.client || "");
                $row.find(".item-scope").val((item.scope || []).join(", "));
                $row.find(".item-story").val(item.story || "");
                $row.find(".item-images").val((item.images || []).join(", "));
                $row.attr("data-index", index);
                $container.append($row);
            });
        }

        function syncJson() {
            var newItems = [];
            $container.find(".portfolio-item-row").each(function() {
                var $row = $(this);
                var scopeVal = $row.find(".item-scope").val();
                var imagesVal = $row.find(".item-images").val();
                newItems.push({
                    id: Date.now() + Math.floor(Math.random() * 1000),
                    title: $row.find(".item-title").val(),
                    location: $row.find(".item-location").val(),
                    category: $row.find(".item-category").val(),
                    image: $row.find(".item-image").val(),
                    year: $row.find(".item-year").val(),
                    client: $row.find(".item-client").val(),
                    scope: scopeVal ? scopeVal.split(",").map(function(s){ return s.trim(); }) : [],
                    story: $row.find(".item-story").val(),
                    images: imagesVal ? imagesVal.split(",").map(function(s){ return s.trim(); }) : [$row.find(".item-image").val()]
                });
            });
            $jsonField.val(JSON.stringify(newItems));
        }

        // Initial render
        renderItems();

        // Events
        $("#add-portfolio-item").click(function() {
            items.push({ title: "", location: "", category: "", image: "", year: "", client: "", scope: [], story: "", images: [] });
            renderItems();
            syncJson();
        });

        $container.on("click", ".remove-item", function() {
            var index = $(this).closest(".portfolio-item-row").data("index");
            items.splice(index, 1);
            renderItems();
            syncJson();
        });

        $container.on("input change", "input, textarea", function() {
            syncJson();
        });

        $("#portfolio-settings-form").appForm({
            isModal: false,
            onBeforePost: function() {
                syncJson();
            },
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
