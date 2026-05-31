<div id="page-content" class="page-wrapper clearfix">
    <style type="text/css">
        .dashboard-icon-widget {
            border: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .dashboard-icon-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .widget-icon {
            border-radius: 10px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-gradient-info { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .bg-gradient-coral { background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%); }
        .bg-gradient-primary { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
        
        .widget-details h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        .widget-details span {
            font-weight: 500;
            opacity: 0.8;
        }
        #project-dashboard-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eaedf0;
        }
        .link-text {
            font-weight: 600;
            color: #4e5e6a;
        }
        .link-text:hover {
            color: #2575fc;
        }

        /* Modern Approval Card Styles */
        .approval-card {
            border: 1px solid rgba(255, 184, 34, 0.25);
            border-left: 5px solid #ffb822;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px -3px rgba(255, 184, 34, 0.08);
            background: #fff;
        }
        .approval-card .card-header {
            background-color: rgba(255, 184, 34, 0.03);
            border-bottom: 1px solid rgba(255, 184, 34, 0.12);
            padding: 15px 20px;
        }
        .text-warning-icon {
            color: #d39e00;
        }
        .bg-soft-warning {
            background-color: rgba(255, 184, 34, 0.12);
            color: #d39e00 !important;
            font-weight: 600;
            border-radius: 30px;
        }
        .bg-secondary-soft {
            background-color: #f1f3f5;
            color: #495057;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 6px;
            border-radius: 4px;
        }
        .text-dark-blue {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-approval-review {
            background-color: #ffb822;
            color: #fff !important;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 16px;
            box-shadow: 0 2px 4px rgba(255, 184, 34, 0.2);
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .btn-approval-review:hover {
            background-color: #e0a000;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 184, 34, 0.3);
        }
    </style>

    <!-- Page Title -->
    <div class="page-title clearfix">
        <div class="float-start mr15">
            <a href="<?php echo get_uri("projects"); ?>" class="btn btn-default"><i data-feather="list" class="icon-16"></i> <?php echo app_lang("projects"); ?></a>
        </div>
        <h1 class="float-start"><i data-feather="activity" class="icon-24 mr10"></i> <?php echo app_lang("project_dashboard"); ?></h1>
    </div>

    <?php if (isset($pending_approvals) && count($pending_approvals) > 0) { ?>
    <div class="card mb20 approval-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb0 text-warning d-flex align-items-center" style="font-size: 16px; font-weight: 600;">
                <i data-feather="clock" class="icon-18 mr10 text-warning-icon"></i>
                <span>Persetujuan RAB Tertunda (<?php echo count($pending_approvals); ?>)</span>
            </h5>
            <span class="badge bg-soft-warning text-warning px-3 py-1 font-size-12">Action Required</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb0 align-middle">
                <thead>
                    <tr>
                        <th class="border-0 px-4 py-3" style="font-size: 11px; text-transform: uppercase; color: #888;">PROYEK</th>
                        <th class="border-0 px-4 py-3" style="font-size: 11px; text-transform: uppercase; color: #888;">PEKERJAAN / TUGAS</th>
                        <th class="border-0 px-4 py-3" style="font-size: 11px; text-transform: uppercase; color: #888;">NOMINAL RAB</th>
                        <th class="border-0 px-4 py-3 text-center" style="font-size: 11px; text-transform: uppercase; color: #888; width: 150px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_approvals as $approval) { ?>
                    <tr>
                        <td class="px-4 py-3 font-weight-bold">
                            <span class="text-dark-blue"><?php echo $approval->project_title; ?></span>
                        </td>
                        <td class="px-4 py-3 text-secondary">
                            <span class="badge bg-secondary-soft mr10">TASK</span>
                            <strong><?php echo $approval->task_title; ?></strong>
                        </td>
                        <td class="px-4 py-3">
                            <strong class="text-warning"><?php echo to_currency($approval->pending_nominal_rab); ?></strong>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php echo modal_anchor(get_uri("project_dashboard/modal_edit_rab"), "<i data-feather='eye' class='icon-14 mr5'></i> Review", array("class" => "btn btn-sm btn-approval-review", "title" => "Review Pengajuan Bobot RAB", "data-post-task_id" => $approval->task_id, "data-post-project_id" => $approval->project_id)); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>
    <!-- Summary Widgets -->
    <div class="row">
        <div class="col-md-6 col-sm-6 mb20">
            <div class="card dashboard-icon-widget">
                <div class="card-body">
                    <div class="widget-icon bg-gradient-info text-white">
                        <i data-feather="briefcase" class="icon"></i>
                    </div>
                    <div class="widget-details">
                        <h1><?php echo $total_projects; ?></h1>
                        <span>Total Projects</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-6 mb20">
            <div class="card dashboard-icon-widget">
                <div class="card-body">
                    <div class="widget-icon bg-gradient-success text-white">
                        <i data-feather="trending-up" class="icon"></i>
                    </div>
                    <div class="widget-details">
                        <h1><?php echo number_format($avg_progress, 1); ?>%</h1>
                        <span>Avg. Progress</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Monitoring Table -->
    <div class="card">
        <div class="card-header">
            <h4 class="mb0"> <i data-feather="grid" class="icon-16 mr5"></i> Active Projects Monitoring</h4>
        </div>
        <div class="table-responsive">
            <table id="project-dashboard-table" class="display" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Client</th>
                        <th class="text-right">Project Value</th>
                        <th class="w20p">Progress</th>
                        <th>S-Curve Status</th>
                        <th class="text-center option w100"><i data-feather="menu" class="icon-16"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project) { ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_uri("project_dashboard/view/" . $project->id); ?>" class="link-text"><?php echo $project->title; ?></a>
                        </td>
                        <td><?php echo $project->client_name; ?></td>
                        <td class="text-right"><?php echo to_currency($project->price); ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1" style="height: 6px; margin-bottom: 0;">
                                    <?php 
                                    $bar_class = "bg-primary";
                                    if ($project->deviation < -5) $bar_class = "bg-danger";
                                    else if ($project->deviation < 0) $bar_class = "bg-orange";
                                    ?>
                                    <div class="progress-bar <?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo $project->actual_progress; ?>%;"></div>
                                </div>
                                <span class="ml10 text-bold" style="min-width: 45px;"><?php echo number_format($project->actual_progress, 1); ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $badge_class = "bg-success";
                            if ($project->deviation < -5) $badge_class = "bg-danger";
                            else if ($project->deviation < 0) $badge_class = "bg-orange";
                            ?>
                            <span class="badge <?php echo $badge_class; ?>" style="padding: 5px 10px;"><?php echo $project->status; ?></span>
                        </td>
                        <td class="text-center option">
                            <a href="<?php echo get_uri("project_dashboard/view/" . $project->id); ?>" class="btn btn-sm" style="background-color: #4e5e6a; color: #ffffff; border-radius: 4px; padding: 5px 15px; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; min-width: 80px;"><i data-feather="eye" class="icon-14 mr5"></i> Detail</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#project-dashboard-table").DataTable({
            "order": [[0, "asc"]],
            "language": {
                "lengthMenu": "_MENU_",
                "search": "",
                "searchPlaceholder": "<?php echo app_lang('search'); ?>"
            },
            "pageLength": 10,
            "columnDefs": [
                { "orderable": false, "targets": [5] }
            ]
        });
        feather.replace();
    });
</script>