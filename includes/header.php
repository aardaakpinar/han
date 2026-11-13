<?php
/** @var PDO $pdo */
$__user = current_user();
$__siteSettings = site_settings();
$__logo = (string)($__siteSettings['logo'] ?? '');
$__favicon = (string)($__siteSettings['favicon'] ?? '');
$__hideName = !empty($__siteSettings['hide_name_with_logo']) && $__logo !== '';
$__accent = (string)effective_setting('accent');
$__accentCustom = $__accent === 'custom' ? effective_accent_custom() : '';
$__canRegister = $__siteSettings['registration_open'] || User::countAll() === 0;
$__isAdmin = $__user && ($__user['role'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    verify_csrf();
    remember_me_forget();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    redirect(url());
}
?>
<!DOCTYPE html>
<html lang="en"<?= appearance_attrs() ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0b0b0c" media="(prefers-color-scheme: dark)">
<title><?= e($pageTitle ?? $__siteSettings['name']) ?></title>
<link rel="preload" href="/assets/fonts/inter-Regular.woff" as="font" type="font/woff" crossorigin>
<link rel="stylesheet" href="/assets/css/main.css">
<?php if ($__accentCustom !== ''): ?>
<link rel="stylesheet" href="<?= e(url('theme.css')) ?>?a=<?= e(substr($__accentCustom, 1)) ?>">
<?php endif; ?>
<?php if ($__favicon !== ''): ?>
<link rel="icon" href="<?= e(url($__favicon)) ?>">
<?php else: ?>
<link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/favicon.svg')) ?>">
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= e(url()) ?>" class="brand" aria-label="<?= e($__siteSettings['name']) ?> home">
            <?php if ($__logo !== ''): ?>
                <img src="<?= e(url($__logo)) ?>" alt="<?= e($__siteSettings['name']) ?>" class="brand-logo">
            <?php else: ?>
                <svg class="brand-mark" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">
                    <path d="M10 25.5V6.5M10 15.5h6.5a5.5 5.5 0 0 1 5.5 5.5v4.5"
                          stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php endif; ?>
            <?php if (!$__hideName): ?>
                <span class="wordmark"><?= e($__siteSettings['name']) ?></span>
            <?php endif; ?>
        </a>

        <form class="header-search" method="get" action="<?= e(url('search')) ?>" role="search">
            <i data-lucide="search" class="header-search-icon"></i>
            <input type="search" name="q" placeholder="Search threads" aria-label="Search threads" maxlength="100" autocomplete="off">
            <kbd class="kbd-hint" aria-hidden="true">/</kbd>
        </form>

        <div class="header-actions">
            <a href="<?= e(url('search')) ?>" class="icon-btn mobile-search-link" aria-label="Search" title="Search">
                <i data-lucide="search"></i>
            </a>
            <button type="button" class="icon-btn mobile-sidebar-toggle" aria-label="Open sidebar" aria-expanded="false" aria-controls="home-sidebar" data-sidebar-toggle>
                <i data-lucide="panel-left"></i>
            </button>
            <?php if ($__user): ?>
                <?php $__unread = Notification::unreadCount($__user); ?>
                <div class="user-menu">
                    <button class="user-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                        <?= avatar($__user['username'], 'sm') ?>
                        <span class="user-name"><?= e($__user['username']) ?></span>
                        <i data-lucide="chevron-down" class="user-caret"></i>
                    </button>
                    <div class="user-popup" role="menu">
                        <div class="popup-head">
                            <strong><?= e($__user['username']) ?></strong>
                            <span><?= e($__user['email']) ?></span>
                        </div>
                        <a href="<?= e(url('profile/' . rawurlencode($__user['username']))) ?>" role="menuitem"><i data-lucide="user"></i>Profile</a>
                        <a href="<?= e(url('notifications')) ?>" role="menuitem"><i data-lucide="bell"></i>Notifications<?php if ($__unread > 0): ?><span class="count-pill"><?= (int)$__unread ?></span><?php endif; ?></a>
                        <a href="<?= e(url('settings')) ?>" role="menuitem"><i data-lucide="settings"></i>Settings</a>
                        <?php if (($__user['role'] ?? '') === 'admin'): ?>
                            <a href="<?= e(url('admin')) ?>" role="menuitem"><i data-lucide="shield"></i>Admin panel</a>
                        <?php endif; ?>
                        <div class="popup-sep"></div>
                        <form method="post" class="form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="logout" role="menuitem"><i data-lucide="log-out"></i>Log out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>" class="btn btn-ghost btn-sm">Log in</a>
                <?php if ($__canRegister): ?><a href="<?= e(url('register')) ?>" class="btn btn-primary btn-sm">Sign up</a><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>
<main id="main">
<?php if ($__isAdmin && $__siteSettings['maintenance_mode']): ?>
<div class="announcement announcement-warning" role="status">
    <i data-lucide="wrench"></i>
    <span>Maintenance mode is on. Only admins can use the forum right now.</span>
    <a href="<?= e(url('admin?tab=settings&section=notices')) ?>">Turn it off</a>
</div>
<?php endif; ?>
<?php if ($__siteSettings['announcement_enabled'] && $__siteSettings['announcement_text'] !== ''): ?>
<div class="announcement announcement-<?= e($__siteSettings['announcement_type']) ?>" role="status" data-announcement="<?= e(substr(md5($__siteSettings['announcement_text']), 0, 10)) ?>">
    <i data-lucide="<?= $__siteSettings['announcement_type'] === 'warning' ? 'triangle-alert' : ($__siteSettings['announcement_type'] === 'success' ? 'circle-check' : 'megaphone') ?>"></i>
    <span><?= e($__siteSettings['announcement_text']) ?></span>
    <button type="button" class="announcement-close" aria-label="Dismiss announcement"><i data-lucide="x"></i></button>
</div>
<?php endif; ?>
<div class="page-wrap">
