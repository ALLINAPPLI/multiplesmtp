CRM.$(function($) {

  // Sortir immédiatement si le bloc n'existe pas sur cette page
  if (!$('#multiplesmtp-block').length) {
    return;
  }

  var $altBlock  = $('#multiplesmtp-block');
  var $bySMTP    = $('#bySMTP');
  var $checkboxAll = $('input[name="multiplesmtp_enabled"]');
  var $hiddenVis = $('input[name="multiplesmtp_is_visible"]');
  var authName   = 'multiplesmtp_smtp_auth';

  // ── 1. Déplacer les blocs AVANT les boutons submit ─────────────────────
  var $enableBlock = $('#multiplesmtp-enable-block');
  var $submitBtns  = $('form[name="Smtp"] .crm-submit-buttons').first();

  if ($submitBtns.length) {
    $enableBlock.insertBefore($submitBtns);
    $altBlock.insertBefore($submitBtns);
  } else {
    $enableBlock.insertAfter($bySMTP);
    $altBlock.insertAfter($enableBlock);
  }

  // ── 2. Pré-sélectionner les radios YesNo depuis le data-attribute ──────
  var authVal = $altBlock.data('smtp-auth');
  if (authVal !== undefined && authVal !== '') {
    $('input[name="' + authName + '"][value="' + parseInt(authVal) + '"]')
      .prop('checked', true);
  }

  // ── 3. Afficher/masquer username + password selon le radio auth ─────────
  function toggleAuthFields() {
    var authRequired = $('input[name="' + authName + '"]:checked').val() == '1';
    var $authRows = $altBlock.find(
      '.crm-smtp-form-block-smtp-username, .crm-smtp-form-block-smtp-password'
    );
    if (authRequired) {
      $authRows.slideDown(150);
    } else {
      $authRows.slideUp(150);
    }
  }

  // ── 4. Visibilité de l'encart SMTP alternatif (pilotée par la checkbox) ─
  function syncEnabledBlock(animate) {
    var enabled = $checkboxAll.filter(':checked').length > 0;
    // Synchroniser toutes les checkboxes au même état
    $checkboxAll.prop('checked', enabled);
    if (animate === false) {
      if (enabled) {
        $altBlock.show();
        syncSmtpVisibility();
      } else {
        $altBlock.hide();
      }
    } else {
      if (enabled) {
        $altBlock.slideDown(200, function() { syncSmtpVisibility(); });
      } else {
        $altBlock.slideUp(200);
      }
    }
  }

  // ── 5. Sync visibilité interne (SMTP principal visible ou non) ──────────
  function syncSmtpVisibility() {
    if (!$checkboxAll.filter(':checked').length) {
      return;
    }
    var smtpVisible = $bySMTP.length ? $bySMTP.is(':visible') : true;
    $hiddenVis.val(smtpVisible ? 1 : 0);
    toggleAuthFields();
  }

  // ── 6. Bouton de test — dans l'encart ──────────────────────────────────
  $('#multiplesmtp_test_btn').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    var $btn = $(this);
    $btn.prop('disabled', true)
        .find('i')
        .removeClass('fa-envelope-o')
        .addClass('fa-spinner fa-spin');

    // 1. Soumettre le formulaire via AJAX
    var $form = $('form[name="Smtp"]');
    var formData = $form.serialize();

    $.ajax({
      type: 'POST',
      url: $form.attr('action'),
      data: formData,
      success: function() {
        // 2. Une fois enregistré, lancer le test
        CRM.api4('Multiplesmtp', 'testSmtp', {}).then(function(results) {
          var result = results[0];
          CRM.alert(result.message, ts('Succès'), 'success');
        }).catch(function(error) {
          var message = (error && error.error_message)
            ? error.error_message
            : ts('Erreur inconnue — voir la console');
          CRM.alert(message, ts('Erreur SMTP transactionnel'), 'error');
        }).finally(function() {
          $btn.prop('disabled', false)
              .find('i')
              .removeClass('fa-spinner fa-spin')
              .addClass('fa-envelope-o');
        });
      },
      error: function() {
        CRM.alert(ts('Échec de la sauvegarde'), ts('Erreur'), 'error');
        $btn.prop('disabled', false)
            .find('i')
            .removeClass('fa-spinner fa-spin')
            .addClass('fa-envelope-o');
      }
    });

    return false;
  });

  // ── 7. MutationObserver sur #bySMTP (changement de style) ──────────────
  if (window.MutationObserver && $bySMTP.length) {
    var observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(m) {
        if (m.attributeName === 'style') {
          syncSmtpVisibility();
        }
      });
    });
    observer.observe($bySMTP[0], { attributes: true, attributeFilter: ['style'] });
  }

  // ── 8. Écouteurs d'événements ───────────────────────────────────────────
  $checkboxAll.on('change', function() {
    var checked = $(this).is(':checked');
    $checkboxAll.prop('checked', checked);
    syncEnabledBlock();
  });

  $('input[name="outBound_option"]').on('change', function() {
    setTimeout(syncSmtpVisibility, 50);
  });

  $('input[name="' + authName + '"]').on('change', toggleAuthFields);

  // ── 9. État initial ─────────────────────────────────────────────────────
  syncEnabledBlock(false);

  // Masquer les blocs hors <form>
  document.querySelectorAll('#multiplesmtp-enable-block').forEach(el => {
    if (!el.closest('form')) el.style.display = 'none';
  });

  // Style commun
  const style = {
    backgroundColor: '#e8f0f7',
    border: '1px solid #c8d8e8',
    borderRadius: '4px',
    padding: '0.6em 1em',
  };

  ['#multiplesmtp-enable-block', '#multiplesmtp-block'].forEach(selector => {
    const el = document.querySelector(selector);
    if (el) Object.assign(el.style, style);
  });
});