/// Page de résultats d'une liste paginée Laravel (`data` + `meta`).
class Paginated<T> {
  const Paginated({required this.items, required this.currentPage, required this.lastPage});

  final List<T> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  static Paginated<T> fromJson<T>(Map<String, dynamic> json, T Function(Map<String, dynamic>) item) {
    final meta = (json['meta'] as Map<String, dynamic>?) ?? const {};
    return Paginated(
      items: ((json['data'] as List?) ?? const []).cast<Map<String, dynamic>>().map(item).toList(),
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
    );
  }
}
