<?php
$pageTitle = 'Edit Fit';
require __DIR__ . '/../config/db.php';

$outfitId = (int) ($_GET['id'] ?? 0);
if ($outfitId <= 0) {
    session_start();
    $_SESSION['flash_message'] = 'Invalid fit selected.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: wardrobe.php');
    exit;
}

$outfitStmt = $conn->prepare('SELECT * FROM outfits WHERE id = ? LIMIT 1');
$outfitStmt->bind_param('i', $outfitId);
$outfitStmt->execute();
$outfit = $outfitStmt->get_result()->fetch_assoc();

if (!$outfit) {
    session_start();
    $_SESSION['flash_message'] = 'Fit not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: wardrobe.php');
    exit;
}

$items = $conn->query('SELECT id, name, category, image_path, color, occasion FROM clothes ORDER BY category ASC, name ASC')->fetch_all(MYSQLI_ASSOC);
$selected = $conn->prepare('SELECT clothing_id FROM outfit_items WHERE outfit_id = ?');
$selected->bind_param('i', $outfitId);
$selected->execute();
$selectedIds = array_map('intval', array_column($selected->get_result()->fetch_all(MYSQLI_ASSOC), 'clothing_id'));

require __DIR__ . '/../includes/header.php';
?>

<section class="section-heading">
    <span class="eyebrow">Saved fit</span>
    <h1>Edit outfit combination</h1>
    <p class="text-muted">Update the fit name, styling context, or the wardrobe pieces included in this combination.</p>
</section>

<form action="../actions/update_outfit.php" method="POST" class="feature-card mb-4">
    <input type="hidden" name="outfit_id" value="<?= (int) $outfit['id'] ?>">
    <div class="row g-3">
        <div class="col-md-5">
            <label for="outfit_name" class="form-label">Fit name</label>
            <input type="text" class="form-control" id="outfit_name" name="outfit_name" value="<?= htmlspecialchars($outfit['name']) ?>" required>
        </div>
        <div class="col-md-3">
            <label for="occasion" class="form-label">Occasion</label>
            <select class="form-select" id="occasion" name="occasion">
                <option value="">Optional</option>
                <?php foreach (['Casual', 'Formal', 'Party', 'Business', 'Travel', 'Sportswear'] as $option): ?>
                    <option value="<?= $option ?>" <?= $outfit['occasion'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="season" class="form-label">Season</label>
            <select class="form-select" id="season" name="season">
                <option value="">Optional</option>
                <?php foreach (['All Season', 'Summer', 'Rainy', 'Winter', 'Spring', 'Autumn'] as $option): ?>
                    <option value="<?= $option ?>" <?= $outfit['season'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 d-grid align-items-end">
            <label class="form-label opacity-0">Save</label>
            <button type="submit" class="btn btn-gold">Save</button>
        </div>
    </div>

    <hr class="my-4">

    <div class="edit-fit-grid">
        <?php foreach ($items as $item): ?>
            <label class="edit-fit-card <?= in_array((int) $item['id'], $selectedIds, true) ? 'is-selected' : '' ?>">
                <input type="checkbox" name="selected_items[]" value="<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedIds, true) ? 'checked' : '' ?>>
                <div class="edit-fit-thumb">
                    <img src="<?= htmlspecialchars('../' . ($item['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/320x320/e7eaee/3b434c?text=Item';">
                </div>
                <strong><?= htmlspecialchars($item['name']) ?></strong>
                <small class="text-muted"><?= htmlspecialchars($item['category']) ?> • <?= htmlspecialchars($item['color'] ?: $item['occasion']) ?></small>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-3 flex-wrap mt-4">
        <button type="submit" class="btn btn-gold">Update Fit</button>
        <a href="wardrobe.php" class="btn btn-outline-dark">Back to Wardrobe</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
