<?php echo form_open(get_uri("landingpage_cms/save_hero_slide"), array("id" => "reply-request-form", "class" => "general-form", "role" => "form", "enctype" => "multipart/form-data")); ?>
<div class="modal-body clearfix" style="min-height: 400px;">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="task" value="send_reply_email" />
        
        <div class="form-group">
            <label for="to" class="col-md-3">Recipient Email</label>
            <div class="col-md-9">
                <input type="text" id="to" name="to" value="<?php echo $model_info->contact; ?>" class="form-control" data-rule-required="true" />
            </div>
        </div>

        <div class="form-group">
            <label for="subject" class="col-md-3">Subject</label>
            <div class="col-md-9">
                <input type="text" id="subject" name="subject" value="Havia Studio Portfolio - <?php echo $model_info->interest; ?>" class="form-control" data-rule-required="true" />
            </div>
        </div>

        <div class="form-group">
            <label for="message" class="col-md-12">Message Body</label>
            <div class="col-md-12">
                <textarea id="message" name="message" class="form-control" style="height: 200px;" data-rule-required="true"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="attachment" class="col-md-3">Attach File</label>
            <div class="col-md-9">
                <input type="file" id="attachment" name="attachment" class="form-control" />
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
                    appAlert.success(result.message, {duration: 5000});
                    $("[data-bs-target='#requests-tab']").trigger("click");
                } else {
                    appAlert.error(result.message);
                }
            }
        });
        if (typeof feather !== "undefined") feather.replace();
    });
</script>
