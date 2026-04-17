<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

function decodeJsonPayload(string $raw): ?array
{
    $trimmed = trim($raw);
    if ($trimmed === '') {
        return null;
    }

    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($trimmed, '{');
    $end = strrpos($trimmed, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    $candidate = substr($trimmed, $start, ($end - $start) + 1);
    $decoded = json_decode($candidate, true);
    return is_array($decoded) ? $decoded : null;
}

function determineAutoOutfitCount(array $wardrobe, array $fallbackWardrobe, string $occasion): int
{
    $requiredCategories = array_map('strtolower', match (strtolower($occasion)) {
        'business', 'travel' => ['Top', 'Bottom', 'Outerwear', 'Shoes'],
        'party' => ['Dress', 'Shoes', 'Accessory'],
        'formal', 'sportswear', 'casual' => ['Top', 'Bottom', 'Shoes'],
        default => ['Top', 'Bottom', 'Shoes'],
    });

    $availableRequired = [];
    foreach ($wardrobe as $item) {
        $category = strtolower((string) ($item['category'] ?? ''));
        if (in_array($category, $requiredCategories, true)) {
            $availableRequired[$category] = ($availableRequired[$category] ?? 0) + 1;
        }
    }

    if (count($availableRequired) === count($requiredCategories)) {
        $wardrobeCount = count($wardrobe);
        if ($wardrobeCount >= 12) {
            return 5;
        }
        if ($wardrobeCount >= 8) {
            return 4;
        }
        return 3;
    }

    return count($fallbackWardrobe) >= 3 ? 3 : max(1, count($fallbackWardrobe));
}

function wardrobeRecommendationPrompt(
    array $wardrobe,
    string $occasion,
    string $season,
    string $preferredStyle,
    string $color,
    int $outfitCount,
    int $filteredCount,
    int $fullWardrobeCount
): string
{
    $wardrobeRows = [];
    foreach ($wardrobe as $item) {
        $wardrobeRows[] = [
            'id' => (int) ($item['id'] ?? 0),
            'name' => (string) ($item['name'] ?? ''),
            'category' => (string) ($item['category'] ?? ''),
            'color' => (string) ($item['color'] ?? ''),
            'season' => (string) ($item['season'] ?? ''),
            'occasion' => (string) ($item['occasion'] ?? ''),
            'favorite' => (int) ($item['favorite'] ?? 0),
            'wear_count' => (int) ($item['wear_count'] ?? 0),
            'notes' => (string) ($item['notes'] ?? ''),
        ];
    }

    $request = [
        'occasion' => $occasion,
        'season' => $season,
        'preferred_style' => $preferredStyle,
        'preferred_color' => $color,
        'outfit_count' => $outfitCount,
        'filtered_match_count' => $filteredCount,
        'full_wardrobe_count' => $fullWardrobeCount,
    ];

    return implode("\n\n", [
        'You are a luxury fashion-tech stylist for Closet Couture.',
        'Generate outfit recommendations from the wardrobe data below.',
        'Return only valid JSON with this exact shape:',
        '{"message":"string","access":"string","outfits":[{"title":"string","summary":"string","wear_guide":["string"],"mode":"items|description","items":[{"id":0,"name":"string","category":"string","color":"string","occasion":"string","wear_count":0}],"reasons":["string"]}]}',
        'Rules:',
        '- Always create exactly the requested number of recommendations unless the wardrobe is completely empty.',
        '- If the wardrobe has enough compatible pieces, use mode "items" and include real wardrobe items from the list.',
        '- If the filtered wardrobe does not have enough compatible pieces, use mode "description" and leave items as an empty array while giving a distinct fit concept grounded in the broader wardrobe context.',
        '- In description mode, you may suggest complementary pieces outside the wardrobe only when needed to complete the outfit, but you must clearly anchor the recommendation to the saved wardrobe pieces first.',
        '- In description mode, still make the recommendations specific, varied, and accurate based on available categories, colors, occasions, and wardrobe patterns.',
        '- Recommendations must be clearly different from each other in styling direction, silhouette, or color approach.',
        '- Never present invented clothing items as if they already exist in the wardrobe list.',
        '- Every outfit must include a wear_guide array explaining what kind of clothes to wear in short stylist instructions.',
        '- Keep reasons concise and helpful.',
        '- The "message" should summarize what was generated for the user.',
        '- The "access" should be a short user-facing note about how the result was generated.',
        '- If filtered_match_count is 0 but full_wardrobe_count is greater than 0, produce text-based recommendations using the full wardrobe as inspiration.',
        'User request:',
        json_encode($request, JSON_PRETTY_PRINT),
        'Wardrobe:',
        json_encode($wardrobeRows, JSON_PRETTY_PRINT),
    ]);
}

function callOpenAIRecommendations(string $apiKey, array $payload): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL is not available for OpenAI requests.', 'debug' => 'curl extension missing'];
    }

    $model = appOpenAIModel();
    $requestBody = [
        'model' => $model,
        'reasoning' => ['effort' => 'low'],
        'input' => [
            [
                'role' => 'developer',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'You are a wardrobe recommendation engine that must return valid JSON only.',
                    ],
                ],
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => wardrobeRecommendationPrompt(
                            $payload['wardrobe_for_ai'] ?? [],
                            (string) ($payload['occasion'] ?? ''),
                            (string) ($payload['season'] ?? ''),
                            (string) ($payload['preferred_style'] ?? ''),
                            (string) ($payload['color'] ?? ''),
                            (int) ($payload['outfit_count'] ?? 3),
                            (int) ($payload['filtered_count'] ?? 0),
                            (int) ($payload['full_wardrobe_count'] ?? 0)
                        ),
                    ],
                ],
            ],
        ],
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $response === '' || $curlError !== '') {
        return ['success' => false, 'message' => 'OpenAI request failed.', 'debug' => $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['success' => false, 'message' => 'OpenAI response was not valid JSON.', 'debug' => $response];
    }

    if ($httpCode >= 400) {
        $errorMessage = $decoded['error']['message'] ?? 'OpenAI returned an error.';
        return ['success' => false, 'message' => $errorMessage, 'debug' => $response];
    }

    $outputText = trim((string) ($decoded['output_text'] ?? ''));
    if ($outputText === '') {
        return ['success' => false, 'message' => 'OpenAI returned an empty response.', 'debug' => $response];
    }

    $parsed = decodeJsonPayload($outputText);
    if (!is_array($parsed) || !isset($parsed['outfits'])) {
        return ['success' => false, 'message' => 'OpenAI returned non-JSON recommendation content.', 'debug' => $outputText];
    }

    return ['success' => true, 'parsed' => $parsed];
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

$occasion = trim($_POST['occasion'] ?? '');
$season = trim($_POST['season'] ?? '');
$preferredStyle = trim($_POST['preferred_style'] ?? '');
$color = trim($_POST['color'] ?? '');
if ($occasion === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Occasion is required.']);
    exit;
}

$sql = 'SELECT * FROM clothes WHERE occasion IN (?, "Casual")';
$params = [$occasion];
$types = 's';

if ($season !== '') {
    $sql .= ' AND (season = ? OR season = "All Season")';
    $params[] = $season;
    $types .= 's';
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$wardrobe = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$fallbackWardrobe = $conn->query('SELECT * FROM clothes ORDER BY favorite DESC, wear_count ASC, created_at DESC')->fetch_all(MYSQLI_ASSOC);
$outfitCount = determineAutoOutfitCount($wardrobe, $fallbackWardrobe, $occasion);

$payload = [
    'occasion' => $occasion,
    'season' => $season,
    'preferred_style' => $preferredStyle,
    'color' => $color,
    'outfit_count' => $outfitCount,
    'filtered_count' => count($wardrobe),
    'full_wardrobe_count' => count($fallbackWardrobe),
    'wardrobe' => $wardrobe,
    'fallback_wardrobe' => $fallbackWardrobe,
    'wardrobe_for_ai' => $wardrobe !== [] ? $wardrobe : $fallbackWardrobe,
];

$openAiApiKey = trim((string) getenv('OPENAI_API_KEY'));
if ($openAiApiKey !== '') {
    $aiResult = callOpenAIRecommendations($openAiApiKey, $payload);
    if ($aiResult['success']) {
        $aiResult['parsed']['engine'] = 'openai';
        echo json_encode(['success' => true, 'result' => $aiResult['parsed']]);
        exit;
    }

    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'OpenAI styling is configured but the recommendation request failed.',
        'debug' => $aiResult['message'] ?? 'Unknown OpenAI error.',
        'engine' => 'openai',
    ]);
    exit;
}

$tempFile = tempnam(sys_get_temp_dir(), 'couture_reco_');
file_put_contents($tempFile, json_encode($payload, JSON_PRETTY_PRINT));

$script = realpath(__DIR__ . '/../ai/recommender.py');
$result = runPythonScript($script, [$tempFile]);
@unlink($tempFile);

if (!$result['success'] || $result['output'] === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Python recommender did not return a response.', 'debug' => $result['message']]);
    exit;
}

$parsed = decodeJsonPayload($result['output']);
if (!is_array($parsed)) {
    error_log('Closet Couture recommendation invalid output: ' . $result['output']);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid recommender output.', 'raw' => $result['output']]);
    exit;
}

if (!empty($parsed['message']) && isset($parsed['outfits'])) {
    $parsed['engine'] = 'ml_fallback';
    echo json_encode(['success' => true, 'result' => $parsed]);
    exit;
}

http_response_code(500);
echo json_encode(['success' => false, 'message' => 'Unexpected recommender payload.', 'raw' => $result['output']]);
