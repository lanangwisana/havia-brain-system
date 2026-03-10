<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1> <?php echo app_lang('user_management'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("user_management/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_user'), array("class" => "btn btn-default", "title" => app_lang('add_user'))); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="user-management-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#user-management-table").appTable({
            source: '<?php echo_uri("user_management/list_data") ?>',
            order: [[1, "asc"]],
            columns: [
                {title: '', "class": "w50 text-center"},
                {title: '<?php echo app_lang("name") ?>'},
                {title: '<?php echo app_lang("email") ?>'},
                {title: '<?php echo app_lang("job_title") ?>'},
                {title: '<?php echo app_lang("role") ?>'},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ]
        });
    });
</script>
