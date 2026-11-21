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
