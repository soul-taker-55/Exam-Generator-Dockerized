// Track the last focused input/textarea
let lastFocusedElement = null;
        
// Set up event listeners to track focus
document.addEventListener('DOMContentLoaded', function() {
    const textInputs = document.querySelectorAll('textarea, input[type="text"]');
    textInputs.forEach(input => {
        input.addEventListener('focus', function() {
            lastFocusedElement = this;
        });
    });
    
    // Initialize with one choice
    addChoice();
});

// Toggle sidebar expansion
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
    
    // Change icon
    const icon = document.querySelector('.toggle-sidebar i');
    if (sidebar.classList.contains('collapsed')) {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    } else {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
    }
}

// Toggle category expansion
function toggleCategory(categoryElement) {
    // Close all other categories first if they're not in the same parent
    if (!categoryElement.classList.contains('active')) {
        document.querySelectorAll('.category').forEach(cat => {
            if (cat !== categoryElement) {
                cat.classList.remove('active');
            }
        });
    }
    
    // Toggle the clicked category
    categoryElement.classList.toggle('active');
    
    // Prevent the click from propagating to document
    event.stopPropagation();
}

function insertSymbolIntoField(fieldId, symbol) {
    // Handle both ID strings and element references
    const field = typeof fieldId === 'string' ? document.getElementById(fieldId) : fieldId;
    
    if (!field) return;
    
    field.focus();
    
    // Special handling for equation delimiters
    if (symbol === '\\[ \\]') {
        const cursorPos = field.selectionStart;
        const currentValue = field.value;
        
        // Insert the delimiters with space between them
        field.value = currentValue.substring(0, cursorPos) + '\\[ \\]' + currentValue.substring(cursorPos);
        
        // Position cursor between the brackets (after '\\[')
        field.selectionStart = field.selectionEnd = cursorPos + 2;
    } else {
        // Regular symbol insertion
        insertSymbol(symbol);
    }
    
    updatePreview();
}


function insertSymbol(symbol) {
    if (!lastFocusedElement) return;

    const cursorPos = lastFocusedElement.selectionStart;
    let currentValue = lastFocusedElement.value;
    let textBefore = currentValue.substring(0, cursorPos);
    let textAfter = currentValue.substring(cursorPos);

    // Special handling for equation delimiters
    if (symbol === '\\[ \\]') {
        lastFocusedElement.value = textBefore + '\\[ \\]' + textAfter;
        lastFocusedElement.selectionStart = lastFocusedElement.selectionEnd = cursorPos + 2;
        lastFocusedElement.focus();
        updatePreview();
        return;
    }

    // Check if we're inside existing \[ \]
    const lastOpen = textBefore.lastIndexOf('\\[');
    const lastClose = textBefore.lastIndexOf('\\]');
    const isInsideEquation = lastOpen > lastClose;

    // If we're inside an equation OR the symbol already has delimiters, insert raw
    if (isInsideEquation || symbol.startsWith('\\[') || symbol.startsWith('\\(')) {
        lastFocusedElement.value = textBefore + symbol + textAfter;
    } 
    // Otherwise, wrap with delimiters
    else {
        lastFocusedElement.value = textBefore + `\\[${symbol}\\]` + textAfter;
    }

    // Position cursor after inserted symbol
    lastFocusedElement.selectionStart = lastFocusedElement.selectionEnd = 
        cursorPos + (isInsideEquation ? symbol.length : symbol.length + 4);
    
    lastFocusedElement.focus();
    updatePreview();
}

// Add a new choice field
function addChoice() {
    const choicesContainer = document.getElementById('choicesContainer');
    const choiceCount = choicesContainer.children.length;
    const choiceName = `choices[${choiceCount}]`;
    
    const choiceDiv = document.createElement('div');
    choiceDiv.className = 'choice-item';
    choiceDiv.innerHTML = `
        <input type="radio" name="correct_answer" value="${choiceCount}">
        <input type="text" id="${choiceName}" name="${choiceName}" placeholder="Enter choice ${choiceCount + 1}..." oninput="updatePreview()">
        <div style="margin-left: 10px;">
            <button type="button" class="symbol-button" onclick="insertSymbolIntoField('${choiceName}', '\\\\[ \\\\]')">
                <i class="fas fa-plus"></i> Eq
            </button>
            <button type="button" class="symbol-button clear-btn" onclick="this.parentElement.parentElement.remove(); updatePreview()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    choicesContainer.appendChild(choiceDiv);
    
    // Focus on the new choice input and track it
    const newInput = document.getElementById(choiceName);
    lastFocusedElement = newInput;
    
    // Add focus event listener to the new input
    newInput.addEventListener('focus', function() {
        lastFocusedElement = this;
    });
    
    // Add change listener for radio buttons
    const radioInput = choiceDiv.querySelector('input[type="radio"]');
    radioInput.addEventListener('change', updatePreview);
}

// Update the live preview
function updatePreview() {
    const questionText = document.getElementById('questionText').value.trim();
    const previewDiv = document.getElementById('livePreview');
    
    if (!questionText) {
        previewDiv.innerHTML = '<p class="empty-message">Your question will appear here as you type...</p>';
        return;
    }
    
    // Format question text with MathJax
    const formattedQuestion = questionText.replace(/\\\[(.*?)\\\]/g, 
        '<span class="inline-equation">\\($1\\)</span>');
    
    let previewHTML = `
        <div class="question-text">
            <strong>Preview:</strong> ${formattedQuestion}
        </div>
    `;
    
    // Add choices to preview if they exist
    const choices = [];
    const correctIndices = [];
    const choiceElements = document.querySelectorAll('#choicesContainer .choice-item');
    
    choiceElements.forEach((choiceEl, index) => {
        const choiceInput = choiceEl.querySelector('input[type="text"]');
        const choiceRadio = choiceEl.querySelector('input[type="radio"]');
        
        if (choiceInput.value.trim()) {
            choices.push(choiceInput.value.trim());
            if (choiceRadio.checked) {
                correctIndices.push(index);
            }
        }
    });
    
    if (choices.length > 0) {
        previewHTML += `
            <div class="choices-list">
                ${choices.map((choice, index) => {
                    const formattedChoice = choice.replace(/\\\[(.*?)\\\]/g, 
                        '<span class="inline-equation">\\($1\\)</span>');
                    return `
                        <span class="${correctIndices.includes(index) ? 'choice-correct' : ''}">
                            ${String.fromCharCode(65 + index)}. ${formattedChoice}
                        </span>
                    `;
                }).join(' ')}
            </div>
        `;
    }
    
    previewDiv.innerHTML = previewHTML;
    
    // Render MathJax for the preview
    MathJax.typesetPromise([previewDiv]).catch(err => console.log(err));
}

// Add question to the test paper
function addQuestionToTest() {
    const questionText = document.getElementById('questionText').value.trim();
    if (!questionText) {
        alert('Please enter a question text');
        return;
    }
    
    const choices = [];
    let correctAnswerIndex = -1;
    const choiceElements = document.querySelectorAll('#choicesContainer .choice-item');
    
    if (choiceElements.length === 0) {
        alert('Please add at least one choice');
        return;
    }
    
    choiceElements.forEach((choiceEl, index) => {
        const choiceInput = choiceEl.querySelector('input[type="text"]');
        const choiceRadio = choiceEl.querySelector('input[type="radio"]');
        
        if (choiceInput.value.trim()) {
            choices.push(choiceInput.value.trim());
            if (choiceRadio.checked) {
                correctAnswerIndex = index;
            }
        }
    });
    
    if (choices.length === 0) {
        alert('Please enter at least one valid choice');
        return;
    }
    
    if (correctAnswerIndex === -1) {
        alert('Please select the correct answer');
        return;
    }
    
    const testPaper = document.getElementById('testPaper');
    const questionNumber = testPaper.querySelectorAll('.question-item').length + 1;
    
    // Replace \[ \] with \( \) for inline display and wrap with span
    const formattedQuestion = questionText.replace(/\\\[(.*?)\\\]/g, 
        '<span class="inline-equation">\\($1\\)</span>');
    
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item';
    questionDiv.innerHTML = `
        <div class="question-text">
            <strong>Question ${questionNumber}:</strong> ${formattedQuestion}
        </div>
        <div class="choices-list">
            ${choices.map((choice, index) => {
                const formattedChoice = choice.replace(/\\\[(.*?)\\\]/g, 
                    '<span class="inline-equation">\\($1\\)</span>');
                return `
                    <span class="${index === correctAnswerIndex ? 'choice-correct' : ''}">
                        ${String.fromCharCode(65 + index)}. ${formattedChoice}
                    </span>
                `;
            }).join(' ')}
        </div>
        <button class="symbol-button clear-btn" onclick="this.parentElement.remove(); renumberQuestions();" style="margin-top:10px;">
            <i class="fas fa-trash"></i> Remove Question
        </button>
    `;
    
    // Remove empty message if it exists
    const emptyMsg = testPaper.querySelector('.empty-message');
    if (emptyMsg) {
        emptyMsg.remove();
    }
    
    testPaper.appendChild(questionDiv);
    
    // Clear the form
    document.getElementById('questionText').value = '';
    document.getElementById('choicesContainer').innerHTML = '';
    
    // Add one empty choice for the next question
    addChoice();
    
    // Update preview to show empty state
    updatePreview();
    
    // Render MathJax for the new question
    MathJax.typesetPromise([questionDiv]).catch(err => console.log(err));
}

// Renumber questions after deletion
function renumberQuestions() {
    const questions = document.querySelectorAll('.question-item');
    if (questions.length === 0) {
        document.getElementById('testPaper').innerHTML = `
            <p class="empty-message">No questions added yet. Start by creating a question above.</p>
        `;
        return;
    }
    
    questions.forEach((question, index) => {
        const questionText = question.querySelector('.question-text');
        questionText.innerHTML = questionText.innerHTML.replace(
            /Question \d+:/, 
            `Question ${index + 1}:`
        );
    });
}

// Clear the entire test
function clearTest() {
    if (confirm('Are you sure you want to clear all questions?')) {
        document.getElementById('testPaper').innerHTML = `
            <p class="empty-message">No questions added yet. Start by creating a question above.</p>
        `;
    }
}

// Close all categories when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.category') && !e.target.closest('.sidebar-header')) {
        document.querySelectorAll('.category').forEach(cat => {
            cat.classList.remove('active');
        });
    }
});