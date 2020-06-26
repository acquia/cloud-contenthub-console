<?php

namespace Acquia\Console\Cloud\Tests\Command\DatabaseBackup;

use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupList;

/**
 * Class AcquiaCloudDatabaseBackupListTest.
 *
 * @coversDefaultClass \Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupList
 *
 * @group acquia-console-cloud
 *
 * @package Acquia\Console\Cloud\Tests\Command\DatabaseBackup
 */
class AcquiaCloudDatabaseBackupListTest extends AcquiaCloudDatabaseBackupTestBase {

  /**
   * @covers ::doRunCommand
   */
  public function testDatabaseBackupList() {
    $backups_response = $this->getFixture('ace_backups_resp.php');
    $tester = $this->getCmdTesterInstanceOf(
      AcquiaCloudDatabaseBackupList::class,
      [$backups_response]
    );
    $tester->setInputs([1, 0, '2017-01-08T05:00:02Z'])->execute([]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString($this->getFixtureContents('ace_db_list_output.txt'), $output, 'Backups listed for the given database.');
  }

  /**
   * @covers ::doRunCommand
   */
  public function testWithNoBackupsAvailable() {
    $tester = $this->getCmdTesterInstanceOf(
      AcquiaCloudDatabaseBackupList::class,
      [[]]
    );
    $tester->setInputs([1, 0])->execute([]);
    $output = $tester->getDisplay();

    $this->assertStringContainsString('No backups found', $output, 'Backups listed for the given database.');
  }

}
