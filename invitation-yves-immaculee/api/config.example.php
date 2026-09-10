<?php
// Copie ce fichier sous config.php puis renseigne les valeurs.
return [
    'whatsapp' => [
        'access_token'      => 'COLLER_TON_ACCESS_TOKEN_META',
        'phone_number_id'   => 'COLLER_TON_PHONE_NUMBER_ID',
        'recipient'         => 'COLLER_LE_NUMERO_DESTINATAIRE',  // ex: 2250101643606
        'graph_version'     => 'v23.0',
        'template_name'     => 'rsvp_mariage',
        'template_language' => 'fr'
    ],
    'cloudinary' => [
        // Retrouve ces valeurs sur https://console.cloudinary.com → Dashboard
        'cloud_name'    => 'COLLER_TON_CLOUD_NAME',
        'api_key'       => 'COLLER_TON_API_KEY',
        'api_secret'    => 'COLLER_TON_API_SECRET',
        // Dossier où seront stockés les médias dans ton Cloud Cloudinary
        'folder'        => 'mariage-yves-immaculee'
    ]
];
