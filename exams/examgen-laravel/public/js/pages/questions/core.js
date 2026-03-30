// public/js/pages/questions/core.js
// Namespace: window.QS
(function(){
  const NS = {
    // تحميل ديناميكي لملفات JS الخاصة بكل تبويب عند الحاجة (مرة واحدة)
    _loaded: new Set(),
    _loadOnce(src){
      return new Promise((resolve, reject)=>{
        if(NS._loaded.has(src)) return resolve();
        const s = document.createElement('script');
        s.src = src + (src.includes('?') ? '' : ('?v=' + Date.now()));
        s.defer = true;
        s.onload = ()=>{ NS._loaded.add(src); resolve(); };
        s.onerror = ()=> reject(new Error('Failed to load '+src));
        document.head.appendChild(s);
      });
    },

    // تفعيل سكربتات inline إن وُجدت (نستخدمها احتياطاً)
    _runFragmentScripts(root){
      const scripts = Array.from(root.querySelectorAll('script'));
      scripts.forEach(old=>{
        const s = document.createElement('script');
        for(const a of old.attributes) s.setAttribute(a.name, a.value);
        if(old.src){ s.src = old.src; s.defer = old.defer; }
        else s.textContent = old.textContent;
        old.parentNode.replaceChild(s, old);
      });
    },

    // دالة التهيئة العامة: تُنادى من exams/form.js بعد كل fetch
    async hydrate(root, ctx={}){
      if(!root) return;
      // شغّل أي سكربتات inline داخل الفراغ (احتياطي)
      NS._runFragmentScripts(root);

      // كشف التبويب حسب الـID الموجود بجذر الفراغ
      const isNew    = !!root.querySelector('#qsNew');
      const isIndex  = !!root.querySelector('#qsIndex');
      const isImport = !!root.querySelector('#qsImport');

      try{
        if(isNew){
          await NS._loadOnce('/js/pages/questions/new.js');
          window.QS_New && window.QS_New.init(root.querySelector('#qsNew'), ctx);
        }else if(isIndex){
          await NS._loadOnce('/js/pages/questions/index.js');
          window.QS_Index && window.QS_Index.init(root.querySelector('#qsIndex'), ctx);
        }else if(isImport){
          await NS._loadOnce('/js/pages/questions/import.js');
          window.QS_Import && window.QS_Import.init(root.querySelector('#qsImport'), ctx);
        }
      }catch(e){
        console.error(e);
        const container = root.firstElementChild || root;
        container.insertAdjacentHTML('afterbegin',
          `<div class="alert alert-error">Failed to initialize tab. ${e.message||''}</div>`);
      }
    }
  };

  window.QS = NS;
})();
