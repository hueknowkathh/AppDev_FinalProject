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

$select = $conn->prepare('SELECT name, favorite FROM clothes WHERE id = ? LIMIT 1');
$select->bind_param('i', $id);
$select->execute();
$item = $select->get_result()->fetch_assoc();

if (!$item) {
    $_SESSION['flash_message'] = 'Clothing item not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

$nextFavorite = (int) ($item['favorite'] ?? 0) === 1 ? 0 : 1;

$update = $conn->prepare('UPDATE clothes SET favorite = ? WHERE id = ?');
$update->bind_param('ii', $nextFavorite, $id);

if ($update->execute()) {
    $_SESSION['flash_message'] = $nextFavorite === 1
        ? '"' . $item['name'] . '" added to favorites.'
        : '"' . $item['name'] . '" removed from favorites.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Unable to update favorite status.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ../pages/wardrobe.php');
exit;
