<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files\Generators;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CHTTPClient;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CRequest;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Bcb\CBcbObject;
use Ox\Mediboard\Compendium\CCompendium;
use Ox\Mediboard\CompteRendu\CHtmlToPDFConverter;
use Ox\Mediboard\CompteRendu\CWkhtmlToPDF;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;
use Throwable;

/**
 * Description
 */
class CFileGenerator extends CObjectGenerator {
  const RANDOM_PAGE = 'https://fr.wikipedia.org/wiki/Special:randompage';
  const REGEXP_CHARSET = "/charset=(?P<charset>[\w\-]*)/";

  static $mb_class = CFile::class;
  static $dependances = array(CMediusers::class, CSejour::class);

  /** @var CMbObject $context */
  protected $context;
  protected $author_id;
  protected $file_name;

  /** @var CFile */
  protected $object;

  /**
   *  Generate an object
   *
   * @param string $type File type to generate
   *
   * @return CMbObject
   * @throws Exception
   */
  function generate($type = 'wiki') {
    $this->object = new CFile();

    $this->object->author_id = $this->author_id;

    if ($this->force || !$this->context) {
      $this->context = (new CSejourGenerator())->generate();
    }

    $this->object->setObject($this->context);

    $content = "";
    switch ($type) {
      case "wiki":
        $this->object->file_date = CMbDT::getRandomDate($this->context->entree, $this->context->sortie);
        try {
          $content = $this->getRandomWiki();
        }
        catch (Exception $e) {
          CAppUI::setMsg($e->getMessage());

          return null;
        }

        $this->object->file_name = $this->file_name ?: 'fichier.pdf';
        break;
      case "mono":
        $this->object->file_date = CMbDT::getRandomDate($this->context->entree, $this->context->sortie);
        $content         = $this->getRandomProduit();
        $this->object->file_name = 'Monographie.pdf';
        break;
      default:
        // Do nothing
    }

    if (!$content) {
      return null;
    }

    $this->object->file_type = "application/pdf";
    $this->object->fillFields();
    $this->object->setContent($content);

    $this->object->updateFormFields();

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      CAppUI::setMsg("CFile-msg-create", UI_MSG_OK);
      $this->trace(static::TRACE_STORE, $this->object);
    }

    return $this->object;
  }

  /**
   * Init the file generator
   *
   * @param CMbObject  $context   Context to link files to
   * @param CMediusers $author_id Files author
   *
   * @return static
   */
  function init($context, $author_id) {
    $this->context   = $context;
    $this->author_id = $author_id;

    return $this;
  }

  /**
   * Get a random wikipedia page in PDF format
   *
   * @return string|null
   * @throws Exception
   */
  protected function getRandomWiki() {
    $body         = "";
    $from_charset = "UTF-8";
    try {
      $client = new CHTTPClient(self::RANDOM_PAGE);
      $client->setOption(CURLOPT_FOLLOWLOCATION, true);
      $body  = $client->get(false);
      $infos = $client->getInfo();

      if ($infos['content_type'] && preg_match(self::REGEXP_CHARSET, $infos['content_type'], $charset)) {
        $from_charset = $charset[1];
      }

      $this->file_name = mb_convert_encoding(basename(urldecode($infos['url'])), "Windows-1252", $from_charset);

      $client->closeConnection();
    }
    catch (Exception $e) {
      CApp::log($e->getMessage());
    }

    $body = CMbString::purifyHTML(mb_convert_encoding($body, "Windows-1252", $from_charset));

    try {
      CHtmlToPDFConverter::init("CWkHtmlToPDFConverter");

      return CHtmlToPDFConverter::convert($body, "a4", "portrait");
    }
    catch (Throwable $e) {
      CAppUI::stepAjax($e->getMessage(), UI_MSG_WARNING);

      return $body;
    }
  }

  /**
   * Get the view of a random product depending on the DB used
   *
   * @return string
   */
  protected function getRandomProduit() {
    switch (CAppUI::conf("dPmedicament base")) {
      case "bcb":
        $content = $this->getRandomBcbProduct();
        break;
      case "compendium":
        $content = $this->getRandomCompendiumProduct();
        break;
      case "vidal":
      case "besco":
      default:
        $content = "";
    }

    return $content;
  }

  /**
   * Get the monographie of a BCB Product in PDF format
   *
   * @return string
   */
  protected function getRandomBcbProduct() {
    try {
      $code = $this->getRandomUCDCode();
    }
    catch (Exception $e) {
      CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

      return null;
    }


    $url = array(
      array(
        "m"        => "bcb",
        "dialog"   => "ajax_vw_produit",
        "print"    => 1,
        "code_ucd" => $code,
      )
    );

    try {
      $result = CWkhtmlToPDF::makePDF($this->context, "monoTest", $url, "A4", "Portrait", "print", false);
    }
    catch (Throwable $e) {
      $result = null;
      CAppUI::stepAjax($e->getMessage(), UI_MSG_WARNING);
    }

    return $result;
  }

  /**
   * Get the monographie of a compendium Product in PDF format
   *
   * @return string
   */
  protected function getRandomCompendiumProduct() {
    try {
      $code = $this->getRandomCompendiumCode();
    }
    catch (Exception $e) {
      CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

      return null;
    }

    $url = array(
      array(
        "m"        => "compendium",
        "tab"      => "ajax_vw_produit",
        "print"    => 1,
        "code_cis" => $code,
      )
    );

    return CWkhtmlToPDF::makePDF($this->context, "monoTest", $url, "A4", "Portrait", "print", false);
  }

  /**
   * Get a random product code
   *
   * @return string|null
   * @throws Exception
   */
  protected function getRandomUCDCode() {
    $ds = CBcbObject::getDataSource();

    $cache = new Cache(__METHOD__, "", Cache::INNER);
    if (!$cache->exists()) {
      $query = new CRequest();
      $query->addTable("UCD_CIPS");
      $total = $ds->loadResult($query->makeSelectCount());
      $cache->put($total);
    }
    else {
      $total = $cache->get();
    }

    $idx   = rand(0, $total - 1);
    $query = new CRequest();
    $query->addSelect("CODEUCD");
    $query->addTable("UCD_CIPS");
    $query->setLimit("$idx, 1");

    return $ds->loadResult($query->makeSelect());
  }

  /**
   * Get a random product code
   *
   * @return string|null
   * @throws Exception
   */
  protected function getRandomCompendiumCode() {
    $compendium = new CCompendium();
    $ds         = $compendium->getDS();

    $cache = new Cache(__METHOD__, "", Cache::INNER);
    if (!$cache->exists()) {
      $query = new CRequest();
      $query->addTable("product");
      $total = $ds->loadResult($query->makeSelectCount());
      $cache->put($total);
    }
    else {
      $total = $cache->get();
    }

    $idx   = rand(0, $total - 1);
    $query = new CRequest();
    $query->addSelect("product_id");
    $query->addTable("product");
    $query->setLimit("$idx, 1");

    return $ds->loadResult($query->makeSelect());
  }
}
