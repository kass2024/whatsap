import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../models/user.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../services/chat_service.dart';
import '../services/push_notification_service.dart';
import '../services/settings_service.dart';

class AppState extends ChangeNotifier {
  final ApiService api = ApiService();
  late final AuthService auth = AuthService(api);
  late final ChatService chat = ChatService(api);
  late final SettingsService settings = SettingsService(api);

  User? user;
  bool loading = true;

  /// Incremented after admin-only phone list changes so inbox/dashboard refetch `is_admin_only`.
  int conversationListVersion = 0;

  void bumpConversationListRefresh() {
    conversationListVersion++;
    notifyListeners();
  }

  Future<void> registerPublicDeviceToken(String token) async {
    try {
      final res = await api.postJsonPublic('/push/register-device', {'fcm_token': token});
      if (res.statusCode == 200) {
        debugPrint('FCM device registered (no login required) — ${token.length} chars');
      } else {
        debugPrint(
          'FCM public register failed: HTTP ${res.statusCode} ${res.body}',
        );
      }
    } catch (e, st) {
      debugPrint('FCM public register error: $e');
      debugPrint('$st');
    }
  }

  Future<void> registerAuthenticatedFcmToken(String token) async {
    try {
      final res = await api.postJson('/device/fcm-token', {'fcm_token': token});
      if (res.statusCode == 200) {
        debugPrint('FCM token saved for logged-in user (${token.length} chars)');
      } else {
        debugPrint(
          'FCM user token failed: HTTP ${res.statusCode} ${res.body}',
        );
      }
    } catch (e, st) {
      debugPrint('FCM user token error: $e');
      debugPrint('$st');
    }
  }

  Future<void> syncFcmTokenToBackend(String token) async {
    await registerPublicDeviceToken(token);
    final t = await api.getToken();
    if (t != null) {
      await registerAuthenticatedFcmToken(token);
    }
  }

  Future<void> _pushFcmToken() async {
    try {
      final t = await FirebaseMessaging.instance.getToken();
      if (t == null) {
        debugPrint('FCM getToken returned null (check Firebase config / google-services.json).');
        return;
      }
      await syncFcmTokenToBackend(t);
    } catch (e, st) {
      debugPrint('FCM getToken failed: $e');
      debugPrint('$st');
    }
  }

  /// After notification permission or app resume — works logged in or out.
  Future<void> syncFcmTokenOnResume() async {
    await _pushFcmToken();
  }

  /// Permission + listeners, then send token(s) to public + authenticated endpoints.
  Future<void> registerPushForCurrentSession() async {
    await setupPushNotifications(syncFcmTokenToBackend);
    await _pushFcmToken();
    await Future<void>.delayed(const Duration(seconds: 2));
    await _pushFcmToken();
  }

  Future<void> bootstrap() async {
    loading = true;
    notifyListeners();
    try {
      user = await auth.me();
      await registerPushForCurrentSession();
    } catch (e, st) {
      debugPrint('bootstrap: $e');
      debugPrint('$st');
      user = null;
      try {
        await registerPushForCurrentSession();
      } catch (_) {}
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> login(String email, String password) async {
    user = await auth.login(email, password);
    await registerPushForCurrentSession();
    notifyListeners();
  }

  Future<void> logout() async {
    await auth.logout();
    user = null;
    notifyListeners();
    await registerPushForCurrentSession();
  }
}
