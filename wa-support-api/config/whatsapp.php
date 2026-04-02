<?php

return [

    'graph_version' => env('META_GRAPH_VERSION', env('WHATSAPP_GRAPH_VERSION', 'v21.0')),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'business_id' => env('WHATSAPP_BUSINESS_ID'),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),

    'media_disk' => env('WHATSAPP_MEDIA_DISK', 'public'),

    'media_directory' => env('WHATSAPP_MEDIA_DIRECTORY', 'wa-media'),

];
