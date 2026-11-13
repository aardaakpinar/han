<?php
$pageTitle = page_title($category ? $category['name'] : 'Threads');
$__nav = 'home';
require __DIR__ . '/../../includes/header.php';

$baseUrl = $category ? url('category/' . $category['slug']) : url();
$qs = static function (array $over = []) use ($sort, $filter, $defaultSort): array {
    $p = array_merge(['sort' => $sort, 'filter' => $filter ?: null], $over);
    if (($p['sort'] ?? null) === $defaultSort) {
        $p['sort'] = null; // varsayılan sıralama URL'de gösterilmez
    }
    return array_filter($p, static fn($v) => $v !== null && $v !== '');
};
$link = static fn(array $over = []) => $baseUrl . ($qs($over) ? '?' . http_build_query($qs($over)) : '');
?>

<?php if (!$user && !$category && $page === 1 && site_setting('show_welcome_intro')): ?>
<section class="intro">
    <div>
        <h1>Welcome to <?= e(site_settings()['name']) ?></h1>
        <p><?= e(site_settings()['tagline']) ?></p>
    </div>
    <div class="intro-actions">
        <?php if ($__canRegister): ?><a href="<?= e(url('register')) ?>" class="btn btn-primary">Create account</a><?php endif; ?>
        <a href="<?= e(url('login')) ?>" class="btn btn-secondary">Log in</a>
    </div>
</section>
<?php endif; ?>

<?php
$__widgets = home_widgets_html(['categories' => $categories, 'category' => $category, 'stats' => $stats]);
$__catZone = home_widget_zone('categories');
$__hasCategoryNav = $categories && $__catZone !== 'off';
?>
<div class="layout<?= $__widgets['sidebar'] === '' ? ' no-sidebar' : '' ?>">
    <?php if ($__widgets['sidebar'] !== ''): ?>
    <aside class="sidebar" id="home-sidebar" aria-label="Sidebar">
        <?= $__widgets['sidebar'] ?>
    </aside>
    <?php endif; ?>
    <div>
        <?php if ($__widgets['top'] !== ''): ?>
        <div class="home-widgets home-widgets-top only-desktop"><?= $__widgets['top'] ?></div>
        <?php endif; ?>
        <?php if ($__widgets['m_top'] !== ''): ?>
        <div class="home-widgets home-widgets-top only-mobile"><?= $__widgets['m_top'] ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div class="page-header-title">
                <h1><?= e($category ? $category['name'] : 'Threads') ?></h1>
                <?php if ($category && $category['description'] !== ''): ?>
                    <p class="lead"><?= e($category['description']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="page-header-actions">
                <div class="select-wrap sort-select">
                    <select aria-label="Sort threads" data-nav-select>
                        <option value="<?= e($link(['sort' => 'latest', 'page' => null])) ?>"<?= $sort === 'latest' ? ' selected' : '' ?>>Latest</option>
                        <option value="<?= e($link(['sort' => 'new', 'page' => null])) ?>"<?= $sort === 'new' ? ' selected' : '' ?>>New</option>
                        <option value="<?= e($link(['sort' => 'top', 'page' => null])) ?>"<?= $sort === 'top' ? ' selected' : '' ?>>Top</option>
                        <option value="<?= e($link(['sort' => 'views', 'page' => null])) ?>"<?= $sort === 'views' ? ' selected' : '' ?>>Most viewed</option>
                    </select>
                    <i data-lucide="chevron-down"></i>
                </div>
                <?php if ($user): ?>
                <div class="page-actions">
                    <a href="<?= e(url('new-thread') . ($category ? '?c=' . rawurlencode($category['slug']) : '')) ?>" class="btn btn-primary"><i data-lucide="plus"></i>New thread</a>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="chips">
            <a class="chip<?= $filter === '' ? ' active' : '' ?>" href="<?= e($link(['filter' => null, 'page' => null])) ?>">All</a>
            <?php if ($user): ?>
                <a class="chip<?= $filter === 'following' ? ' active' : '' ?>" href="<?= e($link(['filter' => 'following', 'page' => null])) ?>"><i data-lucide="bell"></i>Following</a>
            <?php endif; ?>
            <?php if ($__hasCategoryNav): ?>
            <div class="dropdown" data-dropdown>
                <button type="button" class="chip chip-dropdown<?= $category ? ' active' : '' ?>" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle>
                    <i data-lucide="folder"></i>
                    <span><?= e($category ? $category['name'] : 'Categories') ?></span>
                    <i data-lucide="chevron-down" class="chip-caret"></i>
                </button>
                <div class="dropdown-panel cat-panel" aria-label="Categories">
                    <a href="<?= e(url()) ?>" class="dropdown-item<?= !$category ? ' active' : '' ?>">
                        <i data-lucide="layers"></i>
                        <span class="dropdown-item-label">All categories</span>
                    </a>
                    <?php foreach ($categories as $c): ?>
                        <a href="<?= e(url('category/' . $c['slug'])) ?>" class="dropdown-item<?= $category && (int)$category['id'] === (int)$c['id'] ? ' active' : '' ?>">
                            <i data-lucide="folder"></i>
                            <span class="dropdown-item-label"><?= e($c['name']) ?></span>
                            <span class="dropdown-item-count"><?= (int)$c['thread_count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="chip<?= $filter === 'mine' ? ' active' : '' ?>" href="<?= e($link(['filter' => 'mine', 'page' => null])) ?>"><i data-lucide="user"></i>My threads</a>
            <?php endif; ?>
            <a class="chip<?= $filter === 'unanswered' ? ' active' : '' ?>" href="<?= e($link(['filter' => 'unanswered', 'page' => null])) ?>"><i data-lucide="circle-help"></i>Unanswered</a>
        </div>

        <?php if (!$threads): ?>
            <div class="card empty-state">
                <i data-lucide="inbox"></i>
                <h3>No threads here yet</h3>
                <p><?= $filter ? 'Nothing matches this filter.' : 'Be the first to start a conversation.' ?></p>
                <?php if ($user): ?><a class="btn btn-primary" href="<?= e(url('new-thread') . ($category ? '?c=' . rawurlencode($category['slug']) : '')) ?>">Start a thread</a><?php endif; ?>
            </div>
        <?php else: ?>
            <?php require __DIR__ . '/partials/thread_rows.php'; ?>
            <?= pagination($page, $pages, $baseUrl, $qs()) ?>
        <?php endif; ?>

        <?php if ($__widgets['bottom'] !== ''): ?>
        <div class="home-widgets home-widgets-bottom only-desktop"><?= $__widgets['bottom'] ?></div>
        <?php endif; ?>
        <?php if ($__widgets['m_bottom'] !== ''): ?>
        <div class="home-widgets home-widgets-bottom only-mobile"><?= $__widgets['m_bottom'] ?></div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
