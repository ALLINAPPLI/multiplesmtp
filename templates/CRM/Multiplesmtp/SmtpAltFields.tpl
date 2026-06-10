<div id="multiplesmtp-block" style="display:none;" data-smtp-auth="{$smtpAltDefaults.multiplesmtp_smtp_auth|default:0}">
  <fieldset>
    <legend>{ts}Configuration SMTP Transactionnel{/ts}</legend>
    <p class="description" style="margin-bottom:1em;">
      {ts}Ce SMTP sera utilisé pour les mails individuels (confirmations, alertes, reçus).
      Les mailings en masse utilisent le SMTP principal ci-dessus.{/ts}
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

      </tbody>
    </table>

  </fieldset>
</div>