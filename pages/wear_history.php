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
?>

<section class="section-heading">
    <span class="eyebrow">Usage timeline</span>
    <h1>Wear history</h1>
    <p class="text-muted">A running record of what has been worn and when it last appeared in rotation.</p>
</section>

<div class="feature-card">
    <?php if ($history === []): ?>
        <div class="text-center py-5">
            <h3>No wear records yet</h3>
            <p class="text-muted mb-0">Mark items as worn from the wardrobe page to populate this timeline.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
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
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars('../' . ($entry['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($entry['name']) ?>" class="history-thumb" onerror="this.src='https://placehold.co/80x80/f4efe6/372f2d?text=Item';">
                                    <strong><?= htmlspecialchars($entry['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($entry['category']) ?></td>
                            <td><?= htmlspecialchars($entry['color'] ?: 'Unspecified') ?></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($entry['worn_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
