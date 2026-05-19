import 'dart:convert';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class PusherService {
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter();

  Future<void> init({
    required int conversationId,
    required Function(dynamic) onMessage,
  }) async {
    await _pusher.init(
      apiKey: "cc1c85717f9b9207ee77",
      cluster: "mt1",
      useTLS: true,
      onConnectionStateChange: (currentState, previousState) {
        print("Pusher: $currentState");
      },
      onError: (message, code, e) {
        print("Pusher error: $message");
      },
    );

    await _pusher.subscribe(
      channelName: "chat.$conversationId",
      onEvent: (event) {
        print("EVENT: ${event.eventName}");
        print("DATA: ${event.data}");

        if (event.eventName == "message.sent") {
          final data = jsonDecode(event.data);
          onMessage(data); // 👈 important
        }
      },
    );

    await _pusher.connect();
  }
}
