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
                    <a href="#shortcuts" class="submenu-link" data-section="shortcuts">Zkratky</a>
                    <a href="#tagging" class="submenu-link" data-section="tagging">Tagy</a>
                    <a href="#editor" class="submenu-link" data-section="editor">Editor</a>
                    <a href="#notes" class="submenu-link" data-section="notes">Poznámky</a>
                    <a href="#todo" class="submenu-link" data-section="todo">TODO</a>
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
                <p class="text-white small mb-3">Pro nejlepší výsledek při ukládání mixu HTML a JS doporučujeme použít "Markdown" formát s kódovými bloky:</p>
                
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
                    Vyber tag (např. `React`) a pak začni psát. Filtr se aplikuje na už vyfiltrované výsledky.
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
                    V bočním panelu najdete bleskový přehled počtu snippetů, poznámek a **aktivních** úkolů. Grafika karet je nyní statická pro nerušené čtení.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Persistence vzhledu:</strong>
                    Vaše volba mezi tmavým a světlým režimem je bezpečně uložena v databázi. Při příštím přihlášení nebo na jiném zařízení se vám aplikace zobrazí přesně tak, jak jste ji naposledy zanechali.
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
                    <span class="badge bg-primary me-2">Option + 2</span> nebo <span class="badge bg-primary me-2">Alt + 2</span> <strong class="text-white ms-2">Code:</strong> Otevře kódový editor (Scratchpad).
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 3</span> nebo <span class="badge bg-primary me-2">Alt + 3</span> <strong class="text-white ms-2">Notes:</strong> Přejde na poznámky.
                </li>
                <li class="mb-2">
                    <span class="badge bg-primary me-2">Option + 4</span> nebo <span class="badge bg-primary me-2">Alt + 4</span> <strong class="text-white ms-2">TODO:</strong> Otevře správu úkolů.
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
                    Systém tagů je oddělený pro tři hlavní kategorie. V Nastavení jasně vidíte a spravujete, které tagy patří k programátorským snipetům, které k poznámkám a které k vašim úkolům (TODO).
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

        <div class="glass-card no-jump p-4 mt-4 help-section" id="editor">
            <h4 class="text-white mb-4"><i class="bi bi-braces me-2"></i> Práce s Editorem (Code Scratchpad)</h4>
            <p class="text-white small mb-3">Tato sekce slouží jako vaše "pískoviště" pro libovolný kód, konfigurační řetězce nebo technické poznámky, které chcete mít neustále po ruce.</p>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Více draftů (Taby) a Persistence:</strong>
                    Nově můžete mít v sekci Scratchpad otevřeno **mnoho různých draftů** současně. Mezi nimi přepínáte pomocí tabů nad editorem. Každý draft si můžete **přejmenovat** jednoduše kliknutím na jeho název. Aplikace si automaticky pamatuje, který tab jste měli naposledy otevřený, takže se k němu vždy vrátíte.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Poslat do Snippetů / Poznámek:</strong>
                    Pokud máte hotový kód nebo text, který chcete trvale uložit, využijte tlačítko <i class="bi bi-send me-1"></i> **Poslat do**. To vám umožní vybraný draft okamžitě převést na plnohodnotný **Snippet** (včetně jazyka a popisu) nebo do **Poznámek** (obsah se vloží jako kódový blok). Po úspěšném přesunu se původní draft smaže, abyste měli v editoru pořádek.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Profesionální funkce a Správa jazyků:</strong>
                    Editor podporuje automatické doplňování závorek a uvozovek, zavírání HTML tagů a zvýraznění párových značek. V **Nastavení** můžete spravovat seznam dostupných programovacích jazyků, přidávat nové nebo upravovat jejich třídy pro Prism.js. Editor obsahuje také funkci **Folding** (skládání kódu) pomocí šipek u čísel řádků. Celý obsah editoru můžete také jedním kliknutím uložit do schránky pomocí tlačítka **copy**.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Klávesové zkratky:</strong>
                    Rychlost je základ, proto můžete v editoru používat tyto zkratky:
                    <ul class="mt-2 text-white">
                        <li><span class="badge bg-primary me-2">Option + L</span> nebo <span class="badge bg-primary me-2">Alt + L</span> <strong>Skok do editoru</strong> (rychle zaměří kurzor do editoru)</li>
                        <li><span class="badge bg-primary me-2">Ctrl + S</span> <strong>Uložit</strong> (uloží rozpracovaný kód do databáze)</li>
                        <li><span class="badge bg-primary me-2">Ctrl + Space</span> <strong>Našeptávání</strong> (autocomplete klíčových slov)</li>
                        <li><span class="badge bg-primary me-2">Ctrl + F</span> <strong>Vyhledávání</strong> přímo uvnitř kódu</li>
                        <li><span class="badge bg-primary me-2">Alt + F</span> <strong>Nahrazení</strong> textu</li>
                        <li><span class="badge bg-primary me-2">Ctrl + Q</span> <strong>Sbalit blok</strong> (fold) na aktuálním řádku</li>
                        <li><span class="badge bg-primary me-2">Alt + G</span> <strong>Skočit na řádek...</strong></li>
                    </ul>
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Persistence:</strong>
                    Obsah editoru je uložen v databázi. I když prohlížeč zavřete nebo restartujete počítač, váš kód tam bude na vás čekat přesně tak, jak jste ho naposledy uložili.
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
                    U každého úkolu můžete nastavit konkrétní datum splnění. Pokud se termín blíží (dnes nebo zítra), u úkolu se objeví žlutý odznak **Blíží se**. Pokud termín již vypršel, objeví se červený odznak **Po termínu**. Termíny lze zadat při přidávání úkolu nebo dodatečně přes ikonu tužky.
                </li>
            </ul>
        </div>

        <div class="glass-card no-jump p-4 mt-4 help-section" id="security">
            <h4 class="text-white mb-4"><i class="bi bi-shield-lock me-2"></i> Zabezpečení a Soukromí</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Zámek aplikace:</strong>
                    V **Nastavení** můžete zapnout ochranu heslem. Před první aktivací je nutné heslo nastavit a potvrdit (pro vyloučení překlepů). Pokud již máte heslo nastaveno, můžete funkci zámku libovolně vypínat a zapínat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Ruční uzamčení:</strong>
                    Pokud máte zámek aktivní, v horní liště vedle Dark Mode přepínače uvidíte ikonu **zámku**. Kliknutím na ni se aplikace okamžitě uzamkne a přesměruje vás na zamykací obrazovku. Ideální, když odcházíte od počítače.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Skrytí obsahu (Zámek položek):</strong>
                    Jednotlivé snipety a poznámky můžete v editačním modálu označit volbou **"Skrýt obsah"**. Taková položka bude mít v hlavním přehledu svůj obsah skrytý (zobrazí se pouze ikona zámku). Pro zobrazení obsahu je pak nutné aplikaci odemknout vaším globálním heslem.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Bezpečnost hesla:</strong>
                    Hesla jsou v databázi uložena jako bezpečné hashe. Pokud heslo zapomenete, lze jej resetovat přímo v databázi (tabulka `settings`, klíč `security_enabled` na `0`).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Offline bezpečnost:</strong>
                    DevBase je navržena tak, aby nepotřebovala internet. Žádná vaše data ani hesla neopouštějí váš lokální stroj/Docker kontejner.
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
