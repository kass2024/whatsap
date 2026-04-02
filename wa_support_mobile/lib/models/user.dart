class User {
  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });

  final int id;
  final String name;
  final String email;
  final String role;

  factory User.fromJson(Map<String, dynamic> j) {
    return User(
      id: j['id'] as int,
      name: j['name'] as String,
      email: j['email'] as String,
      role: j['role'] as String,
    );
  }
}
