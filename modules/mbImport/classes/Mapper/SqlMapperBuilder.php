<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\MbImport\Mapper;

use Exception;
use Ox\Core\CSQLDataSource;
use Ox\Import\Framework\Adapter\MySqlAdapter;
use Ox\Import\Framework\Configuration\ConfigurableInterface;
use Ox\Import\Framework\Configuration\ConfigurationTrait;
use Ox\Import\Framework\Mapper\MapperBuilderInterface;
use Ox\Import\Framework\Mapper\MapperInterface;
use Ox\Import\Framework\Mapper\MapperMetadata;

/**
 * Description
 */
class SqlMapperBuilder implements MapperBuilderInterface, ConfigurableInterface
{
  use ConfigurationTrait;

  /** @var string string */
  private $dsn;

  /**
   * MbSqlMapperBuilder constructor.
   *
   * @param string $dsn
   */
  public function __construct(string $dsn)
  {
    $this->dsn = $dsn;
  }

  /**
   * @inheritDoc
   */
  public function build(string $name): MapperInterface
  {
    $adapter = new MySqlAdapter(CSQLDataSource::get($this->dsn));

    if ($this->configuration) {
      $adapter->setConfiguration($this->configuration);
    }

    switch ($name) {
      case 'utilisateur':
        $metadata = new MapperMetadata('users', 'user_id', $this->configuration);
        $mapper   = new UserMapper($metadata, $adapter);
        break;

      case 'patient':
        $metadata = new MapperMetadata('patients', 'patient_id', $this->configuration);
        $mapper   = new PatientMapper($metadata, $adapter);
        break;

      case 'medecin':
        $metadata = new MapperMetadata('medecin', 'medecin_id', $this->configuration);
        $mapper   = new MedecinMapper($metadata, $adapter);
        break;

      case 'plage_consultation':
        $metadata = new MapperMetadata('plageconsult', 'plageconsult_id', $this->configuration);
        $mapper   = new PlageConsultMapper($metadata, $adapter);
        break;

      case 'consultation':
        $metadata = new MapperMetadata('consultation', 'consultation_id', $this->configuration);
        $mapper   = new ConsultationMapper($metadata, $adapter);
        break;

      case 'consultation_anesthesique':
        $metadata = new MapperMetadata('consultation_anesth', 'consultation_anesth_id', $this->configuration);
        $mapper   = new ConsultationAnesthMapper($metadata, $adapter);
        break;

      case 'sejour':
        $metadata = new MapperMetadata('sejour', 'sejour_id', $this->configuration);
        $mapper   = new SejourMapper($metadata, $adapter);
        break;

      case 'fichier':
        $metadata = new MapperMetadata('files_mediboard', 'file_id', $this->configuration);
        $mapper   = new FileMapper($metadata, $adapter);
        break;

      case 'document':
        $metadata = new MapperMetadata('files_mediboard', 'file_id', $this->configuration);
        $mapper   = new DocumentMapper($metadata, $adapter);
        break;

      default:
        throw new Exception('Unknown mapper');
    }

    if ($mapper instanceof ConfigurableInterface && $this->configuration) {
      $mapper->setConfiguration($this->configuration);
    }

    return $mapper;
  }
}
