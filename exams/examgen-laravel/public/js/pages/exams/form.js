// Helpers
const $  = (s, root=document) => root.querySelector(s);
const $$ = (s, root=document) => Array.from(root.querySelectorAll(s));

// Elements (main page)
const subjectSel   = $("#subject_id");
const dateInput    = $("#exam_date");
const templateSel  = $("#template_type");
const previewImg   = $("#template-preview-img");
const toastEl      = $("#toast");

const filtersBar   = $("#filtersBar");
const selectHint   = $("#selectSubjectHint");
const qList        = $("#questionList");
const noQuestions  = $("#noQuestions");
const noResults    = $("#noResults");

const totalQuestionsEl = $("#totalQuestions");
const selectedCountEl  = $("#selectedCount");
const totalPointsEl    = $("#totalPoints");
const generateBtn      = $("#generateBtn");

const selectAllBtn     = $("#selectAllBtn");
const deselectAllBtn   = $("#deselectAllBtn");
const searchInput      = $("#questionSearch");
const diffFilter       = $("#difficultyFilter");

const openPanelBtn     = $("#openQuestionsPanelBtn");

// ====== In-memory sample data (demo) ======
const SUBJECT_QUESTIONS = {
  3: [],
  6: [
    { id: 1, text: "What is SQL Injection?", difficulty: 1, points: 5,  date: "2025-05-15" },
    { id: 2, text: "Explain Cross-Site Scripting (XSS).", difficulty: 2, points: 10, date: "2025-05-20" },
    { id: 3, text: "Implement Dijkstra and analyze complexity.", difficulty: 3, points: 15, date: "2025-05-25" },
  ],
  7: [
    { id: 11, text: "Define algorithm stability.", difficulty: 1, points: 5, date: "2025-05-10" },
    { id: 12, text: "Explain dynamic programming memoization.", difficulty: 2, points: 10, date: "2025-05-18" }
  ],
  8: [
    { id: 21, text: "Prolog: write a rule for ancestor/2.", difficulty: 2, points: 10, date: "2025-05-22" }
  ],
  9: [
    { id: 31, text: "Compare symmetric vs asymmetric encryption.", difficulty: 1, points: 5, date: "2025-05-12" },
    { id: 32, text: "Outline TLS handshake steps.", difficulty: 2, points: 10, date: "2025-05-19" },
    { id: 33, text: "Side-channel attacks: discuss countermeasures.", difficulty: 3, points: 15, date: "2025-05-26" },
  ]
};

// -- Toast ---
function showToast(msg, timeout=1800){
  if(!toastEl) return;
  toastEl.textContent = msg;
  toastEl.classList.add("show");
  setTimeout(()=> toastEl.classList.remove("show"), timeout);
}

// Build card
function cardHTML(q, idx){
  const diffClass = `difficulty-${q.difficulty}`;
  const diffLabel = q.difficulty===1 ? "Easy" : (q.difficulty===2 ? "Medium" : "Hard");
  return `
  <div class="question-card" data-id="${q.id}" data-difficulty="${q.difficulty}" data-points="${q.points}">
    <div class="question-header">
      <div class="question-actions">
        <input type="checkbox" id="q_${q.id}" name="questions[]" value="${q.id}">
        <label for="q_${q.id}" class="checkbox-label"></label>
      </div>
      <span class="question-serial-number">#${idx}</span>
      <div class="difficulty-badge ${diffClass}">${diffLabel}</div>
    </div>
    <div class="question-content">${q.text}</div>
    <div class="question-footer">
      <span class="date">${new Date(q.date).toLocaleDateString('en-GB',{year:'numeric',month:'short',day:'2-digit'})}</span>
      <span class="mark">${q.points} pts</span>
    </div>
  </div>`;
}

// Render by subject
function renderQuestionsForSubject(subjectId){
  selectedCountEl.textContent = "0";
  totalPointsEl.textContent   = "0";

  if(!subjectId){
    filtersBar.style.display = "none";
    qList.style.display      = "none";
    noQuestions.style.display= "none";
    noResults.style.display  = "none";
    selectHint.style.display = "flex";
    totalQuestionsEl.textContent = "0";
    updateGenerateState();
    return;
  }

  selectHint.style.display = "none";
  filtersBar.style.display = "flex";

  const data = SUBJECT_QUESTIONS[subjectId] || [];
  totalQuestionsEl.textContent = String(data.length);

  if(data.length === 0){
    qList.innerHTML = "";
    qList.style.display       = "none";
    noResults.style.display   = "none";
    noQuestions.style.display = "flex";
    updateGenerateState();
    return;
  }

  qList.innerHTML = data.map((q, i)=> cardHTML(q, i+1)).join("");
  qList.style.display       = "grid";
  noQuestions.style.display = "none";
  noResults.style.display   = "none";

  $$('#questionList input[type="checkbox"]').forEach(cb=>{
    cb.addEventListener("change", ()=>{
      updateSelectedCounters();
      updateGenerateState();
    });
  });

  filterQuestions();
  updateGenerateState();
}

// Filter logic
function filterQuestions(){
  const text = (searchInput?.value || "").toLowerCase();
  const dVal = parseInt(diffFilter?.value || "0");
  let visible = 0;

  $$(".question-card", qList).forEach(card=>{
    const cText = card.querySelector(".question-content").textContent.toLowerCase();
    const cdiff = parseInt(card.dataset.difficulty);
    const show  = cText.includes(text) && (dVal===0 || dVal===cdiff);
    card.style.display = show ? "block" : "none";
    if(show) visible++;
  });

  noResults.style.display = (visible===0 && qList.style.display !== "none") ? "flex" : "none";
}

// Selection counters
function updateSelectedCounters(){
  let count=0, pts=0;
  $$('#questionList input[name="questions[]"]').forEach(ch=>{
    if(ch.checked){
      count++;
      const card = ch.closest(".question-card");
      pts += parseInt(card?.dataset.points || "0");
    }
  });
  selectedCountEl.textContent = String(count);
  totalPointsEl.textContent   = String(pts);
}

// Enable/disable Generate
function updateGenerateState(){
  const hasSubject = !!subjectSel.value;
  const hasDate    = !!dateInput.value;
  const selected   = parseInt(selectedCountEl.textContent || "0");
  generateBtn.disabled = !(hasSubject && hasDate && selected > 0);
}

// Template preview
function showTemplatePreview(type) {
  if (!previewImg) return;
  const ds = previewImg.dataset;
  ["default-description","split-description","split-line-description"]
    .forEach(id=>{ const el = document.getElementById(id); if(el) el.style.display="none"; });

  if (type === "split") {
    previewImg.src = ds.split;
    $("#split-description").style.display = "block";
  } else if (type === "split_line") {
    previewImg.src = ds.splitLine;
    $("#split-line-description").style.display = "block";
  } else {
    previewImg.src = ds.default;
    $("#default-description").style.display = "block";
  }
}

// Submit (static)
function onSubmitExam(e){
  e.preventDefault();
  if(!dateInput.value){
    $("#errDate").style.display="block";
    setTimeout(()=> $("#errDate").style.display="none", 1600);
    return;
  }
  $("#okMsg").style.display="block";
  setTimeout(()=> $("#okMsg").style.display="none", 1600);
}

document.addEventListener("DOMContentLoaded", ()=>{
  renderQuestionsForSubject("");
  showTemplatePreview(templateSel.value);

  subjectSel.addEventListener("change", e=> renderQuestionsForSubject(e.target.value));
  dateInput.addEventListener("input", updateGenerateState);
  templateSel.addEventListener("change", e=> showTemplatePreview(e.target.value));
  searchInput.addEventListener("keyup", filterQuestions);
  diffFilter.addEventListener("change", filterQuestions);

  selectAllBtn.addEventListener("click", ()=>{
    $$('#questionList input[type="checkbox"]').forEach(cb=>cb.checked=true);
    updateSelectedCounters(); updateGenerateState();
  });
  deselectAllBtn.addEventListener("click", ()=>{
    $$('#questionList input[type="checkbox"]').forEach(cb=>cb.checked=false);
    updateSelectedCounters(); updateGenerateState();
  });

  $("#examForm").addEventListener("submit", onSubmitExam);
});

/* ================== Modal (fetch /questions/*?embed=1) ================== */
const qsModal         = document.getElementById('qsModal');
const qsBackdrop      = document.getElementById('qsModalBackdrop');
const qsClose         = document.getElementById('qsModalClose');
const qsTabs          = document.getElementById('qsTabs');
const qsContent       = document.getElementById('qsContent');
const qsLoading       = document.getElementById('qsLoading');

function getCsrf(){
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// ضف embed=1 و subject_id
function makeEmbeddedUrl(url){
  const u = new URL(url, window.location.origin);
  u.searchParams.set('embed', '1');
  const sid = subjectSel.value;
  if (sid) u.searchParams.set('subject_id', sid);
  return u.toString();
}

function openQsModal(){
  if(!subjectSel.value){
    showToast("Select a subject first");
    return;
  }
  qsModal.classList.add('show');
  const active = qsTabs.querySelector('.tab-link.active') || qsTabs.querySelector('.tab-link');
  if (active) loadTab(active);
}

function closeQsModal(){
  qsModal.classList.remove('show');
  qsContent.innerHTML = '';
}

async function loadTab(btn){
  if(!btn) return;
  qsTabs.querySelectorAll('.tab-link').forEach(b=> b.classList.remove('active'));
  btn.classList.add('active');

  const url = makeEmbeddedUrl(btn.dataset.route);
  qsLoading.style.display = 'block';
  try{
    const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const html = await res.text();
    qsContent.innerHTML = html;
    wireEmbeddedHandlers();

    // ★★★ مهم: فعّل تبويب الأسئلة المحقون
    if (window.QS && typeof window.QS.hydrate === 'function') {
      window.QS.hydrate(qsContent, { subjectId: subjectSel.value || null });
    }
  }catch(e){
    qsContent.innerHTML = `<div class="alert alert-error">Failed to load. ${e?.message||''}</div>`;
  }finally{
    qsLoading.style.display = 'none';
  }
}

function wireEmbeddedHandlers(){
  // اعتراض الروابط الداخلية ضمن اللوحة
  qsContent.querySelectorAll('a[href]').forEach(a=>{
    const href = a.getAttribute('href');
    if(!href || href.startsWith('#')) return;
    a.addEventListener('click', (ev)=>{
      const isInternal = href.startsWith('/') || href.startsWith(window.location.origin);
      if(!isInternal) return; // الروابط الخارجية تُفتح طبيعي
      ev.preventDefault();
      const active = qsTabs.querySelector('.tab-link.active');
      if(!active) return;
      const u = new URL(href, window.location.origin);
      active.dataset.route = u.pathname + u.search; // حدّث المسار
      loadTab(active);
    });
  });

  // اعتراض إرسال الفورمات
  qsContent.querySelectorAll('form').forEach(form=>{
    form.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const action = form.getAttribute('action') || qsTabs.querySelector('.tab-link.active')?.dataset.route || '';
      const method = (form.getAttribute('method') || 'POST').toUpperCase();
      const enc = form.getAttribute('enctype') || 'application/x-www-form-urlencoded';
      const headers = { 'X-Requested-With':'XMLHttpRequest' };
      const csrf = getCsrf();
      if(csrf) headers['X-CSRF-TOKEN'] = csrf;

      let body;
      if(enc.includes('multipart/form-data')){
        body = new FormData(form);
      }else{
        body = new URLSearchParams(new FormData(form));
        headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
      }

      qsLoading.style.display = 'block';
      try{
        const res = await fetch(makeEmbeddedUrl(action), { method, headers, body });
        const ct  = res.headers.get('content-type') || '';
        const txt = await res.text();

        if(ct.includes('text/html')){
          qsContent.innerHTML = txt;
          wireEmbeddedHandlers();

          // ★★★ مهم أيضاً بعد إعادة الحقن
          if (window.QS && typeof window.QS.hydrate === 'function') {
            window.QS.hydrate(qsContent, { subjectId: subjectSel.value || null });
          }
        }else{
          try{ const data = JSON.parse(txt); if(data?.message) showToast(data.message); }catch(_){}
        }

        // بعد أي تعديل ناجح — حدّث قائمة الأسئلة للـ subject الحالي
        if(subjectSel.value){
          renderQuestionsForSubject(subjectSel.value);
        }
      }catch(e){
        showToast('Operation failed');
      }finally{
        qsLoading.style.display = 'none';
      }
    });
  });
}

// فتح/إغلاق + تبديل تبويبات
openPanelBtn?.addEventListener('click', openQsModal);
qsClose?.addEventListener('click', closeQsModal);
qsBackdrop?.addEventListener('click', closeQsModal);
qsTabs?.addEventListener('click', (e)=>{
  const btn = e.target.closest('.tab-link');
  if(!btn) return;
  e.preventDefault();
  loadTab(btn);
});
