# DevBase - The OneNote Killer

DevBase je jednoduchá, ale vizuálně líbivá webová aplikace pro ukládání, organizaci a vyhledávání programátorských snipetů. Slouží jako rychlý depozitář vlastních skriptů, HTML elementů či kompletních konfiguračních souborů s plnou podporou zvýraznění syntaxe a štítkování.

## Vlastnosti a funkce

- **Barevné štítky (Tagy)**: Systém štítků je plně podporován pro Snipety, Poznámky i TODO úkoly. Každému štítku můžete přiřadit vlastní barvu (s validací HEX kódu) pro okamžitou vizuální identifikaci. V nastavení lze také libovolně měnit **pořadí štítků** pomocí Drag & Drop. Barevné badges jsou nyní viditelné i v seznamu při výběru štítků v editačních modálech.
- **Sekce Poznámky**: Samostatný prostor pro vaše nápady, úkoly nebo SQL s podporou zvýraznění syntaxe, vyhledávání, vlastního řazení a přiřazování štítků. Tvorba poznámek v plnohodnotném **WYSIWYG editoru (Quill.js)** pro jednoduší a moderní zápis textu.
- **Sekce TODO (Úkoly)**: Nástroj pro správu priorit s podporou vlastních štítků, filtrování, odškrtnutí (okamžitá archivace), trvalého odstranění a řazení pomocí Drag & Drop. Panel pro přidávání úkolů je moderně integrován v designu vyhledávacího baru. Pro urychlení práce se při přidávání nového úkolu automaticky předvybírá první dostupný štítek. Na mobilu lze formulář pro přidání úkolu skýt a zobrazit kliknutím na ikonu **+**. Kliknutím na text úkolu se otevře **přehledný detailní náhled** (s termínem, časem, poznámkou i štítky), zatímco pro editaci slouží vyhrazené tlačítko. Sekce plně podporuje **Termíny splnění (Deadlines)** včetně **konkrétního času**. Systém vás na termíny vizuálně upozorní přímo u úkolu a v hlavičce aplikace (**systém upozornění - zvoneček**). Pomocí funkce **AI Bojový plán** získáte od AI strategické doporučení. Nově sekce podporuje **Podúkoly**, které si můžete k hlavním úkolům přidávat. U hlavního úkolu pak hned vidíte **počet podúkolů** v barevném odznaku, který se **okamžitě aktualizuje přes AJAX** při přidání nebo smazání podúkolu.
- **Archivy**: Praktický jednoklikový odklízeč pro uložení splněných či neaktuálních poznámek a úkolů. Odškrtnuté úkoly i staré poznámky putují do svých vyhrazených archivů, odkud je můžete později snadno obnovit či trvale smazat. V archivech je nyní k dispozici **inteligentní filtrování podle štítků** (tags) a u poznámek také fulltextové vyhledávání. Archiv TODO úkolů si zachovává **stromovou strukturu**, takže i mezi splněnými úkoly vidíte podúkoly u svých rodičů. Celý archiv lze navíc vyčistit jedním tlačítkem **Vysypat archiv**.
- **Vlastní řazení (Drag & Drop) a Připínání**: Důležité položky si můžete připnout špendlíkem, čímž zůstanou vždy nahoře. Pořadí poznámek, úkolů i snipetů si pak můžete měnit jednoduchým přetažením myší v rámci sekcí (Připnuté / Ostatní). Režim úprav je vizuálně indikován jemným vibrováním prvků pro lepší orientaci. U snipetů je tato funkce dostupná v sekci **Správa snippetů**.
- **Konzistentní správa**: Sjednocené rozhraní pro správu všech sekcí s intuitivním filtrováním podle tagů a rychlým fulltextovým vyhledáváním. Pro maximální přehlednost výběr štítku ve filtrech automaticky vynuluje aktivní vyhledávání. Aplikace si navíc **pamatuje váš poslední aktivní štítek** nejen v Poznámkách, ale i v **přehledu Snippetů**, což usnadňuje návrat k rozdělané práci.
- **Rychlé vyhledávání**: Inteligentní vyhledávání v reálném čase napříč sekcemi, které prohledává názvy i obsah. Vyhledávací pole je vybaveno tlačítkem **X** pro okamžité smazání dotazu.
- **Code Drafts (Kódové koncepty)**: Speciální prostor pro psaní a ukládání libovolného kódu s podporou **více otevřených draftů (tabů)** současně. Každý draft si můžete libovolně pojmenovat. Aplikace si **pamatuje váš poslední aktivní draft**, takže se k němu vždy vrátíte. Drafty jsou chráněny **funkcí Autosave**, která ukládá změny v pravidelných intervalech i při přepínání tabů. Hotový kód můžete jedním kliknutím **poslat do Snippetů nebo do Poznámek**. Editor je postaven na knihovně **CodeMirror 5** a nabízí pokročilé funkce jako chytré doplňování, našeptávání, vyhledávání, **výběr z několika vizuálních témat** (např. Dracula, Nord, Monokai) a **AI formátování kódu** (Beautify / Minify). Sekci lze v nastavení vypnout.
- **Note Drafts (Poznámkové koncepty)**: Rychlé pískoviště pro vaše textové poznámky předtím, než je finálně uložíte. Podobně jako u kódových draftů můžete mít otevřeno více tabů najednou a využívat **automatické ukládání (Autosave)**. Editor využívá **Quill.js (WYSIWYG)** pro pohodlný zápis formátovaného textu. K dispozici jsou pokročilé **AI nástroje** pro strukturování textu, opravu pravopisu, extrakci úkolů a také **AI Prompt Bar** pro zadávání vlastních instrukcí přímo k obsahu poznámky. Draft lze následně jedním kliknutím **přesunout do hlavní sekce Poznámky** nebo jej okamžitě **zkopírovat do schránky** pomocí dedikovaného tlačítka.
- **Detailní náhled**: Snipety i poznámky lze otevřít ve velkém modálním okně pro pohodlné čtení. Nově je v náhledu k dispozici tlačítko pro okamžitý přechod do režimu úprav.
- **Plně AJAXové rozhraní**: Prakticky veškerá interakce v aplikaci probíhá bez obnovování stránky. **Nastavení** (sekce, barvy, AI, Inbox, správa jazyků) se ukládají bleskově a okamžitě při změně přepínače nebo uložení formuláře, což zajišťuje špičkový uživatelský zážitek a bleskovou odezvu. Tato modernizace se týká i přepínání témat či správy štítků.
- **Automatické sledování změn**: Po každém uložení, úpravě nebo vytvoření nového záznamu vás aplikace automaticky přesune na danou položku a zvýrazní ji jemným animovaným efektem pro zachování kontextu. Stejná logika funguje i v **Nastavení**, kde vás aplikace po přidání štítku nebo jazyka plynule vrátí přesně k sekci, se kterou jste pracovali.
- **Interaktivní nápověda**: Stránka s nápovědou obsahuje chytré sticky menu, které se na mobilních zařízeních sbalí do přehledného overlaye a automaticky sleduje, kterou sekci právě čtete.
- **Tmavý / Světlý režim**: Možnost hladkého přepínání témat přímo v side-baru s automatickým ukládáním volby. Tento přepínač lze v Nastavení také zcela skrýt.
- **Inteligentní kopírování**: Kromě tlačítek pro okamžité uložení bloku zdrojového kódu do schránky (clipboard) aplikace podporuje **automatické kopírování výběru**. Jakýkoliv označený text v detailním náhledu se okamžitě po uvolnění myši uloží do schránky s vizuálním potvrzením ("Selection copied!"), což výrazně urychluje práci.
- **Integrovaný Markdown**: Snipety lze zapisovat formou Markdownu. Pokud zvolíte jazyk Markdown, DevBase jej v hlavním přehledu (gridu) automaticky vyrenderuje jako **živý náhled**, takže vidíte formátovaný text přímo v kartě snippetu bez nutnosti jej otevírat.
- **Flexibilní navigace**: Rychlé přepínání mezi sekcemi přímo v hlavičce. Na mobilních zařízeních se celá navigace pro pohodlnější ovládání přesouvá k **dolnímu okraji obrazovky** (Bottom Sticky Header). U sekce TODO se může zobrazovat "badge" s počtem aktivních úkolů.
- **Rychlé statistiky**: Přehledný panel v bočním menu (offcanvas), který okamžitě zobrazuje aktuální počty vašich snippetů, poznámek, aktivních úkolů a také počty rozpracovaných draftů (Code/Note) v elegantních minikartách.
- **Zabezpečení (App Lock & Item Lock)**: Možnost uzamknout celou aplikaci pod heslo. Funkci lze aktivovat a konfigurovat v sekci **Nastavení editoru a zabezpečení**. Následně lze aplikaci kdykoliv odhlásit pomocí ikony **odhlášení (boxy s šipkou)** v hlavičce, což ji okamžitě uzamkne. Kromě toho lze **uzamknout i jednotlivé snipety a poznámky** (přepínač "Skrýt obsah" v editaci). Takto označené položky budou mít v přehledu skrytý obsah a k jeho zobrazení je nutné aplikaci odemknout globálním heslem. Heslo lze kdykoliv resetovat v nastavení. Systém využívá bezpečné hashování hesla.
- **E-mailový Inbox (Import přes e-mail)**: Unikátní funkce, která vám umožní posílat si poznámky a úkoly do aplikace přímo z vašeho e-mailu. Stačí si v nastavení nakonfigurovat **IMAP přístup** ke své schránce. Aplikace dokáže automaticky rozlišit typ obsahu podle tagů v předmětu (`@note`, `@todo`, `@draft`) a automaticky k nim přiřadit štítky pomocí hashtagů (např. `#prace #dulezite`). Pokud e-mail tag neobsahuje, zůstane v **Inboxu**, kde jej můžete otevřít v **detailním modálním okně** a zařadit ručně. Aplikace navíc provádí **automatickou synchronizaci každých 5 minut** (včetně kontroly duplicitních zpráv a ignorování spamových odesílatelů) a na nové přírůstky vás upozorní **modrým badge počítadlem** přímo u položky Inbox v hlavním menu i systémovými notifikacemi v hlavičce. Synchronizaci lze také spustit ručně zeleným tlačítkem **Načíst nové**. Celou historii importu lze navíc jedním kliknutím vyčistit tlačítkem **Vymazat historii**. Počet novinek zmizí až po návštěvě sekce Inbox. Systém podporuje seznam povolených odesílatelů pro maximální bezpečnost.
- **Záloha a Přenos dat (Export/Import)**: Kompletní správa vašich dat. Celou databázi (snippety, poznámky, úkoly, tagy i nastavení) lze jedním kliknutím exportovat do JSON souboru a následně jej importovat zpět – buď formou přidání k existujícím datům, nebo kompletním přepsáním.
- **Správa jazyků**: DevBase umožňuje definovat vlastní programovací jazyky a přiřadit jim příslušné CSS třídy pro Prism.js. Seznam jazyků lze spravovat v Nastavení, včetně přidávání nových a mazání nepotřebných.
- **Propracované UI**: Elegantní rozhraní postavené na Bootstrap 5, oživené plovoucími barevnými prvky, blur efekty, micro-animacemi a moderní vizí glassmorphismu. Rozhraní je **plně responzivní** a optimalizované pro mobilní zařízení. V nastavení lze přizpůsobit **velikost písma** v editorech a detailech a také zvolit **vizuální téma editoru**. Pro bleskové přepnutí písma slouží **ikona Gear** v hlavičce. Pro maximální plynulost aplikace využívá **optimalizaci výkonu**: pokud s oknem nepracujete, automaticky se pozastaví náročné animace. Díky `Intersection Observer` se vizuální efekty aplikují pouze na prvky, které právě vidíte na obrazovce. Administrátoři mají k dispozici také stránku **PHP Info** pro rychlou kontrolu konfigurace serveru. Významné akce jsou doprovázeny **fialovým flash efektem** pro jasnou vizuální odezvu.
- **Plně lokální běh**: Všechny knihovny, ikony a fonty jsou uloženy lokálně v projektu. Aplikace nevyžaduje přístup k internetu pro své fungování (ideální pro bezpečné interní prostředí).
- **PWA Podpora**: Aplikace je plnohodnotná **Progressive Web App**. Můžete si ji tak "nainstalovat" přímo na plochu svého počítače nebo telefonu (Safari / Chrome). Díky Service Workeru se klíčové knihovny načítají bleskově a aplikace je připravena k okamžitému použití i při slabém připojení.
- **Klávesové zkratky**: Podpora pro rychlé vyhledávání (**Alt+F**) a přidávání záznamů (**Alt+N**). V code editoru jsou pak dostupné pokročiče zkratky pro ukládání (**Ctrl+S**), našeptávání a manipulaci s kódem.
- **Integrace AI (Gemini, OpenAI & Vlastní)**: DevBase využívá sílu umělé inteligence od Google, OpenAI i vlastních lokálních modelů (např. Ollama) pro usnadnění vaší práce. Funkce jsou dostupné po zadání API klíče nebo endpointu v Nastavení:
  - **Snippets & Code**: AI dokáže analyzovat, lidsky vysvětlit kód, refaktorovat jej, nebo jej **zformátovat** (Beautify / Minify) pro lepší čitelnost či minimální velikost.
  - **Poznámky**: Automatické generování titulku, stručného souhrnu v odrážkách, kontrola pravopisu a **inteligentní extrakce úkolů** (AI automaticky vytvoří TODO seznam z textu poznámky). Navržené opravy lze jedním kliknutím **aplikovat přímo do poznámky**. V sekcích Drafts lze navíc využít **Vlastní AI prompt** pro libovolné úpravy textu i nástroje pro **inteligentní formátování kódu** (Beautify / Minify), které zformátují kusy kódu i uvnitř poznámek.
  - **TODO**: Funkce **AI Bojový plán**, která na základě vašich aktivních úkolů a jejich termínů navrhne nejlepší strategii pro daný den.
  - **Code Editor**: V sekci Code Drafts jsou dostupné pokročilé AI nástroje jako **Vysvětlit kód**, **Refaktorovat**, **Debugger** a nově i **Beautify/Minify**. Integrovaný **Color Picker** pak umožňuje vybírat a měnit barvy přímo v kódu.
  - **Konfigurace**: V nastavení si můžete zvolit **preferovaného poskytovatele (Gemini / OpenAI / Vlastní)** a konkrétní model (např. GPT-4o Mini, Gemini 2.0 Flash nebo vlastní model přes Ollama) a otestovat platnost spojení. Celou AI integraci lze v nastavení globálně vypnout jedním přepínačem. Všechny AI prvky jsou v UI barevně odlišeny (fialová s premium glow efektem).

## Klávesové zkratky

Pro maximální efektivitu můžete používat tyto systémové zkratky:

- **Option + 1, 2, 3, 4, 5, 6**: Rychlá navigace mezi hlavními sekcemi (Snippety, Code Drafts, Note Drafts, Poznámky, TODO, Inbox).
- **Option + F** (nebo **Alt + F**): Rychlé zaměření vyhledávacího pole v aktuální sekci (Snippety / Poznámky).
- **Option + N** (nebo **Alt + N**): Okamžité otevření okna pro přidání nového snippetu/poznámky nebo vytvoření nového draftu (v sekcích Drafts).
- **Option + L** (v sekci Code Drafts): Rychlé zaměření kurzoru do editoru kódu.
- **Option + W** (v sekcích Drafts): Zavřít aktuální draft.
- **Option + ↑ / ↓** (v sekcích Drafts): Přepínání mezi taby draftů.
- **Ctrl + S** (v editorech a modálech): Rychlé uložení rozpracovaného obsahu nebo odeslání formuláře.

## Instalace a Spuštění

Konfigurační soubor `docker-compose.yml` nastaví celou aplikační strukturu včetně dedikované MySQL databáze a administračního nástroje phpMyAdmin.

1. **Klonování nebo zkopírování repozitáře:**
   Nejprve se ujistěte, že máte složku k dispozici a otevřenou ve svém terminálu.

2. **Spuštění přes Docker Compose:**
   Zadejte následující příkaz. Pokud projekt spouštíte po prvotní čisté instalaci, rovnou jej pro vás sestaví:

   ```bash
   docker-compose up --build
   ```

3. **Konfigurace aplikace:**
   Soubor `includes/config.php` je ignorován Gitem. Musíte jej vytvořit ze šablony:
   ```bash
   cp includes/config.example.php includes/config.php
   ```
   *Poznámka: Pokud používáte výchozí Docker prostředí, hodnoty v šabloně by měly odpovídat (případně změňte `DB_HOST` na `mysql_db`).*

4. **První otevření aplikace:**
   Jakmile se proces úspěšně dokončí, automatický migrační script sestaví a zainicializuje tabulky. Aplikace a databázové nástroje budou nyní naservírovány na portech z vašeho lokálního stroje. Otevřete Váš prohlížeč na těchto adresách:
   - 🌍 **Webové rozhraní aplikace:** [http://localhost:9060](http://localhost:9060)
   - 🛠️ **PhpMyAdmin (Správa databáze):** [http://localhost:9061](http://localhost:9061)

## Připojení k databázi (Externě)

Pokud se chcete k databázi přes Docker připojit přes nástroje jakým je např. Datagrip či DBeaver (nebo pro integraci se svými skripty třetích stran), lokální nastavení je následující:

- **Host (Server):** `mysql_db` (pokud jste uvnitř Docker sítě), případně `localhost` a sdílený port.
- **Uživatel:** `root`
- **Heslo:** `root`

## Technologie pod kapotou

- **Backend:** Nativní PHP, bez těžkých frameworků.
- **Frontend / UX:** HTML5, Bootstrap 5.3 a čistý JavaScript (vše linkováno lokálně z `/assets`).
- **Zpracování úkolů:** Interaktivní odškrtávání a řazení bez nutnosti obnovování stránky (SortableJS)
- **Formátování & Editor:** WYSIWYG editor Quill.js (pro poznámky a Note Drafts) a profesionální kódový editor **CodeMirror 5** (pro Code Drafts)
- **Zvýraznění kódu:** Prism.js (autoloader s podporou témat) a integrované zvýraznění v reálném čase v editoru
- **Databáze**: Relační databáze spravovaná pomocí MySQL
- **AI Integrace**: Google Gemini AI & OpenAI (volitelně pro pokročilé funkce)
- **Nasazení:** Kontejnerizace přes Docker, orchestrace pomocí Docker-Compose
