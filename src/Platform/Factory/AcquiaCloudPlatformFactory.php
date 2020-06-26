<?php

namespace Acquia\Console\Cloud\Platform\Factory;

use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Consolidation\Config\ConfigInterface;
use EclipseGc\CommonConsole\Event\GetPlatformTypeEvent;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\PlatformFactoryInterface;
use EclipseGc\CommonConsole\PlatformInterface;
use EclipseGc\CommonConsole\ProcessRunner;

class AcquiaCloudPlatformFactory implements PlatformFactoryInterface {

  /**
   * The Acquia Cloud Client Factory object.
   *
   * @var \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory
   */
  protected $factory;

  /**
   * The platform storage object.
   *
   * @var \EclipseGc\CommonConsole\Platform\PlatformStorage
   */
  protected $storage;

  /**
   * AcquiaCloudPlatformFactory constructor.
   *
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *   The Acquia Cloud Client Factory object.
   * @param \EclipseGc\CommonConsole\Platform\PlatformStorage $storage
   *   The platform storage service.
   */
  public function __construct(AcquiaCloudClientFactory $factory, PlatformStorage $storage) {
    $this->factory = $factory;
    $this->storage = $storage;
  }

  /**
   * Create a new AcquiaCloudPlatform.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypeEvent $event
   *   The get platform type event.
   * @param \Consolidation\Config\ConfigInterface $config
   *   The platform's configuration.
   * @param \EclipseGc\CommonConsole\ProcessRunner $runner
   *   The process runner.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface
   *   The AcquiaCloudPlatform object.
   */
  public function create(GetPlatformTypeEvent $event, ConfigInterface $config, ProcessRunner $runner) : PlatformInterface {
    $class = $event->getClass();
    return new $class($config, $runner, $this->storage, $this->factory);
  }

}
