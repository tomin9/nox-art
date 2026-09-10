<?php if (!defined('ABSPATH')) exit; ?>
<section class="section program" id="program" aria-labelledby="program-title">
  <div class="section-label section-label-light reveal"><span>02</span> Program</div>
  <div class="program-head reveal">
    <h2 id="program-title">Dve noci.<br>Jedna svetelná trasa.</h2>
    <p>Program budeme odhaľovať postupne. Finálny harmonogram, miesta a mená autorov zverejníme pred festivalom.</p>
  </div>
  <?php if ($programByDay): ?>
  <div class="program-tabs reveal" role="tablist" aria-label="Festivalové dni">
    <?php $i = 0; foreach ($programByDay as $datum => $items): list($dayName, $dayDate) = nox_art_site_day_label($datum); $panelId = 'day-' . sanitize_title($datum); ?>
    <button class="program-tab<?php echo $i === 0 ? ' is-active' : ''; ?>" role="tab" type="button" data-tab="<?php echo esc_attr($panelId); ?>" id="tab-<?php echo esc_attr($panelId); ?>" aria-controls="<?php echo esc_attr($panelId); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
      <span><?php echo esc_html($dayName); ?></span><strong><?php echo esc_html($dayDate); ?></strong>
    </button>
    <?php $i++; endforeach; ?>
  </div>
  <div class="program-panels reveal">
    <?php $i = 0; foreach ($programByDay as $datum => $items): $panelId = 'day-' . sanitize_title($datum); ?>
    <div class="program-panel<?php echo $i === 0 ? ' is-active' : ''; ?>" id="<?php echo esc_attr($panelId); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr($panelId); ?>"<?php echo $i === 0 ? '' : ' hidden'; ?>>
      <?php foreach ($items as $ri => $item): ?>
      <article class="program-row">
        <time><?php echo esc_html($item['casOd']); ?><?php echo $item['casDo'] ? '&ndash;' . esc_html($item['casDo']) : ''; ?></time>
        <div>
          <h3><?php echo esc_html($item['nazov']); ?></h3>
          <?php if ($item['popis']): ?><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($item['popis']), 26, '…')); ?></p><?php endif; ?>
        </div>
        <span><?php echo esc_html(sprintf('%02d', $ri + 1)); ?></span>
      </article>
      <?php endforeach; ?>
    </div>
    <?php $i++; endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty" style="color:var(--paper);opacity:.7">Program zatiaľ nie je zverejnený.</div>
  <?php endif; ?>
</section>
