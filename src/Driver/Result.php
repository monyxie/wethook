<?php


namespace Monyxie\Webhooked\Driver;


use Psr\Http\Message\ResponseInterface;

class Result
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var HookEvent
     */
    private $event;

    public function __construct(ResponseInterface $response, HookEvent $event)
    {
        $this->response = $response;
        $this->event = $event;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return HookEvent
     */
    public function getEvent(): HookEvent
    {
        return $this->event;
    }
}