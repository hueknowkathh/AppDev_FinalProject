<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/app.php';

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
        return null;
    }

    $cutoutDir = __DIR__ . '/../assets/uploads/cutouts';
    if (!is_dir($cutoutDir) && !mkdir($cutoutDir, 0777, true) && !is_dir($cutoutDir)) {
        return null;
    }

    $cutoutDir = realpath($cutoutDir);
    if ($cutoutDir === false) {
        return null;
    }

    $baseName = pathinfo($imageFullPath, PATHINFO_FILENAME);
    $baseName = preg_replace('/_cutout$/', '', $baseName);
    $cutoutAbsolute = $cutoutDir . DIRECTORY_SEPARATOR . $baseName . '_cutout.png';
    $result = runPythonScript($script, [$imageFullPath, $cutoutAbsolute]);

    if (!$result['success'] || $result['output'] === '') {
        error_log('Closet Couture reprocess background removal failed: ' . $result['message']);
        return null;
    }

    $data = json_decode($result['output'], true);
    if (!is_array($data) || empty($data['success']) || !file_exists($cutoutAbsolute)) {
        error_log('Closet Couture reprocess invalid background removal output: ' . $result['output']);
        return null;
    }

    return [
        'absolute' => $cutoutAbsolute,
        'relative' => 'assets/uploads/cutouts/' . basename($cutoutAbsolute),
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
        error_log('Closet Couture reprocess color detection failed: ' . $result['message']);
        return null;
    }

    $data = json_decode($result['output'], true);
    if (!is_array($data)) {
        error_log('Closet Couture reprocess color detection invalid output: ' . $result['output']);
        return null;
    }

    return $data['dominant_color'] ?? null;
}

$rows = $conn->query("SELECT id, image_path FROM clothes WHERE image_path IS NOT NULL AND image_path <> ''")->fetch_all(MYSQLI_ASSOC);
$processed = 0;
$failed = 0;

foreach ($rows as $row) {
    $sourcePath = realpath(__DIR__ . '/../' . $row['image_path']);
    if (!$sourcePath || !is_file($sourcePath)) {
        $failed++;
        continue;
    }

    $cutout = removeBackground($sourcePath);
    if (!$cutout) {
        $failed++;
        continue;
    }

    $detectedColor = detectColor($cutout['absolute']) ?? null;
    $stmt = $conn->prepare('UPDATE clothes SET image_path = ?, color = COALESCE(?, color) WHERE id = ?');
    $stmt->bind_param('ssi', $cutout['relative'], $detectedColor, $row['id']);
    $stmt->execute();
    $processed++;
}

$_SESSION['flash_message'] = "Reprocessed {$processed} image(s)" . ($failed ? " and skipped {$failed}." : '.');
$_SESSION['flash_type'] = $failed ? 'warning' : 'success';
header('Location: ../pages/wardrobe.php');
exit;
