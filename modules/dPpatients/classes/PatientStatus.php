<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

/**
 * Gestion du statut du patient en fonction de ses attributs et de la source d'identité sélectionnée
 */
class PatientStatus
{
    /** @var CPatient */
    private $patient;

    /** @var bool */
    private static $updating_status;

    public function __construct(CPatient $patient)
    {
        $this->patient = $patient;
    }

    public function updateStatus(): ?string
    {
        if (self::$updating_status) {
            return null;
        }

        self::$updating_status = true;

        $sources_identite = $this->patient->loadRefsSourcesIdentite();

        $this->patient->loadRefPatientState();
        $this->patient->completeField('status');

        $status = null;

        // Gestion du statut VIDE lors de la réception de patients
        if (
            $this->patient->status === 'VIDE'
            && (count($sources_identite) === 1)
            && ($this->patient->loadRefSourceIdentite()->mode_obtention === 'interop')
        ) {
            return null;
        }

        if (count($sources_identite)) {
            $type_justificatif = null;
            $mode_obtention = null;

            foreach ($sources_identite as $_source_identite) {
                if (!$_source_identite->active) {
                    continue;
                }

                if ($_source_identite->type_justificatif) {
                    $type_justificatif = $_source_identite->type_justificatif;
                }

                if ($_source_identite->mode_obtention && $mode_obtention !== 'insi') {
                    $mode_obtention = $_source_identite->mode_obtention;
                }
            }

            if (
                (!$type_justificatif && $mode_obtention !== 'insi')
                || ($this->patient->_douteux)
                || ($this->patient->_fictif)
            ) {
                // Statut IV - ; INSi -
                // Pas de type de justificatif à haut niveau de confiance et pas de modification
                // de l'identité sur la base des retours INSi
                // OU le patient a l'attribut "identité douteuse"
                // OU le patient a l'attribut "identité fictive"
                $status = 'PROV';
            } elseif (!$type_justificatif && $mode_obtention === 'insi') {
                // Statut IV - ; INSi +
                // Pas de type de justificatif à haut niveau de confiance et identité créée sur la base des retours INSi
                $status = 'RECUP';
            } elseif ($type_justificatif && $mode_obtention !== 'insi') {
                // Statut IV + ; INSi -
                $status = 'VALI';
            } elseif ($type_justificatif && $mode_obtention === 'insi') {
                // Statut IV + ; INSi +
                $status = 'QUAL';
            }
        }

        $msg = null;

        if ($status && ($this->patient->status !== $status)) {
            $this->patient->status = $status;
            $msg                   = $this->patient->store();
        }

        self::$updating_status = false;

        return $msg;
    }
}
