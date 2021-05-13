<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Persister;

use Ox\Core\CStoredObject;
use Ox\Import\Framework\Exception\PersisterException;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 */
class DefaultPersister extends AbstractPersister
{
    public function persistObject(CStoredObject $object): CStoredObject
    {
        switch (get_class($object)) {
            case CPatient::class:
                return $this->persistPatient($object);
            case CFile::class:
                return $this->persistFile($object);
            default:
            return parent::persistObject($object);
        }
    }

    public function persistPatient(CPatient $patient): CPatient
    {
        $patient->_generate_IPP   = false;
        $patient->_mode_obtention = 'import';

        return $this->persist($patient);
    }

    public function persistFile(CFile $file): CFile
    {
        if (!$file->_file_path) {
            throw new PersisterException('PersisterException-File must have a context');
        }

        return $this->persist($file);
    }
}
