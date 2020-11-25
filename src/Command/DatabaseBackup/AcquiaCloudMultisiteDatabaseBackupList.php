<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcquiaCloudMultisiteDatabaseBackupList.
 *
 * @package Acquia\Console\Cloud\Command\DatabaseBackup
 */
class AcquiaCloudMultisiteDatabaseBackupList extends AcquiaCloudMultisiteDatabaseBackupBase {

  use AcquiaCloudDatabaseBackupHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace-multi:database:backup:list';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List database backups for multisite environment.');
    $this->addOption('all', 'a', InputOption::VALUE_NONE, 'List backups for all databases in the multisite platform.');
    $this->setAliases(['ace-dblm']);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRunCommand(string $env_id, array $databases, InputInterface $input, OutputInterface $output): int {
    $list = [];
    foreach($databases as $db_name) {
      $list[$db_name] = $this->getBackupList($env_id, $db_name);
    }
    if (empty($list) && !isset($list)) {
      $output->writeln('No backups found for any database.');
      return 1;
    }

    $table = new Table($output);
    $table->setHeaders(['DB Name', 'Completed', 'ID', 'Type']);
    foreach ($list as $db_name => $backups) {
      foreach($backups as $backup) {
        $table->addRow([$db_name, $backup->completedAt, $backup->id, $backup->type]);
      }
    }
    $table->render();

    return 0;
  }

}
