<?php

namespace Thunder\UpdateTester\Task;

use Robo\Collection\Collection;
use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;
use Robo\Task\FileSystem\loadTasks as FileSystemTasks;
use Thunder\UpdateTester\Exec\Drush;
use Thunder\UpdateTester\Task\Drupal\CloneDatabase;
use Thunder\UpdateTester\Task\Drupal\ValidateSite;
use Thunder\UpdateTester\Task\Drupal\CloneFiles;
use Thunder\UpdateTester\Task\FileSystem\CreateDestination;
use Thunder\UpdateTester\Util\DocrootResolver;

/**
 * Task to clone drupal site.
 */
class CloneSite extends BaseTask {

  use FileSystemTasks;
  use IO;

  /**
   * Source folder.
   *
   * @var string
   */
  protected $source;

  /**
   * Destination folder.
   *
   * @var string
   */
  protected $destination;

  /**
   * Status of source site fetched by Drush.
   *
   * @var array
   */
  protected $sourceSiteStatus;

  protected $destinationSiteStatus;

  /**
   * Database configuration for destination site.
   *
   * @var array
   */
  protected $databaseSettings;

  /**
   * CloneSite constructor.
   *
   * @param string $source
   *   Source directory of site.
   * @param string $destination
   *   Destination directory for site clone.
   */
  public function __construct($source, $destination) {
    $this->source = $source;
    $this->destination = $destination;
  }

  /**
   * Get source folder as it's provided for command.
   *
   * @return string
   *   Returns source folder (can be also relative path).
   */
  protected function getSource() {
    return $this->source;
  }

  /**
   * Get destination folder as it's provided for command.
   *
   * @return string
   *   Returns destination folder (can be also relative path).
   */
  protected function getDestination() {
    return $this->destination;
  }

  /**
   * Get status of source site fetched by Drush command.
   *
   * @return array
   *   Returns array with status of source site.
   */
  protected function getSourceSiteStatus() {
    if (!isset($this->sourceSiteStatus)) {

      $drushCmd = new Drush();
      $drushCmd->inflect($this);
      $drushCmd->printOutput(FALSE);

      $drushCmd->dir(DocrootResolver::getDocroot(realpath($this->getSource())));

      $drushCmd
        ->arg('status')
        ->option('pipe')
        ->option('show-passwords');

      /** @var \Robo\Result $result */
      $result = $drushCmd->run();

      if ($result->wasSuccessful()) {
        $this->sourceSiteStatus = json_decode($result->getOutputData(), TRUE);
      }
      else {
        $this->sourceSiteStatus = [];
      }
    }

    return $this->sourceSiteStatus;
  }

  /**
   * Get status of destination site fetched by Drush command.
   *
   * @return array
   *   Returns array with status of destination site.
   */
  protected function getDestinationSiteStatus() {
    if (!isset($this->destinationSiteStatus)) {

      $drushCmd = new Drush();
      $drushCmd->inflect($this);

      $drushCmd->dir(DocrootResolver::getDocroot($this->getDestination()));

      $drushCmd
        ->arg('status')
        ->option('pipe')
        ->option('show-passwords');

      /** @var \Robo\Result $result */
      $result = $drushCmd->run();

      if ($result->wasSuccessful()) {
        $this->destinationSiteStatus = json_decode($result->getOutputData(), TRUE);
      }
      else {
        $this->destinationSiteStatus = [];
      }
    }

    return $this->destinationSiteStatus;
  }

  /**
   * Get Database configuration.
   *
   * @return array
   *   Database configuration.
   */
  protected function getDatabaseSettings() {
    if (!isset($this->databaseSettings)) {
      $status = $this->getSourceSiteStatus();
      $input = $this->input();

      // Get default options.
      $database = ($input->getOption('db-name')) ? $input->getOption('db-name') : $status['db-name'] . '_clone';
      $username = ($input->getOption('db-username')) ? $input->getOption('db-username') : $status['db-username'];
      $password = ($input->getOption('db-password')) ? $input->getOption('db-password') : $status['db-password'];

      $this->databaseSettings = [
        'db-name' => $database,
        'db-username' => $username,
        'db-password' => $password,
        'db-hostname' => $status['db-hostname'],
        'db-port' => $status['db-port'],
      ];
    }

    return $this->databaseSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->say('Starting cloning of site ...');

    $status = $this->getSourceSiteStatus();
    if (empty($status)) {
      Result::error($this, 'Unable to fetch Drupal 8 site status.');
    }

    $result = $this->collection()->run();

    if ($result->wasSuccessful()) {
      $validateClonedSite = new ValidateSite($this->getDestinationSiteStatus());
      $validateClonedSite->inflect($this);
      $validateClonedSite->setOutput($this->output());
      $validationResult = $validateClonedSite->run();

      $result->merge($validationResult);
    }

    return $result;
  }

  /**
   * Return task collection for this task.
   *
   * @return \Robo\Collection\Collection
   *   The task collection.
   */
  public function collection() {
    $collection = new Collection();

    $validateSite = new ValidateSite($this->getSourceSiteStatus());
    $validateSite->inflect($this);
    $validateSite->setOutput($this->output());
    $collection->add($validateSite);

    $createDestination = new CreateDestination($this->getDestination());
    $createDestination->inflect($this);
    $createDestination->setOutput($this->output());
    $collection->add($createDestination);

    $cloneFiles = new CloneFiles($this->getSource(), $this->getDestination());
    $cloneFiles->inflect($this);
    $cloneFiles->setOutput($this->output());
    $collection->add($cloneFiles);

    $cloneDatabase = new CloneDatabase($this->getSource(), $this->getDestination(), $this->getDatabaseSettings());
    $cloneDatabase->inflect($this);
    $cloneDatabase->setOutput($this->output());
    $collection->add($cloneDatabase);

    return $collection;
  }

}
