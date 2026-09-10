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

/* ---------- Helpers ---------- */
function respond($payload, $status = 200){
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- Validation de base ---------- */
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    respond(['error' => 'Méthode non autorisée.'], 405);
}
if($cloudName === '' || strpos($cloudName, 'COLLER_') === 0){
    respond(['error' => 'Cloudinary non configuré — renseigne config.php.'], 503);
}
if(!isset($_FILES['media'])){
    respond(['error' => 'Aucun fichier reçu.'], 400);
}

/* ---------- Types autorisés ---------- */
$allowedMimes = [
    'image/jpeg'  => 'image',
    'image/png'   => 'image',
    'image/webp'  => 'image',
    'image/gif'   => 'image',
    'video/mp4'   => 'video',
    'video/quicktime' => 'video',
    'video/webm'  => 'video',
];
$maxFileSize = 50 * 1024 * 1024; // 50 Mo

/* ---------- Upload vers Cloudinary ---------- */
$uploaded = 0;
$errors   = [];
$finfo    = new finfo(FILEINFO_MIME_TYPE);

$files = $_FILES['media'];
// Normaliser en tableau si un seul fichier
$count = is_array($files['name']) ? count($files['name']) : 1;
if(!is_array($files['name'])){
    foreach(['name','type','tmp_name','error','size'] as $k){
        $files[$k] = [$files[$k]];
    }
}

$uploadUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload";

foreach($files['tmp_name'] as $index => $tmpName){
    $error = $files['error'][$index];
    $size  = (int) $files['size'][$index];

    if($error !== UPLOAD_ERR_OK){
        $errors[] = 'Un fichier n\'a pas pu être reçu.';
        continue;
    }
    if($size > $maxFileSize){
        $errors[] = 'Un fichier dépasse la limite de 50 Mo.';
        continue;
    }
    $mime = $finfo->file($tmpName);
    if(!isset($allowedMimes[$mime])){
        $errors[] = 'Un format de fichier n\'est pas autorisé.';
        continue;
    }

    /* ---- Signature Cloudinary ---- */
    $timestamp  = time();
    $publicId   = $folder . '/' . bin2hex(random_bytes(10));
    $paramsToSign = [
        'folder'    => $folder,
        'public_id' => $publicId,
        'timestamp' => $timestamp,
    ];
    ksort($paramsToSign);
    $signatureStr = '';
    foreach($paramsToSign as $k => $v){
        $signatureStr .= "{$k}={$v}&";
    }
    $signatureStr = rtrim($signatureStr, '&');
    $signature = sha1($signatureStr . $apiSecret);

    /* ---- Envoi via cURL ---- */
    $postData = [
        'file'      => new CURLFile($tmpName, $mime, $files['name'][$index]),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => $folder,
        'public_id' => $publicId,
    ];

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpCode >= 200 && $httpCode < 300){
        $uploaded++;
    } else {
        $decoded = json_decode($response, true);
        $errors[] = $decoded['error']['message'] ?? 'Erreur Cloudinary lors de l\'envoi.';
    }
}

if($uploaded === 0){
    respond(['error' => $errors[0] ?? 'Aucun fichier n\'a été ajouté.'], 400);
}

respond(['uploaded' => $uploaded, 'errors' => $errors]);
