<?php
$pageTitle = 'Insights';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/header.php';

$totals = $conn->query('SELECT COUNT(*) AS total_items, SUM(favorite = 1) AS total_favorites FROM clothes')->fetch_assoc();
$mostWorn = $conn->query('SELECT name, wear_count FROM clothes ORDER BY wear_count DESC, name ASC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
$leastWorn = $conn->query('SELECT name, wear_count FROM clothes ORDER BY wear_count ASC, name ASC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
$neverWorn = $conn->query('SELECT name, category FROM clothes WHERE wear_count = 0 ORDER BY name ASC LIMIT 10')->fetch_all(MYSQLI_ASSOC);
$categoryStats = $conn->query('SELECT category, COUNT(*) AS total FROM clothes GROUP BY category ORDER BY total DESC')->fetch_all(MYSQLI_ASSOC);
$seasonStats = $conn->query('SELECT season, COUNT(*) AS total FROM clothes GROUP BY season ORDER BY total DESC')->fetch_all(MYSQLI_ASSOC);
$totalItems = max(1, (int) ($totals['total_items'] ?? 0));
?>

<section class="section-heading">
    <span class="eyebrow">Wardrobe analytics</span>
    <h1>Insights and usage patterns</h1>
    <p class="text-muted">Surface wardrobe health, usage balance, and styling opportunities from the data you collect.</p>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="metric-card">
            <span>Total items</span>
            <h2><?= (int) ($totals['total_items'] ?? 0) ?></h2>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card">
            <span>Favorite items</span>
            <h2><?= (int) ($totals['total_favorites'] ?? 0) ?></h2>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card">
            <span>Never worn items</span>
            <h2><?= count($neverWorn) ?></h2>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card">
            <span>Tracked categories</span>
            <h2><?= count($categoryStats) ?></h2>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="feature-card h-100">
            <span class="mini-label">Most worn</span>
            <h3>Rotation leaders</h3>
            <?php if ($mostWorn === []): ?>
                <p class="text-muted mb-0">No data yet.</p>
            <?php else: ?>
                <?php foreach ($mostWorn as $item): ?>
                    <div class="insight-row">
                        <span><?= htmlspecialchars($item['name']) ?></span>
                        <strong><?= (int) $item['wear_count'] ?> wears</strong>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="feature-card h-100">
            <span class="mini-label">Least worn</span>
            <h3>Pieces to revisit</h3>
            <?php if ($leastWorn === []): ?>
                <p class="text-muted mb-0">No data yet.</p>
            <?php else: ?>
                <?php foreach ($leastWorn as $item): ?>
                    <div class="insight-row">
                        <span><?= htmlspecialchars($item['name']) ?></span>
                        <strong><?= (int) $item['wear_count'] ?> wears</strong>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="feature-card h-100">
            <span class="mini-label">Category distribution</span>
            <h3>Wardrobe composition</h3>
            <?php if ($categoryStats === []): ?>
                <p class="text-muted mb-0">No data yet.</p>
            <?php else: ?>
                <?php foreach ($categoryStats as $stat): ?>
                    <?php $percent = (int) round(($stat['total'] / $totalItems) * 100); ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($stat['category']) ?></span>
                            <strong><?= (int) $stat['total'] ?></strong>
                        </div>
                        <div class="progress luxury-progress">
                            <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="feature-card h-100">
            <span class="mini-label">Season distribution</span>
            <h3>Seasonal readiness</h3>
            <?php if ($seasonStats === []): ?>
                <p class="text-muted mb-0">No data yet.</p>
            <?php else: ?>
                <?php foreach ($seasonStats as $stat): ?>
                    <?php $percent = (int) round(($stat['total'] / $totalItems) * 100); ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($stat['season']) ?></span>
                            <strong><?= (int) $stat['total'] ?></strong>
                        </div>
                        <div class="progress luxury-progress">
                            <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12">
        <div class="feature-card">
            <span class="mini-label">Never worn</span>
            <h3>Items waiting for their first outing</h3>
            <?php if ($neverWorn === []): ?>
                <p class="text-muted mb-0">Every tracked item has been worn at least once.</p>
            <?php else: ?>
                <div class="chip-row mt-3">
                    <?php foreach ($neverWorn as $item): ?>
                        <span><?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['category']) ?>)</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
