<?php

namespace Ox\Core\Tests\Unit\Api\Etag;

use Ox\Core\Api\Etag\CEtag;
use Ox\Core\CController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CEtagController extends CController
{

    public function jsonWithoutEtag()
    {
        return new JsonResponse(static::getJson());
    }


    public function jsonWithEtag()
    {
        $response = new JsonResponse(static::getJson());
        $response->setEtag(static::getEtag());
        return $response;
    }

    public function jsonWithEtagTyped()
    {
        $response = new JsonResponse(static::getJson());
        $response->setEtag(static::getEtagTyped());
        return $response;
    }

    public static function getJson()
    {
        return json_encode(['lorem' => 'ipsum', 'foo' => 'bar']);
    }

    public static function getEtag()
    {
        return md5(static::getJson());
    }


    public static function getEtagTyped()
    {
        return CEtag::TYPE_LOCALES . ':' . static::getEtag();
    }
}
