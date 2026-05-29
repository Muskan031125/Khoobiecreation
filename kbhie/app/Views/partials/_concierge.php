<?php
/**
 * Khoobie AI Concierge — floating bottom-right chat widget.
 * Hidden on private pages (admin, partner, checkout) via master.php skip logic.
 */
?>
<div x-data="kbConcierge()" x-cloak class="fixed z-40 pointer-events-none"
     style="bottom: calc(env(safe-area-inset-bottom, 0px) + 6.5rem); right: 1rem;">

    <!-- ===== Launcher button ===== -->
    <button @click="open = ! open" x-show="! open"
            class="pointer-events-auto group relative w-14 h-14 rounded-full bg-gradient-to-br from-brand-500 to-bloom-500 text-white shadow-cta-lg hover:scale-110 transition flex items-center justify-center">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.74 1.42 5.16 3.62 6.78L4 22l4.95-2.04c.98.21 2 .31 3.05.31 5.52 0 10-4.03 10-9s-4.48-9-10-9zm-1 13H7v-2h4v2zm6-4H7V9h10v2z"/></svg>
        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-amber-900 text-[10px] font-black animate-pulse">AI</span>
    </button>

    <!-- ===== Panel ===== -->
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="pointer-events-auto w-[92vw] max-w-sm bg-white rounded-2xl shadow-soft-lg ring-1 ring-slate-200 overflow-hidden flex flex-col"
         style="max-height: 80vh;">

        <!-- Header -->
        <div class="bg-gradient-to-r from-brand-500 to-bloom-500 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-white/20 inline-flex items-center justify-center">🤖</span>
                <div>
                    <div class="font-display font-black leading-tight">Khoobie Concierge</div>
                    <div class="text-[10px] opacity-90">AI-powered · finds the right thing for your kid</div>
                </div>
            </div>
            <button @click="open = false" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <!-- Message area -->
        <div class="flex-1 overflow-y-auto p-3 space-y-3 bg-slate-50" x-ref="thread" style="min-height: 240px;">
            <!-- Greeting -->
            <div class="bg-white rounded-2xl rounded-tl-sm p-3 text-sm shadow-soft max-w-[85%]">
                Hi! I'm your Khoobie AI helper.<br>
                Tell me about your child — age, interest, occasion — and I'll find the perfect thing.
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <template x-for="ex in examples">
                        <button @click="q = ex; ask()" class="text-[11px] px-2 py-1 rounded-full bg-brand-100 hover:bg-brand-200 text-brand-700 font-bold transition" x-text="ex"></button>
                    </template>
                </div>
            </div>

            <!-- Conversation -->
            <template x-for="(msg, i) in messages" :key="i">
                <div>
                    <!-- User -->
                    <div x-show="msg.role === 'user'" class="flex justify-end">
                        <div class="bg-brand-500 text-white rounded-2xl rounded-tr-sm px-3 py-2 text-sm max-w-[85%]" x-text="msg.content"></div>
                    </div>
                    <!-- Assistant -->
                    <div x-show="msg.role === 'assistant'" class="space-y-2">
                        <div class="bg-white rounded-2xl rounded-tl-sm p-3 text-sm shadow-soft max-w-[85%]" x-text="msg.reply"></div>
                        <template x-for="p in msg.picks">
                            <a :href="p.url" class="flex gap-3 bg-white rounded-xl shadow-soft hover:shadow-soft-lg ring-1 ring-slate-100 hover:ring-brand-200 overflow-hidden transition">
                                <img :src="p.image" class="w-16 h-16 object-cover shrink-0">
                                <div class="flex-1 min-w-0 py-2 pr-3">
                                    <div class="font-bold text-xs text-slate-900 line-clamp-1" x-text="p.name"></div>
                                    <div class="text-[11px] text-slate-600 line-clamp-2 mt-0.5" x-text="p.why"></div>
                                    <div class="mt-1 text-xs font-black text-brand-600" x-text="p.price"></div>
                                </div>
                            </a>
                        </template>
                        <div x-show="msg.follow_up" class="bg-amber-50 rounded-xl p-2.5 text-xs text-amber-800 max-w-[85%]" x-text="msg.follow_up"></div>
                    </div>
                </div>
            </template>

            <!-- Typing indicator -->
            <div x-show="busy" x-cloak class="bg-white rounded-2xl rounded-tl-sm px-3 py-2 text-sm shadow-soft inline-flex gap-1 items-center w-fit">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 100ms"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 200ms"></span>
            </div>
        </div>

        <!-- Composer -->
        <form @submit.prevent="ask()" class="border-t border-slate-200 p-2 bg-white flex gap-2">
            <input type="text" x-model="q" :disabled="busy" placeholder="e.g. craft kit for my 8-year-old who loves animals"
                   class="flex-1 px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
            <button type="submit" :disabled="busy || ! q.trim()"
                    class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm shadow-sm disabled:opacity-50 transition">
                Ask
            </button>
        </form>
        <p class="text-[9px] text-slate-400 text-center py-1.5 bg-slate-50 border-t border-slate-100">Powered by Khoobie AI · suggestions, not advice · please verify</p>
    </div>
</div>
