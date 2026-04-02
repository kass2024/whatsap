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

  Future<void> registerFcmToken(String token) async {
    try {
      await api.postJson('/device/fcm-token', {'fcm_token': token});
    } catch (_) {}
  }

  Future<void> _pushFcmToken() async {
    try {
      final t = await FirebaseMessaging.instance.getToken();
      if (t == null) {
        return;
      }
      await registerFcmToken(t);
    } catch (_) {}
  }

  Future<void> bootstrap() async {
    loading = true;
    notifyListeners();
    try {
      user = await auth.me();
      if (user != null) {
        await _pushFcmToken();
        await setupPushNotifications(registerFcmToken);
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
    await _pushFcmToken();
    await setupPushNotifications(registerFcmToken);
    notifyListeners();
  }

  Future<void> logout() async {
    await auth.logout();
    user = null;
    notifyListeners();
  }
}
