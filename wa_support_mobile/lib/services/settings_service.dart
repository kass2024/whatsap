import 'dart:convert';

import 'api_service.dart';

class AdminPhonesPayload {
  AdminPhonesPayload({
    required this.phonesText,
    required this.count,
  });

  final String phonesText;
  final int count;

  factory AdminPhonesPayload.fromJson(Map<String, dynamic> j) {
    return AdminPhonesPayload(
      phonesText: j['phones_text'] as String? ?? '',
      count: (j['count'] as num?)?.toInt() ?? 0,
    );
  }
}

class SettingsService {
  SettingsService(this._api);

  final ApiService _api;

  Future<AdminPhonesPayload> fetchAdminPhones() async {
    final res = await _api.get('/settings/admin-phones');
    if (res.statusCode != 200) {
      throw Exception(res.body);
    }
    return AdminPhonesPayload.fromJson(
      jsonDecode(res.body) as Map<String, dynamic>,
    );
  }

  Future<void> saveAdminPhones(String phones) async {
    final res = await _api.putJson('/settings/admin-phones', {'phones': phones});
    if (res.statusCode != 200) {
      throw Exception(res.body);
    }
  }
}
