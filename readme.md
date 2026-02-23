# DevBase - The OneNote Killer

DevBase je jednoduchá, ale vizuálně líbivá webová aplikace pro ukládání, organizaci a vyhledávání programátorských snipetů. Slouží jako rychlý depozitář vlastních skriptů, HTML elementů či kompletních konfiguračních souborů s plnou podporou zvýraznění syntaxe a štítkování.

## Vlastnosti a funkce

- **Barevné štítky (Tagy)**: Systém štítků je plně podporován pro Snipety, Poznámky i TODO úkoly. Každému štítku můžete přiřadit vlastní barvu (s validací HEX kódu) pro okamžitou vizuální identifikaci. V nastavení lze také libovolně měnit **pořadí štítků** pomocí Drag & Drop. Barevné badges jsou nyní viditelné i v seznamu při výběru štítků v editačních modálech.
- **Sekce Poznámky**: Samostatný prostor pro vaše nápady, úkoly nebo SQL s podporou zvýraznění syntaxe, vyhledávání, vlastního řazení a přiřazování štítků. Tvorba poznámek v plnohodnotném **WYSIWYG editoru (Quill.js)** pro jednoduší a moderní zápis textu.
- **Sekce TODO (Úkoly)**: Nástroj pro správu priorit s podporou vlastních štítků, filtrování, odškrtnutí (okamžitá archivace), trvalého odstranění a řazení pomocí Drag & Drop. Panel pro přidávání úkolů je moderně integrován v designu vyhledávacího baru. Nově s podporou pro **Termíny splnění (Deadlines)**, které vás vizuálně upozorní na blížící se nebo uplynulý termín.
- **Archivy**: Praktický jednoklikový odklízeč pro uložení splněných či neaktuálních poznámek a úkolů. Odškrtnuté úkoly i staré poznámky putují do svých vyhrazených archivů, odkud je můžete později snadno obnovit či trvale smazat. V archivech je nyní k dispozici **inteligentní filtrování podle štítků** (tags) a u poznámek také fulltextové vyhledávání. Archiv TODO úkolů lze navíc vyčistit jedním tlačítkem **Vysypat archiv**.
- **Vlastní řazení (Drag & Drop) a Připínání**: Důležité položky si můžete připnout špendlíkem, čímž zůstanou vždy nahoře. Pořadí poznámek, úkolů i snipetů si pak můžete měnit jednoduchým přetažením myší v rámci sekcí (Připnuté / Ostatní). Režim úprav je vizuálně indikován jemným vibrováním prvků pro lepší orientaci. U snipetů je tato funkce dostupná v sekci **Správa snippetů**.
- **Konzistentní správa**: Sjednocené rozhraní pro správu všech sekcí s intuitivním filtrováním podle tagů a rychlým fulltextovým vyhledáváním.
- **Rychlé vyhledávání**: Inteligentní vyhledávání v reálném čase napříč sekcemi, které prohledává názvy i obsah.
- **Code Scratchpad (Pískoviště)**: Speciální prostor pro psaní a ukládání libovolného kódu nebo poznámek. Editor je postaven na profesionální knihovně **CodeMirror 5** a nabízí pokročilé funkce jako chytré doplňování závorek, našeptávání (autocomplete), skládání kódu (folding) a vyhledávání přímo v textu. Vše se ukládá do databáze a je k dispozici při každém přístupu. Sekci lze v nastavení vypnout.
- **Detailní náhled**: Snipety i poznámky lze otevřít ve velkém modálním okně pro pohodlné čtení. Nově je v náhledu k dispozici tlačítko pro okamžitý přechod do režimu úprav.
- **Tmavý / Světlý režim**: Možnost hladkého přepínání témat přímo v side-baru s automatickým ukládáním volby. Tento přepínač lze v Nastavení také zcela skrýt.
- **Kopírování v jednom kroku**: Tlačítka pro okamžité uložení bloku zdrojového kódu do schránky (clipboard).
- **Integrovaný Markdown**: Snipety lze zapisovat formou Markdownu pro kombinaci více jazyků či tabulek.
- **Flexibilní navigace**: Rychlé přepínání mezi snipety, poznámkami a úkoly přímo v hlavičce (dle aktuálního nastavení). U sekce TODO se navíc může zobrazovat "badge" s počtem aktivních úkolů.
- **Rychlé statistiky**: Přehledný panel v bočním menu (offcanvas), který okamžitě zobrazuje aktuální počty vašich snippetů, poznámek a **aktivních** úkolů v elegantních minikartách.
- **Zabezpečení (App Lock)**: Možnost uzamknout celou aplikaci pod heslo. Funkci lze aktivovat v Nastavení po zadání a potvrzení hesla. Následně lze aplikaci kdykoliv "zacvaknout" pomocí ikony zámku v hlavičce. Systém využívá bezpečné hashování hesla.
- **Záloha a Přenos dat (Export/Import)**: Kompletní správa vašich dat. Celou databázi (snippety, poznámky, úkoly, tagy i nastavení) lze jedním kliknutím exportovat do JSON souboru a následně jej importovat zpět – buď formou přidání k existujícím datům, nebo kompletním přepsáním.
- **Propracované UI**: Elegantní rozhraní postavené na Bootstrap 5, oživené plovoucími barevnými prvky, blur efekty, micro-animacemi a moderní vizí glassmorphismu.
- **Plně lokální běh**: Všechny knihovny, ikony a fonty jsou uloženy lokálně v projektu. Aplikace nevyžaduje přístup k internetu pro své fungování (ideální pro bezpečné interní prostředí).

## Instalace a Spuštění

Konfigurační soubor `docker-compose.yml` nastaví celou aplikační strukturu včetně dedikované MariaDB databáze a administračního nástroje phpMyAdmin.

1. **Klonování nebo zkopírování repozitáře:**
   Nejprve se ujistěte, že máte složku k dispozici a otevřenou ve svém terminálu.

2. **Spuštění přes Docker Compose:**
   Zadejte následující příkaz. Pokud projekt spouštíte po prvotní čisté instalaci, rovnou jej pro vás sestaví:

   ```bash
   docker-compose up --build
   ```

3. **První otevření aplikace:**
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
- **Formátování & Editor:** WYSIWYG editor Quill.js (pro poznámky) a profesionální kódový editor **CodeMirror 5** (pro Scratchpad)
- **Zvýraznění kódu:** Prism.js (autoloader s podporou témat) a integrované zvýraznění v reálném čase v editoru
- **Databáze:** Relační databáze spravovaná pomocí MySQL
- **Nasazení:** Kontejnerizace přes Docker, orchestrace pomocí Docker-Compose
