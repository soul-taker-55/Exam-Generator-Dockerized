/**
 * Subject Questions Management System
 * Handles:
 * - Tab navigation
 * - Bulk actions (delete, change difficulty, group, export)
 * - Question filtering
 * - MathJax rendering
 * - Question loading
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initTabs();
    initBulkActions();
    initQuickDelete();
    renderMath();
});

// ========================
// CORE FUNCTIONALITY
// ========================

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update UI
            tabButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            const tabContent = document.getElementById(`${tabId}-tab`);
            tabContent.classList.add('active');
            
            // Load content if not already loaded
            if (tabId !== 'all' && !tabContent.dataset.loaded) {
                loadTabContent(tabId);
                tabContent.dataset.loaded = true;
            }
        });
    });
    
    // Load initial tab
    const activeTab = document.querySelector('.tab-btn.active') || tabButtons[0];
    if (activeTab) {
        const tabId = activeTab.getAttribute('data-tab');
        const tabContent = document.getElementById(`${tabId}-tab`);
        if (tabId !== 'all' && tabContent && !tabContent.dataset.loaded) {
            loadTabContent(tabId);
            tabContent.dataset.loaded = true;
        }
    }
}

function initBulkActions() {
    const bulkActionSelect = document.getElementById('bulk-action');
    const applyBulkActionBtn = document.getElementById('apply-bulk-action');
    const modal = document.getElementById('bulk-modal');
    const closeModalButtons = document.querySelectorAll('.close-modal');
    const confirmBulkActionBtn = document.getElementById('confirm-bulk-action');

    applyBulkActionBtn?.addEventListener('click', () => {
        const action = bulkActionSelect.value;
        const selectedQuestions = getSelectedQuestions();
        
        if (!action || selectedQuestions.length === 0) {
            showAlert('Please select an action and at least one question', 'error');
            return;
        }
        
        openBulkModal(action, selectedQuestions);
    });

    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => modal.style.display = 'none');
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) modal.style.display = 'none';
    });

    confirmBulkActionBtn?.addEventListener('click', () => {
        const action = document.getElementById('modal-title').getAttribute('data-action');
        const selectedQuestions = document.getElementById('modal-title').getAttribute('data-questions').split(',');
        
        switch(action) {
            case 'delete': deleteQuestions(selectedQuestions); break;
            case 'change-difficulty': 
                updateQuestionDifficulty(selectedQuestions, document.getElementById('bulk-difficulty').value); 
                break;
            case 'group': 
                addQuestionsToGroup(selectedQuestions, document.getElementById('bulk-group-num').value); 
                break;
            case 'export': exportQuestions(selectedQuestions); break;
        }
        
        modal.style.display = 'none';
    });
}

function initQuickDelete() {
    // Remove any existing event listeners first by cloning and replacing elements
    document.querySelectorAll('.quick-delete').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
    });
    
    // Add a single event listener to each delete button
    document.querySelectorAll('.quick-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this question?')) {
                e.preventDefault();
            }
        });
    });
}

// ========================
// UTILITY FUNCTIONS
// ========================

function getSelectedQuestions() {
    return Array.from(document.querySelectorAll('.question-checkbox:checked'))
        .map(checkbox => checkbox.value);
}

function renderMath() {
    if (typeof MathJax !== 'undefined') {
        // Wait for MathJax to be fully loaded
        if (MathJax.typesetPromise) {
            MathJax.typesetPromise()
                .then(() => document.body.classList.add('mathjax-processed'))
                .catch(err => console.error('MathJax error:', err));
        } else {
            // If MathJax is still loading, retry after a short delay
            setTimeout(renderMath, 100);
        }
    }
}

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `global-alert alert-${type}`;
    alert.textContent = message;
    
    // Style the alert
    Object.assign(alert.style, {
        position: 'fixed',
        bottom: '20px',
        right: '20px',
        padding: '10px 20px',
        backgroundColor: type === 'error' ? '#f8d7da' : 
                        type === 'success' ? '#d4edda' : '#d1ecf1',
        color: type === 'error' ? '#721c24' : 
               type === 'success' ? '#155724' : '#0c5460',
        borderRadius: '4px',
        boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
        zIndex: '1000',
        animation: 'fadeIn 0.3s'
    });
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'fadeOut 0.3s';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// ========================
// TAB CONTENT LOADING
// ========================

function loadTabContent(tabId) {
    const tabContent = document.getElementById(`${tabId}-tab`);
    const subjectId = new URLSearchParams(window.location.search).get('subject_id');
    
    if (!tabContent || !subjectId) return;
    
    // Show loading state
    tabContent.innerHTML = '<div class="loading">Loading questions...</div>';
    
    // Include current search/filter parameters
    const searchParams = new URLSearchParams(window.location.search);
    
    fetch(`ajax/load_tab.php?tab=${tabId}&subject_id=${subjectId}&${searchParams.toString()}`)
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.text();
    })
    .then(html => {
        if (html.trim() === '') {
            tabContent.innerHTML = '<div class="no-questions">No questions found in this view</div>';
        } else {
            tabContent.innerHTML = html;
            renderMath(); // Render any LaTeX in the new content
            
            // Initialize any interactive elements in the new content
            initQuickDelete();
        }
    })
    .catch(error => {
        console.error('Error loading tab content:', error);
        tabContent.innerHTML = `
            <div class="error">
                Error loading questions. 
                <button onclick="loadTabContent('${tabId}')">Try again</button>
            </div>
        `;
    });
}

// ========================
// BULK ACTION FUNCTIONS
// ========================

function openBulkModal(action, questionIds) {
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    
    modalTitle.setAttribute('data-action', action);
    modalTitle.setAttribute('data-questions', questionIds.join(','));
    
    const templates = {
        'delete': `
            <p>You are about to delete ${questionIds.length} question(s). This action cannot be undone.</p>
            <p>Are you sure you want to proceed?</p>
        `,
        'change-difficulty': `
            <p>Changing difficulty for ${questionIds.length} question(s).</p>
            <div class="form-group">
                <label for="bulk-difficulty">New Difficulty:</label>
                <select id="bulk-difficulty" class="form-control">
                    <option value="1">Easy</option>
                    <option value="2">Medium</option>
                    <option value="3">Hard</option>
                </select>
            </div>
        `,
        'group': `
            <p>Adding ${questionIds.length} question(s) to a group.</p>
            <div class="form-group">
                <label for="bulk-group-num">Group Number:</label>
                <input type="number" id="bulk-group-num" class="form-control" min="1" required>
            </div>
            <p class="text-muted">Enter 0 to remove from group</p>
        `,
        'export': `
            <p>Exporting ${questionIds.length} question(s).</p>
            <div class="form-group">
                <label for="export-format">Format:</label>
                <select id="export-format" class="form-control">
                    <option value="pdf">PDF</option>
                    <option value="word">Word Document</option>
                    <option value="text">Plain Text</option>
                </select>
            </div>
            <div class="form-group">
                <label for="include-answers">Include Answers:</label>
                <input type="checkbox" id="include-answers" checked>
            </div>
        `
    };
    
    modalTitle.textContent = {
        'delete': 'Confirm Delete',
        'change-difficulty': 'Change Difficulty',
        'group': 'Add to Group',
        'export': 'Export Questions'
    }[action];
    
    modalBody.innerHTML = templates[action];
    document.getElementById('bulk-modal').style.display = 'block';
}

async function performAjaxAction(url, data, successMessage) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            if (response.ok) {
                // If response is OK but not JSON, consider it a success
                showAlert(successMessage, 'success');
                return true;
            }
            throw new Error('Invalid response format');
        }

        // Check for explicit failure
        if (result.success === false && result.message) {
            throw new Error(result.message);
        }

        // Consider it a success if we got here
        showAlert(successMessage, 'success');
        return true;
    } catch (error) {
        console.error('API Error:', error);
        showAlert(error.message || 'An error occurred', 'error');
        return false;
    }
}

function deleteQuestions(questionIds) {
    performAjaxAction(
        'ajax/delete_questions.php',
        { question_ids: questionIds },
        `${questionIds.length} question(s) deleted successfully`
    ).then(success => {
        if (success) {
            questionIds.forEach(id => {
                document.querySelector(`.question-card[data-id="${id}"]`)?.remove();
            });
        }
    });
}

function updateQuestionDifficulty(questionIds, difficulty) {
    performAjaxAction(
        'ajax/update_difficulty.php',
        { question_ids: questionIds, difficulty: difficulty },
        'Difficulty updated successfully'
    ).then(success => {
        if (success) {
            questionIds.forEach(id => {
                const card = document.querySelector(`.question-card[data-id="${id}"]`);
                const badge = card?.querySelector('.difficulty-badge');
                if (badge) {
                    badge.className = `difficulty-badge difficulty-${difficulty}`;
                    badge.textContent = ['Easy', 'Medium', 'Hard'][difficulty - 1];
                }
            });
        }
    });
}

function addQuestionsToGroup(questionIds, groupNum) {
    performAjaxAction(
        'ajax/update_group.php',
        { question_ids: questionIds, group_num: groupNum },
        'Group updated successfully'
    ).then(success => {
        if (success) {
            questionIds.forEach(id => {
                const card = document.querySelector(`.question-card[data-id="${id}"]`);
                if (card) {
                    let groupBadge = card.querySelector('.group-badge');
                    
                    if (groupNum > 0) {
                        if (!groupBadge) {
                            groupBadge = document.createElement('span');
                            groupBadge.className = 'group-badge';
                            card.querySelector('.card-header').appendChild(groupBadge);
                        }
                        groupBadge.textContent = `Group ${groupNum}`;
                    } else if (groupBadge) {
                        groupBadge.remove();
                    }
                }
            });
        }
    });
}

function exportQuestions(questionIds) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_questions.php';
    
    const fields = {
        'question_ids': questionIds.join(','),
        'format': document.getElementById('export-format').value,
        'include_answers': document.getElementById('include-answers').checked ? '1' : '0'
    };
    
    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(20px); }
    }
`;
document.head.appendChild(style);