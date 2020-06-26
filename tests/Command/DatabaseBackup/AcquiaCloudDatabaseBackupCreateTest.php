<?php

namespace Acquia\Console\Cloud\Tests\Command\DatabaseBackup;

use Acquia\Console\Cloud\Command\ContentHubAcquiaCloudInit;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate;

/**
 * Class AcquiaCloudDatabaseBackupCreateTest.
 *
 * @coversDefaultClass \Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate
 *
 * @group acquia-console-cloud
 *
 * @package Acquia\Console\Cloud\Tests\Command\DatabaseBackup
 */
class AcquiaCloudDatabaseBackupCreateTest extends AcquiaCloudDatabaseBackupTestBase {

  /**
   * @covers ::doRunCommand
   */
  public function testNoSitesAvailable() {
    $tester = $this->getCmdTesterInstanceOf(AcquiaCloudDatabaseBackupCreate::class, [], [
      ContentHubAcquiaCloudInit::CONFIG_CLOUD_SITES => [],
    ]);
    $tester->execute([]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString('No sites have been registered in the current platform.', $output, 'Profile is empty, no sites available.');
  }

  /**
   * @covers ::doRunCommand
   */
  public function testDatabaseCreate() {
    $object = (object) [
      'message' => 'Database backup created.',
      '_links' => (object) [
        'notification' => (object) [
          'href' => 'https://test2.devcloud.acquia-sites.com/api/v2/notifications/2343b683-b194-4217-982a-6a95c72ad9a8',
        ],
      ],
    ];
    $notification = $this->getFixture('ace_notification.php')['backup_create'];

    $tester = $this->getCmdTesterInstanceOf(AcquiaCloudDatabaseBackupCreate::class, [$object, $object, $notification]);
    $tester->setInputs([1, 0, 'no'])->execute([]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString('Backup process terminated by user', $output);

    $tester->setInputs([1, 0, 'yes'])->execute([]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString('Process has been queued. Check the task logs for more information.', $output);

    $tester->setInputs([1, 0, 'yes'])->execute(['--wait' => TRUE]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString('Database created', $output, 'Wait function works.');
  }

}
