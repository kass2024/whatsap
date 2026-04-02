import 'dart:convert';
import 'dart:io';

import '../models/chat_message.dart';
import '../models/conversation.dart';
import 'api_service.dart';

class AgentSummary {
  const AgentSummary({
    required this.id,
    required this.name,
    this.email,
    this.role = 'agent',
  });

  final int id;
  final String name;
  final String? email;

  /// `admin` or `agent` — admin-only chats may only be assigned to `admin`.
  final String role;

  factory AgentSummary.fromJson(Map<String, dynamic> j) {
    return AgentSummary(
      id: j['id'] as int,
      name: j['name'] as String,
      email: j['email'] as String?,
      role: j['role'] as String? ?? 'agent',
    );
  }
}

class SessionStatus {
  SessionStatus({
    required this.active,
    this.expiresAt,
    required this.reason,
  });

  final bool active;
  final String? expiresAt;
  final String reason;

  factory SessionStatus.fromJson(Map<String, dynamic> j) {
    return SessionStatus(
      active: j['active'] as bool,
      expiresAt: j['expires_at'] as String?,
      reason: j['reason'] as String? ?? '',
    );
  }
}

class ChatService {
  ChatService(this._api);

  final ApiService _api;

  Future<List<AgentSummary>> fetchAgents() async {
    final res = await _api.get('/agents');
    if (res.statusCode != 200) {
      throw Exception(res.body);
    }
    final j = jsonDecode(res.body) as Map<String, dynamic>;
    final list = j['data'] as List<dynamic>? ?? [];
    return list.map((e) => AgentSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<Conversation> fetchConversation(int id) async {
    final res = await _api.get('/conversations/$id');
    if (res.statusCode != 200) {
      throw Exception(res.body);
    }
    return Conversation.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
  }

  Future<List<Conversation>> fetchConversations() async {
    final res = await _api.get('/conversations');
    if (res.statusCode != 200) throw Exception(res.body);
    final j = jsonDecode(res.body) as Map<String, dynamic>;
    final list = j['data'] as List<dynamic>? ?? [];
    return list.map((e) => Conversation.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<SessionStatus> sessionStatus(int conversationId) async {
    final res = await _api.get('/conversations/$conversationId/session');
    if (res.statusCode != 200) throw Exception(res.body);
    return SessionStatus.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
  }

  Future<List<ChatMessage>> fetchMessages(int conversationId) async {
    final res = await _api.get('/conversations/$conversationId/messages');
    if (res.statusCode != 200) throw Exception(res.body);
    final j = jsonDecode(res.body) as Map<String, dynamic>;
    final list = j['data'] as List<dynamic>? ?? [];
    return list.map((e) => ChatMessage.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<ChatMessage>> poll(int conversationId, int afterId) async {
    final res = await _api.get('/messages/poll', query: {
      'conversation_id': '$conversationId',
      'after_id': '$afterId',
    });
    if (res.statusCode != 200) throw Exception(res.body);
    final j = jsonDecode(res.body) as Map<String, dynamic>;
    final list = j['messages'] as List<dynamic>? ?? [];
    return list.map((e) => ChatMessage.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> markRead(int conversationId) async {
    await _api.postJson('/conversations/$conversationId/read', {});
  }

  Future<ChatMessage> sendText(int conversationId, String text) async {
    final res = await _api.postJson('/conversations/$conversationId/messages/text', {'text': text});
    if (res.statusCode != 201) throw Exception(res.body);
    return ChatMessage.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
  }

  Future<ChatMessage> sendMedia(int conversationId, File file, {String? caption}) async {
    final fields = <String, String>{};
    if (caption != null && caption.isNotEmpty) {
      fields['caption'] = caption;
    }
    final res = await _api.postMultipart(
      '/conversations/$conversationId/messages/media',
      'file',
      file,
      fields: fields.isEmpty ? null : fields,
    );
    if (res.statusCode != 201) throw Exception(res.body);
    return ChatMessage.fromJson(jsonDecode(res.body) as Map<String, dynamic>);
  }

  Future<void> sendTemplate(
    int conversationId,
    String name,
    String language, {
    List<dynamic>? components,
  }) async {
    final res = await _api.postJson('/conversations/$conversationId/messages/template', {
      'name': name,
      'language': language,
      if (components != null) 'components': components,
    });
    if (res.statusCode != 201) throw Exception(res.body);
  }

  Future<void> assignConversation(int conversationId, int? userId) async {
    final res = await _api.patchJson('/conversations/$conversationId/assign', {
      'assigned_to': userId,
    });
    if (res.statusCode != 200) throw Exception(res.body);
  }
}
