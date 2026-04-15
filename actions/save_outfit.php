<?php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/wardrobe.php');
    exit;
}

$outfitName = trim($_POST['outfit_name'] ?? '');
$occasion = trim($_POST['occasion'] ?? '');
$season = trim($_POST['season'] ?? '');
$selectedItems = array_values(array_unique(array_map('intval', $_POST['selected_items'] ?? [])));
$selectedItems = array_filter($selectedItems, static fn ($id) => $id > 0);

if ($outfitName === '' || count($selectedItems) < 2) {
    $_SESSION['flash_message'] = 'Select at least 2 clothing items and give the fit a name.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

$conn->begin_transaction();

try {
    $outfitStmt = $conn->prepare('INSERT INTO outfits (name, occasion, season) VALUES (?, ?, ?)');
    $outfitStmt->bind_param('sss', $outfitName, $occasion, $season);
    $outfitStmt->execute();
    $outfitId = (int) $conn->insert_id;

    $itemStmt = $conn->prepare('INSERT INTO outfit_items (outfit_id, clothing_id) VALUES (?, ?)');
    foreach ($selectedItems as $clothingId) {
        $itemStmt->bind_param('ii', $outfitId, $clothingId);
        $itemStmt->execute();
    }

    $conn->commit();
    $_SESSION['flash_message'] = 'Fit saved successfully to your outfit combinations.';
    $_SESSION['flash_type'] = 'success';
} catch (Throwable $exception) {
    $conn->rollback();
    $_SESSION['flash_message'] = 'Unable to save the selected fit right now.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ../pages/wardrobe.php');
exit;
