<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Response\BackupsResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcquiaCloudDatabaseBackupList.
 *
 * @package Acquia\Console\Cloud\Command\DatabaseBackup
 */
class AcquiaCloudDatabaseBackupList extends AcquiaCloudDatabaseBackupBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:database:backup:list';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List database backups.');
    $this->setAliases(['ace-dbl']);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRunCommand(string $env_id, string $db, InputInterface $input, OutputInterface $output): int {
    $list = $this->listBackups($env_id, $db);
    if (!$list) {
      $output->writeln('No backups found.');
      return 1;
    }

    $table = new Table($output);
    $table->setHeaders(['Completed', 'ID', 'Type']);
    /** @var \AcquiaCloudApi\Response\BackupResponse $backup */
    foreach ($list as $backup) {
      $table->addRow([$backup->completedAt, $backup->id, $backup->type]);
    }
    $table->render();

    return 0;
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
  protected function listBackups(string $env_id, string $db_name): ?BackupsResponse {
    $db_backups = new DatabaseBackups($this->acquiaCloudClient);
    $all = $db_backups->getAll($env_id, $db_name);
    if ($all->count() < 1) {
      return NULL;
    }

    return $all;
  }

}
