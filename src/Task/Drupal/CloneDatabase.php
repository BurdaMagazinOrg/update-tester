<?php

namespace Thunder\UpdateTester\Task\Drupal;

use Robo\Collection\Collection;
use Robo\Common\IO;
use Robo\Task\Base\Exec;
use Robo\Task\BaseTask;
use Robo\Task\Docker\Result;
use Thunder\UpdateTester\Exec\Drush;
use Thunder\UpdateTester\Task\Drupal\Database\CreateDatabaseRequirements;
use Thunder\UpdateTester\Task\Drupal\Database\SetDatabaseSettings;
use Thunder\UpdateTester\Util\DocrootResolver;

/**
 * Database cloning task.
 *
 * @package Thunder\UpdateTester\Task\Drupal
 */
class CloneDatabase extends BaseTask {

  use IO;

  /**
   * Source folder.
   *
   * @var string
   */
  protected $source;

  /**
   * Destination directory for site.
   *
   * @var string
   */
  protected $destination;

  /**
   * Database settings.
   *
   * @var array
   */
  protected $databaseSettings;

  /**
   * Destination docroot directory.
   *
   * @var string
   */
  protected $destinationDocroot;

  /**
   * Create task for cloning of database.
   *
   * @param string $source
   *   Source directory of site.
   * @param string $destination
   *   Destination directory for site clone.
   * @param array $databaseSettings
   *   Database settings.
   */
  public function __construct($source, $destination, array $databaseSettings) {
    $this->source = $source;
    $this->destination = $destination;
    $this->databaseSettings = $databaseSettings;
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
   * Get database dumo file name.
   *
   * @return string
   *   File used to dump database.
   */
  protected function getDumpFileName() {
    return realpath($this->destination) . '/db-dump.sql';
  }

  /**
   * Return docroot of destination site.
   *
   * @return string
   *   Docroot to destination site.
   */
  protected function getDocRoot() {
    if (isset($this->destinationDocroot)) {
      return $this->destinationDocroot;
    }

    $this->destinationDocroot = DocrootResolver::getDocroot($this->destination);

    return $this->destinationDocroot;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (empty($this->getDocRoot())) {
      return Result::error($this, 'Unable to get destination docroot.');
    }

    $this->say('Cloning database ... ');

    return $this->collection()->run();
  }

  /**
   * Return task collection for this task.
   *
   * @return \Robo\Collection\Collection
   *   The task collection.
   */
  public function collection() {
    $collection = new Collection();

    $setDatabaseSettings = new SetDatabaseSettings($this->destination, $this->databaseSettings);
    $setDatabaseSettings->inflect($this);
    $setDatabaseSettings->setOutput($this->output());
    $collection->add($setDatabaseSettings);

    $createDatabaseRequirements = new CreateDatabaseRequirements($this->databaseSettings);
    $createDatabaseRequirements->inflect($this);
    $createDatabaseRequirements->setOutput($this->output());
    $collection->add($createDatabaseRequirements);

    $dumpFile = $this->getDumpFileName();
    $drushCmd = new Drush();
    $drushCmd->inflect($this);
    $drushCmd->arg('sql-dump')
      ->arg('--structure-tables-key=common')
      ->arg('--result-file=' . $dumpFile);
    $drushCmd->dir(DocrootResolver::getDocroot(realpath($this->getSource())));
    $collection->add($drushCmd);

    $importDatabase = new Exec(sprintf('drush --yes sql-cli < %s', $dumpFile));
    $importDatabase->inflect($this);
    $importDatabase->dir(realpath($this->getDocRoot()));
    $collection->add($importDatabase);

    return $collection;
  }

}
