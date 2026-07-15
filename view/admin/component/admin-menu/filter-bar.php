<!-- Filter Bar -->
<div class="p-5 flex items-center justify-between border-b border-gray-50">
    <div class="relative w-full max-w-xl">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
            <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
        </span>
        <input type="text" id="searchInput" placeholder="Search food items..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
    </div>
    <div class="flex items-center space-x-3">
        <select id="categoryFilter" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
        </button>
    </div>
</div>