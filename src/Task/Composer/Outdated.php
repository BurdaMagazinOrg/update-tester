<?php

namespace Thunder\UpdateTester\Task\Composer;

use Robo\Task\Composer\Base;

/**
 * Task to execute composer outdated command.
 *
 * @package Thunder\UpdateTester\Task\Composer
 */
class Outdated extends Base {

  /**
   * {@inheritdoc}
   */
  protected $action = 'outdated';

  /**
   * {@inheritdoc}
   */
  public function run() {
    $command = $this->getCommand();

    return $this->executeCommand($command);
  }

}
