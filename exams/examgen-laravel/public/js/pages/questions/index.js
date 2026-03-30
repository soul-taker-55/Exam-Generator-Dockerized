// public/js/pages/questions/index.js
// منطق تبويب "Questions" (قائمة/بحث/فلترة - Placeholder بسيط الآن)
(function(){
  function init(root, ctx){
    if(!root) return;
    const list = root.querySelector('.qs-index-list');
    const info = root.querySelector('.qs-index-info');

    // Placeholder: نعرض subjectId إن وجد
    if(info){
      info.textContent = ctx?.subjectId ? `Subject #${ctx.subjectId}` : 'All subjects';
    }

    // إن احتجت لاحقاً لربط select/filters أضف هنا…
    // مثال: listener للروابط الداخلية داخل index
    root.addEventListener('click', (e)=>{
      const a = e.target.closest('a[href]');
      if(!a) return;
      // يترك المعالجة الافتراضية للـexams/form.js لاعتراضها.
    });
  }

  window.QS_Index = { init };
})();
