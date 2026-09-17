<?php
header('Content-Type: application/json; charset=utf-8');

/* ---------- Config ---------- */
$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$config     = file_exists($configFile) ? require $configFile : [];
$cloud      = $config['cloudinary'] ?? [];

$cloudName = $cloud['cloud_name'] ?? '';
$apiKey    = $cloud['api_key']    ?? '';
$apiSecret = $cloud['api_secret'] ?? '';
$folder    = $cloud['folder']     ?? 'mariage';

/* ---------- Cloudinary non configuré → fallback liste vide ---------- */
if($cloudName === '' || strpos($cloudName, 'COLLER_') === 0){
    echo json_encode(['media' => [], 'error' => 'Cloudinary non configuré.']);
    exit;
}

/* ---------- Signature pour l'Admin API ---------- */
$timestamp    = time();
$paramsToSign = ['max_results' => 500, 'prefix' => $folder, 'timestamp' => $timestamp, 'type' => 'upload'];
ksort($paramsToSign);
$signatureStr = '';
foreach($paramsToSign as $k => $v){
    $signatureStr .= "{$k}={$v}&";
}
$signatureStr = rtrim($signatureStr, '&');
$signature    = sha1($signatureStr . $apiSecret);

/* ---------- Appel Admin API Cloudinary ---------- */
$queryStr = http_build_query([
    'prefix'      => $folder,
    'max_results' => 500,
    'type'        => 'upload',
    'timestamp'   => $timestamp,
    'api_key'     => $apiKey,
    'signature'   => $signature,
]);
$adminUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/resources/image?{$queryStr}";

$ch = curl_init($adminUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$responseImg  = curl_exec($ch);
$codeImg      = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* Idem pour les vidéos */
$adminUrlVideo = "https://api.cloudinary.com/v1_1/{$cloudName}/resources/video?{$queryStr}";
$ch = curl_init($adminUrlVideo);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$responseVid  = curl_exec($ch);
curl_close($ch);

/* ---------- Fusion & formatage ---------- */
$media = [];

$parseResources = function($json, $type) use (&$media, $cloudName){
    $data = json_decode($json, true);
    if(!isset($data['resources'])) return;
    foreach($data['resources'] as $r){
        $media[] = [
            'name'     => basename($r['public_id']),
            'url'      => $r['secure_url'],
            'type'     => $type,
            'modified' => strtotime($r['created_at'] ?? 'now'),
        ];
    }
};

$parseResources($responseImg,  'image');
$parseResources($responseVid,  'video');

/* Tri du plus récent au plus ancien */
usort($media, fn($a, $b) => $b['modified'] <=> $a['modified']);

echo json_encode(['media' => $media], JSON_UNESCAPED_UNICODE);
