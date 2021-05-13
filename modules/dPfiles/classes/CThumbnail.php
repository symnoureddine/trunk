<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Exception;
use Imagine\Gd;
use Imagine\Image;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Imagick;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbPath;
use Ox\Core\Module\CModule;
use Ox\Import\Osoft\COsoftDossier;
use Ox\Import\Osoft\COsoftHistorique;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\CompteRendu\CCompteRendu;

/**
 * Image manipulation class using Imagine with Imagick or GD
 */
abstract class CThumbnail implements IShortNameAutoloadable {
  const IMG_NOT_FOUND = 'images/pictures/notfound.png';
  const IMG_ACCESS_DENIED = 'images/pictures/accessdenied.png';
  const IMG_DRAFT = 'modules/drawing/images/draft.png';
  const IMG_DEFAULT = 'images/pictures/medifile.png';
  const JPEG_QUALITY = 80;

  static $img_not_found = self::IMG_NOT_FOUND;
  static $img_access_denied = self::IMG_ACCESS_DENIED;
  static $img_draft = self::IMG_DRAFT;
  static $img_default = self::IMG_DEFAULT;

  protected static $tmp_files = array();

  public static $profiles = array(
    'small'  => array(
      'w'         => 120,
      'h'         => 120,
      'display_w' => 60,
      'display_h' => 60,
      'dpi'       => 25,
    ),
    'medium' => array(
      'w'         => 400,
      'h'         => 400,
      'display_w' => 200,
      'display_h' => 200,
      'dpi'       => 75,
    ),
    'large'  => array(
      'w'         => 1200,
      'h'         => 1200,
      'display_w' => 600,
      'display_h' => 600,
      'dpi'       => 200,
    ),
  );

  public static $quality = array(
    "jpeg" => array(
      'low'    => 20,
      'medium' => 50,
      'high'   => 80,
      'full'   => 100,
    ),
    "png"  => array(
      'low'    => 2,
      'medium' => 5,
      'high'   => 7,
      'full'   => 9,
    ),
  );

  /**
   * Get a thumbnail path
   *
   * @param CFile  $file    The file we want a thumb of
   * @param string $engine  The engine to use : true = Imagick, false = Gd
   * @param string $profile CThumbnail::$profiles to use
   * @param int    $page    Page to get thumbnail of
   * @param bool   $crop    Crop the thumbnail to fit the size
   * @param int    $quality Image quality
   *
   * @return string Thumbnail path
   */
  static function getThumbnailPath($file, $engine, $profile = 'medium', $page = 1, $crop = false, $quality = 80) {
    $thumbpath = CAppUI::conf('dPfiles CFile thumbnails_directory');

    // Set the path for the thumbnail directory
    if (!$thumbpath) {
      $thumbpath = CAppUI::conf('root_dir') . '/tmp/phpthumb/';
    }
    else {
      $thumbpath = rtrim($thumbpath, '/') . '/';
    }

    if ($page) {
      $page -= 1;
    }

    // Path of the thumbnail
    $tmp_path = self::getFileTmpPath($file, $thumbpath, $profile);

    $tmp_file_path = "{$tmp_path}p{$page}-c{$crop}-q{$quality}";

    $ext = 'jpeg';

    $tmp_file_path .= ".{$ext}";

    // If thumbnail doesn't exist create it
    if (!file_exists($tmp_file_path) || filemtime($file->_file_path) >= filemtime($tmp_file_path)) {
      $success = self::createThumb($engine, $tmp_file_path, $file, $profile, $page, $crop, $quality);
      if (!$success) {
        // If thumbnail can't be show, display the default image instead
        CApp::rip();
      }
    }

    return $tmp_file_path;
  }

  /**
   * Create a thumbnail from a file.
   *
   * @param string $engine        The engine to use for the render : true = Imagick, false = Gd
   * @param string $tmp_file_path The location of the resulting file
   * @param CFile  $file          File to convert to thumb
   * @param string $profile       Image profile to use
   * @param int    $page          For PDF the page number to convert
   * @param bool   $crop          Crop the thumbnail to fit the size
   * @param int    $quality       Image quality
   *
   * @return bool
   */
  static function createThumb($engine, $tmp_file_path, $file, $profile, $page = 0, $crop = false, $quality = 80) {
    // Purge thumbnails
    CApp::doProbably(
      100,
      function () {
        CFile::purgeThumbnails(100);
      }
    );

    $imagine = null;
    try {
      $imagine = ($engine) ? new Imagick\Imagine() : new Gd\Imagine();

      // Put the page number at the end of the file path
      $file_path = ($page !== null) ? "{$file->_file_path}[$page]" : $file->_file_path;
      $image     = self::openFile($imagine, $file, $file_path, $tmp_file_path, $page, $profile);
      //$image     = $imagine->open($file_path);

      $mode = ImageInterface::THUMBNAIL_INSET;

      $width  = self::$profiles[$profile]["w"];
      $height = self::$profiles[$profile]["h"];

      $options = array(
        'jpeg_quality' => $quality,
        'format'       => 'jpeg',
      );

      $size     = new Box($width, $height);
      $src_size = $image->getSize();

      $palette    = new Image\Palette\RGB();
      $background = new Image\Palette\Color\RGB($palette, array(255, 255, 255), 100);
      $top_left   = new Image\Point(0, 0);
      $canvas     = $imagine->create(new Box($src_size->getWidth(), $src_size->getHeight()), $background);

      if ($crop) {
        $crop_infos = self::getCropPoint($src_size->getWidth(), $src_size->getHeight());
        $image      = $image->crop($crop_infos['start_point'], $crop_infos['crop_box']);

        $mode   = ImageInterface::THUMBNAIL_OUTBOUND;
        $canvas = $imagine->create($crop_infos['crop_box'], $background);
      }

      if (!$engine && version_compare(PHP_VERSION, '7.0.0') >= 0) {
        $width  = ($width <= $src_size->getWidth()) ? $width : $src_size->getWidth();
        $height = ($height <= $src_size->getHeight()) ? $height : $src_size->getHeight();

        if ($src_size->getWidth() > $src_size->getHeight()) {
          $ratio  = $src_size->getWidth() / $src_size->getHeight();
          $height = $width / $ratio;
        }
        elseif ($src_size->getWidth() < $src_size->getHeight()) {
          $ratio = $src_size->getHeight() / $src_size->getWidth();
          $width = $height / $ratio;
        }

        $size = new Box($width, $height);

        // GD is having a bug with the thumbnail function, using resize instead
        $canvas->paste($image, $top_left)
          ->resize($size)
          ->interlace(ImageInterface::INTERLACE_PLANE)
          ->save($tmp_file_path, $options);
      }
      else {
        $canvas->paste($image, $top_left)
          ->thumbnail($size, $mode)
          ->interlace(ImageInterface::INTERLACE_PLANE)
          ->save($tmp_file_path, $options);
      }
    }
    catch (Exception $e) {
      static::removeTmpFiles();
      self::buildHeaders();
      // If an error occure display the medifile image
      $imagine->open(CAppUI::conf('root_dir') . '/' . self::$img_default)->show('png');

      return false;
    }

    static::removeTmpFiles();
    return true;
  }

  /**
   * Get the crop starting point
   *
   * @param int $src_width  Width of the source image
   * @param int $src_height Height of the source image
   *
   * @return array
   */
  static function getCropPoint($src_width, $src_height) {
    if ($src_width > $src_height) {
      $start_y   = 0;
      $diff      = $src_width - $src_height;
      $start_x   = max($diff / 2, 0);
      $src_width -= $diff;
    }
    elseif ($src_height > $src_width) {
      $start_x    = 0;
      $diff       = $src_height - $src_width;
      $start_y    = max($diff / 2, 0);
      $src_height -= $diff;
    }
    else {
      $start_x = 0;
      $start_y = 0;
    }

    return array(
      'start_point' => new Image\Point($start_x, $start_y),
      'crop_box'    => new Box($src_width, $src_height)
    );
  }

  /**
   * Create the path for the thumbnail
   *
   * @param CFile  $file    Hash of the file
   * @param string $path    Path to Mediboard tmp dir
   * @param string $profile Profile used
   *
   * @return string
   */
  static function getFileTmpPath($file, $path, $profile) {
    $path .= intval($file->_id / 1000) . "/{$file->_id}/{$profile}/";
    CMbPath::forceDir($path);

    return $path;
  }

  /**
   * Make a thumbnail from a CFile or a CCompteRendu
   *
   * @param int    $document_id    CDocumentItem ID of the document
   * @param string $document_class Class of the document (CFile|CCompteRendu)
   * @param string $profile        Profile to use for the thumbnail
   * @param int    $page           Page of the document to display a thumbnail of
   * @param int    $rotate         Rotation of the document
   * @param bool   $crop           Crop the thumbnail to fit
   * @param string $quality        JPEG quality to display
   * @param string $perm_callback  Function to check perms
   * @param bool   $show           Display thumbnail or get the thumbnail
   *
   * @return mixed|void
   */
  static function makeThumbnail(
      $document_id, $document_class = 'CFile', $profile = 'medium', $page = null, $rotate = 0, $crop = false, $quality = 'high',
      $perm_callback = null, $show = true
  ) {
    $root_dir   = CAppUI::conf('root_dir');
    $file       = null;
    $error_path = '';

    try {
      if (!static::checkImagineExists()) {
        throw new Exception();
      }

      $imagine = static::getEngineInstance();

      $file = self::createFileForThumb($document_id, $document_class);

      if (is_string($file)) {
        $error_path = $file;
        $file       = null;
      }

      // Check the rights, file_exists and if the file is a draft
      // Show image and call CApp::rip() if a condition is validated.
      if ($error_path = self::handleFileErrors($file, $error_path, $perm_callback)) {
        if ($show) {
          ob_clean();
          self::buildHeaders();
          $imagine->open($root_dir . '/' . $error_path)->show('png');
          CApp::rip();
        }
        else {
          return $imagine->open($root_dir . '/' . $error_path)->get('png');
        }
      }

      // If the file is a svg display it
      if ($file && strpos($file->file_type, 'svg') !== false) {
        if ($show) {
          header('Content-type: image/svg+xml');
          $last_modify = filemtime($file->_file_path);
          self::buildHeaders($file->_file_path, $last_modify);
          readfile($file->_file_path);
          CApp::rip();
        }
        else {
          return readfile($file->_file_path);
        }
      }

      // If the file is not an image or a pdf try convert it to pdf
      if (strpos($file->file_type, 'image') === false && strpos($file->file_type, 'pdf') === false && $file->isPDFconvertible()) {
        $file = self::convertFileToPdf($file);
      }

      // Display the image
      $last_modify = filemtime($file->_file_path);

      $engine = ($imagine instanceof Imagick\Imagine);

      $quality       = self::$quality['jpeg'][$quality];
      $tmp_file_path = self::getThumbnailPath($file, $engine, $profile, $page, $crop, $quality);

      $ext   = 'jpeg';
      $image = $imagine->open($tmp_file_path)->interlace(ImageInterface::INTERLACE_PLANE);

      $rotate = $rotate ?: $file->rotation;
      if ($rotate != 0) {
        // If rotation reset the last modified time
        $image->rotate($rotate);
        $last_modify = time();
      }
      else {
        // Vérification de la date de modification du champ "roration"
        if ($file->date_rotation && strtotime($file->date_rotation) > $last_modify) {
          $last_modify = time();
        }
      }

      self::buildHeaders($tmp_file_path, $last_modify);

      $options = array('jpeg_quality' => $quality);

      if ($show) {
        $image->show($ext, $options);
      }
      else {
        return $image->get($ext, $options);
      }

      CApp::rip();
    }
    catch (Exception $e) {
      header('Content-type: image/jpg');
      readfile($root_dir . '/' . self::$img_default);

      CApp::rip();
    }
  }

  /**
   * Make a thumbnail from a CFile or a CCompteRendu
   *
   * @param int    $document_id    CDocumentItem ID of the document
   * @param string $document_class Class of the document (CFile|CCompteRendu)
   * @param int    $page           Page of the document to display a thumbnail of
   * @param int    $disposition    Force file download (1) or not (0) only used with $thumb=0
   * @param bool   $download_raw   Download the raw file (for Osoft)
   * @param int    $length         Number of pages to slice
   *
   * @return mixed|void
   * @throws Exception
   */
  static function makePreview(
      $document_id, $document_class = 'CFile', $page = null, $disposition = 0, $download_raw = false, $length = null
  ) {
    // Direct download of the file
    // BEGIN extra headers to resolve IE caching bug (JRP 9 Feb 2003)
    // [http://bugs.php.net/bug.php?id=16173]
    header("Pragma: ");
    header("Cache-Control: ");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");  //HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    // END extra headers to resolve IE caching bug

    header("MIME-Version: 1.0");
    if ($document_class == 'CFile') {
      $file = new CFile();
      $file->load($document_id);

      if (!$file->getPerm(CPermObject::READ)) {
        CApp::rip();
      }

      $disposition = ($disposition) ? 'attachment' : 'inline';

      if ($download_raw || ($file->file_type != 'application/osoft' && $file->file_type != 'text/osoft')
          || !CModule::getInstalled('osoft')
      ) {
        header("Content-disposition: $disposition; filename=\"{$file->file_name}\";");
        header("Content-type: {$file->file_type}");

        if ($page && $page > 0) {
          $content = CFile::slicePDF($file, $page, $length);
          header("Content-length: " . strlen($content));
          echo $content;
        }
        else {

          header("Content-length: {$file->doc_size}");
          readfile($file->_file_path);
        }
      }
      else {
        if ($file->file_type == 'application/osoft') {
          $doc     = new COsoftDossier(false);
          $content = $doc->toHTML($file->getBinaryContent(), false);
        }
        else {
          $doc     = new COsoftHistorique(false);
          $content = $doc->toHTML($file->getBinaryContent());
        }

        $file_name = str_replace('.osoft', '.txt', $file->file_name);
        header("Content-disposition: $disposition; filename=\"{$file_name}\";");
        header("Content-length: " . strlen($content));
        header("Content-type: application/msword");

        $tmp_file = tempnam('', 'osoft_');
        file_put_contents($tmp_file, $content);
        readfile($tmp_file);
        unlink($tmp_file);
      }

      CApp::rip();
    }
    else {
      readfile(CAppUI::conf('root_dir') . '/' . self::$img_default);
      CApp::rip();
    }
  }

  /**
   * Instanciate and create a CFile from $document_id and $document_class
   *
   * @param int    $document_id    ID of CDocumentItem to get
   * @param string $document_class Class of the object to get (CFile|CCompteRendu)
   *
   * @return CFile|string
   */
  static function createFileForThumb($document_id, $document_class = 'CFile') {
    // If document is a CCompteRendu create the preview image.
    // Check the perms on the object before creating preview
    if ($document_class == 'CCompteRendu') {
      $cr = new CCompteRendu();
      try {
        $cr->load($document_id);
      }
      catch (Exception $e) {
        return self::$img_default;
      }

      if ($cr && $cr->_id) {
        $cr->loadRefsFwd();
        $file = $cr->loadFile();

        if (!$file || !$file->_id) {
          $cr->makePDFpreview();
          $file = $cr->_ref_file;

          if (!$file || !$file->_id) {
            return self::$img_default;
          }
        }

        return $file;
      }
      else {
        return self::$img_default;
      }
    }
    elseif ($document_class == 'CFile') {
      $file = new CFile();
      try {
        $file->load($document_id);
      }
      catch (Exception $e) {
        return self::$img_not_found;
      }

      if ($file && $file->_id) {
        return $file;
      }
      else {
        return self::$img_not_found;
      }
    }
    else {
      return self::$img_not_found;
    }
  }

  /**
   * Check the perms and existence of a file. Also check if the file is a draft or not
   * Display the appropriate image if one of the conditions are false
   *
   * @param CFile                      $file          File to check errors for
   * @param string                     $error_path    Path of the error if there is one
   * @param string                     $perm_callback Function to check perms
   *
   * @return string
   */
  static function handleFileErrors($file, $error_path = null, $perm_callback = null) {
    // Check perms on the file
    if ($perm_callback) {
      if ($file && $file->_id && !forward_static_call($perm_callback, $file)) {
        $error_path = self::$img_access_denied;
      }
    }
    else {
      if ($file && $file->_id && !$file->getPerm(CPermObject::READ)) {
        $error_path = self::$img_access_denied;
      }
    }


    // Check if the file exists
    if (!$file || !$file->_id || !file_exists($file->_file_path)) {
      $error_path = self::$img_not_found;
    }

    // If the file is a draft display the draft image
    if ($file && $file->file_type == "image/fabricjs") {
      $error_path = self::$img_draft;
    }

    // If an error occured display the corresponding image
    if ($error_path) {
      return $error_path;
      if ($show) {
        ob_clean();
        self::buildHeaders();
        $imagine->open($root_dir . '/' . $error_path)->show('png');
        return false;
      }
      else {
        return $imagine->open($root_dir . '/' . $error_path)->get('png');
      }
    }

    return null;
  }

  /**
   * Create a pdf file from a file. Throw an exception if an error occure
   *
   * @param CFile $file Object to convert to pdf
   *
   * @throws Exception
   * @return CFile
   */
  static function convertFileToPdf($file) {
    $fileconvert = $file->loadPDFconverted();
    $success     = 1;
    if (!$fileconvert || $fileconvert->_id) {
      $success = $file->convertToPDF();
    }
    if ($success) {
      $fileconvert = $file->loadPDFconverted();
    }
    if ($fileconvert && $fileconvert->_id) {
      return $fileconvert;
    }
    else {
      throw new Exception('Failed pdf convertion');
    }
  }

  /**
   * Build the http headers for the file
   *
   * @param string $tmp_file_path Path to the thumbnail
   * @param int    $last_modify   Last modification time of the file
   *
   * @return void
   */
  static function buildHeaders($tmp_file_path = null, $last_modify = null) {
    $week_time = 604800;

    header("Cache-Control: max-age=$week_time");
    header('Connection: keep-alive');

    if ($tmp_file_path && $last_modify) {
      header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modify) . " GMT");
    }

    header_remove('Pragma');
  }

  /**
   * Return the binaries data from the thumbnail of the file
   *
   * @param CFile  $file    Mediboard file to get binaries from
   * @param int    $page    File page number to get
   * @param int    $width   Max-width for the thumbnail
   * @param int    $height  Max-height for the thumbnail
   * @param string $quality Quality to display the image in
   * @param int    $rotate  Rotation of the document
   *
   * @return string
   */
  static function displayThumb($file, $page = null, $width = 120, $height = null, $quality = 'high', $rotate = 0) {
    if (!static::checkImagineExists()) {
      return '';
    }

    $engine  = extension_loaded('Imagick');
    $imagine = ($engine) ? new Imagick\Imagine() : new Gd\Imagine();

    if (file_exists($file->_file_path)) {
      $height    = $height ?: $width;
      $temp_path = tempnam('tmp/', 'img_');
      try {
        $image     = self::openFile($imagine, $file, $file->_file_path, $temp_path, $page);
      }
      catch (Exception $e) {
          unlink($temp_path);
        return $imagine->open(rtrim(CAppUI::conf("root_dir"), '/\\') . '/' . self::$img_not_found)->get('png');
      }

      if ($rotate) {
        $image->rotate($rotate);
      }

      $type      = 'jpeg';
      if (strpos($file->_file_type, 'png') !== false) {
        $type = 'png';
      }

      $src_size = $image->getSize();

      unlink($temp_path);

      $options = ($type == 'jpeg') ? array('jpeg_quality' => self::$quality['jpeg'][$quality]) :
        array('png_compression_level' => self::$quality['png'][$quality]);

      if (!$engine && version_compare(PHP_VERSION, '7.0.0') >= 0) {
        $width  = ($width <= $src_size->getWidth()) ? $width : $src_size->getWidth();
        $height = ($height <= $src_size->getHeight()) ? $height : $src_size->getHeight();

        if ($src_size->getWidth() > $src_size->getHeight()) {
          $ratio  = $src_size->getWidth() / $src_size->getHeight();
          $height = $width / $ratio;
        }
        elseif ($src_size->getWidth() < $src_size->getHeight()) {
          $ratio = $src_size->getHeight() / $src_size->getWidth();
          $width = $height / $ratio;
        }

        $size = new Box($width, $height);

        // GD is having a bug with the thumbnail function, using resize instead
        return $image->resize($size)->get($type, $options);
      }
      else {
        $size = new Box($width, $height);

        return $image->thumbnail($size, ImageInterface::THUMBNAIL_INSET)->get($type, $options);
      }
    }
    else {
      return $imagine->open(rtrim(CAppUI::conf("root_dir"), '/\\') . '/' . self::$img_not_found)->get('png');
    }
  }

  /**
   * @param Imagick\Imagine|Gd\Imagine $imagine   The Imagine instance
   * @param CFile                      $file      The CFile to get thumb of
   * @param string                     $file_path The path of the file to get thumb of
   * @param string                     $temp_file Temp file name
   * @param int                        $page      Page number to open
   * @param string                     $profile   Profile to use for the resolution
   *
   * @return ImageInterface
   * @throws Exception
   */
  static function openFile($imagine, $file, $file_path, $temp_file, $page = null, $profile = 'medium') {
    $gs         = CAppUI::conf('dPfiles CThumbnail gs_alias');
    $type       = $file->file_type;

    if ($type == "application/zip") {
      $f = static::extractFileForPreview($file);

      if (!is_array($f) && is_file($f)) {
        if (!isset(static::$tmp_files[dirname($f)])) {
          static::$tmp_files[dirname($f)] = array();
        }

        static::$tmp_files[dirname($f)][] = $file->_file_path = $file_path = $f;
        $type = CMbPath::getExtension($file->_file_path);
      }
      else {
        if (is_array($f)) {
          $tmp_dir_name = dirname($f[0]);
          foreach ($f as $_file) {
            if (is_file($_file)) {
              unlink($_file);
            }
            elseif (is_dir($_file)) {
              CMbPath::emptyDir($_file);
            }
          }

          CMbPath::recursiveRmEmptyDir($tmp_dir_name);
        }

        throw new Exception("Multiple files in zip");
      }
    }

    $resolution = self::$profiles[$profile]['dpi'];
    if (strpos($type, 'pdf') !== false) {
      $escaped_path = escapeshellarg($file->_file_path);

      // Create the PDF with a good quality
      $command = "$gs -dDEVICEXRESOLUTION=$resolution -dDEVICEYRESOLUTION=$resolution -sDEVICE=jpeg";
      if ($page !== null) {
        $command .= " -dFirstPage=" . ($page + 1) . " -dLastPage=" . ($page + 1);
      }
      $command .= " -o " . $temp_file . " " . $escaped_path;
      exec($command);
      $file_path = $temp_file;
    }

    return $imagine->open($file_path);
  }

  /**
   * Extract a zip file to preview its content
   *
   * @param CFile $file File to extract
   *
   * @return array|string
   */
  static function extractFileForPreview($file) {
    $thumbpath = CAppUI::conf('dPfiles CFile thumbnails_directory');

    // Set the path for the thumbnail directory
    if (!$thumbpath) {
      $thumbpath = rtrim(CAppUI::conf('root_dir'), "\\/") . '/tmp/phpthumb';
    }

    $extract_dir =  $thumbpath . "/" . $file->file_real_filename;
    CMbPath::forceDir($extract_dir);

    CMbPath::extract($file->_file_path, $extract_dir, "zip");
    $f = glob($extract_dir . '/*');

    if (count($f) === 1) {
      return reset($f);
    }

    return $f;
  }

  /**
   * Check if hte Imagine library exists or not
   *
   * @return bool
   */
  static function checkImagineExists() {
    return class_exists("\Imagine\Gd\Imagine") || class_exists("\Imagine\Imagick\Imagine");
  }

  /**
   * Remove the temporary files created from extracting a zip
   *
   * @return void
   */
  static function removeTmpFiles() {
    foreach (static::$tmp_files as $_dir => $_files) {
      foreach ($_files as $_file) {
        unlink($_file);
      }

      CMbPath::recursiveRmEmptyDir($_dir);
    }
  }

  /**
   * @return Gd\Imagine|Imagick\Imagine
   */
  protected static function getEngineInstance() {
    $engine = extension_loaded('Imagick');
    return ($engine) ? new Imagick\Imagine() : new Gd\Imagine();
  }
}
