import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../config/app_colors.dart';

/// WhatsApp-like bottom composer: [+] attach, rounded text field, send or hold-to-record mic.
class WhatsAppChatComposer extends StatefulWidget {
  const WhatsAppChatComposer({
    super.key,
    required this.controller,
    required this.onSend,
    required this.onAttach,
    required this.onHoldRecordStart,
    required this.onHoldRecordEnd,
    this.recordingHold = false,
    this.enabled = true,
  });

  final TextEditingController controller;
  final VoidCallback onSend;
  final VoidCallback onAttach;
  final Future<void> Function() onHoldRecordStart;
  final Future<void> Function() onHoldRecordEnd;
  final bool recordingHold;
  final bool enabled;

  @override
  State<WhatsAppChatComposer> createState() => _WhatsAppChatComposerState();
}

class _WhatsAppChatComposerState extends State<WhatsAppChatComposer> {
  var _hasText = false;

  @override
  void initState() {
    super.initState();
    _hasText = widget.controller.text.trim().isNotEmpty;
    widget.controller.addListener(_onText);
  }

  void _onText() {
    final next = widget.controller.text.trim().isNotEmpty;
    if (next != _hasText) {
      setState(() => _hasText = next);
    }
  }

  @override
  void dispose() {
    widget.controller.removeListener(_onText);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom;
    const footerBg = Color(0xFFF0F2F5);
    const iconMuted = Color(0xFF54656F);

    return Material(
      color: footerBg,
      child: Padding(
        padding: EdgeInsets.fromLTRB(6, 6, 6, bottom + 6),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Material(
              color: Colors.white,
              shape: const CircleBorder(),
              clipBehavior: Clip.antiAlias,
              child: IconButton(
                visualDensity: VisualDensity.compact,
                padding: const EdgeInsets.all(10),
                onPressed: widget.enabled ? widget.onAttach : null,
                icon: Icon(
                  Icons.add,
                  color: widget.enabled ? iconMuted : iconMuted.withValues(alpha: 0.4),
                  size: 26,
                ),
                tooltip: 'Attach',
              ),
            ),
            const SizedBox(width: 6),
            Expanded(
              child: Container(
                constraints: const BoxConstraints(minHeight: 44),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.06),
                      blurRadius: 4,
                      offset: const Offset(0, 1),
                    ),
                  ],
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: widget.controller,
                        enabled: widget.enabled,
                        keyboardType: TextInputType.multiline,
                        textCapitalization: TextCapitalization.sentences,
                        minLines: 1,
                        maxLines: 6,
                        style: const TextStyle(fontSize: 16, height: 1.35),
                        decoration: InputDecoration(
                          hintText: widget.enabled ? 'Message' : 'Messaging locked',
                          hintStyle: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 16,
                          ),
                          isDense: true,
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 10,
                          ),
                        ),
                        onSubmitted: widget.enabled
                            ? (_) {
                                HapticFeedback.lightImpact();
                                widget.onSend();
                              }
                            : null,
                      ),
                    ),
                    if (widget.enabled && _hasText) ...[
                      Padding(
                        padding: const EdgeInsets.only(bottom: 4, right: 4),
                        child: Material(
                          color: AppColors.green,
                          shape: const CircleBorder(),
                          child: InkWell(
                            customBorder: const CircleBorder(),
                            onTap: () {
                              HapticFeedback.lightImpact();
                              widget.onSend();
                            },
                            child: const Padding(
                              padding: EdgeInsets.all(8),
                              child: Icon(
                                Icons.send_rounded,
                                color: Colors.white,
                                size: 22,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(width: 6),
            if (widget.enabled && !_hasText)
              _MicButton(
                recording: widget.recordingHold,
                onLongPressStart: () async {
                  HapticFeedback.mediumImpact();
                  await widget.onHoldRecordStart();
                },
                onLongPressEnd: () async {
                  HapticFeedback.selectionClick();
                  await widget.onHoldRecordEnd();
                },
              ),
          ],
        ),
      ),
    );
  }
}

class _MicButton extends StatelessWidget {
  const _MicButton({
    required this.recording,
    required this.onLongPressStart,
    required this.onLongPressEnd,
  });

  final bool recording;
  final Future<void> Function() onLongPressStart;
  final Future<void> Function() onLongPressEnd;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: 'Hold to record',
      child: GestureDetector(
        onLongPressStart: (_) => onLongPressStart(),
        onLongPressEnd: (_) => onLongPressEnd(),
        child: Material(
          color: recording ? Colors.red.shade100 : Colors.white,
          shape: const CircleBorder(),
          clipBehavior: Clip.antiAlias,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Icon(
              Icons.mic_rounded,
              color: recording ? Colors.red.shade700 : const Color(0xFF54656F),
              size: 26,
            ),
          ),
        ),
      ),
    );
  }
}
