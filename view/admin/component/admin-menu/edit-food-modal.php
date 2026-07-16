<!-- Edit Food Modal -->
<?php if ($editFood): ?>
<div id="editFoodModal" class="modal-overlay fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Edit Food Item</h2>
            <a href="admin-menu.php" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>

        <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message['message']); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="edit_food" value="1">
            <input type="hidden" name="food_id" value="<?php echo $editFood['id']; ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editFood['name']); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $editFood['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prep Time (mins)</label>
                    <input type="number" name="preparation_time" value="<?php echo $editFood['preparation_time'] ?? 15; ?>" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Sizes</label>
                <div class="space-y-2" id="edit-size-list">
                    <?php
                    $sizes = [];
                    if (!empty($editFood['id'])) {
                        $foodSizes = $foodController->getSizes((int) $editFood['id']);
                        $sizes = $foodSizes;
                    }
                    if (empty($sizes)):
                        $sizes = [new \App\Food\Domain\Entities\FoodSize(null, (int) $editFood['id'], 'Default', (float) $editFood['price'], (int) $editFood['stock'], true)];
                    endif;
                    foreach ($sizes as $index => $size):
                    ?>
                        <div class="flex items-center gap-2">
                            <input type="text" name="size_name[]" value="<?php echo htmlspecialchars($size->getSizeName()); ?>" placeholder="e.g. Small" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <input type="number" name="size_price[]" value="<?php echo number_format($size->getPrice(), 2, '.', ''); ?>" placeholder="0.00" step="0.01" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <input type="number" name="size_stock[]" value="<?php echo $size->getStock(); ?>" placeholder="0" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addSizeRow('edit-size-list', 'edit')" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    <i class="fa-solid fa-plus mr-1"></i>Add another size
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm"><?php echo htmlspecialchars($editFood['description'] ?? ''); ?></textarea>
            </div>
            
            <!-- IMAGE UPLOAD WITH CURRENT IMAGE -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Image</label>
                <?php if (!empty($editFood['image'])): ?>
                    <div class="mb-2">
                        <img src="/Campus-Food-Ordering-System/Public/uploads/foods/<?php echo htmlspecialchars($editFood['image']); ?>" 
                             alt="Current Image" 
                             class="max-h-20 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-500 mt-1">Current image: <?php echo htmlspecialchars($editFood['image']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-lg hover:border-indigo-500 transition-colors cursor-pointer" id="editImageDropZone">
                    <div class="space-y-1 text-center">
                        <i class="fa-regular fa-image text-3xl text-slate-400"></i>
                        <div class="flex text-sm text-slate-600">
                            <label for="editFoodImage" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload new image</span>
                                <input id="editFoodImage" name="image" type="file" class="sr-only" accept="image/*" onchange="previewImage(event, 'editImagePreview')">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">PNG, JPG, GIF up to 2MB</p>
                        <div id="editImagePreview" class="hidden mt-2">
                            <img id="editImagePreviewImg" src="#" alt="Preview" class="max-h-32 mx-auto rounded-lg border border-slate-200">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editFood['image'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update Food Item</button>
            <a href="admin-menu.php" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm block text-center">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>