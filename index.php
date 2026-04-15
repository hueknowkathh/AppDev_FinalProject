<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/header.php';

$totalItems = (int) $conn->query('SELECT COUNT(*) AS total FROM clothes')->fetch_assoc()['total'];
$totalOutfits = (int) $conn->query('SELECT COUNT(*) AS total FROM outfits')->fetch_assoc()['total'];
$favorites = (int) $conn->query('SELECT COUNT(*) AS total FROM clothes WHERE favorite = 1')->fetch_assoc()['total'];
$mostWorn = $conn->query("SELECT name, wear_count FROM clothes ORDER BY wear_count DESC, name ASC LIMIT 1")->fetch_assoc();
$latestOutfit = $conn->query("
    SELECT o.name, o.occasion, o.season, o.created_at, COUNT(oi.id) AS item_total
    FROM outfits o
    LEFT JOIN outfit_items oi ON oi.outfit_id = o.id
    GROUP BY o.id, o.name, o.occasion, o.season, o.created_at
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 1
")->fetch_assoc();
$signatureFavorite = $conn->query("
    SELECT name, category, color, occasion, last_worn
    FROM clothes
    WHERE favorite = 1
    ORDER BY wear_count DESC, created_at DESC
    LIMIT 1
")->fetch_assoc();
$recentPieces = $conn->query("
    SELECT name, category, color, occasion, wear_count, last_worn, favorite
    FROM clothes
    ORDER BY created_at DESC, id DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
$dormantPieces = $conn->query("
    SELECT name, category, occasion, wear_count, last_worn
    FROM clothes
    ORDER BY (last_worn IS NULL) DESC, last_worn ASC, wear_count ASC, created_at ASC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);
$topCategory = $conn->query("
    SELECT category, COUNT(*) AS total
    FROM clothes
    GROUP BY category
    ORDER BY total DESC, category ASC
    LIMIT 1
")->fetch_assoc();
$topColor = $conn->query("
    SELECT color, COUNT(*) AS total
    FROM clothes
    WHERE color IS NOT NULL AND color <> ''
    GROUP BY color
    ORDER BY total DESC, color ASC
    LIMIT 1
")->fetch_assoc();
$topOccasion = $conn->query("
    SELECT occasion, COUNT(*) AS total
    FROM clothes
    GROUP BY occasion
    ORDER BY total DESC, occasion ASC
    LIMIT 1
")->fetch_assoc();

$wearScore = $totalItems > 0 ? (int) round((($favorites * 2) + (($mostWorn['wear_count'] ?? 0) * 3) + ($totalOutfits * 4)) / max(1, $totalItems)) : 0;
$wearScore = max(12, min(98, $wearScore));
$signatureLookTitle = $latestOutfit['name'] ?? ($signatureFavorite['name'] ?? 'Signature look pending');
$signatureLookMeta = $latestOutfit
    ? trim(($latestOutfit['occasion'] ?: 'Curated look') . (!empty($latestOutfit['season']) ? ' / ' . $latestOutfit['season'] : ''))
    : trim(($signatureFavorite['category'] ?? 'Favorite piece') . (!empty($signatureFavorite['occasion']) ? ' / ' . $signatureFavorite['occasion'] : ''));
$signatureLookSupport = $latestOutfit
    ? (($latestOutfit['item_total'] ?? 0) . ' pieces composed / ' . date('M d, Y', strtotime($latestOutfit['created_at'])))
    : ($signatureFavorite ? (($signatureFavorite['color'] ?: 'Refined palette') . ' / ' . ($signatureFavorite['last_worn'] ? 'Last styled ' . date('M d', strtotime($signatureFavorite['last_worn'])) : 'Ready to style')) : 'Save an outfit or mark a favorite to surface your signature edit.');

$styleCategory = $topCategory['category'] ?? 'Curated essentials';
$styleCategoryTotal = (int) ($topCategory['total'] ?? 0);
$styleColor = $topColor['color'] ?? 'Platinum neutrals';
$styleOccasion = $topOccasion['occasion'] ?? 'Everyday luxury';

$dormantCount = 0;
foreach ($dormantPieces as $piece) {
    if (empty($piece['last_worn']) || (int) ($piece['wear_count'] ?? 0) === 0) {
        $dormantCount++;
    }
}

$aiInsight = $totalItems === 0
    ? 'Begin with a few wardrobe pieces to unlock richer styling intelligence and signature outfit suggestions.'
    : 'Your wardrobe leans ' . strtolower($styleCategory) . ' with a strong ' . strtolower($styleColor) . ' story. Build your next curated look around ' . strtolower($styleOccasion) . ' and reactivate ' . $dormantCount . ' dormant piece' . ($dormantCount === 1 ? '' : 's') . ' for a sharper rotation.';
?>

<section class="hero-panel mb-4 mb-lg-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <span class="eyebrow">Chrome atelier dashboard</span>
            <h1 class="display-5 hero-title mt-2">Elevate your digital closet with sharper wardrobe intelligence and editorial polish.</h1>
            <p class="hero-copy">Closet Couture organizes wardrobe pieces, reveals dormant luxury, and turns daily outfit planning into a premium fashion-tech ritual.</p>
            <div class="hero-chip-row mt-4">
                <span>Wardrobe Pieces: <?= $totalItems ?></span>
                <span>Curated Looks: <?= $totalOutfits ?></span>
                <span>Signature Favorites: <?= $favorites ?></span>
            </div>
            <div class="d-flex flex-wrap gap-3 mt-4">
                <a href="pages/wardrobe.php?open_add=1" class="btn btn-gold">Add New Piece</a>
                <a href="pages/recommendation.php" class="btn btn-outline-dark hero-outline-btn">Open AI Styling</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="feature-card hero-spotlight-card tall-card">
                <span class="mini-label">Today's Signature Look</span>
                <h3><?= htmlspecialchars($signatureLookTitle) ?></h3>
                <p class="hero-spotlight-meta"><?= htmlspecialchars($signatureLookMeta ?: 'Refined wardrobe direction') ?></p>
                <p class="text-muted mb-3"><?= htmlspecialchars($signatureLookSupport) ?></p>
                <div class="spotlight-divider"></div>
                <div class="spotlight-points">
                    <div>
                        <span class="spotlight-kicker">Style DNA</span>
                        <strong><?= htmlspecialchars($styleCategory) ?></strong>
                    </div>
                    <div>
                        <span class="spotlight-kicker">Primary tone</span>
                        <strong><?= htmlspecialchars($styleColor) ?></strong>
                    </div>
                    <div>
                        <span class="spotlight-kicker">Mood</span>
                        <strong><?= htmlspecialchars($styleOccasion) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="metric-card luxury-metric-card">
            <span>Wardrobe Pieces</span>
            <h2><?= $totalItems ?></h2>
            <small class="metric-footnote">Cataloged pieces in your chrome atelier.</small>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card luxury-metric-card">
            <span>Curated Looks</span>
            <h2><?= $totalOutfits ?></h2>
            <small class="metric-footnote">Saved outfit compositions ready to revisit.</small>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card luxury-metric-card">
            <span>Signature Favorites</span>
            <h2><?= $favorites ?></h2>
            <small class="metric-footnote">Hero pieces leading your wardrobe identity.</small>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card luxury-metric-card">
            <span>Wear Score</span>
            <h2><?= $wearScore ?></h2>
            <small class="metric-footnote"><?= isset($mostWorn['wear_count']) ? 'Led by ' . htmlspecialchars($mostWorn['name'] ?? 'rotation') . ' with ' . (int) $mostWorn['wear_count'] . ' wears' : 'Start tracking usage to shape your score.' ?></small>
        </div>
    </div>
</section>

<section class="dashboard-section mb-4">
    <div class="section-heading dashboard-heading">
        <span class="eyebrow">Wardrobe intelligence</span>
        <h2>Editorial signals from your current collection</h2>
        <p class="text-muted mb-0">Sharper hierarchy, clearer styling prompts, and a luxury dashboard rhythm built around the wardrobe data you already track.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="feature-card dashboard-feature-card h-100">
                <span class="mini-label">Style DNA</span>
                <h3><?= htmlspecialchars($styleCategory) ?></h3>
                <p class="text-muted">Your collection currently centers on <?= htmlspecialchars(strtolower($styleCategory)) ?> with a strong preference for <?= htmlspecialchars(strtolower($styleColor)) ?> and <?= htmlspecialchars(strtolower($styleOccasion)) ?> dressing.</p>
                <div class="dashboard-stat-stack">
                    <div class="dashboard-stat-chip">
                        <span>Lead category</span>
                        <strong><?= $styleCategoryTotal ?></strong>
                    </div>
                    <div class="dashboard-stat-chip">
                        <span>Color direction</span>
                        <strong><?= htmlspecialchars($styleColor) ?></strong>
                    </div>
                    <div class="dashboard-stat-chip">
                        <span>Signature mood</span>
                        <strong><?= htmlspecialchars($styleOccasion) ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="feature-card dashboard-feature-card h-100">
                <span class="mini-label">Dormant Luxury</span>
                <h3>Pieces waiting for their next appearance</h3>
                <?php if ($dormantPieces === []): ?>
                    <p class="text-muted mb-0">No dormant wardrobe pieces yet.</p>
                <?php else: ?>
                    <div class="dashboard-list">
                        <?php foreach ($dormantPieces as $piece): ?>
                            <div class="insight-row dashboard-list-row">
                                <div>
                                    <strong><?= htmlspecialchars($piece['name']) ?></strong>
                                    <div class="dashboard-row-meta"><?= htmlspecialchars($piece['category']) ?><?= !empty($piece['occasion']) ? ' / ' . htmlspecialchars($piece['occasion']) : '' ?></div>
                                </div>
                                <span class="dashboard-status-pill"><?= empty($piece['last_worn']) ? 'Not styled yet' : 'Last styled ' . date('M d', strtotime($piece['last_worn'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="feature-card dashboard-feature-card dashboard-insight-card h-100">
                <span class="mini-label">AI Styling Insight</span>
                <h3>What to refine next</h3>
                <p class="mb-4"><?= htmlspecialchars($aiInsight) ?></p>
                <a href="pages/recommendation.php" class="dashboard-inline-link">Generate a refined styling recommendation</a>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section mb-4">
    <div class="section-heading dashboard-heading">
        <span class="eyebrow">Boutique wardrobe ledger</span>
        <h2>Recently cataloged wardrobe pieces</h2>
        <p class="text-muted mb-0">A cleaner boutique-style table for quick scanning of rotation, styling potential, and wardrobe freshness.</p>
    </div>
    <div class="feature-card wardrobe-ledger-card">
        <?php if ($recentPieces === []): ?>
            <div class="text-center py-5">
                <h3>No wardrobe pieces yet</h3>
                <p class="text-muted mb-0">Add your first piece to activate the dashboard ledger.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table wardrobe-ledger-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Wardrobe Piece</th>
                            <th>Category</th>
                            <th>Color Story</th>
                            <th>Occasion</th>
                            <th>Wear Score</th>
                            <th>Last Styled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPieces as $piece): ?>
                            <tr>
                                <td>
                                    <div class="ledger-piece-cell">
                                        <div class="ledger-piece-mark"></div>
                                        <div>
                                            <strong><?= htmlspecialchars($piece['name']) ?></strong>
                                            <div class="dashboard-row-meta"><?= !empty($piece['favorite']) ? 'Signature Favorite' : 'Wardrobe Piece' ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($piece['category']) ?></td>
                                <td><?= htmlspecialchars($piece['color'] ?: 'Metallic neutral') ?></td>
                                <td><?= htmlspecialchars($piece['occasion']) ?></td>
                                <td><span class="dashboard-status-pill"><?= (int) $piece['wear_count'] ?> wears</span></td>
                                <td><?= $piece['last_worn'] ? htmlspecialchars(date('M d, Y', strtotime($piece['last_worn']))) : 'Not styled yet' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
