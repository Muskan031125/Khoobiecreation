<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#FF6F61">
<title><?= esc($page['title'] ?? 'Krafty Khoobie') ?></title>
<meta name="description" content="<?= esc($page['description'] ?? '') ?>">

<!-- Open Graph / Twitter -->
<meta property="og:type" content="<?= esc($page['type'] ?? 'website') ?>">
<meta property="og:title" content="<?= esc($page['title'] ?? '') ?>">
<meta property="og:description" content="<?= esc($page['description'] ?? '') ?>">
<meta property="og:image" content="<?= esc($page['image'] ?? '') ?>">
<meta property="og:url" content="<?= esc($page['url'] ?? '') ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="canonical" href="<?= esc($page['url'] ?? '') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/favicon.png') ?>">

<!-- Brand fonts — Inter for body, Fraunces (variable) for display. preconnect for instant render -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&display=swap" rel="stylesheet">

<!-- GTM (head) -->
<?php if (!empty($tracking['gtm'])): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= esc($tracking['gtm']) ?>');</script>
<?php endif; ?>

<!-- Meta Pixel -->
<?php if (!empty($tracking['meta'])): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= esc($tracking['meta']) ?>');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= esc($tracking['meta']) ?>&ev=PageView&noscript=1"/></noscript>
<?php endif; ?>

<!-- Compiled CSS (Tailwind) -->
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">

<!-- Organization JSON-LD: helps Google, ChatGPT, Perplexity, Gemini understand the brand -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?= esc($brand['name'] ?? 'Krafty Khoobie') ?>",
  "url": "<?= base_url('/') ?>",
  "logo": "<?= base_url('assets/brand/logo.png') ?>",
  "description": "<?= esc($brand['tagline'] ?? 'Hands-on, heart-led, screen-free learning for children.') ?>",
  "email": "<?= esc($brand['email'] ?? '') ?>",
  "telephone": "<?= esc($brand['phone'] ?? '') ?>",
  "sameAs": ["https://instagram.com/khoobie", "https://facebook.com/khoobie"],
  "areaServed": "IN"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "url": "<?= base_url('/') ?>",
  "name": "<?= esc($brand['name'] ?? 'Krafty Khoobie') ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?= base_url('shop?q=') ?>{search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
</head>
<body class="bg-white text-slate-900 antialiased font-sans">

<!-- GTM (body noscript) -->
<?php if (!empty($tracking['gtm'])): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc($tracking['gtm']) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<?= $this->include('partials/_megamenu') ?>

<main class="min-h-[60vh]">
    <?= $this->renderSection('content') ?>
</main>

<?= $this->include('partials/footer') ?>
<?php
// Page-path detection (strip /kbhie/ alias prefix, then look at first segment)
$_path = trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
$_path = preg_replace('#^[^/]*/?#', '', $_path);

// Pages where the global floating cart bar should NOT show.
//  - product/*  → PDP has its own richer pdpState-aware bar
//  - cart, checkout, thank-you → you're already there
//  - admin, partner, account, login, signup, logout, lead → private/admin flows
$_skipFloatingCart = (
    str_starts_with($_path, 'admin')    ||
    str_starts_with($_path, 'partner')  ||
    str_starts_with($_path, 'account')  ||
    str_starts_with($_path, 'checkout') ||
    str_starts_with($_path, 'cart')     ||
    str_starts_with($_path, 'thank')    ||
    str_starts_with($_path, 'login')    ||
    str_starts_with($_path, 'signup')   ||
    str_starts_with($_path, 'logout')   ||
    str_starts_with($_path, 'lead')     ||
    str_starts_with($_path, 'shortlist')||
    str_starts_with($_path, 'compare')  ||
    str_starts_with($_path, 'recently-viewed') ||
    str_starts_with($_path, 'feed/')    ||
    str_starts_with($_path, 'sitemap')  ||
    str_starts_with($_path, 'llms')     ||
    str_starts_with($_path, 'robots')   ||
    str_starts_with($_path, 'product/') ||
    $_path === 'product'
);
if (! $_skipFloatingCart && empty($hideFloatingCart)):
    echo $this->include('partials/_floating_cart_bar');
endif;
?>
<?= $this->include('partials/popups') ?>
<?= $this->include('partials/_cart_toast') ?>
<?= $this->include('partials/_location_picker') ?>
<?php
// Social proof popup — only on public discovery pages, never during checkout / private flows
$_skipSocialProof = (
    str_starts_with($_path, 'admin')    ||
    str_starts_with($_path, 'partner')  ||
    str_starts_with($_path, 'account')  ||
    str_starts_with($_path, 'checkout') ||
    str_starts_with($_path, 'cart')     ||
    str_starts_with($_path, 'login')    ||
    str_starts_with($_path, 'signup')   ||
    str_starts_with($_path, 'logout')   ||
    str_starts_with($_path, 'lead')
);
if (! $_skipSocialProof): ?>
    <?= $this->include('partials/_social_proof') ?>
<?php endif; ?>

<?php
// AI Concierge widget — public pages only (skip admin/partner/checkout/auth)
$_skipConcierge = (
    str_starts_with($_path, 'admin')    ||
    str_starts_with($_path, 'partner')  ||
    str_starts_with($_path, 'checkout') ||
    str_starts_with($_path, 'login')    ||
    str_starts_with($_path, 'signup')   ||
    str_starts_with($_path, 'logout')
);
if (! $_skipConcierge): ?>
    <?= $this->include('partials/_concierge') ?>
<?php endif; ?>

<script>window.kbBaseUrl = '<?= base_url('/') ?>';</script>
<script src="<?= base_url('assets/app.js') ?>" defer></script>
</body>
</html>
