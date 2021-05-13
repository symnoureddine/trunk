<?php

/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Developpement\Tests\Unit;

use Ox\Core\CAppUI;
use Ox\Core\CMbPath;
use Ox\Mediboard\Developpement\CModuleBuilder;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class CModuleBuilderTest extends UnitTestMediboard
{
    private const DUMMY_MOD_IMAGE = 'mod_image';

    private const FILES_CHECK = [
        '/classes/CConfigurationDummy.php',
        '/classes/CMyClass.php',
        '/classes/CSetupDummy.php',
        '/classes/CTabsDummy.php',
        '/locales/fr.php',
        '/templates/configure.tpl',
        '/composer.json',
        '/config.php',
        '/configure.php',
    ];

    public function testBuildModuleExists(): void
    {
        $builder = new CModuleBuilder(
            'dPdeveloppement',
            'Ox\\Mediboard\\Developpement',
            'dev',
            'dev',
            'GNU GPL',
            'dev',
            'autre',
            'autre',
            'Mediboard',
            ''
        );


        $this->expectExceptionMessage("Module 'dPdeveloppement' existe déjà");
        $builder->build();
    }

    public function testBuildDummyModule(): void
    {
        $current_dir = CAppUI::conf('root_dir') . '/modules/dPdeveloppement/tests/Resources/';
        $builder     = new CModuleBuilder(
            'dummy',
            'Ox\\Mediboard\\Dummy',
            'dum',
            'dummy',
            'OXOL',
            'dum',
            'autre',
            'autre',
            'Mediboard',
            file_get_contents($current_dir . self::DUMMY_MOD_IMAGE)
        );

        $builder->build();

        $dummy_dir = CAppUI::conf('root_dir') . '/modules/dummy';
        $this->assertDirectoryExists($dummy_dir);

        // Check if files have been created
        foreach (self::FILES_CHECK as $_file) {
            $this->assertFileExists($dummy_dir . $_file);
        }

        // Check if keywords have been replaced
        $this->assertNotFalse(strpos(file_get_contents($dummy_dir . '/classes/CMyClass.php'), 'Ox\\Mediboard\\Dummy'));

        // Check logo
        $this->assertFileEquals($current_dir . 'icon.png', $dummy_dir . '/images/iconographie/fr/icon.png');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $dummy_dir = CAppUI::conf('root_dir') . '/modules/dummy';
        if (is_dir($dummy_dir)) {
            CMbPath::remove($dummy_dir);
        }
    }
}
