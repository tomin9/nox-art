<?php if (!defined('ABSPATH')) exit; ?>
<section class="section info" id="info" aria-labelledby="info-title">
  <div class="section-label reveal"><span>04</span> Mapa a praktické info</div>
  <div class="info-grid">
    <div class="route-map reveal" id="mapa" aria-label="Mapa festivalových diel">
      <div class="route-map-head">
        <p>Mapa diel</p>
        <span><?php echo (int) count($miesta); ?> miest / Sídlisko Píly</span>
      </div>
      <div class="route-map-stage">
        <div class="site-map-wrap"><div id="nox-site-map" class="site-map"></div></div>
      </div>
      <p class="route-note">Klikni na značku na mape, alebo použi „Ukázať na mape&ldquo; pri diele vyššie.</p>
    </div>
    <div class="info-content reveal">
      <h2 id="info-title"><?php echo (int) $dielaCount; ?> diel. Jedna nočná trasa.</h2>
      <dl class="info-list">
        <div><dt>Kedy</dt><dd>30.&ndash;31. október 2026</dd></div>
        <div><dt>Kde</dt><dd>Sídlisko Píly, Prievidza</dd></div>
        <div><dt>Diela</dt><dd>Približne <?php echo (int) $dielaCount; ?> svetelných, digitálnych a zvukových inštalácií</dd></div>
        <div><dt>Odporúčanie</dt><dd>Teplé oblečenie, pohodlná obuv a nabitý telefón</dd></div>
      </dl>
      <a class="button button-dark" href="https://maps.google.com/?q=S%C3%ADdlisko+P%C3%ADly+Prievidza" target="_blank" rel="noreferrer">Otvoriť polohu <span aria-hidden="true">↗</span></a>
    </div>
  </div>
</section>
