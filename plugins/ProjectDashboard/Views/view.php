<div id="page-content" class="page-wrapper clearfix">
    <style type="text/css">
        .project-info-card {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            background: #fff;
        }

        .dashboard-icon-widget {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .dashboard-icon-widget:hover {
            transform: translateY(-3px);
        }

        .widget-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #485BBD 0%, #6690F4 100%);
        }

        .bg-gradient-coral {
            background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%);
        }

        .widget-details h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .table-custom thead th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 600;
            border-bottom: 2px solid #eaedf0;
        }

        .card-header h4 {
            font-weight: 600;
            color: #4e5e6a;
        }

        .table-custom td.option a,
        .table-custom td.option .edit {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            margin: 0 4px !important;
            float: none !important;
        }

        .table-custom th,
        .table-custom td {
            vertical-align: middle !important;
        }
    </style>

    <!-- Back Button & Title -->
    <div class="page-title clearfix">
        <div class="float-start mr15">
            <a href="<?php echo get_uri("project_dashboard"); ?>" class="btn btn-default"><i data-feather="arrow-left"
                    class="icon-16"></i> Back to Dashboard</a>
        </div>
        <h1 class="float-start">
            <i data-feather="activity" class="icon-24 mr10 text-primary"></i>
            <?php echo $project_info->title; ?>
        </h1>
    </div>

    <!-- Project Info Card -->
    <div class="card project-info-card mb20">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 col-sm-6 mb15">
                    <div class="text-off mb5"><small>Client</small></div>
                    <strong class="text-dark"><?php echo $project_info->company_name; ?></strong>
                </div>
                <div class="col-md-2 col-sm-6 mb15">
                    <div class="text-off mb5"><small>Start Date</small></div>
                    <strong
                        class="text-dark"><?php echo ($project_info && $project_info->start_date) ? format_to_date($project_info->start_date) : "-"; ?></strong>
                </div>
                <div class="col-md-2 col-sm-6 mb15">
                    <div class="text-off mb5"><small>Deadline</small></div>
                    <strong
                        class="text-dark"><?php echo ($project_info && $project_info->deadline) ? format_to_date($project_info->deadline) : "-"; ?></strong>
                </div>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="text-off mb5"><small>Contract Value</small></div>
                    <strong
                        class="text-primary"><?php echo ($project_info && isset($project_info->price)) ? to_currency($project_info->price) : to_currency(0); ?></strong>
                </div>
                <div class="col-md-3 col-sm-6 mb15 text-md-right">
                    <div class="text-off mb5"><small>Status</small></div>
                    <?php
                    $status_class = "bg-success";
                    $status_label = (isset($project_info->status_key_name) && $project_info->status_key_name) ? app_lang($project_info->status_key_name) : (isset($project_info->status_title) ? $project_info->status_title : "Open");
                    ?>
                    <span class="badge <?php echo $status_class; ?>"
                        style="padding: 6px 12px; border-radius: 20px;"><?php echo $status_label; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- S-Curve and Progress Summary Widgets -->
    <div class="row">
        <div class="col-md-8">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <div class="card-header bg-white" style="border-bottom: 1px solid #f1f1f1;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i data-feather="trending-up" class="icon-16 mr5"></i> S-Curve Performance
                            Monitoring</h4>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-default active">Weekly</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="min-height: 340px; position: relative;">
                        <canvas id="s-curve-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-gradient-primary text-white mb20 dashboard-icon-widget">
                <div class="card-body p20">
                    <div class="d-flex align-items-center">
                        <div class="widget-icon bg-white-translucent mr15">
                            <i data-feather="target" class="icon-24"></i>
                        </div>
                        <div class="widget-details">
                            <div class="text-white-translucent">Current Actual Progress</div>
                            <h1><?php echo number_format((float) $current_actual, 2); ?>%</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card bg-gradient-success text-white mb20 dashboard-icon-widget">
                <div class="card-body p20">
                    <div class="d-flex align-items-center">
                        <div class="widget-icon bg-white-translucent mr15">
                            <i data-feather="calendar" class="icon-24"></i>
                        </div>
                        <div class="widget-details">
                            <div class="text-white-translucent">Current Week</div>
                            <h1>Week <?php echo $current_week; ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-white mb20 dashboard-icon-widget" style="border: 1px solid #f1f1f1;">
                <div class="card-body p20">
                    <div class="d-flex align-items-center">
                        <div class="widget-icon bg-gradient-coral mr15">
                            <i data-feather="dollar-sign" class="icon-24"></i>
                        </div>
                        <div class="widget-details">
                            <div class="text-off mb5">Total Project Price</div>
                            <h3 class="mb-0 text-dark"><?php echo to_currency($total_project_price ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Table -->
    <div class="card mb20" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="card-header bg-white">
            <h4 class="mb-0"><i data-feather="list" class="icon-16 mr5"></i> Weekly Progress Breakdown</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-custom mb0">
                <thead>
                    <tr>
                        <th class="pl20">Week</th>
                        <th class="text-right">Planned (%)</th>
                        <th class="text-right">Cumulative Plan (%)</th>
                        <th class="text-right">Actual (%)</th>
                        <th class="text-right">Cumulative Actual (%)</th>
                        <th class="text-right pr20">Deviation (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Map actual history by week number for easy lookup
                    $actual_map = array();
                    if ($actual_history) {
                        foreach ($actual_history as $act) {
                            $actual_map[$act->week_number] = $act;
                        }
                    }

                    $max_weeks = is_array($planned_schedules) ? count($planned_schedules) : 0;
                    if ($max_weeks == 0) {
                        echo "<tr><td colspan='6' class='text-center p20'>No schedule data found.</td></tr>";
                    } else {
                        foreach ($planned_schedules as $plan) {
                            $actual = isset($actual_map[$plan->week_number]) ? $actual_map[$plan->week_number] : null;
                            $has_actual = ($actual !== null);
                            $deviation = $has_actual ? ((float)$actual->actual_percentage - (float)$plan->planned_percentage) : null;
                            $dev_class = '';
                            if ($deviation !== null) {
                                $dev_class = $deviation >= 0 ? 'text-primary' : 'text-danger';
                            }
                            ?>
                            <tr class="<?php echo ($plan->week_number > $current_week) ? 'text-off' : ''; ?>">
                                <td class="pl20">
                                    Week <?php echo $plan->week_number; ?>
                                    <?php if ($plan->week_number == $current_week) { ?>
                                        <span class="badge bg-info ml5">Current</span>
                                    <?php } ?>
                                </td>
                                <td class="text-right"><?php echo number_format((float) $plan->planned_percentage, 2); ?>%</td>
                                <td class="text-right"><?php echo number_format((float) $plan->cumulative_planned, 2); ?>%</td>
                                <td class="text-right">
                                    <?php echo $has_actual ? number_format((float) $actual->actual_percentage, 2) . '%' : '-'; ?>
                                </td>
                                <td class="text-right">
                                    <?php echo $has_actual ? number_format((float) $actual->cumulative_actual, 2) . '%' : '-'; ?>
                                </td>
                                <td class="text-right pr20 <?php echo $dev_class; ?>">
                                    <?php if ($deviation !== null) { ?>
                                        <strong><?php echo ($deviation >= 0 ? '+' : '') . number_format($deviation, 2); ?>%</strong>
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RAB Weighting Table -->
    <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i data-feather="layers" class="icon-16 mr5"></i> Work Items & RAB Weighting</h4>
            <a href="<?php echo get_uri("project_dashboard/activity_log/" . $project_info->id); ?>" class="btn btn-default btn-sm" style="border-radius: 6px;">
                <i data-feather="list" class="icon-14 mr5"></i> Log Aktivitas
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-custom mb0">
                <thead>
                    <tr>
                        <th class="pl20">Work Description</th>
                        <th class="text-right">Nominal RAB</th>
                        <th class="text-right">Bobot (%)</th>
                        <th class="text-right">Actual Progress</th>
                        <th class="text-center">Status Actual</th>
                        <th class="text-center">Plan Start Date</th>
                        <th class="text-center">Plan End Date</th>
                        <th class="text-center option w100"><i data-feather="menu" class="icon-16"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($parent_tasks) > 0) {
                        foreach ($parent_tasks as $task) {
                            $weight = isset($weights_map[$task->id]) ? number_format($weights_map[$task->id], 2) . '%' : '-';
                            $actual_prog = isset($actual_progress_map[$task->id]) ? number_format($actual_progress_map[$task->id], 2) . '%' : '0.00%';
                            $status_info = isset($plan_status_map[$task->id]) ? $plan_status_map[$task->id] : array("title" => $task->status_title ?: "Unknown", "color" => $task->status_color ?: "#888");
                            $status_color = $status_info['color'];
                            $status_title = $status_info['title'];
                            $approval_status = isset($approval_status_map[$task->id]) ? $approval_status_map[$task->id] : 'Approved';
                            ?>
                            <tr class="bg-light parent-task-accordion" data-parent-id="<?php echo $task->id; ?>"
                                style="cursor: pointer;" title="Click to toggle sub-tasks">
                                <td class="pl20">
                                    <div class="d-flex align-items-center">
                                        <?php if (isset($sub_tasks_map[$task->id])) { ?>
                                            <i data-feather="chevron-down" class="icon-16 mr5 toggle-icon-<?php echo $task->id; ?>"></i>
                                        <?php } ?>
                                        <strong><?php echo $task->title; ?></strong>
                                    </div>
                                    <?php 
                                    $task_reject_reason = isset($reject_reason_map[$task->id]) ? $reject_reason_map[$task->id] : null;
                                    ?>
                                    <?php if ($approval_status === 'Pending') { ?>
                                        <span class="badge ml5"
                                            style="background-color: #fff9ed; color: #d39e00; border: 1px solid #ffe8b5; font-size: 10px; padding: 3px 8px; border-radius: 4px; font-weight: 600;"><i
                                                data-feather="clock" class="icon-10 mr5"></i>Pending Approval</span>
                                    <?php } else if ($approval_status === 'Rejected') { ?>
                                        <span class="badge ml5"
                                            style="background-color: #fff5f5; color: #ef4444; border: 1px solid #ffcccc; font-size: 10px; padding: 3px 6px; border-radius: 4px; font-weight: 600;"><i
                                                data-feather="x-circle" class="icon-10 mr3"></i>Rejected</span>
                                        <?php if ($task_reject_reason) { ?>
                                            <span class="d-block mt2" style="font-size: 10px; color: #b91c1c; font-style: italic; max-width: 220px; white-space: normal; line-height: 1.4;">
                                                <i data-feather="message-square" class="icon-10 mr3"></i><?php echo htmlspecialchars($task_reject_reason); ?>
                                            </span>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                                <?php if (isset($sub_tasks_map[$task->id])) {
                                    $parent_nominal = 0;
                                    $parent_weight = 0;
                                    $parent_actual = isset($actual_progress_map[$task->id]) ? $actual_progress_map[$task->id] : 0;
                                    $parent_start = (isset($task->start_date) && is_date_exists($task->start_date)) ? format_to_date($task->start_date, false) : null;
                                    $parent_end = (isset($task->deadline) && is_date_exists($task->deadline)) ? format_to_date($task->deadline, false) : null;
                                    $calc_start = null;
                                    $calc_end = null;
                                    foreach ($sub_tasks_map[$task->id] as $st) {
                                        $parent_nominal += isset($nominal_rab_map[$st->id]) ? (float) $nominal_rab_map[$st->id] : 0;
                                        $w_val = isset($weights_map[$st->id]) ? (float) $weights_map[$st->id] : 0;
                                        $parent_weight += $w_val;

                                        $st_start = isset($start_date_map[$st->id]) && $start_date_map[$st->id] !== '-' ? $start_date_map[$st->id] : null;
                                        $st_end = isset($end_date_map[$st->id]) && $end_date_map[$st->id] !== '-' ? $end_date_map[$st->id] : null;

                                        if ($st_start && (!$calc_start || $st_start < $calc_start)) {
                                            $calc_start = $st_start;
                                        }
                                        if ($st_end && (!$calc_end || $st_end > $calc_end)) {
                                            $calc_end = $st_end;
                                        }
                                    }
                                    if (!$parent_start) {
                                        $parent_start = $calc_start;
                                    }
                                    if (!$parent_end) {
                                        $parent_end = $calc_end;
                                    }
                                    ?>
                                    <td class="text-right"><?php echo to_currency($parent_nominal); ?></td>
                                    <td class="text-right"><strong><?php echo number_format($parent_weight, 2); ?>%</strong></td>
                                    <td class="text-right"><strong><?php echo number_format($parent_actual, 2); ?>%</strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $p_actual_status = isset($actual_status_map[$task->id]) ? $actual_status_map[$task->id] : 'To Do';
                                        $p_actual_color = $p_actual_status === 'Done' ? '#10b981' : ($p_actual_status === 'In Progress' ? '#f59e0b' : '#888');
                                        ?>
                                        <span class="badge"
                                            style="background-color: <?php echo $p_actual_color; ?>; color: #fff; font-size: 10px;"><?php echo $p_actual_status; ?></span>
                                    </td>
                                    <td class="text-center"><?php echo $parent_start ? $parent_start : '-'; ?></td>
                                    <td class="text-center"><?php echo $parent_end ? $parent_end : '-'; ?></td>
                                <?php } else { ?>
                                    <td class="text-right"><?php echo to_currency($nominal_rab_map[$task->id] ?? 0); ?></td>
                                    <td class="text-right"><strong><?php echo $weight; ?></strong></td>
                                    <td class="text-right"><strong><?php echo $actual_prog; ?></strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $t_actual_status = isset($actual_status_map[$task->id]) ? $actual_status_map[$task->id] : 'To Do';
                                        $t_actual_color = $t_actual_status === 'Done' ? '#10b981' : ($t_actual_status === 'In Progress' ? '#f59e0b' : '#888');
                                        ?>
                                        <span class="badge"
                                            style="background-color: <?php echo $t_actual_color; ?>; color: #fff; font-size: 10px;"><?php echo $t_actual_status; ?></span>
                                    </td>
                                    <td class="text-center"><?php echo ($start_date_map[$task->id] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo ($end_date_map[$task->id] ?? '-'); ?></td>
                                <?php } ?>
                                <td class="text-center option">
                                    <div class="d-flex align-items-center justify-content-center" style="gap: 6px;">
                                        <?php if (isset($can_edit_project_weights) && $can_edit_project_weights) { ?>
                                            <?php if (!isset($sub_tasks_map[$task->id])) { ?>
                                                <?php echo modal_anchor(get_uri("project_dashboard/modal_edit_rab"), "<i data-feather='edit-2' class='icon-14'></i>", array("class" => "edit", "title" => "Edit RAB Weight", "data-post-task_id" => $task->id, "data-post-project_id" => $project_info->id)); ?>
                                                <?php echo modal_anchor(get_uri("project_dashboard/modal_edit_parent_dates"), "<i data-feather='calendar' class='icon-14'></i>", array("class" => "edit", "title" => "Edit Dates & Week", "data-post-task_id" => $task->id, "data-post-project_id" => $project_info->id)); ?>
                                            <?php } else { ?>
                                                <?php echo modal_anchor(get_uri("project_dashboard/modal_edit_parent_dates"), "<i data-feather='calendar' class='icon-14'></i>", array("class" => "edit", "title" => "Edit Dates & Week", "data-post-task_id" => $task->id, "data-post-project_id" => $project_info->id)); ?>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-off">-</span>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            if (isset($sub_tasks_map[$task->id])) {
                                foreach ($sub_tasks_map[$task->id] as $sub_task) {
                                    $st_weight = isset($weights_map[$sub_task->id]) ? number_format($weights_map[$sub_task->id], 2) . '%' : '0.00%';
                                    $st_actual_prog = isset($actual_progress_map[$sub_task->id]) ? number_format($actual_progress_map[$sub_task->id], 2) . '%' : '0.00%';
                                    $st_status_info = isset($plan_status_map[$sub_task->id]) ? $plan_status_map[$sub_task->id] : array("title" => $sub_task->status_title ?: "Unknown", "color" => $sub_task->status_color ?: "#888");
                                    $st_status_color = $st_status_info['color'];
                                    $st_status_title = $st_status_info['title'];
                                    $st_approval_status = isset($approval_status_map[$sub_task->id]) ? $approval_status_map[$sub_task->id] : 'Approved';
                                    ?>
                                    <tr class="sub-task-row-<?php echo $task->id; ?>" style="display: none; background-color: #fcfcfc;">
                                        <td class="pl40">
                                            <div class="d-flex align-items-center">
                                                <i data-feather="corner-down-right" class="icon-14 text-muted mr5"></i>
                                                <span><?php echo $sub_task->title; ?></span>
                                            </div>
                                            <?php 
                                            $st_reject_reason = isset($reject_reason_map[$sub_task->id]) ? $reject_reason_map[$sub_task->id] : null;
                                            ?>
                                            <?php if ($st_approval_status === 'Pending') { ?>
                                                <span class="badge ml5"
                                                    style="background-color: #fff9ed; color: #d39e00; border: 1px solid #ffe8b5; font-size: 10px; padding: 3px 8px; border-radius: 4px; font-weight: 600;"><i
                                                        data-feather="clock" class="icon-10 mr5"></i>Pending Approval</span>
                                            <?php } else if ($st_approval_status === 'Rejected') { ?>
                                                <span class="badge ml5"
                                                    style="background-color: #fff5f5; color: #ef4444; border: 1px solid #ffcccc; font-size: 10px; padding: 3px 6px; border-radius: 4px; font-weight: 600;"><i
                                                        data-feather="x-circle" class="icon-10 mr3"></i>Rejected</span>
                                                <?php if ($st_reject_reason) { ?>
                                                    <span class="d-block mt2" style="font-size: 10px; color: #b91c1c; font-style: italic; max-width: 220px; white-space: normal; line-height: 1.4;">
                                                        <i data-feather="message-square" class="icon-10 mr3"></i><?php echo htmlspecialchars($st_reject_reason); ?>
                                                    </span>
                                                <?php } ?>
                                            <?php } ?>
                                        </td>
                                        <td class="text-right"><?php echo to_currency($nominal_rab_map[$sub_task->id] ?? 0); ?></td>
                                        <td class="text-right"><?php echo $st_weight; ?></td>
                                        <td class="text-right">-</td>
                                        <td class="text-center">-</td>
                                        <td class="text-center"><?php echo ($start_date_map[$sub_task->id] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo ($end_date_map[$sub_task->id] ?? '-'); ?></td>
                                        <td class="text-center option">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <?php if (isset($can_edit_project_weights) && $can_edit_project_weights) { ?>
                                                    <?php echo modal_anchor(get_uri("project_dashboard/modal_edit_rab"), "<i data-feather='edit-2' class='icon-14'></i>", array("class" => "edit", "title" => "Edit RAB Weight", "data-post-task_id" => $sub_task->id, "data-post-project_id" => $project_info->id)); ?>
                                                <?php } else { ?>
                                                    <span class="text-off">-</span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="8" class="text-center p30 text-off">No tasks found for this project.</td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="b-t">
                        <th class="pl20">TOTAL</th>
                        <th class="text-right"><?php echo to_currency($total_leaf_rab); ?></th>
                        <th class="text-right"><?php echo number_format($total_weight, 2); ?>%</th>
                        <th class="text-right"><?php echo number_format((float) $current_actual, 2); ?>%</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        feather.replace();

        // Accordion toggle for parent tasks
        $('.parent-task-accordion').on('click', function (e) {
            if ($(e.target).closest('a, button').length) return; // Prevent toggle when clicking modal links
            var parentId = $(this).attr('data-parent-id');
            var $subRows = $('.sub-task-row-' + parentId);
            $subRows.toggle();

            var $icon = $(this).find('.toggle-icon-' + parentId);
            if ($subRows.is(':visible')) {
                $icon.replaceWith('<i data-feather="chevron-down" class="icon-16 mr5 toggle-icon-' + parentId + '"></i>');
            } else {
                $icon.replaceWith('<i data-feather="chevron-right" class="icon-16 mr5 toggle-icon-' + parentId + '"></i>');
            }
            feather.replace();
        });

        // Delete handler with centered Modal Confirmation
        $('body').on('click', '[data-action=delete-confirmation-modal]', function (e) {
            var $instance = $(this);
            var actionUrl = $instance.attr('data-action-url');
            var id = $instance.attr('data-id');

            $("#confirmationModal").modal("show");
            $("#confirmDeleteButton").unbind().click(function () {
                appLoader.show();
                appAjaxRequest({
                    url: actionUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function (result) {
                        if (result.success) {
                            appAlert.warning(result.message, { duration: 10000 });
                            location.reload();
                        } else {
                            appAlert.error(result.message);
                        }
                        appLoader.hide();
                    }
                });
            });
            return false;
        });

        var ctx = document.getElementById('s-curve-chart');
        if (ctx) {
            var chartCtx = ctx.getContext('2d');

            // Use theme colors (#485BBD for success/planned, #6690F4 for primary/actual)
            var plannedGradient = chartCtx.createLinearGradient(0, 0, 0, 350);
            plannedGradient.addColorStop(0, 'rgba(72, 91, 189, 0.15)');
            plannedGradient.addColorStop(1, 'rgba(72, 91, 189, 0)');

            var actualGradient = chartCtx.createLinearGradient(0, 0, 0, 350);
            actualGradient.addColorStop(0, 'rgba(102, 144, 244, 0.15)');
            actualGradient.addColorStop(1, 'rgba(102, 144, 244, 0)');

            <?php
            $labels = array();
            $planned_data = array();
            $actual_data = array();

            // Map actual history by week number for easy lookup
            $actual_map = array();
            foreach ($actual_history as $act) {
                $actual_map[$act->week_number] = $act->cumulative_actual;
            }

            foreach ($planned_schedules as $plan) {
                $labels[] = "W" . $plan->week_number;
                $planned_data[] = (float) $plan->cumulative_planned;

                // If we have actual data for this week and it is not a future week, add it
                if (isset($actual_map[$plan->week_number])) {
                    $actual_data[] = (float) $actual_map[$plan->week_number];
                }
            }
            ?>

            new Chart(chartCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Planned (Rencana)',
                        data: <?php echo json_encode($planned_data); ?>,
                        borderColor: '#485BBD',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#485BBD',
                        backgroundColor: plannedGradient,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Actual (Realisasi)',
                        data: <?php echo json_encode($actual_data); ?>,
                        borderColor: '#6690F4',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#6690F4',
                        backgroundColor: actualGradient,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Open Sans', sans-serif"
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (value) { return value + '%'; },
                                stepSize: 20,
                                font: {
                                    family: "'Open Sans', sans-serif"
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Open Sans', sans-serif"
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    $(document).ready(function () {
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>