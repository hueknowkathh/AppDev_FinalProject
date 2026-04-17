<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Closet Couture';
$isPageDir = str_contains($_SERVER['PHP_SELF'], '/pages/');
$assetPrefix = $isPageDir ? '../' : '';
$cssRelativePath = 'assets/css/style.css';
$cssVersionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cssRelativePath);
$cssVersion = is_file($cssVersionPath) ? filemtime($cssVersionPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Closet Couture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($assetPrefix . $cssRelativePath . '?v=' . $cssVersion) ?>" rel="stylesheet">
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/navbar.php'; ?>
        <main class="container py-4 py-lg-5">
            <?php if (!empty($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'success') ?> shadow-sm border-0">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
