<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <!-- Sidebar tabs -->
            <ul data-bs-toggle="ajax-tab" class="nav nav-pills nav-stacked mt-4" style="display: block;">
                <li class="active mt-2 mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/hero"); ?>" data-bs-target="#hero-tab"
                        data-reload="1">Hero Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/portfolio"); ?>" data-bs-target="#portfolio-tab"
                        data-reload="1">Projects</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/requests"); ?>" data-bs-target="#requests-tab"
                        data-reload="1">Portfolio Requests</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/about"); ?>" data-bs-target="#about-tab"
                        data-reload="1">About Section</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/team"); ?>" data-bs-target="#team-tab" data-reload="1">Our
                        Team</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/gallery"); ?>" data-bs-target="#gallery-tab"
                        data-reload="1">Life at Havia</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/trust"); ?>" data-bs-target="#trust-tab"
                        data-reload="1">Clients & Testimonial</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/contact"); ?>" data-bs-target="#contact-tab"
                        data-reload="1">Contact Info</a></li>
                <li class="mb-2"><a role="presentation" data-bs-toggle="tab"
                        href="<?php echo_uri("landingpage_cms/whatsapp"); ?>" data-bs-target="#whatsapp-tab"
                        data-reload="1">WhatsApp CTA</a></li>
            </ul>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4>Landing Page CMS</h4>
                </div>
                <div class="tab-content" style="padding: 15px;">
                    <div role="tabpanel" class="tab-pane fade active show" id="hero-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="portfolio-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="requests-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="about-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="team-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="gallery-tab"></div>
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
        // Global client-side validation to reject files > 5MB before uploading
        $(document).on('change', 'input[type="file"]', function() {
            var file = this.files[0];
            var $input = $(this);
            
            // Hapus pesan error sebelumnya (jika ada)
            $input.siblings('.file-size-error').remove();

            if (file) {
                var sizeMB = file.size / (1024 * 1024);
                if (sizeMB > 5) {
                    var errorMsg = "Ukuran file terlalu besar (" + sizeMB.toFixed(2) + " MB). Silakan pilih file di bawah 5 MB!";
                    var errorHtml = '<div class="text-danger mt-1 file-size-error" style="font-size: 12px;"><span data-feather="alert-circle" class="icon-16"></span> ' + errorMsg + '</div>';
                    
                    $input.after(errorHtml);
                    if (typeof feather !== 'undefined') feather.replace();
                    
                    $input.val(''); // Instantly clear the field
                }
            }
        });

        setTimeout(function () {
            $("[data-bs-target='#hero-tab']").trigger("click");
        }, 210);

        $("ul.nav-pills a").click(function () {
            $("ul.nav-pills li").removeClass("active");
            $(this).parent().addClass("active");
        });
    });
</script>