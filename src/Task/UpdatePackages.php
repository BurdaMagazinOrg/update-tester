<?php

namespace Thunder\UpdateTester\Task;

use Composer\DependencyResolver\Pool;
use Composer\Package\Package;
use Composer\Package\Version\VersionSelector;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;
use Thunder\UpdateTester\OutdatedPackage;
use Thunder\UpdateTester\Task\Composer\Outdated;
use UnexpectedValueException;

/**
 * Task to update composer packages for project.
 *
 * @package Thunder\UpdateTester\Task
 */
class UpdatePackages extends BaseTask {

  use IO;

  protected $workingDirectory = '';

  protected static $composerInputJson = 'composer.json';

  protected $composerOutputJson = 'composer.json';

  protected $versionParser;
  protected $versionSelector;

  /**
   * UpdatePackages constructor.
   */
  public function __construct() {
    $this->versionParser = new VersionParser();
    $this->versionSelector = new VersionSelector(new Pool());
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $outdatedCmd = new Outdated();
    $outdatedCmd->inflect($this);
    $outdatedCmd->dir($this->getWorkingDirectory());
    $outdatedCmd->noAnsi();

    $outdatedCmd->option('minor-only')
      ->option('direct')
      ->option('no-interaction');
    $outdatedResult = $outdatedCmd->run();

    if (!$outdatedResult->wasSuccessful()) {
      return Result::error($this, 'Unable to fetch outdated packages.');
    }

    $data = $outdatedResult->getOutputData();
    if (empty($data)) {
      return Result::success($this, 'All packages are up-to-date.');
    }

    $outdatedPackages = [];
    foreach (explode(PHP_EOL, $data) as $dataRow) {
      if (empty($dataRow)) {
        continue;
      }

      $outdatedPackages[] = new OutdatedPackage($dataRow);
    }

    $this->updateJson($outdatedPackages);

    return $outdatedResult;
  }

  /**
   * Update and output new composer json file.
   *
   * @param array $outdatedPackages
   *   List of packages that should be updated. Take look at ::getVersions.
   * @param bool $useRecommended
   *   Use recommended version instead of full version.
   */
  protected function updateJson(array $outdatedPackages, $useRecommended = TRUE) {
    $jsonData = json_decode(file_get_contents(realpath($this->getWorkingDirectory() . '/' . static::$composerInputJson)), TRUE);

    $require = $jsonData['require'];
    /** @var \Thunder\UpdateTester\OutdatedPackage $outdatedPackage */
    foreach ($outdatedPackages as $outdatedPackage) {
      $packageName = $outdatedPackage->getPackageName();
      $packageNewVersion = $outdatedPackage->getPackageNewVersion();

      if (!isset($require[$packageName])) {
        $this->logger->warning(sprintf('No package found: %s', $packageName));

        continue;
      }

      try {
        $version = $this->versionParser->normalize($packageNewVersion);
      }
      catch (UnexpectedValueException $e) {
        $this->logger->warning(sprintf('Not supported version: %s for %s', $packageNewVersion, $packageName));

        continue;
      }

      if (!Comparator::greaterThan($version, $require[$packageName])) {
        $this->logger->warning(sprintf('Version is not bigger for: %s from %s to %s', $packageName, $require[$packageName], $packageNewVersion));

        continue;
      }

      $package = new Package($packageName, $version, $version);
      if ($useRecommended) {
        $newVersion = $this->versionSelector->findRecommendedRequireVersion($package);
      }
      else {
        $newVersion = $version;
      }

      $require[$packageName] = $newVersion;

      // Log new updated version for package.
      $this->say(sprintf('Version update for: %s from %s to %s. Using composer version option: %s', $packageName, $require[$packageName], $version, $newVersion));
    }
    $jsonData['require'] = $require;

    file_put_contents($this->getComposerOutputJson(), json_encode($jsonData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }

  /**
   * Set output composer json file.
   *
   * @param string $composerOutputJson
   *   New composer output json file.
   */
  public function setComposerOutputJson($composerOutputJson) {
    $this->composerOutputJson = $composerOutputJson;
  }

  /**
   * Get full path to composer.json file.
   *
   * @return string
   *   Returns full path to composer.json file.
   */
  public function getComposerOutputJson() {
    return realpath($this->getWorkingDirectory() . '/' . $this->composerOutputJson);
  }

  /**
   * Get full path to working directory.
   *
   * @return string
   *   Returns full path to set working directory.
   */
  public function getWorkingDirectory() {
    if (empty($this->workingDirectory)) {
      $this->workingDirectory = getcwd();
    }

    return realpath($this->workingDirectory);
  }

  /**
   * Set working directory (it can be relative or absolute).
   *
   * @param string $workingDirectory
   *   Relative or absolute path to working directory.
   */
  public function setWorkingDirectory($workingDirectory) {
    $this->workingDirectory = $workingDirectory;
  }

}
