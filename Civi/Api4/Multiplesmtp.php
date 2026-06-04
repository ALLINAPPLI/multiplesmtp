<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * Entité API4 pour l'extension Multiple SMTP
 */
class Multiplesmtp extends AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Multiplesmtp\TestSmtp
   */
  public static function testSmtp(bool $checkPermissions = TRUE): Action\Multiplesmtp\TestSmtp {
    return (new Action\Multiplesmtp\TestSmtp(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getFields(bool $checkPermissions = TRUE): BasicGetFieldsAction {
    return (new BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return [
      'default'  => ['administer CiviCRM'],
      'testSmtp' => ['administer CiviCRM'],
    ];
  }
}