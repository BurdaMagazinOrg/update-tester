<?php

namespace Thunder\UpdateTester\Task\Drupal\Database;

use Robo\Common\IO;
use Robo\Task\BaseTask;
use Robo\Task\Docker\Result;
use Thunder\UpdateTester\Exec\MySQL;

/**
 * Task to create database requirements.
 *
 * @package Thunder\UpdateTester\Task\Drupal\Database
 */
class CreateDatabaseRequirements extends BaseTask {

  use IO;

  /**
   * Database settings.
   *
   * @var array
   */
  protected $databaseSettings;

  /**
   * CreateDatabaseRequirements constructor.
   *
   * @param array $databaseSettings
   *   Database settings.
   */
  public function __construct(array $databaseSettings) {
    $this->databaseSettings = $databaseSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->printTaskInfo('Creating database requirements (database, user, grants)');

    $dbSettings = $this->databaseSettings;

    // Create new database.
    $mysqlCmd = new MySQL();
    $mysqlCmd->inflect($this);
    $mysqlCmd
      ->option('execute', sprintf('CREATE DATABASE %s;', $dbSettings['db-name']))
      ->option('user', 'root');
    $result = $mysqlCmd->run();
    if (!$result->wasSuccessful()) {
      return $result;
    }

    // Check if user already exists.
    $mysqlCmd = new MySQL();
    $mysqlCmd->inflect($this);
    $mysqlCmd
      ->option('silent')
      ->option('execute', sprintf('SELECT count(*) AS num_of_users FROM mysql.user WHERE user=\'%s\' AND host=\'localhost\';', $dbSettings['db-username']))
      ->option('user', 'root');
    $result = $mysqlCmd->run();

    // Create user required to authenticate, if it doesn't already exist.
    if (trim($result->getOutputData()) === '0') {
      $mysqlCmd = new MySQL();
      $mysqlCmd->inflect($this);
      $mysqlCmd
        ->option('execute', sprintf('CREATE USER \'%s\'@\'localhost\' IDENTIFIED BY \'%s\';', $dbSettings['db-username'], $dbSettings['db-password']))
        ->option('user', 'root');
      $mysqlCmd->run();
    }

    // Grant privileges for user.
    $mysqlCmd = new MySQL();
    $mysqlCmd->inflect($this);
    $mysqlCmd
      ->option('execute', sprintf('GRANT ALL ON %s.* TO \'%s\'@\'localhost\';', $dbSettings['db-name'], $dbSettings['db-username']))
      ->option('user', 'root');
    $result = $mysqlCmd->run();
    if (!$result->wasSuccessful()) {
      return $result;
    }

    return Result::success($this);
  }

}
