<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Segments;

use Ox\Core\CMbDT;
use Ox\Interop\Eai\CInteropActor;
use Ox\Mediboard\Patients\CPatient;

/**
 * Class CHL7v2SegmentPID
 * PID - Represents an HL7 PID message segment (Patient Identification)
 */
class CHL7v2SegmentPID_FR extends CHL7v2SegmentPID
{
    /**
     * Fill other identifiers
     *
     * @param array         &$identifiers Identifiers
     * @param CPatient       $patient     Person
     * @param CInteropActor  $actor       Interop actor
     *
     * @return null
     */
    function fillOtherIdentifiers(&$identifiers, CPatient $patient, CInteropActor $actor = null)
    {
        // INS-C
        $ins = $patient->loadLastINS();
        if ($ins) {
            $identifiers[] = [
                $ins->ins,
                null,
                null,
                // PID-3-4 Autorité d'affectation
                $this->getAssigningAuthority("INS-$ins->type"),
                "INS-$ins->type",
                null,
                $ins->date ? CMbDT::date($ins->date) : null,
            ];
        }

        // INS-NIR - INS-NIA uniquement si l'identité du patient est qualifiée
        $patient_ins_nir = $patient->loadRefPatientINSNIR();
        if ($patient_ins_nir->_id && $patient->status === 'QUAL') {
            $source_identite = $patient_ins_nir->loadRefSourceIdentite();
            $ins_type = $patient_ins_nir->is_nia ? 'INS-NIA' : 'INS-NIR';

            $identifiers[] = [
                $patient_ins_nir->ins_nir,
                null,
                null,
                // PID-3-4 Autorité d'affectation
                $this->getAssigningAuthority($ins_type),
                $ins_type,
                null,
                $source_identite->debut ? CMbDT::date($source_identite->debut) : null,
                $source_identite->fin ? CMbDT::date($source_identite->fin) : null,
            ];
        }

        if ($patient->matricule) {
            $identifiers[] = [
                $patient->matricule,
                null,
                null,
                // PID-3-4 Autorité d'affectation
                $this->getAssigningAuthority("INSEE"),
                "SS",
            ];
        }

        if ($actor->_configs["send_own_identifier"]) {
            $identifiers[] = [
                $patient->_id,
                null,
                null,
                // PID-3-4 Autorité d'affectation
                $this->getAssigningAuthority("mediboard", null, null, null, $actor->group_id),
                $actor->_configs["build_identifier_authority"] == "PI_AN" ? "PI" : "RI",
            ];
        }
    }
}
