<script>
// ============================================
// SEARCH
// ============================================
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#menuTableBody tr').forEach(row => {
        const name = row.querySelector('td:first-child span.font-medium')?.textContent?.toLowerCase() || '';
        const category = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        row.style.display = (name.includes(searchTerm) || category.includes(searchTerm)) ? '' : 'none';
    });
});

// ============================================
// CATEGORY FILTER
// ============================================
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    document.querySelectorAll('#menuTableBody tr').forEach(row => {
        const rowCategory = row.dataset.category || '';
        row.style.display = (categoryId === '' || rowCategory === categoryId) ? '' : 'none';
    });
});

// ============================================
// ADD FOOD MODAL
// ============================================
function openAddFoodModal() {
    document.getElementById('addFoodModal').classList.remove('hidden');
    document.getElementById('addFoodModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddFoodModal() {
    document.getElementById('addFoodModal').classList.add('hidden');
    document.getElementById('addFoodModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('addFoodForm')?.reset();
    document.getElementById('addImagePreview')?.classList.add('hidden');
}

// ============================================
// EDIT FOOD MODAL
// ============================================
function openEditFoodModal(foodId) {
    window.location.href = 'admin-menu.php?edit=' + foodId;
}

function closeEditFoodModal() {
    window.location.href = 'admin-menu.php';
}

// ============================================
// DELETE CONFIRM
// ============================================
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this food item? This action cannot be undone.')) {
        event.preventDefault();
        return false;
    }
    return true;
}

// ============================================
// IMAGE PREVIEW
// ============================================
function previewImage(event, previewId) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewContainer = document.getElementById(previewId);
        const previewImg = document.getElementById(previewId + 'Img');
        previewImg.src = e.target.result;
        previewContainer.classList.remove('hidden');
        previewContainer.classList.add('block');
    }
    reader.readAsDataURL(file);
}

function addSizeRow(containerId, mode = 'add') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 size-row';
    row.innerHTML = `
        <input type="hidden" name="size_id[]" value="">
        <input type="text" name="size_name[]" placeholder="e.g. Large" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
        <input type="number" name="size_price[]" placeholder="0.00" step="0.01" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">
        <input type="number" name="size_stock[]" placeholder="0" min="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm">
        <button type="button" onclick="removeSizeRow(this, '${containerId}', null, '${containerId === 'edit-size-list' ? 'edit-deleted-size-ids' : ''}')" class="size-delete-btn rounded-lg border border-rose-200 bg-rose-50 p-2 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-colors" title="Remove size">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(row);
    updateSizeRowButtons(containerId);
}

function removeSizeRow(button, containerId, sizeId = null, deletedContainerId = null) {
    const container = document.getElementById(containerId);
    const row = button.closest('.size-row');
    if (!container || !row) return;

    const sizeRows = container.querySelectorAll('.size-row');
    if (sizeRows.length <= 1) {
        showToast('At least one size is required.', 'error');
        return;
    }

    if (sizeId && deletedContainerId) {
        const deletedContainer = document.getElementById(deletedContainerId);
        if (deletedContainer) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'deleted_size_ids[]';
            hiddenInput.value = sizeId;
            deletedContainer.appendChild(hiddenInput);
        }
    }

    row.remove();
    updateSizeRowButtons(containerId);
}

function updateSizeRowButtons(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const rows = container.querySelectorAll('.size-row');
    rows.forEach(row => {
        const button = row.querySelector('.size-delete-btn');
        if (!button) return;

        const isLastRow = rows.length <= 1;
        button.disabled = isLastRow;
        button.className = `size-delete-btn rounded-lg p-2 transition-colors ${isLastRow ? 'border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed' : 'border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700'}`;
    });
}

// ============================================
// DRAG AND DROP SUPPORT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Add drag and drop for add modal
    const addDropZone = document.getElementById('addImageDropZone');
    if (addDropZone) {
        addDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-500', 'bg-indigo-50');
        });
        addDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        addDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('addFoodImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        });
    }
    
    // Add drag and drop for edit modal
    const editDropZone = document.getElementById('editImageDropZone');
    if (editDropZone) {
        editDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-500', 'bg-indigo-50');
        });
        editDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        editDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-indigo-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('editFoodImage');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        });
    }
});

// ============================================
// PREVENT DOUBLE FORM SUBMISSION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';
            }
        });
    });
});

// ============================================
// TOAST
// ============================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg transform transition-all duration-300 z-50 max-w-md';
    const colors = {
        success: { bg: '#10B981', text: 'white' },
        error: { bg: '#EF4444', text: 'white' },
        info: { bg: '#3B82F6', text: 'white' }
    };
    const style = colors[type] || colors.success;
    toast.style.background = style.bg;
    toast.style.color = style.text;
    setTimeout(() => {
        toast.classList.remove('translate-y-24', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}

<?php if (isset($message)): ?>
    <?php if ($message['success']): ?>
        showToast('<?php echo htmlspecialchars($message['message']); ?>', 'success');
    <?php else: ?>
        showToast('<?php echo htmlspecialchars($message['message']); ?>', 'error');
    <?php endif; ?>
<?php endif; ?>
</script>