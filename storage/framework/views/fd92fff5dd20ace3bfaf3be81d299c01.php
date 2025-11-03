

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 100%; border-radius: 1rem;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mb-3" style="padding-bottom: 40px;"><a class="btn btn-primary" href="<?php echo e(route('emails.index')); ?>" style="float:left;">Inbox</a><a class="btn btn-primary" href="<?php echo e(route('auth.google')); ?>" style="float: right;">Connect with another GMail account</a></h4>
                </div>
            </div>
            <div class="row">
                
                <div class="col-md-7">
                    <h4 class="mb-3">Categories</h4>
                    <div class="category-list mt-3">
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="category-item d-flex justify-content-between align-items-start p-3 mb-3 border rounded shadow-sm">
                                <div>
                                    <h5 class="mb-1">
                                        <a href="<?php echo e(route('category.emails.index', $cat->id)); ?>" class="text-decoration-none category-name">
                                            <?php echo e($cat->name); ?>

                                        </a>
                                        <span class="badge bg-secondary ms-2"><?php echo e($cat->emails_count); ?></span>
                                    </h5>
                                    <p class="mb-0 text-muted"><?php echo e($cat->description); ?></p>
                                </div>
        
                                <div class="btn-group">
                                    <a href="<?php echo e(route('categories.edit', $cat->id)); ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="<?php echo e(route('categories.destroy', $cat->id)); ?>" method="POST" onsubmit="return confirm('Delete this category?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
                        <?php if($categories->isEmpty()): ?>
                            <p class="text-center text-muted mt-4">No categories yet. Add one to get started!</p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-center bt-pagination">
                            <?php echo e($categories->links()); ?>

                        </div>
                    </div>
                </div>
        
                
                <div class="col-md-5">
                    <h4 class="mb-3">Add New Category</h4>
                    <form action="<?php echo e(route('categories.store')); ?>" method="POST" class="category-form mt-3">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Enter category name" required>
                        </div>
        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                        </div>
        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\vstep\ai-email-manager\resources\views/categories/index.blade.php ENDPATH**/ ?>