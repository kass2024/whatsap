import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../models/user.dart';
import 'api_log.dart';
import 'api_service.dart';

class AuthService {
  AuthService(this._api);

  final ApiService _api;

  Future<User> login(String email, String password) async {
    final res = await _api.postJson('/login', {
      'email': email,
      'password': password,
      'device_name': 'flutter',
    });

    logApiHttpResponse(label: 'POST /login', response: res);

    if (res.statusCode != 200) {
      throw Exception(_err(res.body));
    }

    try {
      final j = jsonDecode(res.body) as Map<String, dynamic>;
      final token = j['token'] as String;
      await _api.saveToken(token);
      return User.fromJson(j['user'] as Map<String, dynamic>);
    } catch (e, st) {
      debugPrint('[ParrotAPI] Login JSON parse error: $e');
      debugPrint('$st');
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await _api.postJson('/logout', {});
    } catch (_) {}
    await _api.clearToken();
  }

  Future<User?> me() async {
    final t = await _api.getToken();
    if (t == null) return null;
    try {
      final res = await _api.get('/me');
      if (res.statusCode != 200) {
        logApiHttpResponse(label: 'GET /me', response: res);
        return null;
      }
      return User.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
    } catch (e, st) {
      debugPrint('[ParrotAPI] me() error: $e');
      debugPrint('$st');
      return null;
    }
  }

  /// Laravel JSON errors often include `message`, `file`, `line`, `exception`.
  String _err(String body) {
    try {
      final j = jsonDecode(body) as Map<String, dynamic>;
      final msg = j['message'];
      final buf = StringBuffer();
      if (msg != null) {
        buf.writeln(msg);
      } else {
        buf.writeln(body);
      }
      final ex = j['exception'];
      if (ex != null) buf.writeln('exception: $ex');
      final file = j['file'];
      final line = j['line'];
      if (file != null) {
        buf.writeln('at: $file:${line ?? '?'}');
      }
      return buf.toString().trim();
    } catch (_) {
      return body;
    }
  }
}
