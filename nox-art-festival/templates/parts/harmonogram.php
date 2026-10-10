<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Časový harmonogram – jeden z pohľadov sekcie Program a diela (prepína sa
 * posledným filtrom). Vypisuje všetko, čo má vyplnený čas, po dňoch v
 * riadkoch. Čerpá z toho istého zoznamu ako dlaždice, takže sa obe
 * zobrazenia nemôžu rozísť.
 */
$dni = nox_art_site_schedule();
?>
<?php if (!$dni): ?>
<p class="empty" style="color:var(--paper);opacity:.7">Harmonogram zatiaľ nie je vyplnený — pridaj dielam a bodom programu čas v administrácii.</p>
<?php endif; ?>

<?php
// Dni pred začiatkom festivalu (napr. warm-up v inom meste) sa zobrazia
// oddelene, nech sa nezamiešajú s programom na sídlisku.
$zaciatok = apply_filters('nox_art_festival_start', '2026-10-30');
$pred = [];
$hlavne = [];
foreach ($dni as $datum => $polozky) {
    if ($datum !== '' && $datum < $zaciatok) $pred[$datum] = $polozky;
    else $hlavne[$datum] = $polozky;
}

$vykresli = function ($dni) {
foreach ($dni as $datum => $polozky) {
?>
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
<div class="schedule-day">
  <h3 class="schedule-day-title"><?php echo esc_html($nadpis); ?><?php if ($podnadpis): ?> <span><?php echo esc_html($podnadpis); ?></span><?php endif; ?></h3>
  <ul class="schedule-list">
    <?php foreach ($polozky as $p): ?>
    <li class="schedule-row"<?php
      // Riadok vedie buď na detail konkrétnej položky, alebo – ak zlučuje
      // viac diel – na svoju kategóriu v zozname.
      if ($p['detailId']) echo ' data-row-detail="' . esc_attr($p['detailId']) . '"';
      elseif ($p['kategoriaSlug']) echo ' data-row-kategoria="' . esc_attr($p['kategoriaSlug']) . '"';
    ?>>
      <time class="schedule-time"><?php echo esc_html($p['casOd']); ?><?php echo $p['casDo'] ? '–' . esc_html($p['casDo']) : ''; ?></time>
      <?php $pinColor = nox_art_item_color($p['kategorie']); ?>
      <span class="schedule-dot" aria-hidden="true"<?php echo $pinColor ? ' style="--pin:' . esc_attr($pinColor) . '"' : ''; ?>></span>
      <?php
      // Počet diel za zlúčeným riadkom stojí hneď za názvom.
      $pocetText = (!$p['jednotlivo'] && $p['pocet'] > 1)
          ? $p['pocet'] . ' ' . ($p['pocet'] < 5 ? 'diela' : 'diel')
          : '';
      ?>
      <span class="schedule-name"><?php echo esc_html($p['nazov']); ?><?php if ($pocetText): ?> <span class="schedule-count"><?php echo esc_html($pocetText); ?></span><?php endif; ?></span>
      <?php if ($p['miesto']): ?><span class="schedule-meta"><?php echo esc_html($p['miesto']); ?></span><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php
}
};
?>
<?php if ($pred): ?>
<div class="schedule-pre">
  <p class="schedule-pre-label">Pred festivalom <span>mimo festivalových dní</span></p>
  <?php $vykresli($pred); ?>
</div>
<?php endif; ?>
<?php if ($hlavne): ?>
<div class="schedule-pre schedule-main">
  <p class="schedule-pre-label">Festival <span><b class="schedule-date">30.–31. 10.</b> · Sídlisko Píly</span></p>
  <?php $vykresli($hlavne); ?>
</div>
<?php endif; ?>
