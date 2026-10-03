<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Detaily autorov – vykreslené vedľa detailov diel, prepína ich skript.
 */
$dielaPodlaAutora = [];
foreach (nox_art_data_diela() as $dielo) {
    if ($dielo['umelecId']) $dielaPodlaAutora[$dielo['umelecId']][] = $dielo;
}
?>
<?php foreach (nox_art_data_umelci() as $a): $diela = $dielaPodlaAutora[$a['id']] ?? []; ?>
<article class="detail" data-detail="autor-<?php echo (int) $a['id']; ?>"<?php echo $a['slug'] ? ' data-slug="' . esc_attr($a['slug']) . '"' : ''; ?> data-autor-diela="<?php echo esc_attr(implode(' ', array_map(function ($d) { return 'work-' . $d['id']; }, $diela))); ?>" hidden>
  <h3 class="detail-title"><?php echo esc_html($a['meno']); ?></h3>
  <?php if ($a['fotoId']): ?>
  <?php echo wp_get_attachment_image($a['fotoId'], 'large', false, [
      'class' => 'detail-foto',
      'alt' => esc_attr($a['meno']),
      'loading' => 'lazy',
      'decoding' => 'async',
      'sizes' => '(max-width: 760px) 92vw, 30vw',
  ]); ?>
  <?php endif; ?>
  <?php if (trim(wp_strip_all_tags($a['popis']))): ?>
  <div class="detail-text"><?php echo wp_kses_post($a['popis']); ?></div>
  <?php endif; ?>
  <?php if ($diela): ?>
  <ul class="author-works">
    <?php foreach ($diela as $dielo): ?>
    <li><button type="button" class="author-work" data-row-detail="work-<?php echo (int) $dielo['id']; ?>"><?php echo esc_html($dielo['nazov']); ?></button></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</article>
<?php endforeach; ?>
