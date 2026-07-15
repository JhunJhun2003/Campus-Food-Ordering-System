<!-- Pagination -->
<div class="p-4 border-t border-gray-100 flex items-center justify-between bg-white">
    <p class="text-sm text-gray-400">
        Showing <span class="font-medium text-gray-600"><?php echo count($foods); ?></span> items
    </p>
    <nav class="inline-flex -space-x-px rounded-md space-x-2" aria-label="Pagination">
        <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </button>
        <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">
            1
        </button>
        <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </button>
    </nav>
</div>