<?php
require_once 'includes/functions.php';
include 'includes/header.php';
?>
<div class="container">
<div class="row">
    <div class="col-12 mb-4">
        <h2 class="text-white fw-bold">Nápověda a tipy</h2>
        <p class="text-white-50">Jak pracovat se snippety a Markdownem v DevBase.</p>
    </div>

    <!-- Sticky Submenu Navigation -->
    <div class="col-12 sticky-top help-submenu-outer-wrapper mb-4">
        <div class="help-submenu-inner glass-card p-2">
            <!-- Mobile Toggle Label (Hidden on Desktop) -->
            <button class="btn btn-link text-white d-md-none w-100 d-flex justify-content-between align-items-center text-decoration-none px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpSubmenuCollapse">
                <span id="currentSectionLabel"><i class="bi bi-list me-2"></i> Vyberte sekci</span>
                <i class="bi bi-chevron-down small opacity-50"></i>
            </button>
            
            <!-- Links Container -->
            <div class="collapse d-md-block " id="helpSubmenuCollapse">
                <div class="d-flex flex-column flex-md-row justify-content-center gap-1 gap-md-2 p-1 p-md-0 overflow-x-auto no-scrollbar" id="helpSubmenu">
                    <a href="#markdown" class="submenu-link active" data-section="markdown">Markdown</a>
                    <a href="#searching" class="submenu-link" data-section="searching">Hledání</a>
                    <a href="#ai" class="submenu-link" data-section="ai">AI Funkce</a>
                    <a href="#shortcuts" class="submenu-link" data-section="shortcuts">Zkratky</a>
                    <a href="#tagging" class="submenu-link" data-section="tagging">Tagy</a>
                    <a href="#code-drafts" class="submenu-link" data-section="code-drafts">Code</a>
                    <a href="#note-drafts" class="submenu-link" data-section="note-drafts">Drafts</a>
                    <a href="#notes" class="submenu-link" data-section="notes">Poznámky</a>
                    <a href="#todo" class="submenu-link" data-section="todo">TODO</a>
                    <a href="#inbox" class="submenu-link" data-section="inbox">Inbox</a>
                    <a href="#security" class="submenu-link" data-section="security">Bezpečnost</a>
                    <a href="#backup" class="submenu-link" data-section="backup">Záloha</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Markdown Overview -->
    <div class="col-lg-12 mb-4 help-section" id="markdown">
        <div class="glass-card no-jump p-4">
            <h4 class="text-white mb-4"><i class="bi bi-markdown me-2"></i> Markdown Tahák</h4>
            
            <div class="help-section mb-4">
                <h6 class="text-white-50 mb-2">Kombinace HTML a JavaScriptu</h6>
            
                
                <div class="snippet-code-wrapper mb-3 position-relative">
                    <button class="btn btn-sm btn-outline-light copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard(this, 'help-md-1')">
                        copy
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
                        copy
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
                        copy
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
    <div class="col-lg-12">
        <div class="glass-card no-jump p-4 mb-4 help-section" id="searching">
            <h4 class="text-white mb-4"><i class="bi bi-lightbulb me-2"></i> Tipy pro vyhledávání</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Full-text v kódu:</strong>
                    Vyhledávání neprohledává jen názvy, ale i samotný **vnitřek kódu**. Můžeš tak najít snippet podle názvu funkce nebo proměnné.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Kombinované filtry:</strong>
                    Vyberte tag (např. `React`) a následně použijte vyhledávání pro zúžení výsledků. Pozor: pokud již máte něco vyhledáno a kliknete na tag, vyhledávací pole se pro větší přehlednost **automaticky vymaže**, aby se zobrazily všechny položky s daným štítkem.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Rychlé vymazání (X):</strong>
                    V pravé části vyhledávacího pole najdete červenou ikonu **X**, která se objeví při psaní. Jedním kliknutím tak můžete okamžitě smazat hledaný výraz a vrátit se k plnému výpisu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Hledání v poznámkách:</strong>
                    Stejně jako u snipetů, i v sekci **Poznámky** najdete vyhledávací pole, které filtruje karty v reálném čase podle názvu i obsahu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Detailní náhled a rychlá úprava:</strong>
                    Kliknutím na kartu snipetu otevřete **detailní náhled** v modálním okně. Pokud potřebujete provést změnu, můžete využít tlačítko **Upravit** přímo v tomto náhledu, které vás okamžitě přepne do editačního režimu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Automatické kopírování výběru:</strong>
                    V detailním náhledu snippetu nebo poznámky se jakýkoliv **označený text automaticky zkopíruje** do schránky v momentě, kdy pustíte tlačítko myši. Ideální pro bleskové vytažení části kódu bez nutnosti stisknout Ctrl+C.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Připínání (Pinning):</strong>
                    Důležité snipety si můžete připnout ikonou špendlíku. Zůstanou pak v samostatné sekci **PŘIPNUTÉ** vždy na začátku seznamu, bez ohledu na ostatní filtry.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Vlastní řazení snipetů:</strong>
                    Kliknutím na **Upravit pořadí** můžete snipety libovolně přetahovat. Řazení funguje nezávisle pro připnuté a pro ostatní snipety. Při aktivaci se karty rozvibrují pro jasnou vizuální odezvu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Statistiky v menu:</strong>
                    V bočním panelu najdete bleskový přehled počtu snippetů, poznámek a **aktivních** úkolů, včetně počtu rozpracovaných kódových a textových draftů.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Persistence vzhledu a filtrů:</strong>
                    Vaše volba mezi tmavým a světlým režimem je bezpečně uložena v databázi. V sekcích **Snippety** a **Poznámky** si navíc aplikace pamatuje váš poslední hledaný výraz i vybraný štítek (uloženo lokálně), takže i po přechodu jinam a návratu najdete své rozpracované výsledky tam, kde jste skončili (sekce TODO se pro přehlednost při načtení vždy resetuje na "Vše"). V **Nastavení** si také můžete přizpůsobit **velikost písma** v editorech a detailech a zvolit si své oblíbené **vizuální téma editoru** (např. Dracula, Nord, Monokai). Pro bleskovou změnu velikosti písma můžete využít **ikonu ozubeného kolečka (Gear)** v horní navigaci, která otevře menu s rychlou volbou.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Skočit k obsahu (Mobil):</strong>
                    Na mobilních zařízeních se aplikace snaží šetřit váš čas – při přepínání tabů v sekcích Drafts nebo při kliknutí na hlavičku aktivního editačního panelu vás automaticky **plynule posune k editoru**, abyste nemuseli zbytečně scrollovat dolů.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Automatické sledování změn:</strong>
                    Po každém uložení, úpravě nebo vytvoření nového záznamu vás aplikace automaticky přesune na danou položku a zvýrazní ji jemným animovaným efektem. Nemusíte tak položku znovu hledat v seznamu.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mb-4 help-section" id="shortcuts">
            <h4 class="text-white mb-4"><i class="bi bi-keyboard me-2"></i> Globální klávesové zkratky</h4>
            <p class="text-white-50 small mb-3">Tyto zkratky fungují napříč celou aplikací pro zrychlení vaší práce:</p>
            
            <h6 class="text-white-50 small text-uppercase fw-bold mb-3" style="font-size: 0.7rem; letter-spacing: 1px;">Navigace mezi sekcemi</h6>
            <ul class="text-white-50 small list-unstyled mb-4">
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 1</span> nebo <span class="badge bg-primary me-2">Alt + 1</span> <strong class="text-white ms-2">Snippets:</strong> Přejde na hlavní přehled snippetů.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 2</span> nebo <span class="badge bg-primary me-2">Alt + 2</span> <strong class="text-white ms-2">Code Drafts:</strong> Otevře drafty pro kód.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 3</span> nebo <span class="badge bg-primary me-2">Alt + 3</span> <strong class="text-white ms-2">Note Drafts:</strong> Otevře drafty pro poznámky.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 4</span> nebo <span class="badge bg-primary me-2">Alt + 4</span> <strong class="text-white ms-2">Notes:</strong> Přejde na poznámky.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 5</span> nebo <span class="badge bg-primary me-2">Alt + 5</span> <strong class="text-white ms-2">TODO:</strong> Otevře správu úkolů.
                </li>
            </ul>

            <h6 class="text-white-50 small text-uppercase fw-bold mb-3" style="font-size: 0.7rem; letter-spacing: 1px;">Akce a Vyhledávání</h6>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + F</span> nebo <span class="badge bg-primary me-2">Alt + F</span>
                    <strong class="text-white ms-2">Rychlé vyhledávání:</strong> Okamžitě zaměří (focus) vyhledávací pole v aktuální sekci.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + N</span> nebo <span class="badge bg-primary me-2">Alt + N</span>
                    <strong class="text-white ms-2">Nový záznam:</strong> Otevře modální okno pro přidání nového snippetu nebo poznámky.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + L</span> nebo <span class="badge bg-primary me-2">Alt + L</span>
                    <strong class="text-white ms-2">Skočit do editoru:</strong> V sekci Code Drafts rychle zaměří kurzor do editoru kódu.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Ctrl + S</span>
                    <strong class="text-white ms-2">Rychlé uložení / Odeslání:</strong> Uloží kód v editoru nebo odešle formulář v modálním okně.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 help-section" id="tagging">
            <h4 class="text-white mb-4"><i class="bi bi-tags me-2"></i> Strategie tagování</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Barvy a validace štítků:</strong>
                    V **Nastavení** můžete každému štítku přiřadit barvu (hexagonální HEX kód). Štítky jsou pak barevně odlišeny v přehledu, při filtrování a nově i přímo v seznamu při výběru štítků v modálních oknech.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Oddělené tagy pro vše:</strong>
                    Systém tagů je oddělený pro tři hlavní kategorie. V Nastavení jasně vidíte a spravujete, které tagy patří k programátorským snipetům, které k poznámkám a které k vašim úkolům (TODO). Díky tomu můžete mít štítky se stejným názvem (např. "Inbox") v různých kategoriích, aniž by se navzájem ovlivňovaly.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Vlastní řazení tagů:</strong>
                    V **Nastavení** si můžete tagy libovolně seřadit ve všech kategoriích pomocí funkce **Drag & Drop**. Toto pořadí se pak promítne i do filtrů a výběrů v aplikaci. Při aktivaci řazení se prvky jemně rozvibrují pro lepší vizuální odezvu.
                </li>
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

        <div class="glass-card no-jump p-4 mt-4 help-section" id="code-drafts">
            <h4 class="text-white mb-4"><i class="bi bi-braces me-2"></i> Práce s Code</h4>
            <p class="text-white small mb-3">Tato sekce slouží jako vaše "pískoviště" pro libovolný kód, konfigurační řetězce nebo technické poznámky, které chcete mít neustále po ruce předtím, než se rozhodnete je trvale uložit do snippetů.</p>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Více draftů (Taby), Persistence a Autosave:</strong>
                    V sekci Code Drafts můžete mít otevřeno **mnoho různých draftů** současně. Mezi nimi přepínáte pomocí tabů nad editorem. Každý draft si můžete **přejmenovat** jednoduše kliknutím na jeho název (změna názvu se pro maximální pohodlí po kliknutí jinam ihned automaticky uloží). Aplikace si automaticky pamatuje, který tab jste měli naposledy otevřený. Vaše práce je chráněna funkcí **Autosave**, která automaticky ukládá rozpracovaný kód v pravidelných intervalech.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Poslat do Snippetů / Poznámek:</strong>
                    Pokud máte hotový kód, který chcete trvale uložit, využijte tlačítko <i class="bi bi-send me-1"></i> **Poslat do**. To vám umožní vybraný draft okamžitě převést na plnohodnotný **Snippet** (včetně jazyka a popisu) nebo do **Poznámek** (obsah se vloží jako kódový blok). Po úspěšném přesunu se původní draft smaže.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Profesionální funkce a Color Picker:</strong>
                    Editor je postaven na **CodeMirror 5** a podporuje automatické doplňování závorek, zvýraznění syntaxe a funkci **Folding** (skládání kódu). V nastavení si navíc můžete zvolit jeden z mnoha **vizuálních vzhledů** (Dracula, Nord, Monokai atd.) podle toho, na co jste zvyklí ze svého IDE. Pokud v kódu napíšete barvu v HEX nebo RGB formátu, editor u ní automaticky zobrazí **barevný náhled**, na který můžete kliknout a barvu změnit pomocí integrovaného kapátka.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">AI nástroje v editoru:</strong>
                    Přímo v horní liště editoru najdete AI menu, které vám umožní označený nebo celý kód **Vysvětlit**, **Refaktorovat**, spustit **Debugger** nebo kód automaticky **Zformátovat** (Beautify / Minify) pro lepší přehlednost.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Klávesové zkratky (v sekci Code Drafts):</strong>
                    <ul class="mt-2 text-white">
                        <li><span class="badge bg-primary me-2">Option + L</span> <strong>Skok do editoru</strong></li>
                        <li><span class="badge bg-primary me-2">Ctrl + S</span> <strong>Uložit</strong></li>
                        <li><span class="badge bg-primary me-2">Ctrl + Space</span> <strong>Našeptávání</strong></li>
                        <li><span class="badge bg-primary me-2">Option + N</span> <strong>Nový draft</strong></li>
                        <li><span class="badge bg-primary me-2">Option + W</span> <strong>Zavřit draft</strong></li>
                        <li><span class="badge bg-primary me-2">Option + ← / →</span> <strong>Přepínat drafty</strong></li>
                    </ul>
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Optimalizace pro mobil:</strong>
                    Při přepínání mezi taby na mobilních zařízeních vás aplikace **automaticky posune k editoru**, abyste mohli ihned začít psát bez zbytečného scrollování.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="note-drafts">
            <h4 class="text-white mb-4"><i class="bi bi-journal-plus me-2"></i> Práce s Drafts</h4>
            <p class="text-white small mb-3">Rychlé pískoviště pro vaše textové poznámky a nápady. Ideální místo pro rozepsání obsahu, než ho finálně zařadíte do kategorií.</p>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">WYSIWYG Editor:</strong>
                    Na rozdíl od kódových draftů zde píšete v plnohodnotném textovém editoru (Quill.js) se snadným formátováním (tučné, nadpisy, seznamy).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Organizace v tabech a Autosave:</strong>
                    I zde můžete pracovat na více poznámkách současně díky systému tabů. Každý draft si můžete libovolně pojmenovat. Stejně jako u kódových draftů, i zde funguje **automatické ukládání (Autosave)** na pozadí.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Přesun do Poznámek a kopírování:</strong>
                    Pomocí tlačítka <i class="bi bi-send me-1"></i> **Poslat do** můžete rozpracovaný draft kdykoliv převést na trvalou položku v sekci **Poznámky** (včetně výběru štítků). Pokud potřebujete text jen rychle dostat jinam, využijte tlačítko <i class="bi bi-clipboard me-1"></i> **Kopírovat**.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">AI Nástroje a Vlastní prompt:</strong>
                    V Note Drafts jsou k dispozici pokročilé AI funkce pro strukturování textu nebo extrakci úkolů. Unikátní funkcí je **AI Prompt Bar**, který vyvoláte z AI menu. Umožní vám zadat AI libovolnou instrukci (např. "přelož do angličtiny" nebo "přepiš jako formální dopis") a AI ji provede přímo s vaším textem.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="notes">
            <h4 class="text-white mb-4"><i class="bi bi-journal-text me-2"></i> Práce s Poznámkami</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Štítkování poznámek:</strong>
                    Stejně jako u snipetů, i poznámkám nyní můžete přiřadit vlastní barevné tagy pro mnohem lepší organizaci a následné filtrování.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Zápis s podporou Quill Editoru:</strong>
                    Tvorba a úprava poznámek probíhá v moderním WYSIWYG editoru, který umožňuje snadné formátování textu (nadpisy, stylování textu, seznamy) přímo v grafickém rozhraní.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Archivace a vyhledávání:</strong>
                    Méně aktuální nebo splněné poznámky můžete 1 kliknutím přesunout do <strong>Archivu poznámek</strong> (oranžová ikonka krabice) a udržet tak hlavní výpis přehledný. V archivu můžete poznámky **filtrovat podle štítků** nebo v nich **fulltextově vyhledávat**. Z archivu je lze kdykoliv obnovit nebo trvale smazat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Vlastní řazení a připínání:</strong>
                    Poznámky lze připnout špendlíkem nahoru. Přes tlačítko **Upravit pořadí** pak můžete měnit jejich posloupnost (zvlášť v připnutých a zvlášť v ostatních). Karty se při tom jemně rozvibrují.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Zvýraznění kódu:</strong>
                    I poznámkám můžeš přiřadit jazyk. V detailu pak uvidíš kód krásně obarvený a připravený ke kopírování.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Nastavení:</strong>
                    Pokud poznámky nepoužíváš, můžeš je v **Nastavení** úplně skrýt, aby nepřekážely v menu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Rychlé přepínání:</strong>
                    V hlavičce aplikace najdete tlačítko pro rychlé přepnutí mezi hlavními sekcemi jako jsou **Snipety**, **Poznámky** a **TODO** (viditelné, pokud jsou povoleny v Nastavení). U sekce TODO můžete také vidět počet aktivních úkolů v barevném kolečku (badge).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Nastavení viditelnosti:</strong>
                    V **Nastavení** můžete kromě sekcí skrýt i samotný přepínač **Dark modu** nebo právě zmíněný **badge** u TODO úkolů, aby rozhraní zůstalo přesně takové, jaké ho chcete.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="todo">
            <h4 class="text-white mb-4"><i class="bi bi-check2-square me-2"></i> Správa úkolů (TODO)</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Přidávání úkolů:</strong>
                    V horní části najdete panel, který je vizuálně sjednocen s vyhledávacím barem. Jednoduše napište co potřebujete udělat a stiskněte Enter nebo tlačítko pro přidání. Pro maximální efektivitu se při vytváření úkolu automaticky předvolí první štítek z vašeho seznamu (pokud existuje).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Detailní náhled a Úpravy:</strong>
                    Kliknutím na text úkolu nově otevřete **detailní modální okno** s celkovým přehledem o daném úkolu (včetně rozepsané poznámky, termínu, času a štítků), přičemž režim úloh je chráněn proti nechtěným přepisům. Pokud chcete úkol upravit, stačí kliknout na vyhrazené tlačítko pro editaci.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Štítkování úkolů:</strong>
                    Úkolům můžete přiřazovat barevné štítky. Buď při rychlém přidání (jeden štítek), nebo přes ikonu tužky (neomezeně štítků). Nad seznamem úkolů pak najdete filtry, které vám umožní zobrazit jen úkoly s konkrétním tématem.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Odškrtnutí a Archivace:</strong>
                    Kliknutím na prázdný čtvereček u úkolu se označí jako splněný a automaticky přesune do sekce <strong>Archiv TODO</strong>. V archivu můžete úkoly **filtrovat podle použitých štítků** (např. abyste viděli jen splněné úkoly z konkrétního projektu). Celý archiv lze také vymazat jedním kliknutím na tlačítko **Vysypat archiv**. Splněné úkoly lze z archivu kdykoliv obnovit zpět mezi aktivní.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Trvalé smazání:</strong>
                    Aktivní nebo dříve archivovaný úkol lze okamžitě a nenávratně odstranit kliknutím na ikonu koše.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Připínání a řazení podle priorit:</strong>
                    Důležité úkoly si připněte pomocí špendlíku. Pořadí úkolů můžete libovolně měnit přes tlačítko **Upravit pořadí**. Úkoly se v režimu úprav jemně rozvibrují a lze je přetahovat v rámci sekcí "Připnuté" a "Ostatní".
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Badge s počtem úkolů:</strong>
                    V hlavní navigaci se u odkazu na TODO může zobrazovat počet aktivních (nearchivovaných) úkolů. Tuto funkci lze vypnout v **Nastavení**.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Termíny splnění (Deadlines):</strong>
                    U každého úkolu můžete nastavit konkrétní datum a čas splnění. Pokud se termín blíží (dnes nebo zítra), u úkolu se objeví žlutý odznak **Blíží se**. Pokud termín již vypršel, objeví se červený odznak **Po termínu**. Termíny a časy lze zadat při přidávání úkolu nebo dodatečně přes ikonu tužky. **Tip:** Pokud při přidávání úkolu zvolíte pouze čas, aplikace za vás automaticky vyplní dnešní datum.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Systém upozornění (Notifikace):</strong>
                    V pravé části horní navigace se nachází **ikona zvonečku**, která vás upozorní na úkoly s vypršeným nebo blížícím se termínem. Ikona obsahuje badge s počtem připomínek. Červená barva a pulzování značí úkoly po termínu, oranžová pak úkoly zítřejší. Kliknutím na zvoneček otevřete rychlý přehled s přímými odkazy na konkrétní úkoly. Po prokliknutí z upozornění se daný úkol okamžitě otevře v detailním náhledu a v seznamu se přibližně na 2 vteřiny **vizuálně zvýrazní**, abyste jej vůbec nemuseli hledat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">AI Bojový plán (Strategický souhrn):</strong>
                    Pokud máte aktivní AI, v sekci TODO najdete fialové tlačítko **AI Souhrn**. AI analyzuje všechny vaše aktivní úkoly, jejich priority (připnutí) a termíny splnění, a sestaví vám přehledný strategický plán, jak úkoly nejlépe odbavit.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Responzivní rozvržení:</strong>
                    Na mobilních zařízeních a menších obrazovkách se ovládací prvky úkolů automaticky přizpůsobí – tlačítka pro editaci a připínání se přesunou do horního rohu, aby zbyl maximální prostor pro text úkolu a panel pro přidávání se odsunul pod ně pro lepší ovladatelnost.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="ai">
            <h4 class="text-white mb-4"><i class="bi bi-robot me-2"></i> Integrace Umělé Inteligence (AI)</h4>
            <p class="text-white small mb-3">DevBase využívá pokročilé modely Google Gemini a OpenAI k analýze kódu, generování souhrnů a kontrole textů. Všechny AI funkce jsou v rozhraní zvýrazněny fialovou barvou s prémiovým efektem záře.</p>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Nastavení a aktivace:</strong>
                    V **Nastavení** (sekce AI Nastavení) zvolte svého preferovaného poskytovatele (**Gemini** nebo **OpenAI**) a vložte příslušný API klíč. Zde si také můžete vybrat konkrétní model (např. **GPT-4o Mini**, **GPT-5.2**, **Gemini 2.5 Pro** nebo **Gemini 3.1 Flash Lite**) a ověřit platnost klíče tlačítkem **Otestovat API**. Pokud je klíč platný a poskytovatel vybrán, v celé aplikaci se zpřístupní AI nástroje. Všechny AI funkce lze také v nastavení jedním kliknutím **globálně vypnout**, což skryje všechna fialová tlačítka v celé aplikaci pro dokonale čisté rozhraní.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Vysvětlení kódu (Snippety) a navigace:</strong>
                    V detailním náhledu každého snippetu najdete fialové tlačítko **Vysvětlit kód**. AI analyzuje váš kód a vypíše srozumitelný souhrn toho, co kód dělá, včetně vysvětlení klíčových částí.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Chytré názvy (Poznámky):</strong>
                    Při psaní poznámky v editačním modálu můžete kliknout na ikonu robota u pole Název. AI na základě rozepsaného obsahu navrhne výstižný titulek, který okamžitě vyplní.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Souhrny a pravopis (Poznámky):</strong>
                    V detailním náhledu poznámky najdete v liště AI nástroje:
                    <ul class="mt-2">
                        <li><strong>Vytvořit souhrn:</strong> AI přečte celou poznámku a vytvoří z ní stručný seznam v odrážkách.</li>
                        <li><strong>Kontrola pravopisu:</strong> Provede revizi českého textu a upozorní na chyby nebo navrhne lepší stylistiku.</li>
                        <li><strong>Extrahovat úkoly:</strong> AI projde text a automaticky z něj vytvoří seznam TODO úkolů, které si můžete uložit.</li>
                    </ul>
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Zpracování kódu a draftů:</strong>
                    Přímo v editorech draftů může AI kód nejen vysvětlit, ale také **zformátovat** (beautify) nebo zmenšit (minify). Tyto vylepšené AI funkce pro formátování kódu jsou nyní konzistentně k dispozici v Code Drafts i Note Drafts. U Note Drafts pak navíc můžete využít **AI Prompt Bar** pro jakoukoliv vlastní manipulaci s textem na základě vašeho zadání.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Strategický souhrn úkolů:</strong>
                    V sekci TODO vám AI pomůže se stanovením priorit díky funkci **AI Souhrn**, která vygeneruje "Bojový plán" pro vaše resty a blížící se termíny.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Soukromí:</strong>
                    Vaše API klíče jsou bezpečně uloženy v lokální databázi. Obsah je odesílán na servery poskytovatelů (Google/OpenAI) pouze v momentě, kdy explicitně kliknete na AI tlačítko.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="inbox">
            <h4 class="text-white mb-4"><i class="bi bi-mailbox me-2"></i> E-mailový Inbox</h4>
            <p class="text-white small mb-3">Tato funkce vám umožní vytvořit poznámku nebo úkol jednoduše tím, že pošlete e-mail do své schránky. Aplikace si ho přes IMAP stáhne a roztřídí. Celý proces je nyní plně zautomatizován.</p>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Automatická synchronizace a Notifikace:</strong>
                    Aplikace každých **5 minut automaticky kontroluje** vaši schránku (tento interval lze v nastavení vypnout). Pokud dorazí nové zprávy, u položky **Inbox** v hlavním menu se objeví modrý badge s počtem novinek a aplikace vás na ně upozorní systémovou notifikací (zvonečkem) v hlavičce. Systém si pamatuje, které zprávy jste již viděli, a badge zmizí až ve chvíli, kdy sekci Inbox navštívíte. Nové zprávy lze také bleskově načíst ručně pomocí zeleného tlačítka **Načíst nové**. Systém je vybaven ochranou proti duplicitám, takže i při opakovaném odeslání stejného mailu (např. přes Make.com) se v aplikaci vytvoří pouze jeden záznam. Celou historii stažených zpráv můžete kdykoliv promazat tlačítkem **Vymazat historii**.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Konfigurace (IMAP):</strong>
                    V **Nastavení** (sekce E-mailový Inbox) vyplňte údaje k vaší e-mailové schránce. Standardně se používá port **993** (pro SSL/TLS). Funkčnost můžete okamžitě ověřit tlačítkem **Testovat spojení**. Zde také najdete přepínač pro zapnutí/vypnutí automatické kontroly na pozadí.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Rozřazování pomocí tagů (@):</strong>
                    Aplikace určuje, co má s e-mailem udělat, podle tagu v jeho **předmětu**:
                    <ul class="mt-2">
                        <li><code>@note</code> – Vytvoří novou **Poznámku**.</li>
                        <li><code>@todo</code> – Vytvoří nový **Úkol**.</li>
                        <li><code>@draft</code> – Vytvoří nový **Poznámkový draft**.</li>
                    </ul>
                    Pokud v předmětu není žádný tag, e-mail zůstane v Inboxu jako "Bez tagu" a můžete ho zařadit ručně.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Automatické štítkování (#hashtags):</strong>
                    Přímo v předmětu e-mailu můžete použít hashtagy (např. <code>#nakup #dulezite</code>). Systém je automaticky převede na štítky v aplikaci. Pokud štítek ještě neexistuje, automaticky ho vytvoří (vše se převádí na malá písmena).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Zabezpečení (Důvěryhodní odesílatelé):</strong>
                    V nastavení doporučujeme vyplnit pole **Povolené e-maily odesílatelů**. Aplikace pak bude ignorovat maily, které přijdou z jiných adres, což zamezí spamu nebo nechtěným importům.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Detailní náhled a ruční import:</strong>
                    Na stránce **Inbox** vidíte přehled všech stažených mailů. Kliknutím na e-mail otevřete **detailní modální okno** s celým obsahem zprávy. U těch mailů, které se neimportovaly automaticky (např. chyběl tag v předmětu), najdete přímo v tomto detailu tlačítka pro ruční zařazení do poznámek, úkolů nebo draftů.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="security">
            <h4 class="text-white mb-4"><i class="bi bi-shield-lock me-2"></i> Zabezpečení a Soukromí</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Zámek aplikace:</strong>
                    V **Nastavení** (sekce Editor a Zabezpečení) můžete zapnout ochranu heslem. Před první aktivací je nutné heslo nastavit a potvrdit (pro vyloučení překlepů). Pokud již máte heslo nastaveno, můžete funkci zámku libovolně vypínat a zapínat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Ruční odhlášení (Lock):</strong>
                    Pokud máte zámek aktivní, v horní liště uvidíte ikonu **odhlášení (šipka ven z boxu)**. Kliknutím na ni se aplikace okamžitě odhlásí a přesměruje vás na zamykací obrazovku. Tato funkce je dostupná i v mobilním menu. Ideální, když odcházíte od počítače.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Skrytí obsahu (Zámek položek):</strong>
                    Jednotlivé snipety a poznámky můžete v editačním modálu označit volbou **"Skrýt obsah"**. Taková položka bude mít v hlavním přehledu svůj obsah skrytý (zobrazí se pouze ikona zámku). Pro zobrazení obsahu je pak nutné aplikaci odemknout vaším globálním heslem.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Bezpečnost hesla:</strong>
                    Hesla jsou v databázi uložena jako bezpečné hashe. Pokud heslo znáte a chcete jej změnit nebo zrušit, můžete tak učinit přímo v **Nastavení** pomocí tlačítka **Resetovat heslo**. Pokud heslo zapomenete, lze jej nouzově resetovat přímo v databázi (tabulka `settings`, klíč `security_enabled` na `0`).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Offline bezpečnost a PHP Info:</strong>
                    DevBase je navržena tak, aby nepotřebovala internet. Žádná vaše data ani hesla neopouštějí váš lokální stroj. V **Nastavení** (Obecné) mají administrátoři přístup k detailnímu výpisu **PHP Konfigurace** (info.php) pro potřeby debugování prostředí.
                </li>
            </ul>
        </div>
        <div class="glass-card no-jump p-4 mt-4 help-section" id="backup">
            <h4 class="text-white mb-4"><i class="bi bi-cloud-arrow-down me-2"></i> Záloha a obnovení dat</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Export do JSON:</strong>
                    V **Nastavení** můžete vygenerovat kompletní zálohu svých dat (snippety, poznámky, úkoly, tagy a nastavení) do jediného souboru JSON. Tento soubor slouží jako bezpečná offline záloha.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Režimy importu:</strong>
                    Při nahrávání zálohy máte dvě možnosti:
                    <ul class="mt-2">
                        <li><strong>Přidat k existujícím:</strong> Ponechá vaše současná data a pouze k nim přidá obsah ze souboru.</li>
                        <li><strong>Přepsat vše:</strong> Kompletně smaže stávající databázi a nahradí ji obsahem ze zálohy (včetně nastavení a hesel).</li>
                    </ul>
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Bezpečnost při importu:</strong>
                    Při režimu "Přepsat vše" dojde i k přepsání vašeho bezpečnostního hesla tím, které bylo v záloze. Ujistěte se, že toto heslo znáte.
                </li>
            </ul>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
