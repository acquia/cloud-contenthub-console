<?php

namespace Acquia\Console\Cloud\Tests\Command;

use Acquia\Console\Cloud\Command\ContentHubAcquiaCloudInit;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ContentHubAcquiaCloudInitTest.
 *
 * @coversDefaultClass \Acquia\Console\Cloud\Command\ContentHubAcquiaCloudInit
 *
 * @group acquia-contenthub-cli
 *
 * @package Acquia\Console\Cloud\Tests\Command
 */
class ContentHubAcquiaCloudInitTest extends TestCase {

  use CommandTestHelperTrait;
  use PlatformCommandTestHelperTrait;

  /**
   * @throws \Exception
   */
  public function testAcquiaCloudSubscriptionInit() {
    $command = new ContentHubAcquiaCloudInit(
      $this->getDispatcher(),
      ContentHubAcquiaCloudInit::getDefaultName());
    $command->addPlatform('test', $this->getPlatform());
    $this->doRunCommand($command, [], ['alias' => '@test']);

    $config = $command->getPlatform('source')
      ->get(ContentHubAcquiaCloudInit::CONFIG_CLOUD_SITES);
    $this->assertEquals([
        'env_uuid' => 'env_id',
        'name' => 'test',
        'active_domain' => 'test.com',
        'databases' => [
          'db_id' => 'test_db'
        ],
      ], $config[0]);
  }

  /**
   * Create object from array.
   *
   * @param array $mock_data
   *   Data of mock object.
   *
   * @return object
   *   Mock of the endpoint outputs.
   */
  protected function getTestMockEnvData(array $mock_data): object {
    $mock_object = new \stdClass();
    foreach ($mock_data as $key => $item) {
      $mock_object->$key = $item;
    }

    return $mock_object;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    $client_mock_callback = function(MockObject $client) {
      $client->method('request')->willReturnOnConsecutiveCalls(
        [
          $this->getTestMockEnvData([
            'id' => 'env_id',
            'name' => 'test',
            'active_domain' => 'test.com',
          ])
        ],
        [
          $this->getTestMockEnvData([
            'id' => 'db_id',
            'name' => 'test_db',
          ])
        ]
      );
    };

    return $this->getAcquiaCloudPlatform(
      [
        AcquiaCloudPlatform::ACE_API_KEY => 'test_key',
        AcquiaCloudPlatform::ACE_API_SECRET => 'test_secret',
        AcquiaCloudPlatform::ACE_APPLICATION_ID => ['test1'],
      ],
      $client_mock_callback
    );
  }

}
