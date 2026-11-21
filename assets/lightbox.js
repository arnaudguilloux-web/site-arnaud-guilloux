/* Galerie : photo en grand dans une pop-in (✕, Échap, clic à côté, flèches ← →). */
(function () {
  var dlg = document.getElementById('lightbox');
  if (!dlg || typeof dlg.showModal !== 'function') return;
  var triggers = Array.prototype.slice.call(document.querySelectorAll('.gallery .g-zoom'));
  if (!triggers.length) return;
  var img = dlg.querySelector('.lb-stage img'), cap = dlg.querySelector('figcaption'), cur = 0;

  function show(i) {
    cur = (i + triggers.length) % triggers.length;
    var src = triggers[cur].querySelector('img'), fc = triggers[cur].parentNode.querySelector('figcaption');
    img.src = src.currentSrc || src.src;
    img.alt = src.alt;
    cap.innerHTML = fc ? fc.innerHTML : '';
    dlg.classList.toggle('single', triggers.length < 2);
  }
  triggers.forEach(function (t, i) {
    t.addEventListener('click', function () { show(i); dlg.showModal(); document.documentElement.classList.add('lb-open'); });
  });
  dlg.querySelector('.lb-close').addEventListener('click', function () { dlg.close(); });
  dlg.querySelector('.lb-prev').addEventListener('click', function () { show(cur - 1); });
  dlg.querySelector('.lb-next').addEventListener('click', function () { show(cur + 1); });
  /* clic sur le fond (hors photo, légende et boutons) : ferme */
  dlg.addEventListener('click', function (ev) {
    if (ev.target === dlg || ev.target === dlg.querySelector('.lb-stage')) dlg.close();
  });
  dlg.addEventListener('keydown', function (ev) {
    if (ev.key === 'ArrowLeft') { ev.preventDefault(); show(cur - 1); }
    else if (ev.key === 'ArrowRight') { ev.preventDefault(); show(cur + 1); }
  });
  dlg.addEventListener('close', function () { document.documentElement.classList.remove('lb-open'); img.removeAttribute('src'); });
})();
