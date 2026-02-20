# DevBase - The OneNote Killer

DevBase je jednoduchá, ale vizuálně líbivá webová aplikace pro ukládání, organizaci a vyhledávání programátorských snipetů. Slouží jako rychlý depozitář vlastních skriptů, HTML elementů či kompletních konfiguračních souborů s plnou podporou zvýraznění syntaxe a štítkování. 

## Vlastnosti a funkce

- **Snadná organizace**: Snipety si můžete sdružovat pomocí barevných štítků a přiřazovat jim primární programovací jazyk (PHP, HTML, JavaScript atd.).
- **Rychlé vyhledávání**: K dispozici je fulltextové vyhledávání napříč názvy, popisy i samotným zdrojovým kódem a tlačítkové filtrování přes štítky.
- **Tmavý / Světlý režim**: Možnost hladkého přepínání témat přímo v side-baru, aplikace si také poslední volbu pamatuje do lokální paměti prohlížeče.
- **Kopírování v jednom kroku**: Tlačítko pro okamžité uložení bloku zdrojového kódu do schránky (clipboard).
- **Integrovaný Markdown**: Snipety lze zapisovat formou Markdownu pro kombinaci více jazyků či tabulek (skvělé pro tutoriály či složité poznámky).
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
