<?php

/**
 * Resolve Firebase service account JSON path from FCM_SERVICE_ACCOUNT_PATH.
 *
 * Tries: absolute path, path relative to Laravel root, and storage/app/firebase/{basename}.
 */
$rawPath = env('FCM_SERVICE_ACCOUNT_PATH');
$envHint = is_string($rawPath) && $rawPath !== '' ? $rawPath : null;
$resolved = null;

if (is_string($rawPath) && $rawPath !== '') {
    $p = trim($rawPath);
    $candidates = array_unique(array_filter([
        $p,
        base_path($p),
        base_path(ltrim($p, '/')),
        (strlen($p) < 200 && str_ends_with($p, '.json')) ? storage_path('app/firebase/'.basename($p)) : null,
        (strlen($p) < 200 && str_ends_with($p, '.json')) ? base_path('storage/app/firebase/'.basename($p)) : null,
    ]));

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '' && is_readable($candidate)) {
            $resolved = $candidate;
            break;
        }
    }
}

return [
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => $resolved,
    'service_account_env_hint' => $envHint,
];
