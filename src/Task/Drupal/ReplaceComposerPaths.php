<?php

namespace Thunder\UpdateTester\Task\Drupal;

use Robo\Common\IO;
use Robo\Task\BaseTask;
use Robo\Task\Docker\Result;

/**
 * Task to replace install paths in composer.json.
 *
 * @package Thunder\UpdateTester\Task\Composer
 */
class ReplaceComposerPaths extends BaseTask {

  use IO;

  /**
   * Path to composer.json file.
   *
   * @var string
   */
  protected $composerFile;

  /**
   * Install path that should be replaced.
   *
   * @var string
   */
  protected $fromPath;

  /**
   * Install path that should be set.
   *
   * @var string
   */
  protected $toPath;

  /**
   * Constructor for replace install paths task.
   *
   * @param string $composerFile
   *   Composer file path.
   * @param string $fromPath
   *   Path that should be replaced from.
   * @param string $toPath
   *   Path that should be replaced to.
   */
  public function __construct($composerFile, $fromPath, $toPath) {
    $this->composerFile = $composerFile;
    $this->fromPath = $fromPath;
    $this->toPath = $toPath;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $composerFile = realpath($this->composerFile);
    $fromPath = $this->fromPath;
    $toPath = $this->toPath;

    $this->printTaskInfo(sprintf('Replacing install paths in composer file %s', $composerFile));

    $jsonData = json_decode(file_get_contents($composerFile), TRUE);
    if (empty($jsonData) || empty($jsonData['extra']['installer-paths'])) {
      return Result::error($this, sprintf('Unable to get data from %s', $composerFile));
    }

    // Adjust install paths.
    $this->say(sprintf('Change install path from %s to %s.', $fromPath, $toPath));
    $installPaths = $jsonData['extra']['installer-paths'];
    foreach (array_keys($installPaths) as $path) {
      if (strpos($path, $fromPath) === 0) {
        $newPath = $toPath . substr($path, strlen($fromPath));

        $this->say(sprintf('Changing %s into %s.', $path, $newPath));

        $installPaths[$newPath] = $installPaths[$path];
        unset($installPaths[$path]);
      }
    }
    $jsonData['extra']['installer-paths'] = $installPaths;

    file_put_contents($composerFile, json_encode($jsonData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return Result::success($this, sprintf('Composer file %s successfully modified with new install paths.', $composerFile));
  }

}
