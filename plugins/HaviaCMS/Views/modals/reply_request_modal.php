<?php echo form_open(get_uri("landingpage_cms/send_reply_email"), array("id" => "reply-request-form", "class" => "general-form", "role" => "form", "enctype" => "multipart/form-data")); ?>
<div class="modal-body clearfix" style="min-height: 400px;">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <label for="to" class="col-md-3">Recipient Email</label>
            <div class="col-md-9">
                <input type="text" id="to" name="to" value="<?php echo $model_info->contact; ?>" class="form-control"
                    data-rule-required="true" />
            </div>
        </div>

        <div class="form-group">
            <label for="subject" class="col-md-3">Subject</label>
            <div class="col-md-9">
                <input type="text" id="subject" name="subject"
                    value="Havia Studio Portfolio - <?php echo $model_info->interest; ?>" class="form-control"
                    data-rule-required="true" />
            </div>
        </div>

        <div class="form-group">
            <label for="message" class="col-md-12">Message Body</label>
            <div class="col-md-12">
                <textarea id="message" name="message" class="form-control" style="height: 200px;"
                    data-rule-required="true"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="attachment" class="col-md-12">Attach File (PDF, Max 25MB)</label>
            <div class="col-md-12">
                <input type="file" id="attachment" name="attachment" class="form-control" accept=".pdf" />
                <div id="attachment-error" class="text-danger p-1" style="display: none; font-size: 11px;"></div>
                <small class="text-muted">Max 25MB. Jika lebih dari 25MB, mohon gunakan link Drive.</small>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary">Send Email</button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#reply-request-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    appAlert.success(result.message, { duration: 5000 });
                    $("[data-bs-target='#requests-tab']").trigger("click");
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        $("#attachment").on("change", function () {
            var file = this.files[0];
            var $error = $("#attachment-error");
            $error.hide().text(""); // Reset error state

            if (file) {
                var sizeInMB = file.size / (1024 * 1024);
                var fileName = file.name;
                var extension = fileName.split('.').pop().toLowerCase();

                if (extension !== 'pdf') {
                    $error.text("Hanya file PDF yang diperbolehkan.").show();
                    this.value = "";
                    return;
                }

                if (sizeInMB > 25) {
                    $error.text("File anda melebihi ukuran (25MB), silakan cantumkan link porto pada pesan/message.").show();
                    this.value = "";
                }
            }
        });
        if (typeof feather !== "undefined") feather.replace();
    });
</script>