<?php

namespace Thunder\UpdateTester\Task\Drupal\Database;

use Robo\Common\IO;
use Robo\Task\BaseTask;
use Robo\Task\Docker\Result;

/**
 * Task to set database settings in destination site.
 *
 * @package Thunder\UpdateTester\Task\Drupal\Database
 */
class SetDatabaseSettings extends BaseTask {

  use IO;

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
   * SetDatabaseSettings constructor.
   *
   * @param string $destination
   *   Destination directory for site clone.
   * @param array $databaseSettings
   *   Database settings.
   */
  public function __construct($destination, array $databaseSettings) {
    $this->destination = $destination;
    $this->databaseSettings = $databaseSettings;
  }

  /**
   * Get file name for settings.php file.
   *
   * @return string
   *   Absolute path for settings.php file.
   */
  protected function getFileName() {
    $fileName = realpath($this->destination . '/sites/default/settings.php');

    if (!is_file($fileName)) {
      $fileName = realpath($this->destination . '/docroot/sites/default/settings.php');

      if (!is_file($fileName)) {
        return '';
      }
    }

    return $fileName;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $fileName = $this->getFileName();
    if (empty($fileName)) {
      return Result::error($this, 'Unable to find settings.php file in destination folder.');
    }

    if (!is_writable($fileName)) {
      $this->say('File settings.php in destination folder is not writable. Trying to change it to writable.');

      if (chmod($fileName, 0666) === FALSE) {
        return Result::error($this, 'Unable to set writable status for settings.php file in destination folder.');
      }
    }

    $fileStream = fopen($fileName, 'a');
    if ($fileStream === FALSE) {
      return Result::error($this, 'Unable to open settings.php file in destination folder.');
    }

    $dbSettings = $this->databaseSettings;

    fwrite($fileStream, PHP_EOL . PHP_EOL . '/*** Added by "clone-site" command of "test-scaffold" ***/' . PHP_EOL . PHP_EOL);
    fwrite($fileStream, 'if (!isset($databases)) { $databases = []; }' . PHP_EOL);
    fwrite($fileStream, '$databases[\'default\'][\'default\'] = [' . PHP_EOL);
    fwrite($fileStream, '  \'driver\' => \'mysql\',' . PHP_EOL);
    fwrite($fileStream, sprintf('  \'database\' => \'%s\',', $dbSettings['db-name']) . PHP_EOL);
    fwrite($fileStream, sprintf('  \'username\' => \'%s\',', $dbSettings['db-username']) . PHP_EOL);
    fwrite($fileStream, sprintf('  \'password\' => \'%s\',', $dbSettings['db-password']) . PHP_EOL);
    fwrite($fileStream, sprintf('  \'host\' => \'%s\',', $dbSettings['db-hostname']) . PHP_EOL);
    fwrite($fileStream, sprintf('  \'port\' => \'%s\',', $dbSettings['db-port']) . PHP_EOL);
    fwrite($fileStream, '];' . PHP_EOL);

    fclose($fileStream);

    return Result::success($this);
  }

}
