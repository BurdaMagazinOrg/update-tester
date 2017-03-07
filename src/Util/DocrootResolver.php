<?php

namespace Thunder\UpdateTester\Util;

use RuntimeException;

/**
 * Helper class to resolve docroot folder based on provided site folder.
 */
class DocrootResolver {

  /**
   * Get docroot folder for provided site folder.
   *
   * @param string $siteDirectory
   *   Site directory, can be provided as relative or absolute path.
   *
   * @return string
   *   Returns absolute path to docroot folder.
   */
  public static function getDocroot($siteDirectory) {
    $docRoot = realpath($siteDirectory);

    if (!is_dir($docRoot)) {
      throw new RuntimeException('Unable to resolve docroot for provided site folder.');
    }

    if (!is_file($docRoot . '/autoload.php')) {
      $docRoot = realpath($docRoot . '/docroot');

      if (!is_file($docRoot . '/autoload.php')) {
        throw new RuntimeException('Unable to resolve docroot for provided site folder.');
      }
    }

    return $docRoot;
  }

}
