import 'package:flutter/material.dart';

/// Parrot / WA Support web palette (matches `public/css/parrot-app.css`).
abstract final class AppColors {
  static const Color green = Color(0xFF427431);
  static const Color greenHover = Color(0xFF356A2A);
  static const Color greenDeep = Color(0xFF1E3D18);
  /// App bar / logo gradient (Parrot green)
  static const Color brandGradientStart = Color(0xFF4F8A42);
  static const Color brandGradientEnd = Color(0xFF2E6B24);
  /// WhatsApp-inspired accent (chips, success hints only)
  static const Color waAccent = Color(0xFF25D366);
  static const Color sidebar = Color(0xFF1A3320);
  static const Color sidebarEnd = Color(0xFF0F1F14);
  static const Color pageBg = Color(0xFFF1F5F9);
  static const Color border = Color(0xFFE2E8F0);
  static const Color muted = Color(0xFF64748B);
  static const Color text = Color(0xFF0F172A);
  static const Color adminBadgeBg = Color(0xFFFEF3C7);
  static const Color adminBadgeFg = Color(0xFFB45309);
}
