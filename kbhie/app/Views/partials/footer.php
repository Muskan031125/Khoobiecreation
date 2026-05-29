<footer class="bg-slate-900 text-slate-300 pt-12 pb-24 lg:pb-12 mt-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-8">

        <div class="col-span-2">
            <img src="<?= base_url('assets/brand/logo.png') ?>" alt="<?= esc($brand['name']) ?>" class="h-12 w-auto bg-white p-2 rounded-lg inline-block">
            <p class="mt-3 text-sm text-slate-400 max-w-sm">Bringing unique handmade crafts and creative treasures to your doorstep.</p>

            <form method="post" action="<?= base_url('lead/newsletter') ?>" class="mt-5 flex max-w-sm">
                <?= csrf_field() ?>
                <input type="email" name="email" required placeholder="Your email" class="flex-1 px-3 py-2.5 rounded-l-lg text-slate-900 text-sm border-0">
                <button class="px-4 py-2.5 bg-brand-500 hover:bg-brand-600 rounded-r-lg text-white text-sm font-bold">Join</button>
            </form>

            <div class="mt-5 flex gap-3">
                <a href="https://instagram.com/" target="_blank" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-brand-500 flex items-center justify-center transition" aria-label="Instagram">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.81-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.75 3.75 0 0 1-1.38-.9 3.74 3.74 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.81.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63a5.92 5.92 0 0 0-2.13 1.39A5.92 5.92 0 0 0 .62 4.14C.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.73 1.46 1.39 2.13a5.93 5.93 0 0 0 2.13 1.39c.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.93 5.93 0 0 0 2.13-1.39 5.93 5.93 0 0 0 1.39-2.13c.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.93 5.93 0 0 0-1.39-2.13A5.92 5.92 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32ZM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm6.41-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88Z"/></svg>
                </a>
                <a href="https://facebook.com/" target="_blank" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-brand-500 flex items-center justify-center transition" aria-label="Facebook">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.68 0H1.32C.59 0 0 .59 0 1.32v21.36C0 23.41.59 24 1.32 24h11.49v-9.29H9.69V11.1h3.13V8.41c0-3.1 1.9-4.79 4.66-4.79 1.32 0 2.46.1 2.8.14v3.24h-1.92c-1.5 0-1.8.71-1.8 1.76v2.31h3.59l-.47 3.62h-3.12V24h6.12c.73 0 1.32-.59 1.32-1.32V1.32C24 .59 23.41 0 22.68 0Z"/></svg>
                </a>
                <a href="https://twitter.com/" target="_blank" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-brand-500 flex items-center justify-center transition" aria-label="Twitter / X">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zM17.083 19.77h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://wa.me/<?= esc($brand['whatsapp']) ?>" target="_blank" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-emerald-500 flex items-center justify-center transition" aria-label="WhatsApp">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12.057 0C5.494 0 .163 5.331.16 11.892c0 2.096.546 4.142 1.588 5.945L.057 24l6.305-1.654a11.876 11.876 0 0 0 5.692 1.45h.005c6.554 0 11.886-5.332 11.89-11.893a11.821 11.821 0 0 0-3.48-8.413A11.821 11.821 0 0 0 12.057 0zm0 21.785h-.004a9.864 9.864 0 0 1-5.031-1.378l-.36-.214-3.741.982 1.001-3.648-.235-.374a9.86 9.86 0 0 1-1.511-5.26c.003-5.45 4.437-9.884 9.889-9.884a9.825 9.825 0 0 1 6.987 2.898 9.825 9.825 0 0 1 2.892 6.99c-.003 5.45-4.437 9.884-9.887 9.884z"/></svg>
                </a>
            </div>
        </div>

        <div>
            <h4 class="font-semibold text-white mb-3">Shop</h4>
            <ul class="space-y-1.5 text-sm">
                <li><a href="<?= base_url('shop/arts') ?>" class="hover:text-white">Learning Kits</a></li>
                <li><a href="<?= base_url('shop/nature') ?>" class="hover:text-white">Nature Kits</a></li>
                <li><a href="<?= base_url('shop/accessories') ?>" class="hover:text-white">Accessories</a></li>
                <li><a href="<?= base_url('shop/return-gifts') ?>" class="hover:text-white">Return Gifts</a></li>
                <li><a href="<?= base_url('shop') ?>" class="hover:text-white">All Products</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-semibold text-white mb-3">Policies</h4>
            <ul class="space-y-1.5 text-sm">
                <li><a href="<?= base_url('pages/about-us') ?>" class="hover:text-white">About Us</a></li>
                <li><a href="<?= base_url('pages/privacy-policy') ?>" class="hover:text-white">Privacy Policy</a></li>
                <li><a href="<?= base_url('pages/terms-of-service') ?>" class="hover:text-white">Terms of Service</a></li>
                <li><a href="<?= base_url('pages/refund-policy') ?>" class="hover:text-white">Refund Policy</a></li>
                <li><a href="<?= base_url('pages/shipping-policy') ?>" class="hover:text-white">Shipping Policy</a></li>
                <li><a href="<?= base_url('pages/contact-us') ?>" class="hover:text-white">Contact Us</a></li>
            </ul>
        </div>

        <div class="col-span-2 md:col-span-1">
            <h4 class="font-semibold text-white mb-3">Get in Touch</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="tel:<?= esc($brand['phone']) ?>" class="hover:text-white flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <?= esc($brand['phone']) ?>
                </a></li>
                <li><a href="mailto:<?= esc($brand['email']) ?>" class="hover:text-white flex items-center gap-2 break-all">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                    <?= esc($brand['email']) ?>
                </a></li>
                <li class="flex items-start gap-2 text-slate-400">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= esc(env('khoobie.company_address', '')) ?>
                </li>
            </ul>
        </div>
    </div>

    <div class="mt-10 border-t border-slate-800 pt-6 text-center text-xs text-slate-500 px-4">
        Copyright &copy; <?= date('Y') ?> <?= esc($brand['name']) ?>. All rights reserved.
    </div>
</footer>
