<?php
/**
 * @package Mediboard\NovxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\NovxtelHospitality;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbException;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceHTTP;

/**
 * Manage calls to Novxtel-Hospitality source
 */
class CSourceNovxtelHospitality implements IShortNameAutoloadable {
  /* @var CGroups */
  public $_group;
  /* @var CSourceHTTP */
  public $_source_http;

  /**
   * Access to the HTTP source set for the establishment
   *
   * @return CSourceHTTP
   */
  function getSource() {
    $this->_group       = CGroups::loadCurrent();
    $this->_source_http = CExchangeSource::get("novxtelHospitality-" . $this->_group->_id, CSourceHTTP::TYPE);

    return $this->_source_http;
  }

  /**
   * Get data to access the HTTP source set for the establishment
   *
   * @param number      $ipp    IPP
   * @param CSourceHTTP $source Source HTTP
   *
   * @return array
   */
  function getData($ipp, $source) {
   $datas = array(
      "autologin" => true,
      "login"     => $source->user,
      "pass"      => $source->password,
      "module"    => "hospitality",
      "page"      => "searchclient",
      "ipp"       => $ipp
    );

    return $datas;
  }

  /**
   * Get URL hospitality
   *
   * @param number $ipp IPP
   *
   * @return string
   * @throws CMbException
   */
  function getUrl($ipp) {
    $source = $this->getSource();

    if (!$source) {
      throw new CMbException("CSourceNovxtelHospitality-msg-Source unreachable");
    }

   $content = http_build_query($this->getData($ipp, $source), null, "&");

    return $source->host . "" . $content;
  }
}
