<?php

namespace Thunder\UpdateTester\Task\Drupal;

use Robo\Common\IO;
use Robo\Task\BaseTask;
use Robo\Task\Docker\Result;

/**
 * Task to adjust autoload.php in Drupal docroot directory.
 *
 * @package Thunder\UpdateTester\Task\Composer
 */
class SetAutoload extends BaseTask {

  use IO;

  /**
   * Drupal docroot directory.
   *
   * @var string
   */
  protected $docrootDir;

  /**
   * Project directory (where composer.json) is located.
   *
   * @var string
   */
  protected $projectDir;

  /**
   * Constructor of Drupal set autoload task.
   *
   * @param string $docrootDir
   *   Drupal docroot directory.
   * @param string $projectDir
   *   Project directory (where composer.json) is located.
   */
  public function __construct($docrootDir, $projectDir) {
    $this->docrootDir = $docrootDir;
    $this->projectDir = $projectDir;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $autoloadFile = realpath($this->docrootDir . '/autoload.php');
    $projectDir = realpath($this->projectDir);

    $this->printTaskInfo(sprintf('Changing docroot autoload file %s to use project at %s', $autoloadFile, $projectDir));

    if (!is_file($autoloadFile)) {
      Result::error($this, sprintf('Autoload file %s is not valid.', $autoloadFile));
    }

    // This will be new content of Drupal docroot autoload.php file. It will
    // include composer autoload.php file.
    $content = '<?php' . PHP_EOL . PHP_EOL . sprintf('return require \'%s/vendor/autoload.php\';', $projectDir);
    file_put_contents($autoloadFile, $content);

    return Result::success($this, sprintf('Autoload file %s is successfully modified.', $autoloadFile));
  }

}
