<?php

namespace Thunder\UpdateTester\Exec;

use Robo\Task\Base\Exec;

/**
 * Drush command execution wrapper.
 */
class MySQL extends Exec {

  /**
   * Drush constructor.
   */
  public function __construct() {
    parent::__construct('mysql');

    $this->printed(FALSE);
  }

}
