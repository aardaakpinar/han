<?php
declare(strict_types=1);

/**
 * Ana sayfa widget sistemi.
 *
 * Her widget bir bölgeye (zone) yerleştirilir: sidebar, top (konu listesinin üstü),
 * bottom (altı) veya off (gizli). Sıra ve bölge ayarı "home_widgets" ayarında
 * "id:zone,id:zone,..." biçiminde saklanır. Yeni bir widget eklemek için
 * home_widget_registry()'ye bir satır ve render_home_widget()'e bir case eklemek yeterlidir.
 */

const HOME_WIDGET_ZONES = [
    'sidebar' => 'Sidebar',
    'top'     => 'Above threads',
    'bottom'  => 'Below threads',
    'off'     => 'Hidden',
];

/** Mobilde kenar çubuğu yoktur; widget'lar konu listesinin üstüne veya altına yerleşir. */
const HOME_WIDGET_ZONES_MOBILE = [
    'top'    => 'Above threads',
    'bottom' => 'Below threads',
    'off'    => 'Hidden',
];

/** id => [label, icon, description, varsayılan bölge] (dizideki sıra varsayılan sıradır). */
function home_widget_registry(): array
{
    return [
        'categories' => ['label' => 'Categories', 'icon' => 'folder', 'description' => 'Browse threads by category.', 'zone' => 'sidebar'],
        'stats'      => ['label' => 'Forum stats', 'icon' => 'activity', 'description' => 'Thread, reply and member counts.', 'zone' => 'sidebar'],
        'popular'    => ['label' => 'Popular threads', 'icon' => 'flame', 'description' => 'The most viewed or discussed threads.', 'zone' => 'off'],
        'recent'     => ['label' => 'Recent replies', 'icon' => 'messages-square', 'description' => 'The latest replies across the forum.', 'zone' => 'off'],
        'members'    => ['label' => 'New members', 'icon' => 'users', 'description' => 'Who joined most recently.', 'zone' => 'off'],
        'about'      => ['label' => 'About', 'icon' => 'info', 'description' => 'A short text about your community. Edit it under Admin → Settings → Home page.', 'zone' => 'off'],
        'links'      => ['label' => 'Links', 'icon' => 'link', 'description' => 'A list of useful links. Edit it under Admin → Settings → Home page.', 'zone' => 'off'],
    ];
}

/** Bir widget'ın verilen bölge kümesindeki varsayılan bölgesi (mobilde sidebar => bottom). */
function home_widget_default_zone(string $id, bool $mobile = false): string
{
    $zone = home_widget_registry()[$id]['zone'] ?? 'off';
    if ($mobile && $zone === 'sidebar') {
        return $id === 'categories' ? 'off' : 'bottom';
    }
    return $zone;
}

/**
 * "id:zone,..." metnini geçerli, sıralı [['id','zone'],...] listesine çevirir;
 * eksik widget'lar varsayılan bölgeyle sona eklenir.
 */
function home_widgets_parse(string $raw, bool $mobile = false): array
{
    $registry = home_widget_registry();
    $zones = $mobile ? HOME_WIDGET_ZONES_MOBILE : HOME_WIDGET_ZONES;
    $out = [];
    $seen = [];
    foreach (explode(',', $raw) as $part) {
        [$id, $zone] = array_pad(explode(':', trim($part), 2), 2, '');
        if (!isset($registry[$id]) || isset($seen[$id])) {
            continue;
        }
        if (!isset($zones[$zone])) {
            $zone = home_widget_default_zone($id, $mobile);
        }
        $out[] = ['id' => $id, 'zone' => $zone];
        $seen[$id] = true;
    }
    foreach ($registry as $id => $meta) {
        if (!isset($seen[$id])) {
            $out[] = ['id' => $id, 'zone' => home_widget_default_zone($id, $mobile)];
        }
    }
    return $out;
}

/** Masaüstü yerleşiminden otomatik mobil yerleşim: sidebar => alt, üst => üst, alt => alt. */
function home_widgets_mobile_from_desktop(array $desktop): array
{
    $out = [];
    foreach ($desktop as $w) {
        $zone = match ($w['zone']) {
            'top' => 'top',
            'bottom', 'sidebar' => $w['id'] === 'categories' && $w['zone'] === 'sidebar' ? 'off' : 'bottom',
            default => 'off',
        };
        $out[] = ['id' => $w['id'], 'zone' => $zone];
    }
    return $out;
}

function home_widgets_serialize(array $list): string
{
    return implode(',', array_map(static fn(array $w): string => $w['id'] . ':' . $w['zone'], $list));
}

function home_widgets_default(): string
{
    return home_widgets_serialize(home_widgets_parse(''));
}

/** Geçerli (kullanıcı tercihi veya site) masaüstü yerleşim listesi. */
function home_widgets_layout(): array
{
    return home_widgets_parse((string)effective_setting('home_widgets'));
}

/** Geçerli mobil yerleşim; boşsa masaüstü yerleşiminden türetilir. */
function home_widgets_layout_mobile(): array
{
    $raw = (string)effective_setting('home_widgets_mobile');
    if ($raw === '') {
        return home_widgets_mobile_from_desktop(home_widgets_layout());
    }
    return home_widgets_parse($raw, true);
}

function home_widget_zone(string $id, bool $mobile = false): string
{
    foreach ($mobile ? home_widgets_layout_mobile() : home_widgets_layout() as $w) {
        if ($w['id'] === $id) {
            return $w['zone'];
        }
    }
    return 'off';
}

/**
 * Bölge => HTML. Boş çıktı veren widget'lar (ör. metni girilmemiş "About") atlanır.
 * Masaüstü: sidebar/top/bottom. Mobil: m_top/m_bottom (sidebar mobilde gösterilmez).
 *
 * @param array{categories: array, category: ?array, stats: array} $ctx
 * @return array{sidebar: string, top: string, bottom: string, m_top: string, m_bottom: string}
 */
function home_widgets_html(array $ctx): array
{
    $zones = ['sidebar' => '', 'top' => '', 'bottom' => '', 'm_top' => '', 'm_bottom' => ''];
    foreach (home_widgets_layout() as $w) {
        if ($w['zone'] !== 'off') {
            $zones[$w['zone']] .= render_home_widget($w['id'], $ctx);
        }
    }
    foreach (home_widgets_layout_mobile() as $w) {
        if ($w['zone'] !== 'off') {
            $zones['m_' . $w['zone']] .= render_home_widget($w['id'], $ctx);
        }
    }
    return $zones;
}

function widget_item_limit(): int
{
    return max(3, min(10, (int)site_setting('widget_item_count') ?: 5));
}

function render_home_widget(string $id, array $ctx): string
{
    // Aynı widget masaüstü ve mobil yerleşimde geçebilir; sorguları bir kez çalıştır.
    static $cache = [];
    if (isset($cache[$id])) {
        return $cache[$id];
    }

    $title = static fn(string $t): string => '<h2 class="widget-title">' . e($t) . '</h2>';
    $limit = widget_item_limit();
    $pdo = Database::get();

    ob_start();
    switch ($id) {
        case 'categories':
            $stats = $ctx['stats'];
            $category = $ctx['category'];
            ?>
            <section class="widget widget-categories">
                <?= $title('Categories') ?>
                <ul class="cat-list">
                    <li><a href="<?= e(url()) ?>" class="<?= !$category ? 'active' : '' ?>"><span>All threads</span><span class="count"><?= (int)$stats['threads'] ?></span></a></li>
                    <?php foreach ($ctx['categories'] as $c): ?>
                        <li><a href="<?= e(url('category/' . $c['slug'])) ?>" class="<?= $category && (int)$category['id'] === (int)$c['id'] ? 'active' : '' ?>"><span><?= e($c['name']) ?></span><span class="count"><?= (int)$c['thread_count'] ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
            break;

        case 'stats':
            $stats = $ctx['stats'];
            ?>
            <section class="widget widget-stats">
                <?= $title('Forum stats') ?>
                <dl class="stat-list">
                    <div><dt>Threads</dt><dd><?= compact_number((int)$stats['threads']) ?></dd></div>
                    <div><dt>Replies</dt><dd><?= compact_number((int)$stats['posts']) ?></dd></div>
                    <div><dt>Members</dt><dd><?= compact_number((int)$stats['members']) ?></dd></div>
                </dl>
                <?php if (!empty($stats['newest'])): ?>
                    <p class="widget-note">Newest member: <a href="<?= e(url('profile/' . rawurlencode($stats['newest']['username']))) ?>"><?= e($stats['newest']['username']) ?></a></p>
                <?php endif; ?>
            </section>
            <?php
            break;

        case 'popular':
            $order = site_setting('count_views') ? 't.views DESC, replies DESC' : 'replies DESC';
            $stmt = $pdo->prepare(
                "SELECT t.id, t.title, t.views, (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) AS replies
                 FROM threads t ORDER BY $order, t.id DESC LIMIT " . $limit
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (!$rows) {
                break;
            }
            ?>
            <section class="widget widget-popular">
                <?= $title('Popular threads') ?>
                <ul class="widget-list">
                    <?php foreach ($rows as $r): ?>
                        <li><a class="widget-link" href="<?= e(url('thread/' . (int)$r['id'])) ?>">
                            <span class="widget-link-title"><?= e($r['title']) ?></span>
                            <span class="widget-link-meta"><?= (int)$r['replies'] ?> <?= (int)$r['replies'] === 1 ? 'reply' : 'replies' ?><?= site_setting('count_views') ? ' · ' . compact_number((int)$r['views']) . ' views' : '' ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
            break;

        case 'recent':
            $stmt = $pdo->prepare(
                'SELECT p.id, p.thread_id, p.body, p.created_at, u.username, t.title
                 FROM posts p JOIN users u ON u.id = p.user_id JOIN threads t ON t.id = p.thread_id
                 ORDER BY p.id DESC LIMIT ' . $limit
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (!$rows) {
                break;
            }
            ?>
            <section class="widget widget-recent">
                <?= $title('Recent replies') ?>
                <ul class="widget-list">
                    <?php foreach ($rows as $r):
                        $pg = Post::pageOf((int)$r['thread_id'], (int)$r['id']);
                        $href = url('thread/' . (int)$r['thread_id']) . ($pg > 1 ? '?page=' . $pg : '') . '#post-' . (int)$r['id'];
                    ?>
                        <li><a class="widget-link" href="<?= e($href) ?>">
                            <span class="widget-link-title"><?= e(plain_excerpt((string)$r['body'], 70)) ?></span>
                            <span class="widget-link-meta"><?= e($r['username']) ?> · <?= e($r['title']) ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
            break;

        case 'members':
            $stmt = $pdo->prepare('SELECT username, created_at FROM users ORDER BY id DESC LIMIT ' . $limit);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (!$rows) {
                break;
            }
            ?>
            <section class="widget widget-members">
                <?= $title('New members') ?>
                <ul class="widget-list">
                    <?php foreach ($rows as $r): ?>
                        <li><a class="widget-member" href="<?= e(url('profile/' . rawurlencode($r['username']))) ?>">
                            <?= avatar($r['username'], 'sm') ?>
                            <span class="widget-link-title"><?= e($r['username']) ?></span>
                            <span class="widget-link-meta"><?= e(time_ago((int)$r['created_at'])) ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
            break;

        case 'about':
            $text = trim((string)site_setting('widget_about_text'));
            if ($text === '') {
                break;
            }
            $heading = trim((string)site_setting('widget_about_title')) ?: 'About';
            ?>
            <section class="widget widget-about">
                <?= $title($heading) ?>
                <p class="widget-text"><?= nl2br(e($text)) ?></p>
            </section>
            <?php
            break;

        case 'links':
            $items = [];
            foreach (preg_split('/\r?\n/', (string)site_setting('widget_links')) ?: [] as $line) {
                $parts = array_map('trim', explode('|', $line, 2));
                if (count($parts) !== 2 || $parts[0] === '' || !preg_match('#^(https?://|/)#i', $parts[1])) {
                    continue;
                }
                $items[] = $parts;
            }
            if (!$items) {
                break;
            }
            $heading = trim((string)site_setting('widget_links_title')) ?: 'Links';
            ?>
            <section class="widget widget-links">
                <?= $title($heading) ?>
                <ul class="widget-list">
                    <?php foreach ($items as [$label, $href]):
                        $external = (bool)preg_match('#^https?://#i', $href);
                    ?>
                        <li><a class="widget-link widget-link-plain" href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                            <span class="widget-link-title"><?= e($label) ?></span>
                            <?php if ($external): ?><i data-lucide="arrow-up-right"></i><?php endif; ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
            break;
    }
    return $cache[$id] = (string)ob_get_clean();
}
