<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use Exception;
use Ox\Core\CMbModelNotFoundException;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;

/**
 * Class CGitlabProject
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabProject extends CMbObject {
  /** @var integer Primary key */
  public $ox_gitlab_project_id;

  /** @var integer */
  public $id;

  /** @var string */
  public $name;

  /** @var string */
  public $name_with_namespace;

  /** @var string */
  public $web_url;

  /** @var bool */
  public $ready;

  /** @var bool */
  public $bind;

  /** @var CGitlabBranch[] */
  public $_ref_branches;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table    = 'gitlab_project';
    $spec->key      = 'ox_gitlab_project_id';
    $spec->seek     = 'match';
    $spec->loggable = false;

    $spec->uniques['project'] = ['id'];
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps(): array {
    $props = parent::getProps();
    $props['id']                  = 'num pos notNull';
    $props['name']                = 'str seekable notNull';
    $props['name_with_namespace'] = 'str seekable notNull';
    $props['web_url']             = 'str';
    $props['ready']               = 'bool notNull default|0';
    $props['bind']                = 'bool notNull default|1';
    return $props;
  }

  /**
   * @inheritDoc
   */
  function updateFormFields()
  {
    parent::updateFormFields();

    $this->_view      = $this->name_with_namespace;
    $this->_shortview = $this->name;
  }

  /**
   * Returns whether the current project shall be integrated or not
   *
   * @return bool
   */
  public function isReady(): bool {
    return !empty($this->ready);
  }

  /**
   * Load all the branches a commit is present on
   *
   * @return CGitlabBranch[]|CStoredObject[]
   * @throws Exception
   */
  function loadRefBranches(): array {
    return $this->_ref_branches = !empty($this->_ref_branches) ?: $this->loadBackRefs("gitlab_project_branches");
  }

  /**
   * @return static
   * @throws CMbModelNotFoundException
   */
  public static function getMediboardProject(): self {
    $project       = new self();
    $project->name = 'mediboard';

    if ($project->loadMatchingObject()) {
      return $project;
    }

    throw new CMbModelNotFoundException('common-error-Object not found');
  }
}
