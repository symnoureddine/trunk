<?php

namespace Ox\Core\Tests\Unit;

use Exception;
use Ox\Core\CError;
use Ox\Mediboard\System\CErrorLog;
use Ox\Tests\UnitTestMediboard;

class CErrorTest extends UnitTestMediboard
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        CError::clearErrorBuffer();
    }

    public function testLogError()
    {
        $text         = uniqid('error_');
        $code         = array_rand(CError::$_types);
        $log          = new CErrorLog();
        $db_count     = $log->countList();

        CError::errorHandler($code, $text, __FILE__, __LINE__);

        // Wait for writting finish before next glob
        sleep(1);

        $this->assertTrue(CError::countWaitingBuffer() > 0);

        CError::storeBuffer(CError::getCurrentFileBuffer());

        $this->assertTrue($log->countList() > $db_count);

        $log->text = $text;
        $log->loadMatchingObject();
        $this->assertNotNull($log->_id);
    }


    public function testWaitingBuffer()
    {
        $this->assertEquals(count(CError::globWaitingBuffer()), CError::countWaitingBuffer());
    }

    /**
     * @dataProvider puissanceProvider
     */
    public function testExponentialError(int $nb_error, int $nb_lines)
    {
        $text = uniqid('exception_');
        $i    = 1;
        while ($i <= $nb_error) {
            // trigger error
            CError::exceptionHandler(new Exception($text));
            $i++;
        }

        $buffer = CError::getCurrentFileBuffer();
        $lines  = @file($buffer);
        // check lines
        $this->assertEquals($nb_lines, count($lines));

        CError::$buffered_signatures = [];
        CError::storeBuffer(CError::getCurrentFileBuffer());

        $log       = new CErrorLog();
        $log->text = $text;
        $log->loadMatchingObject();

        // check bd
        $this->assertEquals($log->count, 2 ** ($nb_lines - 1));
    }

    /**
     * On stock dans le buffer des la première erreure
     * On a tjs un décalage d'une ligne en trop dans le buffer mais au store en bdd on corrige ce décalage
     */
    public function puissanceProvider()
    {
        return [
            [1, 1],
            [2, 2],
            [3, 2],
            [4, 3],
            [8, 4],
            [63, 6],
            [64, 7],
            [65, 7],
        ];
    }

    public function testErrorTriggerdInSuccessProcess()
    {
        $text = uniqid('exception_');
        $max  = rand(1, 10);
        $i    = 1;
        while ($i <= $max) {
            // trigger error
            CError::exceptionHandler(new Exception($text));

            if ($i % 2) {
                CError::exceptionHandler(new Exception(uniqid('random_exception_')));
            }

            $i++;
        }

        CError::storeBuffer(CError::getCurrentFileBuffer());

        $log       = new CErrorLog();
        $log->text = $text;
        $log->loadMatchingObject();

        // check bd
        $this->assertEquals($log->count, $max);
    }


    /**
     * IHM TU
     * $i = 1;
     * $error = 'perdu';
     * echo '<table><tr><td>errors</td><td>lines</td><td>estimated count</td><td>real count</td></tr>';
     *
     * //ini_set("display_errors",0);
     * $randname = uniqid('random_');
     * $count_random = 0;
     *
     * while ($i <= 2048) {
     * trigger_error($error);
     * echo '<tr><td>'.$i.'</td>';
     *
     * $lines = file(CError::getCurrentFileBuffer());
     * $count_lines = count($lines)-1;
     *
     * echo '<td>'.$count_lines.'</td>';
     *
     * echo '<td>'. 2 ** $count_lines.'</td>';
     *
     * $real = CError::$buffered_signatures['6d8670cb7cf496bba16ce12fa15fedd9'];
     *
     * echo '<td>'. $real.'</td></tr>';
     * //
     * //    if (rand(0,5) === 1){
     * //        $count_random++;
     * //        trigger_error($randname);
     * //    }
     *
     * $i++;
     * }
     */
}
