<?php
$pageTitle = 'Recommendations';
require __DIR__ . '/../includes/header.php';
?>

<section class="section-heading">
    <span class="eyebrow">Digital stylist</span>
    <h1>Generate 3 to 5 smart outfit options</h1>
    <p class="text-muted">Users access the AI from this page through the Recommendations menu in the navbar, then generate multiple looks based on theme, season, occasion, and what is already in the wardrobe.</p>
</section>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="feature-card h-100">
            <form id="recommendationForm" class="row g-3">
                <div class="col-12">
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
                    <label for="season" class="form-label">Season</label>
                    <select class="form-select" id="season" name="season">
                        <option value="">Any season</option>
                        <option>All Season</option>
                        <option>Summer</option>
                        <option>Rainy</option>
                        <option>Winter</option>
                        <option>Spring</option>
                        <option>Autumn</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="preferred_style" class="form-label">Theme / style</label>
                    <input type="text" class="form-control" id="preferred_style" name="preferred_style" list="styleSuggestions" placeholder="Minimalist, chic, smart casual, korean, streetwear">
                    <datalist id="styleSuggestions">
                        <option value="Minimalist">
                        <option value="Chic">
                        <option value="Smart Casual">
                        <option value="Streetwear">
                        <option value="Korean">
                        <option value="Comfy">
                        <option value="Monochrome">
                        <option value="Classic">
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label for="color" class="form-label">Optional color preference</label>
                    <input type="text" class="form-control" id="color" name="color" placeholder="Black, beige, white, navy">
                </div>
                <div class="col-md-6">
                    <div class="h-100 d-flex flex-column justify-content-end">
                        <div class="recommendation-auto-note">
                            <span class="mini-label">Auto mode</span>
                            <p class="mb-0 text-muted small">The system now decides how many outfit ideas to generate based on your wardrobe and available matches.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-gold">Generate Outfit Options</button>
                </div>
                <div class="col-12">
                    <p class="text-muted small mb-0">For more accurate recos, use a clear style keyword like <em>Minimalist</em>, <em>Smart Casual</em>, or <em>Streetwear</em>.</p>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="feature-card h-100">
            <span class="mini-label">Recommendation result</span>
            <div id="recommendationResult">
                <h3>Ready to style</h3>
                <p class="text-muted mb-3">Submit the form to receive 3 to 5 outfit ideas generated from the wardrobe you already uploaded.</p>
                <div class="chip-row">
                    <span>Access point: Recommendations page</span>
                    <span>Trigger: Generate Outfit Options button</span>
                    <span>Source: Your saved wardrobe pieces</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
