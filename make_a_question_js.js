// Track the last focused input/textarea
let lastFocusedElement = null;

document.addEventListener('DOMContentLoaded', function () {
  // Track focus for all text inputs
  const textInputs = document.querySelectorAll('textarea, input[type="text"]');
  textInputs.forEach(input => {
    input.addEventListener('focus', function () {
      lastFocusedElement = this;
    });
  });

  // Initialize with one choice only if none exist
  const choicesContainer = document.getElementById('choicesContainer');
  if (choicesContainer && choicesContainer.querySelectorAll('.choice-item').length === 0) {
    addChoice();
  }

  // Wire category headers only (no handler on the whole .category)
  document.querySelectorAll('.category-header').forEach(header => {
    header.addEventListener('click', function (e) {
      e.stopPropagation();
      const cat = this.closest('.category');
      toggleCategory(cat);
    });
  });

  // Prevent clicks inside symbol panels from bubbling up
  document.querySelectorAll('.symbol-panel').forEach(panel => {
    panel.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  });

  // Outside click closes all categories
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.category') && !e.target.closest('.sidebar-header')) {
      document.querySelectorAll('.category').forEach(cat => cat.classList.remove('active'));
    }
  });
});

// Toggle sidebar expansion
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;

  sidebar.classList.toggle('collapsed');

  // Change chevron icon
  const icon = document.querySelector('.toggle-sidebar i');
  if (icon) {
    const collapsed = sidebar.classList.contains('collapsed');
    icon.classList.toggle('fa-chevron-left', !collapsed);
    icon.classList.toggle('fa-chevron-right', collapsed);
  }
}

// Toggle category expansion (accordion-like close-others)
function toggleCategory(categoryElement) {
  if (!categoryElement) return;

  // Close others
  document.querySelectorAll('.category').forEach(cat => {
    if (cat !== categoryElement) cat.classList.remove('active');
  });

  // Toggle this one
  categoryElement.classList.toggle('active');
}

// Insert \[ \] into a specific field (accepts id OR a DOM element)
function insertSymbolIntoField(fieldIdOrEl, symbol) {
  const field = typeof fieldIdOrEl === 'string'
    ? (document.getElementById(fieldIdOrEl) ||
       document.querySelector(`textarea[name="${fieldIdOrEl}"], input[name="${fieldIdOrEl}"]`))
    : fieldIdOrEl;

  if (!field) return;

  field.focus();

  if (symbol === '\\[ \\]') {
    const cursorPos = field.selectionStart || 0;
    const currentValue = field.value || '';
    field.value = currentValue.substring(0, cursorPos) + '\\[ \\]' + currentValue.substring(cursorPos);
    field.selectionStart = field.selectionEnd = cursorPos + 2; // place cursor inside
    updatePreview();
    return;
  }

  insertSymbol(symbol);
}

// Insert symbol at current caret of the last focused element
function insertSymbol(symbol) {
  if (!lastFocusedElement) return;

  const el = lastFocusedElement;
  const cursorPos = el.selectionStart || 0;
  const val = el.value || '';
  const before = val.substring(0, cursorPos);
  const after = val.substring(cursorPos);

  if (symbol === '\\[ \\]') {
    el.value = before + '\\[ \\]' + after;
    el.selectionStart = el.selectionEnd = cursorPos + 2;
    el.focus();
    updatePreview();
    return;
  }

  // Are we already inside \[ ... \] ?
  const lastOpen = before.lastIndexOf('\\[');
  const lastClose = before.lastIndexOf('\\]');
  const insideEquation = lastOpen > lastClose;

  if (insideEquation || symbol.startsWith('\\[') || symbol.startsWith('\\(')) {
    el.value = before + symbol + after;
    el.selectionStart = el.selectionEnd = cursorPos + symbol.length;
  } else {
    const wrapped = `\\[${symbol}\\]`;
    el.value = before + wrapped + after;
    el.selectionStart = el.selectionEnd = cursorPos + wrapped.length;
  }

  el.focus();
  updatePreview();
}

// Add a new choice field
function addChoice() {
  const choicesContainer = document.getElementById('choicesContainer');
  if (!choicesContainer) return;

  const count = choicesContainer.querySelectorAll('.choice-item').length;
  if (count >= 5) { alert('Maximum 5 choices (A–E).'); return; }

  const name = `choices[${count}]`;

  const choiceDiv = document.createElement('div');
  choiceDiv.className = 'choice-item';
  choiceDiv.innerHTML = `
    <input type="radio" id="correct_${count}" name="correct_answer" value="${count}">
    <input type="text" id="${name}" name="${name}" placeholder="Enter choice ${count + 1}..." oninput="updatePreview()">
    <div style="margin-left: 10px;">
      <button type="button" class="symbol-button" onclick="insertSymbolIntoField('${name}', '\\\\[ \\\\]')">
        <i class="fas fa-plus"></i> Eq
      </button>
      <button type="button" class="symbol-button clear-btn" onclick="this.parentElement.parentElement.remove(); renumberChoices(); updatePreview()">
        <i class="fas fa-times"></i>
      </button>
    </div>
  `;

  choicesContainer.appendChild(choiceDiv);

  // Track focus on the new input
  const newInput = document.getElementById(name);
  if (newInput) {
    lastFocusedElement = newInput;
    newInput.addEventListener('focus', function () { lastFocusedElement = this; });
  }

  // Update preview when picking correct answer
  const radio = choiceDiv.querySelector('input[type="radio"]');
  if (radio) radio.addEventListener('change', updatePreview);
}

// Re-number choices (after deletion)
function renumberChoices() {
  const items = document.querySelectorAll('#choicesContainer .choice-item');
  items.forEach((el, idx) => {
    const r = el.querySelector('input[type="radio"]');
    const t = el.querySelector('input[type="text"]');
    if (r) { r.id = 'correct_' + idx; r.value = idx; }
    if (t) { t.name = `choices[${idx}]`; t.id = `choices[${idx}]`; t.placeholder = `Enter choice ${idx + 1}...`; }
  });
}

// Update the live preview (MathJax)
function updatePreview() {
  const qEl = document.getElementById('questionText');
  const previewDiv = document.getElementById('livePreview');
  if (!qEl || !previewDiv) return;

  const questionText = (qEl.value || '').trim();

  if (!questionText) {
    previewDiv.innerHTML = '<p class="empty-message">Your question will appear here as you type...</p>';
    return;
  }

  const formattedQuestion = questionText.replace(/\\\[(.*?)\\\]/g, '<span class="inline-equation">\\($1\\)</span>');
  let html = `<div class="question-text"><strong>Preview:</strong> ${formattedQuestion}</div>`;

  // Choices
  const choiceEls = document.querySelectorAll('#choicesContainer .choice-item');
  const choices = [];
  const correct = [];
  choiceEls.forEach((choiceEl, index) => {
    const t = choiceEl.querySelector('input[type="text"]');
    const r = choiceEl.querySelector('input[type="radio"]');
    if (t && (t.value || '').trim()) {
      choices.push(t.value.trim());
      if (r && r.checked) correct.push(index);
    }
  });

  if (choices.length > 0) {
    html += `
      <div class="choices-list">
        ${choices.map((c, i) => {
          const formatted = c.replace(/\\\[(.*?)\\\]/g, '<span class="inline-equation">\\($1\\)</span>');
          return `<span class="${correct.includes(i) ? 'choice-correct' : ''}">${String.fromCharCode(65 + i)}. ${formatted}</span>`;
        }).join(' ')}
      </div>
    `;
  }

  previewDiv.innerHTML = html;

  // Render MathJax for preview only
  if (window.MathJax && MathJax.typesetPromise) {
    MathJax.typesetPromise([previewDiv]).catch(err => console.log(err));
  }
}
