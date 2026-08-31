# GitHub Plugin Sync

WordPress plugin, ktorý drží iné pluginy zosynchronizované s ich GitHub
repozitárom. Zadáš zoznam **verejných** repozitárov (a voliteľne cestu
k pluginu v nich, ak plugin nie je priamo v koreni repa) – pri každom pushi
na sledovanú vetvu GitHub okamžite pošle webhook a plugin sa automaticky
stiahne a nainštaluje/aktualizuje priamo na živom webe.

## Inštalácia

1. Skopíruj priečinok `github-plugin-sync` do `wp-content/plugins/`.
2. Aktivuj plugin – hneď pri aktivácii sa vygeneruje náhodný **webhook secret**.
3. Choď do **Nastavenia → GitHub Plugin Sync**.
4. Pridaj repozitár:
   - **Repozitár** – `owner/repo`, napr. `tomin9/nox-art`.
   - **Vetva** – ktorú vetvu sledovať (napr. `main`).
   - **Cesta k pluginu v repe** – ak plugin nie je v koreni repozitára, ale
     v podpriečinku (napr. `nox-art-festival`), vlož ju sem. Ak je repo =
     plugin, nechaj prázdne.
   - **Cieľový priečinok (slug)** – meno priečinka vo `wp-content/plugins/`,
     kam sa má plugin nainštalovať.
   - **Auto-aktivovať** – po prvej inštalácii plugin rovno aktivuje.
5. Ulož nastavenia.
6. V GitHub repozitári choď do **Settings → Webhooks → Add webhook**:
   - **Payload URL** – skopíruj z poľa „Webhook URL“ na stránke nastavení.
   - **Content type** – `application/json`.
   - **Secret** – skopíruj z poľa „Webhook secret“.
   - **Events** – „Just the push event“.
7. Ulož webhook v GitHube. GitHub hneď pošle testovací `ping` – ak je secret
   správne, webhook sa v GitHube zobrazí ako úspešný (zelená fajočka).

Odteraz každý push na sledovanú vetvu daného repozitára automaticky
nainštaluje najnovšiu verziu ako WordPress plugin. Tlačidlo
„Synchronizovať teraz“ pri repozitári spustí to isté ručne (užitočné na
prvotné otestovanie).

## Bezpečnosť

- Webhook endpoint (`/wp-json/ghps/v1/webhook`) prijme a spracuje iba
  požiadavky, ktoré nesú platný HMAC-SHA256 podpis vypočítaný z webhook
  secretu – bez znalosti secretu (uloženého len v databáze WordPressu a v
  nastaveniach GitHub webhooku) sa nedá vynútiť inštalácia ničoho.
- Funguje len s **verejnými** repozitármi – žiadny GitHub token sa nikde
  neukladá.
- Pri synchronizácii sa celý obsah cieľového priečinka pluginu vymaže a
  nahradí obsahom z GitHubu – necháva ho teda ako presnú kópiu danej vetvy.
  Prípadné úpravy urobené priamo na serveri (mimo GitHubu) sa touto cestou
  stratia.
