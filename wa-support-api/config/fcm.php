<?php

$rawPath = env('FCM_SERVICE_ACCOUNT_PATH');

$resolved = null;
$envHint = $rawPath;

// FORCE resolve path more reliably
if ($rawPath) {
    // If absolute path
    if (file_exists($rawPath)) {
        $resolved = $rawPath;
    }

    // If relative path
    if (!$resolved && file_exists(base_path($rawPath))) {
        $resolved = base_path($rawPath);
    }

    // Try storage fallback
    if (!$resolved) {
        $fallback = storage_path('app/firebase/' . basename($rawPath));
        if (file_exists($fallback)) {
            $resolved = $fallback;
        }
    }
}

// 🔥 IMPORTANT: fallback to env path even if unreadable
// (so we can debug instead of getting null)
if (!$resolved && $rawPath) {
    $resolved = $rawPath;
}

return [
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => $resolved,
    'service_account_env_hint' => $envHint,
];