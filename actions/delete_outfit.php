<?php
session_start();
require __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_message'] = 'Invalid fit selected.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

$stmt = $conn->prepare('DELETE FROM outfits WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

$_SESSION['flash_message'] = 'Fit deleted successfully.';
$_SESSION['flash_type'] = 'success';
header('Location: ../pages/wardrobe.php');
exit;
