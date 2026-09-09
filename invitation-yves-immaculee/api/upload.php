<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
$maxFileSize = 50 * 1024 * 1024;
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'video/mp4' => 'mp4',
    'video/quicktime' => 'mov',
    'video/webm' => 'webm'
];

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['error' => 'Methode non autorisee.']);
    exit;
}

if(!isset($_FILES['media'])){
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier recu.']);
    exit;
}

if(!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)){
    http_response_code(500);
    echo json_encode(['error' => 'Le dossier de stockage est indisponible.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$uploaded = 0;
$errors = [];

foreach($_FILES['media']['tmp_name'] as $index => $tmpName){
    $error = $_FILES['media']['error'][$index];
    $size = (int) $_FILES['media']['size'][$index];
    if($error !== UPLOAD_ERR_OK){
        $errors[] = 'Un fichier n’a pas pu etre recu.';
        continue;
    }
    if($size > $maxFileSize){
        $errors[] = 'Un fichier depasse la limite de 50 Mo.';
        continue;
    }
    $mime = $finfo->file($tmpName);
    if(!isset($allowedTypes[$mime])){
        $errors[] = 'Un format de fichier n’est pas autorise.';
        continue;
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mime];
    if(move_uploaded_file($tmpName, $uploadDir . DIRECTORY_SEPARATOR . $filename)){
        $uploaded++;
    }else{
        $errors[] = 'Un fichier n’a pas pu etre enregistre.';
    }
}

if($uploaded === 0){
    http_response_code(400);
    echo json_encode(['error' => $errors[0] ?? 'Aucun fichier n’a ete ajoute.']);
    exit;
}

echo json_encode(['uploaded' => $uploaded, 'errors' => $errors]);
