import 'package:flutter/material.dart';

import 'theme/parrot_theme.dart';

class WaSupportApp extends StatelessWidget {
  const WaSupportApp({super.key, required this.home});

  final Widget home;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WA Support',
      debugShowCheckedModeBanner: false,
      theme: buildParrotTheme(),
      home: home,
    );
  }
}
