<?php

namespace Ox\Core\Tests\Unit\Auth;

use Exception;
use Ox\Core\Auth\BasicAuthentication;
use Ox\Core\Auth\CAuthentication;
use Ox\Core\Auth\Exception\CouldNotAuthenticate;
use Ox\Core\Auth\LoginAuthentication;
use Ox\Core\Auth\SessionAuthentication;
use Ox\Core\Auth\StandardAuthentication;
use Ox\Core\Auth\TokenAuthentication;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\CViewAccessToken;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CAuthenticationTest extends UnitTestMediboard
{
    /** @var string */
    private $username;

    /** @var string */
    private $lastname;

    /** @var string */
    private $password;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->setUserInfos();
        parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testConstruct(): void
    {
        $request        = Request::create('/');
        $authentication = new CAuthentication($request);

        $this->assertInstanceOf(CAuthentication::class, $authentication);
        $this->assertNull($authentication->getUser());
    }

    private function setUserInfos(): void
    {
        $this->username = 'PHPUnit';
        $this->lastname = 'XUnit';
        $this->password = CAppUI::conf('sourceCode phpunit_user_password');
    }

    /**
     * @dataProvider getSuccessfulAuths
     *
     * @param string  $method
     * @param Request $request
     * @param string  $service_class
     *
     * @throws Exception
     */
    public function testSuccessfulAuth(string $method, Request $request, string $service_class): void
    {
        $user_expected = $this->getPhpUnitUser();

        $authentication = new CAuthentication($request);
        $authentication->doAuth();

        $elected_service = $authentication->getElectedService();

        $this->assertEquals($method, $authentication->getMethod());
        $this->assertEquals($user_expected->_id, $authentication->getUser()->user_id);
        $this->assertNotNull($elected_service);
        $this->assertEquals($service_class, get_class($elected_service));
    }

    /**
     * /!\ Create tokens outside of dataProviders because of running in separate process causes the token suppression
     *
     * @throws Exception
     */
    public function testSuccessfulTokenAuth(): void
    {
        $token = $this->getPhpUnitToken(false)->hash;

        $requests = [
            'Token - GUI' => ['token', $this->getTokenRequest(false, $token), TokenAuthentication::class],
            'Token - API' => ['token', $this->getTokenRequest(true, $token), TokenAuthentication::class],
        ];

        foreach ($requests as $_name => $_data) {
            [$method, $request, $service_class] = $_data;

            $user_expected = $this->getPhpUnitUser();

            $authentication = new CAuthentication($request);
            $authentication->doAuth();

            $elected_service = $authentication->getElectedService();

            $this->assertEquals($method, $authentication->getMethod());
            $this->assertEquals($user_expected->_id, $authentication->getUser()->user_id);
            $this->assertNotNull($elected_service);
            $this->assertEquals($service_class, get_class($elected_service));
        }
    }

    /**
     * @dataProvider getFailedAuths
     *
     * @throws Exception
     */
    public function testFailedAuth(Request $request, string $service_class, ?string $redirection = null): void
    {
        $authentication = new CAuthentication($request);

        try {
            $authentication->doAuth();
            $this->fail('No exception thrown');
        } catch (Throwable $t) {
            $elected_service = $authentication->getElectedService();

            $this->assertNotNull($elected_service);
            $this->assertEquals($service_class, get_class($elected_service));

            $this->assertInstanceOf(CouldNotAuthenticate::class, $t);

            if ($redirection !== null) {
                $this->assertEquals($redirection, $t->getRedirection());
            }
        }
    }

    /**
     * /!\ Create tokens outside of dataProviders because of running in separate process causes the token suppression
     *
     * @throws Exception
     */
    public function testFailedTokenAuth(): void
    {
        $expired_token = $this->getPhpUnitToken(true)->hash;

        $requests = [
            'Token - GUI'           => [
                $this->getTokenRequest(false, 'azerty'),
                TokenAuthentication::class,
                CouldNotAuthenticate::REDIRECT_LOGIN,
            ],
            'Token - GUI - Expired' => [
                $this->getTokenRequest(false, $expired_token),
                TokenAuthentication::class,
                CouldNotAuthenticate::REDIRECT_LOGIN,
            ],
            'Token - API'           => [
                $this->getTokenRequest(true, 'azerty'),
                TokenAuthentication::class,
            ],
            'Token - API - Expired' => [
                $this->getTokenRequest(true, $expired_token),
                TokenAuthentication::class,
            ],
        ];

        foreach ($requests as $_name => $_data) {
            [$request, $service_class,] = $_data;
            $redirection = ($_data[2]) ?? null;

            $authentication = new CAuthentication($request);

            try {
                $authentication->doAuth();
                $this->fail('No exception thrown');
            } catch (Throwable $t) {
                $elected_service = $authentication->getElectedService();

                $this->assertNotNull($elected_service);
                $this->assertEquals($service_class, get_class($elected_service));

                $this->assertInstanceOf(CouldNotAuthenticate::class, $t);

                if ($redirection !== null) {
                    $this->assertEquals($redirection, $t->getRedirection());
                }
            }
        }
    }

    /**
     * @dataProvider getNoServiceAuths
     *
     * @throws Exception
     */
    public function testNoServiceAuth(Request $request, ?string $redirection = null): void
    {
        $authentication = new CAuthentication($request);

        try {
            $authentication->doAuth();
            $this->fail('No exception thrown');
        } catch (Throwable $t) {
            $elected_service = $authentication->getElectedService();

            $this->assertNull($elected_service);
            $this->assertInstanceOf(CouldNotAuthenticate::class, $t);

            if ($redirection !== null) {
                $this->assertEquals($redirection, $t->getRedirection());
            }
        }
    }

    /**
     * @dataProvider getWeakPasswords
     *
     * @throws Exception
     */
    public function testWeakPasswords(Request $request, ?string $redirection = null): void
    {
        $authentication = $this->getMockBuilder(CAuthentication::class)
            ->setConstructorArgs([$request])
            ->setMethods(['hasUserAWeakPassword'])
            ->getMock();

        $matcher = ($request->attributes->getBoolean('is_api')) ? $this->never() : $this->once();

        $authentication->expects($matcher)->method('hasUserAWeakPassword')->willReturn(true);

        try {
            $authentication->doAuth();
            $authentication->afterAuth();
        } catch (Throwable $t) {
            $this->assertInstanceOf(CouldNotAuthenticate::class, $t);

            if ($redirection !== null) {
                $this->assertEquals($redirection, $t->getRedirection());
            }
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSuccessfulAuths(): array
    {
        // In order to determine the number of tests,
        // PHPUnit runs dataProviders before actually running the tests (and the setUp method)
        $this->setUserInfos();

        $requests = [];
        $requests = array_merge($requests, $this->getSuccessfulBasicAuths());
        //$requests = array_merge($requests, $this->getSuccessfulTokenAuths());
        $requests = array_merge($requests, $this->getSuccessfulLoginAuths());
        $requests = array_merge($requests, $this->getSuccessfulStandardAuths());

        return $requests;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFailedAuths(): array
    {
        // In order to determine the number of tests,
        // PHPUnit runs dataProviders before actually running the tests (and the setUp method)
        $this->setUserInfos();

        $requests = [];
        $requests = array_merge($requests, $this->getFailedBasicAuths());
        //$requests = array_merge($requests, $this->getFailedTokenAuths());
        $requests = array_merge($requests, $this->getFailedLoginAuths());
        $requests = array_merge($requests, $this->getFailedStandardAuths());

        // Todo: Session authentication hard to test because of test bootstrap initializing CAppUI::$instance
        //$requests = array_merge($requests, $this->getFailedSessionAuths());

        return $requests;
    }

    public function getNoServiceAuths(): array
    {
        $gui_request = Request::create('/');
        $gui_request->attributes->set('is_api', false);

        $api_request = Request::create('/');
        $api_request->attributes->set('is_api', true);

        return [
            'NO METHOD - GUI' => [$gui_request, CouldNotAuthenticate::REDIRECT_LOGIN],
            'NO METHOD - API' => [$gui_request],
        ];
    }

    public function getWeakPasswords(): array
    {
        // In order to determine the number of tests,
        // PHPUnit runs dataProviders before actually running the tests (and the setUp method)
        $this->setUserInfos();

        $requests = [];
        $requests = array_merge($requests, $this->getWeakBasicAuths());
        $requests = array_merge($requests, $this->getWeakLoginAuths());
        $requests = array_merge($requests, $this->getWeakStandardAuths());

        return $requests;
    }

    private function getSuccessfulBasicAuths(): array
    {
        return [
            'Basic - GUI' => [
                'basic',
                $this->getBase64Request(false, $this->username, $this->password),
                BasicAuthentication::class,
            ],
            'Basic - API' => [
                'basic',
                $this->getBase64Request(true, $this->username, $this->password),
                BasicAuthentication::class,
            ],
        ];
    }

    //    private function getSuccessfulTokenAuths(): array
    //    {
    //        $token = $this->getPhpUnitToken(false)->hash;
    //
    //        return [
    //            'Token - GUI' => ['token', $this->getTokenRequest(false, $token), TokenAuthentication::class],
    //            'Token - API' => ['token', $this->getTokenRequest(true, $token), TokenAuthentication::class],
    //        ];
    //    }

    private function getSuccessfulLoginAuths(): array
    {
        return [
            'Login - GUI' => [
                'standard',
                $this->getLoginRequest(false, $this->username, $this->password),
                LoginAuthentication::class,
            ],
            'Login - API' => [
                'standard',
                $this->getLoginRequest(true, $this->username, $this->password),
                LoginAuthentication::class,
            ],
        ];
    }

    private function getSuccessfulStandardAuths(): array
    {
        return [
            'Standard - GUI' => [
                'standard',
                $this->getStandardRequest(false, $this->username, $this->password),
                StandardAuthentication::class,
            ],
            'Standard - API' => [
                'standard',
                $this->getStandardRequest(true, $this->username, $this->password),
                StandardAuthentication::class,
            ],
        ];
    }

    private function getFailedBasicAuths(): array
    {
        return [
            'Basic - GUI' => [
                $this->getBase64Request(false, 'lorem', 'ipsum'),
                BasicAuthentication::class,
                CouldNotAuthenticate::REDIRECT_LOGIN,
            ],
            'Basic - API' => [
                $this->getBase64Request(true, 'lorem', 'ipsum'),
                BasicAuthentication::class,
            ],
        ];
    }

    //    private function getFailedTokenAuths(): array
    //    {
    //        $expired_token = $this->getPhpUnitToken(true)->hash;
    //
    //        return [
    //            'Token - GUI'           => [
    //                $this->getTokenRequest(false, 'azerty'),
    //                TokenAuthentication::class,
    //                CouldNotAuthenticate::REDIRECT_LOGIN,
    //            ],
    //            'Token - GUI - Expired' => [
    //                $this->getTokenRequest(false, $expired_token),
    //                TokenAuthentication::class,
    //                CouldNotAuthenticate::REDIRECT_LOGIN,
    //            ],
    //            'Token - API'           => [
    //                $this->getTokenRequest(true, 'azerty'),
    //                TokenAuthentication::class,
    //            ],
    //            'Token - API - Expired' => [
    //                $this->getTokenRequest(true, $expired_token),
    //                TokenAuthentication::class,
    //            ],
    //        ];
    //    }

    private function getFailedLoginAuths(): array
    {
        return [
            'Login - GUI' => [
                $this->getLoginRequest(false, 'lorem', 'ipsum'),
                LoginAuthentication::class,
                CouldNotAuthenticate::REDIRECT_LOGIN,
            ],
            'Login - API' => [
                $this->getLoginRequest(true, 'lorem', 'ipsum'),
                LoginAuthentication::class,
            ],
        ];
    }

    private function getFailedStandardAuths(): array
    {
        return [
            'Standard - GUI' => [
                $this->getStandardRequest(false, 'lorem', 'ipsum'),
                StandardAuthentication::class,
                CouldNotAuthenticate::REDIRECT_LOGIN,
            ],
            'Standard - API' => [
                $this->getStandardRequest(true, 'lorem', 'ipsum'),
                StandardAuthentication::class,
            ],
        ];
    }

    private function getFailedSessionAuths(): array
    {
        $session_name = CAppUI::forgeSessionName();

        $gui_request = Request::create('/');
        $gui_request->cookies->set($session_name, '123456');
        $gui_request->attributes->set('is_api', false);

        $api_request = Request::create('/');
        $api_request->cookies->set($session_name, '123456');
        $api_request->attributes->set('is_api', true);

        return [
            'Session - GUI' => [$gui_request, SessionAuthentication::class, CouldNotAuthenticate::REDIRECT_LOGIN],
            'Session - API' => [$api_request, SessionAuthentication::class],
        ];
    }

    private function getWeakBasicAuths(): array
    {
        return [
            'Basic - GUI' => [
                $this->getBase64Request(false, $this->username, $this->password),
                CouldNotAuthenticate::REDIRECT_CHANGE_PASSWORD,
            ],
            'Basic - API' => [
                $this->getBase64Request(true, $this->username, $this->password),
            ],
        ];
    }

    private function getWeakLoginAuths(): array
    {
        return [
            'Login - GUI' => [
                $this->getLoginRequest(false, $this->username, $this->password),
                CouldNotAuthenticate::REDIRECT_CHANGE_PASSWORD,
            ],
            'Login - API' => [
                $this->getLoginRequest(true, $this->username, $this->password),
            ],
        ];
    }

    private function getWeakStandardAuths(): array
    {
        return [
            'Standard - GUI' => [
                $this->getStandardRequest(false, $this->username, $this->password),
                CouldNotAuthenticate::REDIRECT_CHANGE_PASSWORD,
            ],
            'Standard - API' => [
                $this->getStandardRequest(true, $this->username, $this->password),
            ],
        ];
    }

    private function getBase64Request(bool $is_api, ?string $username = null, ?string $password = null): Request
    {
        $base_64 = base64_encode("{$username}:{$password}");
        $request = Request::create('/');

        $request->headers->set('Authorization', 'Basic ' . $base_64);
        $request->attributes->set('is_api', $is_api);

        return $request;
    }

    private function getLoginRequest(bool $is_api = false, ?string $username = null, ?string $password = null): Request
    {
        $request = Request::create('/', 'GET', ['login' => "{$username}:{$password}"]);
        $request->attributes->set('is_api', $is_api);

        return $request;
    }

    private function getTokenRequest(bool $is_api = false, ?string $token = null): Request
    {
        if ($is_api) {
            $request = Request::create('/');
            $request->headers->set(TokenAuthentication::HEADER_KEY, $token);
        } else {
            $request = Request::create('/', 'GET', ['token' => $token]);
        }

        $request->attributes->set('is_api', $is_api);

        return $request;
    }

    private function getStandardRequest(
        bool $is_api = false,
        ?string $username = null,
        ?string $password = null
    ): Request {
        $request = Request::create('/', 'POST', ['username' => $username, 'password' => $password]);
        $request->attributes->set('is_api', $is_api);

        return $request;
    }

    /**
     * @return CUser
     * @throws Exception
     */
    private function getPhpUnitUser(): CUser
    {
        $user                 = new CUser();
        $user->user_username  = $this->username;
        $user->user_last_name = $this->lastname;
        $user->loadMatchingObject();

        return $user;
    }

    /**
     * @return CViewAccessToken
     * @throws Exception
     */
    private function getPhpUnitToken(bool $expired = false): CViewAccessToken
    {
        $token                 = new CViewAccessToken();
        $token->user_id        = $this->getPhpUnitUser()->_id;
        $token->datetime_start = CMbDT::dateTime('-5 seconds');

        if ($expired) {
            $token->_hash_length = $token::DEFAULT_HASH_LENGTH;
            $token->datetime_end = CMbDT::dateTime('-2 seconds');
        } else {
            $token->datetime_end = CMbDT::dateTime('+ 1 month');
        }

        $token->purgeable = 1;
        $token->params    = "m=php\na=unit";

        try {
            $token->store();
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        return $token;
    }
}
