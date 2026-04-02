import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';

import '../firebase_options.dart';

const _androidChannelId = 'wa_support_alerts';
const _androidChannelName = 'Support alerts';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
}

final FlutterLocalNotificationsPlugin _local = FlutterLocalNotificationsPlugin();

bool _handlersAttached = false;

/// Registers FCM listeners, permission, and local notifications for foreground alerts.
Future<void> setupPushNotifications(
  Future<void> Function(String token) registerToken,
) async {
  if (Firebase.apps.isEmpty) {
    return;
  }

  final messaging = FirebaseMessaging.instance;

  if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
    await Permission.notification.request();
  }

  await messaging.requestPermission(
    alert: true,
    badge: true,
    sound: true,
    provisional: false,
  );

  const init = InitializationSettings(
    android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    iOS: DarwinInitializationSettings(),
    macOS: DarwinInitializationSettings(),
  );

  await _local.initialize(init);

  final androidPlugin = _local.resolvePlatformSpecificImplementation<
      AndroidFlutterLocalNotificationsPlugin>();
  await androidPlugin?.createNotificationChannel(
    const AndroidNotificationChannel(
      _androidChannelId,
      _androidChannelName,
      importance: Importance.high,
    ),
  );

  if (!_handlersAttached) {
    _handlersAttached = true;

    FirebaseMessaging.onMessage.listen((RemoteMessage m) async {
      final n = m.notification;
      final title = n?.title ?? m.data['title']?.toString() ?? 'Support';
      final body = n?.body ?? m.data['body']?.toString() ?? '';
      await _local.show(
        m.hashCode,
        title,
        body,
        const NotificationDetails(
          android: AndroidNotificationDetails(
            _androidChannelId,
            _androidChannelName,
            importance: Importance.high,
            priority: Priority.high,
          ),
          iOS: DarwinNotificationDetails(),
        ),
      );
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage m) {
      debugPrint('Notification opened: ${m.messageId}');
    });

    FirebaseMessaging.instance.onTokenRefresh.listen((token) {
      unawaited(registerToken(token));
    });
  }
}
