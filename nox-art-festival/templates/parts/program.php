<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Program a diela v jednom zozname. Program festivalu obsahuje aj diela aj
 * sprievodný program, takže ich nemá zmysel deliť na dve sekcie – rozlišujú
 * sa kategóriami (Inštalácie, Nové sgrafitá, Živé sgrafitá, Galéria ulice,
 * Sprievodný program), podľa ktorých sa dá filtrovať.
 *
 * Poradie: najprv body s dátumom (chronologicky, tie už pole program má),
 * potom diela bez dátumu.
 */
$polozky = [];

foreach (nox_art_data_program() as $item) {
    list($dayName, $dayDate) = $item['datum'] ? nox_art_site_day_label($item['datum']) : ['', ''];
    $cas = $item['casOd'] . ($item['casDo'] ? '–' . $item['casDo'] : '');
    $polozky[] = [
        'id' => 'program-' . $item['id'],
        'nazov' => $item['nazov'],
        'foto' => $item['foto'],
        'kategorie' => $item['kategorie'],
        'meta' => trim(trim($dayName . ' ' . $dayDate) . ($cas ? ' · ' . $cas : '')),
        'work' => '',
        'miestoId' => $item['miestoId'],
    ];
}

foreach ($diela as $d) {
    $u = $umelecById[$d['umelecId']] ?? null;
    $polozky[] = [
        'id' => 'work-' . $d['id'],
        'nazov' => $d['nazov'],
        'foto' => $d['foto'],
        'kategorie' => $d['kategorie'],
        'meta' => $u ? $u['meno'] : '',
        'work' => $d['id'],
        'miestoId' => $d['miestoId'],
    ];
}

/* Miesta sa do zoznamu dostanú, len keď majú priradenú kategóriu – bežné
   miesto je nositeľom súradníc pre dielo, nie samostatná položka programu.
   Partnerský podnik naopak kategóriu má, a tak sa zobrazí ako dlaždica. */
foreach ($miesta as $m) {
    if (!$m['kategorie']) continue;
    $polozky[] = [
        'id' => 'miesto-' . $m['id'],
        'nazov' => $m['nazov'],
        'foto' => $m['foto'],
        'kategorie' => $m['kategorie'],
        'meta' => $m['adresa'],
        'work' => '',
        'miestoId' => $m['id'],
    ];
}

$filtre = nox_art_filter_tree(['nox_program', 'nox_dielo', 'nox_miesto']);
?>
<section class="section program" id="program" aria-labelledby="program-title">
  <div class="section-label section-label-light reveal"><span>01</span> Program a diela</div>
  <div class="program-head reveal">
    <h2 id="program-title">Dve noci.<br>Jedna svetelná trasa.</h2>
    <p>Program budeme odhaľovať postupne. <?php echo (int) $dielaCount; ?> diel, sprievodný program a podniky s festivalovým menu — prepni si, čo ťa práve zaujíma.</p>
  </div>

  <?php if ($filtre): ?>
  <?php /* Žiadne "Všetky" – skupiny sú rovnocenné, prvá je zapnutá pri
           načítaní stránky. Podkategórie sa odkryjú až po zvolení skupiny,
           ktorá ich má. */ ?>
  <div class="filter-bar filter-bar-light reveal" data-filter-group="program" role="group" aria-label="Filtrovanie programu">
    <?php foreach ($filtre as $i => $uzol): $term = $uzol['term']; ?>
    <button class="filter-chip<?php echo $i === 0 ? ' is-active' : ''; ?>" type="button" data-filter="<?php echo esc_attr($term->slug); ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>"><i class="chip-pin" aria-hidden="true" style="--pin:<?php echo esc_attr(nox_art_term_color($term)); ?>"></i><?php echo esc_html($term->name); ?></button>
    <?php endforeach; ?>
  </div>
  <?php foreach ($filtre as $i => $uzol): if (!$uzol['children']) continue; ?>
  <div class="filter-bar filter-bar-light filter-bar-sub" data-filter-sub="<?php echo esc_attr($uzol['term']->slug); ?>" data-filter-parent="program" role="group" aria-label="Spresnenie: <?php echo esc_attr($uzol['term']->name); ?>"<?php echo $i === 0 ? '' : ' hidden'; ?>>
    <?php foreach ($uzol['children'] as $child): ?>
    <button class="filter-chip filter-chip-sm" type="button" data-filter="<?php echo esc_attr($child->slug); ?>" aria-pressed="false"><?php echo esc_html($child->name); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <div class="program-layout">
  <div class="program-layout-list">
  <div class="gallery-grid" data-filter-target="program">
    <?php if (!$polozky): ?>
    <p class="empty" style="color:var(--paper);opacity:.7">Program zatiaľ nie je zverejnený — pridaj ho v administrácii (NOX:ART &rsaquo; Program, Diela).</p>
    <?php endif; ?>
    <?php foreach ($polozky as $i => $p): $visual = $visualClasses[$i % count($visualClasses)]; ?>
    <article class="gallery-tile reveal" id="<?php echo esc_attr($p['id']); ?>"<?php echo $p['work'] ? ' data-work="' . esc_attr($p['work']) . '"' : ''; ?> data-miesto="<?php echo esc_attr($p['miestoId'] ?: ''); ?>" data-cat="<?php echo esc_attr(implode(' ', $p['kategorie'])); ?>">
      <?php if ($p['foto']): ?>
      <div class="tile-media" aria-hidden="true" style="background-image:url('<?php echo esc_url($p['foto']); ?>')"></div>
      <?php else: ?>
      <div class="tile-media tile-media-empty <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <?php $pinColor = nox_art_item_color($p['kategorie']); ?>
      <span class="tile-pin" aria-hidden="true"<?php echo $pinColor ? ' style="--pin:' . esc_attr($pinColor) . '"' : ''; ?>><b><?php echo (int) ($i + 1); ?></b></span>
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

  <?php /* Mapa drží krok so zoznamom – na veľkej obrazovke je prilepená
           (sticky) vpravo, na malej sa presunie pod zoznam. */ ?>
  <aside class="program-layout-map reveal" id="mapa" aria-label="Mapa festivalových diel">
    <div class="route-map">
      <div class="route-map-head">
        <p>Mapa diel</p>
        <span><?php echo (int) count($miesta); ?> miest / Sídlisko Píly</span>
      </div>
      <div class="route-map-stage">
        <div class="site-map-wrap"><div id="nox-site-map" class="site-map"></div></div>
      </div>
      <p class="route-note">Klikni na značku na mape, alebo na dlaždicu v zozname.</p>
    </div>
  </aside>
  </div>
</section>
