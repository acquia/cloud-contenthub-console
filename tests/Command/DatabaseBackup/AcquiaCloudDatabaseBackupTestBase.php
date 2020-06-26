<?php

namespace Acquia\Console\Cloud\Tests\Command\DatabaseBackup;

use Acquia\Console\Cloud\Command\ContentHubAcquiaCloudInit;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\TestFixtureHelperTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AcquiaCloudDatabaseBackupTestBase.
 *
 * @package Acquia\Console\Cloud\Tests\Command\DatabaseBackup
 */
abstract class AcquiaCloudDatabaseBackupTestBase extends TestCase {

  use TestFixtureHelperTrait;
  use PlatformCommandTestHelperTrait;
  use CommandTestHelperTrait;

  /**
   * Returns a command tester instance.
   *
   * @param string $cmd
   *   The command to instantiate.
   * @param array $client_response
   *   The expected client response.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *   The command tester.
   *
   * @throws \ReflectionException
   */
  protected function getCmdTesterInstanceOf(string $cmd, array $client_response = [], array $config_overwrite = []) {
    $reflection = new \ReflectionClass($cmd);
    /** @var \Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupBase $cmd */
    $cmd = $reflection->newInstanceArgs([$this->getDispatcher()]);
    $platform = $this->getPlatform($client_response);
    if ($config_overwrite) {
      foreach ($config_overwrite as $key => $value) {
        $platform->set($key, $value);
      }
    }
    $cmd->addPlatform('test', $platform);

    return $this->getCommandTester($cmd);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    $client_mock_callback = function (MockObject $client) use ($args) {
      $client->method('request')->willReturnOnConsecutiveCalls(...$args);
    };

    return $this->getAcquiaCloudPlatform(
      [
        AcquiaCloudPlatform::ACE_API_KEY => 'test_key',
        AcquiaCloudPlatform::ACE_API_SECRET => 'test_secret',
        AcquiaCloudPlatform::ACE_APPLICATION_ID => ['test1'],
        ContentHubAcquiaCloudInit::CONFIG_CLOUD_SITES => $this->getFixture('ace_sites.php'),
      ],
      $client_mock_callback
    );
  }

}
