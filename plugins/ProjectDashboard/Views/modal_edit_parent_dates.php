<?php echo form_open(get_uri("project_dashboard/save_parent_dates"), array("id" => "edit-parent-dates-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />

        <div class="form-group mb15">
            <div class="row">
                <label for="start_date" class="col-md-3">Planned Start Date</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "start_date",
                        "name" => "start_date",
                        "value" => (isset($task_info->start_date) && is_date_exists($task_info->start_date)) ? date("Y-m-d", strtotime($task_info->start_date)) : "",
                        "class" => "form-control",
                        "readonly" => "readonly",
                        "disabled" => "disabled"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <label for="deadline" class="col-md-3">Planned End Date</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "deadline",
                        "name" => "deadline",
                        "value" => (isset($task_info->deadline) && is_date_exists($task_info->deadline)) ? date("Y-m-d", strtotime($task_info->deadline)) : "",
                        "class" => "form-control",
                        "readonly" => "readonly",
                        "disabled" => "disabled"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <label class="col-md-3">Week</label>
                <div class="col-md-9">
                    <input type="text" id="week_display" class="form-control" readonly="readonly" disabled="disabled" value="" />
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <label class="col-md-3">Benchmark Bobot</label>
                <div class="col-md-9 mt5">
                    <strong><span
                            id="benchmark-weight"><?php echo number_format($weight_percentage, 2); ?></span>%</strong>
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <label class="col-md-3">Total Input Bobot</label>
                <div class="col-md-9 mt5">
                    <strong><span id="total-input-weight">0.00</span>%</strong>
                    <span id="weight-validation-status" class="ml10 text-danger font-size-12"></span>
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <label class="col-md-3">Total Aktual Input</label>
                <div class="col-md-9 mt5">
                    <strong><span id="total-actual-weight">0.00</span>%</strong>
                    <span id="actual-validation-status" class="ml10 text-danger font-size-12"></span>
                </div>
            </div>
        </div>

        <div class="form-group mb15">
            <div class="row">
                <div class="col-md-12">
                    <hr />
                    <div class="d-flex justify-content-between align-items-center mb10">
                        <strong>Weekly Weight Distribution</strong>
                        <div>
                            <span class="text-off mr15 text-bold" id="weeks-limit-info" style="font-size:12px;"></span>
                            <button type="button" class="btn btn-info text-white btn-sm" id="btn-add-week"><span
                                    data-feather="plus" class="icon-14"></span> Add Week</button>
                        </div>
                    </div>

                    <div id="weekly-rows-header" class="row mb5 text-bold" style="display:none; font-size:12px; color:#666;">
                        <div class="col-md-4">Week</div>
                        <div class="col-md-3">Plan (%)</div>
                        <div class="col-md-3">Actual (%)</div>
                        <div class="col-md-2 text-center">Action</div>
                    </div>
                    <div id="weekly-rows-container">
                        <!-- Dynamic week rows will be appended here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer" style="border-top: 1px solid #f1f3f5; padding: 15px 20px;">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"
        style="border-radius: 6px; font-weight: 600;"><span data-feather="x" class="icon-16"></span>
        <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"
        style="background-color: #2575fc; border-color: #2575fc; color: #fff; border-radius: 6px; padding: 6px 18px; font-weight: 600; box-shadow: 0 2px 4px rgba(37, 117, 252, 0.15);"><span
            data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var benchmarkWeight = parseFloat($("#benchmark-weight").text()) || 0;
        var startWeek = <?php echo isset($start_week) ? $start_week : 1; ?>;
        var endWeek = <?php echo isset($end_week) ? $end_week : 1; ?>;
        var maxWeeks = endWeek - startWeek + 1;

        $("#weeks-limit-info").text("(Anda hanya bisa menambahkan maksimal " + maxWeeks + " week)");

        var savedWeights = <?php echo json_encode($weekly_weights); ?> || [];

        function calculateTotalInput() {
            var total = 0;
            $(".week-weight-input").each(function () {
                var val = parseFloat($(this).val()) || 0;
                total += val;
            });
            $("#total-input-weight").text(total.toFixed(2));

            if (Math.abs(total - benchmarkWeight) < 0.01) {
                $("#weight-validation-status").html('<span class="text-success"><span data-feather="check" class="icon-14"></span> Valid</span>');
            } else {
                $("#weight-validation-status").html('<span class="text-danger"><span data-feather="alert-circle" class="icon-14"></span> Total harus ' + benchmarkWeight.toFixed(2) + '%</span>');
            }

            // Calculate total actual input
            var totalActual = 0;
            $(".week-actual-input").each(function () {
                var val = parseFloat($(this).val()) || 0;
                totalActual += val;
            });
            $("#total-actual-weight").text(totalActual.toFixed(2));

            if (totalActual > benchmarkWeight + 0.01) {
                $("#actual-validation-status").html('<span class="text-danger"><span data-feather="alert-circle" class="icon-14"></span> Tidak boleh melebihi ' + benchmarkWeight.toFixed(2) + '%</span>');
            } else {
                $("#actual-validation-status").html('<span class="text-success"><span data-feather="check" class="icon-14"></span> Valid</span>');
            }

            var rowCount = $(".weekly-row").length;
            if (rowCount > 0) {
                $("#weekly-rows-header").show();
            } else {
                $("#weekly-rows-header").hide();
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function addWeekRow(weekName, weekValue, actualValue) {
            var currentRows = $(".weekly-row").length;
            if (currentRows >= maxWeeks) {
                appAlert.error("Anda hanya bisa menambahkan maksimal " + maxWeeks + " week.");
                return;
            }

            var nextWeekNum = startWeek;
            if (weekName) {
                var match = weekName.match(/(\d+)/);
                if (match) {
                    nextWeekNum = parseInt(match[1]);
                }
            } else {
                // Find the first available week number between startWeek and endWeek
                var usedWeeks = [];
                $("input[name='weekly_weeks[]']").each(function () {
                    var match = $(this).val().match(/(\d+)/);
                    if (match) {
                        usedWeeks.push(parseInt(match[1]));
                    }
                });

                for (var w = startWeek; w <= endWeek; w++) {
                    if (usedWeeks.indexOf(w) === -1) {
                        nextWeekNum = w;
                        break;
                    }
                }
            }
            var wVal = weekValue !== undefined ? weekValue : "";
            var actVal = actualValue !== undefined ? actualValue : "";

            var weekLabel = 'Week ' + nextWeekNum;

            var rowHtml = '<div class="row mb10 weekly-row align-items-center">' +
                '<div class="col-md-4">' +
                '<input type="text" value="' + weekLabel + '" class="form-control" readonly="readonly" style="background-color:#f0f0f0; font-weight:600;" />' +
                '<input type="hidden" name="weekly_weeks[]" value="' + weekLabel + '" />' +
                '</div>' +
                '<div class="col-md-3">' +
                '<input type="number" step="0.0001" min="0" max="100" name="weekly_values[]" value="' + wVal + '" class="form-control week-weight-input" placeholder="Plan (%)" required />' +
                '</div>' +
                '<div class="col-md-3">' +
                '<input type="number" step="0.0001" min="0" max="100" name="weekly_actuals[]" value="' + actVal + '" class="form-control week-actual-input" placeholder="Actual (%)" />' +
                '</div>' +
                '<div class="col-md-2 text-center">' +
                '<button type="button" class="btn btn-danger btn-sm btn-delete-row"><span data-feather="trash-2" class="icon-14"></span></button>' +
                '</div>' +
                '</div>';

            $("#weekly-rows-container").append(rowHtml);

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            calculateTotalInput();
        }

        // Populate saved weights
        if (savedWeights.length > 0) {
            $.each(savedWeights, function (index, item) {
                var match = item.week_name.match(/(\d+)/);
                var storedWeekNum = match ? parseInt(match[1]) : 0;
                
                var correctWeekNum = storedWeekNum;
                var firstSavedMatch = savedWeights[0].week_name.match(/(\d+)/);
                var firstStoredWeekNum = firstSavedMatch ? parseInt(firstSavedMatch[1]) : 0;
                
                if (firstStoredWeekNum > 0 && firstStoredWeekNum < startWeek) {
                    var shift = startWeek - firstStoredWeekNum;
                    correctWeekNum = storedWeekNum + shift;
                }
                
                if (correctWeekNum <= endWeek) {
                    addWeekRow('Week ' + correctWeekNum, item.weight, item.actual);
                }
            });
        }

        $("#btn-add-week").click(function () {
            addWeekRow();
        });

        $(document).on("click", ".btn-delete-row", function () {
            $(this).closest(".weekly-row").remove();
            calculateTotalInput();
        });

        $(document).on("input change", ".week-weight-input, .week-actual-input", function () {
            calculateTotalInput();
        });

        // Populate the Week display field
        $("#week_display").val(maxWeeks + " Week(s)");

        $("#edit-parent-dates-form").appForm({
            onSubmit: function () {
                var total = 0;
                $(".week-weight-input").each(function () {
                    var val = parseFloat($(this).val()) || 0;
                    total += val;
                });

                if (Math.abs(total - benchmarkWeight) >= 0.01) {
                    appAlert.error("Total bobot mingguan (" + total.toFixed(2) + "%) harus sama dengan bobot total pekerjaan (" + benchmarkWeight.toFixed(2) + "%).");
                    return false;
                }

                var totalActual = 0;
                $(".week-actual-input").each(function () {
                    var val = parseFloat($(this).val()) || 0;
                    totalActual += val;
                });

                if (totalActual > benchmarkWeight + 0.01) {
                    appAlert.error("Total aktual mingguan (" + totalActual.toFixed(2) + "%) tidak boleh melebihi bobot total pekerjaan (" + benchmarkWeight.toFixed(2) + "%).");
                    return false;
                }
            },
            onSuccess: function (result) {
                appAlert.success(result.message, { duration: 10000 });
                setTimeout(function () {
                    location.reload();
                }, 500);
            }
        });

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>