// pjax.js — تنقل داخلي بدون Reload كامل مع الحفاظ على الصوت
(function(){
  const APP_SEL = '#app';

  function isInternalLink(a){
    if (!a || a.target === '_blank' || a.download) return false;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    try {
      const url = new URL(href, window.location.href);
      return url.origin === window.location.origin;
    } catch { return false; }
  }

  async function navigateTo(url, push=true){
    const app = document.querySelector(APP_SEL);
    if (!app) return window.location.assign(url); // احتياط

    const res = await fetch(url, { headers: { 'X-PJAX': 'true' } });
    if (!res.ok) { window.location.assign(url); return; }
    const html = await res.text();

    // نقرأ HTML الهدف ونستخرج منه #app
    const tmp = document.createElement('html');
    tmp.innerHTML = html;
    const newApp = tmp.querySelector(APP_SEL);
    const newTitle = tmp.querySelector('title');

    if (!newApp) { window.location.assign(url); return; }

    // استبدل محتوى #app فقط
    app.replaceChildren(...Array.from(newApp.childNodes));

    // حدث العنوان
    if (newTitle) document.title = newTitle.textContent;

    // حدّث السجل
    if (push) history.pushState({ url }, document.title, url);

    // أعلم أي سكربت داخلي ان المحتوى اتغير (لو بدك تربط أحداث بعد كل انتقال)
    document.dispatchEvent(new CustomEvent('pjax:after', { detail: { url } }));
  }

  // التقاط نقرات الروابط الداخلية
  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a');
    if (!isInternalLink(a)) return;

    // استثني أي رابط ما بدك pjax يلمسه
    if (a.dataset.noPjax === '1') return;

    e.preventDefault();
    navigateTo(a.href, true).catch(()=> window.location.assign(a.href));
  });

  // أزرار الرجوع/التقدم
  window.addEventListener('popstate', (e)=>{
    const url = (e.state && e.state.url) ? e.state.url : window.location.href;
    navigateTo(url, false).catch(()=> window.location.assign(url));
  });

  // اختياري: مرجع للأعلى بعد كل انتقال
  document.addEventListener('pjax:after', ()=> window.scrollTo({ top: 0, behavior: 'instant' }));
})();
