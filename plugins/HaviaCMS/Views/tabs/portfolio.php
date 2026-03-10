<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "portfolio-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="landingpage_portfolio_accent" class=" col-md-2">Accent Label</label>
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
            <label class=" col-md-2">Categories</label>
            <div class=" col-md-10">
                <input type="hidden" id="landingpage_portfolio_categories" name="landingpage_portfolio_categories" value="<?php echo get_setting('landingpage_portfolio_categories') ? get_setting('landingpage_portfolio_categories') : 'All, Residential, Healthcare, Commercial, Corporate'; ?>" />
                <div id="categories-list-container"></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-category-btn"><i data-feather="plus" class="icon-14"></i> Add Category</button>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <div class="row">
            <label class="col-md-2">Projects</label>
            <div class="col-md-10">
                <div id="projects-container"></div>
                
                <textarea id="landingpage_portfolio_json" name="landingpage_portfolio_json" class="hide" style="display:none;"><?php echo get_setting('landingpage_portfolio_json'); ?></textarea>
                
                <button type="button" class="btn btn-default mt15" id="add-project-btn"><i data-feather="plus" class="icon-16"></i> Add Work</button>
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
        
        var jsonField = $("#landingpage_portfolio_json");
        var container = $("#projects-container");
        var initialData = [];
        
        try {
            var val = jsonField.val();
            if(val) {
                initialData = JSON.parse(val);
            }
        } catch(e) { console.error("Invalid JSON in portfolio"); }
        var catHiddenField = $("#landingpage_portfolio_categories");
        var catContainer = $("#categories-list-container");
        
        function getCategoriesArray() {
            var catStr = catHiddenField.val() || "All";
            return catStr.split(",").map(function(c){ return c.trim(); }).filter(function(c) { return c.toLowerCase() !== "all" && c !== ""; });
        }
        
        function updateHiddenCategories() {
            var cats = [];
            catContainer.find(".p-category-val").each(function() {
                var v = $(this).val().trim();
                if (v && v.toLowerCase() !== "all") {
                    cats.push(v);
                }
            });
            catHiddenField.val("All, " + cats.join(", "));
            
            var newCatOptions = getCategoriesArray();
            $(".p-category").each(function() {
                var select = $(this);
                var currentVal = select.val();
                select.empty();
                select.append('<option value="">- Select Category -</option>');
                for(var c=0; c < newCatOptions.length; c++) {
                    var selected = (currentVal === newCatOptions[c]) ? 'selected' : '';
                    select.append('<option value="'+newCatOptions[c]+'" '+selected+'>'+newCatOptions[c]+'</option>');
                }
            });
            updateJSON();
        }
        
        function renderCategoriesList() {
            var cats = getCategoriesArray();
            catContainer.empty();
            cats.forEach(function(c) {
                var html = '<div class="input-group mb-2 category-input-row">';
                html += '<input type="text" class="form-control p-category-val" value="'+c+'" placeholder="e.g. Residential" />';
                html += '<div class="input-group-append"><button type="button" class="btn btn-danger remove-category-btn"><i data-feather="trash-2" class="icon-16"></i></button></div>';
                html += '</div>';
                catContainer.append(html);
            });
            feather.replace();
        }
        
        renderCategoriesList();
        
        $("#add-category-btn").click(function() {
            var html = '<div class="input-group mb-2 category-input-row">';
            html += '<input type="text" class="form-control p-category-val" value="" placeholder="e.g. Interior" />';
            html += '<div class="input-group-append"><button type="button" class="btn btn-danger remove-category-btn"><i data-feather="trash-2" class="icon-16"></i></button></div>';
            html += '</div>';
            catContainer.append(html);
            feather.replace();
            updateHiddenCategories();
        });
        
        catContainer.on("click", ".remove-category-btn", function() {
            $(this).closest(".category-input-row").remove();
            updateHiddenCategories();
        });
        
        catContainer.on("change keyup", ".p-category-val", function() {
            updateHiddenCategories();
        });
        
        function renderItem(item, index) {
            
            var scopeStr = Array.isArray(item.scope) ? item.scope.join(", ") : (item.scope || "");
            var images = Array.isArray(item.images) ? item.images.join(", ") : (item.images || item.image || "");
            
            var html = '<div class="card p15 mb15 pt15 border-dashed project-item" data-index="'+index+'" style="position:relative;">';
            html += '<div style="position:absolute; right:15px; top:15px; cursor:pointer;" class="text-danger remove-project"><i data-feather="x" class="icon-16"></i> Remove</div>';
            
            html += '<div class="row mb10">';
            html += '<div class="col-md-6"><label>Title</label><input type="text" class="form-control p-title" value="'+(item.title||'')+'" /></div>';
            
            // Generate category dropdown options
            html += '<div class="col-md-6"><label>Category</label><select class="form-control p-category">';
            html += '<option value="">- Select Category -</option>';
            var catOptions = getCategoriesArray();
            for(var c=0; c < catOptions.length; c++) {
                var selected = (item.category === catOptions[c]) ? 'selected' : '';
                html += '<option value="'+catOptions[c]+'" '+selected+'>'+catOptions[c]+'</option>';
            }
            html += '</select></div>';
            
            html += '</div>';

            html += '<div class="row mb10">';
            html += '<div class="col-md-4"><label>Location</label><input type="text" class="form-control p-location" value="'+(item.location||'')+'" /></div>';
            html += '<div class="col-md-4"><label>Year</label><input type="text" class="form-control p-year" value="'+(item.year||'')+'" /></div>';
            html += '<div class="col-md-4"><label>Client</label><input type="text" class="form-control p-client" value="'+(item.client||'')+'" /></div>';
            html += '</div>';

            html += '<div class="row mb10">';
            html += '<div class="col-md-12"><label>Story / Description</label><textarea class="form-control p-story" style="height:60px;">'+(item.story||'')+'</textarea></div>';
            html += '</div>';

            html += '<div class="row mb10">';
            html += '<div class="col-md-12"><label>Images URL Paths</label>';
            html += '<div class="images-container">';
            
            var imgArr = item.images && item.images.length > 0 ? item.images : [(item.image || "")];
            for (var i = 0; i < imgArr.length; i++) {
                html += '<div class="input-group mb-2 image-input-row">';
                html += '<input type="text" class="form-control p-image-val" value="'+(imgArr[i]||'')+'" placeholder="/havia-project-1.jpg" />';
                html += '<div class="input-group-append"><button type="button" class="btn btn-danger remove-image-btn"><i data-feather="trash-2" class="icon-16"></i></button></div>';
                html += '</div>';
            }
            
            html += '</div>'; // end images-container
            html += '<button type="button" class="btn btn-sm btn-outline-primary add-image-btn mt-2"><i data-feather="plus" class="icon-14"></i> Add Image</button>';
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            container.append(html);
        }
        
        function updateJSON() {
            var dataArr = [];
            container.find(".project-item").each(function(i, el) {
                var elem = $(el);
                
                var imgArr = [];
                elem.find(".p-image-val").each(function(){
                    var val = $(this).val().trim();
                    if(val) imgArr.push(val);
                });
                var firstImg = imgArr.length > 0 ? imgArr[0] : "";

                dataArr.push({
                    title: elem.find(".p-title").val(),
                    category: elem.find(".p-category").val(),
                    location: elem.find(".p-location").val(),
                    year: elem.find(".p-year").val(),
                    client: elem.find(".p-client").val(),
                    story: elem.find(".p-story").val(),
                    image: firstImg,
                    images: imgArr
                });
            });
            jsonField.val(JSON.stringify(dataArr));
        }
        
        if (initialData.length > 0) {
            initialData.forEach(function(item, idx) {
                renderItem(item, idx);
            });
            feather.replace();
        } else {
            // Render default
            renderItem({title:"Sample Project", category:"Residential", image:"/havia-project-1.jpg", images:["/havia-project-1.jpg"]});
            feather.replace();
        }
        
        $("#add-project-btn").click(function() {
            renderItem({}, container.find(".project-item").length);
            feather.replace();
            updateJSON();
        });
        
        container.on("click", ".remove-project", function() {
            $(this).closest(".project-item").remove();
            updateJSON();
        });
        
        container.on("click", ".add-image-btn", function() {
            var imgContainer = $(this).closest(".row").find(".images-container");
            var html = '<div class="input-group mb-2 image-input-row">';
            html += '<input type="text" class="form-control p-image-val" value="" placeholder="/havia-project-new.jpg" />';
            html += '<div class="input-group-append"><button type="button" class="btn btn-danger remove-image-btn"><i data-feather="trash-2" class="icon-16"></i></button></div>';
            html += '</div>';
            imgContainer.append(html);
            feather.replace();
            updateJSON();
        });

        container.on("click", ".remove-image-btn", function() {
            $(this).closest(".image-input-row").remove();
            updateJSON();
        });

        container.on("change keyup", "input, select, textarea", function() {
            updateJSON();
        });

        $("#portfolio-settings-form").appForm({
            isModal: false,
            beforeAjaxSubmit: function (data) {
                updateJSON();
            },
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
