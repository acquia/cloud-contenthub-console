<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use AcquiaCloudApi\Connector\ClientInterface;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Response\BackupsResponse;
use AcquiaCloudApi\Response\OperationResponse;

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

  /**
   * Returns a BackupsResponse object.
   *
   * @param string $env_id
   *   The environment id.
   * @param string $db_name
   *   The name of the database to list the backups of.
   *
   * @return \AcquiaCloudApi\Response\BackupsResponse|null
   *   The response of the process.
   */
  protected function getBackupList(string $env_id, string $db_name): ?BackupsResponse {
    $db_backups = new DatabaseBackups($this->acquiaCloudClient);
    $all = $db_backups->getAll($env_id, $db_name);
    if ($all->count() < 1) {
      return NULL;
    }

    return $all;
  }

  /**
   * Initiate the backup.
   *
   * @param string $env_id
   *   The environment id.
   * @param string $db_name
   *   The name of the database to create backup of.
   *
   * @return \AcquiaCloudApi\Response\OperationResponse
   *   The response of the process.
   */
  protected function initiateBackup(string $env_id, string $db_name): OperationResponse {
    $db_backups = new DatabaseBackups($this->acquiaCloudClient);
    return $db_backups->create($env_id, $db_name);
  }

  /**
   * Make request against /environments/{environment_uuid}/databases endpoint.
   *
   * @param string $env_uuid
   *   Environment uuid.
   *
   * @return array
   *   Response of API call (DB info).
   */
  protected function getDatabasesByEnvironment(string $env_uuid) {
    return $this->acquiaCloudClient->request('get', "/environments/$env_uuid/databases");
  }

}
