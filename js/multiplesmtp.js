CRM.$(function($) {

  // Ne rien faire si on n'est pas sur la page SMTP
  if (!$('#bySMTP').length) {
    return;
  }

  var $altBlock  = $('#multiplesmtp-block');
  var $bySMTP    = $('#bySMTP');
  var $hiddenVis = $('input[name="multiplesmtp_is_visible"]');

  // 1. Déplacer le bloc juste après #bySMTP
  $altBlock.insertAfter($bySMTP);

  // 2. Sync visibilité + mise à jour du hidden
  function syncVisibility() {
    var visible = $bySMTP.is(':visible');
    $hiddenVis.val(visible ? 1 : 0);

    if (visible) {
      $altBlock.slideDown(200);
    } else {
      $altBlock.hide();
    }
  }

  // 3. MutationObserver sur #bySMTP
  if (window.MutationObserver) {
    var observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(m) {
        if (m.attributeName === 'style') {
          syncVisibility();
        }
      });
    });
    observer.observe($bySMTP[0], {
      attributes: true,
      attributeFilter: ['style']
    });
  }

  // Double sécurité sur les radios
  $('input[name="outBound_option"]').on('change', function() {
    setTimeout(syncVisibility, 50);
  });

  // État initial au chargement
  syncVisibility();

});