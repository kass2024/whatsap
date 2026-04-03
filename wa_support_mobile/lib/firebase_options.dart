// Generated from android/app/google-services.json (client: com.example.wa_support_mobile).
// Re-run `flutterfire configure` if you add iOS/Web or change Firebase apps.
//
// ignore_for_file: lines_longer_than_80_chars

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static bool get isConfigured {
    const android = DefaultFirebaseOptions.android;
    return android.projectId.isNotEmpty &&
        android.projectId != 'your-project-id' &&
        !android.apiKey.startsWith('REPLACE_') &&
        !android.appId.contains(':000000000000:');
  }

  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      case TargetPlatform.macOS:
        return macos;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not configured for this platform.',
        );
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyDE6Bq4mPbDMo0VEsVz3mKfUoqdRQlBtMQ',
    appId: '1:309472083581:web:0000000000000000000000',
    messagingSenderId: '309472083581',
    projectId: 'parrotchatsupport',
    authDomain: 'parrotchatsupport.firebaseapp.com',
    storageBucket: 'parrotchatsupport.firebasestorage.app',
  );

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyDE6Bq4mPbDMo0VEsVz3mKfUoqdRQlBtMQ',
    appId: '1:309472083581:android:a059f506f32c659e1c7b14',
    messagingSenderId: '309472083581',
    projectId: 'parrotchatsupport',
    storageBucket: 'parrotchatsupport.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyDE6Bq4mPbDMo0VEsVz3mKfUoqdRQlBtMQ',
    appId: '1:309472083581:ios:0000000000000000000000',
    messagingSenderId: '309472083581',
    projectId: 'parrotchatsupport',
    storageBucket: 'parrotchatsupport.firebasestorage.app',
    iosBundleId: 'com.example.waSupportMobile',
  );

  static const FirebaseOptions macos = FirebaseOptions(
    apiKey: 'AIzaSyDE6Bq4mPbDMo0VEsVz3mKfUoqdRQlBtMQ',
    appId: '1:309472083581:ios:0000000000000000000000',
    messagingSenderId: '309472083581',
    projectId: 'parrotchatsupport',
    storageBucket: 'parrotchatsupport.firebasestorage.app',
    iosBundleId: 'com.example.waSupportMobile',
  );
}
