<?php

/**
 * Mailer "façade" qui route chaque envoi vers le SMTP principal
 * ou le SMTP alternatif, selon un flag positionné par alterMailParams
 * juste avant l'appel à $mailer->send().
 */
class CRM_Multiplesmtp_ProxyMailer {

  private $defaultMailer;

  public function __construct($defaultMailer) {
    $this->defaultMailer = $defaultMailer;
  }

  public function send($recipients, $headers, $body, $originalValues = []) {
    $target = $this->defaultMailer;

    if (CRM_Multiplesmtp_Hook::$useAltMailerForNextSend) {
      $alt = CRM_Multiplesmtp_Hook::buildAlternativeMailerPublic();
      if ($alt !== NULL) {
        $target = $alt;
      }
    }

    // On réinitialise systématiquement après consommation, par sécurité.
    CRM_Multiplesmtp_Hook::$useAltMailerForNextSend = FALSE;
    $retour =  $target->send($recipients, $headers, $body, $originalValues);
    return $retour;
  }

  // Délègue toute méthode non définie ici (getDriver(), disconnect(), etc.)
  public function __call($name, $arguments) {
    return call_user_func_array([$this->defaultMailer, $name], $arguments);
  }

  public function getDelegate() {
    return $this->defaultMailer;
  }
}