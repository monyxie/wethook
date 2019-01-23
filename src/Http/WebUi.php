<?php


namespace Monyxie\Webhooked\Http;


use League\Plates\Engine as TemplateEngine;
use Monyxie\Webhooked\Task\Runner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function RingCentral\Psr7\stream_for;

class WebUi
{
    /**
     * @var TemplateEngine
     */
    private $engine;
    /**
     * @var Runner
     */
    private $runner;

    /**
     * WebUi constructor.
     * @param TemplateEngine $engine
     * @param Runner $runner
     */
    public function __construct(TemplateEngine $engine, Runner $runner)
    {
        $this->engine = $engine;
        $this->runner = $runner;
    }

    public function addRoutes(Router $router) {
        $router->addRoute('GET', '/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $data = [
                'numEnqueued' => $this->runner->getNumEnqueued(),
                'numFinished' => $this->runner->getNumFinished(),
                'latestEnqueuedAt' => $this->runner->getLatestEnqueuedAt(),
                'latestFinishedAt' => $this->runner->getLatestFinishedAt(),
            ];
            return $response->withBody(stream_for($this->engine->render('index', $data)));
        });
    }
}