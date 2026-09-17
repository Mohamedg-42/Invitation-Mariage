<?php
header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$config = file_exists($configFile) ? require $configFile : [];
$whatsapp = $config['whatsapp'] ?? [];
$dataFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'rsvps.json';

function respond($payload, $status = 200){
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['error' => 'Methode non autorisee.'], 405);

$input = json_decode(file_get_contents('php://input'), true);
$name = trim((string) ($input['name'] ?? ''));
$presence = $input['presence'] ?? '';
$guests = filter_var($input['guests'] ?? null, FILTER_VALIDATE_INT);

if($name === '' || mb_strlen($name) > 120) respond(['error' => 'Nom invalide.'], 400);
if(!in_array($presence, ['oui', 'non'], true)) respond(['error' => 'Presence invalide.'], 400);
if($guests === false || $guests < 1 || $guests > 30) respond(['error' => 'Nombre de personnes invalide.'], 400);

$directory = dirname($dataFile);
if(!is_dir($directory) && !mkdir($directory, 0755, true)) respond(['error' => 'Stockage indisponible.'], 500);
$list = [];
if(file_exists($dataFile)){
    $stored = json_decode(file_get_contents($dataFile), true);
    if(is_array($stored)) $list = $stored;
}

$entry = [
    'id' => bin2hex(random_bytes(12)),
    'name' => $name,
    'presence' => $presence,
    'guests' => $guests,
    'date' => date('d/m/Y H:i')
];
$list[] = $entry;
if(file_put_contents($dataFile, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false){
    respond(['error' => 'Impossible d’enregistrer la réponse.'], 500);
}

$notification = 'non_configuree';
$token = $whatsapp['access_token'] ?? '';
$phoneNumberId = $whatsapp['phone_number_id'] ?? '';
$recipient = $whatsapp['recipient'] ?? '';
$templateName = $whatsapp['template_name'] ?? '';
$language = $whatsapp['template_language'] ?? 'fr';
$graphVersion = $whatsapp['graph_version'] ?? 'v23.0';

if($token !== '' && strpos($token, 'COLLER_') !== 0 && $phoneNumberId !== '' && $recipient !== '' && $templateName !== ''){
    $templatePayload = [
        'messaging_product' => 'whatsapp',
        'to' => $recipient,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => ['code' => $language],
            'components' => [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $name],
                    ['type' => 'text', 'text' => $presence === 'oui' ? 'Oui' : 'Non'],
                    ['type' => 'text', 'text' => (string) $guests],
                    ['type' => 'text', 'text' => $entry['date']]
                ]
            ]]
        ]
    ];
    $ch = curl_init('https://graph.facebook.com/' . rawurlencode($graphVersion) . '/' . rawurlencode($phoneNumberId) . '/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($templatePayload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $notification = ($httpCode >= 200 && $httpCode < 300) ? 'envoyee' : 'echec';
}

respond(['success' => true, 'entry' => $entry, 'notification' => $notification]);
