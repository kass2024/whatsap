import 'package:flutter/material.dart';

import '../branding/app_brand.dart';
import '../config/app_colors.dart';

/// Parrot Canada × chat bubble — readable at any size, no raster assets.
class ParrotBrandMark extends StatelessWidget {
  const ParrotBrandMark({super.key, this.size = 48});

  final double size;

  @override
  Widget build(BuildContext context) {
    final r = BorderRadius.circular(size * 0.22);
    return Semantics(
      label: AppBrand.appName,
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          borderRadius: r,
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              AppColors.brandGradientStart,
              AppColors.brandGradientEnd,
            ],
          ),
          boxShadow: [
            BoxShadow(
              color: AppColors.green.withValues(alpha: 0.32),
              blurRadius: size * 0.12,
              offset: Offset(0, size * 0.05),
            ),
          ],
        ),
        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.center,
          children: [
            Positioned(
              right: size * 0.08,
              bottom: size * 0.1,
              child: Container(
                width: size * 0.3,
                height: size * 0.24,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.22),
                  borderRadius: BorderRadius.circular(size * 0.07),
                ),
              ),
            ),
            Icon(
              Icons.forum_rounded,
              color: Colors.white,
              size: size * 0.46,
              shadows: const [
                Shadow(
                  color: Color(0x33000000),
                  blurRadius: 4,
                  offset: Offset(0, 1),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Login / splash: logo + wordmark.
class ParrotBrandHero extends StatelessWidget {
  const ParrotBrandHero({super.key, this.logoSize = 72});

  final double logoSize;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ParrotBrandMark(size: logoSize),
        const SizedBox(height: 20),
        Text(
          AppBrand.appName,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.6,
            color: AppColors.text,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          AppBrand.shortSubtitle,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 14,
            height: 1.35,
            color: Color(0xFF475569),
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}
