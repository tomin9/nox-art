<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Autori – pohľad sekcie Program a diela. Dlaždice vyzerajú ako dlaždice diel
 * (fotka autora + meno), kliknutie otvorí detail autora (autori-detail.php).
 */
$autori = nox_art_data_umelci();
$dielaPodlaAutora = [];
foreach (nox_art_data_diela() as $dielo) {
    if ($dielo['umelecId']) $dielaPodlaAutora[$dielo['umelecId']][] = $dielo;
}
?>
<?php if (!$autori): ?>
<p class="empty" style="color:var(--paper);opacity:.7">Autori zatiaľ nie sú zverejnení — pridaj ich v administrácii (NOX:ART &rsaquo; Umelci).</p>
<?php endif; ?>

<div class="gallery-grid authors-grid">
  <?php foreach ($autori as $i => $a): $pocet = count($dielaPodlaAutora[$a['id']] ?? []); ?>
  <article class="gallery-tile author-tile reveal" data-autor data-open="autor-<?php echo (int) $a['id']; ?>" tabindex="0" role="button" aria-label="<?php echo esc_attr($a['meno']); ?>">
    <?php if ($a['fotoId']): ?>
    <div class="tile-media" aria-hidden="true">
      <?php echo wp_get_attachment_image($a['fotoId'], 'large', false, [
          'class' => 'tile-media-img',
          'alt' => '',
          'loading' => 'lazy',
          'decoding' => 'async',
          'sizes' => '(max-width: 760px) 92vw, (max-width: 1180px) 44vw, 20vw',
      ]); ?>
    </div>
    <?php else: ?>
    <div class="tile-media tile-media-empty <?php echo esc_attr($visualClasses[$i % count($visualClasses)]); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
    <?php endif; ?>
    <div class="tile-caption">
      <h3><?php echo esc_html($a['meno']); ?></h3>
      <?php if ($pocet): ?><p><?php echo (int) $pocet; ?> <?php echo $pocet === 1 ? 'dielo' : ($pocet < 5 ? 'diela' : 'diel'); ?></p><?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</div>
