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

  Future<void> registerFcmToken(String token) async {
    try {
      final res = await api.postJson('/device/fcm-token', {'fcm_token': token});
      if (res.statusCode == 200) {
        debugPrint('FCM token saved on server (${token.length} chars)');
      } else {
        debugPrint(
          'FCM token registration failed: HTTP ${res.statusCode} ${res.body}',
        );
      }
    } catch (e, st) {
      debugPrint('FCM token registration error: $e');
      debugPrint('$st');
    }
  }

  Future<void> _pushFcmToken() async {
    try {
      final t = await FirebaseMessaging.instance.getToken();
      if (t == null) {
        debugPrint('FCM getToken returned null (check Firebase config / google-services.json).');
        return;
      }
      await registerFcmToken(t);
    } catch (e, st) {
      debugPrint('FCM getToken failed: $e');
      debugPrint('$st');
    }
  }

  /// Call when app returns to foreground (permission may have been granted after login).
  Future<void> syncFcmTokenIfLoggedIn() async {
    if (user == null) {
      return;
    }
    await _pushFcmToken();
  }

  /// Permission + channel setup, then token — with a short retry (first getToken can race after init).
  Future<void> registerPushForLoggedInUser() async {
    if (user == null) {
      return;
    }
    await setupPushNotifications(registerFcmToken);
    await _pushFcmToken();
    await Future<void>.delayed(const Duration(seconds: 2));
    await _pushFcmToken();
  }

  Future<void> bootstrap() async {
    loading = true;
    notifyListeners();
    try {
      user = await auth.me();
      if (user != null) {
        await registerPushForLoggedInUser();
      }
    } catch (e, st) {
      debugPrint('bootstrap: $e');
      debugPrint('$st');
      user = null;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> login(String email, String password) async {
    user = await auth.login(email, password);
    await registerPushForLoggedInUser();
    notifyListeners();
  }

  Future<void> logout() async {
    await auth.logout();
    user = null;
    notifyListeners();
  }
}
