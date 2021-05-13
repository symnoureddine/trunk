<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Etag;


use Ox\Core\Api\Etag\CEtag;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Cache;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CEtagTest extends UnitTestMediboard
{
    const URL = 'api/tests/unit';

    public function testCheckValiditySuccess()
    {
        $hash = $this->forgeEtag();

        $cache = new Cache(CEtag::CACHE_PREFIX, $hash, Cache::INNER_OUTER);
        $cache->put([self::URL]);

        $etag = new CEtag($hash, self::URL);

        $this->assertTrue($etag->checkValidity());
    }

    private function forgeEtag()
    {
        return md5(uniqid('hash', true));
    }


    public function testCheckValidityFailed()
    {
        $hash = $this->forgeEtag();
        $etag = new CEtag($hash, self::URL);

        $this->assertFalse($etag->checkValidity());
    }

    public function testCheckValidityFailedCacheMalformatted()
    {
        $hash  = $this->forgeEtag();
        $cache = new Cache(CEtag::CACHE_PREFIX, $hash, Cache::INNER_OUTER);
        $cache->put('toto');
        $etag = new CEtag($hash, self::URL);

        $this->assertFalse($etag->checkValidity());
    }


    public function testCache()
    {
        $hash           = $this->forgeEtag();
        $hash_with_type = CEtag::TYPE_LOCALES . ':' . $hash;
        $etag           = new CEtag($hash_with_type, self::URL);
        $etag->cache();

        $etag = new CEtag($hash_with_type, self::URL . '?filter=toto');
        $etag->cache();

        $cache = new Cache(CEtag::CACHE_PREFIX, $hash, Cache::INNER_OUTER);
        $this->assertTrue($cache->exists());
        $this->assertContains(self::URL, $cache->get());
        $this->assertContains(self::URL . '?filter=toto', $cache->get());

        $cache_tagging = new Cache(CEtag::CACHE_PREFIX_TAGGING, CEtag::TYPE_LOCALES, Cache::INNER_OUTER);
        $this->assertTrue($cache_tagging->exists());
        $this->assertContains($hash, $cache_tagging->get());
        $this->assertCount(1, $cache_tagging->get());
    }

    public function testStringable()
    {
        $hash = $this->forgeEtag();
        $etag = new CEtag($hash, self::URL);
        $this->assertIsString((string)$etag);
    }

    public function testTypeException()
    {
        $hash           = $this->forgeEtag();
        $hash_with_type = 'invalid_type:' . $hash;
        $this->expectException(CApiException::class);
        new CEtag($hash_with_type, self::URL);
    }

    public function testGetter()
    {
        $hash = '"' . $this->forgeEtag() . '"';
        $etag = new CEtag($hash, self::URL);

        $this->assertStringNotContainsString('"', $etag->getEtag());
        $this->assertEquals($etag->getEtag(), $this->invokePrivateMethod($etag, 'sanitize', $hash));
        $this->assertEquals(self::URL, $etag->getUri());
    }

}
