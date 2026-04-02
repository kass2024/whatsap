import 'package:flutter/foundation.dart'
    show kIsWeb, kReleaseMode, defaultTargetPlatform, TargetPlatform;

/// Resolves the Laravel API base URL.
///
/// Override anytime:
/// `flutter run --dart-define=API_BASE=http://192.168.0.100:8000/api`
///
/// - **Web (Chrome)**: `http://127.0.0.1:8000/api` — `10.0.2.2` is only for the Android emulator.
/// - **Android emulator**: `http://10.0.2.2:8000/api` → host machine.
/// - **Physical device**: set `API_BASE` to your PC’s LAN IP.
class ApiConfig {
  /// Production API (used in release builds when `API_BASE` is not passed).
  static const String productionBaseUrl = 'https://whatsapp.parrotcanada.site/api';

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
    if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }
}
