<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use Acquia\Console\Cloud\Command\AceNotificationHandlerTrait;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Response\OperationResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class AcquiaCloudDatabaseBackupCreate.
 *
 * @package Acquia\Console\Cloud\Command\DatabaseBackup
 */
class AcquiaCloudDatabaseBackupCreate extends AcquiaCloudDatabaseBackupBase {

  use AceNotificationHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:database:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Create database backups.');
    $this->addOption('wait', 'w', InputOption::VALUE_NONE, 'Wait for task until it is completed.');
    $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Perform backups for all sites in the platform.');
    $this->setAliases(['ace-dbcr']);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRunCommand(string $env_id, string $db, InputInterface $input, OutputInterface $output): int {
    if ($input->hasOption('all') && $input->getOption('all')) {
      $output->writeln(sprintf('Initiating backup for database = "%s"...', $db));
    }
    else {
      $helper = $this->getHelper('question');
      $confirm = new ConfirmationQuestion(sprintf('Database backup initiation for db = "%s". Would you like to proceed? (Y/N): ', $db));
      $yes = $helper->ask($input, $output, $confirm);
      if (!$yes) {
        $output->writeln('Backup process terminated by user.');
        return 1;
      }
    }

    $resp = $this->initiateBackup($env_id, $db);
    $output->writeln("<info>{$resp->message}</info>");
    $this->waitInteractive($input, $output, $resp->links->notification->href, $this->acquiaCloudClient);

    return 0;
  }

  /**
   * Initiate the backup.
   *
   * @param string $env_id
   *   The environment id.
   * @param string $db_name
   *   The name of the database to crate backup of.
   *
   * @return \AcquiaCloudApi\Response\OperationResponse
   *   The response of the process.
   */
  protected function initiateBackup(string $env_id, string $db_name): OperationResponse {
    $db_backups = new DatabaseBackups($this->acquiaCloudClient);
    return $db_backups->create($env_id, $db_name);
  }

}
