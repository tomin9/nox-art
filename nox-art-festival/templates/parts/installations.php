<?php if (!defined('ABSPATH')) exit; ?>
<section class="section installations" id="instalacie" aria-labelledby="installations-title">
  <div class="section-label reveal"><span>03</span> Inštalácie</div>
  <div class="installations-head reveal">
    <h2 id="installations-title">Približne <?php echo (int) $dielaCount; ?> diel rozsvieti sídlisko Píly.</h2>
    <p><strong><?php echo (int) $dielaCount; ?> bodov</strong><br>Každé dielo má vlastnú kartu, autora, anotáciu a presné miesto na mape.</p>
  </div>
  <div class="installation-grid">
    <?php if (!$diela): ?>
    <p class="empty" style="color:var(--muted)">Diela zatiaľ nie sú pridané — pridaj ich v administrácii (NOX:ART &rsaquo; Diela).</p>
    <?php endif; ?>
    <?php foreach ($diela as $i => $d): $u = $umelecById[$d['umelecId']] ?? null; $visual = $visualClasses[$i % count($visualClasses)]; $typ = $d['typ'] ?: 'Inštalácia'; ?>
    <article class="installation-card reveal" id="work-<?php echo esc_attr($d['id']); ?>" data-title="<?php echo esc_attr($d['nazov']); ?>" data-type="<?php echo esc_attr($typ); ?>" data-work="<?php echo esc_attr($d['id']); ?>" data-miesto="<?php echo esc_attr($d['miestoId'] ?: ''); ?>">
      <?php if ($d['foto']): ?>
      <div class="card-visual" aria-hidden="true" style="background-image:url('<?php echo esc_url($d['foto']); ?>');background-size:cover;background-position:center"></div>
      <?php else: ?>
      <div class="card-visual <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <div class="card-top"><span><?php echo esc_html(sprintf('%02d', $i + 1)); ?> / <?php echo (int) $dielaCount; ?></span><span><?php echo esc_html($typ); ?></span></div>
      <h3><?php echo esc_html($d['nazov']); ?></h3>
      <p><?php echo esc_html($d['popis'] ? wp_trim_words(wp_strip_all_tags($d['popis']), 22, '…') : ($u ? 'Dielo od ' . $u['meno'] . '.' : 'Popis čoskoro doplníme.')); ?></p>
      <?php if ($d['miestoId']): ?>
      <a href="<?php echo nox_art_site_link('info', 'mapa'); ?>" data-show-on-map="<?php echo esc_attr($d['id']); ?>" aria-label="Ukázať dielo <?php echo esc_attr($d['nazov']); ?> na mape">Ukázať na mape <span aria-hidden="true">↗</span></a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>
