<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
$publicPrefix = 'uploads/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'mov', 'webm'];
$media = [];

if(is_dir($uploadDir)){
    foreach(scandir($uploadDir) as $filename){
        if($filename === '.' || $filename === '..') continue;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(!in_array($extension, $allowedExtensions, true)) continue;
        $media[] = [
            'name' => $filename,
            'url' => $publicPrefix . rawurlencode($filename),
            'type' => in_array($extension, ['mp4', 'mov', 'webm'], true) ? 'video' : 'image',
            'modified' => filemtime($uploadDir . DIRECTORY_SEPARATOR . $filename)
        ];
    }
}

usort($media, function($first, $second){
    return $second['modified'] <=> $first['modified'];
});

echo json_encode(['media' => $media]);
