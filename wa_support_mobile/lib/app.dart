import 'package:flutter/material.dart';

import 'branding/app_brand.dart';
import 'theme/parrot_theme.dart';

class WaSupportApp extends StatelessWidget {
  const WaSupportApp({super.key, required this.home});

  final Widget home;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: AppBrand.appName,
      debugShowCheckedModeBanner: false,
      theme: buildParrotTheme(),
      home: home,
    );
  }
}
