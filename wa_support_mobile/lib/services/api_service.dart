import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../config/api_config.dart';

class ApiService {
  static const _tokenKey = 'auth_token';
  static const _timeout = Duration(seconds: 25);

  Future<String?> getToken() async {
    final p = await SharedPreferences.getInstance();
    return p.getString(_tokenKey);
  }

  Future<void> saveToken(String token) async {
    final p = await SharedPreferences.getInstance();
    await p.setString(_tokenKey, token);
  }

  Future<void> clearToken() async {
    final p = await SharedPreferences.getInstance();
    await p.remove(_tokenKey);
  }

  Future<Map<String, String>> _headers({bool json = true}) async {
    final t = await getToken();
    final h = <String, String>{
      if (json) 'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    };
    return h;
  }

  Uri _u(String path, [Map<String, String>? query]) {
    final base = ApiConfig.baseUrl.replaceAll(RegExp(r'/$'), '');
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$p').replace(queryParameters: query);
  }

  Future<http.Response> get(String path, {Map<String, String>? query}) async {
    return http.get(_u(path, query), headers: await _headers()).timeout(_timeout);
  }

  Future<http.Response> postJson(String path, Map<String, dynamic> body) async {
    final h = await _headers();
    h['Content-Type'] = 'application/json';
    return http.post(_u(path), headers: h, body: jsonEncode(body)).timeout(_timeout);
  }

  Future<http.Response> patchJson(String path, Map<String, dynamic> body) async {
    final h = await _headers();
    h['Content-Type'] = 'application/json';
    return http.patch(_u(path), headers: h, body: jsonEncode(body)).timeout(_timeout);
  }

  Future<http.Response> putJson(String path, Map<String, dynamic> body) async {
    final h = await _headers();
    h['Content-Type'] = 'application/json';
    return http.put(_u(path), headers: h, body: jsonEncode(body)).timeout(_timeout);
  }

  Future<http.Response> postMultipart(
    String path,
    String fieldName,
    File file, {
    Map<String, String>? fields,
  }) async {
    final req = http.MultipartRequest('POST', _u(path));
    final h = await _headers(json: false);
    h['Accept'] = 'application/json';
    req.headers.addAll(h);
    if (fields != null) {
      req.fields.addAll(fields);
    }
    req.files.add(await http.MultipartFile.fromPath(fieldName, file.path));
    final streamed = await req.send().timeout(_timeout);
    return http.Response.fromStream(streamed);
  }
}
