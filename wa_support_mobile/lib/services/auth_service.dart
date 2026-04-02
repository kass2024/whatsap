import 'dart:convert';

import '../models/user.dart';
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
    if (res.statusCode != 200) {
      throw Exception(_err(res.body));
    }
    final j = jsonDecode(res.body) as Map<String, dynamic>;
    final token = j['token'] as String;
    await _api.saveToken(token);
    return User.fromJson(j['user'] as Map<String, dynamic>);
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
    final res = await _api.get('/me');
    if (res.statusCode != 200) return null;
    return User.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
  }

  String _err(String body) {
    try {
      final j = jsonDecode(body) as Map<String, dynamic>;
      return (j['message'] as String?) ?? body;
    } catch (_) {
      return body;
    }
  }
}
