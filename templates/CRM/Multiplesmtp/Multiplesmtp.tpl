<div class="crm-block crm-form-block" style="margin-top: 2em;">
  <h3>{ts}SMTP Transactionnel (multiplesmtp){/ts}</h3>
  <p class="description">
    {ts}Ce SMTP sera utilisé pour les mails individuels (confirmations, alertes).
    Les envois en masse (Mailings) continuent d'utiliser le SMTP principal.{/ts}
  </p>

  <table class="form-layout-compressed">
    {foreach from=$smtpAltFields key=fieldKey item=fieldInfo}
      <tr class="crm-admin-smtp-form-block-{$fieldKey}">
        <td class="label">
          <label for="{$smtpAltPrefix}{$fieldKey}">
            {ts}{$fieldInfo.label}{/ts}
          </label>
        </td>
        <td>
          {$form[$smtpAltPrefix|cat:$fieldKey].html}
        </td>
      </tr>
    {/foreach}
  </table>
</div>