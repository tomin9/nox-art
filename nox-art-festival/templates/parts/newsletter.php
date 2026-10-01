<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * Newsletter ako vysúvací panel: na stránke nie je vidieť nič, okno
 * s formulárom sa vysunie sprava po kliknutí na "Sleduj nás" (alebo na
 * ľubovoľný odkaz na #kontakt). Panel je fixovaný, takže na stránke
 * nezaberá miesto a je po ruke v ktorejkoľvek jej časti.
 */
?>
<aside class="newsletter-dock" id="kontakt" data-newsletter-dock>
  <div class="newsletter-panel" id="newsletter-panel" role="dialog" aria-labelledby="newsletter-title">
    <button class="newsletter-close" type="button" data-newsletter-close aria-label="Zavrieť panel">×</button>
    <p class="section-label"><span>04</span> Zostaň v obraze</p>
    <h2 id="newsletter-title">Program, autori a nové diela priamo do e-mailu.</h2>
    <form class="newsletter-form" data-newsletter-form>
      <label class="sr-only" for="email">E-mailová adresa</label>
      <input type="email" id="email" name="email" placeholder="tvoj@email.sk" autocomplete="email" required>
      <button type="submit">Prihlásiť sa <span aria-hidden="true">↗</span></button>
    </form>
    <p class="form-status" data-form-status role="status" aria-live="polite"></p>
    <p class="newsletter-note">Prihlásením súhlasíte so zasielaním noviniek o festivale. Z odberu sa môžete kedykoľvek odhlásiť.</p>
  </div>
</aside>
