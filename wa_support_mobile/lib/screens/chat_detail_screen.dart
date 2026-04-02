import 'dart:async';
import 'dart:io';

import 'package:audioplayers/audioplayers.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:record/record.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/app_colors.dart';
import '../models/chat_message.dart';
import '../models/conversation.dart';
import '../providers/app_state.dart';
import '../services/chat_service.dart';
import '../widgets/whatsapp_chat_composer.dart';
import 'admin_phones_screen.dart';

class ChatDetailScreen extends StatefulWidget {
  const ChatDetailScreen({super.key, required this.conversation});

  final Conversation conversation;

  @override
  State<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends State<ChatDetailScreen> {
  final _text = TextEditingController();
  final _scroll = ScrollController();
  late Conversation _conv;
  List<ChatMessage> _messages = [];
  SessionStatus? _session;
  List<AgentSummary> _agents = [];
  bool _loading = true;
  String? _error;
  Timer? _poll;
  int _maxId = 0;
  final _audio = AudioPlayer();
  final _recorder = AudioRecorder();
  bool _holdRecording = false;
  String? _holdRecordPath;

  ChatService get _chat => context.read<AppState>().chat;

  bool _admin = false;
  var _bootStarted = false;

  @override
  void initState() {
    super.initState();
    _conv = widget.conversation;
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _admin = context.read<AppState>().user?.role == 'admin';
    if (!_bootStarted) {
      _bootStarted = true;
      _bootstrap();
    }
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      if (_admin) {
        _agents = await _chat.fetchAgents();
      }
      await _chat.markRead(_conv.id);
      final msgs = await _chat.fetchMessages(_conv.id);
      final sess = await _chat.sessionStatus(_conv.id);
      final fresh = await _chat.fetchConversation(_conv.id);
      _maxId = msgs.isEmpty ? 0 : msgs.map((m) => m.id).reduce((a, b) => a > b ? a : b);
      if (mounted) {
        setState(() {
          _messages = msgs;
          _session = sess;
          _conv = fresh;
          _loading = false;
        });
      }
      _scrollBottom();
      _startPoll();
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = '$e';
          _loading = false;
        });
      }
    }
  }

  Future<void> _assign(int? userId) async {
    try {
      await _chat.assignConversation(_conv.id, userId);
      final fresh = await _chat.fetchConversation(_conv.id);
      if (mounted) {
        setState(() => _conv = fresh);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Assignment updated.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  void _startPoll() {
    _poll?.cancel();
    _poll = Timer.periodic(const Duration(seconds: 2), (_) async {
      try {
        final next = await _chat.poll(_conv.id, _maxId);
        if (next.isEmpty || !mounted) {
          return;
        }
        for (final m in next) {
          if (m.id > _maxId) {
            _maxId = m.id;
          }
        }
        setState(() {
          final ids = _messages.map((e) => e.id).toSet();
          for (final m in next) {
            if (!ids.contains(m.id)) {
              _messages = [..._messages, m];
            }
          }
          _messages.sort((a, b) => a.id.compareTo(b.id));
        });
        _scrollBottom();
      } catch (_) {}
    });
  }

  void _scrollBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.jumpTo(_scroll.position.maxScrollExtent);
      }
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    _text.dispose();
    _scroll.dispose();
    _audio.dispose();
    _recorder.dispose();
    super.dispose();
  }

  bool get _sessionActive => _session?.active == true;

  Future<void> _sendText() async {
    final t = _text.text.trim();
    if (t.isEmpty) {
      return;
    }
    _text.clear();
    try {
      final m = await _chat.sendText(_conv.id, t);
      setState(() {
        _messages = [..._messages, m];
        if (m.id > _maxId) {
          _maxId = m.id;
        }
      });
      _scrollBottom();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _pick(ImageSource src) async {
    final picker = ImagePicker();
    final x = await picker.pickImage(source: src, imageQuality: 85);
    if (x == null) {
      return;
    }
    try {
      final m = await _chat.sendMedia(_conv.id, File(x.path));
      setState(() {
        _messages = [..._messages, m];
        if (m.id > _maxId) {
          _maxId = m.id;
        }
      });
      _scrollBottom();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _pickFile() async {
    final r = await FilePicker.platform.pickFiles(type: FileType.any, withData: false);
    if (r == null || r.files.single.path == null) {
      return;
    }
    try {
      final m = await _chat.sendMedia(_conv.id, File(r.files.single.path!));
      setState(() {
        _messages = [..._messages, m];
        if (m.id > _maxId) {
          _maxId = m.id;
        }
      });
      _scrollBottom();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _beginHoldRecord() async {
    if (await _recorder.hasPermission() != true) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Microphone permission is required to record.')),
        );
      }
      return;
    }
    final dir = await getTemporaryDirectory();
    _holdRecordPath = '${dir.path}/wa_hold_${DateTime.now().millisecondsSinceEpoch}.m4a';
    await _recorder.start(
      const RecordConfig(encoder: AudioEncoder.aacLc),
      path: _holdRecordPath!,
    );
    if (mounted) {
      setState(() => _holdRecording = true);
    }
  }

  Future<void> _endHoldRecord() async {
    if (!_holdRecording) {
      return;
    }
    await _recorder.stop();
    if (mounted) {
      setState(() => _holdRecording = false);
    }
    final path = _holdRecordPath;
    _holdRecordPath = null;
    if (path == null) {
      return;
    }
    try {
      final file = File(path);
      if (!await file.exists() || await file.length() < 400) {
        return;
      }
      final m = await _chat.sendMedia(_conv.id, file);
      if (mounted) {
        setState(() {
          _messages = [..._messages, m];
          if (m.id > _maxId) {
            _maxId = m.id;
          }
        });
        _scrollBottom();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  String get _e164Phone {
    final raw = _conv.phone.trim();
    return raw.startsWith('+') ? raw : '+$raw';
  }

  Future<void> _callCustomer() async {
    final uri = Uri.parse('tel:$_e164Phone');
    try {
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Unable to open the phone app.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _addToContacts() async {
    final display = (_conv.customerName != null && _conv.customerName!.trim().isNotEmpty)
        ? _conv.customerName!.trim()
        : 'Customer $_e164Phone';
    try {
      final granted = await FlutterContacts.requestPermission();
      if (!granted) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Allow contacts access to save this number.')),
          );
        }
        return;
      }
      final contact = Contact(
        displayName: display,
        phones: [Phone(_e164Phone, label: PhoneLabel.mobile)],
      );
      await contact.insert();
      if (mounted) {
        HapticFeedback.mediumImpact();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Contact saved on this device')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  Future<void> _openTemplateDialog() async {
    final nameCtrl = TextEditingController();
    final langCtrl = TextEditingController(text: 'en');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Send template'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameCtrl,
              decoration: const InputDecoration(
                labelText: 'Template name',
              ),
            ),
            TextField(
              controller: langCtrl,
              decoration: const InputDecoration(labelText: 'Language code'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Send')),
        ],
      ),
    );
    if (ok != true) {
      return;
    }
    final name = nameCtrl.text.trim();
    final lang = langCtrl.text.trim();
    if (name.isEmpty || lang.isEmpty) {
      return;
    }
    try {
      await _chat.sendTemplate(_conv.id, name, lang);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Template sent')),
        );
      }
      final sess = await _chat.sessionStatus(_conv.id);
      if (mounted) {
        setState(() => _session = sess);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasName = _conv.customerName?.isNotEmpty == true;
    final title = hasName ? _conv.customerName! : _e164Phone;
    final df = DateFormat.Hm();

    return Scaffold(
      backgroundColor: AppColors.pageBg,
      resizeToAvoidBottomInset: true,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(title),
            if (hasName)
              Text(
                _e164Phone,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade700,
                  fontWeight: FontWeight.w400,
                ),
              ),
            if (_admin && _conv.isAdminOnly)
              const Text(
                'Admin-only · hidden from agents',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
              ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Call',
            icon: const Icon(Icons.phone_outlined),
            color: AppColors.green,
            onPressed: _callCustomer,
          ),
          IconButton(
            tooltip: 'Add to contacts',
            icon: const Icon(Icons.person_add_alt_1_outlined),
            onPressed: _addToContacts,
          ),
        ],
      ),
      body: Column(
        children: [
          if (_admin && _conv.isAdminOnly)
            MaterialBanner(
              backgroundColor: const Color(0xFFFFFBEB),
              content: const Text(
                'This number is on the restricted list. Agents cannot access this thread.',
                style: TextStyle(color: Color(0xFF92400E), fontSize: 13),
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(context).push<void>(
                      MaterialPageRoute<void>(builder: (_) => const AdminPhonesScreen()),
                    );
                  },
                  child: const Text('Edit list'),
                ),
              ],
            ),
          if (_session != null && !_sessionActive)
            MaterialBanner(
              content: const Text(
                '24h session inactive. Only template messages until the customer replies.',
              ),
              actions: [
                TextButton(
                  onPressed: _openTemplateDialog,
                  child: const Text('Template'),
                ),
              ],
            ),
          if (_admin)
            Container(
              width: double.infinity,
              margin: const EdgeInsets.fromLTRB(12, 8, 12, 0),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  const Text('Assign:', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: DropdownButtonFormField<int?>(
                      key: ValueKey<int?>(_conv.assignedTo),
                      initialValue: _conv.assignedTo,
                      decoration: const InputDecoration(
                        isDense: true,
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('Unassigned'),
                        ),
                        ..._agents.map(
                          (a) => DropdownMenuItem<int?>(
                            value: a.id,
                            child: Text(a.name),
                          ),
                        ),
                      ],
                      onChanged: (v) => _assign(v),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.green))
                : _error != null
                    ? Center(child: Text(_error!))
                    : Container(
                        color: const Color(0xFFECE5DD),
                        child: ListView.builder(
                        controller: _scroll,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                        itemCount: _messages.length,
                        itemBuilder: (context, i) {
                          final m = _messages[i];
                          final align =
                              m.isAgent ? Alignment.centerRight : Alignment.centerLeft;
                          final bg = m.isAgent ? AppColors.green : Colors.white;
                          return Align(
                            alignment: align,
                            child: ConstrainedBox(
                              constraints: BoxConstraints(
                                maxWidth: MediaQuery.sizeOf(context).width * 0.82,
                              ),
                              child: DecoratedBox(
                                decoration: BoxDecoration(
                                  color: bg,
                                  borderRadius: BorderRadius.circular(12),
                                  border: m.isAgent
                                      ? null
                                      : Border.all(color: AppColors.border),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.04),
                                      blurRadius: 4,
                                      offset: const Offset(0, 1),
                                    ),
                                  ],
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.all(10),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      _MessageBody(message: m, audioPlayer: _audio),
                                      const SizedBox(height: 4),
                                      Text(
                                        df.format(m.createdAt.toLocal()),
                                        style: TextStyle(
                                          fontSize: 10,
                                          color: m.isAgent
                                              ? Colors.white70
                                              : AppColors.muted,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                      ),
          ),
          if (_sessionActive) ...[
            if (_holdRecording)
              Material(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.fiber_manual_record, color: Colors.red.shade700, size: 14),
                      const SizedBox(width: 10),
                      Text(
                        'Recording… release to send',
                        style: TextStyle(
                          color: Colors.red.shade900,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            WhatsAppChatComposer(
              controller: _text,
              recordingHold: _holdRecording,
              onSend: _sendText,
              onAttach: _showAttachSheet,
              onHoldRecordStart: _beginHoldRecord,
              onHoldRecordEnd: _endHoldRecord,
            ),
          ] else
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Chat is locked until the customer sends a message (24h session). You can still send an approved template.',
                    style: TextStyle(fontSize: 13, color: Colors.grey.shade800),
                  ),
                  const SizedBox(height: 10),
                  FilledButton.icon(
                    onPressed: _openTemplateDialog,
                    icon: const Icon(Icons.article_outlined),
                    label: const Text('Send template message'),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  void _showAttachSheet() {
    showModalBottomSheet<void>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera),
              title: const Text('Camera'),
              onTap: () {
                Navigator.pop(ctx);
                _pick(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Gallery'),
              onTap: () {
                Navigator.pop(ctx);
                _pick(ImageSource.gallery);
              },
            ),
            ListTile(
              leading: const Icon(Icons.attach_file),
              title: const Text('File'),
              onTap: () {
                Navigator.pop(ctx);
                _pickFile();
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _MessageBody extends StatelessWidget {
  const _MessageBody({required this.message, required this.audioPlayer});

  final ChatMessage message;
  final AudioPlayer audioPlayer;

  @override
  Widget build(BuildContext context) {
    final isAgent = message.isAgent;

    if (message.messageType == 'image' && message.mediaUrl != null) {
      return Image.network(
        message.mediaUrl!,
        errorBuilder: (_, __, ___) => const Text('[Image]'),
      );
    }
    if (message.messageType == 'audio' && message.mediaUrl != null) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: Icon(Icons.play_arrow, color: isAgent ? Colors.white : AppColors.green),
            onPressed: () async {
              await audioPlayer.play(UrlSource(message.mediaUrl!));
            },
          ),
          Text('Voice / audio', style: TextStyle(color: isAgent ? Colors.white : AppColors.text)),
        ],
      );
    }
    if (message.messageType == 'video' && message.mediaUrl != null) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.videocam, size: 40, color: isAgent ? Colors.white : AppColors.muted),
          TextButton(
            onPressed: () => launchUrl(Uri.parse(message.mediaUrl!)),
            child: Text('Open video', style: TextStyle(color: isAgent ? Colors.white : AppColors.green)),
          ),
        ],
      );
    }
    if (message.messageType == 'document' && message.mediaUrl != null) {
      return TextButton.icon(
        onPressed: () => launchUrl(Uri.parse(message.mediaUrl!)),
        icon: Icon(Icons.description, color: isAgent ? Colors.white : AppColors.green),
        label: Text(
          message.fileName ?? 'Document',
          style: TextStyle(color: isAgent ? Colors.white : AppColors.green),
        ),
      );
    }
    return Text(
      message.content ?? '',
      style: TextStyle(color: isAgent ? Colors.white : AppColors.text),
    );
  }
}

