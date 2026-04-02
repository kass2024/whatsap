import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../config/app_colors.dart';
import '../providers/app_state.dart';
import '../widgets/parrot_brand_logo.dart';
import '../widgets/parrot_drawer.dart';
import 'admin_phones_screen.dart';
import 'chat_list_screen.dart';
import 'dashboard_screen.dart';
import 'profile_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

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
        title: Row(
          children: [
            const ParrotBrandMark(size: 34),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                titles[safeIndex],
                overflow: TextOverflow.ellipsis,
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
