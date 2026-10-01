<?php if (!defined('ABSPATH')) exit; ?>
<?php $dielaFiltre = nox_art_filter_terms('nox_dielo'); ?>
<section class="section installations" id="instalacie" aria-labelledby="installations-title">
  <div class="section-label reveal"><span>03</span> Diela</div>
  <div class="installations-head reveal">
    <h2 id="installations-title">Približne <?php echo (int) $dielaCount; ?> diel rozsvieti sídlisko Píly.</h2>
    <p><strong><?php echo (int) $dielaCount; ?> bodov</strong><br>Každé dielo má vlastnú kartu, autora, anotáciu a presné miesto na mape.</p>
  </div>

  <?php if ($dielaFiltre): ?>
  <div class="filter-bar reveal" data-filter-group="diela" role="group" aria-label="Filtrovanie diel">
    <button class="filter-chip is-active" type="button" data-filter="*" aria-pressed="true"><i class="chip-pin" aria-hidden="true"></i>Všetky</button>
    <?php foreach ($dielaFiltre as $term): ?>
    <button class="filter-chip" type="button" data-filter="<?php echo esc_attr($term->slug); ?>" aria-pressed="false"><?php echo esc_html($term->name); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="gallery-grid" data-filter-target="diela">
    <?php if (!$diela): ?>
    <p class="empty" style="color:var(--muted)">Diela zatiaľ nie sú pridané — pridaj ich v administrácii (NOX:ART &rsaquo; Diela).</p>
    <?php endif; ?>
    <?php foreach ($diela as $i => $d): $u = $umelecById[$d['umelecId']] ?? null; $visual = $visualClasses[$i % count($visualClasses)]; ?>
    <article class="gallery-tile reveal" id="work-<?php echo esc_attr($d['id']); ?>" data-work="<?php echo esc_attr($d['id']); ?>" data-cat="<?php echo esc_attr(implode(' ', $d['kategorie'])); ?>" data-miesto="<?php echo esc_attr($d['miestoId'] ?: ''); ?>">
      <?php if ($d['foto']): ?>
      <div class="tile-media" aria-hidden="true" style="background-image:url('<?php echo esc_url($d['foto']); ?>')"></div>
      <?php else: ?>
      <div class="tile-media tile-media-empty <?php echo esc_attr($visual); ?>" aria-hidden="true"><span></span><i></i><b></b></div>
      <?php endif; ?>
      <span class="tile-pin" aria-hidden="true"><b><?php echo (int) ($i + 1); ?></b></span>
      <div class="tile-caption">
        <h3><?php echo esc_html($d['nazov']); ?></h3>
        <?php if ($u): ?><p><?php echo esc_html($u['meno']); ?></p><?php endif; ?>
      </div>
      <?php if ($d['miestoId']): ?>
      <a class="tile-link" href="<?php echo nox_art_site_link('info', 'mapa'); ?>" data-show-on-map="<?php echo esc_attr($d['id']); ?>"><span class="sr-only">Ukázať dielo <?php echo esc_html($d['nazov']); ?> na mape</span></a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="filter-empty" data-filter-empty="diela" hidden>V tejto kategórii zatiaľ nie je žiadne dielo.</p>
</section>
