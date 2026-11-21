/* Carrousel à défilement (scroll-snap) avec points et flèches. Utilisé par Highlights et Témoignages. */
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  function carousel(carId, dotsId, prevId, nextId, sel) {
    var car = document.getElementById(carId), dots = document.getElementById(dotsId);
    if (!car || !dots) return;
    var slides = car.querySelectorAll(sel), marks = dots.children;
    Array.prototype.forEach.call(marks, function (m, i) {
      m.addEventListener('click', function () { slides[i].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', inline: 'center', block: 'nearest' }); });
    });
    /* Point actif : 1re carte tout à gauche, dernière tout à droite, sinon la carte la plus proche du centre.
       (L'ancien calcul par IntersectionObserver prenait la dernière carte visible, donc un point du milieu au chargement.) */
    var raf = 0;
    function update() {
      raf = 0;
      var k = 0, max = car.scrollWidth - car.clientWidth;
      if (car.scrollLeft <= 2) k = 0;
      else if (car.scrollLeft >= max - 2) k = slides.length - 1;
      else {
        var mid = car.getBoundingClientRect().left + car.clientWidth / 2, best = Infinity;
        Array.prototype.forEach.call(slides, function (sl, i) {
          var r = sl.getBoundingClientRect(), d = Math.abs(r.left + r.width / 2 - mid);
          if (d < best) { best = d; k = i; }
        });
      }
      Array.prototype.forEach.call(marks, function (m, i) { m.classList.toggle('on', i === k); });
    }
    function queue() { if (!raf) raf = requestAnimationFrame(update); }
    car.addEventListener('scroll', queue, { passive: true });
    addEventListener('resize', queue);
    update();
    [[prevId, -1], [nextId, 1]].forEach(function (a) {
      var b = document.getElementById(a[0]);
      if (!b) return;
      b.addEventListener('click', function () {
        var step = slides[0].getBoundingClientRect().width + 20;
        car.scrollBy({ left: a[1] * step, behavior: reduce ? 'auto' : 'smooth' });
      });
    });
  }
  window.carousel = carousel;
})();
