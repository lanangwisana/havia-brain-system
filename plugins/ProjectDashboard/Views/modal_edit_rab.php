<?php echo form_open(get_uri("project_dashboard/save_rab_weight"), array("id" => "edit-rab-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        
        <?php 
        $approval_status = $model_info ? ($model_info->approval_status ?: 'Approved') : 'Approved';
        $pending_nominal_rab = $model_info ? $model_info->pending_nominal_rab : null;
        $nominal_rab = $model_info ? $model_info->nominal_rab : 0;
        $reject_reason = $model_info ? ($model_info->reject_reason ?? null) : null;
        
        $display_val = ($approval_status === 'Pending' && $pending_nominal_rab !== null) ? $pending_nominal_rab : $nominal_rab;
        ?>

        <?php if ($approval_status === 'Pending') { ?>
            <div class="alert alert-warning p15" style="background-color: #fffdf6; border: 1px solid #ffe8b5; border-left: 5px solid #ffb822; border-radius: 8px; margin-bottom: 20px;">
                <div class="d-flex align-items-center">
                    <i data-feather="clock" class="icon-18 mr10 text-warning" style="stroke: #d39e00;"></i>
                    <div>
                        <strong style="color: #856404; font-size: 14px;">Menunggu Persetujuan (Pending Approval)</strong>
                        <div style="font-size: 12px; color: #666;" class="mt2">
                            <?php if ($login_user->is_admin) { ?>
                                Tinjau pengajuan nominal RAB baru di bawah sebelum menyetujui atau menolak.
                            <?php } else { ?>
                                Perubahan nominal RAB sedang ditinjau oleh Admin. Form dikunci sementara.
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else if ($approval_status === 'Rejected') { ?>
            <div class="alert alert-danger p15" style="background-color: #fff8f8; border: 1px solid #ffcccc; border-left: 5px solid #ef4444; border-radius: 8px; margin-bottom: 20px;">
                <div class="d-flex align-items-center">
                    <i data-feather="x-circle" class="icon-18 mr10 text-danger" style="stroke: #ef4444;"></i>
                    <div>
                        <strong style="color: #b91c1c; font-size: 14px;">Pengajuan Sebelumnya Ditolak</strong>
                        <div style="font-size: 12px; color: #7f1d1d;" class="mt2">
                            Pengajuan nominal RAB ini ditolak oleh Admin. Silakan masukkan nominal baru dan simpan untuk mengajukan kembali.
                        </div>
                        <?php if ($reject_reason) { ?>
                            <div class="mt5 p10" style="background: #fff0f0; border-radius: 6px; border: 1px solid #ffb3b3;">
                                <strong style="font-size: 12px; color: #b91c1c;"><i data-feather="message-square" class="icon-12 mr5"></i>Alasan Penolakan:</strong>
                                <div style="font-size: 12px; color: #7f1d1d; margin-top: 4px;"><?php echo htmlspecialchars($reject_reason); ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        
        <div class="form-group">
            <div class="row">
                <label for="nominal_rab" class="col-md-3">Nominal RAB</label>
                <div class="col-md-9">
                    <?php
                    $input_data = array(
                        "id" => "nominal_rab",
                        "name" => "nominal_rab",
                        "value" => $display_val ? number_format($display_val, 2, '.', '') : "",
                        "class" => "form-control",
                        "placeholder" => "0.00",
                        "type" => "number",
                        "step" => "0.01"
                    );
                    if ($approval_status === 'Pending') {
                        $input_data["readonly"] = "readonly";
                    }
                    echo form_input($input_data);
                    ?>
                </div>
            </div>
        </div>

        <!-- Reject Reason Input (only for admin when pending) -->
        <?php if ($approval_status === 'Pending' && $login_user->is_admin) { ?>
        <div id="reject-reason-section" style="display: none; margin-top: 15px; padding: 15px; background: #fff8f8; border: 1px solid #ffcccc; border-radius: 8px;">
            <label style="font-weight: 600; color: #b91c1c; font-size: 13px; margin-bottom: 6px; display: block;">
                <i data-feather="message-square" class="icon-14 mr5"></i>Alasan Penolakan <span style="color: #ef4444;">*</span>
            </label>
            <textarea id="reject_reason_input" name="reject_reason" class="form-control" rows="3" placeholder="Tuliskan alasan mengapa pengajuan ini ditolak..." style="border-color: #ffb3b3; font-size: 13px; resize: vertical;"></textarea>
            <div class="mt10 d-flex" style="gap: 8px;">
                <button type="button" id="btn-confirm-reject" class="btn btn-danger btn-sm" style="background-color: #ef4444; border-color: #ef4444; color: #fff; border-radius: 6px; font-weight: 600;">
                    <span data-feather="x-circle" class="icon-14"></span> Konfirmasi Tolak
                </button>
                <button type="button" id="btn-cancel-reject" class="btn btn-default btn-sm" style="border-radius: 6px;">
                    Batal
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<div class="modal-footer" style="border-top: 1px solid #f1f3f5; padding: 15px 20px;">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" style="border-radius: 6px; font-weight: 600;"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <?php if ($approval_status === 'Pending') { ?>
        <?php if ($login_user->is_admin) { ?>
            <button type="button" class="btn btn-danger" id="btn-reject-rab" style="background-color: #ef4444; border-color: #ef4444; color: #fff; border-radius: 6px; padding: 6px 18px; font-weight: 600; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.15);"><span data-feather="x-circle" class="icon-16"></span> Reject</button>
            <button type="button" class="btn btn-success" id="btn-approve-rab" style="background-color: #10b981; border-color: #10b981; color: #fff; border-radius: 6px; padding: 6px 18px; font-weight: 600; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.15);"><span data-feather="check-circle" class="icon-16"></span> Approve</button>
        <?php } else { ?>
            <!-- hidden save button for PM when pending -->
        <?php } ?>
    <?php } else { ?>
        <button type="submit" class="btn btn-primary" style="background-color: #2575fc; border-color: #2575fc; color: #fff; border-radius: 6px; padding: 6px 18px; font-weight: 600; box-shadow: 0 2px 4px rgba(37, 117, 252, 0.15);"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    <?php } ?>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var approvalStatus = "<?php echo $approval_status; ?>";
        var isAdmin = <?php echo $login_user->is_admin ? 'true' : 'false'; ?>;

        $("#edit-rab-form").appForm({
            onSubmit: function() {
                if (approvalStatus === 'Pending') {
                    return false; // Prevent submit if pending
                }
            },
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
                setTimeout(function () {
                    location.reload();
                }, 500);
            }
        });
        
        $("#btn-approve-rab").click(function () {
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('project_dashboard/approve_rab'); ?>",
                type: "POST",
                data: {
                    task_id: "<?php echo $task_id; ?>",
                    project_id: "<?php echo $project_id; ?>"
                },
                success: function (result) {
                    var response = typeof result === 'string' ? JSON.parse(result) : result;
                    if (response.success) {
                        appAlert.success(response.message, {duration: 10000});
                        setTimeout(function () { location.reload(); }, 500);
                    } else {
                        appAlert.error(response.message);
                    }
                    appLoader.hide();
                }
            });
        });

        // Show reject reason form on Reject button click
        $("#btn-reject-rab").click(function () {
            $("#reject-reason-section").slideDown(200);
            $(this).hide();
            $("#btn-approve-rab").prop("disabled", true).css("opacity", "0.5");
            if (typeof feather !== 'undefined') feather.replace();
        });

        // Cancel reject
        $("#btn-cancel-reject").click(function () {
            $("#reject-reason-section").slideUp(200);
            $("#btn-reject-rab").show();
            $("#btn-approve-rab").prop("disabled", false).css("opacity", "1");
            $("#reject_reason_input").val('');
        });

        // Confirm reject with reason
        $("#btn-confirm-reject").click(function () {
            var reason = $.trim($("#reject_reason_input").val());
            if (!reason) {
                appAlert.error("Harap isi alasan penolakan terlebih dahulu.");
                $("#reject_reason_input").focus();
                return;
            }
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('project_dashboard/reject_rab'); ?>",
                type: "POST",
                data: {
                    task_id: "<?php echo $task_id; ?>",
                    project_id: "<?php echo $project_id; ?>",
                    reject_reason: reason
                },
                success: function (result) {
                    var response = typeof result === 'string' ? JSON.parse(result) : result;
                    if (response.success) {
                        appAlert.success(response.message, {duration: 10000});
                        setTimeout(function () { location.reload(); }, 500);
                    } else {
                        appAlert.error(response.message);
                    }
                    appLoader.hide();
                }
            });
        });

        if (approvalStatus !== 'Pending') {
            setTimeout(function () {
                $("#nominal_rab").focus();
            }, 200);
        }

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
