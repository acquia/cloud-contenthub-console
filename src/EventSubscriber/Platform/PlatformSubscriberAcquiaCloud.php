<?php

namespace Acquia\Console\Cloud\EventSubscriber\Platform;

use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use AcquiaCloudApi\Endpoints\Environments;
use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\GetPlatformTypeEvent;
use EclipseGc\CommonConsole\Event\GetPlatformTypesEvent;
use EclipseGc\CommonConsole\Event\PlatformWriteEvent;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PlatformSubscriberAcquiaCloud
 *
 * @package Acquia\Console\Cloud\EventSubscriber\Platform
 */
class PlatformSubscriberAcquiaCloud implements EventSubscriberInterface {

  /**
   * @var \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory
   */
  protected $factory;

  public function __construct(AcquiaCloudClientFactory $factory) {
    $this->factory = $factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CommonConsoleEvents::GET_PLATFORM_TYPES] = 'onGetPlatformTypes';
    $events[CommonConsoleEvents::GET_PLATFORM_TYPE] = 'onGetPlatformType';
    $events[CommonConsoleEvents::PLATFORM_WRITE] = ['onPlatformWrite', 10];
    return $events;
  }

  /**
   * Expose available platform(s) to CommonConsole.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypesEvent $event
   *   The get platform TYPES event.
   */
  public function onGetPlatformTypes(GetPlatformTypesEvent $event) {
    $event->addPlatformType(AcquiaCloudPlatform::getPlatformId());
  }

  /**
   * Add class and/or factory data for an available platform.
   *
   * @param \EclipseGc\CommonConsole\Event\GetPlatformTypeEvent $event
   *   The get platform TYPE event.
   *
   * @throws \Exception
   */
  public function onGetPlatformType(GetPlatformTypeEvent $event) {
    if ($event->getPlatformType() === AcquiaCloudPlatform::getPlatformId()) {
      $event->addClass(AcquiaCloudPlatform::class);
      $event->addFactory('factory.platform.ace');
      $event->stopPropagation();;
    }
  }

  /**
   * Extract environment ids before saving platform details to disk.
   *
   * Rather than having to make multiple calls for each command per each
   * application in the command run time, we spend a little extra time before
   * saving the values to ensure that we have all the relevant environment ids.
   * This will save us some cloud calls later and make the overall performance
   * faster when running commands.
   *
   * @param \EclipseGc\CommonConsole\Event\PlatformWriteEvent $event
   *   The platform write event.
   */
  public function onPlatformWrite(PlatformWriteEvent $event) {
    $platform = $event->getPlatform();
    if ($platform->get(PlatformInterface::PLATFORM_TYPE_KEY) !== AcquiaCloudPlatform::getPlatformId()) {
      return;
    }
    $client = $this->factory->fromCredentials($platform->get(AcquiaCloudPlatform::ACE_API_KEY), $platform->get(AcquiaCloudPlatform::ACE_API_SECRET));
    $environment = new Environments($client);
    $environment_details = [];
    foreach ($platform->get(AcquiaCloudPlatform::ACE_APPLICATION_ID) as $application_id) {
      /** @var \AcquiaCloudApi\Response\EnvironmentResponse $item */
      foreach ($environment->getAll($application_id) as $item) {
        if ($item->name === $platform->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME)) {
          $environment_details[$application_id] = $item->uuid;
          continue;
        }
      }
    }
    $platform->set(AcquiaCloudPlatform::ACE_ENVIRONMENT_DETAILS, $environment_details);
  }

}
