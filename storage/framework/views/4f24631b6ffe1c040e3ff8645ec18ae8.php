

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 90%; border-radius: 1rem;">
        <div class="card-body show-email">
            <?php if($showAccount): ?>
            <h4><strong>Gmail Account:</strong> <?php echo e($email->account_email); ?></h4><hr>
            <?php endif; ?>
            <h3><?php echo e($email['subject']); ?></h3>
            <p><strong>From:</strong> <?php echo e($email['from']); ?></p>
            <p><strong>Date:</strong> <?php echo e($email->created_at->format('Y-m-d H:i')); ?></p>
            <p><strong>AI Summary:</strong> <?php echo e($email['summary']); ?></p>
            <hr>
            <div class="email-body">
                <strong>Message:</strong>
                <?php echo $email['body']; ?>

            </div>
        
            <a href="<?php echo e(route('category.emails.index', $email->category_id)); ?>" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to list</a>
            
            <a href="<?php echo e(route('emails.unsubscribe', $email->gmail_id)); ?>" class="btn btn-secondary mt-3" style="float:right;"><i class="bi bi-box-arrow-right"></i> Unsubscribe</a>
            <a href="<?php echo e(route('emails.trash', $email->gmail_id)); ?>" class="btn btn-danger mt-3" style="float:right; margin-right: 15px;"><i class="bi bi-trash"></i> Move to Trash</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\vstep\ai-email-manager\resources\views/emails/show.blade.php ENDPATH**/ ?>