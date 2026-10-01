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

$filtre = nox_art_filter_terms(['nox_program', 'nox_dielo']);
?>
<section class="section program" id="program" aria-labelledby="program-title">
  <div class="section-label section-label-light reveal"><span>02</span> Program a diela</div>
  <div class="program-head reveal">
    <h2 id="program-title">Dve noci.<br>Jedna svetelná trasa.</h2>
    <p>Program budeme odhaľovať postupne. <?php echo (int) $dielaCount; ?> diel, sgrafitá v uliciach a sprievodný program nájdeš pokope — filtrom si vyberieš, čo ťa zaujíma.</p>
  </div>

  <?php if ($filtre): ?>
  <div class="filter-bar filter-bar-light reveal" data-filter-group="program" role="group" aria-label="Filtrovanie programu">
    <button class="filter-chip is-active" type="button" data-filter="*" aria-pressed="true"><i class="chip-pin" aria-hidden="true"></i>Všetky</button>
    <?php foreach ($filtre as $term): ?>
    <button class="filter-chip" type="button" data-filter="<?php echo esc_attr($term->slug); ?>" aria-pressed="false"><?php echo esc_html($term->name); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="gallery-grid" data-filter-target="program">
    <?php if (!$polozky): ?>
    <p class="empty" style="color:var(--paper);opacity:.7">Program zatiaľ nie je zverejnený — pridaj ho v administrácii (NOX:ART &rsaquo; Program, Diela).</p>
    <?php endif; ?>
    <?php foreach ($polozky as $i => $p): $visual = $visualClasses[$i % count($visualClasses)]; ?>
    <article class="gallery-tile reveal" id="<?php echo esc_attr($p['id']); ?>"<?php echo $p['work'] ? ' data-work="' . esc_attr($p['work']) . '"' : ''; ?> data-cat="<?php echo esc_attr(implode(' ', $p['kategorie'])); ?>">
      <?php if ($p['foto']): ?>
      <div class="tile-media" aria-hidden="true" style="background-image:url('<?php echo esc_url($p['foto']); ?>')"></div>
      <?php else: ?>
      <div class="tile-media tile-media-empty <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <span class="tile-pin" aria-hidden="true"><b><?php echo (int) ($i + 1); ?></b></span>
      <div class="tile-caption">
        <h3><?php echo esc_html($p['nazov']); ?></h3>
        <?php if ($p['meta']): ?><p><?php echo esc_html($p['meta']); ?></p><?php endif; ?>
      </div>
      <?php if ($p['miestoId']): ?>
      <a class="tile-link" href="<?php echo nox_art_site_link('info', 'mapa'); ?>"<?php echo $p['work'] ? ' data-show-on-map="' . esc_attr($p['work']) . '"' : ''; ?>><span class="sr-only">Ukázať <?php echo esc_html($p['nazov']); ?> na mape</span></a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="filter-empty" data-filter-empty="program" hidden>V tejto kategórii zatiaľ nič nie je.</p>
</section>
