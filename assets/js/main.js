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

  // Navigation Map
  const navMap = {
    Digit1: "index.php",
    Digit2: "code.php",
    Digit3: "notes_drafts.php",
    Digit4: "notes.php",
    Digit5: "todo.php",
    Digit6: "inbox.php",
    // Fallback for some layouts
    Numpad1: "index.php",
    Numpad2: "code.php",
    Numpad3: "notes_drafts.php",
    Numpad4: "notes.php",
    Numpad5: "todo.php",
    Numpad6: "inbox.php",
  };

  // Global Keyboard Shortcuts
  document.addEventListener(
    "keydown",
    (e) => {
      // Navigation Shortcuts: Option + 1-4
      const isInput = ["INPUT", "TEXTAREA"].includes(e.target.tagName);
      const isEditable = e.target.isContentEditable;
      const isEditor =
        e.target.closest &&
        (e.target.closest(".CodeMirror") || e.target.closest(".ql-editor"));
      const isTyping = isInput || isEditable || isEditor;

      if (e.altKey && navMap[e.code]) {
        if (isTyping) return;
        e.preventDefault();
        e.stopPropagation();
        console.log("DevBase Shortcut: Navigating to " + navMap[e.code]);
        window.location.href = navMap[e.code];
        return;
      }

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
    },
    true,
  );

  // Auto-copy selection in preview modals
  document.addEventListener("mouseup", (e) => {
    // Only trigger if inside a preview modal or AI insight box
    const container = e.target.closest("#viewSnippetModal, #viewNoteModal, .ai-insight-box");
    if (!container) return;

    // Ignore if clicking on interactive elements or inside an editor
    if (e.target.closest("button, input, textarea, .ql-editor")) return;

    const selection = window.getSelection();
    const selectedText = selection.toString().trim();

    if (selectedText.length > 0) {
      navigator.clipboard
        .writeText(selectedText)
        .then(() => {
          // Find if there is a copy button in the current container to show feedback
          const copyBtn = container.querySelector(".copy-btn");
          if (copyBtn) {
            const originalHTML = copyBtn.innerHTML;
            const originalClass = copyBtn.className;

            copyBtn.innerHTML =
              '<i class="bi bi-check-all me-1"></i> Selection copied!';
            copyBtn.classList.add("btn-success");
            copyBtn.classList.remove("btn-outline-light");

            setTimeout(() => {
              copyBtn.innerHTML = originalHTML;
              copyBtn.className = originalClass;
            }, 1500);
          }
        })
        .catch((err) => {
          console.error("Failed to auto-copy: ", err);
        });
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
          : document.getElementById("note-" + updatedId)
            ? "note-"
            : document.getElementById("snippet-" + updatedId)
              ? "snippet-"
              : "";

    if (prefix) {
      const element = document.getElementById(prefix + updatedId);
      if (element) {
        // We wait a bit for animations and filters to settle
        setTimeout(() => {
          // Clear any inline animation from filters (like popIn) to allow highlight to run
          element.style.animation = "none";
          element.offsetHeight; // trigger reflow

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

  /**
   * Global Autosave Function
   * @param {Object} data - { id, content, name }
   * @param {HTMLElement} indicator - Element to show save status
   */
  window.fetchAutosave = (data, indicator = null) => {
    // Pokud indikátor už obsahuje "Ukládám", budeme se chtít vrátit k "Připraveno"
    const originalText = indicator && !indicator.innerHTML.includes("Ukládám") 
      ? indicator.innerHTML 
      : '<i class="bi bi-cloud-arrow-up me-1"></i> Připraveno';

    return fetch("api/api_autosave.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      keepalive: true, // Crucial for visibilitychange/pagehide
    })
      .then((res) => res.json())
      .then((res) => {
        if (indicator && res.status === "success") {
          indicator.innerHTML = `<i class="bi bi-cloud-check me-1"></i> Uloženo v ${res.time}`;
          indicator.classList.add("text-success");
          setTimeout(() => {
            indicator.innerHTML = originalText;
            indicator.classList.remove("text-success");
          }, 3000);
        }
        return res;
      })
      .catch((err) => {
        console.error("Autosave error:", err);
        if (indicator) {
          indicator.innerHTML = `<i class="bi bi-cloud-slash me-1"></i> Chyba ukládání`;
          indicator.classList.add("text-danger");
          // Reset after error too, so it doesn't stay red forever unless it's a hard fail
          setTimeout(() => {
            indicator.innerHTML = originalText;
            indicator.classList.remove("text-danger");
          }, 5000);
        }
      });
  };

  /**
   * Automatic Inbox Check
   */
  function initInboxAutoCheck() {
    console.log("DevBase: Iniciování Inbox Auto Check (v2.1)...");

    if (!window.DevBase) {
      console.log(
        "DevBase: Objekt DevBase nebyl nalezen, zkusím to znovu za 1s...",
      );
      setTimeout(initInboxAutoCheck, 1000);
      return;
    }

    const { inbox_enabled, inbox_auto_check } = window.DevBase.settings;
    console.log(
      `DevBase: Nastavení - Enabled: ${inbox_enabled}, AutoCheck: ${inbox_auto_check}`,
    );

    if (inbox_enabled != "1" || inbox_auto_check != "1") {
      console.log(
        "DevBase: Auto check je vypnut v nastavení (Enabled != 1 nebo AutoCheck != 1)",
      );
      return;
    }

    //const CHECK_INTERVAL = 60000; // 1 minuta
    const CHECK_INTERVAL = 300000; // 5 minut
    const WATCH_INTERVAL = 15000; // 15 sekund (kontrola stavu)

    const performCheck = async () => {
      console.log("DevBase: Spouštím automatickou synchronizaci inboxu...");
      localStorage.setItem("inbox_last_check", Date.now());

      try {
        const response = await fetch("./api/api_inbox_sync.php");
        const data = await response.json();

        if (data.status === "success" && window.updateGlobalStats) {
          if (data.count > 0) {
            console.log(
              "DevBase: Sync úspěšný, nalezeno nových položek:",
              data.count,
            );
          }
          updateGlobalStats(data);
        }
      } catch (error) {
        console.error("DevBase: Kritická chyba při auto-importu:", error);
      }
    };

    const checkAndRun = () => {
      const lastCheckStr = localStorage.getItem("inbox_last_check");
      const lastCheck = lastCheckStr ? parseInt(lastCheckStr) : 0;
      const now = Date.now();
      const diff = now - lastCheck;

      if (lastCheck === 0 || diff >= CHECK_INTERVAL) {
        performCheck();
      }
    };

    checkAndRun();
    setInterval(checkAndRun, WATCH_INTERVAL);
  }

  initInboxAutoCheck();
});
