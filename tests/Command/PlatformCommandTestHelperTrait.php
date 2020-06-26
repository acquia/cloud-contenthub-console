<?php

namespace Acquia\Console\Cloud\Tests\Command;

use Acquia\Console\Acsf\Client\AcsfClient;
use Acquia\Console\Acsf\Client\AcsfClientFactory;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use AcquiaCloudApi\Connector\Client;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\EventSubscriber\AddPlatform\AnyPlatform;
use EclipseGc\CommonConsole\EventSubscriber\AddPlatform\PlatformIdMatch;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trait PlatformCommandTestHelperTrait.
 *
 * @package Acquia\Console\Cloud\Tests\Command
 */
trait PlatformCommandTestHelperTrait {

  /**
   * Returns a dispatcher bootstrapped for platform commands.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function getDispatcher(): EventDispatcherInterface {
    $subscriber = new PlatformIdMatch();
    $any_platform = new AnyPlatform();
    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(CommonConsoleEvents::ADD_PLATFORM_TO_COMMAND, [$subscriber, 'onAddPlatformToCommand']);
    $dispatcher->addListener(CommonConsoleEvents::ADD_PLATFORM_TO_COMMAND, [$any_platform, 'onAddPlatformToCommand']);
    return $dispatcher;
  }

  /**
   * Returns a platform of type Acquia Cloud.
   *
   * @param array $platform_config
   *   The platform configuration array.
   * @param callable $client_mock_modifier
   *   [Optional] The callable function to alter client. It accepts a
   *   MockObject.
   *
   * @return mixed
   */
  protected function getAcquiaCloudPlatform(array $platform_config, callable $client_mock_modifier = NULL) {
    $client = $this->getMockBuilder(Client::class)
      ->disableOriginalConstructor()
      ->getMock();

    if ($client_mock_modifier) {
      $client_mock_modifier($client);
    }

    $client_factory = $this->getMockBuilder(AcquiaCloudClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $client_factory->method('fromCredentials')->willReturn($client);

    $process_runner = $this->getMockBuilder(ProcessRunner::class)
      ->disableOriginalConstructor()
      ->getMock();

    $platform_storage = $this->getMockBuilder(PlatformStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    return new AcquiaCloudPlatform(
      $this->parseConfigArray($platform_config),
      $process_runner,
      $platform_storage,
      $client_factory
    );
  }

  /**
   * @param array $platform_config
   * @param callable $client_mock_modifier
   *
   * @return \Acquia\Console\Acsf\Platform\ACSFPlatform
   */
  protected function getAcsfPlatform(array $platform_config, callable $client_mock_modifier = NULL) {
    $client = $this->getMockBuilder(AcsfClient::class)
      ->disableOriginalConstructor()
      ->getMock();

    if ($client_mock_modifier) {
      $client_mock_modifier($client);
    }

    $ace_factory = $this->getMockBuilder(AcquiaCloudClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $acsf_factory = $this->getMockBuilder(AcsfClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $acsf_factory->method('fromCredentials')->willReturn($client);

    $process_runner = $this->getMockBuilder(ProcessRunner::class)
      ->disableOriginalConstructor()
      ->getMock();

    $platform_storage = $this->getMockBuilder(PlatformStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    return new ACSFPlatform(
      $this->parseConfigArray($platform_config),
      $process_runner,
      $platform_storage,
      $ace_factory,
      $acsf_factory
    );
  }

  /**
   * Converts configuration array to Consolidation/Config object.
   *
   * @param array $platform_config
   *   The configuration array.
   *
   * @return \Consolidation\Config\Config
   *   The platform's config object.
   */
  private function parseConfigArray(array $platform_config) {
    $config = new Config();
    foreach ($platform_config as $key => $value) {
      $config->set($key, $value);
    }
    return $config;
  }

}
