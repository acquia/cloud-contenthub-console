<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use AcquiaCloudApi\Connector\ClientInterface;
use AcquiaCloudApi\Endpoints\DatabaseBackups;

/**
 * Trait AcquiaCloudDatabaseBackupHelperTrait.
 *
 * @package Acquia\Console\Cloud\Command\DatabaseBackup
 */
trait AcquiaCloudDatabaseBackupHelperTrait {

  /**
   * Returns a list of database backups.
   *
   * @param string $env_id
   *   The environment id.
   * @param string $db_name
   *   The name of the database to list the backups of.
   * @param \AcquiaCloudApi\Connector\ClientInterface $client
   *   The ace client object.
   *
   * @return array
   *   List of backups.
   */
  protected function listBackups(string $env_id, string $db_name, ClientInterface $client): array {
    $db_backups = new DatabaseBackups($client);
    $all = $db_backups->getAll($env_id, $db_name);
    if (!$all) {
      return [];
    }

    $backups = [];
    /** @var \AcquiaCloudApi\Response\BackupResponse $backup */
    foreach ($all as $backup) {
      $backups[$backup->startedAt] = $backup->id;
    }

    return $backups;
  }

}
