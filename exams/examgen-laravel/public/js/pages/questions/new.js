// public/js/pages/questions/new.js
// تبويب "New" — إنشاء سؤال + Toolbox + Preview + sessionStorage (بدون أي تبعيات خارجية)
(function () {
  function esc(s) {
    return (s || "").replace(/[&<>"']/g, function (m) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[m];
    });
  }

  function init(root, ctx) {
    if (!root) return;

    // عناصر الواجهة
    var toolbox     = root.querySelector("#qsToolbox");
    var qText       = root.querySelector("#q_text");
    var choicesWrap = root.querySelector("#choicesWrap");
    var addChoice   = root.querySelector("#addChoiceBtn");
    var preview     = root.querySelector("#livePreview");
    var form        = root.querySelector("#addQuestionForm");
    var clearAllBtn = root.querySelector("#clearAllBtn");
    var saveAllBtn  = root.querySelector("#saveAllBtn");
    var paperBox    = root.querySelector("#testPaperBox");

    // فتح/إغلاق أقسام الرموز
    var catHeads = root.querySelectorAll(".cat-head");
    for (var i = 0; i < catHeads.length; i++) {
      catHeads[i].addEventListener("click", function () {
        this.parentElement.classList.toggle("open");
      });
    }

    // إدراج رموز
    function getFocused() {
      var el = document.activeElement;
      var ok = el && (el.tagName === "TEXTAREA" || (el.tagName === "INPUT" && el.type === "text"));
      return ok ? el : qText;
    }
    function insertSymbol(el, sym) {
      if (!el) return;
      var s = el.selectionStart || 0;
      var e = el.selectionEnd || 0;
      var v = el.value || "";
      el.value = v.slice(0, s) + sym + v.slice(e);
      el.focus();
      el.selectionStart = el.selectionEnd = s + sym.length;
      renderPreview();
    }
    function insertEq(el) {
      // نكتب \[  \] — لازم نهرب الباك سلاش مرتين داخل النص
      insertSymbol(el, "\\\\[  \\\\]");
      var pos = (el.selectionStart || 0) - 3; // ضع المؤشر بين الأقواس
      el.selectionStart = el.selectionEnd = pos;
    }

    if (toolbox) {
      toolbox.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-insert]");
        if (btn) insertSymbol(getFocused(), btn.getAttribute("data-insert"));
      });
    }
    var collapseBtn = root.querySelector("[data-collapse]");
    if (collapseBtn && toolbox) {
      collapseBtn.addEventListener("click", function () {
        toolbox.style.display = toolbox.style.display === "none" ? "" : "none";
      });
    }

    // Chips السريعة تحت السؤال
    var chips = root.querySelectorAll('.chip[data-insert]');
    for (var c = 0; c < chips.length; c++) {
      (function (chip) {
        chip.addEventListener("click", function () {
          insertSymbol(qText, chip.getAttribute("data-insert"));
        });
      })(chips[c]);
    }
    var chipEq = root.querySelector('.chip[data-eq]');
    if (chipEq) chipEq.addEventListener("click", function(){ insertEq(qText); });

    // إدارة الخيارات
    var seq = 0;
    function makeChoiceRow(txt) {
      if (typeof txt === "undefined") txt = "";
      var idx = seq++;
      var row = document.createElement("div");
      row.className = "choice-row";
      row.innerHTML =
        '<input type="radio" name="correct_answer" value="' + idx + '" title="Correct">' +
        '<input type="text" name="choices[' + idx + ']" placeholder="Choice ' + (idx + 1) + '" value="' + esc(txt) + '">' +
        '<button type="button" class="sym-btn" data-eq>Eq</button>' +
        '<button type="button" class="sym-btn" data-remove style="background:#e74c3c">×</button>';

      var eqBtn = row.querySelector("[data-eq]");
      if (eqBtn) {
        eqBtn.addEventListener("click", function () {
          var inp = row.querySelector('input[name="choices[' + idx + ']"]');
          insertEq(inp);
        });
      }
      var removeBtn = row.querySelector("[data-remove]");
      if (removeBtn) {
        removeBtn.addEventListener("click", function () {
          row.parentNode.removeChild(row);
          renderPreview();
        });
      }
      var txtInp = row.querySelector('input[name="choices[' + idx + ']"]');
      if (txtInp) txtInp.addEventListener("input", renderPreview);
      var rd = row.querySelector('input[type="radio"]');
      if (rd) rd.addEventListener("change", renderPreview);

      return row;
    }
    function ensureMinChoices(n) {
      if (typeof n === "undefined") n = 2;
      while (choicesWrap.children.length < n) {
        choicesWrap.appendChild(makeChoiceRow());
      }
    }
    ensureMinChoices();

    if (addChoice) {
      addChoice.addEventListener("click", function () {
        choicesWrap.appendChild(makeChoiceRow());
        renderPreview();
      });
    }

    // Preview
    function renderPreview() {
      var text = (qText.value || "").trim();
      var html = text ? '<div style="margin-bottom:6px"><strong>Q:</strong> ' + esc(text) + "</div>" : "";
      if (choicesWrap.children.length) {
        html += '<ol type="A" style="margin:0 0 0 18px;padding:0">';
        var inputs = choicesWrap.querySelectorAll('input[type="text"]');
        for (var i2 = 0; i2 < inputs.length; i2++) {
          var inp = inputs[i2];
          var radio = choicesWrap.querySelector('input[type="radio"][value="' + i2 + '"]');
          var isChecked = radio && radio.checked;
          html += '<li style="margin:4px 0">' + esc(inp.value || "") + (isChecked ? " <strong>(correct)</strong>" : "") + "</li>";
        }
        html += "</ol>";
      }
      preview.innerHTML = html || "<em>Start typing to see the preview…</em>";
    }
    qText.addEventListener("input", renderPreview);

    // sessionStorage بحسب المادة
    var sid = parseInt(ctx && ctx.subjectId ? ctx.subjectId : 0, 10) || 0;
    var paperKey = "test_paper_" + sid;

    function getPaper() {
      try { return JSON.parse(sessionStorage.getItem(paperKey) || "[]"); }
      catch (e) { return []; }
    }
    function setPaper(arr) {
      sessionStorage.setItem(paperKey, JSON.stringify(arr || []));
      drawPaper();
    }
    function drawPaper() {
      var data = getPaper();
      paperBox.innerHTML = data.length ? "" : '<em style="color:#7f8c8d">No questions added yet.</em>';
      for (var i3 = 0; i3 < data.length; i3++) {
        var q = data[i3];
        var div = document.createElement("div");
        div.className = "paper-item";
        var line = "";
        for (var j = 0; j < q.choices.length; j++) {
          line += '<span style="margin-right:10px;' + (j === q.correct ? "color:#27ae60;font-weight:700" : "") + '">' +
                  String.fromCharCode(65 + j) + ". " + esc(q.choices[j]) + "</span>";
        }
        div.innerHTML =
          '<div><strong>Question ' + (i3 + 1) + ':</strong> ' + esc(q.text) + "</div>" +
          '<div style="margin:6px 0 2px">' + line + "</div>" +
          "<small>Mark: " + q.points + " | Difficulty: " + (q.difficulty === 1 ? "Easy" : (q.difficulty === 2 ? "Medium" : "Hard")) + "</small>";
        paperBox.appendChild(div);
      }
    }

    if (clearAllBtn) clearAllBtn.addEventListener("click", function () { setPaper([]); });
    if (saveAllBtn)  saveAllBtn.addEventListener("click", function () {
      alert("Save All (UI placeholder) — اربط لاحقاً مع الكنترولر.");
    });

    // إرسال الفورم: نضيف السؤال إلى sessionStorage فقط (UI)
    if (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();

        var text = (qText.value || "").trim();
        if (!text) { alert("Question text is required"); return; }

        var radios  = choicesWrap.querySelectorAll('input[type="radio"]');
        var inputs  = choicesWrap.querySelectorAll('input[type="text"]');
        var choices = [];
        for (var k = 0; k < inputs.length; k++) {
          var val = (inputs[k].value || "").trim();
          if (val) choices.push(val);
        }
        var checked = "";
        for (var r = 0; r < radios.length; r++) {
          if (radios[r].checked) { checked = radios[r].value; break; }
        }

        if (!choices.length) { alert("At least one answer choice is required"); return; }
        if (checked === "")   { alert("Please select the correct answer"); return; }

        var points     = parseInt((root.querySelector("#q_points") || {}).value || "1", 10);
        var difficulty = parseInt((root.querySelector("#q_difficulty") || {}).value || "1", 10);
        var isSub      = !!(form.querySelector('input[name="is_sub"]') && form.querySelector('input[name="is_sub"]').checked);

        var item = {
          subject_id: sid,
          text: text,
          points: isNaN(points) ? 1 : points,
          difficulty: isNaN(difficulty) ? 1 : difficulty,
          is_sub: isSub ? 1 : 0,
          choices: choices,
          correct: parseInt(checked, 10)
        };

        var paper = getPaper();
        paper.push(item);
        setPaper(paper);

        // reset
        qText.value = "";
        choicesWrap.innerHTML = "";
        seq = 0;
        ensureMinChoices();
        renderPreview();
      });
    }

    // init
    drawPaper();
    renderPreview();
  }

  window.QS_New = { init: init };
})();
