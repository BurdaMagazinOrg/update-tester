<?php

namespace Thunder\UpdateTester\Task\Drupal;

use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Task to validate drupal site based on provided status.
 *
 * @package Thunder\UpdateTester\Task\Drupal
 */
class ValidateSite extends BaseTask {

  use IO;

  protected $siteStatus;

  /**
   * ValidateSite constructor.
   *
   * @param array $siteStatus
   *   Status of site fetched over drush command.
   */
  public function __construct(array $siteStatus) {
    $this->siteStatus = $siteStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->say('Checking site status for: ' . $this->siteStatus['root']);

    // Check that configuration is correct and Drupal 8 site is installed.
    if (empty($this->siteStatus['bootstrap'])) {
      return Result::error($this, 'Site status is not valid.');
    }

    $this->say('Site is valid.');

    return Result::success($this);
  }

}
