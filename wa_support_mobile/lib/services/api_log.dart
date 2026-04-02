import 'dart:developer' as developer;

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

/// Server-side (Laravel): `tail -f /var/www/whatsap/wa-support-api/storage/logs/laravel.log`
/// Android logcat: `adb logcat -s flutter`
///
/// Writes to Dart developer log + `debugPrint` (visible in **Run** panel and logcat).
void logApiHttpResponse({
  required String label,
  required http.Response response,
  int maxBodyChars = 6000,
}) {
  final uri = response.request?.url ?? Uri();
  final body = response.body;
  final snippet = body.length > maxBodyChars
      ? '${body.substring(0, maxBodyChars)}… [truncated ${body.length - maxBodyChars} chars]'
      : body;

  final msg = '$label ${response.statusCode} $uri\n$snippet';

  developer.log(
    msg,
    name: 'ParrotAPI',
    level: response.statusCode >= 400 ? 1000 : 800,
  );
  debugPrint('[ParrotAPI] $msg');
}
