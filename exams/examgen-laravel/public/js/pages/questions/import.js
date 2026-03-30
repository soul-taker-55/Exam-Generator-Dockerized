// public/js/pages/questions/import.js
// تبويب Import (Placeholder)
(function(){
  function init(root){
    if(!root) return;
    const box = root.querySelector('.qs-import-box');
    if(box){
      box.innerHTML = `
        <div class="alert alert-info" style="margin:10px 0;">
          <i class="fa-solid fa-circle-info"></i>
          Upcoming Versions — bulk import via CSV/Excel and AI-assisted parsing.
        </div>
      `;
    }
  }
  window.QS_Import = { init };
})();
