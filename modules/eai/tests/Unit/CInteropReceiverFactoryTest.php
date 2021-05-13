<?php
/**
 * @package Core\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai\Tests\Unit;

use Ox\Interop\Dmp\CReceiverDMP;
use Ox\Interop\Eai\CInteropReceiverFactory;
use Ox\Interop\Eai\CReceiverException;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Hl7\CReceiverHL7v2;
use Ox\Interop\Hl7\CReceiverHL7v3;
use Ox\Interop\Hprimxml\CDestinataireHprim;
use Ox\Mediboard\Doctolib\CReceiverHL7v2Doctolib;
use Ox\Tests\TestsException;
use Ox\Tests\UnitTestMediboard;

/**
 * Class CAppTest
 * @package Ox\Core\Tests\Unit
 */
class CInteropReceiverFactoryTest extends UnitTestMediboard
{
    /**
     * @dataProvider providerReceiver
     *
     * @param string $method        Method
     * @param string $classname     Classname
     * @param string $receiver_type Receiver type i.e : Doctolib, AppFine
     */
    public function testMake($method, $classname, $receiver_type = null)
    {
        $class = CInteropReceiverFactory::$method($receiver_type);
        $this->assertInstanceOf($classname, $class);
    }

    /**
     * Test make with exception
     */
    public function testMakeWithException()
    {
        $this->expectException(CReceiverException::class);

        CInteropReceiverFactory::makeHL7v2('Test');
    }
//
//    /**
//     * @throws TestsException
//     */
//    public function testMakeWithInternalException()
//    {
//        $this->expectException(CReceiverException::class);
//        $this->invokePrivateMethod(CInteropReceiverFactory::class, 'make', ['toto', 'null']);
//    }

    /**
     * Provider
     *
     * @return array
     */
    public function providerReceiver()
    {
        return [
            "DMP"           => [
                'makeDMP',
                CReceiverDMP::class,
                null,
            ],
            "HL7v2"         => [
                'makeHL7v2',
                CReceiverHL7v2::class,
                null,
            ],
            "HL7v2Doctolib" => [
                'makeHL7v2',
                CReceiverHL7v2Doctolib::class,
                'Doctolib',
            ],
            "HL7v3"         => [
                'makeHL7v3',
                CReceiverHL7v3::class,
                null,
            ],
            "FHIR"          => [
                'makeFHIR',
                CReceiverFHIR::class,
                null,
            ],
            "HprimXML"      => [
                'makeHprimXML',
                CDestinataireHprim::class,
                null,
            ],
        ];
    }
}
