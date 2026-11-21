/* Carrousel à défilement (scroll-snap) avec points et flèches. Utilisé par Highlights et Témoignages. */
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  function carousel(carId, dotsId, prevId, nextId, sel) {
    var car = document.getElementById(carId), dots = document.getElementById(dotsId);
    if (!car || !dots || !('IntersectionObserver' in window)) return;
    var slides = car.querySelectorAll(sel), marks = dots.children;
    Array.prototype.forEach.call(marks, function (m, i) {
      m.addEventListener('click', function () { slides[i].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', inline: 'center', block: 'nearest' }); });
    });
    var so = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          var k = Array.prototype.indexOf.call(slides, en.target);
          Array.prototype.forEach.call(marks, function (m, i) { m.classList.toggle('on', i === k); });
        }
      });
    }, { root: car, threshold: 0.6 });
    slides.forEach(function (s) { so.observe(s); });
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
