<?php
$pageTitle = 'Add Item';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-heading">
    <span class="eyebrow">Wardrobe entry</span>
    <h1>Add an item</h1>
    <p class="text-muted">Capture the essentials now, then let the system help organize and recommend later.</p>
</section>

<div class="feature-card">
    <form action="../actions/save_clothing.php" method="POST" enctype="multipart/form-data" class="row g-4">
        <div class="col-md-6">
            <label for="name" class="form-label">Item name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="col-md-6">
            <label for="brand" class="form-label">Brand</label>
            <input type="text" class="form-control" id="brand" name="brand" placeholder="Optional">
        </div>
        <div class="col-md-4">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
                <option value="">Choose...</option>
                <option>Top</option>
                <option>Bottom</option>
                <option>Dress</option>
                <option>Outerwear</option>
                <option>Shoes</option>
                <option>Accessory</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="color" class="form-label">Color</label>
            <input type="text" class="form-control" id="color" name="color" placeholder="Leave blank to auto-detect">
        </div>
        <div class="col-md-4">
            <label for="season" class="form-label">Season</label>
            <select class="form-select" id="season" name="season" required>
                <option value="">Choose...</option>
                <option>All Season</option>
                <option>Summer</option>
                <option>Rainy</option>
                <option>Winter</option>
                <option>Spring</option>
                <option>Autumn</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="occasion" class="form-label">Occasion</label>
            <select class="form-select" id="occasion" name="occasion" required>
                <option value="">Choose...</option>
                <option>Casual</option>
                <option>Formal</option>
                <option>Party</option>
                <option>Business</option>
                <option>Travel</option>
                <option>Sportswear</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="image" class="form-label">Image upload</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>
        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Fabric, fit, styling notes, or purchase details"></textarea>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="favorite" name="favorite">
                <label class="form-check-label" for="favorite">Mark as favorite</label>
            </div>
        </div>
        <div class="col-md-5">
            <div class="image-preview" data-preview-wrapper>
                <img src="https://placehold.co/640x480/f4efe6/372f2d?text=Preview" alt="Preview" id="imagePreview">
            </div>
        </div>
        <div class="col-md-7 d-flex align-items-end">
            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-gold">Save Item</button>
                <a href="wardrobe.php" class="btn btn-outline-dark">View Wardrobe</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
