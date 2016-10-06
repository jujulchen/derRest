<?php

namespace derRest;

use Klein\Klein;
use Klein\Request;
use Klein\Response;

final class Routes
{

    public function routes(Klein $klein):Klein
    {
        $klein->respond('GET', '/', function (Request $request, Response $response) {
            $response->body(file_get_contents('html/index.html'));
        });
        $klein->respond('POST', '/api/highscore', function (Request $request, Response $response) {
            $json = json_decode($request->body());
            if ($json && isset($json->name) && isset($json->score) && isset($json->level) && isset($json->elapsedTime)) {
                $db = new DatabaseAbstraction;
                $db->insert('highscore', [
                    'name' => $json->name,
                    'score' => $json->score,
                    'level' => $json->level,
                    'elapsedTime' => $json->elapsedTime,
                    'timestamp' => time(),
                ]);
                //save things
                $response->json(['message' => 'saved']);
            } else {
                $response->json(['error' => true]);
            }
        });
        $klein->respond('GET', '/api/highscore', function (Request $request, Response $response) {

            $db = new DatabaseAbstraction;
            $result = $db->select('highscore', '*', [
                'ORDER' => ['score' => 'DESC'],
                'LIMIT' => 10,
            ]);
            $response->json($result);
        });
        $klein->respond('GET', '/api/maze', function (Request $request, Response $response) {

            $x = Maze::DEFAULT_MAZE_WIDTH;
            $y = Maze::DEFAULT_MAZE_HEIGHT;

            if (!empty($_GET['x']) && is_numeric($_GET['x'])) {
                $x = (int)$_GET['x'];
            }
            if (!empty($_GET['y']) && is_numeric($_GET['y'])) {
                $y = (int)$_GET['y'];
            }
            $m = new Maze($x, $y);
            $resultData = $m->generate()->getMaze();
            $response->json($resultData);
        });
        $klein->onHttpError(function ($code, $router) {
            echo $code;
        });

        return $klein;
    }

    public function dispatch()
    {
        $klein = new Klein();

        $klein = $this->routes($klein);

        $klein->dispatch($this->generateRequest());
    }

    protected function generateRequest():Request
    {
        $request = Request::createFromGlobals();
        $uri = $request->server()->get('REQUEST_URI');
        $scriptFilename = $request->server()->get('SCRIPT_FILENAME');
        $documentRoot = $request->server()->get('DOCUMENT_ROOT');
        $dir = dirname(substr($scriptFilename, strlen($documentRoot)));
        if ($dir == '.' || $dir == '..') {
            $dir = '';
        }
        $request->server()->set('REQUEST_URI', substr($uri, strlen($dir)));
        return $request;
    }
}