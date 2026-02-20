<?php
require_once 'includes/functions.php';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Napověda a tipy</h2>
        <p class="text-white-50">Jak pracovat se snippety a Markdownem v DevBase.</p>
    </div>

    <!-- Markdown Overview -->
    <div class="col-lg-8 mb-4">
        <div class="glass-card p-4">
            <h4 class="text-white mb-4"><i class="bi bi-markdown me-2"></i> Markdown Tahák</h4>
            
            <div class="help-section mb-4">
                <h6 class="text-white-50 mb-2">Kombinace HTML a JavaScriptu</h6>
                <p class="text-white small mb-3">Pro nejlepší výsledek při ukládání mixu HTML a JS doporučujeme použít "Markdown" formát s kódovými bloky:</p>
                
                <div class="snippet-code-wrapper mb-3 position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard(this, 'help-md-1')">
                        Copy
                    </button>
                    <pre><code id="help-md-1" class="language-markdown"># Nadpis
Tady je HTML:
```html
<nav class="navbar">...</nav>
```

A tady k tomu JS:
```javascript
console.log('Hello World');
```</code></pre>
                </div>
            </div>

            <hr class="border-light opacity-10 my-4">

            <div class="help-section mb-4">
                <h6 class="text-white-50 mb-2">Základní formátování</h6>
                <div class="snippet-code-wrapper mb-3 position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard(this, 'help-md-2')">
                        Copy
                    </button>
                    <pre><code id="help-md-2" class="language-markdown">**Tučné písmo**
*Kurzíva*
- Seznam položka 1
- Seznam položka 2

[Odkaz](https://example.com)</code></pre>
                </div>
            </div>

            <hr class="border-light opacity-10 my-4">

            <div class="help-section mb-4">
                <h6 class="text-white-50 mb-2">Tabulky v Markdownu</h6>
                <p class="text-white small mb-3">Skvělé pro dokumentaci API nebo konfigurací:</p>
                <div class="snippet-code-wrapper mb-3 position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard(this, 'help-md-table')">
                        Copy
                    </button>
                    <pre><code id="help-md-table" class="language-markdown">| Parametr | Typ | Popis |
| :--- | :--- | :--- |
| `id` | int | Unikátní ID |
| `name` | string | Název položky |</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips Sidebar -->
    <div class="col-lg-4">
        <div class="glass-card p-4 mb-4">
            <h4 class="text-white mb-4"><i class="bi bi-lightbulb me-2"></i> Tipy pro vyhledávání</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Full-text v kódu:</strong>
                    Vyhledávání neprohledává jen názvy, ale i samotný **vnitřek kódu**. Můžeš tak najít snippet podle názvu funkce nebo proměnné.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Kombinované filtry:</strong>
                    Vyber tag (např. `React`) a pak začni psát. Filtr se aplikuje na už vyfiltrované výsledky.
                </li>
            </ul>
        </div>

        <div class="glass-card p-4">
            <h4 class="text-white mb-4"><i class="bi bi-tags me-2"></i> Strategie tagování</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Více tagů najednou:</strong>
                    Snippetu můžeš dát neomezeně tagů. Doporučujeme kombinovat **technologii** (např. `PHP`) a **účel** (např. `Utility`, `Database`).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Hierarchie:</strong>
                    I když systém tagy neřadí hierarchicky, můžeš použít prefixy jako `ui:button` nebo `api:auth`.
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
