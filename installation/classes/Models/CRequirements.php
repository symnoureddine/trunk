<?php
/**
 * @package Mediboard\\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Installation\Models;

/**
 * Class CRequirements
 */
class  CRequirements
{
    private $php_version;
    private $sql_version;
    private $php_extensions;
    private $url_restrictions;
    private $path_access;
    private $pdo;

    /**
     * CRequirements constructor.
     *
     * @param CPHPVersion     $php_version
     * @param CPHPExtension   $php_extensions
     * @param CUrlRestriction $url_restrictions
     * @param CPathAccess     $path_access
     * @param CMySQLVersion   $sql_version
     */
    public function __construct(
        CPHPVersion $php_version,
        CPHPExtension $php_extensions,
        CUrlRestriction $url_restrictions,
        CPathAccess $path_access,
        CMySQLVersion $sql_version
    ) {
        $this->php_version      = $php_version;
        $this->php_extensions   = $php_extensions;
        $this->url_restrictions = $url_restrictions;
        $this->path_access      = $path_access;
        $this->sql_version      = $sql_version;
    }


    /**
     * @return CPHPVersion
     */
    public function getPhpVersion(): CPHPVersion
    {
        return $this->php_version->getAll();
    }

    /**
     * @return CMySQLVersion
     */
    public function getSqlVersion(): CMySQLVersion
    {
        return $this->sql_version->getAll();
    }

    /**
     * @return CPrerequisite[]
     */
    public function getPhpExtensions(): array
    {
        return $this->php_extensions->getAll();
    }

    /**
     * @return CPrerequisite[]
     */
    public function getUrlRestrictions(): array
    {
        return $this->url_restrictions->getAll();
    }

    /**
     * @return CPrerequisite[]
     */
    public function getPathAccess(): array
    {
        return $this->path_access->getAll();
    }
}
