<?php

/**
 * Resolve Firebase service account JSON path safely using config values.
 */

// Get from env ONCE (safe for config files)
$rawPath = $_ENV['FCM_SERVICE_ACCOUNT_PATH'] ?? $_SERVER['FCM_SERVICE_ACCOUNT_PATH'] ?? null;

$envHint = is_string($rawPath) && $rawPath !== '' ? $rawPath : null;
$resolved = null;

if ($envHint) {
    $p = trim($envHint);

    $candidates = array_unique(array_filter([
        $p,
        base_path($p),
        base_path(ltrim($p, '/')),
        (str_ends_with($p, '.json')) ? storage_path('app/firebase/' . basename($p)) : null,
        (str_ends_with($p, '.json')) ? base_path('storage/app/firebase/' . basename($p)) : null,
    ]));

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '' && file_exists($candidate) && is_readable($candidate)) {
            $resolved = $candidate;
            break;
        }
    }
}

return [
    'project_id' => $_ENV['FCM_PROJECT_ID'] ?? $_SERVER['FCM_PROJECT_ID'] ?? null,

    // FINAL resolved path (used by app)
    'service_account_path' => $resolved,

    // Just for debugging
    'service_account_env_hint' => $envHint,
];