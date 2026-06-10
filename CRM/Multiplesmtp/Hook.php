<?php
use CRM_Multiplesmtp_ExtensionUtil as E;
// -- ICI
class CRM_Multiplesmtp_Hook {

  const SETTING_PREFIX = 'multiplesmtp_';

  // Flag statique pour éviter que alterMailParams se déclenche
  // pendant un envoi test initié par postProcess
  private static bool $internalSend = FALSE;
  
  private static $fields = [
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
    // Civi::log()->debug(" --- CRM_Multiplesmtp_Hook --- ");
    
    $settings = Civi::settings();
    
    foreach (self::$fields as $key => $info) {
      $fullKey      = self::SETTING_PREFIX . $key;
      $currentValue = $settings->get($fullKey);

      // Civi::log()->debug(" - key : ".print_r($key,1));
      // Civi::log()->debug(" - fullKey : ".print_r($fullKey,1));
      // Civi::log()->debug(" - currentValue : ".print_r($currentValue,1));
      // Civi::log()->debug(" - info : ".print_r($info,1));


      if ($info['type'] === 'radio') {
        // Boutons radio Oui / Non
        $form->addYesNo($fullKey,  $info['label'], empty($props[$fullKey]['disabled']), FALSE, $props[$fullKey] ?? []);
        if($currentValue == 1 && $fullKey == "multiplesmtp_smtp_auth") {
          // Civi::log()->debug(" - multiplesmtp_smtp_auth : 1");
          $form->setDefaults([$fullKey => (int) $currentValue]);
        }
        // stocker la valeur pour qu'elle soit accessible en js.
        $form->assign('smtpAltDefaults', [
            'multiplesmtp_smtp_auth' => (int) Civi::settings()->get('multiplesmtp_smtp_auth'),
        ]);
      }
      elseif ($info['type'] === 'checkbox') {
        $form->addElement('checkbox', $fullKey, $info['label']);
        $form->setDefaults([$fullKey => (bool) $currentValue]);
      }
      elseif ($info['type'] === 'password') {
        $form->addElement('password', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45, 'autocomplete' => 'off']
        );
        // ⚠️  NE PAS faire setDefaults ici — le champ reste vide.
        // Si l'utilisateur ne saisit rien → on garde la valeur DB.
      }
      else {
        $form->addElement('text', $fullKey, $info['label'],
          ['class' => 'crm-form-text', 'size' => 45]
        );
        $form->setDefaults([$fullKey => $currentValue]);
      }
    }

    // Champ hidden visibilité
    $form->addElement('hidden', 'multiplesmtp_is_visible', 0);
    $form->setDefaults(['multiplesmtp_is_visible' => 0]);

    $form->assign('smtpAltFields', self::$fields);
    $form->assign('smtpAltPrefix', self::SETTING_PREFIX);
    

    if($formName == 'CRM_Admin_Form_Setting_Smtp') {
        Civi::resources()->addScriptFile('multiplesmtp', 'js/multiplesmtp.js');
        CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Multiplesmtp/SmtpAltFields.tpl',]);
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
    $isVisible = !empty($values['multiplesmtp_is_visible']);

    // Si le bloc SMTP n'était pas visible, on ne sauvegarde rien
    if (!$isVisible) {
      return;
    }

    $s      = Civi::settings();
    $prefix = self::SETTING_PREFIX;

    // Sauvegarder les settings
    foreach (self::$fields as $key => $info) {
      $fullKey = $prefix . $key;
      $value = $values[$fullKey] ?? NULL;
      
      // Civi::log()->debug(" - save fullKey : ".print_r($fullKey,1));
      // Civi::log()->debug(" - save value : ".print_r($value,1));

      if ($key === 'smtp_auth') {       
        if ($value !== NULL) {
          $s->set($fullKey, (int) ($value ?? 0));
        }      
        continue;
      }

      if ($key === 'smtp_password') {
        $newPlain = $values[$fullKey] ?? '';

        if (!empty($newPlain)) {
          // L'utilisateur a saisi un nouveau mot de passe → on chiffre la valeur BRUTE
          $s->set($fullKey, self::encryptPassword($newPlain));
        }
        // Si vide → on ne touche pas la valeur déjà en base (déjà chiffrée)
        continue;
      }

      $value = $values[$fullKey] ?? NULL;
      if ($value !== NULL) {
        $s->set($fullKey, $value);
      }
    }

    // Détecter si c'est le bouton de test alternatif qui a été cliqué
    // Civi::log()->debug(" multiplesmtp_test BUTOON CLIQUE : ".print_r($values['multiplesmtp_test'],1));
    if (!empty($values['multiplesmtp_test'])) {
      self::sendTestEmail();
    }
  }

  // -------------------------------------------------------
  // 3. Interception et routage du SMTP
  // -------------------------------------------------------
  public static function alterMailParams(&$params, $context = NULL) {
    // Ne pas interférer pendant nos propres envois internes
    if (self::$internalSend) {
      return;
    }

    $isBulk = self::isBulkMailing($params, $context);
    // Civi::log()->debug(" alterMailParams isBulk : ".print_r($isBulk,1));

    if ($isBulk) {
      // SMTP principal — comportement par défaut, on ne touche à rien
      return;
    }

    // Mail transactionnel → SMTP alternatif
    $altMailer = self::buildAlternativeMailer();
    // Civi::log()->debug(" alterMailParams altMailer : ".print_r($altMailer,1));

    if ($altMailer !== NULL) {
      $params['mailer'] = $altMailer;
    }
  }

  // -------------------------------------------------------
  // Envoi du mail de test (depuis le formulaire)
  // -------------------------------------------------------
  private static function sendTestEmail() {
    // Récupérer l'email de l'administrateur connecté
    $userEmail = CRM_Core_Session::singleton()->getLoggedInContactEmail();

    if (empty($userEmail)) {
      CRM_Core_Session::setStatus(
        ts('Impossible de trouver votre adresse email.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    // Construire le mailer alternatif
    $mailer = self::buildAlternativeMailer();

    // Civi::log()->debug(" MAILER : ".print_r($mailer,1));

    if ($mailer === NULL) {
      CRM_Core_Session::setStatus(
        ts('Le SMTP transactionnel n\'est pas configuré.'),
        ts('Erreur'),
        'error'
      );
      return;
    }

    // Construire l'email de test
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
          " . ts('Port : %1',    [1 => Civi::settings()->get('multiplesmtp_smtp_port')]) . "<br>
          " . ts('Envoyé le : %1', [1 => date('d/m/Y H:i:s')]) . "
        </small></p>
      </body>
      </html>
    ";
    self::$internalSend = TRUE;
    // Envoyer via le mailer alternatif directement
    // Civi::log()->debug(" MAILER avant send : ".print_r($mailer,1));
    $result = $mailer->send($userEmail, $headers, $body);
    self::$internalSend = FALSE;

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
  // Helpers privés / publics
  // -------------------------------------------------------

  private static function isBulkMailing(&$params, $context) {
     // Contexte explicite (CiviCRM 5.x passe ce paramètre)
    if ($context === 'civimail') {
      Civi::log()->debug(" isBulkMailing : civimail");
      return TRUE;
    }

    // Mail de test du SMTP principal (bouton "Tester" natif CiviCRM)
    // → laisser le core gérer, ne pas router vers le SMTP alternatif
    if (!empty($params['groupName']) && stripos($params['groupName'], 'SMTP') !== FALSE) {
      Civi::log()->debug(" isBulkMailing : SMTP test principal");
      return TRUE;
    }

    // Vérifier le groupName dans les params
    if (!empty($params['groupName']) && stripos($params['groupName'], 'Mailing') !== FALSE) {
      Civi::log()->debug(" isBulkMailing : groupName Mailing");
      return TRUE;
    }

    // // Vérifier le header X-CiviMail-Bounce s'il est présent
    // if (!empty($params['headers']['X-CiviMail-Bounce'])) {
    //   Civi::log()->debug(" isBulkMailing : headers X-CiviMail-Bounce : ".print_r($params['headers']['X-CiviMail-Bounce'],1));
    //   return TRUE;
    // }

    // 🆕 Mosaico : il pose son propre header sur les previews et envois test
    if (!empty($params['headers']['X-Mosaico-Tracking'])) {
      Civi::log()->debug(" isBulkMailing : headers X-Mosaico-Tracking : ".print_r($params['headers']['X-Mosaico-Tracking'],1));
        return TRUE;
    }

    // 🆕 Mosaico : les envois test depuis l'éditeur ont ce groupName spécifique
    if (!empty($params['groupName']) && stripos($params['groupName'], 'mosaico') !== FALSE) {
        Civi::log()->debug(" isBulkMailing : groupName mosaico");
        return TRUE;
    }

    // 🆕 Mosaico stocke l'ID du template dans les params lors des previews
    if (!empty($params['templateId']) || !empty($params['mosaicoTemplateId'])) {
        Civi::log()->debug(" isBulkMailing : templateId mosaicoTemplateId");
        return TRUE;
    }

    // 🆕 Vérifier si le body contient la signature Mosaico
    // (envois test depuis l'éditeur visuel, avant job de mailing)
    if (!empty($params['html']) && strpos($params['html'], 'data-mosaico') !== FALSE) {
        Civi::log()->debug(" isBulkMailing : signature Mosaico");
        return TRUE;
    }

    // 🆕 Emails de test/preview de mailing (Mosaico et standard)
    // CiviCRM utilise ce groupName pour les envois test
    if (!empty($params['groupName']) &&
        stripos($params['groupName'], 'test') !== FALSE &&
        stripos($params['groupName'], 'Mailing') !== FALSE) {
          Civi::log()->debug(" isBulkMailing : groupName test ou Mailing");
        return TRUE;
    }

    return FALSE;
  }

  private static function buildAlternativeMailer() {
    $s = Civi::settings();

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

  /**
   * Public pour permettre à TestSmtp (Api4) de l'appeler.
   */
  public static function decryptPasswordPublic(string $encrypted): string {
    if (class_exists('CRM_Utils_Crypt')) {
      return CRM_Utils_Crypt::decrypt($encrypted);
    }
    return base64_decode($encrypted);
  }
}

