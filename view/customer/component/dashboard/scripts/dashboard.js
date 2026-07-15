// ============================================
// DASHBOARD - MENU FUNCTIONALITY
// ============================================

/**
 * Render menu grid with filtered items
 */
function renderMenuGrid() {
    const container = document.getElementById('menu-grid-container');
    const emptyState = document.getElementById('empty-state');
    container.innerHTML = '';

    const filteredItems = menuDatabase.filter(item => {
        const matchesCategory = (activeCategory === 'all' || item.category.toLowerCase() === activeCategory);
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesCategory && matchesSearch;
    });

    if (filteredItems.length === 0) {
        emptyState.classList.remove('hidden');
        container.classList.add('hidden');
        return;
    }

    emptyState.classList.add('hidden');
    container.classList.remove('hidden');

    filteredItems.forEach(item => {
        const isOutOfStock = (item.stock || 0) <= 0;
        const stockDisplay = item.stock || 0;
        const imageSrc = item.image ? item.image : '';
        const imageMarkup = imageSrc
            ? `<img src="${imageSrc}" alt="${item.name}" class="w-full h-full object-cover rounded-xl" onerror="this.onerror=null; this.remove(); this.parentElement.insertAdjacentHTML('beforeend', '<div class=\"w-full h-full flex items-center justify-center text-3.5xl\">`
            : `<div class="w-full h-full flex items-center justify-center text-3.5xl select-none">${item.emoji || '🍽️'}</div>`;
        
        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-150 rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all flex items-center justify-between';
        card.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-slate-50/50 rounded-xl overflow-hidden border border-slate-100 flex items-center justify-center select-none">
                    ${imageMarkup}
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">${item.name}</h3>
                    <p class="text-sm font-extrabold text-slate-900 mt-1">$ ${item.price}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-medium text-slate-500">Stock: <span class="font-bold ${stockDisplay <= 3 ? 'text-red-500' : 'text-emerald-600'}">${stockDisplay}</span></span>
                        ${stockDisplay <= 3 && stockDisplay > 0 ? '<span class="text-xs text-red-500 font-bold">⚠️ Low Stock</span>' : ''}
                    </div>
                </div>
            </div>
            ${isOutOfStock ? 
                `<span class="text-xs font-bold text-red-500 bg-red-50 px-3 py-1 rounded-full">Out of Stock</span>` :
                `<button class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/10 interactive-transition hover:scale-105 active:scale-95 add-to-cart-btn" data-id="${item.id}" data-name="${item.name}" data-stock="${item.stock}">
                    <i class="fa-solid fa-plus text-sm"></i>
                </button>`
            }
        `;
        container.appendChild(card);
    });

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.dataset.id;
            const foodName = this.dataset.name;
            const stock = parseInt(this.dataset.stock);
            if (stock <= 0) {
                showToast('Sorry, this item is out of stock!', false);
                return;
            }
            addToCart(foodName, foodId, stock);
        });
    });
}

/**
 * Filter menu by category
 */
function filterCategory(category) {
    activeCategory = category;
    document.querySelectorAll('.flex.items-center.gap-3 button').forEach(btn => {
        btn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-700 border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 interactive-transition';
    });
    const activeBtn = document.getElementById(`cat-${category}`);
    if (activeBtn) {
        activeBtn.className = 'px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white border border-emerald-500 shadow-sm interactive-transition';
    }
    renderMenuGrid();
}

/**
 * Handle search input
 */
function handleSearch() {
    searchQuery = document.getElementById('menu-search-input').value;
    renderMenuGrid();
}

/**
 * Add item to cart
 */
function addToCart(dishName, foodId, availableStock) {
    // ✅ This will be replaced with actual value from PHP
    const isLoggedIn = typeof isUserLoggedIn !== 'undefined' ? isUserLoggedIn : false;
    
    if (!isLoggedIn) {
        window.location.href = '/Campus-Food-Ordering-System/view/entrance/login.php';
        return;
    }

    const buttons = document.querySelectorAll('.add-to-cart-btn');
    buttons.forEach(btn => {
        if (btn.dataset.id == foodId) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i>';
            btn.disabled = true;
        }
    });

    fetch('/Campus-Food-Ordering-System/view/customer/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `food_id=${foodId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        buttons.forEach(btn => {
            if (btn.dataset.id == foodId) {
                btn.innerHTML = '<i class="fa-solid fa-plus text-sm"></i>';
                btn.disabled = false;
            }
        });
        if (data.success) {
            cartItemCount = data.item_count || cartItemCount + 1;
            updateCartBadge();
            const item = menuDatabase.find(i => i.id == foodId);
            if (item) {
                item.stock = (item.stock || 0) - 1;
                renderMenuGrid();
            }
            showToast(`"${dishName}" added to cart! 🛒`);
        } else {
            showToast(data.message || 'Failed to add item', false);
        }
    })
    .catch(() => {
        buttons.forEach(btn => {
            if (btn.dataset.id == foodId) {
                btn.innerHTML = '<i class="fa-solid fa-plus text-sm"></i>';
                btn.disabled = false;
            }
        });
        showToast('Failed to add item to cart', false);
    });
}

/**
 * Update cart badge
 */
function updateCartBadge() {
    const badge = document.getElementById('header-cart-badge');
    if (badge) {
        if (cartItemCount > 0) {
            badge.textContent = cartItemCount;
            badge.classList.remove('hidden');
            badge.classList.add('scale-125', 'bg-emerald-600');
            setTimeout(() => {
                badge.classList.remove('scale-125', 'bg-emerald-600');
                badge.classList.add('scale-100');
            }, 300);
        } else {
            badge.classList.add('hidden');
        }
    }
}

/**
 * Show toast notification
 */
function showToast(message, isSuccess = true) {
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.remove('translate-y-24', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}

// Initialize on page load
window.addEventListener('DOMContentLoaded', function() {
    renderMenuGrid();
});

window.addEventListener('load', function() {
    renderMenuGrid();
});