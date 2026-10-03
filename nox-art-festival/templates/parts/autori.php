<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Autori – pohľad sekcie Program a diela. Autor a jeho diela; dielo vedie
 * na svoj detail rovnako ako dlaždica.
 */
$dielaAutorov = [];
foreach (nox_art_data_diela() as $dielo) {
    if ($dielo['umelecId']) $dielaAutorov[$dielo['umelecId']][] = $dielo;
}
$autori = nox_art_data_umelci();
?>
<?php if (!$autori): ?>
<p class="empty" style="color:var(--paper);opacity:.7">Autori zatiaľ nie sú zverejnení — pridaj ich v administrácii (NOX:ART &rsaquo; Umelci).</p>
<?php endif; ?>

<ul class="authors-list">
  <?php foreach ($autori as $a): ?>
  <li class="author" data-autor>
    <?php if ($a['fotoId']): ?>
    <?php echo wp_get_attachment_image($a['fotoId'], 'medium', false, ['class' => 'author-foto', 'alt' => esc_attr($a['meno']), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '120px']); ?>
    <?php endif; ?>
    <div class="author-body">
      <h3 class="author-name"><?php echo esc_html($a['meno']); ?></h3>
      <?php if (trim(wp_strip_all_tags($a['popis']))): ?>
      <div class="author-bio"><?php echo wp_kses_post($a['popis']); ?></div>
      <?php endif; ?>
      <?php if (!empty($dielaAutorov[$a['id']])): ?>
      <ul class="author-works">
        <?php foreach ($dielaAutorov[$a['id']] as $dielo): ?>
        <li><button type="button" class="author-work" data-row-detail="work-<?php echo (int) $dielo['id']; ?>"><?php echo esc_html($dielo['nazov']); ?></button></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </li>
  <?php endforeach; ?>
</ul>
