<?php
$pageTitle = 'Wardrobe';
require __DIR__ . '/../config/db.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$occasion = trim($_GET['occasion'] ?? '');
$season = trim($_GET['season'] ?? '');
$openAddModal = isset($_GET['open_add']) && $_GET['open_add'] === '1';

$sql = 'SELECT * FROM clothes WHERE 1=1';
$params = [];
$types = '';

if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR brand LIKE ? OR color LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($category !== '') {
    $sql .= ' AND category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($occasion !== '') {
    $sql .= ' AND occasion = ?';
    $params[] = $occasion;
    $types .= 's';
}

if ($season !== '') {
    $sql .= ' AND season = ?';
    $params[] = $season;
    $types .= 's';
}

$sql .= ' ORDER BY created_at DESC, id DESC';
$stmt = $conn->prepare($sql);
if ($params !== []) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$savedOutfits = $conn->query(" 
    SELECT o.id, o.name, o.occasion, o.season, o.created_at, COUNT(oi.id) AS item_total
    FROM outfits o
    LEFT JOIN outfit_items oi ON oi.outfit_id = o.id
    GROUP BY o.id, o.name, o.occasion, o.season, o.created_at
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$outfitItemsStmt = $conn->prepare('
    SELECT c.id, c.name, c.category, c.image_path
    FROM outfit_items oi
    INNER JOIN clothes c ON c.id = oi.clothing_id
    WHERE oi.outfit_id = ?
    ORDER BY oi.id ASC
');

foreach ($savedOutfits as &$outfit) {
    $outfitId = (int) $outfit['id'];
    $outfitItemsStmt->bind_param('i', $outfitId);
    $outfitItemsStmt->execute();
    $outfit['items'] = $outfitItemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($outfit);

function fitCategoryKey(string $category): string
{
    return strtolower(trim($category));
}

function summarizeFitLayout(array $items): array
{
    $totals = [];

    foreach ($items as $item) {
        $key = fitCategoryKey($item['category'] ?? '');
        $totals[$key] = ($totals[$key] ?? 0) + 1;
    }

    return [
        'itemTotal' => count($items),
        'hasTop' => !empty($totals['top']),
        'hasBottom' => !empty($totals['bottom']),
        'hasDress' => !empty($totals['dress']),
        'hasOuterwear' => !empty($totals['outerwear']),
        'hasShoes' => !empty($totals['shoes']),
        'hasAccessory' => !empty($totals['accessory']),
    ];
}

function fitPieceClass(string $category): string
{
    return match (fitCategoryKey($category)) {
        'top' => 'fit-piece-top',
        'bottom' => 'fit-piece-bottom',
        'dress' => 'fit-piece-dress',
        'outerwear' => 'fit-piece-outerwear',
        'shoes' => 'fit-piece-shoes',
        'accessory' => 'fit-piece-accessory',
        default => 'fit-piece-generic',
    };
}

function fitPieceStyle(string $category, int $categoryIndex, int $categoryTotal, int $globalIndex, array $layoutContext): string
{
    $itemTotal = (int) ($layoutContext['itemTotal'] ?? 0);
    $hasTop = !empty($layoutContext['hasTop']);
    $hasBottom = !empty($layoutContext['hasBottom']);
    $hasDress = !empty($layoutContext['hasDress']);
    $hasOuterwear = !empty($layoutContext['hasOuterwear']);
    $hasShoes = !empty($layoutContext['hasShoes']);
    $hasAccessory = !empty($layoutContext['hasAccessory']);

    $layouts = match (fitCategoryKey($category)) {
        'top' => [
            ['left' => '50%', 'top' => $hasOuterwear ? '28px' : '12px', 'width' => $hasOuterwear ? '46%' : '58%', 'height' => $hasOuterwear ? '34%' : '40%', 'shiftX' => '-50%', 'z' => 5],
            ['left' => '33%', 'top' => '22px', 'width' => '34%', 'height' => '28%', 'shiftX' => '-50%', 'rotate' => '-8deg', 'z' => 7],
            ['left' => '67%', 'top' => '22px', 'width' => '34%', 'height' => '28%', 'shiftX' => '-50%', 'rotate' => '8deg', 'z' => 7],
            ['left' => '50%', 'top' => '36px', 'width' => '30%', 'height' => '24%', 'shiftX' => '-50%', 'z' => 8],
        ],
        'outerwear' => [
            ['left' => '50%', 'top' => '4px', 'width' => $hasDress ? '56%' : '66%', 'height' => $hasDress ? '38%' : '44%', 'shiftX' => '-50%', 'z' => 3],
            ['left' => '30%', 'top' => '14px', 'width' => '38%', 'height' => '30%', 'shiftX' => '-50%', 'rotate' => '-10deg', 'z' => 4],
            ['left' => '70%', 'top' => '14px', 'width' => '38%', 'height' => '30%', 'shiftX' => '-50%', 'rotate' => '10deg', 'z' => 4],
        ],
        'dress' => [
            ['left' => '50%', 'top' => $hasShoes ? '10px' : '16px', 'width' => '52%', 'height' => $hasShoes ? '68%' : '74%', 'shiftX' => '-50%', 'z' => 4],
            ['left' => '34%', 'top' => '24px', 'width' => '36%', 'height' => '54%', 'shiftX' => '-50%', 'rotate' => '-7deg', 'z' => 6],
            ['left' => '66%', 'top' => '24px', 'width' => '36%', 'height' => '54%', 'shiftX' => '-50%', 'rotate' => '7deg', 'z' => 6],
        ],
        'bottom' => [
            ['left' => '50%', 'bottom' => $hasShoes ? '18px' : '10px', 'width' => $hasAccessory ? '44%' : '48%', 'height' => $hasShoes ? '46%' : '52%', 'shiftX' => '-50%', 'z' => 4],
            ['left' => '34%', 'bottom' => '18px', 'width' => '34%', 'height' => '36%', 'shiftX' => '-50%', 'rotate' => '-6deg', 'z' => 6],
            ['left' => '70%', 'bottom' => '18px', 'width' => '34%', 'height' => '36%', 'shiftX' => '-50%', 'rotate' => '6deg', 'z' => 6],
        ],
        'shoes' => [
            ['left' => $hasBottom ? '24%' : '50%', 'bottom' => '6px', 'width' => $hasBottom ? '30%' : '38%', 'height' => $hasBottom ? '18%' : '22%', 'shiftX' => '-50%', 'rotate' => '-7deg', 'z' => 8],
            ['left' => '76%', 'bottom' => '6px', 'width' => '30%', 'height' => '18%', 'shiftX' => '-50%', 'rotate' => '7deg', 'z' => 8],
            ['left' => '38%', 'bottom' => '24px', 'width' => '24%', 'height' => '15%', 'shiftX' => '-50%', 'rotate' => '-4deg', 'z' => 9],
            ['left' => '62%', 'bottom' => '24px', 'width' => '24%', 'height' => '15%', 'shiftX' => '-50%', 'rotate' => '4deg', 'z' => 9],
        ],
        'accessory' => [
            ['left' => '10px', 'top' => '10px', 'width' => $itemTotal <= 3 ? '30%' : '26%', 'height' => $itemTotal <= 3 ? '26%' : '22%', 'z' => 10],
            ['right' => '10px', 'top' => '10px', 'width' => $itemTotal <= 3 ? '30%' : '26%', 'height' => $itemTotal <= 3 ? '26%' : '22%', 'z' => 10],
            ['left' => '10px', 'bottom' => $hasShoes ? '36px' : '16px', 'width' => '24%', 'height' => '20%', 'z' => 10],
            ['right' => '10px', 'bottom' => $hasShoes ? '36px' : '16px', 'width' => '24%', 'height' => '20%', 'z' => 10],
        ],
        default => [
            ['left' => '12px', 'top' => '12px', 'width' => '26%', 'height' => '22%', 'z' => 7],
            ['right' => '12px', 'top' => '12px', 'width' => '26%', 'height' => '22%', 'z' => 7],
            ['left' => '12px', 'bottom' => '12px', 'width' => '26%', 'height' => '22%', 'z' => 7],
            ['right' => '12px', 'bottom' => '12px', 'width' => '26%', 'height' => '22%', 'z' => 7],
        ],
    };

    $layout = $layouts[min($categoryIndex, count($layouts) - 1)];

    if ($categoryTotal > count($layouts) && $categoryIndex >= count($layouts)) {
        $step = $categoryIndex - count($layouts) + 1;
        if (isset($layout['left']) && str_ends_with($layout['left'], '%')) {
            $base = (float) rtrim($layout['left'], '%');
            $layout['left'] = max(18, min(82, $base + (($step % 2 === 0) ? 8 : -8))) . '%';
        }
        if (isset($layout['top']) && str_ends_with($layout['top'], 'px')) {
            $layout['top'] = ((int) rtrim($layout['top'], 'px') + ($step * 8)) . 'px';
        }
        if (isset($layout['bottom']) && str_ends_with($layout['bottom'], 'px')) {
            $layout['bottom'] = ((int) rtrim($layout['bottom'], 'px') + ($step * 8)) . 'px';
        }
        $layout['rotate'] = (((int) ($step % 2 === 0) * 2) - 1) * min(12, 3 + ($step * 2)) . 'deg';
        $layout['z'] = 9 + $globalIndex;
    }

    $declarations = [
        '--piece-left' => $layout['left'] ?? null,
        '--piece-right' => $layout['right'] ?? null,
        '--piece-top' => $layout['top'] ?? null,
        '--piece-bottom' => $layout['bottom'] ?? null,
        '--piece-width' => $layout['width'] ?? null,
        '--piece-height' => $layout['height'] ?? null,
        '--piece-shift-x' => $layout['shiftX'] ?? '0',
        '--piece-shift-y' => $layout['shiftY'] ?? '0',
        '--piece-rotate' => $layout['rotate'] ?? '0deg',
        '--piece-z' => (string) ($layout['z'] ?? ($globalIndex + 1)),
    ];

    $style = [];
    foreach ($declarations as $property => $value) {
        if ($value === null) {
            continue;
        }

        $style[] = $property . ':' . $value;
    }

    return implode(';', $style);
}

require __DIR__ . '/../includes/header.php';
?>

<section class="section-heading wardrobe-heading">
    <span class="eyebrow">Wardrobe gallery</span>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
        <div>
            <h1>WARDROBE STUDIO</h1>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addClothingModal">Add Item</button>
            <div class="wardrobe-heading-meta">
                <span><?= count($items) ?> pieces</span>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="addClothingModal" tabindex="-1" aria-hidden="true" data-open-on-load="<?= $openAddModal ? '1' : '0' ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content couture-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="eyebrow">Wardrobe entry</span>
                    <h2 class="mb-1">Add an item</h2>
                    <p class="text-muted mb-0">Upload a piece without leaving the wardrobe page.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form action="../actions/save_clothing.php" method="POST" enctype="multipart/form-data" class="row g-4">
                    <div class="col-md-6">
                        <label for="modal_name" class="form-label">Item name</label>
                        <input type="text" class="form-control" id="modal_name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="modal_brand" class="form-label">Brand</label>
                        <input type="text" class="form-control" id="modal_brand" name="brand" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label for="modal_category" class="form-label">Category</label>
                        <select class="form-select" id="modal_category" name="category" required>
                            <option value="">Choose...</option>
                            <option>Top</option>
                            <option>Bottom</option>
                            <option>Dress</option>
                            <option>Outerwear</option>
                            <option>Shoes</option>
                            <option>Accessory</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="modal_color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="modal_color" name="color" placeholder="Leave blank to auto-detect">
                    </div>
                    <div class="col-md-3">
                        <label for="modal_season" class="form-label">Season</label>
                        <select class="form-select" id="modal_season" name="season" required>
                            <option value="">Choose...</option>
                            <option>All Season</option>
                            <option>Summer</option>
                            <option>Rainy</option>
                            <option>Winter</option>
                            <option>Spring</option>
                            <option>Autumn</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="modal_occasion" class="form-label">Occasion</label>
                        <select class="form-select" id="modal_occasion" name="occasion" required>
                            <option value="">Choose...</option>
                            <option>Casual</option>
                            <option>Formal</option>
                            <option>Party</option>
                            <option>Business</option>
                            <option>Travel</option>
                            <option>Sportswear</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label for="modal_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="modal_notes" name="notes" rows="4" placeholder="Fabric, fit, styling notes, or purchase details"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="modal_image" class="form-label">Image upload</label>
                        <input type="file" class="form-control" id="modal_image" name="image" accept="image/*" required>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="modal_favorite" name="favorite">
                            <label class="form-check-label" for="modal_favorite">Mark as favorite</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="image-preview image-preview-compact">
                            <img src="https://placehold.co/640x480/e7eaee/3b434c?text=Preview" alt="Preview" id="modalImagePreview">
                        </div>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="d-flex gap-3 flex-wrap w-100 justify-content-end">
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-gold">Save Item</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="wardrobe-shell mb-4">
    <form class="wardrobe-toolbar" method="GET">
        <div class="toolbar-search">
            <input type="text" class="form-control" name="search" placeholder="Search wardrobe" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="toolbar-pills">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                <?php foreach (['Top', 'Bottom', 'Dress', 'Outerwear', 'Shoes', 'Accessory'] as $option): ?>
                    <option value="<?= $option ?>" <?= $category === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="occasion">
                <option value="">All occasions</option>
                <?php foreach (['Casual', 'Formal', 'Party', 'Business', 'Travel', 'Sportswear'] as $option): ?>
                    <option value="<?= $option ?>" <?= $occasion === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="season">
                <option value="">All seasons</option>
                <?php foreach (['All Season', 'Summer', 'Rainy', 'Winter', 'Spring', 'Autumn'] as $option): ?>
                    <option value="<?= $option ?>" <?= $season === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-gold">Apply</button>
        </div>
    </form>
</div>

<?php if ($items === []): ?>
    <div class="feature-card text-center py-5">
        <h3>No clothing items found</h3>
        <p class="text-muted mb-4">Start building the wardrobe gallery by adding your first piece.</p>
        <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addClothingModal">Add Item</button>
    </div>
<?php else: ?>
    <form action="../actions/save_outfit.php" method="POST" class="fit-builder-form" id="fitBuilderForm">
        <div class="fit-builder-shell mb-4">
            <div class="fit-builder-header">
                <div>
                    <span class="eyebrow">Fit Builder</span>
                    <h3>Create your own outfit combination</h3>
                </div>
                <div class="fit-selection-count" data-fit-count>0 selected</div>
            </div>
            <div class="fit-builder-grid">
                <div>
                    <label for="outfit_name" class="form-label">Fit name</label>
                    <input type="text" class="form-control" id="outfit_name" name="outfit_name" placeholder="Weekend monochrome, Campus smart casual" required>
                </div>
                <div>
                    <label for="fit_occasion" class="form-label">Occasion</label>
                    <select class="form-select" id="fit_occasion" name="occasion">
                        <option value="">Optional</option>
                        <option>Casual</option>
                        <option>Formal</option>
                        <option>Party</option>
                        <option>Business</option>
                        <option>Travel</option>
                        <option>Sportswear</option>
                    </select>
                </div>
                <div>
                    <label for="fit_season" class="form-label">Season</label>
                    <select class="form-select" id="fit_season" name="season">
                        <option value="">Optional</option>
                        <option>All Season</option>
                        <option>Summer</option>
                        <option>Rainy</option>
                        <option>Winter</option>
                        <option>Spring</option>
                        <option>Autumn</option>
                    </select>
                </div>
                <div class="d-grid">
                    <label class="form-label opacity-0">Save</label>
                    <button type="submit" class="btn btn-gold">Save This Fit</button>
                </div>
            </div>
            <div class="fit-selected-preview" data-fit-preview>
                <span class="text-muted small">No items selected yet.</span>
            </div>
        </div>

        <div class="wardrobe-app-grid" data-filter-grid>
            <?php foreach ($items as $item): ?>
                <article class="wardrobe-tile">
                    <div class="wardrobe-tile-image">
                        <img src="<?= htmlspecialchars('../' . ($item['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/640x780/e7eaee/3b434c?text=No+Image';">
                        <div class="wardrobe-tile-topbar">
                            <span class="wardrobe-type-pill"><?= htmlspecialchars($item['category']) ?></span>
                            <label class="wardrobe-select-pill">
                                <input
                                    type="checkbox"
                                    name="selected_items[]"
                                    value="<?= (int) $item['id'] ?>"
                                    data-fit-item
                                    data-item-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                                >
                                <span>Add to fit</span>
                            </label>
                        </div>
                    </div>
                    <div class="wardrobe-tile-body">
                        <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                            <div>
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="wardrobe-subtitle mb-0"><?= htmlspecialchars($item['brand'] ?: $item['occasion']) ?></p>
                            </div>
                            <div class="wardrobe-card-tools">
                                <span class="wardrobe-count"><?= (int) $item['wear_count'] ?></span>
                                <div class="dropdown wardrobe-action-menu">
                                    <button class="wardrobe-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open item actions">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end wardrobe-dropdown-menu">
                                        <li><a href="edit_clothing.php?id=<?= (int) $item['id'] ?>" class="dropdown-item wardrobe-dropdown-item">Edit Piece</a></li>
                                        <li><a href="../actions/toggle_favorite.php?id=<?= (int) $item['id'] ?>" class="dropdown-item wardrobe-dropdown-item"><?= (int) ($item['favorite'] ?? 0) === 1 ? 'Remove Favorite' : 'Mark as Favorite' ?></a></li>
                                        <li><a href="../actions/mark_worn.php?id=<?= (int) $item['id'] ?>" class="dropdown-item wardrobe-dropdown-item">Mark as Worn</a></li>
                                        <li><hr class="dropdown-divider wardrobe-dropdown-divider"></li>
                                        <li><a href="../actions/delete_clothing.php?id=<?= (int) $item['id'] ?>" class="dropdown-item wardrobe-dropdown-item wardrobe-dropdown-delete" data-confirm-delete data-confirm-message="<?= htmlspecialchars("Delete \"" . $item['name'] . "\" from your wardrobe? This action cannot be undone.", ENT_QUOTES) ?>">Delete Piece</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="wardrobe-meta-grid">
                            <span><?= htmlspecialchars($item['color'] ?: 'No color') ?></span>
                            <span><?= htmlspecialchars($item['season']) ?></span>
                            <span><?= htmlspecialchars($item['occasion']) ?></span>
                            <span><?= $item['last_worn'] ? htmlspecialchars(date('M d, Y', strtotime($item['last_worn']))) : 'Never worn' ?></span>
                        </div>
                        <?php if (!empty($item['notes'])): ?>
                            <p class="wardrobe-note mb-3"><?= htmlspecialchars($item['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </form>

    <section class="feature-card mt-4 saved-fits-shell">
        <div class="saved-fits-shell-header">
            <div>
                <span class="eyebrow">Saved fits</span>
                <h3 class="mb-1">Recently created outfit combinations</h3>
                <p class="text-muted mb-0">A refined archive of your saved outfit edits, ready to revisit, adjust, and rotate back into wear.</p>
            </div>
            <div class="saved-fits-summary">
                <span><?= count($savedOutfits) ?> saved</span>
                <span><?= count($items) ?> wardrobe pieces</span>
            </div>
        </div>
        <?php if ($savedOutfits === []): ?>
            <div class="saved-fits-empty-state">
                <span class="mini-label">Fit archive</span>
                <p class="text-muted mb-0">No saved fits yet. Select wardrobe items above and save your first outfit.</p>
            </div>
        <?php else: ?>
            <div class="saved-fits-grid">
                <?php foreach ($savedOutfits as $outfit): ?>
                    <?php
                    $categoryTotals = [];
                    $layoutContext = summarizeFitLayout($outfit['items']);
                    foreach ($outfit['items'] as $piece) {
                        $key = fitCategoryKey($piece['category']);
                        $categoryTotals[$key] = ($categoryTotals[$key] ?? 0) + 1;
                    }

                    $categorySeen = [];
                    ?>
                    <article class="saved-fit-showcase">
                        <div class="saved-fit-stage">
                            <?php foreach ($outfit['items'] as $index => $piece): ?>
                                <?php
                                $key = fitCategoryKey($piece['category']);
                                $categoryIndex = $categorySeen[$key] ?? 0;
                                $categorySeen[$key] = $categoryIndex + 1;
                                ?>
                                <div class="saved-fit-piece <?= fitPieceClass($piece['category']) ?>" style="<?= htmlspecialchars(fitPieceStyle($piece['category'], $categoryIndex, $categoryTotals[$key] ?? 1, $index, $layoutContext)) ?>">
                                    <img src="<?= htmlspecialchars('../' . ($piece['image_path'] ?: 'assets/uploads/default.png')) ?>" alt="<?= htmlspecialchars($piece['name']) ?>" onerror="this.src='https://placehold.co/500x700/e7eaee/3b434c?text=Fit';">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="saved-fit-details">
                            <div class="saved-fit-detail-top">
                                <span class="mini-label">Saved fit</span>
                                <span class="saved-fit-date"><?= htmlspecialchars(date('M d, Y', strtotime($outfit['created_at']))) ?></span>
                            </div>
                            <h4><?= htmlspecialchars($outfit['name']) ?></h4>
                            <p class="saved-fit-subtitle mb-3"><?= htmlspecialchars($outfit['occasion'] ?: 'Open styling') ?><?= !empty($outfit['season']) ? ' / ' . htmlspecialchars($outfit['season']) : '' ?></p>
                            <div class="saved-fit-meta-row">
                                <span><?= (int) ($outfit['item_total'] ?? 0) ?> pieces</span>
                                <span><?= htmlspecialchars($outfit['season'] ?: 'Flexible season') ?></span>
                            </div>
                            <div class="saved-fit-actions">
                                <a href="edit_outfit.php?id=<?= (int) $outfit['id'] ?>" class="saved-fit-action saved-fit-action-edit">Edit Fit</a>
                                <a href="../actions/delete_outfit.php?id=<?= (int) $outfit['id'] ?>" class="saved-fit-action saved-fit-action-delete" data-confirm-delete data-confirm-message="<?= htmlspecialchars("Delete the saved fit \"" . $outfit['name'] . "\"? This action cannot be undone.", ENT_QUOTES) ?>">Delete Fit</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>






