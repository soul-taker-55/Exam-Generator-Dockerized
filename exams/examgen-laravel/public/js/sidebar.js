// public/js/sidebar.js
(function () {
  const KEY = 'eg_sidebar_collapsed';
  const sidebar = document.getElementById('sidebar');
  const btn = document.getElementById('toggleSidebar');
  if (!sidebar || !btn) return;

  const icon = btn.querySelector('i');

  function apply(collapsed) {
    sidebar.classList.toggle('collapsed', collapsed);
    if (icon) {
      icon.classList.remove('fa-chevron-left','fa-chevron-right');
      icon.classList.add(collapsed ? 'fa-chevron-right' : 'fa-chevron-left');
    }
    try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch {}
  }

  // استعادة الحالة
  try { apply(localStorage.getItem(KEY) === '1'); } catch {}

  btn.addEventListener('click', () => {
    apply(!sidebar.classList.contains('collapsed'));
  });
})();
