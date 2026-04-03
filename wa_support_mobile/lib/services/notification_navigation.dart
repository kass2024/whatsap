import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

/// FCM data from Laravel includes `conversation_id`. [HomeShell] listens and opens that chat.
final ValueNotifier<int?> notificationConversationIdToOpen =
    ValueNotifier<int?>(null);

void emitOpenConversationFromNotification(RemoteMessage message) {
  final raw = message.data['conversation_id'];
  if (raw == null) {
    return;
  }
  final id = int.tryParse(raw.toString());
  if (id != null) {
    notificationConversationIdToOpen.value = id;
  }
}
