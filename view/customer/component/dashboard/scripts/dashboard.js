// ============================================
// DASHBOARD - MENU FUNCTIONALITY
// ============================================

let currentPage = 1;

function getDashboardCurrencySymbol() {
    return window.AppSettings && window.AppSettings.currencySymbol
        ? window.AppSettings.currencySymbol
        : '$';
}

function formatDashboardPrice(price) {
    const parsedPrice = Number(price);
    return `${getDashboardCurrencySymbol()}${Number.isFinite(parsedPrice) ? parsedPrice.toFixed(2) : '0.00'}`;
}

/**
 * Determine dynamic page size to show exactly 3 rows based on screen width/columns
 */
function getPageSize() {
    const width = window.innerWidth;
    if (width >= 1280) return 12; // 4 columns * 3 rows
    if (width >= 1024) return 9;  // 3 columns * 3 rows
    if (width >= 640) return 6;   // 2 columns * 3 rows
    return 3;                     // 1 column * 3 rows
}

/**
 * Render pagination controls dynamically
 */
function renderPagination(totalItems, itemsPerPage, totalPages, startIndex, endIndex) {
    const container = document.getElementById('pagination-container');
    if (!container) return;

    if (totalPages <= 1) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');

    // Update info text
    const startEl = document.getElementById('pagination-start');
    const endEl = document.getElementById('pagination-end');
    const totalEl = document.getElementById('pagination-total');
    if (startEl) startEl.textContent = startIndex + 1;
    if (endEl) endEl.textContent = endIndex;
    if (totalEl) totalEl.textContent = totalItems;

    // Render buttons
    const buttonsContainer = document.getElementById('pagination-buttons');
    if (buttonsContainer) {
        buttonsContainer.innerHTML = '';

        // Previous Button
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = `flex items-center justify-center p-2 rounded-lg border text-sm font-semibold transition-all duration-200 ${currentPage === 1 ? 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'}`;
        prevBtn.innerHTML = `
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        `;
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderMenuGrid();
                const grid = document.getElementById('menu-grid-container');
                if (grid) window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
            }
        });
        buttonsContainer.appendChild(prevBtn);

        // Page Number Buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.textContent = i;
            pageBtn.className = `min-w-[36px] h-9 px-3 rounded-lg border text-sm font-semibold transition-all duration-200 ${currentPage === i ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'}`;
            pageBtn.addEventListener('click', () => {
                if (currentPage !== i) {
                    currentPage = i;
                    renderMenuGrid();
                    const grid = document.getElementById('menu-grid-container');
                    if (grid) window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
                }
            });
            buttonsContainer.appendChild(pageBtn);
        }

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = `flex items-center justify-center p-2 rounded-lg border text-sm font-semibold transition-all duration-200 ${currentPage === totalPages ? 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'}`;
        nextBtn.innerHTML = `
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        `;
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderMenuGrid();
                const grid = document.getElementById('menu-grid-container');
                if (grid) window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
            }
        });
        buttonsContainer.appendChild(nextBtn);
    }
}

// Handle window resize to adjust pagination layout/items per page
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        renderMenuGrid();
    }, 150);
});

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

    const itemsPerPage = getPageSize();
    const totalItems = filteredItems.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // Keep currentPage within valid bounds
    if (currentPage > totalPages) {
        currentPage = Math.max(1, totalPages);
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);

    const itemsToRender = filteredItems.slice(startIndex, endIndex);

    if (itemsToRender.length === 0) {
        emptyState.classList.remove('hidden');
        container.classList.add('hidden');
        const pagContainer = document.getElementById('pagination-container');
        if (pagContainer) pagContainer.classList.add('hidden');
        return;
    }

    emptyState.classList.add('hidden');
    container.classList.remove('hidden');

    renderPagination(totalItems, itemsPerPage, totalPages, startIndex, endIndex);

    itemsToRender.forEach(item => {
        const sizes = Array.isArray(item.sizes) ? item.sizes.filter(s => s && s.size_name && s.price) : [];
        
        // Remove duplicates
        const uniqueSizes = [];
        const seen = new Set();
        sizes.forEach(size => {
            const key = `${size.size_name}-${size.price}`;
            if (!seen.has(key)) {
                seen.add(key);
                uniqueSizes.push(size);
            }
        });

        const imageSrc = item.image ? item.image : '';
        const imageMarkup = imageSrc
            ? `<img src="${imageSrc}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=\'flex h-full w-full items-center justify-center text-4xl bg-gray-50\'>${item.emoji || '🍽️'}</div>'">`
            : `<div class="flex h-full w-full items-center justify-center text-4xl select-none bg-gray-50">${item.emoji || '🍽️'}</div>`;

        const defaultSize = (item.activeSizeId && uniqueSizes.find(s => s.id == item.activeSizeId)) || uniqueSizes.find(s => s.is_default) || (uniqueSizes.length > 0 ? uniqueSizes[0] : null);
        const initialPrice = defaultSize ? defaultSize.price : item.price;
        const initialStock = defaultSize ? defaultSize.stock : item.stock;
        
        // Symmetrical grid structure: 2-columns (xs) and 3-columns (lg) for a beautiful alignment
        const sizeSection = uniqueSizes.length > 0 ? `
            <div class="mt-4">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Select Size</span>
                <div class="grid grid-cols-5 gap-1.5 size-btn-grid">
                    ${uniqueSizes.map(size => `
                        <button type="button"
                            class="size-option-btn truncate text-center rounded-md border py-1 px-0.5 text-[10px] font-semibold leading-none transition-all duration-200 ${size === defaultSize ? 'border-emerald-600 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-600/10' : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50'}"
                            data-size-id="${size.id}"
                            data-price="${size.price}"
                            data-stock="${size.stock || 0}"
                            data-name="${size.size_name}"
                            title="${size.size_name}">
                            ${size.size_name}
                        </button>
                    `).join('')}
                </div>
            </div>
        ` : '';

        const card = document.createElement('div');
        card.className = 'menu-card-anim group relative flex flex-col overflow-hidden rounded-2xl bg-white border border-gray-150/80 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-950/5';
        card.innerHTML = `
            <!-- Image Frame -->
            <div class="relative h-44 overflow-hidden bg-gray-50">
                <div class="food-image-shell absolute inset-0">
                    ${imageMarkup}
                </div>
                
                <div class="absolute inset-x-3 top-3 flex items-center justify-between pointer-events-none">
                    <span class="rounded-full bg-white/90 backdrop-blur-md px-2.5 py-1 text-[10px] font-bold tracking-wide text-gray-800 shadow-sm uppercase">
                        ${item.category}
                    </span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide shadow-sm uppercase ${initialStock <= 0 ? 'bg-rose-50 text-rose-700 border border-rose-100' : initialStock <= 3 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'}">
                        ${initialStock <= 0 ? 'Sold Out' : initialStock <= 3 ? 'Low' : 'Active'}
                    </span>
                </div>
            </div>

            <!-- Content details -->
            <div class="flex flex-1 flex-col p-4">
                <div class="mb-1">
                    <h3 class="text-base font-bold text-gray-950 group-hover:text-emerald-700 transition-colors duration-200 line-clamp-1">${item.name}</h3>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">${item.category}</p>
                </div>

                ${sizeSection}

                <!-- Price and stock indicator combined -->
                <div class="mt-5 flex items-baseline justify-between border-t border-gray-100 pt-3.5">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Price</span>
                        <span class="text-xl font-black text-gray-900 price-display">${formatDashboardPrice(initialPrice)}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Available</span>
                        <span class="text-xs font-bold ${initialStock <= 3 && initialStock > 0 ? 'text-amber-600' : 'text-emerald-600'} stock-display">${initialStock || 0} left</span>
                    </div>
                </div>

                <!-- Clear primary Action Call -->
                <button class="add-to-cart-btn mt-4 w-full rounded-xl py-3 text-xs font-bold tracking-wider uppercase transition-all duration-300 ${initialStock > 0 ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/10 active:scale-[0.98]' : 'cursor-not-allowed bg-gray-100 text-gray-400'}"
                    data-id="${item.id}"
                    data-name="${item.name}"
                    data-stock="${initialStock || 0}"
                    data-size-id="${defaultSize ? defaultSize.id : ''}"
                    ${initialStock > 0 ? '' : 'disabled'}>
                    ${initialStock > 0 ? 'Add to Cart' : 'Out of Stock'}
                </button>
            </div>
        `;
        container.appendChild(card);
    });

    // Option Buttons Click Handler
    document.querySelectorAll('.size-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.menu-card-anim');
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

            // Update UI State
            if (priceDisplay) priceDisplay.textContent = formatDashboardPrice(price);
            if (stockDisplay) {
                const parsedStock = parseInt(stock, 10);
                stockDisplay.textContent = `${parsedStock || 0} left`;
                stockDisplay.className = `text-xs font-bold ${parsedStock <= 3 && parsedStock > 0 ? 'text-amber-600' : 'text-emerald-600'} stock-display`;
            }
            if (addButton) {
                addButton.dataset.sizeId = sizeId;
                addButton.dataset.stock = stock;
                addButton.dataset.sizeName = sizeName;
                const parsedStock = parseInt(stock, 10);
                addButton.disabled = parsedStock <= 0;
                addButton.className = `add-to-cart-btn mt-4 w-full rounded-xl py-3 text-xs font-bold tracking-wider uppercase transition-all duration-300 ${parsedStock <= 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/10 active:scale-[0.98]'}`;
                addButton.textContent = parsedStock <= 0 ? 'Out of Stock' : 'Add to Cart';
            }

            // Clean, dynamic active state assignment matching our updated micro-grid design
            this.parentElement.querySelectorAll('.size-option-btn').forEach(candidate => {
                if (candidate === this) {
                    candidate.className = 'size-option-btn truncate text-center rounded-md border py-1 px-0.5 text-[10px] font-semibold leading-none transition-all duration-200 border-emerald-600 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-600/10';
                } else {
                    candidate.className = 'size-option-btn truncate text-center rounded-md border py-1 px-0.5 text-[10px] font-semibold leading-none transition-all duration-200 border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50';
                }
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
// function renderMenuGrid() {
//     const container = document.getElementById('menu-grid-container');
//     const emptyState = document.getElementById('empty-state');
//     container.innerHTML = '';

//     const filteredItems = menuDatabase.filter(item => {
//         const matchesCategory = (activeCategory === 'all' || item.category.toLowerCase() === activeCategory);
//         const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
//         return matchesCategory && matchesSearch;
//     });

//     if (filteredItems.length === 0) {
//         emptyState.classList.remove('hidden');
//         container.classList.add('hidden');
//         return;
//     }

//     emptyState.classList.add('hidden');
//     container.classList.remove('hidden');

//     filteredItems.forEach(item => {
//         const sizes = Array.isArray(item.sizes) ? item.sizes.filter(s => s && s.size_name && s.price) : [];
        
//         // Remove duplicates
//         const uniqueSizes = [];
//         const seen = new Set();
//         sizes.forEach(size => {
//             const key = `${size.size_name}-${size.price}`;
//             if (!seen.has(key)) {
//                 seen.add(key);
//                 uniqueSizes.push(size);
//             }
//         });

//         const imageSrc = item.image ? item.image : '';
//         const imageMarkup = imageSrc
//             ? `<img src="${imageSrc}" alt="${item.name}" class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=\'flex h-full w-full items-center justify-center text-4xl bg-gray-50\'>${item.emoji || '🍽️'}</div>'">`
//             : `<div class="flex h-full w-full items-center justify-center text-4xl select-none bg-gray-50">${item.emoji || '🍽️'}</div>`;

//         // Select default size option
//         const defaultSize = uniqueSizes.find(s => s.is_default) || (uniqueSizes.length > 0 ? uniqueSizes[0] : null);
//         const initialPrice = defaultSize ? defaultSize.price : item.price;
//         const initialStock = defaultSize ? defaultSize.stock : item.stock;

//         // "CHOOSE SIZE" Light Dropdown Selector
//         let sizeSelectorSection = '';
//         if (uniqueSizes.length > 0) {
//             const hasMoreThanThree = uniqueSizes.length > 3;
            
//             sizeSelectorSection = `
//                 <div class="mt-4">
//                     <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Choose Size</label>
//                     <div class="relative w-full">
//                         <select class="size-select-dropdown w-full appearance-none rounded-xl border border-gray-200 bg-white py-3 pl-4 pr-10 text-xs font-bold text-gray-800 outline-none transition-all duration-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10"
//                             data-food-id="${item.id}">
//                             ${uniqueSizes.map(size => `
//                                 <option value="${size.id}" 
//                                     data-price="${size.price}" 
//                                     data-stock="${size.stock || 0}"
//                                     ${size.id === (defaultSize ? defaultSize.id : '') ? 'selected' : ''}>
//                                     ${size.size_name} - $${Number(size.price).toFixed(2)}
//                                 </option>
//                             `).join('')}
//                         </select>
                        
//                         <!-- Show dropdown icon ONLY if it has more than 3 sizes -->
//                         ${hasMoreThanThree ? `
//                         <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400">
//                             <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
//                             </svg>
//                         </div>
//                         ` : ''}
//                     </div>
//                 </div>
//             `;
//         }

//         const card = document.createElement('div');
//         // Original premium light-themed card container styles
//         card.className = 'menu-card-anim group relative flex flex-col overflow-hidden rounded-2xl bg-white border border-gray-150/80 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-950/5';
//         card.innerHTML = `
//             <!-- Image Frame -->
//             <div class="relative h-44 overflow-hidden bg-gray-50">
//                 <div class="food-image-shell absolute inset-0">
//                     ${imageMarkup}
//                 </div>
                
//                 <div class="absolute inset-x-3 top-3 flex items-center justify-between pointer-events-none">
//                     <span class="rounded-full bg-white/90 backdrop-blur-md px-2.5 py-1 text-[10px] font-bold tracking-wide text-gray-800 shadow-sm uppercase">
//                         ${item.category}
//                     </span>
//                     <span class="rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide shadow-sm uppercase ${initialStock <= 0 ? 'bg-rose-50 text-rose-700 border border-rose-100' : initialStock <= 3 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'}">
//                         ${initialStock <= 0 ? 'Sold Out' : initialStock <= 3 ? 'Low' : 'Active'}
//                     </span>
//                 </div>
//             </div>

//             <!-- Content details container (Light Background) -->
//             <div class="flex flex-1 flex-col p-4 justify-between">
//                 <div>
//                     <h3 class="text-base font-bold text-gray-950 group-hover:text-emerald-700 transition-colors duration-200 line-clamp-1">${item.name}</h3>
//                     <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">${item.category}</p>
//                 </div>

//                 ${sizeSelectorSection}

//                 <!-- Price and stock display footer -->
//                 <div class="mt-5 flex items-baseline justify-between border-t border-gray-100 pt-3.5">
//                     <div>
//                         <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Price</span>
//                         <span class="text-xl font-black text-gray-900 price-display">$${Number(initialPrice).toFixed(2)}</span>
//                     </div>
//                     <div class="text-right">
//                         <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Available</span>
//                         <span class="text-xs font-bold ${initialStock <= 3 && initialStock > 0 ? 'text-amber-600' : 'text-emerald-600'} stock-display">${initialStock || 0} left</span>
//                     </div>
//                 </div>

//                 <!-- Primary Action Button with emerald shadow glow -->
//                 <button class="add-to-cart-btn mt-4 w-full rounded-xl py-3 text-xs font-bold tracking-wider uppercase transition-all duration-300 ${initialStock > 0 ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/10 active:scale-[0.98]' : 'cursor-not-allowed bg-gray-100 text-gray-400'}"
//                     data-id="${item.id}"
//                     data-name="${item.name}"
//                     data-stock="${initialStock || 0}"
//                     data-size-id="${defaultSize ? defaultSize.id : ''}"
//                     ${initialStock > 0 ? '' : 'disabled'}>
//                     ${initialStock > 0 ? 'Add to Cart' : 'Out of Stock'}
//                 </button>
//             </div>
//         `;
//         container.appendChild(card);
//     });

//     // Handle Dropdown changes and update the UI accordingly
//     document.querySelectorAll('.size-select-dropdown').forEach(dropdown => {
//         dropdown.addEventListener('change', function() {
//             const card = this.closest('.menu-card-anim');
//             const priceDisplay = card.querySelector('.price-display');
//             const stockDisplay = card.querySelector('.stock-display');
//             const addButton = card.querySelector('.add-to-cart-btn');

//             const selectedOption = this.options[this.selectedIndex];
//             const price = selectedOption.dataset.price;
//             const stock = parseInt(selectedOption.dataset.stock, 10);
//             const sizeId = this.value;

//             // 1. Update visual price and stock displays
//             if (priceDisplay) priceDisplay.textContent = `$${Number(price).toFixed(2)}`;
//             if (stockDisplay) {
//                 stockDisplay.textContent = `${stock || 0} left`;
//                 stockDisplay.className = `text-xs font-bold ${stock <= 3 && stock > 0 ? 'text-amber-600' : 'text-emerald-600'} stock-display`;
//             }

//             // 2. Update the main add button properties
//             if (addButton) {
//                 addButton.dataset.sizeId = sizeId;
//                 addButton.dataset.stock = stock;
//                 addButton.disabled = stock <= 0;
//                 addButton.textContent = stock <= 0 ? 'Out of Stock' : 'Add to Cart';
//                 addButton.className = `add-to-cart-btn mt-4 w-full rounded-xl py-3 text-xs font-bold tracking-wider uppercase transition-all duration-300 ${stock <= 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/10 active:scale-[0.98]'}`;
//             }
//         });
//     });

//     // Handle Add to Cart action click events
//     document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
//         btn.addEventListener('click', function() {
//             const foodId = this.dataset.id;
//             const foodName = this.dataset.name;
//             const stock = parseInt(this.dataset.stock || '0', 10);
//             const sizeId = this.dataset.sizeId;

//             if (stock <= 0) {
//                 showToast('Sorry, this option is currently sold out!', false);
//                 return;
//             }
//             addToCart(foodName, foodId, stock, sizeId);
//         });
//     });
// }
/**
 * Filter menu by category
 */
function filterCategory(category) {
    activeCategory = category;
    currentPage = 1;
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
    currentPage = 1;
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