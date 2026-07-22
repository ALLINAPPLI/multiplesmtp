{* ── Case à cocher maître — toujours visible ──────────────────────────── *}
<div id="multiplesmtp-enable-block" style="margin: 1.2em 0 0.5em;">
  <label style="font-weight:600; cursor:pointer;">
    {$form.multiplesmtp_enabled.html}
    &nbsp;{ts}Souhaitez-vous configurer un flux transactionnel ?{/ts}
  </label>
</div>

{* ── Bloc de configuration — affiché seulement si case cochée ────────── *}
<div id="multiplesmtp-block"
     style="display:none;"
     data-smtp-auth="{$smtpAltDefaults.multiplesmtp_smtp_auth|default:0}">
  <fieldset>
    <legend>{ts}Configuration SMTP Transactionnel{/ts}</legend>

    <p class="description" style="margin-bottom:1em;">
      {ts}Ce SMTP sera utilisé pour les mails individuels (confirmations, alertes, reçus). Les mailings en masse utilisent le SMTP principal ci-dessus.{/ts}
    </p>

    <table class="form-layout-compressed">
      <tbody>

        <tr class="crm-smtp-form-block-smtp-server">
          <td class="label">
            <label for="{$smtpAltPrefix}smtp_server">
              {ts}Serveur SMTP transactionnel{/ts}
            </label>
          </td>
          <td>
            {$form[$smtpAltPrefix|cat:'smtp_server'].html}
            <br>
            <span class="description">
              {ts}Entrez le nom du serveur SMTP, ex : "smtp.sendgrid.net". Pour SSL : "ssl://smtp.example.com".{/ts}
            </span>
          </td>
        </tr>

        <tr class="crm-smtp-form-block-smtp-port">
          <td class="label">
            <label for="{$smtpAltPrefix}smtp_port">
              {ts}Port SMTP transactionnel{/ts}
            </label>
          </td>
          <td>
            {$form[$smtpAltPrefix|cat:'smtp_port'].html}
            <br>
            <span class="description">
              {ts}Les ports courants sont 25, 465 et 587. Vérifiez avec votre fournisseur.{/ts}
            </span>
          </td>
        </tr>

        <tr class="crm-smtp-form-block-smtp_auth">
          <td class="label">
            <label>{ts}Authentification requise{/ts}</label>
          </td>
          <td>
            <span style="white-space:nowrap;">
              {$form[$smtpAltPrefix|cat:'smtp_auth'].html}
            </span>
            <br>
            <span class="description">
              {ts}Votre SMTP transactionnel requiert-il une authentification ?{/ts}
            </span>
          </td>
        </tr>

        <tr class="crm-smtp-form-block-smtp-username">
          <td class="label">
            <label for="{$smtpAltPrefix}smtp_username">
              {ts}Nom d'utilisateur SMTP{/ts}
            </label>
          </td>
          <td>
            {$form[$smtpAltPrefix|cat:'smtp_username'].html}
            <br>
            <span class="description">
              {ts}Nom d'utilisateur fourni par votre prestataire SMTP transactionnel.{/ts}
            </span>
          </td>
        </tr>

        <tr class="crm-smtp-form-block-smtp-password">
          <td class="label">
            <label for="{$smtpAltPrefix}smtp_password">
              {ts}Mot de passe SMTP{/ts}
            </label>
          </td>
          <td>
            {$form[$smtpAltPrefix|cat:'smtp_password'].html}
            <br>
            <span class="description">
              {ts}Laisser vide pour conserver le mot de passe existant.{/ts}
            </span>
          </td>
        </tr>

        {* ── Bouton test — dans l'encart, pas à côté du bouton principal ── *}
        <tr class="crm-smtp-form-block-test-btn">
          <td class="label"></td>
          <td style="padding-top:0.75em;">
            <a href="#"
               id="multiplesmtp_test_btn"
               class="crm-form-xbutton button"
               style="cursor:pointer;">
              <i role="img" aria-hidden="true" class="crm-i fa-envelope-o"></i>
              &nbsp;{ts}Enregistrer &amp; tester SMTP transactionnel{/ts}
            </a>
          </td>
        </tr>

      </tbody>
    </table>
  </fieldset>
</div>
