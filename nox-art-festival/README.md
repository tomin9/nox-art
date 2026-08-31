# NOX:ART Festival – WordPress plugin

Podstránka festivalu NOX:ART: interaktívna mapa miest, kde je možné vidieť
diela, popisky diel, umelci a program festivalu. Všetok obsah sa spravuje
priamo vo WordPress administrácii (custom post types) – žiadna externá
databáza (Supabase a pod.), žiadne API kľúče.

## Inštalácia

1. Skopíruj priečinok `nox-art-festival` do `wp-content/plugins/`.
2. Aktivuj plugin v **Pluginy → Nainštalované pluginy**.
3. V menu **NOX:ART** vytvor obsah:
   - **Miesta** – názov, adresa, popis, fotka, poloha (klikni do mapky pre nastavenie súradníc).
   - **Umelci** – meno, bio, fotka.
   - **Diela** – názov, popis, fotka, priraď umelca a miesto.
   - **Program** – názov bodu programu, dátum, čas, voliteľne miesto.
4. Na stránku/príspevok, kde má byť festivalová podstránka, vlož shortcode:

   ```
   [nox_art]
   ```

## Technické poznámky

- Mapa beží na [Leaflet](https://leafletjs.com/) + dlaždice OpenStreetMap –
  žiadny platený API kľúč.
- Obsah sa na frontend posiela ako predpripravený JSON (`wp_localize_script`)
  pri každom vykreslení stránky – žiadne AJAX volania navyše, žiadne
  prihlasovanie na frontende. Úpravy sa robia výhradne cez wp-admin.
- Súradnice miesta sa dajú zadať ručne, alebo kliknutím do mini-mapy priamo
  v administrácii (pri editácii Miesta).
