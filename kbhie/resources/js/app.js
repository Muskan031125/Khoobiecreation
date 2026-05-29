import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import focus from '@alpinejs/focus'
import intersect from '@alpinejs/intersect'
import 'htmx.org'

window.Alpine = Alpine
Alpine.plugin(collapse)
Alpine.plugin(focus)
Alpine.plugin(intersect)

// ----- PDP component — registered BEFORE Alpine.start() so it's always
// available when x-data="pdpState({...})" parses on the page.
Alpine.data('pdpState', (opts) => ({
    variants: opts.variants || [],
    currentId: opts.defaultVariantId || (opts.variants?.[0]?.id ?? null),
    qty: 1,
    addToCartUrl: opts.addToCartUrl,
    cartUrl: opts.cartUrl,
    checkoutUrl: opts.checkoutUrl,
    csrfName: opts.csrfName,
    csrfHash: opts.csrfHash,
    sku: opts.sku || '',
    get current() {
        return this.variants.find(v => v.id === this.currentId) || this.variants[0] || null
    },
    get currentPrice()   { return this.current ? Number(this.current.price)   : 0 },
    get currentCompare() { return this.current ? Number(this.current.compare) : 0 },
    get discountPct() {
        if (!this.currentCompare || this.currentCompare <= this.currentPrice) return 0
        return Math.round((1 - this.currentPrice / this.currentCompare) * 100)
    },
    formatRupees(paise) {
        const r = Math.round(paise / 100)
        return '₹' + r.toLocaleString('en-IN')
    },
    select(id) { this.currentId = id },
    async addToCart(buyNow) {
        if (!this.currentId) { alert('No product variant selected.'); return }
        const j = await window.kbCart.add(this.currentId, this.qty, {
            csrfName: this.csrfName, csrfHash: this.csrfHash,
            productName: this.current?.name || '', productImage: null,
        })
        if (j.ok) {
            if (window.kbTrack) window.kbTrack('AddToCart', { content_ids: [this.sku], value: this.currentPrice / 100, currency: 'INR' })
            if (buyNow) { location.href = this.cartUrl }   // Buy Now → cart page (per spec)
            // Add to cart → stay; cart:added toast is shown by kbCart.add
        }
    }
}))

// ----- Swipeable PDP gallery (images + playable video slide) -----
Alpine.data('kbGallery', (opts) => ({
    active: 0,
    total: opts.total || 1,
    init() {
        this.$nextTick(() => this.syncIndex())
    },
    goto(i) {
        if (i < 0 || i >= this.total) return
        this.active = i
        const track = this.$refs.track
        if (track) track.scrollTo({ left: i * track.clientWidth, behavior: 'smooth' })
    },
    prev() { this.goto(Math.max(0, this.active - 1)) },
    next() { this.goto(Math.min(this.total - 1, this.active + 1)) },
    syncIndex() {
        const track = this.$refs.track
        if (! track) return
        const idx = Math.round(track.scrollLeft / track.clientWidth)
        if (idx !== this.active) this.active = idx
    },
    /** Replace the poster overlay with an actual playable YouTube iframe. */
    playVideo(idx) {
        const track = this.$refs.track
        if (! track) return
        const slide = track.children[idx]
        if (! slide || ! slide.hasAttribute('data-slide-video')) return
        const src = slide.getAttribute('data-src')
        if (! src) return
        const url = src + (src.includes('?') ? '&' : '?') + 'autoplay=1&rel=0'
        slide.innerHTML = `<iframe src="${url}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`
    }
}))

// ----- Social proof: rotating "X from City just bought Y" notifications ------
Alpine.data('socialProof', (pool) => ({
    pool,
    visible: false,
    current: { name: '', city: '', verb: '', time: '', image: null, product: { slug: '', name: '' } },
    brandUrl: window.kbBaseUrl || '/',
    dismissedCount: 0,
    timer: null,
    autoHide: null,
    rand(arr) { return arr[Math.floor(Math.random() * arr.length)] },
    nextDelay() { return 25000 + Math.floor(Math.random() * 10000) },
    build() {
        const p = this.rand(this.pool.products) || { slug: '#', name: '—', image: null }
        return {
            name:  this.rand(this.pool.firstNames) + ' ' + this.rand(this.pool.initials),
            city:  this.rand(this.pool.cities),
            verb:  this.rand(this.pool.verbs),
            time:  this.rand(this.pool.timeLabels),
            image: p.image,
            product: { slug: p.slug, name: p.name },
        }
    },
    show() {
        if (document.hidden) return this.queueNext()
        if (this.dismissedCount >= 2) return
        if (!this.pool.products || this.pool.products.length === 0) return
        this.current = this.build()
        this.visible = true
        clearTimeout(this.autoHide)
        this.autoHide = setTimeout(() => { this.visible = false; this.queueNext() }, 6500)
    },
    queueNext() {
        clearTimeout(this.timer)
        this.timer = setTimeout(() => this.show(), this.nextDelay())
    },
    dismiss() {
        this.visible = false
        this.dismissedCount++
        clearTimeout(this.autoHide)
        this.queueNext()
    },
    start() {
        this.timer = setTimeout(() => this.show(), 8000)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearTimeout(this.timer); clearTimeout(this.autoHide); this.visible = false
            } else {
                this.queueNext()
            }
        })
    },
}))

// ----- Site-wide floating cart bar — listens for cart:added + cart:item-updated -----
Alpine.data('kbFloatingCart', (init) => ({
    count: init.count || 0,
    total: init.total || 0,
    freeShip: init.freeShip || 0,
    dismissed: false,
    formatRupees(paise) {
        const r = Math.round((paise || 0) / 100)
        return '₹' + r.toLocaleString('en-IN')
    },
    // Fired by kbCart.add() after a successful add — has item_count but no fresh total,
    // so we ping /cart/count to get the authoritative total in one go.
    onCartChange(detail) {
        this.dismissed = false
        if (detail && typeof detail.item_count !== 'undefined') {
            this.count = Number(detail.item_count)
        }
        this.refreshTotal()
    },
    // Fired by kbCart.setQty() — also no total in payload.
    onItemUpdated(_detail) {
        this.dismissed = false
        this.refreshTotal()
    },
    async refreshTotal() {
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'cart/count', { headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            this.count = Number(j.item_count || 0)
            this.total = Number(j.grand_total || 0)
        } catch (e) { /* network — keep existing values */ }
    },
}))

// ----- Khoobie AI Concierge — floating chat widget (LLM-powered) -----
Alpine.data('kbConcierge', () => ({
    open: false,
    q: '',
    busy: false,
    messages: [],
    examples: [
        'craft kit for 7-year-old who loves animals',
        'return gifts for a class of 25',
        'a calming activity for screen-tired evenings',
        'online chess for beginners',
    ],
    async ask() {
        const q = this.q.trim();
        if (! q || this.busy) return;
        this.messages.push({ role: 'user', content: q });
        this.q = ''; this.busy = true;
        this.$nextTick(() => { if (this.$refs.thread) this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
        try {
            const fd = new FormData(); fd.append('q', q);
            const r = await fetch((window.kbBaseUrl || '/') + 'concierge/ask', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            this.messages.push({
                role: 'assistant',
                reply: j.reply || 'Here are some picks.',
                picks: j.picks || [],
                follow_up: j.follow_up || null,
            });
        } catch (e) {
            this.messages.push({ role: 'assistant', reply: 'Sorry, I\'m having trouble right now. Try our shop directly!', picks: [], follow_up: null });
        }
        this.busy = false;
        this.$nextTick(() => { if (this.$refs.thread) this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
    },
}))

// ----- Universal Intent Capture (trial / RSVP / seat-block / discovery call) -----
// Powers the type-aware form on the PDP. 4 steps: form → otp → (pay) → done.
Alpine.data('kbIntent', (init) => ({
    productId:  init.productId,
    kind:       init.kind,
    amountDue:  init.amountDue || 0,
    fields:     init.fields || [],
    form:       { name: '', phone: '', email: '', child_age: null, preferred_slot: '', message: '' },
    step:       'form',
    busy:       false,
    error:      '',
    masked:     '',
    intentId:   null,
    otp:        '',
    successMessage: '',
    resendCooldown: 0,
    resendTimer:    null,

    async submit() {
        this.error = '';
        if (! this.form.name && this.fields.includes('name')) return this.error = 'Please enter your name.'
        if (! this.form.phone && ! this.form.email)          return this.error = 'Please enter phone or email.'
        if (this.form.phone && !/^[6-9]\d{9}$/.test(this.form.phone)) return this.error = 'Enter a valid 10-digit mobile.'

        this.busy = true
        const fd = new FormData()
        fd.append('product_id', this.productId)
        fd.append('kind',       this.kind)
        fd.append('amount_due', this.amountDue)
        Object.entries(this.form).forEach(([k, v]) => { if (v !== '' && v !== null) fd.append(k, v) })

        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'intent/capture', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) {
                this.intentId = j.intent_id
                this.masked   = j.masked
                this.step     = 'otp'
                this.startResendCooldown(30)
            } else {
                this.error = (j.errors && Object.values(j.errors)[0]) || j.error || 'Could not submit. Try again.'
            }
        } catch (e) { this.error = 'Network error. Try again.' }
        this.busy = false
    },

    async verify() {
        this.error = ''
        this.busy = true
        const fd = new FormData()
        fd.append('intent_id', this.intentId)
        fd.append('otp',       this.otp)
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'intent/verify', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) {
                if (j.requires_payment) { this.step = 'pay' }
                else                    { this.step = 'done'; this.successMessage = this.successText() }
            } else {
                this.error = j.error || 'Verification failed.'
            }
        } catch (e) { this.error = 'Network error. Try again.' }
        this.busy = false
    },

    async resend() {
        if (this.resendCooldown > 0) return
        const fd = new FormData(); fd.append('intent_id', this.intentId)
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'intent/resend', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) { this.error = ''; this.startResendCooldown(30) }
            else      { this.error = j.error || 'Could not resend.' }
        } catch (e) { this.error = 'Network error.' }
    },

    startResendCooldown(seconds) {
        clearInterval(this.resendTimer)
        this.resendCooldown = seconds
        this.resendTimer = setInterval(() => {
            this.resendCooldown--
            if (this.resendCooldown <= 0) clearInterval(this.resendTimer)
        }, 1000)
    },

    /**
     * Part-payment step — wires to Razorpay in production. For the demo we POST
     * a mock reference so the intent flips to "reserved" and the success screen renders.
     */
    async payNow() {
        this.busy = true
        const fd = new FormData()
        fd.append('intent_id', this.intentId)
        fd.append('amount',    this.amountDue)
        fd.append('ref',       'MOCK-' + Date.now())
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'intent/pay', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) { this.step = 'done'; this.successMessage = this.successText(true) }
            else      { this.error = j.error || 'Payment failed.' }
        } catch (e) { this.error = 'Network error.' }
        this.busy = false
    },

    successText(paid) {
        switch (this.kind) {
            case 'trial':              return 'You\'re booked! Check WhatsApp for your trial-class link.'
            case 'reserve_seat':       return paid ? 'Seat reserved! Confirmation sent on WhatsApp.' : 'You\'re on the guest list. We\'ll WhatsApp you the venue details.'
            case 'discovery_call':     return 'Our mentor will call you within 24h.'
            case 'notify_me':          return 'You\'re on the early-access list. We\'ll let you know.'
            case 'rsvp':               return 'RSVP confirmed. See you there!'
            case 'contact_instructor': return 'Your enquiry is with the instructor — expect a reply within 24h.'
            default:                   return 'We\'ve got your details. Talk soon!'
        }
    },
}))

// ----- Cart toast (lives in master layout, listens for cart:added) -----
Alpine.data('cartToast', () => ({
    visible: false,
    error: false,
    data: { product_name: '', item_count: 0, qty_added: 1, product_image: null },
    brandUrl: window.kbBaseUrl || '/',
    timer: null,
    show(detail) {
        this.error = false
        this.data = Object.assign({ product_name: '', item_count: 0, qty_added: 1, product_image: null }, detail || {})
        this.visible = true
        clearTimeout(this.timer)
        this.timer = setTimeout(() => this.visible = false, 5000)
    },
    showError(detail) {
        this.error = true
        this.data = { message: (detail && detail.message) || 'Could not add to cart.' }
        this.visible = true
        clearTimeout(this.timer)
        this.timer = setTimeout(() => this.visible = false, 5000)
    },
}))

// ----- Global cart helper used by product cards + PDP buttons -----
window.kbCart = {
    async add(variantId, qty = 1, opts = {}) {
        const fd = new FormData()
        fd.append('variant_id', String(variantId))
        fd.append('qty', String(qty))
        // CSRF is not enforced globally; safe to omit. If you enable CSRF, pass it via opts.csrf.
        if (opts.csrfName && opts.csrfHash) fd.append(opts.csrfName, opts.csrfHash)
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'cart/add', {
                method: 'POST', body: fd, headers: { 'Accept': 'application/json' },
            })
            const j = await r.json()
            if (j.ok) {
                if (window.kbTrack) window.kbTrack('AddToCart', { value: 0, currency: 'INR' })
                window.dispatchEvent(new CustomEvent('cart:added', { detail: {
                    product_name:  opts.productName || j.product_name || '',
                    product_image: opts.productImage || j.product_image || null,
                    item_count:    j.item_count,
                    qty_added:     j.qty_added || qty,
                } }))
                // Notify all cards (with this variant) so they switch to the in-cart state
                window.dispatchEvent(new CustomEvent('cart:item-updated', { detail: {
                    variant_id: Number(variantId),
                    qty:        Number(j.variant_qty ?? j.qty ?? qty),
                } }))
                // Bump cart badge
                document.querySelectorAll('[data-cart-count]').forEach(el => {
                    el.textContent = j.item_count
                    el.classList.remove('animate-cart-bump')
                    void el.offsetWidth        // restart animation
                    el.classList.add('animate-cart-bump')
                })
            } else if (j.enrol_only && j.redirect_to) {
                // Server says this type cannot enter the cart (course / tuition / meetup / service / membership / etc.)
                // Gracefully send the user to the express single-item enrol flow instead of toasting an error.
                window.dispatchEvent(new CustomEvent('cart:enrol-redirect', { detail: {
                    redirect_to:  j.redirect_to,
                    product_slug: j.product_slug || null,
                    message:      j.error || 'This one checks out on its own page.',
                } }))
                // Tiny visual hint then hard navigate — keeps the existing UX promise of "click, get to checkout".
                setTimeout(() => { location.href = (window.kbBaseUrl || '/').replace(/\/$/, '') + j.redirect_to }, 150)
            } else if (j.affiliate && j.redirect_to) {
                // Affiliate product — Khoobie doesn't sell or fulfil it, the partner
                // marketplace does. Open the outbound /go/{slug} in a new tab so the
                // shopper can compare without losing their Khoobie session.
                window.dispatchEvent(new CustomEvent('cart:affiliate-redirect', { detail: {
                    redirect_to:  j.redirect_to,
                    product_slug: j.product_slug || null,
                    message:      j.error || 'Opening the partner marketplace.',
                } }))
                const goUrl = (window.kbBaseUrl || '/').replace(/\/$/, '') + j.redirect_to
                window.open(goUrl, '_blank', 'noopener,noreferrer')
            } else {
                window.dispatchEvent(new CustomEvent('cart:error', { detail: { message: j.error || 'Could not add' } }))
            }
            return j
        } catch (e) {
            window.dispatchEvent(new CustomEvent('cart:error', { detail: { message: 'Network error' } }))
            return { ok: false, error: 'Network error' }
        }
    },
    async refreshCount() {
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'cart/count', { headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = j.item_count)
            return j.item_count
        } catch (e) { return null }
    },
    /** Helper used by product cards — reads variant + name + image from data-* attrs. */
    addFromButton(btn) {
        return this.add(
            parseInt(btn.dataset.variantId, 10),
            1,
            { productName: btn.dataset.productName || '', productImage: btn.dataset.productImage || '' }
        )
    },
    /** Absolute-qty update (powers product-card +/− stepper). qty=0 removes the line. */
    async setQty(variantId, qty) {
        const fd = new FormData()
        fd.append('variant_id', String(variantId))
        fd.append('qty', String(qty))
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'cart/set-qty', {
                method: 'POST', body: fd, headers: { 'Accept': 'application/json' },
            })
            const j = await r.json()
            if (j.ok) {
                // Tell every card with this variant to re-render its state
                window.dispatchEvent(new CustomEvent('cart:item-updated', { detail: {
                    variant_id: Number(variantId),
                    qty:        Number(j.qty ?? qty),
                } }))
                // Bump header cart badge
                document.querySelectorAll('[data-cart-count]').forEach(el => {
                    el.textContent = j.item_count
                    el.classList.remove('animate-cart-bump')
                    void el.offsetWidth
                    el.classList.add('animate-cart-bump')
                })
            } else {
                window.dispatchEvent(new CustomEvent('cart:error', { detail: { message: j.error || 'Could not update' } }))
            }
            return j
        } catch (e) {
            window.dispatchEvent(new CustomEvent('cart:error', { detail: { message: 'Network error' } }))
            return { ok: false, error: 'Network error' }
        }
    },
}

// ----- Shortlist (wishlist) + Compare — session-backed via /shortlist/* and /compare/* endpoints -----
window.kbShortlist = {
    async toggle(productId) {
        const fd = new FormData(); fd.append('product_id', String(productId))
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'shortlist/toggle', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) {
                window.dispatchEvent(new CustomEvent('shortlist:changed', { detail: { product_id: Number(productId), in_list: j.in_list, count: j.count } }))
                document.querySelectorAll('[data-shortlist-count]').forEach(el => { el.textContent = j.count; el.classList.toggle('hidden', j.count === 0) })
            }
            return j
        } catch (e) { return { ok: false, error: 'Network error' } }
    },
}

window.kbCompare = {
    async toggle(productId) {
        const fd = new FormData(); fd.append('product_id', String(productId))
        try {
            const r = await fetch((window.kbBaseUrl || '/') + 'compare/toggle', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
            const j = await r.json()
            if (j.ok) {
                window.dispatchEvent(new CustomEvent('compare:changed', { detail: { product_id: Number(productId), in_list: j.in_list, count: j.count, max: j.max } }))
                document.querySelectorAll('[data-compare-count]').forEach(el => { el.textContent = j.count; el.classList.toggle('hidden', j.count === 0) })
            } else if (j.error) {
                window.dispatchEvent(new CustomEvent('cart:error', { detail: { message: j.error } }))
            }
            return j
        } catch (e) { return { ok: false, error: 'Network error' } }
    },
}

Alpine.start()

// Anonymous visitor ID — first-party cookie for cross-page stitching
function ensureAnonId() {
    const key = 'kb_anon'
    let id = document.cookie.split('; ').find(r => r.startsWith(key + '='))?.split('=')[1]
    if (!id) {
        id = 'a_' + Math.random().toString(36).slice(2) + Date.now().toString(36)
        document.cookie = `${key}=${id};path=/;max-age=31536000;samesite=lax`
    }
    window.__anonId = id
    return id
}
ensureAnonId()

// Server-side event mirror — fires alongside any client pixel
window.kbTrack = function (eventName, payload = {}) {
    const eventId = 'e_' + Math.random().toString(36).slice(2) + Date.now().toString(36)
    if (window.fbq) window.fbq('track', eventName, payload, { eventID: eventId })
    if (window.gtag) window.gtag('event', eventName, payload)
    try {
        navigator.sendBeacon('/track/event', new Blob([JSON.stringify({
            event_name: eventName,
            event_id: eventId,
            anon_id: window.__anonId,
            payload,
            url: location.href,
            referrer: document.referrer,
        })], { type: 'application/json' }))
    } catch (_) {}
}
