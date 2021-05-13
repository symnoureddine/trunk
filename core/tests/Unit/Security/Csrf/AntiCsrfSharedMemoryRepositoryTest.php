<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Security\Csrf;

use Ox\Core\Security\Csrf\AntiCsrfSharedMemoryRepository;
use Ox\Core\Security\Csrf\AntiCsrfToken;
use Ox\Core\Security\Csrf\Exceptions\CouldNotGetCsrfToken;
use Ox\Core\Security\Csrf\Exceptions\CouldNotUseCsrf;
use Ox\Core\Shm\ISharedMemory;
use Ox\Tests\UnitTestMediboard;

/**
 * Description
 */
class AntiCsrfSharedMemoryRepositoryTest extends UnitTestMediboard
{
    /**
     * @param mixed $put_returns
     * @param mixed $get_returns
     * @param bool  $rem_return
     *
     * @return ISharedMemory
     */
    private function getSharedMemoryMock($put_returns, $get_returns, bool $rem_return): ISharedMemory
    {
        $shared_memory = $this->getMockBuilder(ISharedMemory::class)->getMock();

        if (is_array($put_returns)) {
            $shared_memory->method('put')->will($this->returnValueMap($put_returns));
        } else {
            $shared_memory->method('put')->willReturn($put_returns);
        }

        if (is_array($get_returns)) {
            $shared_memory->method('get')->will($this->returnValueMap($get_returns));
        } else {
            $shared_memory->method('get')->willReturn($get_returns);
        }

        $shared_memory->method('rem')->willReturn($rem_return);

        return $shared_memory;
    }

    private function getRepositoryMock(ISharedMemory $shared_memory): AntiCsrfSharedMemoryRepository
    {
        $repository = $this->getMockBuilder(AntiCsrfSharedMemoryRepository::class)
            ->setMethods(['getPrefix'])
            ->setConstructorArgs([$shared_memory])
            ->getMock();

        $repository->method('getPrefix')->willReturn('test-');

        return $repository;
    }

    private function getToken(): AntiCsrfToken
    {
        return AntiCsrfToken::generate('secret', [], 3600);
    }

    public function testInitShouldFail(): void
    {
        $this->expectException(CouldNotUseCsrf::class);

        $repository = $this->getRepositoryMock($this->getSharedMemoryMock(false, null, false));
        $repository->init('identifier', 'secret');
    }

    public function testInitSucceed(): void
    {
        $this->expectNotToPerformAssertions();

        $repository = $this->getRepositoryMock($this->getSharedMemoryMock(true, null, false));
        $repository->init('identifier', 'secret');
    }

    public function testSecretRetrievalIsSuccessful(): void
    {
        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(true, [['test-anti_csrf-secret-identifier', 'secret']], false)
        );
        $repository->init('identifier', 'secret');

        $this->assertEquals('secret', $repository->getSecret('identifier'));
    }

    public function testSecretRetrievalFailureThrowsAnException(): void
    {
        $this->expectException(CouldNotUseCsrf::class);
        $repository = $this->getRepositoryMock($this->getSharedMemoryMock(true, null, false));
        $repository->init('identifier', 'secret');

        $repository->getSecret('identifier');
    }

    public function testTokenRetrievalIsSuccessful(): void
    {
        $token = $this->getToken();

        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                [
                    ['test-anti_csrf-secret-identifier', 'secret'],
                    ['test-anti_csrf-tokens-identifier-' . $token->getToken(), $token],
                ],
                false
            )
        );

        $repository->init('identifier', 'secret');

        $returned_token = $repository->retrieveToken('identifier', $token->getToken());

        $this->assertSame($token, $returned_token);
    }

    public function testTokenIsNotFound(): void
    {
        $this->expectException(CouldNotGetCsrfToken::class);

        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                [
                    ['test-anti_csrf-secret-identifier', 'secret'],
                    ['test-anti_csrf-tokens-identifier-@token', null],
                ],
                false
            )
        );

        $repository->init('identifier', 'secret');

        $repository->retrieveToken('identifier', '@token');
    }

    public function testTokenIsCorrupted(): void
    {
        $this->expectException(CouldNotGetCsrfToken::class);

        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                [
                    ['test-anti_csrf-secret-identifier', 'secret'],
                    ['test-anti_csrf-tokens-identifier-@token', 'not a token object'],
                ],
                false
            )
        );

        $repository->init('identifier', 'secret');

        $repository->retrieveToken('identifier', '@token');
    }

    public function testPersistTokenIsSuccessful(): void
    {
        $this->expectNotToPerformAssertions();

        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                [
                    ['test-anti_csrf-secret-identifier', 'secret'],
                ],
                false
            )
        );

        $repository->init('identifier', 'secret');

        $repository->persistToken('identifier', $this->getToken(), 3600);
    }

    public function testPersistTokenThrowsAnException(): void
    {
        $this->expectException(CouldNotUseCsrf::class);

        $token = $this->getToken();

        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                [
                    ['test-anti_csrf-secret-identifier', 'secret', null, true],
                    ['test-anti_csrf-tokens-identifier-' . $token->getToken(), $token, 3600, false],
                ],
                [
                    ['test-anti_csrf-secret-identifier', 'secret'],
                ],
                false
            )
        );

        $repository->init('identifier', 'secret');

        $repository->persistToken('identifier', $token, 3600);
    }

    public function testTokenInvalidationIsSuccessful(): void
    {
        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                'secret',
                true
            )
        );

        $repository->init('identifier', 'secret');

        $this->assertTrue($repository->invalidateToken('identifier', $this->getToken()));
    }

    public function testTokenInvalidationInError(): void
    {
        $repository = $this->getRepositoryMock(
            $this->getSharedMemoryMock(
                true,
                'secret',
                false
            )
        );

        $repository->init('identifier', 'secret');

        $this->assertFalse($repository->invalidateToken('identifier', $this->getToken()));
    }
}
