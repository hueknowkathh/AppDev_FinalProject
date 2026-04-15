<?php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/wardrobe.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$color = trim($_POST['color'] ?? '');
$season = trim($_POST['season'] ?? '');
$occasion = trim($_POST['occasion'] ?? '');
$brand = trim($_POST['brand'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$favorite = isset($_POST['favorite']) ? 1 : 0;
$existingImagePath = trim($_POST['existing_image_path'] ?? '');
$imagePath = $existingImagePath;

if ($id <= 0 || $name === '' || $category === '' || $season === '' || $occasion === '') {
    $_SESSION['flash_message'] = 'Please complete all required fields.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php');
    exit;
}

if (!empty($_FILES['image']['name']) && (int) $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = realpath(__DIR__ . '/../assets/uploads');
    if ($uploadDir !== false) {
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($extension, $allowed, true)) {
            $filename = uniqid('cloth_', true) . '.' . $extension;
            $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $absolutePath)) {
                $imagePath = 'assets/uploads/' . $filename;
            }
        }
    }
}

$stmt = $conn->prepare('
    UPDATE clothes
    SET name = ?, image_path = ?, category = ?, color = ?, season = ?, occasion = ?, brand = ?, notes = ?, favorite = ?
    WHERE id = ?
');
$stmt->bind_param('ssssssssii', $name, $imagePath, $category, $color, $season, $occasion, $brand, $notes, $favorite, $id);
$stmt->execute();

$_SESSION['flash_message'] = 'Clothing item updated successfully.';
$_SESSION['flash_type'] = 'success';
header('Location: ../pages/wardrobe.php');
exit;
