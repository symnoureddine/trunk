<?php

use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CStoredObject;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Cabinet\Generators\CActeNGAPGenerator;
use Ox\Mediboard\CompteRendu\Generators\CCompteRenduGenerator;
use Ox\Mediboard\Files\CFile;
use Ox\Tests\UnitTestMediboard;

/**
 * @package Mediboard\\${Module}
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
class CObjectGeneratorTest extends UnitTestMediboard
{
    private const EXCLUDED_GENERATOR = [
        CActeNGAPGenerator::class,
        CCompteRenduGenerator::class,
    ];

    public function listGenerators()
    {
        $generators = CClassMap::getInstance()->getClassChildren(CObjectGenerator::class, false, true);
        $datas      = [];
        foreach ($generators as $generator) {
            if (in_array($generator, self::EXCLUDED_GENERATOR, true)) {
                continue;
            }
            $datas[$generator] = [new $generator()];
        }

        return $datas;
    }

    /**
     * @param CObjectGenerator $generator
     *
     * @pref         listDefault br
     *
     * @dataProvider listGenerators
     */
    public function testGenerate(CObjectGenerator $generator)
    {
        $obj = $generator->generate();
        $this->assertInstanceOf(CStoredObject::class, $obj);
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        CFile::$directory  = str_replace('\\', '/', realpath(CAppUI::conf("dPfiles CFile upload_directory")));
        $directory_private = CAppUI::conf("dPfiles CFile upload_directory_private");
        if ($directory_private && is_dir($directory_private)) {
            CFile::$directory_private = rtrim(str_replace('\\', '/', realpath($directory_private)), '/') . '/';
        }
    }
}
