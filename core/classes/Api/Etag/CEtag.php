<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Etag;


use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Cache;

class CEtag
{
    const CACHE_PREFIX = 'etags';
    const CACHE_PREFIX_TAGGING = 'etags_tagging';
    const CACHE_TTL = 3600;

    const TYPE_LOCALES = 'locales';
    const TYPE_CONFIGURATIONS = 'configurations';
    const TYPE_PREFERENCES = 'preferences';

    private $types = [
        self::TYPE_LOCALES,
        self::TYPE_CONFIGURATIONS,
        self::TYPE_PREFERENCES,
    ];


    private $etag;
    private $type;
    private $uri;

    /** @var Cache */
    private $cache;

    /** @var Cache */
    private $cache_tagging;


    /**
     * CEtag constructor.
     *
     * @param string $etag
     * @param string $uri
     *
     * @throws CApiException
     */
    public function __construct(string $etag, string $uri)
    {
        $this->etag = $this->sanitize($etag);

        // Etag from response headers (type:hash)
        if (str_contains($etag, ':')) {
            [$this->type, $this->etag] = explode(':', $this->etag);
            if (!in_array($this->type, $this->types, true)) {
                throw new CApiException('Invalid Etag type ' . $this->type);
            }
        }

        $this->uri = $uri;

        $this->cache = new Cache(static::CACHE_PREFIX, $this->etag, Cache::INNER_OUTER, static::CACHE_TTL);

        if ($this->type) {
            $this->cache_tagging = new Cache(static::CACHE_PREFIX_TAGGING, $this->type, Cache::INNER_OUTER);
        }
    }


    /**
     * @return mixed
     */
    public function getEtag()
    {
        return $this->etag;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    public function checkValidity(): bool
    {
        if (!$this->cache->exists()) {
            return false;
        }

        $urls = $this->cache->get(); // etags-hash => ['url1','url2']

        if (!is_array($urls)) {
            return false;
        }

        return in_array($this->uri, $urls, true);
    }

    private function sanitize(string $etag): string
    {
        return str_replace('"', '', $etag);
    }

    public function cache(): void
    {
        $urls = $this->cache->get();

        if (is_null($urls)) {
            // new entry
            $this->cache->put([$this->uri]);

            // add entry to cache tagging if etag is typed
            if ($this->cache_tagging) {
                $tagging_hash = $this->cache_tagging->get() ?: [];
                $tagging_hash[] = $this->etag;
                $this->cache_tagging->put($tagging_hash);
            }
        } elseif (!in_array($this->uri, $urls, true)) {
            // update entry
            $urls[] = $this->uri;
            $this->cache->put($urls);
        }
    }


    public function __toString()
    {
        return (string)$this->etag;
    }
}
