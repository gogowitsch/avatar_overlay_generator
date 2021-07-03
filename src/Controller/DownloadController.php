<?php

namespace Drupal\avatar_overlay_generator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Returns the resulting picture for the Avatar overlay generator.
 */
class DownloadController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build($file, $name, $result) {
    // Delete most nun-alphanumeric chars from URL data - we don't trust user-supplied data.
    $name = preg_replace('/[^-a-z_0-9.]/i', '', $name);
    $result = preg_replace('/[^-a-z_0-9.]/i', '', $result);
    $file = preg_replace('/[^-a-z_0-9.]/i', '', $file);

    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $tmp_png_file = $file_system->getTempDirectory() . '/' . $file;
    $file_name = substr($name, 0, -4) . "-$result.png";
    $headers = [
      'Content-Type' => 'image/png',
      'Content-Length' => filesize($tmp_png_file),
      'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
    ];
    $response = new BinaryFileResponse($tmp_png_file, 200, $headers);

    return $response;
  }

}
