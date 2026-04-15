<?php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/wardrobe.php');
    exit;
}

$outfitId = (int) ($_POST['outfit_id'] ?? 0);
$outfitName = trim($_POST['outfit_name'] ?? '');
$occasion = trim($_POST['occasion'] ?? '');
$season = trim($_POST['season'] ?? '');
$selectedItems = array_values(array_unique(array_map('intval', $_POST['selected_items'] ?? [])));
$selectedItems = array_filter($selectedItems, static fn ($id) => $id > 0);

if ($outfitId <= 0 || $outfitName === '' || count($selectedItems) < 2) {
    $_SESSION['flash_message'] = 'Give the fit a name and select at least 2 items.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

$conn->begin_transaction();

try {
    $updateStmt = $conn->prepare('UPDATE outfits SET name = ?, occasion = ?, season = ? WHERE id = ?');
    $updateStmt->bind_param('sssi', $outfitName, $occasion, $season, $outfitId);
    $updateStmt->execute();

    $deleteItemsStmt = $conn->prepare('DELETE FROM outfit_items WHERE outfit_id = ?');
    $deleteItemsStmt->bind_param('i', $outfitId);
    $deleteItemsStmt->execute();

    $insertStmt = $conn->prepare('INSERT INTO outfit_items (outfit_id, clothing_id) VALUES (?, ?)');
    foreach ($selectedItems as $clothingId) {
        $insertStmt->bind_param('ii', $outfitId, $clothingId);
        $insertStmt->execute();
    }

    $conn->commit();
    $_SESSION['flash_message'] = 'Fit updated successfully.';
    $_SESSION['flash_type'] = 'success';
} catch (Throwable $exception) {
    $conn->rollback();
    $_SESSION['flash_message'] = 'Unable to update the fit right now.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ../pages/wardrobe.php');
exit;
