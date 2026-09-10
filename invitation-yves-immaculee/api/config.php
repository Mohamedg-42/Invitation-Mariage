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
        'cloud_name'    => 'yckdia0t',
        'api_key'       => '833693642819547',
        'api_secret'    => 'KmXKex61zeaaqt171bAmGYAx7BM',
        // Dossier où seront stockés les médias dans ton Cloud Cloudinary
        'folder'        => 'mariage-yves-immaculee'
    ]
];
