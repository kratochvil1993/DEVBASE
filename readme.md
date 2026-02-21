# DevBase - The OneNote Killer

DevBase je jednoduchá, ale vizuálně líbivá webová aplikace pro ukládání, organizaci a vyhledávání programátorských snipetů. Slouží jako rychlý depozitář vlastních skriptů, HTML elementů či kompletních konfiguračních souborů s plnou podporou zvýraznění syntaxe a štítkování. 

## Vlastnosti a funkce

- **Barevné štítky (Tagy)**: Systém štítků je plně podporován jak pro Snipety, tak i pro Poznámky. Každému štítku můžete přiřadit vlastní barvu (s validací HEX kódu) pro okamžitou vizuální identifikaci.
- **Sekce Poznámky**: Samostatný prostor pro vaše nápady, úkoly nebo SQL s podporou zvýraznění syntaxe, vyhledávání, vlastního řazení a přiřazování štítků.
- **Vlastní řazení (Drag & Drop)**: Pořadí poznámek, ale i samotných štítků v Nastavení, si můžete libovolně měnit jednoduchým přetažením myší (Drag & Drop).
- **Konzistentní správa**: Sjednocené rozhraní pro správu snipetů i poznámek s intuitivním filtrováním podle tagů a rychlým fulltextovým vyhledáváním.
- **Rychlé vyhledávání**: Inteligentní vyhledávání v reálném čase napříč oběma sekcemi (snipety i poznámky), které prohledává názvy i obsah.
- **Detailní náhled**: Snipety lze otevřít ve velkém modálním okně pro pohodlné čtení dlouhých bloků kódu.
- **Tmavý / Světlý režim**: Možnost hladkého přepínání témat přímo v side-baru s automatickým ukládáním volby.
- **Kopírování v jednom kroku**: Tlačítka pro okamžité uložení bloku zdrojového kódu do schránky (clipboard).
- **Integrovaný Markdown**: Snipety lze zapisovat formou Markdownu pro kombinaci více jazyků či tabulek.
- **Flexibilní navigace**: Rychlé přepínání mezi snipety a poznámkami přímo v hlavičce (pokud jsou poznámky povoleny).
- **Správa funkcí**: V administraci můžete vypnout/zapnout celou sekci Poznámky podle aktuální potřeby.
- **Propracované UI**: Elegantní rozhraní postavené na Bootstrap 5, oživené plovoucími barevnými prvky, blur efekty, micro-animacemi a moderní vizí.

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
- **Frontend / UX:** HTML5, Bootstrap 5.3 a čistý JavaScript
- **Zvýraznění kódu:** Prism.js (autoloader s podporou témat)
- **Databáze:** Relační databáze spravovaná pomocí MySQL
- **Nasazení:** Kontejnerizace přes Docker, orchestrace pomocí Docker-Compose
