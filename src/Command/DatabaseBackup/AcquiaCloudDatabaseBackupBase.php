<?php

namespace Acquia\Console\Cloud\Command\DatabaseBackup;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class AcquiaCloudDatabaseBackupBase.
 *
 * @package Acquia\Console\Cloud\Command\DatabaseBackup
 */
abstract class AcquiaCloudDatabaseBackupBase extends AcquiaCloudCommandBase implements PlatformCommandInterface {

  use AcquiaCloudDatabaseBackupHelperTrait;

  public const EMPTYSITESERROR = 1;

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $helper = $this->getHelper('question');
    /** @var \Acquia\Console\Cloud\Platform\AcquiaCloudPlatform $platform */
    $platform = $this->getPlatform('source');
    $options = $platform->getPlatformSites();
    if (empty($options)) {
      $output->writeln('No sites have been registered in the current platform.');
      return 0;
    }

    $sites = [];
    foreach ($options as $uuid => $site_data) {
      $sites[$uuid] = $site_data['uri'];
    }

    $sites = $this->filterSites($input, $output, $sites);
    if (empty($sites)) {
      $output->writeln('<warning>No sites available. Exiting...</warning>');
      return self::EMPTYSITESERROR;
    }

    if (!$input->getOption('all')) {
      do {
        $output->writeln('You are about to create a site backup for one of your Cloud sites.');
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Pick one of the following sites:', $sites);
        $site = $helper->ask($input, $output, $question);

        $quest = new ConfirmationQuestion('Do you want to proceed?');
        $answer = $helper->ask($input, $output, $quest);
      } while ($answer !== TRUE);

      $sites = [$site => $sites[$site]];
    }

    foreach ($sites as $uuid => $site) {
      $databases = $this->getDatabasesByEnvironment($uuid);
      $db_info = reset($databases);
      $this->doRunCommand($uuid, $db_info->name, $input, $output);
    }

    $databases = [];
    foreach ($this->getDatabasesByEnvironment($site) as $db_info) {
      $databases[$db_info->id] = $db_info->name;
    }
    $choice = new ChoiceQuestion('Choose a database:', array_values($databases));
    $db = $helper->ask($input, $output, $choice);

    return $this->doRunCommand($site, $db, $input, $output);
  }

  /**
   * Runs the command implementation.
   *
   * @param string $env_id
   *   The environment id.
   * @param string $db
   *   The database name.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input object.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream.
   *
   * @return int
   *   Exit code.
   */
  abstract protected function doRunCommand(string $env_id, string $db, InputInterface $input, OutputInterface $output): int;

  /**
   * Filter platform sites via groups and other options.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input object.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream.
   * @param array $sites
   *   Sites list.
   *
   * @return array|int
   *   List of sites after filtering.
   */
  protected function filterSites(InputInterface $input, OutputInterface $output, array $sites) {
    if ($input->hasOption('group') && !empty($input->getOption('group'))) {
      $group_name = $input->getOption('group');
      $platform = $this->getPlatform('source');
      $alias = $platform->getAlias();
      $platform_id = self::getExpectedPlatformOptions()['source'];
      $sites = $this->filterSitesByGroup($group_name, $sites, $output, $alias, $platform_id);
    }

    return $sites;
  }

}
