<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Controllers;

use Exception;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CController;
use Ox\Core\CMbString;
use Ox\Core\CSoundex2;
use Ox\Core\CSQLDataSource;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Mediboard\Patients\CINSPatient;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CPaysInsee;
use Ox\Mediboard\Sante400\CIdSante400;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CPatientsController
 */
class CPatientsController extends CController {
  protected static $patient_fields = [
    "nom",
    "prenom",
    "sexe",
    "naissance",
    "_prenom_2",
    "_prenom_3",
    "_prenom_4",
    "nom_jeune_fille",
    "deces",
    "civilite",
    "rang_naissance",
    "cp_naissance",
    "lieu_naissance",
    "vip",
    "adresse",
    "cp",
    "pays",
    "phone_area_code",
    "tel",
    "tel2",
    "allow_sms_notification",
    "tel_pro",
    "tel_autre",
    "tel_autre_mobile",
    "email",
    "allow_email",
    "situation_famille",
    "mdv_familiale",
    "condition_hebergement",
    "niveau_etudes",
    "activite_pro",
    "profession",
    "csp",
    "fatigue_travail",
    "travail_hebdo",
    "transport_jour",
    "matricule",
    "qual_beneficiaire",
    "don_organes",
    "directives_anticipees",
    "rques",
  ];

  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function listPatients(CRequestApi $request_api): Response {
    $nom       = utf8_decode($request_api->getRequest()->get("nom"));
    $prenom    = utf8_decode($request_api->getRequest()->get("prenom"));
    $sexe      = $request_api->getRequest()->get("sexe");
    $naissance = $request_api->getRequest()->get("naissance");
    $ville     = utf8_decode($request_api->getRequest()->get("ville"));
    $cp        = $request_api->getRequest()->get("cp");
    $IPP       = $request_api->getRequest()->get("IPP");
    $NDA       = $request_api->getRequest()->get("NDA");
    $telephone = $request_api->getRequest()->get("telephone");
    $matricule = $request_api->getRequest()->get("NIR");
    $INS       = $request_api->getRequest()->get("INS");
    $proche    = $request_api->getRequest()->get("proche");

    $patient  = new CPatient();
    $patients = [];
    $offset   = $request_api->getOffset();
    $limit    = $request_api->getLimit();
    $total    = 0;

    if ($IPP || $NDA) {
      $patient->getByIPPNDA($IPP, $NDA);
    }

    if (!$patient->_id && $matricule) {
      $patient->getByNumSS($matricule);
    }

    if (!$patient->_id && $INS) {
      $ins_patient = new CINSPatient();
      $ins_patient->ins = $INS;
      if ($ins_patient->loadMatchingObject()) {
        $patient->load($ins_patient->patient_id);
      }
    }

    if ($patient->_id) {
      $patients = [$patient];
      $total    = 1;
    }
    elseif ($nom || $prenom) {
      $ds = CSQLDataSource::get("std");

      $where = $request_api->getFilterAsSQL($ds);

      $soundexObj = new CSoundex2();

      // Because of \w and \W don't match characters with diacritics
      $nom_search    = CMbString::removeDiacritics($nom);
      $prenom_search = CMbString::removeDiacritics($prenom);

      $prenom_search = preg_replace('/[^\w%_]+/', '_', $prenom_search);
      $nom_search    = preg_replace('/[^\w%_]+/', '_', $nom_search);

      if ($sexe) {
        $where["sexe"] = $ds->prepare("= ?", $sexe);
      }

      if ($naissance) {
        $where["naissance"] = $ds->prepare("= ?", $naissance);
      }

      if ($ville) {
        $where["ville"] = $ds->prepare("= ?", $ville);
      }

      if ($cp) {
        $where["cp"] = $ds->prepare("= ?", $cp);
      }

      if ($telephone) {
        $where[] = $ds->prepare("?", $telephone) . " IN (`tel`, `tel2`, `tel_autre`, `tel_autre_mobile`, `tel_pro`)";
      }

      // Résultats proches
      if ($proche) {
        if ($nom_search) {
          $patient_nom_soundex = $soundexObj->build($nom_search);

          $where[] = "`nom_soundex2` " . $ds->prepareLike("$patient_nom_soundex%") .
            " OR `nomjf_soundex2` " . $ds->prepareLike("$patient_nom_soundex%'");

          $where[] = '`nom` NOT ' . $ds->prepareLike("$nom%") .
                     " AND `nom_jeune_fille` NOT " . $ds->prepareLike("$nom%");
        }

        if ($prenom_search) {
          $patient_prenom_soundex = $soundexObj->build($prenom_search);

          $where[] = "`prenom_soundex2` " . $ds->prepareLike("$patient_prenom_soundex%");

          $where[] = '`prenom` NOT ' . $ds->prepareLike("$prenom%");
        }
      }
      else {
        if ($nom) {
          $where[] = "`nom` " . $ds->prepareLike("$nom%") .
            " OR `nom_jeune_fille` " . $ds->prepareLike("$nom%");
        }

        if ($prenom) {
          $where[] = "`prenom` " . $ds->prepareLike("$prenom%");
        }
      }

      $patients = $patient->loadList($where, null, $request_api->getLimitAsSql());

      $total = $patient->countList($where);
    }

    $resource = CCollection::createFromRequest($request_api, $patients);

    $resource->createLinksPagination($offset, $limit, $total);

    return $this->renderApiResponse($resource);
  }

  /**
   * @param CRequestApi $request_api
   * @param CPatient    $patient
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function showPatient(CRequestApi $request_api, CPatient $patient): Response {
    $patient->updateBMRBHReStatus();
    if ($patient->pays_naissance_insee) {
        $patient->_pays_naissance_insee = CPaysInsee::getPaysByNumerique($patient->pays_naissance_insee)->nom_fr;
    }
    return $this->renderApiResponse(CItem::createFromRequest($request_api, $patient));
  }

  /**
   * @param CRequestApi $request_api
   *
   * @return Response
   * @api
   */
  public function showPatientById400SIH(CRequestApi $request_api): Response {
    $patient_id = $request_api->getRequest()->get("patient_id");
    $cabinet_id = $request_api->getRequest()->get("cabinet_id");

    $id400 = CIdSante400::getMatch("CPatient", "ext_patient_id-{$cabinet_id}", $patient_id);

    $patient = new CPatient();
    $patient->load($id400->object_id);

    return $this->showPatient($request_api, $patient);
  }

  /**
   * @param CRequestApi $request_api
   *
   * @return Response|null
   * @throws Exception
   * @api
   */
  public function addPatient(CRequestApi $request_api): ?Response {
    $fields = $request_api->getContent(true, "windows-1252");

    $patient = new CPatient();

    foreach (self::$patient_fields as $_patient_field) {
      $patient->$_patient_field = isset($fields[$_patient_field]) ? $fields[$_patient_field] : "";
    }

    $patient->pays_naissance_insee = isset($fields["pays_naissance"]) ? $fields["pays_naissance"] : "";

    if ($msg = $patient->store()) {
      (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
    }

    $response = $this->showPatient($request_api, $patient);
    $response->setStatusCode(Response::HTTP_CREATED);

    return $response;
  }

  /**
   * @param CRequestApi $request_api
   * @param CPatient    $patient
   *
   * @return Response|null
   * @throws Exception
   * @api
   */
  public function modifyPatient(CRequestApi $request_api, CPatient $patient): ?Response {
    $patient->nom       = $request_api->getRequest()->get("name") ?: $patient->nom;
    $patient->prenom    = $request_api->getRequest()->get("firstname") ?: $patient->prenom;
    $patient->naissance = $request_api->getRequest()->get("birth") ?: $patient->naissance;

    if ($msg = $patient->store()) {
      (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
    }

    return $this->showPatient($request_api, $patient);
  }

  /**
   * @param CRequestApi $request_api
   * @param CPatient    $patient
   *
   * @return Response
   * @throws Exception
   * @api
   */
  public function deletePatient(CRequestApi $request_api, CPatient $patient): ?Response {
    if ($msg = $patient->delete()) {
      (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
    }

    return $this->renderResponse(null, Response::HTTP_NO_CONTENT);
  }
}
