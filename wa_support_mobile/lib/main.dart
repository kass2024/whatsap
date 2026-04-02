import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'app.dart';
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
  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
    firebaseOk = true;
  } catch (e, st) {
    debugPrint('Firebase init skipped ($e)');
    debugPrint('$st');
  }

  if (firebaseOk) {
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  }

  runApp(
    ChangeNotifierProvider(
      create: (_) => AppState()..bootstrap(),
      child: WaSupportApp(
        home: const _Root(),
      ),
    ),
  );
}

class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final app = context.watch<AppState>();
    if (app.loading) {
      return const Scaffold(
        backgroundColor: AppColors.pageBg,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const ParrotBrandMark(size: 72),
              SizedBox(height: 28),
              SizedBox(
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
