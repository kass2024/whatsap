import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';

import '../firebase_options.dart';
import 'notification_navigation.dart';

const _androidChannelId = 'wa_support_alerts';
const _androidChannelName = 'Support alerts';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
}

bool _firebaseOpenHandlersAttached = false;

/// Cold start + notification tap → [emitOpenConversationFromNotification]. Call once after login.
Future<void> attachFirebaseNotificationOpenHandlers() async {
  if (Firebase.apps.isEmpty || _firebaseOpenHandlersAttached) {
    return;
  }
  _firebaseOpenHandlersAttached = true;

  final initial = await FirebaseMessaging.instance.getInitialMessage();
  if (initial != null) {
    emitOpenConversationFromNotification(initial);
  }

  FirebaseMessaging.onMessageOpenedApp.listen(emitOpenConversationFromNotification);
}

final FlutterLocalNotificationsPlugin _local = FlutterLocalNotificationsPlugin();

bool _handlersAttached = false;

/// Registers FCM listeners, permission, and local notifications for foreground alerts.
Future<void> setupPushNotifications(
  Future<void> Function(String token) registerToken,
) async {
  try {
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

    await _local.initialize(
      init,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        final p = response.payload;
        if (p == null || p.isEmpty) {
          return;
        }
        final id = int.tryParse(p);
        if (id != null) {
          notificationConversationIdToOpen.value = id;
        }
      },
    );

    final androidPlugin = _local.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(
      const AndroidNotificationChannel(
        _androidChannelId,
        _androidChannelName,
        description: 'New customer WhatsApp messages',
        importance: Importance.high,
        playSound: true,
        enableVibration: true,
        showBadge: true,
      ),
    );
    await androidPlugin?.requestNotificationsPermission();

    if (!_handlersAttached) {
      _handlersAttached = true;

      FirebaseMessaging.onMessage.listen((RemoteMessage m) async {
        try {
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
                channelShowBadge: true,
                importance: Importance.high,
                priority: Priority.high,
                playSound: true,
                enableVibration: true,
                visibility: NotificationVisibility.public,
                category: AndroidNotificationCategory.message,
              ),
              iOS: DarwinNotificationDetails(presentSound: true),
            ),
            payload: m.data['conversation_id']?.toString(),
          );
        } catch (e, st) {
          debugPrint('FCM foreground show failed: $e');
          debugPrint('$st');
        }
      });

      FirebaseMessaging.instance.onTokenRefresh.listen((token) {
        unawaited(registerToken(token));
      });
    }
  } catch (e, st) {
    debugPrint('setupPushNotifications failed: $e');
    debugPrint('$st');
  }
}
