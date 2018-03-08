<?php

use Monyxie\Webhooked\Request\Gitee\GiteeRequest;
use Monyxie\Webhooked\Request\MalformedRequestException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class GiteeRequestTest
 */
class GiteeRequestTest extends \PHPUnit\Framework\TestCase {
    const SECRET = 'da_secret';
    const REPOSITORY_FULL_NAME = 'da-full/repo-name';
    const EVENT_NAME = 'push';

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     *
     */
    public function testCanBeCreatedFromWellFormedHttpRequest() : GiteeRequest {
//        $httpRequest = $this->getMockBuilder(ServerRequestInterface::class)
//            ->setMethodsExcept()
//            ->setMethods(['getBody', 'getHeader'])
//            ->getMockForAbstractClass();

        $httpRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $httpRequest->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'project' => [
                    'path_with_namespace' => self::REPOSITORY_FULL_NAME,
                ],
                'hook_name' => self::EVENT_NAME . '_hooks',
                'password' => self::SECRET,
            ]));

        $GiteeRequest = new GiteeRequest($httpRequest);
        $this->assertInstanceOf(
            GiteeRequest::class, $GiteeRequest
        );

        return $GiteeRequest;
    }

    /**
     *
     */
    public function testCreatingFromMalformedHttpRequestShouldThrowException() : void {
//        $httpRequest = $this->getMockBuilder(ServerRequestInterface::class)
//            ->setMethods(['getBody', 'getHeader'])
//            ->getMockForAbstractClass();
        $httpRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $this->expectException(
            MalformedRequestException::class
        );

        new GiteeRequest($httpRequest);
    }

    /**
     * @depends testCanBeCreatedFromWellFormedHttpRequest
     * @param $GiteeRequest
     */
    public function testValidateSecretShouldReturnTrueForCorrectSecret($GiteeRequest) {
        $this->assertTrue($GiteeRequest->validateSecret(self::SECRET));
    }

    /**
     * @depends testCanBeCreatedFromWellFormedHttpRequest
     * @param $GiteeRequest
     */
    public function testValidateSecretShouldReturnFalseForIncorrectSecret($GiteeRequest) {
        $this->assertFalse($GiteeRequest->validateSecret(self::SECRET . '#'));
    }
}

