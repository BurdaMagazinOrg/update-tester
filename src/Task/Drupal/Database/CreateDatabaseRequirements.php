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
    $this->say('Creating database requirements ... ');

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

    // Create user required to authenticated.
    $mysqlCmd = new MySQL();
    $mysqlCmd->inflect($this);
    $mysqlCmd
      ->option('execute', sprintf('CREATE USER \'%s\'@\'localhost\' IDENTIFIED BY \'%s\';', $dbSettings['db-username'], $dbSettings['db-password']))
      ->option('user', 'root');

    // Result is ignored here, because command will fail if user already exists.
    $mysqlCmd->run();

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
