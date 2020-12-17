<?php

namespace Acquia\Console\Cloud\Command\Backups;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupList;
use Acquia\Console\ContentHub\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\ServiceSnapshots\ContentHubCreateSnapshot;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudBackupCreate.
 *
 * @package Acquia\Console\Cloud\Command\Backups
 */
class AcquiaCloudBackupCreate extends AcquiaCloudCommandBase {

  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * Parts of the directory path pointing to configuration files.
   *
   * @var array
   */
  protected $config_dir = [
    '.acquia',
    'contenthub',
    'backups'
  ];

  /**
   * The config storage.
   *
   * @var \EclipseGc\CommonConsole\Config\ConfigStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Creates a snapshot of Acquia Content Hub Service and database backups for all sites within the platform.');
    $this->setAliases(['ace-bc']);
  }

  /**
   * AcquiaCloudBackupCreate constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \EclipseGc\CommonConsole\Config\ConfigStorage $config_storage
   *   Config storage.
   * @param \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|NULL $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigStorage $config_storage, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($event_dispatcher, $name);

    $this->storage = $config_storage;
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('We are about to create a backup of all databases in this platform and a snapshot of the subscription.');
    $output->writeln('Please name this backup in order to restore it later (alphanumeric characters only)!');

    $config = new Config();
    $this->config_dir[] = $this->platform->getAlias();

    $helper = $this->getHelper('question');
    $question = new Question('Please enter a name:');
    $question->setValidator(function ($answer) {
      if (!$answer) {
        throw new \RuntimeException(
          'Name cannot be empty!'
        );
      }
      if (strlen($answer) !== strlen(preg_replace('/\s+/', '', $answer))) {
        throw new \RuntimeException(
          'Name cannot contain white spaces!'
        );
      }
      if ($this->storage->configExists($this->config_dir, $answer)) {
        throw new \RuntimeException(
          'Configuration with given name already exists!'
        );
      }

      return $answer;
    });
    $answer = $helper->ask($input, $output, $question);

    try{
      $backups = $this->getBackupId($this->platform, $output);
      if (empty($backups)) {
        $output->writeln('<warning>Cannot find the recently created backup.</warning>');
        return 1;
      }
      $output->writeln('<info>Database backups are successfully created! Starting ACH service snapshot creation!</info>');

      $snapshot = $this->runSnapshotCreateCommand($output);
      if (empty($snapshot)) {
        $output->writeln('<warning>Cannot create service snapshot. Check your content hub service credentials and try again.</warning>');
        return 2;
      }
      $output->writeln("<info>Snapshot is successfully created. Current ACH version is {$snapshot['module_version']}.x .</info>");
    } catch (\Exception $exception) {
      $output->writeln("<error>{$exception->getMessage()}</error>");
      return 3;
    }

    $platform_info = [
      'name' => $this->platform->getAlias(),
      'type' => $this->platform->getPlatformId(),
      'module_version' => $snapshot['module_version'],
      'backupCreated' => time(),
    ];
    $backup_info = [
      'database' => $backups,
      'ach_snapshot' => $snapshot['snapshot_id'],
    ];


    $config->set('name', $answer);
    $config->set('platform', $platform_info);
    $config->set('backups', $backup_info);
    $this->storage->save($config, $answer, $this->config_dir);

    return 0;
  }

  /**
   * Returns info about newly created backups.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return array
   *   Info about newly created database backups.
   * @throws \Exception
   */
  protected function getBackupId(PlatformInterface $platform, OutputInterface $output): array {
    $output->writeln('<info>Starts creating the database backups.</info>');
    $list_before = $this->runBackupListCommand($platform, $output);
    $raw = $this->runBackupCreateCommand($platform);

    if ($raw->getReturnCode() !== 0) {
      throw new \Exception('Database backup creation failed.');
    }

    $list_after = $this->runBackupListCommand($platform, $output);

    return array_diff_key($list_after, $list_before);
  }

  /**
   * List currently saved database backups.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return array
   *   Db backup info.
   *
   * @throws \Exception
   */
  protected function runBackupListCommand(PlatformInterface $platform, OutputInterface $output) {
    $cmd_input = [
      '--all' => true,
      '--silent' => true,
    ];

    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudDatabaseBackupList::getDefaultName(), $platform, $cmd_input);

    $db_backup_list = [];
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      foreach ($data as $backup) {
        $db_backup_list[$backup->backup_id] = [
          'environment_id' => $backup->env_id,
          'database_name' => $backup->database,
          'created_at' => $backup->completed_at,
        ];
      }
    }

    return $db_backup_list;
  }

  /**
   * Runs database backup creation command.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   *
   * @return object
   *   Object containing command run info.
   *
   * @throws \Exception
   */
  protected function runBackupCreateCommand(PlatformInterface $platform): object {
    $cmd_input = [
      '--all' => true,
      '--wait' => true,
    ];

    return $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudDatabaseBackupCreate::getDefaultName(), $platform, $cmd_input);
  }

  /**
   * Creates ACH service snapshot.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return array
   *   Array containing snapshot ID and module version.
   *
   * @throws \Exception
   */
  protected function runSnapshotCreateCommand(OutputInterface $output): array {
    $sites = $this->getPlatformSites('source');
    $site_info = reset($sites);
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubCreateSnapshot::getDefaultName(), $this->getPlatform('source'), [
        '--uri' => $site_info['uri'],
    ]);

    $exit_code = $raw->getReturnCode();
    if ($exit_code !== 0) {
      throw new \Exception("Cannot create ACH service snapshot. Exit code: $exit_code");
    }

    $info = [];

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }


      $info = [
        'snapshot_id' => $data->snapshot_id,
        'module_version' => $data->module_version,
      ];
    }

    return $info;
  }

}
