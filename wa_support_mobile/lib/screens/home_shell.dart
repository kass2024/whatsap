import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../config/app_colors.dart';
import '../providers/app_state.dart';
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

    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        title: Text(titles[_index]),
      ),
      drawer: ParrotDrawer(
        currentIndex: _index,
        onSelect: (i) {
          setState(() => _index = i);
          Navigator.of(context).pop();
        },
      ),
      body: IndexedStack(
        index: _index.clamp(0, pages.length - 1),
        children: pages,
      ),
    );
  }
}
