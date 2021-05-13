<?php

/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\PlanningOp\Controllers;

use DateTime;
use Exception;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Resources\CCollection;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CAppUI;
use Ox\Core\CController;
use Ox\Core\Kernel\Exception\CControllerException;
use Ox\Mediboard\Dmi\CDM;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CAdministrationDM;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CSejoursController
 */
class CSejoursController extends CController
{

    /** @var string[] */
    protected static $sejour_fields = [
        "sejour_entree",
        "sejour_sortie",
        "sejour_type",
        "sejour_libelle",
        "sejour_patient_id",
        "sejour_praticien_id",
        "sejour_service_id",
        "sejour_charge_id",
        "sejour_uf_medicale_id",
        "sejour_rques",
        "sejour_facturable",
        "sejour_ald",
        "sejour_aide_organisee",
        "sejour_handicap",
        "sejour_presence_confidentielle",
        "sejour_frais_sejour",
        "sejour_reglement_frais_sejour",
        "sejour_isolement",
        "sejour_nuit_convenance",
        "sejour_hospit_de_jour",
        "sejour_consult_accomp",
        "sejour_ATNC",
        "sejour_convalescence",
    ];

    /** @var string[] */
    protected static $patient_fields = [
        "patient_tutelle",
    ];

    /**
     * @param CRequestApi $request_api
     *
     * @return Response
     * @throws Exception
     * @api
     */
    public function listSejours(CRequestApi $request_api): Response
    {
        $sejour  = new CSejour();
        $sejours = $sejour->loadListFromRequestApi($request_api);

        //@todo : Traitement back massload etc

        $total = $sejour->countListFromRequestApi($request_api);

        $resource = CCollection::createFromRequest($request_api, $sejours);
        $resource->createLinksPagination($request_api->getOffset(), $request_api->getLimit(), $total);

        return $this->renderApiResponse($resource);
    }

    /**
     * @param CRequestApi $request_api
     * @param CSejour     $sejour
     *
     * @return Response
     * @throws CApiException
     * @api
     */
    public function showSejour(CRequestApi $request_api, CSejour $sejour): Response
    {
        $resource = CItem::createFromRequest($request_api, $sejour);

        return $this->renderApiResponse($resource);
    }

    /**
     * @param CRequestApi $request_api
     *
     * @return Response
     * @api
     */
    public function addSejour(CRequestApi $request_api, CSejour $sejour = null): Response
    {
        $fields = $request_api->getContent(true, "windows-1252");

        $ext_patient_id = $request_api->getRequest()->get("tamm_patient_id");
        $ext_cabinet_id = $request_api->getRequest()->get("sih_cabinet_id");
        $group_id       = $request_api->getRequest()->get("sih_group_id");

        foreach (self::$sejour_fields as $_sejour_field) {
            ${$_sejour_field} = isset($fields[$_sejour_field]) ? $fields[$_sejour_field] : "";
        }

        foreach (self::$patient_fields as $_patient_field) {
            ${$_patient_field} = isset($fields[$_patient_field]) ? $fields[$_patient_field] : "";
        }

        $patient = CPatient::findOrNew($sejour_patient_id);

        if (!$patient->_id) {
            (new CControllerException(Response::HTTP_BAD_REQUEST, CAppUI::tr("CPatient-Patient not found")))->throw();
        }

        foreach (self::$patient_fields as $_patient_field) {
            $patient->{preg_replace("/patient_/", "", $_patient_field)} = ${$_patient_field};
        }

        $sejour = $sejour ?: new CSejour();

        foreach (self::$sejour_fields as $_sejour_field) {
            $sejour->{preg_replace("/sejour_/", "", $_sejour_field)} = ${$_sejour_field};
        }

        $sejour->group_id        = $group_id;
        $sejour->entree_prevue   = $sejour->entree;
        $sejour->sortie_prevue   = $sejour->sortie;
        $sejour->_ext_patient_id = $ext_patient_id;
        $sejour->_ext_cabinet_id = $ext_cabinet_id;

        if ($msg = $patient->check()) {
            (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
        }

        if ($msg = $sejour->check()) {
            (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
        }

        if ($msg = $sejour->store()) {
            (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
        }

        if ($msg = $patient->store()) {
            (new CControllerException(Response::HTTP_BAD_REQUEST, $msg))->throw();
        }

        $this->formatFormFields($sejour);

        $resource = CItem::createFromRequest($request_api, $sejour)->setModelFieldsets(
            [
                CSejour::FIELDSET_DEFAULT,
                CSejour::FIELDSET_ADMISSION,
            ]
        );

        return $this->renderApiResponse($resource)->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param CRequestApi $request_api
     * @param CSejour     $sejour
     *
     * @return Response
     * @api
     */
    public function modifySejour(CRequestApi $request_api, CSejour $sejour): Response
    {
        $sejour->_date_entree_prevue = null;
        $sejour->_date_sortie_prevue = null;

        return $this->addSejour($request_api, $sejour);
    }

    /**
     * @param CRequestApi $request_api
     * @param CSejour     $sejour
     *
     * @return Response
     * @api
     */

    public function getFields(CRequestApi $request_api, CSejour $sejour): Response
    {
        $patient          = $sejour->loadRefPatient();
        $etablissement    = $sejour->loadRefEtablissement();
        $prat             = $sejour->loadRefPraticien();
        $function         = $prat->loadRefFunction();
        $medecin_traitant = $patient->loadRefMedecinTraitant();

        $sejour->loadRefsPrescriptions();

        $prescription_sejour = $sejour->_ref_prescriptions['sejour'];
        $prescription_sortie = $sejour->_ref_prescriptions['sortie'];

        // Transfusion de produits sanguins labiles
        $transfusion_psl = CAppUI::tr('No');

        if ($prescription_sejour->_id) {
            foreach ($prescription_sejour->loadRefsLinesElement(null, 'ds') as $_line_element) {
                foreach ($_line_element->loadRefsAdministrations(null, null, 'dateTime ASC') as $_adm) {
                    $transfusion_psl = CAppUI::tr('Yes') . ' - ' . (new DateTime($_adm->dateTime))->format('d/m/Y');
                    break 2;
                }
            }
        }

        // Administration de dérivés du sang
        $adm_mds       = CAppUI::tr('No');
        $adm_mds_found = false;

        if ($prescription_sejour->_id) {
            /** @var CPrescriptionLineMedicament $_line_med */
            foreach ($prescription_sejour->loadRefsLinesMed() as $_line_med) {
                if (strpos($_line_med->atc, 'B05AA') === false) {
                    continue;
                }

                foreach ($_line_med->loadRefsAdministrations(null, null, 'dateTime ASC') as $_adm) {
                    $adm_mds       = CAppUI::tr('Yes') . ' - ' . (new DateTime($_adm->dateTime))->format('d/m/Y');
                    $adm_mds_found = true;
                    break 2;
                }
            }

            if (!$adm_mds_found) {
                foreach ($prescription_sejour->loadRefsPrescriptionLineMixes() as $_line_mix) {
                    foreach ($_line_mix->loadRefsLines() as $_mix_item) {
                        if (strpos($_line_med->atc, 'B05AA') === false) {
                            continue;
                        }

                        foreach ($_mix_item->loadRefsAdministrations(null, 'dateTime ASC') as $_adm) {
                            $adm_mds = CAppUI::tr('Yes') . ' - ' . (new DateTime($_adm->dateTime))->format('d/m/Y');
                            break 2;
                        }
                    }
                }
            }
        }

        // Liste des dmis posés pendant le séjour
        $dmis = [];

        if ($prescription_sejour->_id) {
            /** @var CAdministrationDM $_line_dmi */
            foreach ($prescription_sejour->loadRefsLinesDMI() as $_line_dmi) {
                $product = $_line_dmi->loadRefProduct();
                $dmi     = CDM::getFromProduct($product);
                $dmis[]  = '[' . $dmi->code . ']' . $product->name;
            }
        }

        // Traitements médicamenteux arrêtés durant le séjour
        $trt_stopped = [];

        if ($prescription_sejour->_id) {
            foreach ($prescription_sejour->loadRefsLinesMed() as $_line_med) {
                if (!$_line_med->date_arret) {
                    continue;
                }

                $_line_med->updateLongView(true, false, false, true);

                $prises_view = [];

                foreach ($_line_med->loadRefsPrises() as $_prise) {
                    $_prise->loadRefsFwd();
                    $prises_view[] = $_prise->_print_view;
                }

                $trt_stopped[] = $_line_med->_long_view
                    . (count($prises_view) ? (' - ' . implode(', ', $prises_view)) : '')
                    . ' - '
                    . CAppUI::tr('CPrescriptionLineMedicament-debut')
                    . ' ' . $_line_med->getFormattedValue('_debut_reel')
                    . ' - '
                    . CAppUI::tr('CPrescriptionLineMedicament-date_arret')
                    . ' ' . $_line_med->getFormattedValue('_datetime_arret');
            }
        }

        // Traitements médicamenteux prescrits à la sortie de l'établissement (ordonnance de sortie)
        $trt_sortie = [];

        if ($prescription_sortie->_id) {
            foreach ($prescription_sortie->loadRefsLinesMed() as $_line_med) {
                $_line_med->updateLongView(true, false, false, true);

                $prises_view = [];

                foreach ($_line_med->loadRefsPrises() as $_prise) {
                    $_prise->loadRefsFwd();
                    $prises_view[] = $_prise->_print_view;
                }

                $trt_sortie[] = $_line_med->_long_view
                    . (count($prises_view) ? (' - ' . implode(', ', $prises_view)) : '')
                    . ' - '
                    . CAppUI::tr('CPrescriptionLineMedicament-debut')
                    . ' ' . $_line_med->getFormattedValue('_debut_reel')
                    . ' - ' . $_line_med->getFormattedValue('_fin_reelle');
            }
        }

        $fields = [
            'motif_demande_hospit'            => $sejour->libelle,
            'entree_hospit'                   => $sejour->getFormattedValue('entree'),
            'sortie_hospit'                   => $sejour->getFormattedValue('sortie'),
            'type_hospit'                     => $sejour->getFormattedValue('type'),
            'specialite_ufm'                  => $sejour->loadRefUFMedicale()->libelle,
            'synthese_med_sejour'             => null,
            'evenements_indesirables_hospit'  => null,
            'recherche_micro_organismes'      => null,
            'identification_micro_organismes' => null,
            'transfusion_psl'                 => $transfusion_psl,
            'accidents_transfusionnels'       => null,
            'administrations_mds'             => $adm_mds,
            'evenements_indesirables_mds'     => null,
            'liste_dmis'                      => $dmis,
            'traitements_arretes'             => $trt_stopped,
            'traitements_sortie'              => $trt_sortie,
            'attente_resultats_examens'       => null,
            'suite_hospit'                    => null,
            'patient_nom'                     => $patient->nom,
            'patient_prenom'                  => $patient->prenom,
            'patient_identification'          => $patient->getFormattedValue('matricule'),
            'patient_adresse'                 => trim("{$patient->adresse}\n{$patient->cp} {$patient->ville}"),
            'patient_email'                   => $patient->email,
            'patient_tel'                     => $patient->getFormattedValue('tel'),
            'etablissement_nom'               => $etablissement->text,
            'etablissement_adresse'           =>
                trim("{$etablissement->adresse}\n{$etablissement->cp} {$etablissement->ville}"),
            'medecin_resp_nom'                => $prat->_user_last_name,
            'medecin_resp_prenom'             => $prat->_user_first_name,
            'medecin_resp_adresse'            =>
                trim(
                    $prat->_user_adresse ?
                        "{$prat->_user_adresse}\n{$prat->_user_cp} {$prat->_user_ville}" :
                        "{$function->adresse}\n{$function->cp} {$function->ville}"
                ),
            'medecin_resp_tel'                =>
                trim(
                    $prat->_user_phone ?
                        $prat->getFormattedValue('_user_phone') : $function->getFormattedValue('tel')
                ),
            'medecin_traitant_nom'            => $medecin_traitant->nom,
            'medecin_traitant_prenom'         => $medecin_traitant->prenom,
            'medecin_traitant_adresse'        =>
                trim("{$medecin_traitant->adresse}\n{$medecin_traitant->cp} {$medecin_traitant->ville}"),
            'medecin_traitant_tel'            => $medecin_traitant->tel
        ];

        $fields = array_map_recursive('utf8_encode', $fields);

        return $this->renderJsonResponse(json_encode($fields));
    }

    /**
     * @param CSejour $sejour
     */
    protected function formatFormFields(CSejour $sejour): void
    {
        [$sejour->_libelle, $libelle_other] = CSejour::getLibelles($sejour);
    }
}
