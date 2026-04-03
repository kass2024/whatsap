import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../branding/app_brand.dart';
import '../config/app_colors.dart';
import '../providers/app_state.dart';
import '../services/chat_service.dart';
import '../services/notification_navigation.dart';
import '../services/push_notification_service.dart';
import '../widgets/parrot_brand_logo.dart';
import '../widgets/parrot_drawer.dart';
import 'admin_phones_screen.dart';
import 'chat_detail_screen.dart';
import 'chat_list_screen.dart';
import 'dashboard_screen.dart';
import 'profile_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> with WidgetsBindingObserver {
  int _index = 0;
  late final VoidCallback _onNotificationConversation;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _onNotificationConversation = _handlePendingConversationFromNotification;
    notificationConversationIdToOpen.addListener(_onNotificationConversation);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      unawaited(attachFirebaseNotificationOpenHandlers());
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    notificationConversationIdToOpen.removeListener(_onNotificationConversation);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && mounted) {
      unawaited(context.read<AppState>().syncFcmTokenIfLoggedIn());
    }
  }

  void _handlePendingConversationFromNotification() {
    final id = notificationConversationIdToOpen.value;
    if (id == null) {
      return;
    }
    notificationConversationIdToOpen.value = null;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) {
        return;
      }
      unawaited(_openConversationFromPush(id));
    });
  }

  Future<void> _openConversationFromPush(int conversationId) async {
    final ChatService chat = context.read<AppState>().chat;
    try {
      final conv = await chat.fetchConversation(conversationId);
      if (!mounted) {
        return;
      }
      setState(() => _index = 1);
      await Navigator.of(context).push<void>(
        MaterialPageRoute<void>(
          builder: (_) => ChatDetailScreen(conversation: conv),
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AppState>().user?.role == 'admin';

    final titles = <String>[
      'Dashboard',
      'Inbox',
      if (admin) 'Admin-only numbers',
      'Profile',
    ];

    final pages = <Widget>[
      DashboardScreen(
        onOpenInbox: () => setState(() => _index = 1),
      ),
      const ChatListScreen(),
      if (admin) const AdminPhonesScreen(),
      const ProfileScreen(),
    ];

    final maxIndex = pages.length - 1;
    final safeIndex = _index.clamp(0, maxIndex);

    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        titleSpacing: 8,
        surfaceTintColor: Colors.transparent,
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                AppBrand.logoAsset,
                width: 36,
                height: 36,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) =>
                    const ParrotBrandMark(size: 34),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                titles[safeIndex],
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: AppColors.brandBlack,
                ),
              ),
            ),
          ],
        ),
      ),
      drawer: ParrotDrawer(
        currentIndex: _index,
        onSelect: (i) {
          setState(() => _index = i);
          Navigator.of(context).pop();
        },
      ),
      body: IndexedStack(
        index: safeIndex,
        children: pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: safeIndex,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          const NavigationDestination(
            icon: Icon(Icons.chat_bubble_outline),
            selectedIcon: Icon(Icons.chat_bubble),
            label: 'Chats',
          ),
          if (admin)
            const NavigationDestination(
              icon: Icon(Icons.shield_outlined),
              selectedIcon: Icon(Icons.shield),
              label: 'Admin',
            ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}
