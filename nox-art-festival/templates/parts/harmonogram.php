<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Časový harmonogram: všetko, čo má vyplnený čas (diela, sprievodný program
 * aj partnerské podniky), vypísané po dňoch v riadkoch. Čerpá z toho istého
 * zoznamu ako sekcia Program, takže sa obe nemôžu rozísť.
 */
$dni = nox_art_site_schedule();
?>
<section class="section schedule" id="harmonogram" aria-labelledby="schedule-title">
  <div class="section-label reveal"><span>02</span> Časový harmonogram</div>
  <div class="program-head reveal">
    <h2 id="schedule-title">Kedy čo beží.</h2>
    <p>Diela, sprievodný program aj podniky pokope — zoradené podľa času.</p>
  </div>

  <?php if (!$dni): ?>
  <p class="empty" style="color:var(--muted)">Harmonogram zatiaľ nie je vyplnený — pridaj dielam a bodom programu čas v administrácii.</p>
  <?php endif; ?>

  <?php foreach ($dni as $datum => $polozky): ?>
  <?php
  if ($datum) {
      list($dayName, $dayDate) = nox_art_site_day_label($datum);
      $nadpis = $dayName;
      $podnadpis = $dayDate;
  } else {
      $nadpis = 'Počas celého festivalu';
      $podnadpis = '';
  }
  ?>
  <div class="schedule-day reveal">
    <h3 class="schedule-day-title"><?php echo esc_html($nadpis); ?><?php if ($podnadpis): ?> <span><?php echo esc_html($podnadpis); ?></span><?php endif; ?></h3>
    <ul class="schedule-list">
      <?php foreach ($polozky as $p): ?>
      <li class="schedule-row">
        <time class="schedule-time"><?php echo esc_html($p['casOd']); ?><?php echo $p['casDo'] ? '–' . esc_html($p['casDo']) : ''; ?></time>
        <?php $pinColor = nox_art_item_color($p['kategorie']); ?>
        <span class="schedule-dot" aria-hidden="true"<?php echo $pinColor ? ' style="--pin:' . esc_attr($pinColor) . '"' : ''; ?>></span>
        <span class="schedule-name"><?php echo esc_html($p['nazov']); ?></span>
        <?php if ($p['meta']): ?><span class="schedule-meta"><?php echo esc_html($p['meta']); ?></span><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
</section>
