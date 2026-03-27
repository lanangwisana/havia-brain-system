<?php echo form_open(get_uri("user_management/save"), array("id" => "user-management-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="first_name" class=" col-md-3"><?php echo app_lang('first_name'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "first_name",
                        "name" => "first_name",
                        "value" => $model_info->first_name,
                        "class" => "form-control",
                        "placeholder" => app_lang('first_name'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="last_name" class=" col-md-3"><?php echo app_lang('last_name'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "last_name",
                        "name" => "last_name",
                        "value" => $model_info->last_name,
                        "class" => "form-control",
                        "placeholder" => app_lang('last_name'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="email" class=" col-md-3"><?php echo app_lang('email'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "email",
                        "name" => "email",
                        "value" => $model_info->email,
                        "class" => "form-control",
                        "placeholder" => app_lang('email'),
                        "data-rule-email" => true,
                        "data-msg-email" => app_lang("enter_valid_email"),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="password" class=" col-md-3"><?php echo app_lang('password'); ?></label>
                <div class=" col-md-9">
                    <?php
                    $password_args = array(
                        "id" => "password",
                        "name" => "password",
                        "class" => "form-control",
                        "placeholder" => app_lang('password'),
                        "data-rule-minlength" => 6,
                        "data-msg-minlength" => app_lang("enter_minimum_6_characters"),
                    );

                    if (!$model_info->id) {
                        $password_args["data-rule-required"] = true;
                        $password_args["data-msg-required"] = app_lang("field_required");
                    }

                    echo form_password($password_args);
                    ?>
                    <?php if ($model_info->id) { ?>
                        <span class="text-off" style="font-size: 11px;">Leave blank to keep current password.</span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="job_title" class=" col-md-3"><?php echo app_lang('job_title'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "job_title",
                        "name" => "job_title",
                        "value" => $model_info->job_title,
                        "class" => "form-control",
                        "placeholder" => app_lang('job_title')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="role" class=" col-md-3"><?php echo app_lang('role'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_dropdown("role_id", $roles_dropdown, array($role), "class='select2' id='user-role' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "'");
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var $form = $("#user-management-form");
        $form.appForm({
            onSuccess: function (result) {
                $("#user-management-table").appTable({newData: result.data, dataId: result.id});
            }
        });
        $("#user-role").select2();

    });
</script>
