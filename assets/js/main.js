document.addEventListener('DOMContentLoaded', () => {
    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    htmlElement.setAttribute('data-bs-theme', savedTheme);
    if (themeToggle) {
        themeToggle.checked = savedTheme === 'dark';
    }

    if (themeToggle) {
        themeToggle.addEventListener('change', () => {
            const theme = themeToggle.checked ? 'dark' : 'light';
            htmlElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
        });
    }

    // Unified Filtering logic
    const searchInput = document.getElementById('snippetSearch');
    const tagButtons = document.querySelectorAll('#tagFilters .btn');
    let currentSearch = '';
    let currentTag = 'all';

    const filterSnippets = () => {
        const cards = document.querySelectorAll('.snippet-card');
        let delay = 0;
        
        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const description = card.querySelector('.card-text').textContent.toLowerCase();
            const code = card.querySelector('.snippet-code-wrapper pre').textContent.toLowerCase();
            const tagsAttr = card.getAttribute('data-tags');
            const tags = tagsAttr ? tagsAttr.toLowerCase().split(',') : [];
            
            const matchesSearch = title.includes(currentSearch) || 
                                 description.includes(currentSearch) || 
                                 code.includes(currentSearch) ||
                                 tags.some(t => t.includes(currentSearch));
            
            const matchesTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            const wrapper = card.parentElement;

            if (matchesSearch && matchesTag) {
                wrapper.style.display = 'block';
                wrapper.style.animation = 'none';
                wrapper.offsetHeight; /* trigger reflow */
                wrapper.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
                delay += 40; // Stagger effect
            } else {
                wrapper.style.display = 'none';
                wrapper.style.animation = 'none';
            }
        });
    };

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase();
            filterSnippets();
        });
    }

    tagButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tagButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTag = btn.getAttribute('data-tag');
            filterSnippets();
        });
    });

    // Copy to Clipboard logic
    window.copyToClipboard = (btn, textId) => {
        const textElement = document.getElementById(textId);
        const text = textElement.textContent;
        
        navigator.clipboard.writeText(text).then(() => {
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Copied!';
            btn.classList.replace('btn-outline-light', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-outline-light');
            }, 2000);
        });
    };

    // Edit Modal Logic
    const snippetForm = document.getElementById('snippetForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const snippetIdInput = document.getElementById('snippetId');

    window.openEditModal = (snippet) => {
        modalTitle.textContent = 'Edit Snippet';
        submitBtn.textContent = 'Update Snippet';
        snippetIdInput.value = snippet.id;

        snippetForm.title.value = snippet.title;
        snippetForm.description.value = snippet.description;
        snippetForm.code.value = snippet.code;
        snippetForm.language_id.value = snippet.language_id || '';

        // Handle tags
        const tagCheckboxes = snippetForm.querySelectorAll('input[name="tags[]"]');
        tagCheckboxes.forEach(cb => {
            cb.checked = snippet.tags.some(tag => tag.name === cb.nextElementSibling.textContent.trim());
        });

        const modal = new bootstrap.Modal(document.getElementById('addSnippetModal'));
        modal.show();
    };

    // Reset modal when closed
    const addSnippetModal = document.getElementById('addSnippetModal');
    if (addSnippetModal) {
        addSnippetModal.addEventListener('hidden.bs.modal', () => {
            modalTitle.textContent = 'Add New Snippet';
            submitBtn.textContent = 'Save Snippet';
            snippetIdInput.value = '';
            snippetForm.reset();
        });
    }

    // Settings Editing Logic
    const tagColorPicker = document.getElementById('tagColorPicker');
    const tagColorInput = document.getElementById('tagColor');
    if (tagColorPicker && tagColorInput) {
        tagColorPicker.addEventListener('input', (e) => {
            tagColorInput.value = e.target.value;
        });
        tagColorInput.addEventListener('input', (e) => {
            if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                tagColorPicker.value = e.target.value;
            }
        });
    }

    window.editTag = (tag) => {
        document.getElementById('tagId').value = tag.id;
        document.getElementById('tagName').value = tag.name;
        
        if (tagColorInput) tagColorInput.value = tag.color || '';
        if (tagColorPicker) {
            if (tag.color && /^#[0-9A-F]{6}$/i.test(tag.color)) {
                tagColorPicker.value = tag.color;
            } else {
                tagColorPicker.value = '#000000';
            }
        }
        
        document.getElementById('tagSubmitBtn').textContent = 'Update Tag';
        document.getElementById('tagName').focus();
    };

    window.editLanguage = (lang) => {
        document.getElementById('langId').value = lang.id;
        document.getElementById('langName').value = lang.name;
        document.getElementById('langClass').value = lang.prism_class;
        document.getElementById('langSubmitBtn').textContent = 'Update Language';
        document.getElementById('langName').focus();
    };

    window.editTerminalCommand = (cmd) => {
        document.getElementById('termId').value = cmd.id;
        document.getElementById('termTitle').value = cmd.title;
        document.getElementById('termCommand').value = cmd.command;
        document.getElementById('termDescription').value = cmd.description;
        document.getElementById('termSubmitBtn').textContent = 'Update Command';
        document.getElementById('termTitle').focus();
    };

    // View Modal Logic
    const viewModalTitle = document.getElementById('viewModalTitle');
    const viewModalCode = document.getElementById('viewModalCode');
    const viewModalTags = document.getElementById('viewModalTags');

    window.openViewModal = (snippet) => {
        if (viewModalTitle) viewModalTitle.textContent = snippet.title;
        
        const viewModalLanguage = document.getElementById('viewModalLanguage');
        if (viewModalLanguage) {
            viewModalLanguage.textContent = snippet.language_name || 'Plain Text';
        }

        if (viewModalTags) {
            viewModalTags.innerHTML = '';
            if (snippet.tags && snippet.tags.length > 0) {
                snippet.tags.forEach(tag => {
                    const span = document.createElement('span');
                    span.className = 'badge tag-badge me-1';
                    if (tag.color) {
                        span.style.setProperty('background-color', tag.color, 'important');
                        span.style.color = '#fff';
                    }
                    span.textContent = tag.name;
                    viewModalTags.appendChild(span);
                });
            }
        }

        if (viewModalCode) {
            viewModalCode.textContent = snippet.code;
            viewModalCode.className = 'language-' + (snippet.prism_class || 'none');
            // Re-apply Prism.js syntax highlighting if loaded
            if (window.Prism) {
                Prism.highlightElement(viewModalCode);
            }
        }
        const modal = new bootstrap.Modal(document.getElementById('viewSnippetModal'));
        modal.show();
    };
});
