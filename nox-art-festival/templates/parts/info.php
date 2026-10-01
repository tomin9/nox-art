<?php if (!defined('ABSPATH')) exit; ?>
<section class="section info" id="info" aria-labelledby="info-title">
  <div class="section-label reveal"><span>03</span> Praktické info</div>
  <div class="info-grid info-grid-single">
    <div class="info-content reveal">
      <h2 id="info-title"><?php echo (int) $dielaCount; ?> diel. Jedna nočná trasa.</h2>
      <dl class="info-list">
        <div><dt>Kedy</dt><dd>30.&ndash;31. október 2026</dd></div>
        <div><dt>Kde</dt><dd>Sídlisko Píly, Prievidza</dd></div>
        <div><dt>Diela</dt><dd>Približne <?php echo (int) $dielaCount; ?> svetelných, digitálnych a zvukových inštalácií</dd></div>
        <div><dt>Odporúčanie</dt><dd>Teplé oblečenie, pohodlná obuv a nabitý telefón</dd></div>
      </dl>
      <div class="info-actions">
        <a class="button button-dark" href="<?php echo nox_art_site_link('program', 'mapa'); ?>">Mapa diel <span aria-hidden="true">↗</span></a>
        <a class="button button-outline" href="https://maps.google.com/?q=S%C3%ADdlisko+P%C3%ADly+Prievidza" target="_blank" rel="noreferrer">Otvoriť polohu <span aria-hidden="true">↗</span></a>
      </div>
    </div>
  </div>
</section>
