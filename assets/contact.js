/* Popin de contact : tout élément [data-contact="sujet"] l'ouvre avec le sujet présélectionné.
   Envoi sans quitter la page ; sans JavaScript, le lien (href) mène au formulaire de la page d'accueil. */
(function () {
  var dlg = document.getElementById('contact-dialog');
  if (dlg && typeof dlg.showModal === 'function') {
    var dform = dlg.querySelector('form');
    var dstatus = dlg.querySelector('[data-cd-status]');
    var closeDlg = function () { dlg.close(); };
    var resetDlg = function () { dstatus.hidden = true; dstatus.removeAttribute('role'); dform.hidden = false; };
    Array.prototype.forEach.call(document.querySelectorAll('[data-contact]'), function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        resetDlg();
        var sel = dform.querySelector('select[name="topic"]');
        if (sel) sel.value = btn.getAttribute('data-contact');
        dlg.showModal();
        var first = dform.querySelector('input[name="name"]');
        if (first) first.focus();
      });
    });
    dlg.querySelector('[data-cd-close]').addEventListener('click', closeDlg);
    dlg.addEventListener('click', function (ev) { if (ev.target === dlg) closeDlg(); });
    dform.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var send = dform.querySelector('button[type="submit"]');
      send.disabled = true;
      fetch(dform.action, { method: 'POST', body: new FormData(dform), credentials: 'same-origin' })
        .then(function (r) { return r.url.indexOf('sent=1') > -1; })
        .catch(function () { return false; })
        .then(function (ok) {
          send.disabled = false;
          dstatus.textContent = dstatus.getAttribute(ok ? 'data-ok' : 'data-ko');
          dstatus.setAttribute('role', ok ? 'status' : 'alert');
          dstatus.hidden = false;
          if (ok) { dform.reset(); dform.hidden = true; }
        });
    });
  }
})();
