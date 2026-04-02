import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;

/// Resolves the Laravel API base URL.
///
/// **Local API** (emulator / LAN / desktop):
/// `flutter run --dart-define=API_BASE=http://10.0.2.2:8000/api` (Android emulator → host)
/// `flutter run --dart-define=API_BASE=http://192.168.x.x:8000/api` (physical phone → PC)
///
/// Debug builds **without** `API_BASE` use **production** on mobile so real devices work.
/// Web debug still uses localhost for typical `php artisan serve` flow.
class ApiConfig {
  /// Production API (used in release builds when `API_BASE` is not passed).
  static const String productionBaseUrl = 'https://whatsapp.parrotcanada.site/api';

  /// Host only (for UI), e.g. `whatsapp.parrotcanada.site`.
  static String get displayHost {
    try {
      final u = Uri.parse(baseUrl);
      if (u.hasScheme && u.host.isNotEmpty) {
        return u.host;
      }
    } catch (_) {}
    return baseUrl;
  }

  static String get baseUrl {
    const fromEnv = String.fromEnvironment('API_BASE', defaultValue: '');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }
    if (kReleaseMode) {
      return productionBaseUrl;
    }
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api';
    }
    // Debug/profile on iOS/Android/desktop: use production unless API_BASE is set.
    // (10.0.2.2 only works on the *emulator*; physical phones would hang trying to reach it.)
    return productionBaseUrl;
  }
}
