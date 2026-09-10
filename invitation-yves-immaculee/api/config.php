<?php
// Copie ce fichier sous config.php puis renseigne les valeurs.
return [
    'whatsapp' => [
        'access_token'     => '3241914839331111|pwjKmOi_g_rNMC1w6JIoSRPsP4A',
        'phone_number_id'  => '1326590693871816',
        'recipient'        => '2250101643606',
        'graph_version'    => 'v23.0',
        'template_name'    => 'rsvp_mariage',
        'template_language'=> 'fr'
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
