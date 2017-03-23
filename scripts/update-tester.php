#!/usr/bin/env robo
<?php

use Robo\Task\Composer\Update;
use Robo\Tasks;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Thunder\UpdateTester\Exec\Drush;
use Thunder\UpdateTester\Task\CloneSite;
use Thunder\UpdateTester\Task\Drupal\ValidateSite;
use Thunder\UpdateTester\Task\UpdatePackages;
use Thunder\UpdateTester\Util\DocrootResolver;

/**
 * Robo script to execute update scaffold tasks.
 */
class UpdateTester extends Tasks {

  /**
   * Clone site from source to destination directory.
   *
   * Cloned site will use same database connection as source site, unless it's
   * differently specified in options. Cloned database will have suffix "_clone"
   * unless it's sepicified by command options.
   *
   * @param string $source
   *   Source directory of site that should be cloned.
   * @param string $destination
   *   Destination directory where site will be cloned.
   * @param array $options
   *   List of options provided for task.
   *
   * @command site:clone
   *
   * @option $db-name Database name for cloned site.
   * @option $db-username Username for cloned site.
   * @option $db-password Password for cloned site.
   */
  public function siteClone(
    $source,
    $destination,
    array $options = [
      'db-name' => InputOption::VALUE_REQUIRED,
      'db-username' => InputOption::VALUE_REQUIRED,
      'db-password' => InputOption::VALUE_REQUIRED,
    ]
  ) {
    /** @var \Thunder\UpdateTester\Task\CloneSite $cloneSite */
    $cloneSite = $this->task(CloneSite::class, $source, $destination);
    $cloneSite->setInput($this->input());
    $cloneSite->setOutput($this->output());

    $cloneSite->run();
  }

  /**
   * Get forced packages from command line option.
   *
   * Forced packages are comma separated list of composer packages and they are
   * passed to tasks as array.
   *
   * @param string $inputValue
   *   Value provided in command line.
   *
   * @return array
   *   Return list of packages as array.
   */
  protected function getForcedPackages($inputValue) {
    $forcePackages = [];

    if ($inputValue) {
      $forcePackages = str_getcsv($inputValue);

      if (empty($forcePackages) || empty($forcePackages[0])) {
        $this->say('List of forced packages is not provided properly. List of comma separated packages is expected.');

        return [];
      }
    }

    return $forcePackages;
  }

  /**
   * Update composer packages for project.
   *
   * @param array $options
   *   List of options provided for task.
   *
   * @command update:packages
   *
   * @option $output-file Optional output file for composer.json.
   * @option $major-versions Update major versions in composer.json.
   * @option $force-packages Comma separated packages that should be updated.
   */
  public function updatePackages(
    array $options = [
      'output-file' => InputOption::VALUE_REQUIRED,
      'major-versions' => FALSE,
      'force-packages' => InputOption::VALUE_REQUIRED,
    ]
  ) {
    /** @var \Thunder\UpdateTester\Task\UpdatePackages $updatePackages */
    $updatePackages = $this->task(UpdatePackages::class);
    $updatePackages->setForcedPackages($this->getForcedPackages($options['force-packages']));
    $updatePackages->setOutput($this->output());

    if (!empty($this->input()->getOption('output-file'))) {
      $updatePackages->setComposerOutputJson($options['output-file']);
    }

    if ($options['major-versions']) {
      $updatePackages->setOnlyMinor(FALSE);
    }

    $updatePackages->run();
  }

  /**
   * Full test of update for installed site.
   *
   * Cloned site will be created and update will be executed on it.
   *
   * @param string $source
   *   Source directory of site that should be cloned.
   * @param string $destination
   *   Destination directory where site will be cloned.
   * @param array $options
   *   List of options provided for task.
   *
   * @option $db-name Database name for cloned site.
   * @option $db-username Username for cloned site.
   * @option $db-password Password for cloned site.
   * @option $major-versions Update major versions in composer.json.
   * @option $ignore-errors Execution will not be interrupted is sub-task fails.
   * @option $force-packages Comma separated packages that should be updated.
   *
   * @command test:update
   */
  public function testUpdate(
    $source,
    $destination,
    array $options = [
      'db-name' => InputOption::VALUE_REQUIRED,
      'db-username' => InputOption::VALUE_REQUIRED,
      'db-password' => InputOption::VALUE_REQUIRED,
      'major-versions' => FALSE,
      'ignore-errors' => FALSE,
      'force-packages' => InputOption::VALUE_REQUIRED,
    ]
  ) {
    $absoluteSource = realpath($source);

    $fileSystem = new Filesystem();
    if (!$fileSystem->isAbsolutePath($destination)) {
      $destination = getcwd() . '/' . $destination;
    }

    // By default stop on fail, unless "--ignore-errors" option is not set.
    $this->stopOnFail(!$options['ignore-errors']);

    /** @var \Thunder\UpdateTester\Task\CloneSite $cloneSite */
    $cloneSite = $this->task(CloneSite::class, $absoluteSource, $destination);
    $cloneSite->setInput($this->input());
    $cloneSite->setOutput($this->output());
    $cloneSite->run();

    $absoluteDestination = realpath($destination);

    /** @var \Thunder\UpdateTester\Task\UpdatePackages $updatePackages */
    $updatePackages = $this->task(UpdatePackages::class);
    $updatePackages->setForcedPackages($this->getForcedPackages($options['force-packages']));
    $updatePackages->setOutput($this->output());
    $updatePackages->setWorkingDirectory($absoluteDestination);
    if ($options['major-versions']) {
      $updatePackages->setOnlyMinor(FALSE);
    }
    $updatePackages->run();

    /** @var \Robo\Task\Composer\Update $composerUpdate */
    $composerUpdate = $this->task(Update::class);
    $composerUpdate->dir($absoluteDestination);
    $composerUpdate->option('no-interaction')->option('no-dev');
    $composerUpdate->run();

    $absoluteDestinationDocroot = DocrootResolver::getDocroot($absoluteDestination);

    /** @var \Thunder\UpdateTester\Exec\Drush $drushCmd */
    $drushCmd = $this->task(Drush::class);
    $drushCmd->dir($absoluteDestinationDocroot);
    $drushCmd->arg('updatedb');
    $drushCmd->option('entity-updates');
    $drushCmd->printOutput(TRUE);
    $drushCmd->run();

    $drushCmd = $this->task(Drush::class);
    $drushCmd->dir($absoluteDestinationDocroot);
    $drushCmd->arg('cache-rebuild');
    $drushCmd->run();

    $drushCmd = $this->task(Drush::class);
    $drushCmd->dir($absoluteDestinationDocroot);
    $drushCmd->printOutput(FALSE);
    $drushCmd
      ->arg('status')
      ->option('pipe')
      ->option('show-passwords');

    /** @var \Robo\Result $resultStatus */
    $resultStatus = $drushCmd->run();

    if ($resultStatus->wasSuccessful()) {
      $destinationSiteStatus = json_decode($resultStatus->getOutputData(), TRUE);

      $validateSite = $this->task(ValidateSite::class, $destinationSiteStatus);
      $resultValidation = $validateSite->run();

      if ($resultValidation->wasSuccessful()) {
        $this->say('Site is valid');
      }
      else {
        $this->say('Site is not valid');
      }
    }
    else {
      $this->say('Unable to fetch status.');
    }

  }

}
