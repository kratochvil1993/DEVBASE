document.addEventListener('DOMContentLoaded', () => {
    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
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
        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const description = card.querySelector('.card-text').textContent.toLowerCase();
            const code = card.querySelector('.snippet-code-wrapper pre').textContent.toLowerCase();
            const tags = Array.from(card.querySelectorAll('.tag-badge')).map(b => b.textContent.toLowerCase());
            
            const matchesSearch = title.includes(currentSearch) || 
                                 description.includes(currentSearch) || 
                                 code.includes(currentSearch) ||
                                 tags.some(t => t.includes(currentSearch));
            
            const matchesTag = currentTag === 'all' || tags.includes(currentTag.toLowerCase());

            if (matchesSearch && matchesTag) {
                card.parentElement.style.display = 'block';
            } else {
                card.parentElement.style.display = 'none';
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
            cb.checked = snippet.tags.includes(cb.nextElementSibling.textContent.trim());
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
    window.editTag = (tag) => {
        document.getElementById('tagId').value = tag.id;
        document.getElementById('tagName').value = tag.name;
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
});
