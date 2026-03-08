<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <!-- Sidebar tabs -->
            <ul data-bs-toggle="ajax-tab" class="nav nav-pills nav-stacked mt-4" style="display: block;">
                <li class="active mt-2 mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/hero"); ?>" data-bs-target="#hero-tab">Hero Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/about"); ?>" data-bs-target="#about-tab">About Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/portfolio"); ?>" data-bs-target="#portfolio-tab">Portfolio Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/trust"); ?>" data-bs-target="#trust-tab">Trust Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/contact"); ?>" data-bs-target="#contact-tab">Contact Info</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("landingpage_cms/whatsapp"); ?>" data-bs-target="#whatsapp-tab">WhatsApp CTA</a></li>
            </ul>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4>Landing Page CMS</h4>
                </div>
                <div class="tab-content" style="padding: 15px;">
                    <div role="tabpanel" class="tab-pane fade active show" id="hero-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="about-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="portfolio-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="trust-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="contact-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="whatsapp-tab"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        setTimeout(function () {
            var tab = "<?php echo isset($tab) ? $tab : 'hero'; ?>";
            if (tab === "hero") {
                $("[data-bs-target='#hero-tab']").trigger("click");
            }
        }, 210);

        // Styling for pills
        $("ul.nav-pills a").click(function() {
            $("ul.nav-pills li").removeClass("active");
            $(this).parent().addClass("active");
        });
    });
</script>
