<?php echo form_open(get_uri("project_dashboard/save_weight"), array("id" => "project-weight-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        
        <div class="form-group">
            <div class="row">
                <label for="parent_id" class="col-md-3">Parent Category</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "parent_id",
                        "name" => "parent_id",
                        "value" => $model_info->parent_id,
                        "class" => "form-control",
                        "placeholder" => "Select Parent Category (Optional)"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="item_name" class="col-md-3">Item Description</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "item_name",
                        "name" => "item_name",
                        "value" => $model_info->item_name,
                        "class" => "form-control",
                        "placeholder" => "e.g. Pekerjaan Persiapan / Interior Room A",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="weight_percentage" class="col-md-3">Weight (%)</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "weight_percentage",
                        "name" => "weight_percentage",
                        "value" => $model_info->weight_percentage ? to_decimal_format($model_info->weight_percentage) : "",
                        "class" => "form-control",
                        "placeholder" => "e.g. 15.5",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="task_ids" class="col-md-3">Linked Tasks</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "task_ids",
                        "name" => "task_ids",
                        "value" => $model_info->task_ids,
                        "class" => "form-control",
                        "placeholder" => "Select related tasks..."
                    ));
                    ?>
                    <small class="text-off mt5">Link tasks ONLY for sub-items (leaf nodes).</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sort_order" class="col-md-3">Sort Order</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "sort_order",
                        "name" => "sort_order",
                        "value" => $model_info->sort_order ? $model_info->sort_order : "0",
                        "class" => "form-control",
                        "type" => "number"
                    ));
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
        $("#project-weight-form").appForm({
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
                location.reload();
            }
        });

        $("#parent_id").select2({
            data: <?php echo json_encode($parents_dropdown); ?>
        });

        $("#task_ids").select2({
            multiple: true,
            data: <?php echo json_encode($tasks_dropdown); ?>
        });

        feather.replace();
    });
</script>
