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

## Výkon a hosting

Plugin si načítanie rieši sám: Mapbox sa sťahuje až keď sa mapa blíži do
zorného poľa, pozadie hlavičky má WebP verzie (mobil/desktop) a predsúva sa
cez `preload`, fotky dlaždíc sú responzívne `<img>` so `srcset` a lazy
loadingom. Adresy CSS/JS nesú `?ver=<čas poslednej úpravy>`, takže ich možno
kešovať naveky – po zmene súboru sa adresa zmení sama.

Zvyšok je na serveri. V `assets/.htaccess` sú hotové pravidlá pre **Apache
a LiteSpeed** (ročná keš + Brotli/gzip) – stačí, aby mal server zapnuté
moduly `mod_headers`, `mod_expires` a `mod_brotli` (alebo aspoň `mod_deflate`).

Na **nginxe** sa `.htaccess` ignoruje, rovnaké pravidlá patria do konfigurácie:

```nginx
# Kompresia (brotli_* vyžaduje modul ngx_brotli, inak stačí gzip)
brotli on;
brotli_comp_level 5;
brotli_types text/css application/javascript image/svg+xml;

gzip on;
gzip_comp_level 5;
gzip_types text/css application/javascript image/svg+xml;

# Dlhá keš pre statické súbory
location ~* \.(css|js|png|jpe?g|gif|webp|avif|svg|ico|woff2?)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

Čo ešte pomôže, ale je mimo pluginu: HTTP/2 alebo HTTP/3, CDN pred webom
a obmedzenie pluginov, ktoré do hlavičky pridávajú vlastné skripty
(analytika, SEO nástroje).
