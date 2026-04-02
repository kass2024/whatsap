class Conversation {
  Conversation({
    required this.id,
    required this.phone,
    this.customerName,
    this.lastMessageAt,
    this.lastMessagePreview,
    required this.unreadCount,
    this.assignedTo,
    this.status,
    this.isAdminOnly = false,
  });

  final int id;
  final String phone;
  final String? customerName;
  final DateTime? lastMessageAt;
  final String? lastMessagePreview;
  final int unreadCount;
  final int? assignedTo;
  final String? status;
  final bool isAdminOnly;

  Conversation copyWith({
    int? id,
    String? phone,
    String? customerName,
    DateTime? lastMessageAt,
    String? lastMessagePreview,
    int? unreadCount,
    int? assignedTo,
    String? status,
    bool? isAdminOnly,
  }) {
    return Conversation(
      id: id ?? this.id,
      phone: phone ?? this.phone,
      customerName: customerName ?? this.customerName,
      lastMessageAt: lastMessageAt ?? this.lastMessageAt,
      lastMessagePreview: lastMessagePreview ?? this.lastMessagePreview,
      unreadCount: unreadCount ?? this.unreadCount,
      assignedTo: assignedTo ?? this.assignedTo,
      status: status ?? this.status,
      isAdminOnly: isAdminOnly ?? this.isAdminOnly,
    );
  }

  factory Conversation.fromJson(Map<String, dynamic> j) {
    return Conversation(
      id: j['id'] as int,
      phone: j['phone'] as String,
      customerName: j['customer_name'] as String?,
      lastMessageAt: j['last_message_at'] != null
          ? DateTime.tryParse(j['last_message_at'] as String)
          : null,
      lastMessagePreview: j['last_message_preview'] as String?,
      unreadCount: (j['unread_count'] as num?)?.toInt() ?? 0,
      assignedTo: (j['assigned_to'] as num?)?.toInt(),
      status: j['status'] as String?,
      isAdminOnly: j['is_admin_only'] as bool? ?? false,
    );
  }
}
