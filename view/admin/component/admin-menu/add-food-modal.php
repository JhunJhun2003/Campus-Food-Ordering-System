<!-- Add Food Modal -->
<div id="addFoodModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Add New Food Item</h2>
            <button onclick="closeAddFoodModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <?php if (isset($message) && !$message['success'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message['message']); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="addFoodForm" enctype="multipart/form-data">
            <input type="hidden" name="add_food" value="1">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" placeholder="e.g., Cheese Burger" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prep Time (mins)</label>
                    <input type="number" name="preparation_time" placeholder="15" min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Sizes</label>
                <div class="space-y-2" id="add-size-list">
                    <div class="flex items-center gap-2">
                        <input type="text" name="size_name[]" placeholder="e.g. Small" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                        <input type="number" name="size_price[]" placeholder="0.00" step="0.01" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                        <input type="number" name="size_stock[]" placeholder="0" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">

                    </div>
                </div>
                <button type="button" onclick="addSizeRow('add-size-list', 'add')" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    <i class="fa-solid fa-plus mr-1"></i>Add another size
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Describe the food item..." class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm"></textarea>
            </div>
            
            <!-- IMAGE UPLOAD -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Food Image</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-lg hover:border-indigo-500 transition-colors cursor-pointer" id="addImageDropZone">
                    <div class="space-y-1 text-center">
                        <i class="fa-regular fa-image text-3xl text-slate-400"></i>
                        <div class="flex text-sm text-slate-600">
                            <label for="addFoodImage" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload a file</span>
                                <input id="addFoodImage" name="image" type="file" class="sr-only" accept="image/*" onchange="previewImage(event, 'addImagePreview')">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">PNG, JPG, GIF up to 2MB</p>
                        <div id="addImagePreview" class="hidden mt-2">
                            <img id="addImagePreviewImg" src="#" alt="Preview" class="max-h-32 mx-auto rounded-lg border border-slate-200">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Add Food Item</button>
            <button type="button" onclick="closeAddFoodModal()" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
        </form>
    </div>
</div>