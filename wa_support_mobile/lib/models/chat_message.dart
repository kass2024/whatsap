class ChatMessage {
  ChatMessage({
    required this.id,
    required this.conversationId,
    required this.senderType,
    required this.messageType,
    this.content,
    this.mediaUrl,
    this.mimeType,
    this.fileName,
    required this.status,
    required this.createdAt,
  });

  final int id;
  final int conversationId;
  final String senderType;
  final String messageType;
  final String? content;
  final String? mediaUrl;
  final String? mimeType;
  final String? fileName;
  final String status;
  final DateTime createdAt;

  bool get isAgent => senderType == 'agent';

  factory ChatMessage.fromJson(Map<String, dynamic> j) {
    return ChatMessage(
      id: j['id'] as int,
      conversationId: j['conversation_id'] as int,
      senderType: j['sender_type'] as String,
      messageType: j['message_type'] as String,
      content: j['content'] as String?,
      mediaUrl: j['media_url'] as String?,
      mimeType: j['mime_type'] as String?,
      fileName: j['file_name'] as String?,
      status: j['status'] as String? ?? 'unknown',
      createdAt: DateTime.parse(j['created_at'] as String),
    );
  }
}
