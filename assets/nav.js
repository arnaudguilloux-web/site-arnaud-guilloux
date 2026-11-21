/* Menu : sous-menus « L'Athlète » et « Le Coach ».
   - Ordinateur (souris) : le sous-menu s'ouvre au survol (ou au focus clavier) ; cliquer le titre ouvre la page.
   - Mobile / tactile : le clic sur le titre déplie le sous-menu (les pages sont atteintes par ses entrées « Présentation »).
   - Échap et clic extérieur referment. */
(function () {
  var wraps = Array.prototype.slice.call(document.querySelectorAll('.nav .has-sub'));
  if (!wraps.length) return;
  var desktop = matchMedia('(hover: hover) and (min-width: 761px)');

  function set(w, open) {
    w.classList.toggle('open', open);
    var t = w.querySelector('.nav-trigger');
    if (t) t.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  function closeAll(except) { wraps.forEach(function (w) { if (w !== except) set(w, false); }); }

  wraps.forEach(function (w) {
    var t = w.querySelector('.nav-trigger');
    t.addEventListener('click', function (ev) {
      if (desktop.matches) return;                 /* ordinateur : le lien navigue */
      ev.preventDefault();
      var o = !w.classList.contains('open'); closeAll(w); set(w, o);
    });
    w.querySelector('.sub').addEventListener('click', function (ev) { if (ev.target.closest('a')) set(w, false); });
    w.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') { set(w, false); w.classList.add('esc'); t.focus(); }
    });
    ['mouseenter', 'focusin'].forEach(function (n) { w.addEventListener(n, function () { w.classList.remove('esc'); }); });
    w.addEventListener('focusout', function (ev) { if (!w.contains(ev.relatedTarget)) { set(w, false); w.classList.remove('esc'); } });
  });
  document.addEventListener('click', function (ev) { wraps.forEach(function (w) { if (!w.contains(ev.target)) set(w, false); }); });
})();

/* Scroll-spy : met en avant, dans les sous-menus, la section visible (liens en #ancre de la page courante). */
(function () {
  var links = Array.prototype.slice.call(document.querySelectorAll('.nav .sub a[href^="#"]:not([data-top])'));
  var topLink = document.querySelector('.nav .sub a[data-top]');   /* « Présentation » : actif en haut de page */
  if (!links.length && !topLink) return;
  var targets = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
  function spy() {
    var cur = -1;
    targets.forEach(function (el, i) { if (el && el.getBoundingClientRect().top <= 120) cur = i; });
    links.forEach(function (a, i) { a.classList.toggle('on', i === cur); });
    if (topLink) topLink.classList.toggle('on', cur === -1);
    Array.prototype.forEach.call(document.querySelectorAll('.nav .has-sub'), function (w) {
      var t = w.querySelector('.nav-trigger');
      t.classList.toggle('on', t.getAttribute('data-page-on') === '1' || !!w.querySelector('.sub a.on'));
    });
  }
  addEventListener('scroll', spy, { passive: true });
  addEventListener('resize', spy);
  spy();
})();
