<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Partneri po skupinách (organizátor, generálny partner, partneri,
 * mediálni partneri). Obsah sa spravuje v NOX:ART → Partneri, logo je
 * náhľadový obrázok záznamu.
 */
$skupiny = nox_art_data_partneri();
?>
<section class="section partners" id="partneri" aria-labelledby="partners-title">
  <div class="section-label reveal"><span>03</span> Partneri</div>
  <div class="partners-head reveal">
    <h2 id="partners-title">Festival vzniká vďaka ľuďom a organizáciám, ktoré veria verejnému priestoru.</h2>
    <p>Ďakujeme všetkým, ktorí pomáhajú dostať súčasné umenie do verejného priestoru.</p>
  </div>

  <?php if (!$skupiny): ?>
  <?php if (current_user_can('edit_posts')): ?>
  <p class="partners-empty">Partnerov aj ich logá pridáš v administrácii v sekcii NOX:ART → Partneri.</p>
  <?php endif; ?>
  <?php else: ?>
  <?php foreach ($skupiny as $slug => $skupina): ?>
  <div class="partner-group reveal" data-skupina="<?php echo esc_attr($slug); ?>">
    <h3 class="partner-group-title"><?php echo esc_html($skupina['nazov']); ?></h3>
    <ul class="partner-logos">
      <?php foreach ($skupina['polozky'] as $partner): ?>
      <li class="partner-logo">
        <?php if ($partner['url']): ?><a href="<?php echo esc_url($partner['url']); ?>" target="_blank" rel="noopener"><?php endif; ?>
        <?php if ($partner['logo']): ?>
        <img src="<?php echo esc_url($partner['logo']); ?>" alt="<?php echo esc_attr($partner['nazov']); ?>" loading="lazy">
        <?php else: ?>
        <span class="partner-logo-text"><?php echo esc_html($partner['nazov']); ?></span>
        <?php endif; ?>
        <?php if ($partner['url']): ?></a><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php /* Doložky o finančnej podpore – spravujú sa v NOX:ART → Nastavenia. */ ?>
  <?php $dolozky = nox_art_support_notes(); ?>
  <?php if ($dolozky): ?>
  <ul class="partner-support reveal">
    <?php foreach ($dolozky as $dolozka): ?>
    <li><?php echo esc_html($dolozka); ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
