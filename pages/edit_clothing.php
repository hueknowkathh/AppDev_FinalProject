<?php
$pageTitle = 'Edit Clothing';
require __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM clothes WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    session_start();
    $_SESSION['flash_message'] = 'Clothing item not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: wardrobe.php');
    exit;
}

require __DIR__ . '/../includes/header.php';
?>

<section class="section-heading">
    <span class="eyebrow">Wardrobe edit</span>
    <h1>Update clothing details</h1>
    <p class="text-muted">Refine metadata to improve search accuracy and recommendation quality.</p>
</section>

<div class="feature-card">
    <form action="../actions/update_clothing.php" method="POST" enctype="multipart/form-data" class="row g-4">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= htmlspecialchars($item['image_path']) ?>">
        <div class="col-md-6">
            <label for="name" class="form-label">Clothing name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="brand" class="form-label">Brand</label>
            <input type="text" class="form-control" id="brand" name="brand" value="<?= htmlspecialchars($item['brand']) ?>">
        </div>
        <div class="col-md-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
                <?php foreach (['Top', 'Bottom', 'Dress', 'Outerwear', 'Shoes', 'Accessory'] as $option): ?>
                    <option value="<?= $option ?>" <?= $item['category'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="color" class="form-label">Color</label>
            <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($item['color']) ?>">
        </div>
        <div class="col-md-3">
            <label for="season" class="form-label">Season</label>
            <select class="form-select" id="season" name="season" required>
                <?php foreach (['All Season', 'Summer', 'Rainy', 'Winter', 'Spring', 'Autumn'] as $option): ?>
                    <option value="<?= $option ?>" <?= $item['season'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="occasion" class="form-label">Occasion</label>
            <select class="form-select" id="occasion" name="occasion" required>
                <?php foreach (['Casual', 'Formal', 'Party', 'Business', 'Travel', 'Sportswear'] as $option): ?>
                    <option value="<?= $option ?>" <?= $item['occasion'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" id="notes" name="notes" rows="4"><?= htmlspecialchars($item['notes']) ?></textarea>
        </div>
        <div class="col-md-4">
            <label for="image" class="form-label">Replace image</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" value="1" id="favorite" name="favorite" <?= (int) $item['favorite'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="favorite">Favorite item</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="image-preview">
                <img src="<?= htmlspecialchars('../' . ($item['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($item['name']) ?>" id="imagePreview" onerror="this.src='https://placehold.co/640x480/f4efe6/372f2d?text=No+Image';">
            </div>
        </div>
        <div class="col-md-8 d-flex align-items-end">
            <div class="d-flex gap-3 flex-wrap">
                <button type="submit" class="btn btn-gold">Update Item</button>
                <a href="wardrobe.php" class="btn btn-outline-dark">Back to Wardrobe</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
