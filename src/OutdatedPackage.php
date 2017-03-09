<?php

namespace Thunder\UpdateTester;

/**
 * Outdated package information (contains current version, new versions)
 *
 * Composer formating of outdated packages looks like following:
 * 1. 42 characters are package name
 * 2. 20 characters are current package version
 * 3. 20 characters are new package version
 * 4. rest of row is package description.
 */
class OutdatedPackage {

  /**
   * Package name.
   *
   * @var string
   */
  protected $packageName;

  /**
   * Package current version.
   *
   * @var string
   */
  protected $packageVersion;

  /**
   * Package new version.
   *
   * @var string
   */
  protected $packageNewVersion;

  /**
   * OutdatedPackage constructor.
   *
   * @param array $packageInfo
   *   Outdated package information provided by composer command.
   */
  public function __construct(array $packageInfo) {
    $this->packageName = $packageInfo['name'];
    $this->packageVersion = $packageInfo['version'];
    $this->packageNewVersion = $packageInfo['latest'];
  }

  /**
   * Create instance of class.
   *
   * @param array $packageInfo
   *   Outdated package information provided by composer command.
   *
   * @return \Thunder\UpdateTester\OutdatedPackage
   *   Returns instance of class.
   */
  public static function create(array $packageInfo) {
    return new static($packageInfo);
  }

  /**
   * Get composer package name.
   *
   * @return string
   *   Returns package name in format: vendor/package.
   */
  public function getPackageName() {
    return $this->packageName;
  }

  /**
   * Get current package version.
   *
   * @return string
   *   Returns current versions for package.
   */
  public function getPackageVersion() {
    return $this->packageVersion;
  }

  /**
   * Get new version of package.
   *
   * @return string
   *   Returns new available version of package.
   */
  public function getPackageNewVersion() {
    return $this->packageNewVersion;
  }

  /**
   * Magic method to return string representation of class.
   *
   * @return string
   *   Return package name with current and new version.
   */
  public function __toString() {
    return sprintf('%s (%s -> %s)', $this->packageName, $this->packageVersion, $this->packageNewVersion);
  }

}
