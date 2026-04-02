import 'dart:convert';

import 'api_service.dart';

class AdminPhoneItem {
  const AdminPhoneItem({required this.phone, this.label});

  final String phone;
  final String? label;

  Map<String, dynamic> toJson() => {
        'phone': phone,
        if (label != null && label!.trim().isNotEmpty) 'label': label!.trim(),
      };

  factory AdminPhoneItem.fromJson(Map<String, dynamic> j) {
    return AdminPhoneItem(
      phone: j['phone'] as String,
      label: j['label'] as String?,
    );
  }
}

class AdminPhonesPayload {
  AdminPhonesPayload({
    required this.phonesText,
    required this.count,
    required this.items,
  });

  final String phonesText;
  final int count;
  final List<AdminPhoneItem> items;

  factory AdminPhonesPayload.fromJson(Map<String, dynamic> j) {
    final raw = j['items'] as List<dynamic>? ?? [];
    final items = raw
        .map((e) => AdminPhoneItem.fromJson(e as Map<String, dynamic>))
        .toList();
    return AdminPhonesPayload(
      phonesText: j['phones_text'] as String? ?? '',
      count: (j['count'] as num?)?.toInt() ?? items.length,
      items: items,
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

  Future<void> saveAdminPhonesItems(List<AdminPhoneItem> items) async {
    final res = await _api.putJson('/settings/admin-phones', {
      'items': items.map((e) => e.toJson()).toList(),
    });
    if (res.statusCode != 200) {
      throw Exception(res.body);
    }
  }
}
