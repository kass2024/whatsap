<?php

$p = env('FCM_SERVICE_ACCOUNT_PATH');

$resolved = null;
if ($p !== null && $p !== '') {
    $candidate = base_path($p);
    if (is_readable($candidate)) {
        $resolved = $candidate;
    } elseif (is_readable($p)) {
        $resolved = $p;
    }
}

return [
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => $resolved,
];
