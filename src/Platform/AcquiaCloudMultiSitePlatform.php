<?php

namespace Acquia\Console\Cloud\Platform;

use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Event\Traits\PlatformArgumentInjectionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

/**
 * Class AcquiaCloudMultiSitePlatform.
 *
 * @package Acquia\Console\Cloud\Platform
 */
class AcquiaCloudMultiSitePlatform extends AcquiaCloudPlatform {

  use PlatformArgumentInjectionTrait;
  use PlatformCmdOutputFormatterTrait;

  const PLATFORM_NAME = "Acquia Cloud Multi Site";

  /**
   * {@inheritdoc}
   */
  public static function getApplicationQuestion(Config $config, AcquiaCloudClientFactory $factory) {
    $question = parent::getApplicationQuestion($config, $factory);
    $question->setMultiselect(FALSE);

    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Command $command, InputInterface $input, OutputInterface $output): int {
    $exit_code = 0;

    $environments = new Environments($this->getAceClient());
    $env_id = current($this->get(self::ACE_ENVIRONMENT_DETAILS));
    $environment = $environments->get($env_id);

    $sshUrl = $environment->sshUrl;
    [, $url] = explode('@', $sshUrl);
    [$application] = explode('.', $url);
    $sites = $this->getPlatformMultiSites($environment, $application, $output);

    if (!$sites) {
      $output->writeln('<warning>No sites available. Exiting...</warning>');
      return 1;
    }

    $input_uri = $input->getOption('uri');
    if ($input_uri) {
      if (in_array($input_uri, $sites, TRUE)) {
        $sites = [$input_uri];
      } else {
        $output->writeln(sprintf("Given Url does not belong to the sites within the platform. %s", $input_uri));
        return 2;
      }
    }

    $args = $this->dispatchPlatformArgumentInjectionEvent($input, $sites, $command);

    foreach ($sites as $uri) {
      $output->writeln(sprintf("Attempting to execute requested command in environment: %s", $uri));
      $process = new Process("ssh $sshUrl 'cd /var/www/html/$application/docroot; ./vendor/bin/commoncli {$args[$uri]->__toString()}'");
      $exit_code += $this->runner->run($process, $this, $output);
    }

    return $exit_code;
  }

  /**
   * Gets site URIs from sites.php on the given platform.
   *
   * @param \AcquiaCloudApi\Response\EnvironmentResponse $env_response
   *   Environment Response instance.
   * @param string $application
   *   Acquia Cloud Application name.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The OutputInterface instance.
   *
   * @return array
   *  Array containing site URIs.
   */
  public function getPlatformMultiSites(EnvironmentResponse $env_response, string $application, OutputInterface $output): array {
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', false));

    $command = "./vendor/bin/commoncli ace:multi:sites";
    $process = new Process("ssh {$env_response->sshUrl} 'cd /var/www/html/$application/docroot; $command'");
    $this->runner->run($process, $this, $remote_output);
    rewind($remote_output->getStream());
    $content = stream_get_contents($remote_output->getStream());

    $lines = explode(PHP_EOL, trim($content));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if (isset($data->sites)) {
        return array_intersect(array_keys(array_unique((array) $data->sites)), $env_response->domains);
      }
    }

    return [];
  }

}
