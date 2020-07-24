<?php

namespace Acquia\Console\Cloud\Platform;

use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;
use EclipseGc\CommonConsole\Platform\PlatformBase;
use EclipseGc\CommonConsole\Platform\PlatformSitesInterface;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

/**
 * Class AcquiaCloudPlatform
 *
 * @package Acquia\Console\Cloud\Platform
 */
class AcquiaCloudPlatform extends PlatformBase implements PlatformSitesInterface {

  const PLATFORM_NAME = "Acquia Cloud";

  public const ACE_API_KEY = 'acquia.cloud.api_key';

  public const ACE_API_SECRET = 'acquia.cloud.api_secret';

  public const ACE_APPLICATION_ID = 'acquia.cloud.application_ids';

  public const ACE_ENVIRONMENT_NAME = 'acquia.cloud.environment.name';

  public const ACE_ENVIRONMENT_DETAILS = 'acquia.cloud.environment.ids';

  /**
   * The Acquia Cloud Client Factory object.
   *
   * @var \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory
   */
  protected $clientFactory;

  public function __construct(ConfigInterface $config, ProcessRunner $runner, PlatformStorage $storage, AcquiaCloudClientFactory $clientFactory) {
    parent::__construct($config, $runner, $storage);
    $this->clientFactory = $clientFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPlatformId(): string {
    return static::PLATFORM_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public static function getQuestions() {
    return [
      self::ACE_API_KEY => new Question("Acquia Cloud API Key? (Instructions: https://docs.acquia.com/acquia-cloud/develop/api/auth/) "),
      self::ACE_API_SECRET => new Question("Acquia Cloud Secret? "),
      self::ACE_APPLICATION_ID => [
        'question' => [AcquiaCloudPlatform::class, 'getApplicationQuestion'],
        'services' => ['http_client_factory.acquia_cloud']
      ],
      self::ACE_ENVIRONMENT_NAME => [
        'question' => [AcquiaCloudPlatform::class, 'getEnvironmentQuestion'],
        'services' => ['http_client_factory.acquia_cloud']
      ]
    ];
  }

  /**
   * Creates a question about available applications for the api key/secret.
   *
   * @param \Consolidation\Config\Config $config
   *   The values thus collected.
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *   The Acquia Cloud Client Factory.
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
   */
  public static function getApplicationQuestion(Config $config, AcquiaCloudClientFactory $factory) {
    $client = $factory->fromCredentials($config->get(self::ACE_API_KEY), $config->get(self::ACE_API_SECRET));
    $applications = new Applications($client);
    $options = [];
    /** @var \AcquiaCloudApi\Response\ApplicationResponse $item */
    foreach ($applications->getAll() as $item) {
      if ($item->hosting->type === 'acsf') {
        continue;
      }
      $options[$item->uuid] = $item->name;
    }
    $question = new ChoiceQuestion("Choose an Application: ", $options);
    $question->setMultiselect(TRUE);
    return $question;
  }

  /**
   * Creates question for available environments for the selected application.
   *
   * @param \Consolidation\Config\Config $config
   *   The values thus collected.
   * @param \Acquia\Console\Cloud\Client\AcquiaCloudClientFactory $factory
   *   The Acquia Cloud Client Factory.
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
   */
  public static function getEnvironmentQuestion(Config $config, AcquiaCloudClientFactory $factory) {
    $client = $factory->fromCredentials($config->get(self::ACE_API_KEY), $config->get(self::ACE_API_SECRET));
    $environment = new Environments($client);
    $options = [];
    foreach ($config->get(self::ACE_APPLICATION_ID) as $application_id) {
      /** @var \AcquiaCloudApi\Response\EnvironmentResponse $item */
      foreach ($environment->getAll($application_id) as $item) {
        $options[$item->name][] = $item->uuid;
      }
    }
    foreach ($options as $env => $envs) {
      if (count($envs) != count($config->get(self::ACE_APPLICATION_ID))) {
        unset($options[$env]);
      }
    }
    $options = array_keys($options);
    $options = array_combine($options, $options);
    /** @var \AcquiaCloudApi\Response\EnvironmentResponse $item */
    return new ChoiceQuestion("Choose an Environment: ", $options);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Command $command, InputInterface $input, OutputInterface $output) : void {
    $environments = new Environments($this->getAceClient());

    foreach ($this->get(self::ACE_ENVIRONMENT_DETAILS) as $application_id => $environment_id) {
      $environment = $environments->get($environment_id);
      $output->writeln(sprintf("Attempting to execute requested command in environment: %s", $environment->uuid));
      $uri = $this->getActivedomain($environment_id);
      $sshUrl = $environment->sshUrl;
      [, $url] = explode('@', $sshUrl);
      [$application] = explode('.', $url);
      $process = new Process("ssh $sshUrl 'cd /var/www/html/$application/docroot; ./vendor/bin/commoncli {$input->__toString()} --uri $uri'");
      $this->runner->run($process, $this, $output);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function out(Process $process, OutputInterface $output, string $type, string $buffer) : void {
    if (Process::ERR === $type) {
      if (substr(trim($buffer), -32) === ": Permission denied (publickey).") {
        $output->writeln("<warning>Your SSH key is likely missing from Acquia Cloud. Follow this document to troubleshoot it: </warning><url>https://docs.acquia.com/acquia-cloud/manage/ssh/enable/add-key/</url>");
      }
    }
  }

  /**
   * Gets an Ace Client for this platform.
   *
   * @return \AcquiaCloudApi\Connector\Client
   */
  public function getAceClient() : Client {
    return $this->clientFactory->fromCredentials($this->get(self::ACE_API_KEY), $this->get(self::ACE_API_SECRET));
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformSites(): array {
    $client = $this->getAceClient();
    $environments = new Environments($client);
    $sites = [];
    $environment_details = $this->get(self::ACE_ENVIRONMENT_DETAILS);
    if (empty($environment_details)) {
      return $sites;
    }
    foreach ($environment_details as $application_id => $environment_id) {
      $environment = $environments->get($environment_id);
      $sites[$environment->uuid] = [implode(', ', $environment->domains), static::getPlatformId()];
    }
    return $sites;
  }

  /**
   * Gets site active domain by environment id.
   *
   * @param string $env_id
   *   Environment id.
   *
   * @return string
   *   Return the domain or an empty string.
   */
  protected function getActiveDomain(string $env_id): string {
    $client = $this->getAceClient();
    $response = $client->request('get', "/environments/{$env_id}");

    return is_array($response) ? '' : $response->active_domain;
  }

  /**
   * Gets active domains for all site in the platform.
   *
   * @return array
   *   Array containing all active domains within the platform.
   */
  public function getActiveDomains(): array {
    $client = $this->getAceClient();

    $domains = [];
    foreach ($this->get(self::ACE_ENVIRONMENT_DETAILS) as $application_id => $environment_id) {
      $response = $client->request('get', "/environments/{$environment_id}");
      $domains[] = [
        'active_domain' => $response->active_domain,
        'env_uuid' => $environment_id,
      ];
    }

    return $domains;
  }

}

