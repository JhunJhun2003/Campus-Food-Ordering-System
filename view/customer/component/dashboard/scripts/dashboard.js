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
        const sizes = Array.isArray(item.sizes) ? item.sizes : [];
        const imageSrc = item.image ? item.image : '';
        const imageMarkup = imageSrc
            ? `<img src="${imageSrc}" alt="${item.name}" class="w-full h-full object-cover rounded-xl" onerror="this.onerror=null; this.remove(); this.parentElement.insertAdjacentHTML('beforeend', '<div class=\"w-full h-full flex items-center justify-center text-3.5xl\">`
            : `<div class="w-full h-full flex items-center justify-center text-3.5xl select-none">${item.emoji || '🍽️'}</div>`;

        const defaultSize = (item.activeSizeId && sizes.find(s => s.id == item.activeSizeId)) || sizes.find(s => s.is_default) || (sizes.length > 0 ? sizes[0] : null);
        const initialPrice = defaultSize ? defaultSize.price : item.price;
        const initialStock = defaultSize ? defaultSize.stock : item.stock;
        const sizeButtons = sizes.length > 0 ? sizes.map(size => `
            <button type="button"
                class="size-option-btn text-xs rounded-full border px-2 py-1 ${size === defaultSize ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-slate-200 text-slate-600 bg-white'}"
                data-size-id="${size.id}"
                data-price="${size.price}"
                data-stock="${size.stock}"
                data-name="${size.size_name}">
                ${size.size_name}
            </button>
        `).join('') : '';

        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-150 rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all';
        card.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-slate-50/50 rounded-xl overflow-hidden border border-slate-100 flex items-center justify-center select-none">
                    ${imageMarkup}
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-slate-800">${item.name}</h3>
                    <div class="mt-2 flex flex-wrap gap-2">${sizeButtons}</div>
                    <p class="text-sm font-extrabold text-slate-900 mt-2 price-display">$ ${Number(initialPrice).toFixed(2)}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-medium text-slate-500">Stock: <span class="font-bold ${initialStock <= 3 && initialStock > 0 ? 'text-red-500' : 'text-emerald-600'} stock-display">${initialStock}</span></span>
                        ${initialStock <= 3 && initialStock > 0 ? '<span class="text-xs text-red-500 font-bold low-stock-warning">⚠️ Low Stock</span>' : ''}
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <button class="w-full rounded-lg py-2 text-sm font-semibold add-to-cart-btn ${initialStock > 0 ? 'bg-emerald-500 hover:bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed'}" 
                    data-id="${item.id}" 
                    data-name="${item.name}" 
                    data-stock="${initialStock}" 
                    data-size-id="${defaultSize ? defaultSize.id : ''}"
                    ${initialStock > 0 ? '' : 'disabled'}>
                    ${initialStock > 0 ? 'Add to Cart' : 'Out of Stock'}
                </button>
            </div>
        `;
        container.appendChild(card);
    });

    document.querySelectorAll('.size-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.bg-white');
            const priceDisplay = card.querySelector('.price-display');
            const stockDisplay = card.querySelector('.stock-display');
            const addButton = card.querySelector('.add-to-cart-btn');
            const price = this.dataset.price;
            const stock = this.dataset.stock;
            const sizeId = this.dataset.sizeId;
            const sizeName = this.dataset.name;
            const foodId = addButton ? addButton.dataset.id : null;

            const item = menuDatabase.find(i => i.id == foodId);
            if (item) {
                item.activeSizeId = sizeId;
            }

            if (priceDisplay) priceDisplay.textContent = `$ ${Number(price).toFixed(2)}`;
            if (stockDisplay) {
                const parsedStock = parseInt(stock, 10);
                stockDisplay.textContent = parsedStock;
                stockDisplay.className = `font-bold ${parsedStock <= 3 && parsedStock > 0 ? 'text-red-500' : 'text-emerald-600'} stock-display`;
                
                const warningContainer = stockDisplay.closest('.flex');
                let warningSpan = warningContainer.querySelector('.low-stock-warning');
                if (parsedStock <= 3 && parsedStock > 0) {
                    if (!warningSpan) {
                        warningSpan = document.createElement('span');
                        warningSpan.className = 'text-xs text-red-500 font-bold low-stock-warning';
                        warningSpan.textContent = ' ⚠️ Low Stock';
                        warningContainer.appendChild(warningSpan);
                    }
                } else {
                    if (warningSpan) {
                        warningSpan.remove();
                    }
                }
            }
            if (addButton) {
                addButton.dataset.sizeId = sizeId;
                addButton.dataset.stock = stock;
                addButton.dataset.sizeName = sizeName;
                addButton.disabled = parseInt(stock, 10) <= 0;
                addButton.className = `w-full rounded-lg ${parseInt(stock, 10) <= 0 ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-emerald-500 hover:bg-emerald-600 text-white'} py-2 text-sm font-semibold add-to-cart-btn`;
                addButton.textContent = parseInt(stock, 10) <= 0 ? 'Out of Stock' : 'Add to Cart';
            }

            this.parentElement.querySelectorAll('.size-option-btn').forEach(candidate => {
                candidate.className = `size-option-btn text-xs rounded-full border px-2 py-1 ${candidate === this ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-slate-200 text-slate-600 bg-white'}`;
            });
        });
    });

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.dataset.id;
            const foodName = this.dataset.name;
            const stock = parseInt(this.dataset.stock || '0', 10);
            const sizeId = this.dataset.sizeId;
            if (stock <= 0) {
                showToast('Sorry, this item is out of stock!', false);
                return;
            }
            addToCart(foodName, foodId, stock, sizeId);
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
function addToCart(dishName, foodId, availableStock, foodSizeId = null) {
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

    const body = new URLSearchParams({
        food_id: foodId,
        quantity: '1',
        food_size_id: foodSizeId || ''
    });

    fetch('/Campus-Food-Ordering-System/view/customer/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
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
                if (foodSizeId && Array.isArray(item.sizes)) {
                    const size = item.sizes.find(s => s.id == foodSizeId);
                    if (size) {
                        size.stock = Math.max(0, (size.stock || 0) - 1);
                    }
                } else {
                    item.stock = Math.max(0, (item.stock || 0) - 1);
                }
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