<?php
/**
 * @package Mediboard\\Api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Resources;

use Exception;
use Ox\Core\Api\Exceptions\CApiException;

/**
 * Class CItem
 */
class CItem extends CAbstractResource
{

    /** @var array */
    private $additional_datas = [];

    /**
     * CItem constructor.
     *
     * @param array|object $datas
     *
     * @throws CApiException
     */
    public function __construct($datas)
    {
        $model_class = is_object($datas) ? get_class($datas) : null;
        parent::__construct(CAbstractResource::TYPE_ITEM, $datas, $model_class);
    }

    /**
     * @inheritDoc
     */
    public function transform(): array
    {
        $datas_transformed = $this->createTransformer()->createDatas();

        // additional datas
        $datas_transformed['datas'] = array_merge($datas_transformed['datas'], $this->additional_datas);

        return $datas_transformed;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setDefaultMetas(): void
    {
        parent::setDefaultMetas();
        /*
        if (!$this->datas instanceof CStoredObject) {
          return;
        }

        $last_action = $this->datas->loadLastLog();
        $version     = $last_action->user_log_id;

        try {
          $dt     = new DateTime($last_action->date, new DateTimeZone('Europe/Paris'));
          $update = $dt->format('Y-m-d H:i:sP');
        }
        catch (Exception $exception) {
          $update = null;
        }

        $this->addMeta('version_id', $version);
        $this->addMeta('updated_at', $update);
        */
    }


    /**
     * @param array $datas
     *
     * @return CItem
     * @throws CApiException
     */
    public function addAdditionalDatas(array $datas): CItem
    {
        //if (count($datas) !== count($datas, COUNT_RECURSIVE)) {
            //throw new CApiException('Invalid multidimensional array');
        //}
        $this->additional_datas = array_merge($this->additional_datas, $datas);

        return $this;
    }


}
