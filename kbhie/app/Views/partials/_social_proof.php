<?php
/**
 * Social-proof purchase notifications.
 *  - Pool: ~80 Indian first names × ~55 cities × random catalog products = effectively infinite combos.
 *  - First fires 8s after page load, then random 25-35s interval.
 *  - Bottom-left slide-in, auto-dismisses after 6s.
 *  - Dismissible. Won't fire again for the rest of the session if user closes 2+ of them.
 *  - Skips on private/checkout/admin pages — see master.php include guard.
 *  - Pauses when tab is hidden (Page Visibility API).
 */

$db = \Config\Database::connect();

// Random pool of active products (only product types people would actually buy)
$products = $db->table('products p')
    ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
    ->select('p.slug, p.name, p.hero_image, v.price')
    ->where('p.status', 'active')
    ->whereIn('p.type', ['simple','variable','bundle','digital','course','membership'])
    ->orderBy('RAND()', '', false)
    ->limit(40)
    ->get()->getResultArray();

$firstNames = [
    'Aarav','Vihaan','Aditya','Arjun','Sai','Reyansh','Ayaan','Krishna','Ishaan','Shaurya',
    'Atharv','Advik','Pranav','Kabir','Rudra','Aniket','Rohan','Karan','Yash','Tanmay',
    'Aryan','Nikhil','Devansh','Manav','Parth','Raghav','Tushar','Veer','Yuvraj','Gautam',
    'Aanya','Aadhya','Saanvi','Pari','Diya','Sara','Aarna','Anika','Anaya','Ahana',
    'Riya','Mira','Ira','Tara','Nisha','Priya','Pooja','Tanvi','Kavya','Aditi',
    'Bhavya','Disha','Esha','Ishita','Jaya','Kiara','Meera','Neha','Sneha','Yamini',
    'Vikram','Suresh','Ravi','Anil','Manish','Rahul','Sunita','Anjali','Deepika','Shalini',
    'Megha','Rashmi','Sangeeta','Vandana','Lakshmi','Padma','Geeta','Madhuri','Swati','Kalpana',
];

$cities = [
    'Mumbai','Delhi','Bangalore','Hyderabad','Chennai','Kolkata','Pune','Ahmedabad','Surat','Jaipur',
    'Lucknow','Kanpur','Nagpur','Indore','Thane','Bhopal','Visakhapatnam','Patna','Vadodara','Ghaziabad',
    'Ludhiana','Agra','Nashik','Faridabad','Meerut','Rajkot','Kalyan','Varanasi','Srinagar','Aurangabad',
    'Dhanbad','Amritsar','Allahabad','Ranchi','Howrah','Jodhpur','Raipur','Chandigarh','Gurugram','Noida',
    'Coimbatore','Madurai','Mysore','Vijayawada','Trivandrum','Kochi','Panaji','Dehradun','Shimla','Guwahati',
    'Bhubaneswar','Mangalore','Jamshedpur','Pondicherry','Udaipur',
];

$initials = ['M.','S.','K.','P.','R.','D.','G.','V.','J.','L.','N.','B.','T.','C.','H.'];

$timeLabels = [
    'just now', '1 min ago', '2 mins ago', '4 mins ago', '6 mins ago', '9 mins ago',
    '12 mins ago', '17 mins ago', '24 mins ago', 'half an hour ago', 'an hour ago',
    'a few hours ago', 'this morning',
];

$verbs = ['just bought', 'just ordered', 'added to cart', 'just got'];

$data = [
    'products'   => array_map(static fn ($p) => [
        'slug'  => $p['slug'],
        'name'  => $p['name'],
        'image' => $p['hero_image'],
        'price' => (int) ($p['price'] ?? 0),
    ], $products),
    'firstNames' => $firstNames,
    'initials'   => $initials,
    'cities'     => $cities,
    'timeLabels' => $timeLabels,
    'verbs'      => $verbs,
];
?>
<div x-data='socialProof(<?= json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
     x-init="start()"
     x-show="visible"
     x-cloak
     x-transition:enter="transition ease-out duration-400"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="hidden sm:block fixed left-4 z-50 max-w-xs"
     style="bottom: calc(env(safe-area-inset-bottom, 0px) + 6.5rem);">
    <div class="bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden">
        <div class="flex items-start gap-3 p-3 pr-7 relative">
            <template x-if="current.image">
                <img :src="current.image" class="w-12 h-12 rounded-lg object-cover shrink-0" alt="">
            </template>
            <template x-if="!current.image">
                <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-xl shrink-0">🎁</div>
            </template>
            <div class="flex-1 min-w-0">
                <div class="text-xs text-slate-600">
                    <span class="font-semibold text-slate-900" x-text="current.name"></span>
                    from <span class="font-semibold text-slate-900" x-text="current.city"></span>
                </div>
                <div class="text-xs text-slate-700 mt-0.5 line-clamp-2">
                    <span x-text="current.verb"></span>
                    <a :href="brandUrl + 'product/' + current.product.slug" class="font-bold text-brand-600 hover:underline" x-text="current.product.name"></a>
                </div>
                <div class="mt-1 flex items-center gap-2 text-[10px] text-slate-400">
                    <span class="inline-flex items-center gap-1 text-emerald-600 font-bold uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Verified
                    </span>
                    <span x-text="current.time"></span>
                </div>
            </div>
            <button @click="dismiss()" type="button" class="absolute top-2 right-2 w-5 h-5 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 text-sm leading-none" aria-label="Dismiss">×</button>
        </div>
    </div>
</div>
