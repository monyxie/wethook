<?php

use Monyxie\Webhooked\Request\Gitea\GiteaRequest;
use Monyxie\Webhooked\Request\MalformedRequestException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class GiteaRequestTest
 */
class GiteaRequestTest extends \PHPUnit\Framework\TestCase {
    const SECRET = 'da_secret';
    const REPOSITORY_FULL_NAME = 'da-full/repo-name';
    const EVENT_NAME = 'push';

    /**
     * 
     */
    public function testCanBeCreatedFromWellFormedHttpRequest() : GiteaRequest {
//        $httpRequest = $this->getMockBuilder(ServerRequestInterface::class)
//            ->setMethodsExcept()
//            ->setMethods(['getBody', 'getHeader'])
//            ->getMockForAbstractClass();

        $httpRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $httpRequest->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'secret' => self::SECRET,
                'repository' => [
                    'full_name' => self::REPOSITORY_FULL_NAME,
                ],
            ]));

        $httpRequest->expects($this->once())
            ->method('getHeader')
            ->with('X-Gitea-Event')
            ->willReturn([ 'push' ]);

        $giteaRequest = new GiteaRequest($httpRequest);
        $this->assertInstanceOf(
            GiteaRequest::class, $giteaRequest
        );
        
        return $giteaRequest;
    }

    /**
     *
     */
    public function testCreatingFromMalformedHttpRequestShouldThrowException() : void {
//        $httpRequest = $this->getMockBuilder(ServerRequestInterface::class)
//            ->setMethods(['getBody', 'getHeader'])
//            ->getMock();

        $httpRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $this->expectException(
            MalformedRequestException::class
        );

        new GiteaRequest($httpRequest);
    }

    /**
     * @depends testCanBeCreatedFromWellFormedHttpRequest
     * @param $giteaRequest
     */
    public function testValidateSecretShouldReturnTrueForCorrectSecret($giteaRequest) {
        $this->assertTrue($giteaRequest->validateSecret(self::SECRET));
    }

    /**
     * @depends testCanBeCreatedFromWellFormedHttpRequest
     * @param $giteaRequest
     */
    public function testValidateSecretShouldReturnFalseForIncorrectSecret($giteaRequest) {
        $this->assertFalse($giteaRequest->validateSecret(self::SECRET . '#'));
    }
}
