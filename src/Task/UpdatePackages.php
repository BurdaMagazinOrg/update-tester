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

  /**
   * Working directory where task will be executed.
   *
   * @var string
   */
  protected $workingDirectory = '';

  /**
   * Input file name. It's statically defined to be composer.json.
   *
   * @var string
   */
  protected static $composerInputJson = 'composer.json';

  /**
   * Output file name.
   *
   * @var string
   */
  protected $composerOutputJson = 'composer.json';

  /**
   * Composer version parser helper.
   *
   * @var \Composer\Semver\VersionParser
   */
  protected $versionParser;

  /**
   * Composer version selector helper.
   *
   * @var \Composer\Package\Version\VersionSelector
   */
  protected $versionSelector;

  /**
   * Only minor versions should be updated. By default: TRUE.
   *
   * @var bool
   */
  protected $onlyMinor = TRUE;

  /**
   * List of packages that will be forced to update.
   *
   * Forced packages will be updated even if they are not in root composer.json
   * file. Alias will be used in order to force update of package.
   *
   * @var array
   */
  protected $forcedPackages = [];

  /**
   * Update packages constructor.
   */
  public function __construct() {
    $this->versionParser = new VersionParser();
    $this->versionSelector = new VersionSelector(new Pool());
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->printTaskInfo(sprintf('Execute package update in %s', $this->getWorkingDirectory()));

    $outdatedCmd = new Outdated();
    $outdatedCmd->inflect($this);
    $outdatedCmd->printOutput(FALSE);
    $outdatedCmd->dir($this->getWorkingDirectory());
    $outdatedCmd->noAnsi();

    $outdatedCmd->option('no-interaction')
      ->option('format', 'json');

    if ($this->getOnlyMinor()) {
      $this->printTaskInfo('Update only minor versions for packages.');

      $outdatedCmd->option('minor-only');
    }

    $outdatedResult = $outdatedCmd->run();

    if (!$outdatedResult->wasSuccessful()) {
      return Result::error($this, 'Unable to fetch outdated packages.');
    }

    $data = $outdatedResult->getMessage();
    if (empty($data)) {
      return Result::success($this, 'All packages are up-to-date.');
    }

    $json = json_decode($data, TRUE);
    if ($json === NULL) {
      return Result::error($this, 'Unable to process result of outdated packages.');
    }

    if (!empty($json['installed'])) {
      $outdatedPackages = array_map(
        function ($packageInfo) {
          return new OutdatedPackage($packageInfo);
        },
        $json['installed']
      );

      $this->updateJson($outdatedPackages);
    }

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

      try {
        $version = $this->versionParser->normalize($packageNewVersion);
      }
      catch (UnexpectedValueException $e) {
        $this->logger->warning(sprintf('Not supported version: %s for %s', $packageNewVersion, $packageName));

        continue;
      }

      if (!isset($require[$packageName])) {
        // If package is not defined in root composer, but it should be updated,
        // then we will set it as alias.
        if (in_array($packageName, $this->getForcedPackages())) {
          $require[$packageName] = sprintf('%s as %s', $outdatedPackage->getPackageNewVersion(), $outdatedPackage->getPackageVersion());
        }

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

      // Log new updated version for package.
      $this->say(sprintf('Version update for: %s from %s to %s. Using composer version option: %s', $packageName, $require[$packageName], $version, $newVersion));

      $require[$packageName] = $newVersion;
    }
    $jsonData['require'] = $require;

    // Check are all forced packages updated.
    $notUpdated = array_diff($this->getForcedPackages(), array_keys($require));
    if (!empty($notUpdated)) {
      $this->logger()->warning(
        sprintf('Following packages are not updated: %s', implode(', ', $notUpdated))
      );
    }

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
    return $this->getWorkingDirectory() . '/' . $this->composerOutputJson;
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

  /**
   * Set if only minor versions should be updated.
   *
   * @param bool $onlyMinor
   *   Set if only minor versions will be updated.
   */
  public function setOnlyMinor($onlyMinor) {
    $this->onlyMinor = $onlyMinor;
  }

  /**
   * Get if only minor versions should be updated.
   *
   * @return bool
   *   Returns if only minor versions should be updated.
   */
  public function getOnlyMinor() {
    return $this->onlyMinor;
  }

  /**
   * Get list of packages.
   *
   * @return array
   *   List of packages for update.
   */
  public function getForcedPackages() {
    return $this->forcedPackages;
  }

  /**
   * Set list of packages that should be updated.
   *
   * @param array $forcedPackages
   *   List of packages for update.
   */
  public function setForcedPackages(array $forcedPackages) {
    $this->forcedPackages = $forcedPackages;
  }

}
