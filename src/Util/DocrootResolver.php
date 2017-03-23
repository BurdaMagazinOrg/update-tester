<?php

namespace Thunder\UpdateTester\Util;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Helper class to resolve docroot directory based on provided site directory.
 */
class DocrootResolver {

  /**
   * Common docroot names used for site.
   *
   * @var array
   */
  protected static $commonDocroot = ['docroot', 'www', 'html'];

  /**
   * Filtering options for drupal/core package.
   *
   * @var array
   */
  protected static $drupalCorePackageFilters = [
    'type:drupal-core',
    'drupal/core',
  ];

  /**
   * Reference file.
   *
   * It's used to determine if directory with existence of it, actually site
   * docroot directory.
   *
   * @var string
   */
  protected static $docrootReferenceFile = 'autoload.php';

  /**
   * Get docroot directory for provided site directory.
   *
   * @param string $siteDirectory
   *   Site directory, can be provided as relative or absolute path.
   *
   * @return string
   *   Returns absolute path to docroot directory.
   */
  public static function getDocroot($siteDirectory) {
    $docRoot = realpath($siteDirectory);

    if (!is_dir($docRoot)) {
      throw new RuntimeException('Provided site directory is not valid.');
    }

    // Check if provided site directory is used as docroot.
    if (is_file($docRoot . '/' . static::$docrootReferenceFile)) {
      return $docRoot;
    }

    // Resolve docroot using information form composer.json.
    if (is_file($docRoot . '/composer.json')) {
      $jsonData = json_decode(file_get_contents($docRoot . '/composer.json'), TRUE);

      if ($jsonData && !empty($jsonData['extra']['installer-paths'])) {
        foreach ($jsonData['extra']['installer-paths'] as $path => $filters) {
          if (!empty(array_intersect(static::$drupalCorePackageFilters, $filters))) {
            $composerBasePath = strstr($path, '/core', TRUE);

            // Skip composer resolving if path '/core' is not found.
            if (!$composerBasePath) {
              break;
            }

            // Check if provided docroot path is absolute and use it.
            $fileSystem = new Filesystem();
            if ($fileSystem->isAbsolutePath($composerBasePath)) {
              $composerBasedDocroot = realpath($composerBasePath);
            }
            else {
              $composerBasedDocroot = realpath($docRoot . '/' . strstr($path, '/core', TRUE));
            }

            // Validate docroot resolved over composer.json information.
            if (is_file($composerBasedDocroot . '/' . static::$docrootReferenceFile)) {
              return $composerBasedDocroot;
            }

            // Even if docroot is not properly resolved over composer.json
            // information, it's found by filters provided for core. That's why
            // there is no point checking other provided install paths.
            break;
          }
        }
      }
    }

    // Try to resolve docroot based on common docroot names.
    foreach (static::$commonDocroot as $commonDocroot) {
      $commonBasedDocroot = realpath($docRoot . '/' . $commonDocroot);

      if (is_file($commonBasedDocroot . '/' . static::$docrootReferenceFile)) {
        return $commonBasedDocroot;
      }
    }

    throw new RuntimeException('Unable to resolve docroot for provided site directory.');
  }

}
