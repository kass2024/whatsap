import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:permission_handler/permission_handler.dart';
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
        context.read<AppState>().bumpConversationListRefresh();
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
                labelText: 'Phone',
                hintText: 'Digits only',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: labelCtrl,
              decoration: const InputDecoration(
                labelText: 'Label (optional)',
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
    try {
      if (kIsWeb) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Contacts are not available on web.')),
          );
        }
        return;
      }

      var granted = false;
      if (defaultTargetPlatform == TargetPlatform.android) {
        var status = await Permission.contacts.request();
        if (!status.isGranted) {
          if (status.isPermanentlyDenied && mounted) {
            await openAppSettings();
          }
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: const Text(
                  'Contacts permission is required. Enable it in system settings for this app.',
                ),
                action: SnackBarAction(
                  label: 'Settings',
                  onPressed: openAppSettings,
                ),
              ),
            );
          }
          return;
        }
        granted = await FlutterContacts.requestPermission(readonly: true);
      } else {
        granted = await FlutterContacts.requestPermission(readonly: true);
      }

      if (!granted) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Allow contacts access in the system prompt when asked.'),
            ),
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
      if (list.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No contacts with phone numbers found.')),
        );
        return;
      }

      final picked = await showModalBottomSheet<Contact>(
        context: context,
        isScrollControlled: true,
        useSafeArea: true,
        builder: (sheetCtx) {
          final h = MediaQuery.of(sheetCtx).size.height * 0.78;
          return SafeArea(
            child: SizedBox(
              height: h,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                    child: Text(
                      'Pick a contact',
                      style: Theme.of(sheetCtx).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                  ),
                  Expanded(
                    child: ListView.builder(
                      itemCount: list.length,
                      itemBuilder: (_, i) {
                        final c = list[i];
                        final phone = c.phones.first.number;
                        return ListTile(
                          title: Text(c.displayName),
                          subtitle: Text(phone),
                          onTap: () => Navigator.pop(sheetCtx, c),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          );
        },
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
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Contacts error: $e')),
        );
      }
    }
  }

  Future<void> _confirmDelete(int index) async {
    final e = _entries[index];
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Remove from restricted list?'),
        content: Text('${e.phone} will no longer be admin-only.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (ok == true && mounted) {
      setState(() => _entries.removeAt(index));
    }
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
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.green.withValues(alpha: 0.35)),
              boxShadow: [
                BoxShadow(
                  color: AppColors.brandBlack.withValues(alpha: 0.04),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.shield_outlined, color: AppColors.brandRed, size: 22),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Agents never see chats for numbers on this list. Only administrators can open or be assigned. Use digits or pick from contacts.',
                    style: TextStyle(
                      color: AppColors.text,
                      fontSize: 13,
                      height: 1.4,
                    ),
                  ),
                ),
              ],
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
          Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              FilledButton.tonalIcon(
                onPressed: _addManual,
                icon: const Icon(Icons.edit_outlined, size: 18),
                label: const Text('Add manually'),
              ),
              const SizedBox(height: 10),
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
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        tooltip: 'Remove',
                        icon: Icon(Icons.delete_outline, color: Colors.red.shade700),
                        onPressed: () => _confirmDelete(i),
                      ),
                      IconButton(
                        tooltip: 'Edit',
                        icon: const Icon(Icons.edit_outlined),
                        onPressed: () => _edit(i),
                      ),
                    ],
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
