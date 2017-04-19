<?php

namespace Thunder\UpdateTester\Exec;

use Robo\Task\Base\Exec;

/**
 * Drush command execution wrapper.
 */
class Drush extends Exec {

  /**
   * Drush constructor.
   */
  public function __construct() {
    parent::__construct('drush');

    $this->printOutput(FALSE);

    $this->option('yes');
  }

}
