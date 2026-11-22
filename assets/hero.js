/* Diaporama du hero (page Athlète) : fondu automatique, puces cliquables.
   Pause au survol / au focus et quand l'onglet est masqué ; pas d'enchaînement automatique si « réduire les animations ». */
(function () {
  var box = document.getElementById('hero-dots');
  if (!box) return;
  var slides = document.querySelectorAll('.hero-slide'), dots = box.children, cur = 0, timer = 0, paused = false;
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(i) {
    cur = (i + slides.length) % slides.length;
    Array.prototype.forEach.call(slides, function (s, k) { s.classList.toggle('is-on', k === cur); });
    Array.prototype.forEach.call(dots, function (d, k) {
      d.classList.toggle('on', k === cur);
      if (k === cur) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
    });
  }
  function start() { stop(); if (!reduce && !paused && !document.hidden) timer = setInterval(function () { show(cur + 1); }, 6000); }
  function stop() { clearInterval(timer); timer = 0; }
  Array.prototype.forEach.call(dots, function (d, k) { d.addEventListener('click', function () { show(k); start(); }); });
  var panel = document.querySelector('.hero-panel');
  panel.addEventListener('mouseenter', function () { paused = true; stop(); });
  panel.addEventListener('mouseleave', function () { paused = false; start(); });
  panel.addEventListener('focusin', function () { paused = true; stop(); });
  panel.addEventListener('focusout', function () { paused = false; start(); });
  document.addEventListener('visibilitychange', start);
  start();
})();
