<!-- Cart "added" toast — global, slides in top-right when window dispatches `cart:added` -->
<div x-data="cartToast()" x-cloak
     @cart:added.window="show($event.detail)"
     @cart:error.window="showError($event.detail)"
     x-show="visible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-20 right-4 z-[100] max-w-sm">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
        <div class="flex items-stretch gap-3 p-3">
            <template x-if="data.product_image">
                <img :src="data.product_image" class="w-16 h-16 rounded-lg object-cover shrink-0" alt="">
            </template>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">✓</span>
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700" x-text="error ? 'Error' : 'Added to cart'"></span>
                </div>
                <div class="mt-1 text-sm font-semibold text-slate-900 line-clamp-2" x-text="data.product_name || data.message"></div>
                <div class="mt-0.5 text-[11px] text-slate-500" x-show="!error">
                    <span x-text="data.qty_added + ' × in cart'"></span> · <span x-text="data.item_count + ' items total'"></span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 border-t border-slate-100">
            <a :href="brandUrl + 'cart'" class="px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50 border-r border-slate-100">View Cart</a>
            <a :href="brandUrl + 'checkout'" class="px-4 py-2.5 text-center text-sm font-bold text-white bg-brand-500 hover:bg-brand-600">Checkout &rarr;</a>
        </div>
    </div>
</div>
