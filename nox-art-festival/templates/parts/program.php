<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Program a diela v jednom zozname. Program festivalu obsahuje aj diela aj
 * sprievodný program, takže ich nemá zmysel deliť na dve sekcie – rozlišujú
 * sa kategóriami, podľa ktorých sa dá filtrovať. Samotný zoznam aj jeho
 * číslovanie stavia nox_art_site_items(), lebo tie isté čísla potrebuje aj
 * mapa (dostáva dáta ešte pred vykreslením šablóny).
 */
$polozky = nox_art_site_items();
$filtre = nox_art_filter_tree(['nox_program', 'nox_dielo', 'nox_miesto', 'nox_podnik']);

// Počítadlo nad mapou začína pri prvej skupine – rovnako, ako je nastavený
// filter. Ďalej ho už prepisuje skript podľa toho, čo je práve vo výbere.
$prvaSkupina = $filtre ? $filtre[0]['term']->slug : '';
$prvyPocet = 0;
foreach ($polozky as $polozka) {
    if (!$prvaSkupina || in_array($prvaSkupina, $polozka['kategorie'], true)) $prvyPocet++;
}
$tvary = nox_art_site_count_labels();
$tvar = $tvary[$prvaSkupina] ?? $tvary['_default'];
$prvySlovo = $prvyPocet === 1 ? $tvar[0] : ($prvyPocet >= 2 && $prvyPocet <= 4 ? $tvar[1] : $tvar[2]);
?>
<section class="section program" id="program" aria-labelledby="program-title">
  <div class="section-label section-label-light reveal"><span>01</span> Program a diela</div>
  <div class="program-head reveal">
    <h2 id="program-title">Dve noci.<br>Jedna svetelná trasa.</h2>
    <p><?php echo (int) $dielaCount; ?> diel, sprievodný program a podniky s festivalovým menu — prepni si, čo ťa práve zaujíma.</p>
  </div>

  <?php if ($filtre): ?>
  <?php /* Žiadne "Všetky" – skupiny sú rovnocenné, prvá je zapnutá pri
           načítaní stránky. Podkategórie sa odkryjú až po zvolení skupiny,
           ktorá ich má. */ ?>
  <div class="filter-bar filter-bar-light reveal" data-filter-group="program" role="group" aria-label="Filtrovanie programu">
    <?php $autoriVlozene = false; foreach ($filtre as $i => $uzol): $term = $uzol['term']; ?>
    <button class="filter-chip<?php echo $i === 0 ? ' is-active' : ''; ?>" type="button" data-filter="<?php echo esc_attr($term->slug); ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>"><i class="chip-pin" aria-hidden="true" style="--pin:<?php echo esc_attr(nox_art_term_color($term)); ?>"></i><?php echo esc_html($term->name); ?></button>
    <?php if ($term->slug === 'diela' || (!$autoriVlozene && $i === 0 && !in_array('diela', array_map(function ($u) { return $u['term']->slug; }, $filtre), true))): $autoriVlozene = true; ?>
    <button class="filter-chip" type="button" data-view="autori" aria-pressed="false"><svg class="chip-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>Autori</button>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php /* Posledné tlačidlo neprepína kategóriu, ale celý pohľad – namiesto
             dlaždíc ukáže ten istý obsah zoradený podľa času. */ ?>
    <button class="filter-chip" type="button" data-view="harmonogram" aria-pressed="false"><svg class="chip-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg><?php echo esc_html('Časový harmonogram'); ?></button>
  </div>
  <?php foreach ($filtre as $i => $uzol): if (!$uzol['children']) continue; ?>
  <div class="filter-bar filter-bar-light filter-bar-sub" data-filter-sub="<?php echo esc_attr($uzol['term']->slug); ?>" data-filter-parent="program" role="group" aria-label="Spresnenie: <?php echo esc_attr($uzol['term']->name); ?>"<?php echo $i === 0 ? '' : ' hidden'; ?>>
    <?php foreach ($uzol['children'] as $child): ?>
    <button class="filter-chip" type="button" data-filter="<?php echo esc_attr($child->slug); ?>" aria-pressed="false"><?php echo esc_html($child->name); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <div class="program-layout">
  <div class="program-layout-list">
  <div data-view-panel="items">
  <div class="gallery-grid" data-filter-target="program">
    <?php if (!$polozky): ?>
    <p class="empty" style="color:var(--paper);opacity:.7">Program zatiaľ nie je zverejnený — pridaj ho v administrácii (NOX:ART &rsaquo; Program, Diela).</p>
    <?php endif; ?>
    <?php foreach ($polozky as $i => $p): $visual = $visualClasses[$i % count($visualClasses)]; $pinColorTile = nox_art_item_color($p['kategorie']); ?>
    <article class="gallery-tile reveal" id="<?php echo esc_attr($p['id']); ?>"<?php echo $p['work'] ? ' data-work="' . esc_attr($p['work']) . '"' : ''; ?> data-miesto="<?php echo esc_attr($p['miestoId'] ?: ''); ?>"<?php echo $p['terminy'] ? ' data-cas="1"' : ''; ?> data-cislo="<?php echo (int) $p['cislo']; ?>"<?php echo $pinColorTile ? ' data-pin="' . esc_attr($pinColorTile) . '"' : ''; ?> data-cat="<?php echo esc_attr(implode(' ', $p['kategorie'])); ?>"<?php echo $p['slug'] ? ' data-slug="' . esc_attr($p['slug']) . '"' : ''; ?>>
      <?php if ($p['foto']): ?>
      <div class="tile-media" aria-hidden="true">
        <?php if ($p['fotoId']): ?>
        <?php echo wp_get_attachment_image($p['fotoId'], 'large', false, [
            'class' => 'tile-media-img',
            'alt' => '',
            'loading' => 'lazy',
            'decoding' => 'async',
            'sizes' => '(max-width: 760px) 92vw, (max-width: 1180px) 44vw, 20vw',
        ]); ?>
        <?php else: ?>
        <img class="tile-media-img" src="<?php echo esc_url($p['foto']); ?>" alt="" loading="lazy" decoding="async">
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="tile-media tile-media-empty <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <span class="tile-pin" aria-hidden="true"<?php echo $pinColorTile ? ' style="--pin:' . esc_attr($pinColorTile) . '"' : ''; ?>><b><?php echo (int) $p['cislo']; ?></b></span>
      <div class="tile-caption">
        <h3><?php echo esc_html($p['nazov']); ?></h3>
        <?php if ($p['meta']): ?><p><?php echo esc_html($p['meta']); ?></p><?php endif; ?>
      </div>
      <?php if ($p['miestoId']): ?>
      <a class="tile-link" href="<?php echo nox_art_site_link('program', 'mapa'); ?>" data-show-on-map="<?php echo esc_attr($p['work'] ?: $p['miestoId']); ?>"><span class="sr-only">Ukázať <?php echo esc_html($p['nazov']); ?> na mape</span></a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="filter-empty" data-filter-empty="program" hidden>V tejto kategórii zatiaľ nič nie je.</p>
  </div>

  <?php /* Detail položky sa vykresľuje na server rovno ku každej dlaždici a
           prepína sa len skrývaním – otvorenie je tak okamžité a bez
           načítavania. */ ?>
  <div data-view-panel="detail" hidden>
    <button class="detail-back" type="button" data-detail-back>&larr; Späť na zoznam</button>
    <?php foreach ($polozky as $p): ?>
    <article class="detail" data-detail="<?php echo esc_attr($p['id']); ?>"<?php echo $p['slug'] ? ' data-slug="' . esc_attr($p['slug']) . '"' : ''; ?> hidden>
      <?php $pinColor = nox_art_item_color($p['kategorie']); ?>
      <h3 class="detail-title">
        <span class="detail-pin" aria-hidden="true"<?php echo $pinColor ? ' style="--pin:' . esc_attr($pinColor) . '"' : ''; ?>><b><?php echo (int) $p['cislo']; ?></b></span>
        <?php echo esc_html($p['nazov']); ?>
      </h3>
      <ul class="detail-facts">
        <?php if ($p['meta']): ?><li><?php echo esc_html($p['meta']); ?></li><?php endif; ?>
        <?php /* Pri podniku je meta riadok adresa miesta, takže jeho názov
                 už nemá čo pridať – vypíšeme ho len keď sa líši. */ ?>
        <?php if ($p['miestoNazov'] && $p['miestoNazov'] !== $p['meta']): ?><li><?php echo esc_html($p['miestoNazov']); ?></li><?php endif; ?>
        <?php foreach (nox_art_site_time_labels($p['terminy']) as $label): ?>
        <li><?php echo esc_html($label); ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($p['foto']): ?>
      <?php if ($p['fotoId']): ?>
      <?php echo wp_get_attachment_image($p['fotoId'], 'large', false, [
          'class' => 'detail-foto',
          'alt' => esc_attr($p['nazov']),
          'loading' => 'lazy',
          'decoding' => 'async',
          'sizes' => '(max-width: 760px) 92vw, 30vw',
      ]); ?>
      <?php else: ?>
      <img class="detail-foto" src="<?php echo esc_url($p['foto']); ?>" alt="<?php echo esc_attr($p['nazov']); ?>" loading="lazy" decoding="async">
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($p['popis']): ?>
      <div class="detail-text"><?php echo wp_kses_post($p['popis']); ?></div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php include NOX_ART_DIR . 'templates/parts/autori-detail.php'; ?>
  </div>

  <div class="schedule" data-view-panel="harmonogram" hidden>
    <?php include NOX_ART_DIR . 'templates/parts/harmonogram.php'; ?>
  </div>

  <div class="authors" data-view-panel="autori" hidden>
    <?php include NOX_ART_DIR . 'templates/parts/autori.php'; ?>
  </div>
  </div>

  <?php /* Mapa drží krok so zoznamom – na veľkej obrazovke je prilepená
           (sticky) vpravo v tretine, na malej sa presunie pod zoznam. */ ?>
  <aside class="program-layout-map reveal" id="mapa" aria-label="Mapa festivalových diel">
    <div class="route-map">
      <div class="route-map-head">
        <p>Festivalová mapa</p>
        <?php /* Počet sa prepisuje podľa toho, čo je práve vo výbere – skript
                 doplní aj správny tvar slova ("20 diel", "6 inštalácií"). */ ?>
        <span data-map-count><?php echo (int) $prvyPocet . ' ' . esc_html($prvySlovo); ?> / Sídlisko Píly</span>
      </div>
      <div class="route-map-stage">
        <div class="site-map-wrap"><div id="nox-site-map" class="site-map"></div></div>
      </div>
    </div>
    <p class="route-note">Klikni na značku na mape, alebo na dlaždicu v zozname.</p>
  </aside>
  </div>
</section>
