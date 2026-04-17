<?php
$pageTitle = 'Wear History';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/header.php';

$history = $conn->query("
    SELECT wh.worn_at, c.name, c.category, c.color, c.image_path
    FROM wear_history wh
    INNER JOIN clothes c ON c.id = wh.clothing_id
    ORDER BY wh.worn_at DESC
")->fetch_all(MYSQLI_ASSOC);

$totalWearEvents = count($history);
$latestWearAt = $history[0]['worn_at'] ?? null;
$categoryBreakdown = [];
foreach ($history as $entry) {
    $categoryName = trim((string) ($entry['category'] ?? 'Uncategorized'));
    $categoryBreakdown[$categoryName] = ($categoryBreakdown[$categoryName] ?? 0) + 1;
}
arsort($categoryBreakdown);
$leadCategory = array_key_first($categoryBreakdown);
$leadCategoryTotal = $leadCategory ? (int) ($categoryBreakdown[$leadCategory] ?? 0) : 0;
?>

<section class="hero-panel wear-history-hero mb-4 mb-lg-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <span class="eyebrow">Usage timeline</span>
            <h1 class="display-5 hero-title mt-2">Track wardrobe rotation with a cleaner wear ledger.</h1>
            <p class="hero-copy">Review recent wear activity, spot the categories leading your rotation, and keep styling decisions grounded in what you actually wear.</p>
            <div class="hero-chip-row mt-4">
                <span>Wear events: <?= $totalWearEvents ?></span>
                <span>Latest log: <?= $latestWearAt ? htmlspecialchars(date('M d, Y', strtotime($latestWearAt))) : 'Pending' ?></span>
                <span>Lead category: <?= htmlspecialchars($leadCategory ?: 'None yet') ?></span>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="feature-card hero-spotlight-card wear-history-spotlight-card">
                <span class="mini-label">Rotation Snapshot</span>
                <h3><?= $totalWearEvents ?></h3>
                <p class="hero-spotlight-meta">Logged wear events</p>
                <p class="text-muted mb-3"><?= $latestWearAt ? 'Last recorded wear on ' . date('M d, Y h:i A', strtotime($latestWearAt)) : 'Mark items as worn from your wardrobe to begin your usage record.' ?></p>
                <div class="spotlight-divider"></div>
                <div class="spotlight-points">
                    <div>
                        <span class="spotlight-kicker">Lead category</span>
                        <strong><?= htmlspecialchars($leadCategory ?: 'Awaiting data') ?></strong>
                    </div>
                    <div>
                        <span class="spotlight-kicker">Category logs</span>
                        <strong><?= $leadCategoryTotal ?></strong>
                    </div>
                    <div>
                        <span class="spotlight-kicker">Timeline status</span>
                        <strong><?= $totalWearEvents > 0 ? 'Active rotation' : 'Empty ledger' ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="metric-card luxury-metric-card wear-history-metric">
            <span>Total wear logs</span>
            <h2><?= $totalWearEvents ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card luxury-metric-card wear-history-metric">
            <span>Latest recorded wear</span>
            <h2><?= $latestWearAt ? htmlspecialchars(date('M d', strtotime($latestWearAt))) : '--' ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card luxury-metric-card wear-history-metric">
            <span>Lead category</span>
            <h2><?= htmlspecialchars($leadCategory ?: 'None') ?></h2>
        </div>
    </div>
</section>

<div class="feature-card wear-history-ledger">
    <?php if ($history === []): ?>
        <div class="text-center py-5">
            <h3>No wear records yet</h3>
            <p class="text-muted mb-0">Mark items as worn from the wardrobe page to populate this timeline.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table wear-history-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Worn At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td>
                                <div class="wear-history-item-cell">
                                    <img src="<?= htmlspecialchars('../' . ($entry['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($entry['name']) ?>" class="history-thumb" onerror="this.src='https://placehold.co/80x80/f4efe6/372f2d?text=Item';">
                                    <div>
                                        <strong><?= htmlspecialchars($entry['name']) ?></strong>
                                        <div class="wear-history-subtext">Wardrobe item log</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="wear-history-pill"><?= htmlspecialchars($entry['category']) ?></span></td>
                            <td><span class="wear-history-pill wear-history-pill-soft"><?= htmlspecialchars($entry['color'] ?: 'Unspecified') ?></span></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($entry['worn_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
