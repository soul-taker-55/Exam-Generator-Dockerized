// Helpers
const $ = (s, root=document) => root.querySelector(s);
const $$ = (s, root=document) => Array.from(root.querySelectorAll(s));

// Modals
const addModal  = $("#addSubjectModal");
const editModal = $("#editSubjectModal");

$("#openAddSubjectBtn")?.addEventListener("click", () => toggle(addModal, true));
$$("[data-close-add]").forEach(el => el.addEventListener("click", () => toggle(addModal, false)));
$$("[data-close-edit]").forEach(el => el.addEventListener("click", () => toggle(editModal, false)));

window.addEventListener("click", (e) => {
  if (e.target === addModal) toggle(addModal, false);
  if (e.target === editModal) toggle(editModal, false);
});

function toggle(modal, show){
  if(!modal) return;
  modal.style.display = show ? "block" : "none";
  modal.setAttribute("aria-hidden", show ? "false" : "true");
}

// Open Edit (prefill from data-*)
$$("[data-open-edit]").forEach(btn => {
  btn.addEventListener("click", () => {
    $("#editSubjectId").value    = btn.dataset.id || "";
    $("#editSubjectName").value  = btn.dataset.name || "";
    $("#editTotalMark").value    = btn.dataset.total || "";
    $("#editDuration").value     = btn.dataset.duration || "";
    toggle(editModal, true);
  });
});

// Fake submit handlers (static demo)
$("#addSubjectForm")?.addEventListener("submit", (e) => {
  e.preventDefault();
  alert("Static demo: subject would be created.");
  e.currentTarget.reset();
  toggle(addModal,false);
});

$("#editSubjectForm")?.addEventListener("submit", (e) => {
  e.preventDefault();
  alert("Static demo: subject would be updated.");
  toggle(editModal,false);
});

// Delete (static)
$$("[data-delete]").forEach(btn => {
  btn.addEventListener("click", () => {
    if(confirm("Are you sure you want to delete this subject?")) {
      alert(`Static demo: subject ${btn.dataset.id} would be deleted.`);
    }
  });
});
