<?php
/**
 * @package Core\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Ox\Core\HttpClient\Client;
use Ox\Core\HttpClient\ClientException;
use Ox\Core\HttpClient\Response;
use Ox\Mediboard\System\CExchangeHTTP;
use Ox\Mediboard\System\CSourceHTTP;
use Ox\Tests\UnitTestMediboard;

/**
 * Class CClientTest
 */
class CClientTest extends UnitTestMediboard {
  CONST END_POINT = 'https://httpbin.org/';

  /**
   * @return CSourceHTTP
   * @throws Exception
   */
  private function getSourceHttp() {
    $source       = new CSourceHTTP();
    $source->host = static::END_POINT;

    return $source;
  }

  /**
   * @throws Exception
   */
  public function testConstruct() {
    $source = $this->getSourceHttp();
    $client = new Client($source);
    $this->assertInstanceOf(Client::class, $client);
  }

  /**
   * @group schedules
   * @throws GuzzleException
   * @throws ClientException
   */
  public function testCallGet() {
    $source = $this->getSourceHttp();
    $client = new Client($source);

    $response = $client->call('GET', '/get');
    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @group schedules
   * @throws ClientException
   */
  public function testCallGetFaild() {
    $source = $this->getSourceHttp();
    $source->host = 'https://toto-tata-titi.ipsum/';
    $source->loggable = true;
    $client = new Client($source);
    $this->expectException(ClientException::class);
    $client->call('GET', '/get');
  }

  /**
   * @group schedules
   * @throws GuzzleException
   * @throws ClientException
   */
  public function testCallGetLoggable() {
    $source           = $this->getSourceHttp();
    $source->loggable = true;
    $client           = new Client($source);
    $response          = $client->call('GET', '/get');
    $this->assertInstanceOf(CExchangeHTTP::class, $response->getExchangeHttp());
    $this->assertEquals($response->getExchangeHttp()->status_code, 200);
  }

  /**
   * @group schedules
   * @throws GuzzleException
   * @throws ClientException
   */
  public function testCallAuth() {
    $source = $this->getSourceHttp();
    $source->user     = 'lorem';
    $source->password = 'azerty1';
    $client = new Client($source);

    $response = $client->call('GET', '/basic-auth/lorem/azerty1');
    $this->assertEquals(200, $response->getStatusCode());
    $body = $response->getBody();
    $this->assertTrue($body['authenticated']);
  }
}