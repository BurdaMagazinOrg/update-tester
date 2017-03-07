<?php

namespace Thunder\UpdateTester\Task\FileSystem;

use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;
use Robo\Task\Filesystem\FilesystemStack;

/**
 * Task to crate destination folder.
 *
 * @package Thunder\UpdateTester\Task\FileSystem
 */
class CreateDestination extends BaseTask {

  use IO;

  protected $destination;

  /**
   * Constructor for destination folder creation.
   *
   * @param string $destination
   *   Destination directory that should be created.
   */
  public function __construct($destination) {
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (is_dir($this->destination)) {
      return Result::error($this, 'Destination directory already exists.');
    }

    $this->say('Creating destination directory ...');
    return (new FilesystemStack())
      ->inflect($this)
      ->mkdir($this->destination)
      ->run();
  }

}
