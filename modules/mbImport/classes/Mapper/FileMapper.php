<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Ox\Import\Framework\Entity\File;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Mapper\AbstractMapper;
use Ox\Mediboard\Files\CFile;

/**
 * Mediboard file mapper
 */
class FileMapper extends AbstractMapper {
  /**
   * @inheritDoc
   */
  protected function createEntity($row): EntityInterface {

    $state = [
      'external_id'     => $row[$this->metadata->getIdentifier()],
      'file_name'       => $row['file_name'],
      'file_date'       => $this->convertToDateTime($row['file_date']),
      'file_content'    => $this->getFileContent($row['file_real_filename']),
      'author_id'       => $row['author_id'],
      'file_type'       => $row['file_type'],
      'sejour_id'       => ($row['object_class'] == 'CSejour') ? $row['object_id'] : null,
      'consultation_id' => ($row['object_class'] == 'CConsultation') ? $row['object_id'] : null,
      'patient_id'      => ($row['object_class'] == 'CPatient') ? $row['object_id'] : null,
    ];

    return File::fromState($state);
  }

  /**
   * @param string $file_real_filename
   *
   * @return string|null
   */
  protected function getFileContent(string $file_real_filename): ?string {
    $sub_dir      = CFile::getSubDir($file_real_filename);
    $absolute_dir = CFile::getPrivateDirectory() . $sub_dir;
    $file_path    = $absolute_dir . DIRECTORY_SEPARATOR . $file_real_filename;

    return (is_readable($file_path)) ? $file_path : null;
  }
}
