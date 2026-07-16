<!-- Menu Table -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse text-left">
        <thead>
            <tr class="bg-gray-50/50 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                <th class="py-3 px-6">Item</th>
                <th class="py-3 px-6">Category</th>
                <th class="py-3 px-6">Status</th>
                <th class="py-3 px-6">Preparing Time</th>
                <th class="py-3 px-6">Details</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody id="menuTableBody" class="divide-y divide-gray-100 text-sm text-gray-700">
            <?php if (empty($foods)): ?>
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-400">
                        <i class="fa-regular fa-utensils text-4xl block mb-3"></i>
                        <p class="text-sm font-medium">No food items found</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($foods as $food): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors" data-category="<?php echo $food->getCategoryId(); ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <?php if ($food->getImage()): ?>
                                    <?php 
                                        $imageFile = $food->getImage();
                                        $imagePath = '/Campus-Food-Ordering-System/Public/uploads/foods/' . rawurlencode($imageFile);
                                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Campus-Food-Ordering-System/Public/uploads/foods/' . $imageFile;
                                        if (file_exists($fullPath)): 
                                    ?>
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($food->getName()); ?>" 
                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                            <?php 
                                                $emoji = match($food->getCategoryId()) {
                                                    1 => '🍔',
                                                    2 => '🍕',
                                                    3 => '🥤',
                                                    4 => '🍰',
                                                    5 => '🍚',
                                                    default => '🍽️'
                                                };
                                                echo $emoji;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                        <?php 
                                            $emoji = match($food->getCategoryId()) {
                                                1 => '🍔',
                                                2 => '🍕',
                                                3 => '🥤',
                                                4 => '🍰',
                                                5 => '🍚',
                                                default => '🍽️'
                                            };
                                            echo $emoji;
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($food->getName()); ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-gray-600">
                            <?php 
                                $categoryName = '';
                                $availableCategories = $categories ?? [];
                                foreach ($availableCategories as $cat) {
                                    if ($cat['id'] == $food->getCategoryId()) {
                                        $categoryName = $cat['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($categoryName ?: 'Uncategorized');
                            ?>
                        </td>
                        <td class="py-4 px-6">
                            <?php 
                                $status = match($food->getStatusId()) {
                                    1 => ['name' => 'Active', 'class' => 'bg-emerald-100 text-emerald-800'],
                                    2 => ['name' => 'Inactive', 'class' => 'bg-gray-100 text-gray-800'],
                                    3 => ['name' => 'Out of Stock', 'class' => 'bg-red-100 text-red-800'],
                                    default => ['name' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800']
                                };
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status['class']; ?>">
                                <?php echo $status['name']; ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-gray-600">
                            <?php echo $food->getPreparationTime(); ?> mins
                        </td>
                        <td class="py-4 px-6 text-gray-600 max-w-xs truncate">
                            <?php echo htmlspecialchars($food->getDescription()); ?>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center space-x-3">
                                <button onclick="openEditFoodModal(<?php echo $food->getId(); ?>)" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event);">
                                    <input type="hidden" name="delete_food" value="1">
                                    <input type="hidden" name="food_id" value="<?php echo $food->getId(); ?>">
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>