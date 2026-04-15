<?php
session_start();
require __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_message'] = 'Invalid clothing item.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

$conn->begin_transaction();

try {
    $update = $conn->prepare('UPDATE clothes SET wear_count = wear_count + 1, last_worn = NOW() WHERE id = ?');
    $update->bind_param('i', $id);
    $update->execute();

    $history = $conn->prepare('INSERT INTO wear_history (clothing_id, worn_at) VALUES (?, NOW())');
    $history->bind_param('i', $id);
    $history->execute();

    $conn->commit();
    $_SESSION['flash_message'] = 'Item marked as worn.';
    $_SESSION['flash_type'] = 'success';
} catch (Throwable $exception) {
    $conn->rollback();
    $_SESSION['flash_message'] = 'Unable to update wear history.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ../pages/wardrobe.php');
exit;
