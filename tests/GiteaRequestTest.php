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
            ->willReturn([ static::EVENT_NAME ]);

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
     * @param GiteaRequest $giteaRequest
     */
    public function testValidateSecret($giteaRequest) {
        $this->assertTrue($giteaRequest->validateSecret(self::SECRET));
        $this->assertFalse($giteaRequest->validateSecret(self::SECRET . '#'));
        $this->assertFalse($giteaRequest->validateSecret(''));
    }

    /**
     * @depends testCanBeCreatedFromWellFormedHttpRequest
     * @param GiteaRequest $giteaRequest
     */
    public function testGetEventName($giteaRequest) {
        $this->assertTrue($giteaRequest->getEventName() === static::EVENT_NAME);
    }
}
