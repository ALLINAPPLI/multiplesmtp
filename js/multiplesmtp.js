CRM.$(function($) {
// -- ICI
  if (!$('#bySMTP').length) {
    return;
  }

  var $altBlock  = $('#multiplesmtp-block');
  var $bySMTP    = $('#bySMTP');
  var $hiddenVis = $('input[name="multiplesmtp_is_visible"]');
  var authName   = 'multiplesmtp_smtp_auth';

  // 1. Déplacer le bloc juste après #bySMTP
  $altBlock.insertAfter($bySMTP);

  // 2. Injecter le bouton de test alternatif à côté du bouton existant
  var $boutonExistant = $('#_qf_Smtp_refresh_test');

  if ($boutonExistant.length) {
    var $nouveauBouton = $(
      '<a href="#" id="multiplesmtp_test_btn" class="crm-form-xbutton button" style="margin-left:8px; cursor:pointer;">' +
      '<i role="img" aria-hidden="true" class="crm-i fa-envelope-o"></i> ' +
      'Enregistrer &amp; tester SMTP transactionnel' +
      '</a>'
    );

    $nouveauBouton.insertAfter($boutonExistant);

    $nouveauBouton.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      console.log('clic détecté sur bouton test alternatif');

      var $btn = $(this);
      $btn.prop('disabled', true)
          .find('i')
          .removeClass('fa-envelope-o')
          .addClass('fa-spinner fa-spin');

      var formData = {
        server   : $('input[name="multiplesmtp_smtp_server"]').val(),
        port     : parseInt($('input[name="multiplesmtp_smtp_port"]').val()),
        auth     : $('input[name="multiplesmtp_smtp_auth"]:checked').val() === '1',
        username : $('input[name="multiplesmtp_smtp_username"]').val(),
        password : '', //  Ne pas envoyer le password — l'API utilisera celui sauvegardé en base
      };

      console.log('formData :', formData);

      CRM.api4('Multiplesmtp', 'testSmtp', formData)
        .then(function(results) {
          console.log('API4 success :', results);
          var result = results[0];
          CRM.alert(result.message, ts('Succès'), 'success');
        })
        .catch(function(error) {
          console.log('API4 error complet :', JSON.stringify(error));
          var message = (error && error.error_message)
            ? error.error_message
            : ts('Erreur inconnue - voir console');
          CRM.alert(message, ts('Erreur SMTP transactionnel'), 'error');
        })
        .finally(function() {
          $btn.prop('disabled', false)
              .find('i')
              .removeClass('fa-spinner fa-spin')
              .addClass('fa-envelope-o');
        });

      return false; // Double sécurité
    });
    // var $nouveauBouton = $(
    //   '<button type="button" id="multiplesmtp_test_btn" class="crm-form-xbutton" style="margin-left:8px;">' +
    //   '<i role="img" aria-hidden="true" class="crm-i fa-envelope-o"></i> ' +
    //   'Enregistrer &amp; tester SMTP transactionnel' +
    //   '</button>'
    // );

    // $nouveauBouton.insertAfter($boutonExistant);

  //   // Au clic : appel API4
  //   $nouveauBouton.on('click', function(e) {
  //     e.preventDefault();

  //     var $btn = $(this);
  //     $btn.prop('disabled', true)
  //         .find('i')
  //         .removeClass('fa-envelope-o')
  //         .addClass('fa-spinner fa-spin');

  //     // Récupérer les valeurs du formulaire
  //     var formData = {
  //       server   : $('input[name="multiplesmtp_smtp_server"]').val(),
  //       port     : parseInt($('input[name="multiplesmtp_smtp_port"]').val()),
  //       auth     : $('input[name="multiplesmtp_smtp_auth"]:checked').val() === '1',
  //       username : $('input[name="multiplesmtp_smtp_username"]').val(),
  //       password : $('input[name="multiplesmtp_smtp_password"]').val(),
  //     };

  //     // Appel API4
  //     CRM.api4('Multiplesmtp', 'testSmtp', formData)
  //       .then(function(results) {
  //         var result = results[0];
  //         CRM.alert(result.message, ts('Succès'), 'success');
  //       })
  //       .catch(function(error) {
  //         CRM.alert(
  //           error.error_message || ts('Erreur inconnue'),
  //           ts('Erreur SMTP transactionnel'),
  //           'error'
  //         );
  //       })
  //       .finally(function() {
  //         $btn.prop('disabled', false)
  //             .find('i')
  //             .removeClass('fa-spinner fa-spin')
  //             .addClass('fa-envelope-o');
  //       });
  //   });
  }

  console.log('bouton existant trouvé :', $boutonExistant.length);
  console.log('nouveau bouton inséré :', $('#multiplesmtp_test_btn').length);

  // 3. Sync visibilité
  function syncVisibility() {
    var visible = $bySMTP.is(':visible');
    $hiddenVis.val(visible ? 1 : 0);

    if (visible) {
      $altBlock.slideDown(200);
      toggleAuthFields();
    } else {
      $altBlock.hide();
    }
  }

  // 4. Masquer/afficher username + password selon auth
  function toggleAuthFields() {
    var authRequired = $('input[name="' + authName + '"]:checked').val() == '1';
    var $authRows    = $altBlock.find(
      '.crm-smtp-form-block-smtp-username, .crm-smtp-form-block-smtp-password'
    );
    if (authRequired) {
      $authRows.slideDown(150);
    } else {
      $authRows.slideUp(150);
    }
  }

  // 5. MutationObserver sur #bySMTP
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

  // 6. Écouter les radios
  $('input[name="outBound_option"]').on('change', function() {
    setTimeout(syncVisibility, 50);
  });

  $('input[name="' + authName + '"]').on('change', toggleAuthFields);

  // 7. État initial
  syncVisibility();

});