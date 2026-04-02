import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:provider/provider.dart';

import '../config/app_colors.dart';
import '../providers/app_state.dart';
import '../services/settings_service.dart';

class _Entry {
  _Entry({required this.phone, this.label});

  String phone;
  String? label;
}

class AdminPhonesScreen extends StatefulWidget {
  const AdminPhonesScreen({super.key});

  @override
  State<AdminPhonesScreen> createState() => _AdminPhonesScreenState();
}

class _AdminPhonesScreenState extends State<AdminPhonesScreen> {
  final List<_Entry> _entries = [];
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final p = await context.read<AppState>().settings.fetchAdminPhones();
      if (mounted) {
        setState(() {
          _entries
            ..clear()
            ..addAll(
              p.items.map((e) => _Entry(phone: e.phone, label: e.label)),
            );
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _error = '$e');
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final items = _entries
          .map(
            (e) => AdminPhoneItem(
              phone: _normalizePhone(e.phone),
              label: e.label?.trim().isEmpty == true ? null : e.label?.trim(),
            ),
          )
          .where((e) => e.phone.isNotEmpty)
          .toList();
      await context.read<AppState>().settings.saveAdminPhonesItems(items);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Restricted numbers saved.')),
        );
        await _load();
      }
    } catch (e) {
      if (mounted) {
        setState(() => _error = '$e');
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  String _normalizePhone(String raw) {
    final d = raw.replaceAll(RegExp(r'\D'), '');
    return d;
  }

  Future<void> _addManual() async {
    final phoneCtrl = TextEditingController();
    final labelCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Add number'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Phone (digits / E.164)',
                hintText: '447911123456',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: labelCtrl,
              decoration: const InputDecoration(
                labelText: 'Label (optional)',
                hintText: 'VIP client',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Add')),
        ],
      ),
    );
    if (ok != true || !mounted) {
      return;
    }
    final p = _normalizePhone(phoneCtrl.text);
    if (p.length < 8) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Enter a valid phone number.')),
      );
      return;
    }
    setState(() {
      _entries.add(_Entry(phone: p, label: labelCtrl.text.trim().isEmpty ? null : labelCtrl.text.trim()));
    });
  }

  Future<void> _pickFromContacts() async {
    final granted = await FlutterContacts.requestPermission();
    if (!granted) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Allow contacts access to pick a number.')),
        );
      }
      return;
    }
    var list = await FlutterContacts.getContacts(withProperties: true);
    list = list.where((c) => c.phones.isNotEmpty).toList()
      ..sort((a, b) => a.displayName.toLowerCase().compareTo(b.displayName.toLowerCase()));

    if (!mounted) {
      return;
    }
    final picked = await showModalBottomSheet<Contact>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.75,
        maxChildSize: 0.95,
        minChildSize: 0.4,
        builder: (_, scroll) => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: Text(
                'Pick a contact',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
            ),
            Expanded(
              child: ListView.builder(
                controller: scroll,
                itemCount: list.length,
                itemBuilder: (_, i) {
                  final c = list[i];
                  final phone = c.phones.first.number;
                  return ListTile(
                    title: Text(c.displayName),
                    subtitle: Text(phone),
                    onTap: () => Navigator.pop(ctx, c),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
    if (picked == null || !mounted) {
      return;
    }
    final raw = picked.phones.first.number;
    final p = _normalizePhone(raw);
    if (p.length < 8) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not read a valid number from this contact.')),
      );
      return;
    }
    final label = picked.displayName.trim().isEmpty ? null : picked.displayName.trim();
    setState(() {
      _entries.add(_Entry(phone: p, label: label));
    });
  }

  Future<void> _edit(int index) async {
    final e = _entries[index];
    final phoneCtrl = TextEditingController(text: e.phone);
    final labelCtrl = TextEditingController(text: e.label ?? '');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Edit entry'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Phone'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: labelCtrl,
              decoration: const InputDecoration(labelText: 'Label (optional)'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              setState(() => _entries.removeAt(index));
              Navigator.pop(ctx, false);
            },
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Save')),
        ],
      ),
    );
    if (ok != true || !mounted) {
      return;
    }
    final p = _normalizePhone(phoneCtrl.text);
    if (p.length < 8) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid phone.')),
      );
      return;
    }
    setState(() {
      _entries[index] = _Entry(
        phone: p,
        label: labelCtrl.text.trim().isEmpty ? null : labelCtrl.text.trim(),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: AppColors.green),
      );
    }

    return ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFFCD34D)),
            ),
            child: const Text(
              'Agents never see chats for numbers on this list. Only administrators can open or be assigned to these threads. Use digits only or pick from contacts.',
              style: TextStyle(
                color: Color(0xFF92400E),
                fontSize: 13,
                height: 1.35,
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              const Text(
                'Restricted numbers',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                  color: AppColors.text,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                '(${_entries.length})',
                style: const TextStyle(color: AppColors.muted),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              FilledButton.tonalIcon(
                onPressed: _addManual,
                icon: const Icon(Icons.edit_outlined, size: 18),
                label: const Text('Add manually'),
              ),
              const SizedBox(width: 8),
              FilledButton.icon(
                onPressed: _pickFromContacts,
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.green,
                  foregroundColor: Colors.white,
                ),
                icon: const Icon(Icons.import_contacts_outlined, size: 18),
                label: const Text('From contacts'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (_entries.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text(
                  'No restricted numbers yet.',
                  style: TextStyle(color: AppColors.muted),
                ),
              ),
            )
          else
            ...List.generate(_entries.length, (i) {
              final e = _entries[i];
              final sub = e.label != null && e.label!.isNotEmpty ? e.label! : 'No label';
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(
                    e.phone,
                    style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(sub),
                  trailing: IconButton(
                    icon: const Icon(Icons.edit_outlined),
                    onPressed: () => _edit(i),
                  ),
                  onTap: () => _edit(i),
                ),
              );
            }),
          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(_error!, style: const TextStyle(color: Colors.red)),
          ],
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: _saving
                ? const SizedBox(
                    height: 22,
                    width: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Text('Save list'),
          ),
        ],
    );
  }
}
