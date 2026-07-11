<!-- Categories Filter -->
<div class="mb-10">
    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Categories</p>
    <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap pb-2">
        <button onclick="filterCategory('all')" id="cat-all" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition">All</button>
        <?php foreach ($categoryNames as $name): ?>
            <button onclick="filterCategory('<?php echo strtolower($name); ?>')" id="cat-<?php echo strtolower($name); ?>" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition">
                <?php echo ($categoryEmojis[$name] ?? '🍽️') . ' ' . $name; ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>