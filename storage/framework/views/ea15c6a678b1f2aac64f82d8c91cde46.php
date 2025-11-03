

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 100%; border-radius: 1rem;">
        <div class="card-body">
            <h2>Gmail Messages for Category: <strong><?php echo e($category->name); ?></strong></h2>
            <form id="bulkActionForm" method="POST" action="<?php echo e(route('emails.bulkAction')); ?>"> <?php echo csrf_field(); ?>
           
                <div style="text-align:right;">
                    <button type="button" name="action1" value="delete" class="btn btn-danger btn-sm" onclick="checkCheckBoxes('delete');">
                        <i class="bi bi-trash"></i> Move to Trash
                    </button>
                    <button type="button" name="action2" value="archive" class="btn btn-secondary btn-sm me-2" onclick="checkCheckBoxes('unsubscribe');">
                        <i class="bi bi-box-arrow-right"></i> Unsubscribe
                    </button>
                    <input type="hidden" name="action" id="action" value="" />
                </div>
            <div class="list-group mt-3">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><div class="form-check"><input class="form-check-input" type="checkbox" id="selectAll"></div></th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>AI Summary</th>
                            <th>Date</th>
                            <?php if($showAccount): ?>
                            <th>Gmail Account</th>
				            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $emails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                            	<td><input type="checkbox" name="email_ids[]" value="<?php echo e($msg->gmail_id); ?>" class="email-checkbox"></td>
                                <td><?php echo e($msg['from']); ?></td>
                                <td><a href="<?php echo e(route('emails.show', $msg['gmail_id'])); ?>"><?php echo e($msg['subject']); ?></a></td>
                                <td><?php echo e($msg['summary'] ?? '(processing...)'); ?></td>
                                <td><?php echo e($msg->created_at->format('Y-m-d H:i')); ?></td>
                                <?php if($showAccount): ?>
                                <td><?php echo e($msg->account_email); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">
                                <div class="d-flex justify-content-center bt-pagination">
                                    <?php echo e($emails->links()); ?>

                                </div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.email-checkbox');
    checkboxes.forEach(cb => cb.checked = e.target.checked);
});
function checkCheckBoxes(action){
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
	if( checkboxes.length == 0 ){
		alert('Please select at least one Email.');
		return;
	}
	document.getElementById('action').value = action;
	document.getElementById('bulkActionForm').submit();
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\vstep\ai-email-manager\resources\views/emails/cat_index.blade.php ENDPATH**/ ?>