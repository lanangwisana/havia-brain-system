<!-- Text settings form -->
<?php echo form_open(get_uri("landingpage_cms/save_settings"), array("id" => "trust-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <h5 class="mb-3">Testimonial & Client Settings</h5>
    
    <div class="form-group"><div class="row">
        <label class="col-md-3">Testimonial Heading</label>
        <div class="col-md-9"><?php echo form_input(array("name" => "landingpage_trust_heading", "value" => get_setting('landingpage_trust_heading') ?: 'Testimonial', "class" => "form-control")); ?></div>
    </div></div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group"><div class="row">
                <label class="col-md-6">Button 1 (Corporate)</label>
                <div class="col-md-6"><?php echo form_input(array("name" => "landingpage_trust_btn_corporate", "value" => get_setting('landingpage_trust_btn_corporate') ?: 'Corporate', "class" => "form-control")); ?></div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="form-group"><div class="row">
                <label class="col-md-6">Button 2 (Personal)</label>
                <div class="col-md-6"><?php echo form_input(array("name" => "landingpage_trust_btn_personal", "value" => get_setting('landingpage_trust_btn_personal') ?: 'Personal', "class" => "form-control")); ?></div>
            </div></div>
        </div>
    </div>

    <div class="form-group"><div class="row">
        <label class="col-md-3">Client Section Heading</label>
        <div class="col-md-9"><?php echo form_input(array("name" => "landingpage_trust_client_heading", "value" => get_setting('landingpage_trust_client_heading') ?: 'Our Clients', "class" => "form-control")); ?></div>
    </div></div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save Settings</button>
</div>
<?php echo form_close(); ?>

<hr/>

<!-- CORPORATE TESTIMONIALS -->
<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6>Corporate Testimonials (<?php echo count($corporate_testimonials); ?>/5)</h6>
        <?php if (count($corporate_testimonials) < 5): ?>
        <?php echo modal_anchor(get_uri("landingpage_cms/testimonial_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add', array("class" => "btn btn-primary btn-sm", "title" => "Add Corporate Testimonial", "data-post-type" => "corporate")); ?>
        <?php endif; ?>
    </div>
    <?php if (empty($corporate_testimonials)): ?>
        <p class="text-muted py-3"><em>No corporate testimonials yet.</em></p>
    <?php else: ?>
        <div class="row">
        <?php foreach ($corporate_testimonials as $t): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php if ($t->image): ?>
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($t->image, 'testimonials'); ?>" style="width:40px; height:40px; object-fit:contain;" />
                        <?php endif; ?>
                        <div>
                            <strong class="small"><?php echo htmlspecialchars($t->name); ?></strong>
                            <p class="text-muted mb-0" style="font-size:10px;"><?php echo htmlspecialchars($t->subtitle); ?></p>
                        </div>
                    </div>
                    <p class="small text-muted fst-italic mb-2">"<?php echo htmlspecialchars(mb_strimwidth($t->description, 0, 80, '...')); ?>"</p>
                    <div class="d-flex gap-1 mt-auto">
                        <?php echo modal_anchor(get_uri("landingpage_cms/testimonial_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Testimonial", "data-post-id" => $t->id)); ?>
                        <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm", "data-id" => $t->id, "data-action-url" => get_uri("landingpage_cms/delete_testimonial"), "data-action" => "delete-confirmation")); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<hr/>

<!-- PERSONAL TESTIMONIALS -->
<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6>Personal Testimonials (<?php echo count($personal_testimonials); ?>/5)</h6>
        <?php if (count($personal_testimonials) < 5): ?>
        <?php echo modal_anchor(get_uri("landingpage_cms/testimonial_modal"), '<span data-feather="plus-circle" class="icon-16"></span> Add', array("class" => "btn btn-primary btn-sm", "title" => "Add Personal Testimonial", "data-post-type" => "personal")); ?>
        <?php endif; ?>
    </div>
    <?php if (empty($personal_testimonials)): ?>
        <p class="text-muted py-3"><em>No personal testimonials yet.</em></p>
    <?php else: ?>
        <div class="row">
        <?php foreach ($personal_testimonials as $t): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php if ($t->image): ?>
                            <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($t->image, 'testimonials'); ?>" style="width:40px; height:40px; object-fit:contain;" />
                        <?php endif; ?>
                        <div>
                            <strong class="small"><?php echo htmlspecialchars($t->name); ?></strong>
                            <p class="text-muted mb-0" style="font-size:10px;"><?php echo htmlspecialchars($t->subtitle); ?></p>
                        </div>
                    </div>
                    <p class="small text-muted fst-italic mb-2">"<?php echo htmlspecialchars(mb_strimwidth($t->description, 0, 80, '...')); ?>"</p>
                    <div class="d-flex gap-1 mt-auto">
                        <?php echo modal_anchor(get_uri("landingpage_cms/testimonial_modal"), '<span data-feather="edit" class="icon-16"></span>', array("class" => "btn btn-default btn-sm", "title" => "Edit Testimonial", "data-post-id" => $t->id)); ?>
                        <?php echo js_anchor('<span data-feather="x" class="icon-16"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm", "data-id" => $t->id, "data-action-url" => get_uri("landingpage_cms/delete_testimonial"), "data-action" => "delete-confirmation")); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<hr/>

<!-- CLIENT LOGOS -->
<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6>Client Logos (<?php echo count($client_logos); ?>/50)</h6>
            <small class="text-muted">Upload client logos for the carousel</small>
        </div>
        <?php if (count($client_logos) < 50): ?>
        <form id="client-logo-upload" enctype="multipart/form-data" class="d-flex gap-2">
            <?php echo csrf_field(); ?>
            <input type="text" name="name" class="form-control form-control-sm" placeholder="Company name (optional)" style="max-width:200px;" />
            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" style="max-width:200px;" required />
            <button type="submit" class="btn btn-primary btn-sm"><span data-feather="upload" class="icon-16"></span></button>
        </form>
        <?php endif; ?>
    </div>

    <div class="row" id="client-logos-list">
        <?php foreach ($client_logos as $cl): ?>
            <div class="col-md-2 col-4 mb-3 text-center">
                <div class="position-relative border rounded p-2" style="height:80px;">
                    <img src="<?php echo \HaviaCMS\Controllers\Landingpage_cms::get_upload_url($cl->image, 'clients'); ?>" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <div class="position-absolute" style="top:2px; right:2px;">
                        <?php echo js_anchor('<span data-feather="x" class="icon-14"></span>', array('title' => 'Delete', "class" => "btn btn-danger", "style" => "padding:0px 3px; font-size:10px; border-radius:50%; line-height:1;", "data-id" => $cl->id, "data-action-url" => get_uri("landingpage_cms/delete_client_logo"), "data-action" => "delete-confirmation")); ?>
                    </div>
                </div>
                <?php if ($cl->name): ?>
                <p class="text-muted mt-1" style="font-size:9px;"><?php echo htmlspecialchars($cl->name); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#trust-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });

        $("#client-logo-upload").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "<?php echo get_uri('landingpage_cms/save_client_logo'); ?>",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message, {duration: 5000});
                        $("[data-bs-target='#trust-tab']").trigger("click");
                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });

        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
