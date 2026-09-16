<?php
use CRM_Multiplesmtp_ExtensionUtil as E;

class CRM_Multiplesmtp_Hook {

  const SETTING_PREFIX = 'multiplesmtp_';

  private static bool $internalSend = FALSE;
  // Dans Hook.php
  private static bool $mailerWasSwapped = FALSE;

  public static function postEmailSend($params) {
    if (self::$mailerWasSwapped) {
      \Civi::container()->set('pear_mail', \CRM_Utils_Mail::createMailer());
      self::$mailerWasSwapped = FALSE;
    }
  }
  
  private static $fields = [
    'enabled' => [
      'label' => 'Configurer un flux transactionnel',
      'type'  => 'checkbox',
    ],
    'smtp_server' => [
      'label'       => 'Serveur SMTP transactionnel',
      'type'        => 'text',
      'description' => 'Entrez le nom du serveur SMTP (machine), tel que "smtp.example.com". Si le serveur utilise SSL, ajoutez "ssl: //" au début du nom du serveur, tel que : "ssl://smtp.example.com".',
    ],
    'smtp_port' => [
      'label'       => 'Port SMTP transactionnel',
      'type'        => 'text',
      'description' => 'Les possibilités de port SMTP les plus courantes sont 25, 465 et 587. Vérifiez avec votre fournisseur de messagerie pour choisir le port approprié.',
    ],
    'smtp_auth' => [
      'label'       => 'Authentification requise',
      'type'        => 'radio',
      'description' => 'Votre SMTP requiert-il une authentification (nom + mot de passe) ?',
    ],
    'smtp_username' => [
      'label'       => 'Nom d\'utilisateur SMTP',
      'type'        => 'text',
      'description' => 'Nom d\'utilisateur fourni par votre prestataire SMTP transactionnel.',
    ],
    'smtp_password' => [
      'label'       => 'Mot de passe SMTP',
      'type'        => 'password',
      'description' => 'Si votre serveur SMTP transactionnel requiert une authentification, entrez votre nom et mot de passe ici.',
    ],
  ];

  // -------------------------------------------------------
  // 1. Injection des champs dans la page
  // -------------------------------------------------------
  public static function buildForm($formName, &$form) {
    if ($formName !== 'CRM_Admin_Form_Setting_Smtp') {
      return;
    }

    $isEnabled = (bool) Civi::settings()->get(self::SETTING_PREFIX . 'enabled');
    $fullKeyEnabled = self::SETTING_PREFIX . 'enabled';
    $form->addElement('checkbox', $fullKeyEnabled, '');
    $form->setDefaults([$fullKeyEnabled => (bool) Civi::settings()->get($fullKeyEnabled)]);

    $settings = Civi::settings();
    // Civi::log()->debug('buildForm settings: ' . print_r($settings, TRUE));
    
    foreach (self::$fields as $key => $info) {
      $fullKey      = self::SETTING_PREFIX . $key;
      $currentValue = $settings->get($fullKey);
      // Civi::log()->debug('buildForm fullKey: ' . print_r($fullKey, TRUE));
      // Civi::log()->debug('buildForm currentValue: ' . print_r($currentValue, TRUE));

      if ($info['type'] === 'radio') {
        $form->addYesNo($fullKey, $info['label'], empty($props[$fullKey]['disabled']), FALSE, $props[$fullKey] ?? []);
        if ($currentValue == 1 && $fullKey == "multiplesmtp_smtp_auth") {
          // $form->setDefaults([$fullKey => (int) $currentValue]);
          if ($isEnabled) {
            $form->setDefaults([$fullKey => (int) $currentValue]);
          } else {
            $form->setDefaults([$fullKey => 0]); // champ vide si désactivé
          }
        }
        $form->assign('smtpAltDefaults', [
          'multiplesmtp_smtp_auth' => (int) Civi::settings()->get('multiplesmtp_smtp_auth'),
        ]);
      }
      elseif ($info['type'] === 'checkbox') {
        $form->addElement('checkbox', $fullKey, $info['label']);
        // $form->setDefaults([$fullKey => (bool) $currentValue]);
        if ($isEnabled) {
          $form->setDefaults([$fullKey => (bool) $currentValue]);
        } else {
          $form->setDefaults([$fullKey => 0]); // champ vide si désactivé
        }
      }
      elseif ($info['type'] === 'password') {
        $form->addElement('password', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45, 'autocomplete' => 'off']
        );
        // Afficher un placeholder si un mot de passe existe déjà en DB
        if (!empty($currentValue)) {
          $form->getElement($fullKey)->updateAttributes(['placeholder' => '(mot de passe enregistré)']);
        }
      }
      else {
        $form->addElement('text', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        // $form->setDefaults([$fullKey => $currentValue]);
        if ($isEnabled) {
          $form->setDefaults([$fullKey => $currentValue]);
        } else {
          $form->setDefaults([$fullKey => '']); // champ vide si désactivé
        }
      }
    }

    $form->addElement('hidden', 'multiplesmtp_is_visible', 0);
    $form->setDefaults(['multiplesmtp_is_visible' => 0]);

    $form->assign('smtpAltFields', self::$fields);
    $form->assign('smtpAltPrefix', self::SETTING_PREFIX);

    if ($formName == 'CRM_Admin_Form_Setting_Smtp') {
      Civi::resources()->addScriptFile('multiplesmtp', 'js/multiplesmtp.js');
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Multiplesmtp/SmtpAltFields.tpl']);
    }
  }

  // -------------------------------------------------------
  // 2. Sauvegarde des champs
  // -------------------------------------------------------
  public static function postProcess($formName, &$form) {
    if ($formName !== 'CRM_Admin_Form_Setting_Smtp') {
      return;
    }

    $values    = $form->exportValues();
    $s         = Civi::settings();
    $prefix    = self::SETTING_PREFIX;

    // ── Vérifier si la case est cochée ──────────────────────────────────
    // La checkbox peut arriver comme '1', 1, ou être absente si décochée
    $isEnabled = !empty($values[$prefix . 'enabled']);

    // Case décochée → effacer TOUS les settings et sortir
    if (!$isEnabled) {
      foreach (array_keys(self::$fields) as $key) {
        $s->set($prefix . $key, NULL);
      }
      $s->set($prefix . 'enabled', NULL);
      return;
    }

    // Case cochée → sauvegarder (pas besoin de vérifier is_visible)
    // is_visible servait à détecter si le SMTP principal était affiché,
    // mais ce n'est pas nécessaire pour les settings alternatifs.
    foreach (self::$fields as $key => $info) {
      $fullKey = $prefix . $key;
      $value   = $values[$fullKey] ?? NULL;

      if ($key === 'enabled') {
        $s->set($fullKey, 1);
        continue;
      }

      if ($key === 'smtp_auth') {
        if ($value !== NULL) {
          $s->set($fullKey, (int) $value);
        }
        continue;
      }

      if ($key === 'smtp_password') {
        $newPlain = $values[$fullKey] ?? '';
        if (!empty($newPlain)) {
          $s->set($fullKey, self::encryptPassword($newPlain));
        }
        // Si vide → on garde la valeur DB existante
        continue;
      }

      if ($value !== NULL) {
        $s->set($fullKey, $value);
      }
    }

    if (!empty($values['multiplesmtp_test'])) {
      self::sendTestEmail();
    }
  }

  public static bool $useAltMailerForNextSend = FALSE;

  /**
   * Décision de routage (SMTP transactionnel ou non) calculée une fois
   * par job de mailing (test ou normal) via onFlexMailerRun(), et
   * consommée ensuite pour chaque destinataire de ce job.
   */
  private static bool $currentJobUseAltMailer = FALSE;

  /**
   * hook_civicrm_alterMailer : n'intervient qu'une fois par requête,
   * au moment où CiviCRM construit son mailer par défaut.
   *
   * On exclut explicitement le bouton natif « Save & Send Test Email »
   * de la page Administer > System Settings > Outbound Mail : ce flux
   * appelle _createMailer() (qui déclenche ce hook) puis envoie via
   * CRM_Utils_Mail::sendTest(), qui appelle $mailer->send() directement
   * SANS repasser par alterMailParams(). Si on enveloppait ce mailer dans
   * notre ProxyMailer, celui-ci consulterait un flag $useAltMailerForNextSend
   * périmé (laissé par un envoi précédent sans rapport), et pourrait donc
   * envoyer le test du SMTP principal via le SMTP alternatif (ou l'inverse).
   * Ce test doit rester strictement indépendant de notre logique de routage.
   */
  public static function alterMailer(&$mailer, $driver, $params) {
    if (self::isNativeSmtpTestCall()) {
      return;
    }

    if (!($mailer instanceof CRM_Multiplesmtp_ProxyMailer)) {
      $mailer = new CRM_Multiplesmtp_ProxyMailer($mailer);
    }
  }

  /**
   * Détecte si on est appelé depuis CRM_Admin_Form_Setting_Smtp::sendTest()
   * (bouton natif « Save & Send Test Email »), via la pile d'appels.
   */
  private static function isNativeSmtpTestCall(): bool {
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $frame) {
      if (($frame['class'] ?? NULL) === 'CRM_Admin_Form_Setting_Smtp'
        && ($frame['function'] ?? NULL) === 'sendTest') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Écouteur de l'événement FlexMailer "civi.flexmailer.run".
   * Se déclenche une fois par job traité (envoi normal OU envoi de test),
   * avant l'envoi effectif des messages du job.
   *
   * On y détermine, pour CE job, si le nombre de destinataires est
   * <= au seuil natif CiviCRM `simple_mail_limit` (Administer > System
   * Settings > Outbound Mail) : si oui, tous les envois de ce job
   * utiliseront le SMTP transactionnel ; sinon, le SMTP principal (bulk).
   */
  public static function onFlexMailerRun(\Civi\FlexMailer\Event\RunEvent $event): void {
    // Par défaut (sécurité) : on reste sur le SMTP principal.
    self::$currentJobUseAltMailer = FALSE;

    try {
      $job = $event->getJob();
      if (!$job || empty($job->id)) {
        return;
      }

      // Le SMTP transactionnel doit être configuré et activé.
      if (self::buildAlternativeMailer() === NULL) {
        return;
      }

      // Réglage natif CiviCRM (Administer > System Settings > Outbound Mail),
      // pas un setting de cette extension.
      $limit = (int) (Civi::settings()->get('simple_mail_limit') ?? 0);
      if ($limit <= 0) {
        // Pas de seuil configuré : comportement inchangé, tout va sur le SMTP principal.
        return;
      }

      // Nombre de destinataires réels de CE job (test ou normal).
      $recipientCount = (int) CRM_Core_DAO::singleValueQuery(
        'SELECT COUNT(*) FROM civicrm_mailing_event_queue WHERE job_id = %1',
        [1 => [$job->id, 'Integer']]
      );

      self::$currentJobUseAltMailer = ($recipientCount > 0 && $recipientCount <= $limit);
    }
    catch (\Throwable $e) {
      // On ne doit JAMAIS faire échouer l'envoi (ou le test) d'un mailing
      // à cause de cette logique de routage. En cas de souci, on journalise
      // et on se rabat silencieusement sur le SMTP principal.
      Civi::log()->error('multiplesmtp: onFlexMailerRun a échoué : ' . $e->getMessage());
      self::$currentJobUseAltMailer = FALSE;
    }
  }

  public static function alterMailParams(&$params, $context = NULL) {
    try {
      if (self::$internalSend) {
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      // Le SMTP transactionnel doit être configuré et activé.
      if (self::buildAlternativeMailer() === NULL) {
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      // Réglage natif CiviCRM (Administer > System Settings > Outbound Mail).
      $limit = (int) (Civi::settings()->get('simple_mail_limit') ?? 0);
      if ($limit <= 0) {
        // Pas de seuil configuré : tout part sur le SMTP principal.
        self::$useAltMailerForNextSend = FALSE;
        return;
      }

      // -----------------------------------------------------------
      // >>> DEB DÉTECTION DES EMAILS DONREC (REÇUS FISCAUX) <<<
      // -----------------------------------------------------------
      // DonRec ajoute un header spécifique :
      //   Nom  : X400-Content-Identifier 
      //            >> TODO ATTENTION Si SYSTOPIA le change (info se trouvant dans CRM_Donrec_Logic_EmailReturnProcessor et appeler dans CRM_Donrec_Exporters_EmailPDF)
      //   Valeur : DONREC#{contact_id}#{contribution_id}#{timestamp}#{profile_id}#
      //
      // Si ce header est présent et commence par "DONREC#", on considère
      // que c'est un envoi de reçu fiscal DonRec → on force le SMTP alternatif.
      // -----------------------------------------------------------
      $isDonRec = FALSE;

      if (!empty($params['headers']['X400-Content-Identifier'])) {
        $headerValue = $params['headers']['X400-Content-Identifier'];
        if (is_string($headerValue) && stripos($headerValue, 'DONREC#') === 0) {
          $isDonRec = TRUE;
        }
      }

      // Fallback : détection via le sujet (au cas où le header serait absent)
      if (!$isDonRec && !empty($params['subject'])) {
        $subject = strtolower($params['subject']);
        if (stripos($subject, 'zuwendungsbescheinigung') !== FALSE
          || stripos($subject, 'donation receipt') !== FALSE
        ) {
          $isDonRec = TRUE;
        }
      }

      // Si c'est un email DonRec, on force l'utilisation du SMTP alternatif.
      if ($isDonRec) {
        self::$useAltMailerForNextSend = TRUE;
        return;
      }
      // -----------------------------------------------------------
      // >>> END DÉTECTION DES EMAILS DONREC (REÇUS FISCAUX) <<<
      // -----------------------------------------------------------

      $isMailingContext = in_array($context, ['civimail', 'flexmailer', 'testEmail'], TRUE)
        || !empty($params['headers']['List-Unsubscribe']);

      if ($isMailingContext) {
        // Envoi via un mailing CiviMail (test ou normal) : la décision a été
        // prise en amont dans onFlexMailerRun(), en fonction du nombre total
        // de destinataires du job par rapport au seuil `simple_mail_limit`.
        self::$useAltMailerForNextSend = self::$currentJobUseAltMailer;
      }
      else {
        // Tout le reste (email transactionnel unitaire, tâche "Envoyer un
        // email" sur une petite sélection, etc.) : on compte les destinataires
        // de CE message précis et on applique la même règle de seuil.
        $recipientCount = self::countRecipients($params);
        self::$useAltMailerForNextSend = ($recipientCount > 0 && $recipientCount <= $limit);
      }
    }
    catch (\Throwable $e) {
      // Ne jamais faire échouer un envoi/test à cause de cette logique de
      // routage : on journalise et on se rabat sur le SMTP principal.
      Civi::log()->error('multiplesmtp: alterMailParams a échoué : ' . $e->getMessage());
      self::$useAltMailerForNextSend = FALSE;
    }
  }

  /**
   * Compte le nombre de destinataires (to + cc + bcc) d'un envoi unitaire,
   * à partir des paramètres passés à hook_civicrm_alterMailParams.
   */
  private static function countRecipients(array $params): int {
    $blob = '';
    foreach (['toEmail', 'to', 'cc', 'bcc'] as $key) {
      if (!empty($params[$key])) {
        $blob .= ' ' . $params[$key];
      }
    }
    preg_match_all('/[^\s,;<>"]+@[^\s,;<>"]+/', $blob, $matches);
    $count = count(array_unique(array_map('strtolower', $matches[0])));
    return max($count, 1);
  }


  // -------------------------------------------------------
  // Envoi du mail de test (depuis le formulaire)
  // -------------------------------------------------------
  private static function sendTestEmail() {
    $userEmail = CRM_Core_Session::singleton()->getLoggedInContactEmail();

    if (empty($userEmail)) {
      CRM_Core_Session::setStatus(
        ts('Impossible de trouver votre adresse email.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    $mailer = self::buildAlternativeMailer();

    if ($mailer === NULL) {
      CRM_Core_Session::setStatus(
        ts('Le SMTP transactionnel n\'est pas configuré.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    $siteName = Civi::settings()->get('site_name') ?: 'CiviCRM';
    $from     = Civi::settings()->get('fromEmailAddress') ?: 'no-reply@example.com';

    $headers = [
      'From'         => $from,
      'To'           => $userEmail,
      'Subject'      => ts('Test SMTP transactionnel - %1', [1 => $siteName]),
      'Content-Type' => 'text/html; charset=UTF-8',
      'Date'         => date('r'),
      'Message-ID'   => '<' . uniqid('multiplesmtp_') . '@' . php_uname('n') . '>',
    ];

    $body = "
      <html>
      <body>
        <p>" . ts('Bonjour,') . "</p>
        <p>" . ts('Ceci est un email de test envoyé via le <strong>SMTP transactionnel</strong> configuré dans votre extension Multiple SMTP.') . "</p>
        <p>" . ts('Si vous recevez cet email, la configuration est correcte.') . "</p>
        <hr>
        <p><small>
          " . ts('Serveur : %1', [1 => Civi::settings()->get('multiplesmtp_smtp_server')]) . "<br>
          " . ts('Port : %1', [1 => Civi::settings()->get('multiplesmtp_smtp_port')]) . "<br>
          " . ts('Envoyé le : %1', [1 => date('d/m/Y H:i:s')]) . "
        </small></p>
      </body>
      </html>
    ";

    self::$internalSend = TRUE;
    try {
      $result = $mailer->send($userEmail, $headers, $body);
    }
    finally {
      self::$internalSend = FALSE;
    }

    if ($result === TRUE || !is_a($result, 'PEAR_Error')) {
      CRM_Core_Session::setStatus(
        ts('Email de test envoyé avec succès à %1 via le SMTP transactionnel.', [1 => $userEmail]),
        ts('Succès'),
        'success'
      );
    }
    else {
      CRM_Core_Session::setStatus(
        ts('Échec de l\'envoi : %1', [1 => $result->getMessage()]),
        ts('Erreur SMTP transactionnel'),
        'error'
      );
    }
  }

  // -------------------------------------------------------
  // Helpers
  // -------------------------------------------------------
  public static function buildAlternativeMailerPublic() {
    return self::buildAlternativeMailer();
  }
  private static function buildAlternativeMailer() {
    $s = Civi::settings();

    // Vérifier que l'extension est activée
    if (!$s->get(self::SETTING_PREFIX . 'enabled')) {
      return NULL;
    }

    $server   = $s->get(self::SETTING_PREFIX . 'smtp_server');
    $port     = $s->get(self::SETTING_PREFIX . 'smtp_port') ?: 587;
    $auth     = (bool) $s->get(self::SETTING_PREFIX . 'smtp_auth');
    $username = $s->get(self::SETTING_PREFIX . 'smtp_username');
    $password = $s->get(self::SETTING_PREFIX . 'smtp_password');

    if (empty($server)) {
      return NULL;
    }

    if (!empty($password)) {
      $password = self::decryptPasswordPublic($password);
    }

    $params = [
      'host'     => $server,
      'port'     => (int) $port,
      'auth'     => $auth,
      'username' => $username,
      'password' => $password,
    ];

    require_once 'Mail.php';
    return Mail::factory('smtp', $params);
  }

  private static function encryptPassword(string $plain): string {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::encrypt($plain);
    }
    return base64_encode($plain);
  }

  public static function decryptPasswordPublic(string $encrypted): string {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::decrypt($encrypted);
    }
    return base64_decode($encrypted);
  }
}