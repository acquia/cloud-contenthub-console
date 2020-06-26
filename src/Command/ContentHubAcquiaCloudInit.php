<?php

namespace Acquia\Console\Cloud\Command;

use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides command for Acquia Cloud sites configuration.
 *
 * @package Acquia\Console\Cloud\Command
 */
class ContentHubAcquiaCloudInit extends AcquiaCloudCommandBase {

  /**
   * Acquia Cloud sites config name.
   */
  public const CONFIG_CLOUD_SITES = 'acquia.cloud.sites';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:init';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Sets up site configuration for Acquia Cloud applications');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('You are about to update your configuration with Acquia Cloud Site information.');
    $applications = $this->platform->get(AcquiaCloudPlatform::ACE_APPLICATION_ID);
    if (!$applications) {
      $output->writeln('<error>No application available</error>');
      return 1;
    }

    $this->setAcquiaCloudSitesConfiguration($applications);
    $output->writeln('<info>Configuration has been updated successfully.</info>');
    return 0;
  }

  /**
   * Sets platform configuration.
   *
   * @param array $application_uuids
   *   Acquia Cloud application uuids.
   *
   * @throws \Exception
   */
  public function setAcquiaCloudSitesConfiguration(array $application_uuids): void {
    $data = $this->getDataFromAcquiaCloud($application_uuids);

    // Bring site information on the same level within the array.
    // After transformation we can set in the config.
    $data = call_user_func_array('array_merge', $data);

    $this->platform
      ->set(self::CONFIG_CLOUD_SITES, $data)
      ->save();
  }

  /**
   * Gathers information from Acquia Cloud.
   *
   * @param array $application_uuids
   *   Acquia Cloud application uuids.
   *
   * @return array
   *   Data to set into config file.
   *
   * @throws \Exception
   */
  protected function getDataFromAcquiaCloud($application_uuids): array {
    $data = [];
    foreach ($application_uuids as $uuid) {
      $data[] = $this->getSiteInfoByApplication($uuid);
    }

    return $data;
  }

  /**
   * Gather information for a specific application.
   *
   * @param string $uuid
   *   Application uuid.
   *
   * @return array
   *   Site information of environments.
   *
   * @throws \Exception
   */
  protected function getSiteInfoByApplication(string $uuid): array {
    $data = [];

    $environments = $this->getEnvironmentsByApplicationUUID($uuid);
    foreach ($environments as $environment) {
      $db_info = $this->getDatabasesByEnvironment($environment->id);
      foreach ($db_info as $db) {
        $databases[$db->id] = $db->name;
      }

      $data[] = [
        'env_uuid' => $environment->id,
        'name' => $environment->name,
        'active_domain' => $environment->active_domain,
        'databases' => $databases ?? [],
      ];

      $databases = [];
    }

    return $data;
  }

  /**
   * Make request against /applications/{application_uuid}/environments endpoint.
   *
   * @param string $uuid
   *   Application uuid.
   *
   * @return array
   *   Response of API call (Environment info).
   */
  protected function getEnvironmentsByApplicationUUID(string $uuid): array {
    return $this->acquiaCloudClient->request('GET', "/applications/$uuid/environments");
  }

  /**
   * Make request against /environments/{environment_uuid}/databases endpoint.
   *
   * @param string $env_uuid
   *   Environment uuid.
   *
   * @return array
   *   Response of API call (DB info).
   */
  protected function getDatabasesByEnvironment(string $env_uuid) {
    return $this->acquiaCloudClient->request('GET', "/environments/$env_uuid/databases");
  }

}
