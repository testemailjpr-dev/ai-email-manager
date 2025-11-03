

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 400px; border-radius: 1rem;">
        <div class="card-body text-center">
            <h2>Welcome, <?php echo e(Auth::user()->name); ?>!</h2>
            <p class="text-muted">Your AI Email Manager dashboard</p>
        
            <a href="<?php echo e(route('categories.index')); ?>" class="btn btn-primary mt-3">
                Manage Categories
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\vstep\ai-email-manager\resources\views/dashboard.blade.php ENDPATH**/ ?>