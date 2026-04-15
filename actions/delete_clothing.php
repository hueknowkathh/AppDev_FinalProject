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

$stmt = $conn->prepare('SELECT image_path FROM clothes WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

$deleteStmt = $conn->prepare('DELETE FROM clothes WHERE id = ?');
$deleteStmt->bind_param('i', $id);
$deleteStmt->execute();

if (!empty($item['image_path'])) {
    $filePath = realpath(__DIR__ . '/../' . $item['image_path']);
    $uploadsRoot = realpath(__DIR__ . '/../assets/uploads');
    if ($filePath && $uploadsRoot && str_starts_with($filePath, $uploadsRoot) && is_file($filePath)) {
        unlink($filePath);
    }
}

$_SESSION['flash_message'] = 'Clothing item deleted.';
$_SESSION['flash_type'] = 'success';
header('Location: ../pages/wardrobe.php');
exit;
