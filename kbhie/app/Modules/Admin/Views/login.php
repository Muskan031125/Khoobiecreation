<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($page['title'] ?? 'Admin Login') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body class="bg-slate-100 antialiased font-sans min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <div class="bg-white rounded-2xl shadow-xl p-7">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand-500 text-white font-black text-xl">K</div>
            <h1 class="mt-3 text-xl font-black">Khoobie Admin</h1>
            <p class="text-xs text-slate-500">Sign in to continue</p>
        </div>

        <?php if (session('error')): ?>
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/login') ?>" class="mt-6 space-y-3">
            <?= csrf_field() ?>
            <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
            </div>
            <input name="identifier" required placeholder="Email"      value="<?= esc(old('identifier')) ?>" class="w-full px-4 py-3 rounded-lg border border-slate-200">
            <input name="password"   required type="password" placeholder="Password" class="w-full px-4 py-3 rounded-lg border border-slate-200">
            <button class="w-full px-4 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold">Log in</button>
        </form>
    </div>
    <p class="mt-4 text-center text-xs text-slate-500"><a href="<?= base_url('/') ?>" class="hover:underline">&larr; Back to storefront</a></p>
</div>

</body>
</html>
