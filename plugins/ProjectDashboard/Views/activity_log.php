<div id="page-content" class="page-wrapper clearfix">
    <style type="text/css">
        .table-custom thead th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 600;
            border-bottom: 2px solid #eaedf0;
        }
        .table-custom tbody td {
            vertical-align: middle;
        }
    </style>

    <!-- Back Button & Title -->
    <div class="page-title clearfix">
        <div class="float-start mr15">
            <a href="<?php echo get_uri("project_dashboard/view/" . $project_info->id); ?>" class="btn btn-default"><i data-feather="arrow-left"
                    class="icon-16"></i> Back to Project S-Curve</a>
        </div>
        <h1 class="float-start">
            <i data-feather="list" class="icon-24 mr10 text-primary"></i>
            Log Aktivitas Progres Aktual - <?php echo $project_info->title; ?>
        </h1>
    </div>

    <!-- Actual Progress Activity Log Card -->
    <div class="card mt20" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="card-header bg-white clearfix">
            <h4 class="float-start mb-0"><i data-feather="list" class="icon-16 mr5"></i> Log Aktivitas Progres Aktual</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-custom card-table">
                <thead>
                    <tr>
                        <th class="pl20" style="width: 20%;">Tanggal & Jam</th>
                        <th style="width: 20%;">Diupdate Oleh</th>
                        <th style="width: 25%;">Pekerjaan / Task</th>
                        <th class="text-center" style="width: 10%;">Minggu</th>
                        <th class="text-right" style="width: 10%;">Bobot Lama</th>
                        <th class="text-right" style="width: 10%;">Bobot Baru</th>
                        <th class="text-right pr20" style="width: 10%;">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activity_logs)) { ?>
                        <tr>
                            <td colspan="7" class="text-center p30 text-off">Belum ada log aktivitas progres aktual untuk proyek ini.</td>
                        </tr>
                    <?php } else {
                        foreach ($activity_logs as $log) {
                            $diff = (float)$log->new_actual - (float)$log->old_actual;
                            $diff_class = $diff >= 0 ? 'text-success' : 'text-danger';
                            $diff_sign = $diff >= 0 ? '+' : '';
                            ?>
                            <tr>
                                <td class="pl20"><?php echo format_to_datetime($log->created_at); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-xs mr10">
                                            <img src="<?php echo get_avatar($log->created_by_avatar); ?>" alt="..." />
                                        </span>
                                        <span><?php echo $log->created_by_user; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $log->task_title; ?></td>
                                <td class="text-center">W<?php echo $log->week_number; ?></td>
                                <td class="text-right"><?php echo number_format((float)$log->old_actual, 2); ?>%</td>
                                <td class="text-right"><?php echo number_format((float)$log->new_actual, 2); ?>%</td>
                                <td class="text-right pr20 <?php echo $diff_class; ?>">
                                    <strong><?php echo $diff_sign . number_format($diff, 2); ?>%</strong>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Footer -->
        <?php if ($total_pages > 1) { ?>
            <div class="card-footer bg-white clearfix border-top">
                <nav aria-label="Page navigation" class="float-end">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous Page -->
                        <?php if ($page > 1) { ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo get_uri("project_dashboard/activity_log/" . $project_id . "?page=" . ($page - 1)); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php } else { ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </span>
                            </li>
                        <?php } ?>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo get_uri("project_dashboard/activity_log/" . $project_id . "?page=" . $i); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php } ?>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages) { ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo get_uri("project_dashboard/activity_log/" . $project_id . "?page=" . ($page + 1)); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php } else { ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </span>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
