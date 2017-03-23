<?php

namespace Thunder\UpdateTester\Task\Drupal;

use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;
use Thunder\UpdateTester\Exec\Drush;

/**
 * Task to clone drupal site.
 *
 * @package Thunder\UpdateTester\Task\Drupal
 */
class CloneFiles extends BaseTask {

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
   * Constructor for CloneFiles.
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
   * {@inheritdoc}
   */
  public function run() {
    $this->printTaskInfo(sprintf('Cloning files from %s to %s', $this->source, $this->destination));

    if (!is_dir($this->source)) {
      return Result::error($this, 'Provided source folder is not valid.');
    }

    if (!is_dir($this->destination)) {
      return Result::error($this, 'Provided destination folder is not valid.');
    }

    $drushCmd = new Drush();
    $drushCmd->inflect($this);

    $drushCmd
      ->args([
        'rsync',
        realpath($this->source) . '/',
        realpath($this->destination) . '/',
      ])
      ->option('include-conf')
      ->option('include-vcs');

    return $drushCmd->run();
  }

}
