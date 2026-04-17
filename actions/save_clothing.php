<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/wardrobe.php');
    exit;
}

function runPythonScript(string $scriptPath, array $arguments = []): array
{
    $python = findPythonExecutable();
    if ($python === null || !file_exists($scriptPath)) {
        return ['success' => false, 'output' => '', 'message' => 'Python executable or script not found.'];
    }

    $parts = [str_contains($python, ' ') && !str_ends_with(strtolower($python), '.exe') ? $python : '"' . $python . '"', escapeshellarg($scriptPath)];
    foreach ($arguments as $argument) {
        $parts[] = escapeshellarg($argument);
    }

    $command = implode(' ', $parts) . ' 2>&1';
    $output = shell_exec($command);

    if ($output === null) {
        return ['success' => false, 'output' => '', 'message' => 'shell_exec returned null.'];
    }

    return ['success' => true, 'output' => trim($output), 'message' => ''];
}

function removeBackground(string $imageFullPath): ?array
{
    $script = realpath(__DIR__ . '/../ai/background_remover.py');
    if (!$script || !file_exists($imageFullPath)) {
        error_log('Closet Couture background removal skipped: script or input image missing.');
        return null;
    }

    $cutoutDir = __DIR__ . '/../assets/uploads/cutouts';
    if (!is_dir($cutoutDir) && !mkdir($cutoutDir, 0777, true) && !is_dir($cutoutDir)) {
        error_log('Closet Couture background removal failed: could not create cutout directory.');
        return null;
    }

    $cutoutDir = realpath($cutoutDir);
    if ($cutoutDir === false) {
        error_log('Closet Couture background removal failed: cutout directory path unavailable.');
        return null;
    }

    $baseName = pathinfo($imageFullPath, PATHINFO_FILENAME);
    $cutoutAbsolute = $cutoutDir . DIRECTORY_SEPARATOR . $baseName . '_cutout.png';
    $result = runPythonScript($script, [$imageFullPath, $cutoutAbsolute]);

    if (!$result['success'] || $result['output'] === '') {
        error_log('Closet Couture background removal failed: ' . $result['message']);
        return null;
    }

    $data = json_decode($result['output'], true);
    if (!is_array($data) || empty($data['success'])) {
        error_log('Closet Couture background removal invalid output: ' . $result['output']);
        return null;
    }

    if (!file_exists($cutoutAbsolute) || filesize($cutoutAbsolute) === 0) {
        error_log('Closet Couture background removal reported success but no cutout file was written.');
        return null;
    }

    return [
        'absolute' => $cutoutAbsolute,
        'relative' => 'assets/uploads/cutouts/' . $baseName . '_cutout.png',
    ];
}

function detectColor(string $imageFullPath): ?string
{
    $script = realpath(__DIR__ . '/../ai/color_detector.py');
    if (!$script || !file_exists($imageFullPath)) {
        return null;
    }

    $result = runPythonScript($script, [$imageFullPath]);
    if (!$result['success'] || $result['output'] === '') {
        error_log('Closet Couture color detection failed: ' . $result['message']);
        return null;
    }

    $data = json_decode($result['output'], true);
    if (!is_array($data)) {
        error_log('Closet Couture color detection invalid JSON: ' . $result['output']);
        return null;
    }

    if (!empty($data['message'])) {
        error_log('Closet Couture color detection message: ' . $data['message']);
    }

    return $data['dominant_color'] ?? null;
}

function uploadErrorMessage(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded image is too large.',
        UPLOAD_ERR_PARTIAL => 'The image upload was incomplete. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server upload temp folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
        UPLOAD_ERR_EXTENSION => 'The upload was blocked by a server extension.',
        default => 'Image upload failed.',
    };
}

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$color = trim($_POST['color'] ?? '');
$season = trim($_POST['season'] ?? '');
$occasion = trim($_POST['occasion'] ?? '');
$brand = trim($_POST['brand'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$favorite = isset($_POST['favorite']) ? 1 : 0;

if ($name === '' || $category === '' || $season === '' || $occasion === '') {
    $_SESSION['flash_message'] = 'Please complete all required fields.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php?open_add=1');
    exit;
}

if (empty($_FILES['image']['name'])) {
    $_SESSION['flash_message'] = 'Please upload an image for the item.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php?open_add=1');
    exit;
}

$relativePath = null;
$absolutePath = null;
$originalAbsolutePath = null;

if (!empty($_FILES['image']['name']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_message'] = uploadErrorMessage((int) $_FILES['image']['error']);
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php?open_add=1');
    exit;
}

if (!empty($_FILES['image']['name']) && (int) $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = realpath(__DIR__ . '/../assets/uploads');
    if ($uploadDir !== false) {
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (!in_array($extension, $allowed, true)) {
            $_SESSION['flash_message'] = 'Unsupported image format.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ../pages/wardrobe.php?open_add=1');
            exit;
        }

        $filename = uniqid('cloth_', true) . '.' . $extension;
        $originalAbsolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $absolutePath = $originalAbsolutePath;
        $relativePath = 'assets/uploads/' . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $absolutePath)) {
            $_SESSION['flash_message'] = 'Image upload failed.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ../pages/wardrobe.php?open_add=1');
            exit;
        }

        $cutout = removeBackground($absolutePath);
        if ($cutout) {
            $absolutePath = $cutout['absolute'];
            $relativePath = $cutout['relative'];
        } else {
            error_log('Closet Couture: using original uploaded image because cutout generation did not complete.');
        }
    }
}

if (!empty($_FILES['image']['name']) && $relativePath === null) {
    $_SESSION['flash_message'] = 'Image upload failed.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../pages/wardrobe.php?open_add=1');
    exit;
}

if ($color === '' && $absolutePath) {
    $color = detectColor($absolutePath) ?? '';
}

$stmt = $conn->prepare('
    INSERT INTO clothes (name, image_path, category, color, season, occasion, brand, notes, favorite, wear_count, last_worn)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)
');
$stmt->bind_param('ssssssssi', $name, $relativePath, $category, $color, $season, $occasion, $brand, $notes, $favorite);
$stmt->execute();

$_SESSION['flash_message'] = 'Clothing item added successfully.';
$_SESSION['flash_type'] = 'success';
header('Location: ../pages/wardrobe.php');
exit;
