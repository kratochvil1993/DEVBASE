document.addEventListener("DOMContentLoaded", () => {
  // Theme Toggle Logic
  const themeToggle = document.getElementById("themeToggle");
  const htmlElement = document.documentElement;

  // Load saved theme
  const savedTheme = localStorage.getItem("theme") || "dark";
  htmlElement.setAttribute("data-bs-theme", savedTheme);
  if (themeToggle) {
    themeToggle.checked = savedTheme === "dark";
  }

  if (themeToggle) {
    themeToggle.addEventListener("change", () => {
      const theme = themeToggle.checked ? "dark" : "light";
      htmlElement.setAttribute("data-bs-theme", theme);
      localStorage.setItem("theme", theme);
    });
  }

  // Unified Filtering logic
  const searchInput = document.getElementById("snippetSearch");
  const tagButtons = document.querySelectorAll("#tagFilters .btn");
  let currentSearch = localStorage.getItem("snippetSearch") || "";
  let currentTag = localStorage.getItem("snippetTag") || "all";

  // Restore initial UI state
  if (searchInput) {
    searchInput.value = currentSearch;
  }
  if (tagButtons.length > 0) {
    tagButtons.forEach((btn) => {
      if (btn.getAttribute("data-tag") === currentTag) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });
  }

  const filterSnippets = () => {
    const cards = document.querySelectorAll(".snippet-card");
    let delay = 0;
    let pinnedVisible = 0;
    let othersVisible = 0;

    cards.forEach((card) => {
      const title = card.querySelector(".card-title").textContent.toLowerCase();
      const description = card
        .querySelector(".card-text")
        .textContent.toLowerCase();
      const code = card
        .querySelector(".snippet-code-wrapper pre")
        .textContent.toLowerCase();
      const tagsAttr = card.getAttribute("data-tags");
      const tags = tagsAttr ? tagsAttr.toLowerCase().split(",") : [];

      const matchesSearch =
        title.includes(currentSearch) ||
        description.includes(currentSearch) ||
        code.includes(currentSearch) ||
        tags.some((t) => t.includes(currentSearch));

      const matchesTag =
        currentTag === "all" || tags.includes(currentTag.toLowerCase());

      const wrapper = card.parentElement;

      if (matchesSearch && matchesTag) {
        wrapper.style.display = "block";
        wrapper.style.animation = "none";
        wrapper.offsetHeight; /* trigger reflow */
        wrapper.style.animation = `popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${delay}ms both`;
        delay += 40; // Stagger effect
        if (wrapper.classList.contains("pinned")) pinnedVisible++;
        else othersVisible++;
      } else {
        wrapper.style.display = "none";
        wrapper.style.animation = "none";
      }
    });

    // Toggle headers
    const pinnedContainer = document.getElementById("pinnedSnippetsContainer");
    const othersHeader = document.getElementById("othersHeader");

    if (pinnedContainer) {
      pinnedContainer.classList.toggle("d-none", pinnedVisible === 0);
    }
    if (othersHeader) {
      othersHeader.classList.toggle(
        "d-none",
        pinnedVisible === 0 || othersVisible === 0,
      );
    }
  };

  // Initial filter application
  if (searchInput || tagButtons.length > 0) {
    filterSnippets();
  }

  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      currentSearch = e.target.value.toLowerCase();
      localStorage.setItem("snippetSearch", currentSearch);
      filterSnippets();
    });
  }

  tagButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      tagButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentTag = btn.getAttribute("data-tag");
      localStorage.setItem("snippetTag", currentTag);
      filterSnippets();
    });
  });

  // Copy to Clipboard logic
  window.copyToClipboard = (btn, textId) => {
    const textElement = document.getElementById(textId);
    const text = textElement.textContent;

    navigator.clipboard.writeText(text).then(() => {
      const originalText = btn.innerHTML;
      btn.innerHTML = "copied!";
      btn.classList.replace("btn-outline-light", "btn-success");

      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.replace("btn-success", "btn-outline-light");
      }, 2000);
    });
  };

  // Edit Modal Logic
  const snippetForm = document.getElementById("snippetForm");
  const modalTitle = document.getElementById("modalTitle");
  const submitBtn = document.getElementById("submitBtn");
  const snippetIdInput = document.getElementById("snippetId");

  window.openEditModal = (snippet) => {
    modalTitle.textContent = "Upravit snipet";
    submitBtn.textContent = "Aktualizovat snipet";
    snippetIdInput.value = snippet.id;

    snippetForm.title.value = snippet.title;
    snippetForm.description.value = snippet.description;
    snippetForm.code.value = snippet.code;
    snippetForm.language_id.value = snippet.language_id || "";

    // Handle tags
    const tagCheckboxes = snippetForm.querySelectorAll('input[name="tags[]"]');
    tagCheckboxes.forEach((cb) => {
      cb.checked = snippet.tags.some((tag) => tag.id == cb.value);
    });

    const modal = new bootstrap.Modal(
      document.getElementById("addSnippetModal"),
    );
    modal.show();
  };

  // Reset modal when closed
  const addSnippetModal = document.getElementById("addSnippetModal");
  if (addSnippetModal) {
    addSnippetModal.addEventListener("hidden.bs.modal", () => {
      modalTitle.textContent = "Přidat nový snipet";
      submitBtn.textContent = "Uložit snipet";
      snippetIdInput.value = "";
      snippetForm.reset();
    });
  }

  // Settings Editing Logic
  const tagForm = document.getElementById("tagForm");
  const tagColorPicker = document.getElementById("tagColorPicker");
  const tagColorInput = document.getElementById("tagColor");

  if (tagForm && tagColorInput) {
    tagForm.addEventListener("submit", (e) => {
      const colorValue = tagColorInput.value.trim();
      if (
        colorValue !== "" &&
        !/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/i.test(colorValue)
      ) {
        e.preventDefault();
        alert(
          "Barva musí začínat symbolem # a být v platném hexadecimálním formátu (např. #FF5733 nebo #F00).",
        );
        tagColorInput.focus();
      }
    });
  }

  if (tagColorPicker && tagColorInput) {
    tagColorPicker.addEventListener("input", (e) => {
      tagColorInput.value = e.target.value;
    });
    tagColorInput.addEventListener("input", (e) => {
      // Update picker only for valid 6-digit hex
      if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
        tagColorPicker.value = e.target.value;
      }
    });
  }

  window.editTag = (tag) => {
    document.getElementById("tagId").value = tag.id;
    document.getElementById("tagName").value = tag.name;

    if (tagColorInput) tagColorInput.value = tag.color || "";
    if (tagColorPicker) {
      if (tag.color && /^#[0-9A-F]{6}$/i.test(tag.color)) {
        tagColorPicker.value = tag.color;
      } else {
        tagColorPicker.value = "#000000";
      }
    }

    document.getElementById("tagSubmitBtn").textContent = "Aktualizovat";
    document.getElementById("tagName").focus();
  };

  // Note Tag Settings Logic
  const noteTagForm = document.getElementById("noteTagForm");
  const noteTagColorPicker = document.getElementById("noteTagColorPicker");
  const noteTagColorInput = document.getElementById("noteTagColor");

  if (noteTagForm && noteTagColorInput) {
    noteTagForm.addEventListener("submit", (e) => {
      const colorValue = noteTagColorInput.value.trim();
      if (
        colorValue !== "" &&
        !/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/i.test(colorValue)
      ) {
        e.preventDefault();
        alert(
          "Barva musí začínat symbolem # a být v platném hexadecimálním formátu.",
        );
        noteTagColorInput.focus();
      }
    });
  }

  if (noteTagColorPicker && noteTagColorInput) {
    noteTagColorPicker.addEventListener("input", (e) => {
      noteTagColorInput.value = e.target.value;
    });
    noteTagColorInput.addEventListener("input", (e) => {
      if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
        noteTagColorPicker.value = e.target.value;
      }
    });
  }

  window.editNoteTag = (tag) => {
    document.getElementById("noteTagId").value = tag.id;
    document.getElementById("noteTagName").value = tag.name;

    if (noteTagColorInput) noteTagColorInput.value = tag.color || "";
    if (noteTagColorPicker) {
      if (tag.color && /^#[0-9A-F]{6}$/i.test(tag.color)) {
        noteTagColorPicker.value = tag.color;
      } else {
        noteTagColorPicker.value = "#000000";
      }
    }

    document.getElementById("noteTagSubmitBtn").textContent = "Aktualizovat";
    document.getElementById("noteTagName").focus();
  };

  // Todo Tag Settings Logic
  const todoTagForm = document.getElementById("todoTagForm");
  const todoTagColorPicker = document.getElementById("todoTagColorPicker");
  const todoTagColorInput = document.getElementById("todoTagColor");

  if (todoTagForm && todoTagColorInput) {
    todoTagForm.addEventListener("submit", (e) => {
      const colorValue = todoTagColorInput.value.trim();
      if (
        colorValue !== "" &&
        !/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/i.test(colorValue)
      ) {
        e.preventDefault();
        alert(
          "Barva musí začínat symbolem # a být v platném hexadecimálním formátu.",
        );
        todoTagColorInput.focus();
      }
    });
  }

  if (todoTagColorPicker && todoTagColorInput) {
    todoTagColorPicker.addEventListener("input", (e) => {
      todoTagColorInput.value = e.target.value;
    });
    todoTagColorInput.addEventListener("input", (e) => {
      if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
        todoTagColorPicker.value = e.target.value;
      }
    });
  }

  window.editTodoTag = (tag) => {
    document.getElementById("todoTagId").value = tag.id;
    document.getElementById("todoTagName").value = tag.name;

    if (todoTagColorInput) todoTagColorInput.value = tag.color || "";
    if (todoTagColorPicker) {
      if (tag.color && /^#[0-9A-F]{6}$/i.test(tag.color)) {
        todoTagColorPicker.value = tag.color;
      } else {
        todoTagColorPicker.value = "#000000";
      }
    }

    document.getElementById("todoTagSubmitBtn").textContent = "Aktualizovat";
    document.getElementById("todoTagName").focus();
  };

  window.editLanguage = (lang) => {
    document.getElementById("langId").value = lang.id;
    document.getElementById("langName").value = lang.name;
    document.getElementById("langClass").value = lang.prism_class;
    document.getElementById("langSubmitBtn").textContent = "Aktualizovat jazyk";
    document.getElementById("langName").focus();
  };

  window.editTerminalCommand = (cmd) => {
    document.getElementById("termId").value = cmd.id;
    document.getElementById("termTitle").value = cmd.title;
    document.getElementById("termCommand").value = cmd.command;
    document.getElementById("termDescription").value = cmd.description;
    document.getElementById("termSubmitBtn").textContent =
      "Aktualizovat příkaz";
    document.getElementById("termTitle").focus();
  };

  // View Modal Logic
  const viewModalTitle = document.getElementById("viewModalTitle");
  const viewModalCode = document.getElementById("viewModalCode");
  const viewModalTags = document.getElementById("viewModalTags");

  window.openViewModal = (snippet) => {
    if (viewModalTitle) viewModalTitle.textContent = snippet.title;

    const viewModalLanguage = document.getElementById("viewModalLanguage");
    if (viewModalLanguage) {
      viewModalLanguage.textContent = snippet.language_name || "Prostý text";
    }

    if (viewModalTags) {
      viewModalTags.innerHTML = "";
      if (snippet.tags && snippet.tags.length > 0) {
        snippet.tags.forEach((tag) => {
          const span = document.createElement("span");
          span.className = "badge tag-badge me-1";
          if (tag.color) {
            span.style.setProperty("background-color", tag.color, "important");
            span.style.color = "#fff";
          }
          span.textContent = tag.name;
          viewModalTags.appendChild(span);
        });
      }
    }

    if (viewModalCode) {
      viewModalCode.textContent = snippet.code;
      viewModalCode.className = "language-" + (snippet.prism_class || "none");

      const viewModalPre = document.getElementById("viewModalPre");
      const viewModalMarkdown = document.getElementById("viewModalMarkdown");

      if (snippet.prism_class === "markdown" && window.marked) {
        if (viewModalPre) viewModalPre.style.display = "none";
        if (viewModalMarkdown) {
          viewModalMarkdown.style.display = "block";
          viewModalMarkdown.innerHTML = marked.parse(snippet.code);
          if (window.Prism) {
            viewModalMarkdown.querySelectorAll("pre code").forEach((block) => {
              Prism.highlightElement(block);
            });
          }
        }
      } else {
        if (viewModalMarkdown) viewModalMarkdown.style.display = "none";
        if (viewModalPre) viewModalPre.style.display = "block";
        // Re-apply Prism.js syntax highlighting if loaded
        if (window.Prism) {
          Prism.highlightElement(viewModalCode);
        }
      }
    }

    // Edit button integration
    const editBtn = document.getElementById("editSnippetFromViewBtn");
    if (editBtn) {
      editBtn.onclick = () => {
        const viewModalEl = document.getElementById("viewSnippetModal");
        const viewModal = bootstrap.Modal.getInstance(viewModalEl);
        if (viewModal) viewModal.hide();
        openEditModal(snippet);
      };
    }

    const modal = new bootstrap.Modal(
      document.getElementById("viewSnippetModal"),
    );
    modal.show();
  };

  // Render markdown snippets directly in the grid preview
  const renderMarkdownSnippets = () => {
    if (!window.marked) return;
    document.querySelectorAll(".snippet-card").forEach((card) => {
      const codeContainer = card.querySelector("pre code");
      if (
        codeContainer &&
        codeContainer.classList.contains("language-markdown")
      ) {
        const preElement = codeContainer.parentElement;

        if (
          !preElement.nextElementSibling?.classList.contains("markdown-preview")
        ) {
          const markdownDiv = document.createElement("div");
          markdownDiv.className = "markdown-preview overflow-hidden h-100 p-2";
          markdownDiv.innerHTML = marked.parse(codeContainer.textContent);

          if (window.Prism) {
            markdownDiv.querySelectorAll("pre code").forEach((block) => {
              Prism.highlightElement(block);
            });
          }

          preElement.style.display = "none";
          preElement.parentElement.appendChild(markdownDiv);
        }
      }
    });
  };
  renderMarkdownSnippets();

  // Global Keyboard Shortcuts
  document.addEventListener("keydown", (e) => {
    // Check for Option+F (altKey) - using e.code for Safari compatibility
    if (e.altKey && e.code === "KeyF") {
      const searchInput =
        document.getElementById("snippetSearch") ||
        document.getElementById("noteSearch");

      if (searchInput) {
        e.preventDefault();
        searchInput.focus();
        searchInput.select(); // Select existing text for easy replacement
      }
    }
    // Check for Option+N (altKey) - New Snippet/Note
    if (e.altKey && e.code === "KeyN") {
      const newBtn =
        document.getElementById("newSnippetBtn") ||
        document.getElementById("newNoteBtn");

      if (newBtn && !newBtn.classList.contains("d-none")) {
        e.preventDefault();
        newBtn.click();
      }
    }
  });

  // Handle scrolling to updated item
  const urlParams = new URLSearchParams(window.location.search);
  const updatedId = urlParams.get("updated_id");
  if (updatedId) {
    const prefix = document.getElementById("snippet-card-" + updatedId)
      ? "snippet-card-"
      : document.getElementById("note-card-" + updatedId)
        ? "note-card-"
        : document.getElementById("todo-card-" + updatedId)
          ? "todo-card-"
          : "";

    if (prefix) {
      const element = document.getElementById(prefix + updatedId);
      if (element) {
        // We wait a bit for animations and filters to settle
        setTimeout(() => {
          element.scrollIntoView({ behavior: "smooth", block: "center" });
          element.classList.add("updated-highlight");

          // Clean up URL without reload to keep it tidy
          const cleanSearch = window.location.search
            .replace(new RegExp("[?&]updated_id=" + updatedId), "")
            .replace(/^&/, "?");
          const newUrl =
            window.location.pathname + (cleanSearch === "?" ? "" : cleanSearch);
          window.history.replaceState({}, document.title, newUrl);
        }, 600);
      }
    }
  }

  // Help page Submenu Scroll-Spy Logic
  const helpSubmenu = document.getElementById("helpSubmenu");
  const helpSections = document.querySelectorAll(".help-section");
  const currentSectionLabel = document.getElementById("currentSectionLabel");

  if (helpSubmenu && helpSections.length > 0) {
    const observerOptions = {
      root: null,
      rootMargin: "-100px 0px -70% 0px", // Focus on the top part of the viewport
      threshold: 0,
    };

    const updateActiveLink = (id) => {
      const links = helpSubmenu.querySelectorAll(".submenu-link");
      links.forEach((link) => {
        const isActive = link.getAttribute("data-section") === id;
        link.classList.toggle("active", isActive);

        if (isActive && currentSectionLabel) {
          currentSectionLabel.innerHTML = `<i class="bi bi-list me-2"></i> Sekce: ${link.textContent}`;

          // On mobile, if user scrolls, we might want to auto-collapse the menu if it was open
          // But usually, only if they clicked. Here we just update the text.
        }
      });
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          updateActiveLink(entry.target.id);
        }
      });
    }, observerOptions);

    helpSections.forEach((section) => observer.observe(section));

    // Smooth scroll for submenu links (optional, scroll-behavior: smooth in CSS is better but this handles mobile collapse)
    helpSubmenu.querySelectorAll(".submenu-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const targetId = link.getAttribute("href");
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 90,
            behavior: "smooth",
          });

          // Collapse menu on mobile after click
          const collapseEl = document.getElementById("helpSubmenuCollapse");
          if (
            collapseEl &&
            window.innerWidth < 768 &&
            collapseEl.classList.contains("show")
          ) {
            const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            if (bsCollapse) bsCollapse.hide();
          }
        }
      });
    });
  }
});
