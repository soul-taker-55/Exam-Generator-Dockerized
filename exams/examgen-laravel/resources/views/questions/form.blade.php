{{-- FRAGMENT ONLY: Add Question with Math Symbols toolbox (no layout) --}}
@php
  $subjectId = request('subject_id');
  $q = array_filter(['embed'=>1,'subject_id'=>$subjectId]);
@endphp

<style>
/* ===== Scoped styles for the New tab (prefix: .qs-new) ===== */
.qs-new{display:flex;gap:16px;min-height:480px}
.qs-new .toolbox{
  flex:0 0 260px;background:#2c3e50;color:#fff;border-radius:10px;
  overflow:hidden;align-self:flex-start;max-height:70vh;display:flex;flex-direction:column
}
.qs-new .toolbox-header{
  display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.1)
}
.qs-new .toolbox-header .title{font-weight:700}
.qs-new .toolbox-content{padding:10px;overflow:auto}
.qs-new .cat{border-radius:6px;margin-bottom:8px;background:rgba(255,255,255,.04)}
.qs-new .cat-head{
  display:flex;align-items:center;gap:8px;padding:8px 10px;cursor:pointer
}
.qs-new .cat-head:hover{background:rgba(255,255,255,.06)}
.qs-new .cat-body{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));
  gap:6px;padding:8px 10px 10px;max-height:0;overflow:hidden;transition:max-height .25s ease
}
.qs-new .cat.open .cat-body{max-height:360px}
.qs-new .sym-btn{
  border:none;border-radius:6px;padding:6px 8px;background:#3498db;color:#fff;font-size:12px;cursor:pointer
}
.qs-new .sym-btn:hover{filter:brightness(.95)}
.qs-new .editor{
  flex:1;background:#fff;border:1px solid #eee;border-radius:10px;padding:14px
}
.qs-new .form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.qs-new .form-group{margin-bottom:10px}
.qs-new .form-group label{display:block;font-weight:700;margin-bottom:6px}
.qs-new input[type="text"], .qs-new input[type="number"], .qs-new select, .qs-new textarea{
  width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font:inherit
}
.qs-new textarea{min-height:100px;resize:vertical}
.qs-new .choices{display:flex;flex-direction:column;gap:8px}
.qs-new .choice-row{display:flex;align-items:center;gap:8px}
.qs-new .chip{display:inline-flex;align-items:center;gap:6px;padding:6px 8px;border:1px solid #d6e9ff;background:#eef6ff;color:#0b5cab;border-radius:8px;cursor:pointer}
.qs-new .btn{padding:10px 14px;border:none;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.qs-new .btn-add{background:#3498db;color:#fff}
.qs-new .btn-cancel{background:#95a5a6;color:#fff}
.qs-new .btn-save{background:#27ae60;color:#fff}
.qs-new .btn-danger{background:#e74c3c;color:#fff}
.qs-new .preview{margin-top:14px;border-top:1px dashed #e5e9f2;padding-top:10px}
.qs-new .preview-box{border:1px dashed #dfe6e9;border-radius:8px;padding:12px;color:#555;min-height:52px}
.qs-new .paper{margin-top:14px;border-top:1px dashed #e5e9f2;padding-top:10px}
.qs-new .paper-item{border-left:4px solid #3498db;background:#fafbfd;border:1px solid #eef2f7;padding:10px;border-radius:8px;margin-bottom:8px}
@media(max-width:992px){.qs-new{flex-direction:column}.qs-new .toolbox{max-height:none}}
</style>

<div class="qs-new" id="qsNew">
  <!-- ===== Left: Math Symbols toolbox ===== -->
  <aside class="toolbox" aria-label="Math symbols">
    <div class="toolbox-header">
      <span class="title"><i class="fa-solid fa-square-root-alt"></i> Math Symbols</span>
      <button class="sym-btn" type="button" data-collapse="#qsToolbox"><i class="fa-solid fa-chevron-down"></i></button>
    </div>
    <div class="toolbox-content" id="qsToolbox">
      @php
        $cats = [
          ['icon'=>'fa-calculator','name'=>'Basic','syms'=>['+','-','\\times','\\div','=','\\neq','\\pm','\\cdot']],
          ['icon'=>'fa-infinity','name'=>'Calculus','syms'=>['\\int_{a}^{b}','\\iint','\\iiint','\\oint','\\frac{d}{dx}','\\frac{\\partial}{\\partial x}','\\nabla','\\Delta','\\lim_{x \\to a}','\\sum_{i=1}^{n}','\\prod_{i=1}^{n}','\\infty']],
          ['icon'=>'fa-greater-than-equal','name'=>'Algebra','syms'=>['\\frac{a}{b}','\\sqrt{x}','\\sqrt[n]{x}','x^{n}','x_{n}','\\leq','\\geq','\\approx','\\equiv','\\propto']],
          ['icon'=>'fa-shapes','name'=>'Geometry','syms'=>['\\pi','\\theta','\\alpha','\\beta','\\gamma','\\sin','\\cos','\\tan','\\angle','\\perp','\\parallel']],
          ['icon'=>'fa-superscript','name'=>'Advanced','syms'=>['\\forall','\\exists','\\emptyset','\\in','\\subset','\\subseteq','\\cup','\\cap','\\mathbb{R}','\\mathbb{Z}']],
          ['icon'=>'fa-grip-lines','name'=>'Matrices / Vectors','syms'=>['\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}','\\begin{bmatrix} a & b \\\\ c & d \\end{bmatrix}','\\begin{pmatrix} a & b & c \\\\ d & e & f \\\\ g & h & i \\end{pmatrix}','\\begin{bmatrix} a & b & c \\\\ d & e & f \\\\ g & h & i \\end{bmatrix}','\\vec{v}','\\hat{i}','\\times','\\cdot','\\|v\\|']],
        ];
      @endphp
      @foreach($cats as $i=>$c)
        <div class="cat {{ $i===0?'open':'' }}">
          <div class="cat-head" data-toggle="cat">
            <i class="fa-solid {{ $c['icon'] }}"></i>
            <span class="cat-title">{{ $c['name'] }}</span>
            <i class="fa-solid fa-chevron-down" style="margin-left:auto"></i>
          </div>
          <div class="cat-body">
            @foreach($c['syms'] as $s)
              <button type="button" class="sym-btn" data-insert="{{ $s }}">{{ Str::limit($s,10) }}</button>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </aside>

  <!-- ===== Right: Question editor ===== -->
  <section class="editor">
    <form method="POST" action="{{ route('questions.create',$q) }}" id="addQuestionForm">
      @csrf
      <input type="hidden" name="subject_id" value="{{ $subjectId }}">
      <div class="form-group">
        <label for="q_text">Question Text</label>
        <textarea id="q_text" name="text" placeholder="Enter your question here..." required></textarea>
        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
          <span class="chip" data-eq><i class="fa-solid fa-plus"></i> Equation (\[ \])</span>
          <span class="chip" data-insert="\\alpha">α</span>
          <span class="chip" data-insert="\\beta">β</span>
          <span class="chip" data-insert="\\sqrt{}">√</span>
          <span class="chip" data-insert="\\frac{}{}">a/b</span>
        </div>
      </div>

      <div class="form-group">
        <label>Multiple Choice Answers</label>
        <div class="choices" id="choicesWrap"></div>
        <button type="button" class="btn btn-add" id="addChoiceBtn"><i class="fa-solid fa-plus"></i> Add Choice</button>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="q_points">Mark</label>
          <input type="number" id="q_points" name="points" min="1" value="1" required>
        </div>
        <div class="form-group">
          <label for="q_difficulty">Difficulty</label>
          <select id="q_difficulty" name="difficulty" required>
            <option value="1">Easy</option>
            <option value="2">Medium</option>
            <option value="3">Hard</option>
          </select>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
          <label style="display:flex;align-items:center;gap:8px;margin:0">
            <input type="checkbox" name="is_sub" value="1"> Is Sub Question
          </label>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
        <a class="btn btn-cancel" href="{{ route('questions.index',$q) }}">Cancel</a>
        <button type="submit" class="btn btn-save"><i class="fa-solid fa-plus-circle"></i> Add to Test Paper</button>
      </div>
    </form>

    <div class="preview">
      <h4 style="margin:0 0 6px">Live Preview</h4>
      <div id="livePreview" class="preview-box"><em>Start typing to see the preview…</em></div>
    </div>

    <div class="paper">
      <h4 style="margin:0 0 6px">Test Paper (current session)</h4>
      <div id="testPaperBox"></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
        <button type="button" class="btn btn-save" id="saveAllBtn"><i class="fa-solid fa-database"></i> Save All (UI)</button>
        <button type="button" class="btn btn-danger" id="clearAllBtn"><i class="fa-solid fa-trash"></i> Clear</button>
      </div>
    </div>
  </section>
</div>

<script>
/* ===== Scoped JS for New tab ===== */
(function(){
  const root        = document.getElementById('qsNew');
  const toolbox     = root.querySelector('#qsToolbox');
  const qText       = root.querySelector('#q_text');
  const choicesWrap = root.querySelector('#choicesWrap');
  const addChoice   = root.querySelector('#addChoiceBtn');
  const preview     = root.querySelector('#livePreview');
  const paperBox    = root.querySelector('#testPaperBox');
  const clearAllBtn = root.querySelector('#clearAllBtn');
  const saveAllBtn  = root.querySelector('#saveAllBtn');
  const form        = root.querySelector('#addQuestionForm');

  // 1) Toolbox toggles & inserts
  root.querySelectorAll('.cat-head').forEach(h=>{
    h.addEventListener('click', ()=> h.parentElement.classList.toggle('open'));
  });
  toolbox.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-insert]');
    if(btn){ insertSymbol(getFocusedEditable(), btn.getAttribute('data-insert')); }
  });
  root.querySelector('[data-collapse]')?.addEventListener('click', ()=>{
    toolbox.style.display = toolbox.style.display==='none' ? '' : 'none';
  });

  // quick chips under question
  root.querySelectorAll('.chip[data-insert]').forEach(c=> c.addEventListener('click', ()=> insertSymbol(qText, c.getAttribute('data-insert')) ));
  root.querySelector('.chip[data-eq]')?.addEventListener('click', ()=> insertEq(qText));

  // 2) Choice rows
  let seq=0;
  function makeChoiceRow(txt=''){
    const idx = seq++;
    const row = document.createElement('div');
    row.className='choice-row';
    row.innerHTML = `
      <input type="radio" name="correct_answer" value="${idx}" title="Correct">
      <input type="text" name="choices[${idx}]" placeholder="Choice ${idx+1}" value="${txt}">
      <button type="button" class="sym-btn" data-eq>Eq</button>
      <button type="button" class="sym-btn" data-remove style="background:#e74c3c">×</button>`;
    row.querySelector('[data-eq]').addEventListener('click', ()=>{
      const input = row.querySelector(\`input[name="choices[${idx}]"]\`);
      insertEq(input);
    });
    row.querySelector('[data-remove]').addEventListener('click', ()=>{ row.remove(); renderPreview(); });
    row.querySelector('input[type="text"]').addEventListener('input', renderPreview);
    row.querySelector('input[type="radio"]').addEventListener('change', renderPreview);
    return row;
  }
  function ensureMinChoices(n=2){ while(choicesWrap.children.length < n) choicesWrap.appendChild(makeChoiceRow()); }
  ensureMinChoices();

  addChoice.addEventListener('click', ()=>{ choicesWrap.appendChild(makeChoiceRow()); renderPreview(); });

  // 3) Insert helpers
  function insertSymbol(el, sym){
    if(!el) return;
    const {selectionStart:s, selectionEnd:e, value:v} = el;
    el.value = v.slice(0,s)+sym+v.slice(e);
    el.focus(); el.selectionStart = el.selectionEnd = s + sym.length;
    renderPreview();
  }
  function insertEq(el){
    insertSymbol(el, '\\\\[  \\\\]');
    // ضع المؤشر بين الأقواس:
    const pos = (el.selectionStart||0) - 3;
    el.selectionStart = el.selectionEnd = pos;
  }
  function getFocusedEditable(){
    const el = document.activeElement;
    return (el && (el.tagName==='TEXTAREA' || (el.tagName==='INPUT' && el.type==='text'))) ? el : qText;
  }
  function esc(s){ return (s||'').replace(/[&<>"']/g, m=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }

  // 4) Live preview
  function renderPreview(){
    const text = qText.value.trim();
    let html = text ? `<div style="margin-bottom:6px"><strong>Q:</strong> ${esc(text)}</div>` : '';
    if(choicesWrap.children.length){
      html += '<ol type="A" style="margin:0 0 0 18px;padding:0">';
      [...choicesWrap.querySelectorAll('input[type="text"]')].forEach((inp,i)=>{
        const checked = choicesWrap.querySelector(\`input[type="radio"][value="${i}"]\`)?.checked;
        html += \`<li style="margin:4px 0">\${esc(inp.value||'')}\${checked?' <strong>(correct)</strong>':''}</li>\`;
      });
      html += '</ol>';
    }
    preview.innerHTML = html || '<em>Start typing to see the preview…</em>';
  }
  qText.addEventListener('input', renderPreview);

  // 5) Test Paper (sessionStorage simulation by subject)
  const paperKey = 'test_paper_'+({{ (int)($subjectId ?: 0) }});
  function getPaper(){ try{ return JSON.parse(sessionStorage.getItem(paperKey) || '[]'); }catch{ return []; } }
  function setPaper(arr){ sessionStorage.setItem(paperKey, JSON.stringify(arr||[])); drawPaper(); }
  function drawPaper(){
    const data = getPaper();
    paperBox.innerHTML = data.length? '' : '<em style="color:#7f8c8d">No questions added yet.</em>';
    data.forEach((q,i)=>{
      const div = document.createElement('div');
      div.className='paper-item';
      div.innerHTML = `
        <div><strong>Question ${i+1}:</strong> ${esc(q.text)}</div>
        <div style="margin:6px 0 2px">
          ${q.choices.map((c,idx)=> `<span style="margin-right:10px;${idx==q.correct?'color:#27ae60;font-weight:700':''}">${String.fromCharCode(65+idx)}. ${esc(c)}</span>`).join('')}
        </div>
        <small>Mark: ${q.points} | Difficulty: ${q.difficulty==1?'Easy':(q.difficulty==2?'Medium':'Hard')}</small>`;
      paperBox.appendChild(div);
    });
  }
  clearAllBtn.addEventListener('click', ()=> setPaper([]));
  saveAllBtn.addEventListener('click', ()=> alert('Save All (UI placeholder) — hook this to your controller later.'));

  // Intercept submit to push into "session"
  form.addEventListener('submit', (e)=>{
    e.preventDefault();
    const text = qText.value.trim();
    if(!text){ alert('Question text is required'); return; }
    const radios = choicesWrap.querySelectorAll('input[type="radio"]');
    const inputs = choicesWrap.querySelectorAll('input[type="text"]');
    const choices = [...inputs].map(i=> i.value.trim()).filter(Boolean);
    const checked = [...radios].find(r=> r.checked)?.value ?? '';
    if(!choices.length){ alert('At least one answer choice is required'); return; }
    if(checked===''){ alert('Please select the correct answer'); return; }
    const item = {
      subject_id: {{ (int)($subjectId ?: 0) }},
      text, points: parseInt(root.querySelector('#q_points').value||'1'),
      difficulty: parseInt(root.querySelector('#q_difficulty').value||'1'),
      is_sub: form.querySelector('input[name="is_sub"]').checked ? 1 : 0,
      choices, correct: parseInt(checked)
    };
    const paper = getPaper(); paper.push(item); setPaper(paper);
    // reset quick
    qText.value=''; choicesWrap.innerHTML=''; seq=0; ensureMinChoices(); renderPreview();
  });

  // Init
  drawPaper(); renderPreview();
})();
</script>
