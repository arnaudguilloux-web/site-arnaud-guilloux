/* Menu : sous-menu « L'athlète » (survol sur souris, clic/tap/clavier partout, Échap et clic extérieur pour fermer). */
(function () {
  var wraps = document.querySelectorAll('.nav .has-sub');
  if (!wraps.length) return;

  function set(w, open) {
    w.classList.toggle('open', open);
    var t = w.querySelector('.nav-trigger');
    if (t) t.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  function closeAll(except) {
    Array.prototype.forEach.call(wraps, function (w) { if (w !== except) set(w, false); });
  }

  Array.prototype.forEach.call(wraps, function (w) {
    var t = w.querySelector('.nav-trigger');
    t.addEventListener('click', function () { var o = !w.classList.contains('open'); closeAll(w); set(w, o); });
    /* Un lien du sous-menu a été choisi : on referme */
    w.querySelector('.sub').addEventListener('click', function (ev) { if (ev.target.closest('a')) set(w, false); });
    w.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') { set(w, false); t.focus(); }
    });
    /* Fermeture quand le focus quitte le menu (navigation clavier) */
    w.addEventListener('focusout', function (ev) {
      if (!w.contains(ev.relatedTarget)) set(w, false);
    });
  });
  document.addEventListener('click', function (ev) {
    Array.prototype.forEach.call(wraps, function (w) { if (!w.contains(ev.target)) set(w, false); });
  });
})();

/* Scroll-spy : met en avant, dans les sous-menus, la section visible (liens en #ancre de la page courante). */
(function () {
  var links = Array.prototype.slice.call(document.querySelectorAll('.nav .sub a[href^="#"]'));
  if (!links.length) return;
  var targets = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
  function spy() {
    var cur = -1;
    targets.forEach(function (el, i) { if (el && el.getBoundingClientRect().top <= 120) cur = i; });
    links.forEach(function (a, i) { a.classList.toggle('on', i === cur); });
    Array.prototype.forEach.call(document.querySelectorAll('.nav .has-sub'), function (w) {
      var t = w.querySelector('.nav-trigger');
      t.classList.toggle('on', t.getAttribute('data-page-on') === '1' || !!w.querySelector('.sub a.on'));
    });
  }
  addEventListener('scroll', spy, { passive: true });
  addEventListener('resize', spy);
  spy();
})();
