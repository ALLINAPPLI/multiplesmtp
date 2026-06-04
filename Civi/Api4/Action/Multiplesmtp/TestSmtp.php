<?php

namespace Civi\Api4\Action\Multiplesmtp;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

class TestSmtp extends AbstractAction {

  protected ?string $server   = NULL;
  protected ?int    $port     = NULL;
  protected ?bool   $auth     = NULL;
  protected ?string $username = NULL;
  protected ?string $password = NULL;
  protected string  $to       = '';

  public function _run(Result $result): void {

    // Priorité aux valeurs passées, sinon on prend celles en base
    $server   = $this->server   ?? \Civi::settings()->get('multiplesmtp_smtp_server');
    $port     = $this->port     ?? \Civi::settings()->get('multiplesmtp_smtp_port') ?? 587;
    $auth     = $this->auth     ?? (bool) \Civi::settings()->get('multiplesmtp_smtp_auth');
    $username = $this->username ?? \Civi::settings()->get('multiplesmtp_smtp_username');

    // Toujours utiliser le mot de passe sauvegardé en base (déchiffré)
    $encrypted = \Civi::settings()->get('multiplesmtp_smtp_password');
    $password  = !empty($encrypted)
      ? \CRM_Multiplesmtp_Hook::decryptPasswordPublic($encrypted)
      : '';

    if (empty($server)) {
      throw new \CRM_Core_Exception(
        \ts('Le serveur SMTP transactionnel n\'est pas configuré.')
      );
    }

    // Construire le mailer SMTP
    require_once 'Mail.php';
    $mailer = \Mail::factory('smtp', [
      'host'     => $server,
      'port'     => (int) $port,
      'auth'     => $auth,
      'username' => $username,
      'password' => $password,
    ]);

    // Déterminer l'email destinataire
    $userEmail = $this->to;

    if (empty($userEmail)) {
      $session   = \CRM_Core_Session::singleton();
      $contactId = $session->getLoggedInContactID();

      if (!empty($contactId)) {
        $emails = \Civi\Api4\Email::get(FALSE)
          ->addWhere('contact_id', '=', $contactId)
          ->addWhere('is_primary', '=', TRUE)
          ->addSelect('email')
          ->execute();

        $userEmail = $emails->first()['email'] ?? NULL;
      }
    }

    if (empty($userEmail)) {
      throw new \CRM_Core_Exception(
        \ts('Impossible de trouver l\'adresse email destinataire.')
      );
    }

    $from = \Civi::settings()->get('fromEmailAddress')
      ?: ('no-reply@' . php_uname('n'));

    $headers = [
      'From'         => $from,
      'To'           => $userEmail,
      'Subject'      => \ts('Test SMTP transactionnel - CiviCRM'),
      'Content-Type' => 'text/html; charset=UTF-8',
      'Date'         => date('r'),
      'Message-ID'   => '<' . uniqid('multiplesmtp_') . '@' . php_uname('n') . '>',
    ];

    $body = '
      <p>' . \ts('Bonjour,') . '</p>
      <p>' . \ts('Ceci est un test du <strong>SMTP transactionnel</strong>.') . '</p>
      <p>' . \ts('Si vous recevez cet email, la configuration est correcte.') . '</p>
      <hr>
      <small>
        ' . \ts('Serveur : %1', [1 => $server]) . '<br>
        ' . \ts('Port : %1',    [1 => $port])   . '<br>
        ' . \ts('Envoyé le : %1', [1 => date('d/m/Y H:i:s')]) . '
      </small>
    ';

    $send = $mailer->send($userEmail, $headers, $body);

    if ($send === TRUE || !is_a($send, 'PEAR_Error')) {
      $result[] = [
        'success' => TRUE,
        'message' => \ts('Email de test envoyé avec succès à %1.', [1 => $userEmail]),
        'to'      => $userEmail,
        'server'  => $server,
        'port'    => $port,
      ];
    }
    else {
      throw new \CRM_Core_Exception(
        \ts('Échec de l\'envoi : %1', [1 => $send->getMessage()])
      );
    }
  }

}