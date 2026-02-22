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

    <!-- Markdown Overview -->
    <div class="col-lg-12 mb-4">
        <div class="glass-card p-4">
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
                <li class="mb-3">
                    <strong class="text-white d-block">Hledání v poznámkách:</strong>
                    Stejně jako u snipetů, i v sekci **Poznámky** najdete vyhledávací pole, které filtruje karty v reálném čase podle názvu i obsahu.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Detailní náhled:</strong>
                    Kliknutím na kartu snipetu (mimo tlačítka) otevřete **detailní náhled** v modálním okně, který je ideální pro čtení dlouhých kódů.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Statistiky v menu:</strong>
                    V bočním panelu najdete bleskový přehled počtu snippetů, poznámek a **aktivních** úkolů. Grafika karet je nyní statická pro nerušené čtení.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Persistence vzhledu:</strong>
                    Vaše volba mezi tmavým a světlým režimem je bezpečně uložena v databázi. Při příštím přihlášení nebo na jiném zařízení se vám aplikace zobrazí přesně tak, jak jste ji naposledy zanechali.
                </li>
            </ul>
        </div>

        <div class="glass-card p-4">
            <h4 class="text-white mb-4"><i class="bi bi-tags me-2"></i> Strategie tagování</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Barvy a validace štítků:</strong>
                    V **Nastavení** můžete každému štítku přiřadit barvu. Barva je automaticky validována a musí být ve formátu HEX (např. `#ff0000`). Štítky jsou pak barevně odlišeny v přehledu i při filtrování.
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

        <div class="glass-card p-4 mt-4">
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
                    <strong class="text-white d-block">Archivace:</strong>
                    Méně aktuální nebo splněné poznámky můžete 1 kliknutím přesunout do <strong>Archivu poznámek</strong> (oranžová ikonka krabice) a udržet tak hlavní výpis přehledný. Z archivu je lze kdykoliv obnovit nebo trvale smazat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Vlastní řazení:</strong>
                    V sekci Poznámky klikni na **Upravit pořadí**. Karty se jemně rozvibrují a ty je můžeš myší přetahovat. Pořadí se ukládá automaticky.
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

        <div class="glass-card p-4 mt-4">
            <h4 class="text-white mb-4"><i class="bi bi-check2-square me-2"></i> Správa úkolů (TODO)</h4>
            <ul class="text-white-50 small list-unstyled">
                <li class="mb-3">
                    <strong class="text-white d-block">Přidávání úkolů:</strong>
                    V horní části najdete panel, který je vizuálně sjednocen s vyhledávacím barem. Jednoduše napište co potřebujete udělat a stiskněte Enter nebo tlačítko pro přidání.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Štítkování úkolů:</strong>
                    Úkolům můžete přiřazovat barevné štítky. Buď při rychlém přidání (jeden štítek), nebo přes ikonu tužky (neomezeně štítků). Nad seznamem úkolů pak najdete filtry, které vám umožní zobrazit jen úkoly s konkrétním tématem.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Odškrtnutí a Archivace:</strong>
                    Kliknutím na prázdný čtvereček u úkolu se označí jako splněný a automaticky přesune do sekce <strong>Archiv TODO</strong>. Nepřekáží tak mezi aktivními úkoly, ale máte k němu stále přístup, můžete ho obnovit nebo trvale smazat.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Trvalé smazání:</strong>
                    Aktivní nebo dříve archivovaný úkol lze okamžitě a nenávratně odstranit kliknutím na ikonu koše.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Řazení podle priorit (Drag & Drop):</strong>
                    Pořadí úkolů můžete libovolně měnit. Stačí odemknout řazení přes tlačítko **Upravit pořadí**. Karty se jemně rozvibrují a vy je můžete myší přetahovat. Pořadí se ukládá automaticky.
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Badge s počtem úkolů:</strong>
                    V hlavní navigaci se u odkazu na TODO může zobrazovat počet aktivních (nearchivovaných) úkolů. Tuto funkci lze vypnout v **Nastavení**.
                </li>
            </ul>
        </div>

        <div class="glass-card p-4 mt-4">
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
                    <strong class="text-white d-block">Bezpečnost hesla:</strong>
                    Hesla jsou v databázi uložena jako bezpečné hashe. Pokud heslo zapomenete, lze jej resetovat přímo v databázi (tabulka `settings`, klíč `security_enabled` na `0`).
                </li>
                <li class="mb-3">
                    <strong class="text-white d-block">Offline bezpečnost:</strong>
                    DevBase je navržena tak, aby nepotřebovala internet. Žádná vaše data ani hesla neopouštějí váš lokální stroj/Docker kontejner.
                </li>
            </ul>
        </div>
        <div class="glass-card p-4 mt-4">
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
