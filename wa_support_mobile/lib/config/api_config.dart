import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;

/// Laravel API base URL for **Parrot Support** (production: `whatsapp.parrotcanada.site`).
///
/// **Release builds** always use [productionBaseUrl]. A mistaken
/// `--dart-define=API_BASE=http://192.168...` in the build command cannot override production.
///
/// **Debug / profile** (local dev):
/// - Use **`API_BASE`** only (not `productionBaseUrl`):
///   `--dart-define=API_BASE=https://whatsapp.parrotcanada.site/api`
/// - Local Laravel: `--dart-define=API_BASE=http://10.0.2.2:8000/api` (emulator) etc.
/// - If unset: **production** on mobile/desktop; **localhost** on web.
class ApiConfig {
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
    if (kReleaseMode) {
      return productionBaseUrl;
    }

    const fromEnv = String.fromEnvironment('API_BASE', defaultValue: '');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api';
    }
    return productionBaseUrl;
  }

  static String _normalizeApiBase(String url) {
    var u = url.trim();
    while (u.endsWith('/')) {
      u = u.substring(0, u.length - 1);
    }
    if (!u.endsWith('/api')) {
      u = '$u/api';
    }
    return u;
  }
}
