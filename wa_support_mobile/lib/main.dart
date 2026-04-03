import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'app.dart';
import 'branding/app_brand.dart';
import 'config/app_colors.dart';
import 'firebase_options.dart';
import 'providers/app_state.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'services/push_notification_service.dart';
import 'widgets/parrot_brand_logo.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  var firebaseOk = false;
  if (!DefaultFirebaseOptions.isConfigured) {
    debugPrint(
      'Firebase not configured: in wa_support_mobile run: dart pub global activate '
      'flutterfire_cli && flutterfire configure — or add Android app in Firebase Console '
      'and place google-services.json under android/app/.',
    );
  } else {
    try {
      await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
      firebaseOk = true;
    } catch (e, st) {
      debugPrint('Firebase init failed ($e)');
      debugPrint('$st');
    }
  }

  if (firebaseOk) {
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  }

  runApp(
    ChangeNotifierProvider(
      create: (_) => AppState()..bootstrap(),
      child: const WaSupportApp(
        home: _Root(),
      ),
    ),
  );
}

class _Root extends StatefulWidget {
  const _Root();

  @override
  State<_Root> createState() => _RootState();
}

class _RootState extends State<_Root> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      final app = context.read<AppState>();
      unawaited(app.syncFcmTokenOnResume());
    }
  }

  @override
  Widget build(BuildContext context) {
    final app = context.watch<AppState>();
    if (app.loading) {
      return Scaffold(
        backgroundColor: Colors.white,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.green.withValues(alpha: 0.2),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: ClipOval(
                  child: Image.asset(
                    AppBrand.logoAsset,
                    width: 88,
                    height: 88,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) =>
                        const ParrotBrandMark(size: 80),
                  ),
                ),
              ),
              const SizedBox(height: 28),
              const SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(
                  color: AppColors.green,
                  strokeWidth: 2.5,
                ),
              ),
            ],
          ),
        ),
      );
    }
    if (app.user == null) {
      return const LoginScreen();
    }
    return const HomeShell();
  }
}
