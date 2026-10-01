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
<div class="schedule-day">
  <h3 class="schedule-day-title"><?php echo esc_html($nadpis); ?><?php if ($podnadpis): ?> <span><?php echo esc_html($podnadpis); ?></span><?php endif; ?></h3>
  <ul class="schedule-list">
    <?php foreach ($polozky as $p): ?>
    <li class="schedule-row">
      <time class="schedule-time"><?php echo esc_html($p['casOd']); ?><?php echo $p['casDo'] ? '–' . esc_html($p['casDo']) : ''; ?></time>
      <?php $pinColor = nox_art_item_color($p['kategorie']); ?>
      <span class="schedule-dot" aria-hidden="true"<?php echo $pinColor ? ' style="--pin:' . esc_attr($pinColor) . '"' : ''; ?>></span>
      <span class="schedule-name"><?php echo esc_html($p['nazov']); ?></span>
      <?php
      // Pri zlúčenom riadku povie počet, koľko diel sa za ním skrýva.
      $doplnok = $p['meta'];
      if (!$p['jednotlivo'] && $p['pocet'] > 1) {
          $doplnok = $p['pocet'] . ' ' . ($p['pocet'] < 5 ? 'diela' : 'diel');
      }
      ?>
      <?php if ($doplnok): ?><span class="schedule-meta"><?php echo esc_html($doplnok); ?></span><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endforeach; ?>
