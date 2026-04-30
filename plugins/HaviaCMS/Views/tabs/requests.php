<div class="card-body">
    <h5 class="mb-1">Portfolio Requests</h5>
    <small class="text-muted d-block mb-3">View and manage portfolio download requests submitted from the landing page.</small>

    <?php if (empty($requests)): ?>
        <p class="text-muted text-center py-5"><em>No portfolio requests yet.</em></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Interest</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($requests as $req): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><strong><?php echo htmlspecialchars($req->name); ?></strong></td>
                        <td><?php echo htmlspecialchars($req->contact); ?></td>
                        <td>
                            <?php if ($req->contact_type === 'email'): ?>
                                <span class="badge bg-info">Email</span>
                            <?php elseif ($req->contact_type === 'whatsapp'): ?>
                                <span class="badge bg-success">WhatsApp</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($req->interest); ?></small></td>
                        <td><small class="text-muted"><?php echo date('d M Y H:i', strtotime($req->created_at)); ?></small></td>
                        <td>
                            <?php if ($req->status === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif ($req->status === 'sent'): ?>
                                <span class="badge bg-success">Sent</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($req->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <!-- Direct contact buttons -->
                                <?php if ($req->contact_type === 'email'): ?>
                                    <?php echo modal_anchor(get_uri("landingpage_cms/hero_modal"), '<span data-feather="mail" class="icon-14"></span>', array("class" => "btn btn-info btn-sm", "title" => "Reply Email", "data-post-id" => $req->id, "data-post-task" => "reply_request")); ?>
                                <?php elseif ($req->contact_type === 'whatsapp'):
                                    $wa_number = preg_replace('/[^0-9]/', '', $req->contact);
                                    if (starts_with($wa_number, "0")) {
                                        $wa_number = "62" . substr($wa_number, 1);
                                    }
                                ?>
                                    <a href="https://wa.me/<?php echo $wa_number; ?>?text=Halo%20<?php echo urlencode($req->name); ?>%2C%20terima%20kasih%20sudah%20menghubungi%20Havia%20Studio." class="btn btn-success btn-sm" title="WhatsApp" target="_blank"><span data-feather="message-circle" class="icon-14"></span></a>
                                <?php endif; ?>
                                
                                <?php if ($req->status === 'pending'): ?>
                                    <button class="btn btn-primary btn-sm mark-sent-btn" data-id="<?php echo $req->id; ?>" title="Mark as Sent"><span data-feather="check" class="icon-14"></span></button>
                                <?php endif; ?>
                                
                                <?php echo js_anchor('<span data-feather="trash-2" class="icon-14"></span>', array('title' => 'Delete', "class" => "btn btn-danger btn-sm request-delete-btn", "id" => "delete-request-".$req->id, "data-id" => $req->id, "data-action-url" => get_uri("landingpage_cms/delete_request"))); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    function reloadRequestsTab() {
        $("[data-bs-target='#requests-tab']").trigger("click");
    }

    $(document).ready(function() {
        // Request delete handler
        $(document).on("click", ".request-delete-btn", function(e) {
            e.preventDefault();
            deleteConfirmationHandler(e, function(result, $target) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 5000});
                    reloadRequestsTab();
                } else {
                    appAlert.error(result.message);
                }
            });
        });

        $(".mark-sent-btn").on("click", function() {
            var id = $(this).data("id");
            $.post("<?php echo get_uri('landingpage_cms/mark_request_sent'); ?>", {id: id, <?php echo csrf_token(); ?>: "<?php echo csrf_hash(); ?>"}, function(result) {
                var res = JSON.parse(result);
                if (res.success) {
                    appAlert.success(res.message, {duration: 5000});
                    $("[data-bs-target='#requests-tab']").trigger("click");
                }
            });
        });
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
