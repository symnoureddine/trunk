<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Interactions\CFHIRInteraction;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCreate;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionRead;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionSearch;
use Ox\Interop\Fhir\Operations\CFHIROperationIhePix;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceDocumentManifest;
use Ox\Interop\Fhir\Resources\CFHIRResourceDocumentReference;
use Ox\Interop\Fhir\Resources\CFHIRResourcePatient;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Sante400\CHyperTextLink;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CExchangeSource;

CCanDo::checkAdmin();

$cn_receiver_guid = CValue::sessionAbs("cn_receiver_guid");
$search_type      = CView::get("search_type", "str");
$count            = CView::get("count", "num");
$id               = CView::get("id", "str");
$uri              = CView::get("uri", "str");

// Récuperation des patients recherchés
$patient_nom        = CView::request("nom", "str");
$patient_prenom     = CView::request("prenom", "str");
$patient_email      = CView::request("email", "str");
$patient_jeuneFille = CView::request("nom_jeune_fille", "str");
$patient_sexe       = CView::request("sexe", "str");
$patient_adresse    = CView::request("adresse", "str");
$patient_ville      = CView::request("ville", "str");
$patient_cp         = CView::request("cp", "num");
$patient_day        = CView::request("Date_Day", "num");
$patient_month      = CView::request("Date_Month", "num");
$patient_year       = CView::request("Date_Year", "num");

$person_id_number    = CView::request("person_id_number", "str");
$person_namespace_id = CView::request("person_namespace_id", "str");

$identity_domain_oid = CView::request("identity_domain_oid", "str");

$encounter_id       = CView::request("encounter_id", "str");
$resource_id        = CView::request("resource_id", "str");
$patient_id         = CView::request("patient_id", "str");
$patient_identifier = CView::request("patient_identifier", "str");
$complement_url     = CView::request("complement_url", "str");
$resource_type      = CView::request("resource_type", "str");
$status_doc         = CView::request("status", "str");

$object_guid    = CView::request("object_guid", "str");
$request_method = CView::request("request_method", "str default|GET");

$_response_type = CView::request("response_type", "enum list|fhir+xml|fhir+json");

$format = "application/$_response_type";

// Cas du POST
if ($request_method == "POST") {
    $_request_type = CView::request("request_type", "enum list|fhir+xml|fhir+json");
    $format        = "application/$_request_type";
}

if (!$cn_receiver_guid) {
    CView::checkin();
    CAppUI::stepAjax("CInteropReceiver.none", UI_MSG_ERROR);
}

/** @var CReceiverFHIR $receiver_fhir */
$receiver_fhir = CMbObject::loadFromGuid($cn_receiver_guid);

$naissance = null;
if ($patient_year || $patient_month || $patient_day) {
    $parts = [$patient_year];

    if ($patient_month) {
        $parts[] = str_pad($patient_month, 2, "0", STR_PAD_LEFT);
    }

    if ($patient_day) {
        $parts[] = str_pad($patient_day, 2, "0", STR_PAD_LEFT);
    }

    $naissance = implode("-", $parts);
}

if ($uri) {
    [$url, $query_string] = explode("?", $uri, 2);
    $dirs = explode("/", rtrim($url, "/"));

    // FIXME
    $last         = end($dirs);
    $resourceType = null;
    if ($last === "Patient" || $last === "DocumentReference" || $last === "DocumentManifest") {
        $resourceType = $last;
    }

    $request = new CFHIRInteraction($resourceType, $format);

    $query = CFHIR::parseQueryString($query_string, true);

    foreach ($query as $_key => $_values) {
        if ($_key === "_format") {
            continue;
        }

        foreach ($_values as $_value) {
            $request->addParameter($_key, $_value);
        }
    }
} else {
    switch ($search_type) {
        case "CPDQm":
            if ($id) {
                $request = new CFHIRInteractionRead("Patient", $format);
                $request->addParameter("_id", $id);
            } else {
                $request = new CFHIRInteractionSearch("Patient", $format);
                $request->addParameter("family", $patient_nom);
                $request->addParameter("family", $patient_jeuneFille);
                $request->addParameter("given", $patient_prenom);
                $request->addParameter("birthdate", $naissance);
                $request->addParameter("email", $patient_email);
                $request->addParameter("address", $patient_adresse);
                $request->addParameter("address-city", $patient_ville);
                $request->addParameter("address-postalcode", $patient_cp);
                $request->addParameter("gender", $patient_sexe);
                $request->addParameter("_count", $count);

                if ($person_id_number) {
                    $request->addParameter("identifier", "urn:oid:$person_namespace_id|$person_id_number");
                }

                if ($identity_domain_oid) {
                    $oids = explode(",", $identity_domain_oid);

                    $oids = array_map(
                        function ($oid) {
                            return "urn:oid:$oid|";
                        },
                        $oids
                    );

                    $oids = implode(",", $oids);

                    $request->addParameter("identifier", $oids);
                }
            }
            break;

        case "CPIXm":
            $request = new CFHIROperationIhePix("Patient", $format);
            $request->addParameter("sourceIdentifier", "urn:oid:$person_namespace_id|$person_id_number");
            $request->addParameter("_count", $count);

            if ($identity_domain_oid) {
                $request->addParameter("targetSystem", "urn:oid:$identity_domain_oid");
            }
            break;

        case "CMHD":
            // Envoi de document
            if ($request_method == "POST") {
                $request = new CFHIRInteractionCreate("DocumentReference", $format);
                // TODO XDS TOOLKIT : Pour le serveur de test XDS TOOLKIT
                //$request->add_format = false;

                if (!$object_guid) {
                    CView::checkin();
                    CAppUI::stepAjax("FHIR-msg-Not file identifiant", UI_MSG_ERROR);
                }

                /** @var CCompteRendu|CFile $doc_item */
                $doc_item = CMbObject::loadFromGuid($object_guid);
                if (!$doc_item || !$doc_item->_id) {
                    CView::checkin();
                    CAppUI::stepAjax("FHIR-msg-Not file identifiant", UI_MSG_ERROR);
                }

                $document_reference = new CDocumentReference();
                // Dans le cas d'un CFile on a une seule version du document
                $document_reference->version = $doc_item->_version;
                $document_reference->setObject($doc_item);
                $document_reference->setActor($receiver_fhir);
                $document_reference->loadMatchingObject();

                // Est-ce qu'on a déjà envoyé le document à ce destinataire?
                if ($document_reference->_id) {
                    CView::checkin();
                    CAppUI::stepAjax("FHIR-msg-File always sent with this actor", UI_MSG_ERROR);
                }

                if (!$doc_item->type_doc_dmp) {
                    CView::checkin();
                    CAppUI::stepAjax("FHIR-msg-Not type doc", UI_MSG_ERROR);
                }

                $file_type = $doc_item instanceof CCompteRendu ? "application/pdf" : $doc_item->file_type;
                if (!CFHIRResourceDocumentReference::getFileType($file_type)) {
                    CView::checkin();
                    CAppUI::stepAjax("fhir-msg-Document type authorized in FHIR|pl", UI_MSG_ERROR);
                }

                try {
                    $source                 = CExchangeSource::get("{$receiver_fhir->_guid}-{$search_type}");
                    $receiver_fhir->_source = $source;
                    $request->_receiver     = $receiver_fhir;
                    $resourceRequest        = $request->build($doc_item);
                    $data                   = $resourceRequest->output($format);
                } catch (CFHIRException $e) {
                    CView::checkin();
                    CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
                }
            } // Recherche de documents
            else {
                $resource_type           = $resource_type ? $resource_type : "DocumentReference";
                $request                 = new CFHIRInteractionSearch($resource_type, $format);
                $request->resource_id    = $resource_id;
                $request->complement_url = $complement_url;
                // TODO XDS TOOLKIT : Pour le serveur de test XDS Toolkit
                $request->add_format = false;
                $request->addParameter("encounter", $encounter_id);
                $request->addParameter("patient", $patient_id);
                $request->addParameter("_count", $count);
                $request->addParameter("patient.identifier", $patient_identifier);
                $request->addParameter("status", $status_doc);
            }

            break;

        default:
            CAppUI::stepAjax("Wrong search type : $search_type", UI_MSG_ERROR);
    }
}

$request->profil = $search_type;
try {
    // Request POST - ITI-65
    if ($request_method === "POST") {
        $response = $receiver_fhir->sendEvent($request, new CPatient(), [$data], [], false, false, "POST");
    } else {
        $response = $receiver_fhir->sendEvent($request, new CPatient());
    }
} catch (CMbException $e) {
    CView::checkin();
    $e->stepAjax();

    return;
}

$smarty = new CSmartyDP();
$smarty->assign("query", $request->buildQuery());
$smarty->assign("response_code", $receiver_fhir->_source->_response_http_code);
$smarty->assign("response_message", $receiver_fhir->_source->_response_http_message);
$smarty->assign("response_headers", $receiver_fhir->_source->_response_http_headers);

$links       = null;
$total       = null;
$results     = [];
$lang        = null;
$error       = null;
$code_status = null;

if ($request_method === "GET") {
    $informations = CFHIRResource::transformResponseInXML(
        $response,
        $format ? $format : $receiver_fhir->_source->_content_type
    );
    $response     = CMbArray::get($informations, "response");
    $lang         = CMbArray::get($informations, "lang");
    $dom          = CMbArray::get($informations, "dom");

    $xpath = new CFHIRXPath($dom);

    if ($search_type === "CPDQm") {
        $total = $xpath->getAttributeValue("fhir:total", $dom->documentElement);

        $link_elements = $xpath->query("fhir:link", $dom->documentElement);

        foreach ($link_elements as $_link) {
            $_relation = $xpath->getAttributeValue("fhir:relation", $_link);
            $url       = $xpath->getAttributeValue("fhir:url", $_link);

            $links[$_relation] = $url;
        }

        $results = CFHIRResourcePatient::getPatientsFromXML($dom);
    } elseif ($search_type === "CMHD") {
        $total = $xpath->getAttributeValue("fhir:total", $dom->documentElement);

        $link_elements = $xpath->query("fhir:link", $dom->documentElement);

        foreach ($link_elements as $_link) {
            $_relation = $xpath->getAttributeValue("fhir:relation", $_link);
            $url       = $xpath->getAttributeValue("fhir:url", $_link);

            $links[$_relation] = $url;
        }

        try {
            if ($resource_type == "DocumentReference") {
                $results = CFHIRResourceDocumentReference::getDocumentReferenceFromXML($dom);
            } else {
                $results = CFHIRResourceDocumentManifest::getManifestFromXML($dom);
            }
        } catch (Exception $e) {
            mbLog($e->getMessage());
        }
    } else {
        $parameter_elements = $xpath->query("fhir:parameter", $dom->documentElement);

        foreach ($parameter_elements as $_parameter) {
            $_name   = $xpath->getAttributeValue("fhir:name", $_parameter);
            $_system = $xpath->getAttributeValue("fhir:valueIdentifier/fhir:system", $_parameter);
            $_value  = $xpath->getAttributeValue("fhir:valueIdentifier/fhir:value", $_parameter);

            $results[] = [
                "name"   => $_name,
                "system" => $_system,
                "value"  => $_value,
            ];
        }
    }
} elseif ($request_method === "POST") {
    // Sur un POST, le body de la réponse peut être vide
    if ($response) {
        $informations = CFHIRResource::transformResponseInXML(
            $response,
            $receiver_fhir->_source->_content_type ? $receiver_fhir->_source->_content_type : $format
        );
        $response     = CMbArray::get($informations, "response");
        $lang         = CMbArray::get($informations, "lang");
        $dom          = CMbArray::get($informations, "dom");

        $xpath = new CFHIRXPath($dom);

        // TODO XDS TOOLKIT : Pour XDS Toolkit => recuperation des erreurs
        if ($search_type === "CMHD") {
            $code = $xpath->getAttributeValue("fhir:issue/fhir:code", $dom->documentElement);

            if ($code == "exception") {
                $error = $xpath->getAttributeValue("fhir:issue/fhir:diagnostics", $dom->documentElement);
            }

            $status = $xpath->query("fhir:entry/fhir:response", $dom->documentElement);

            $error_status = $status->length > 0 ? false : null;
            foreach ($status as $_status) {
                if ($xpath->getAttributeValue('fhir:status', $_status) != "201") {
                    $error_status = true;
                }
            }

            if ($error_status === false) {
                $code_status = "201";
            }
        }

        // Récupération du document reference et document manifest
        $document_reference          = new CDocumentReference();
        $document_reference->version = $doc_item->_version;
        $document_reference->setObject($doc_item);
        $document_reference->setActor($receiver_fhir);
        $document_reference->loadMatchingObject();

        if ($document_reference->_id) {
            $document_manifest = $document_reference->loadRefDocumentManifest();

            // Création des identifiants externes
            $entries = $xpath->query("fhir:entry", $dom->documentElement);
            foreach ($entries as $_entry) {
                $url = $xpath->getAttributeValue("fhir:response/fhir:location", $_entry);

                if (preg_match("#DocumentReference#", $url)) {
                    $idex = CIdSante400::getMatch(
                        $document_reference->_class,
                        CFHIR::getTag(),
                        $url,
                        $document_reference->_id
                    );

                    $hyperlink = new CHyperTextLink();
                    $hyperlink->setObject($document_reference);
                    $hyperlink->name = "DocReference_" . $receiver_fhir->_guid;
                    $hyperlink->link = $url;
                    $hyperlink->loadMatchingObject();
                    $hyperlink->store();
                }
                if (preg_match("#DocumentManifest#", $url)) {
                    $hyperlink = new CHyperTextLink();
                    $hyperlink->setObject($document_reference);
                    $hyperlink->name = "DocManifest_" . $receiver_fhir->_guid;
                    $hyperlink->link = $url;
                    $hyperlink->loadMatchingObject();
                    $hyperlink->store();
                }
            }
        }
    }
}

// checkin ici parce que pour MHD, on ajoute des infos en session
CView::checkin();

$smarty->assign("response", $response);
$smarty->assign("request_method", $request_method);
$smarty->assign("id", $id);
$smarty->assign("results", $results);
$smarty->assign("links", $links);
$smarty->assign("total", $total);
$smarty->assign("search_type", $search_type);
$smarty->assign("format", $_response_type);
$smarty->assign("lang", $lang);
$smarty->assign("error", $error);
$smarty->assign("code_status", $code_status);
$smarty->assign("resource_type", $resource_type);
$smarty->display("inc_response_fhir.tpl");
