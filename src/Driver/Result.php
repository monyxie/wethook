<?php


namespace Monyxie\Wethook\Driver;


use Psr\Http\Message\ResponseInterface;

class Result
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var EventInterface
     */
    private $event;

    public function __construct(ResponseInterface $response, EventInterface $event)
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
     * @return EventInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }
}