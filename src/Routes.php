<?php
declare(strict_types = 1);
namespace derRest;

use derRest\Database\DatabaseConnection;
use derRest\Generator\Maze;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use derRest\Functions\phtml;

final class Routes
{

    public function routes(Klein $klein):Klein
    {
        $klein->respond(['GET', 'POST'], '/github.php', function (Request $request, Response $response) {
            `git pull && composer install`;
        });
        $klein->respond('GET', '/', function (Request $request, Response $response) {
            $response->body(phtml::phtml('html/index.phtml', [
                'baseUrl' => $this->getBasePathFromRequest($request),
            ]));
        });
        $klein->respond('POST', '/api/highscore', function (Request $request, Response $response) {
            $json = json_decode($request->body());
            if ($json && isset($json->name) && isset($json->score) && isset($json->level) && isset($json->elapsedTime)) {
                $db = new DatabaseConnection;
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

            $db = new DatabaseConnection;
            $result = $db->select('highscore', '*', [
                'ORDER' => ['score' => 'DESC'],
                'LIMIT' => 10,
            ]);
            $response->json($result);
        });
        $klein->respond('GET', '/api/maze', function (Request $request, Response $response) {

            $x = Maze::DEFAULT_MAZE_WIDTH;
            $y = Maze::DEFAULT_MAZE_HEIGHT;
            $candyCount = Maze::DEFAULT_CANDY_AMOUNT;

            if (!empty($_GET['x']) && is_numeric($_GET['x'])) {
                $x = (int)$_GET['x'];
            }
            if (!empty($_GET['y']) && is_numeric($_GET['y'])) {
                $y = (int)$_GET['y'];
            }
            if (!empty($_GET['candyCount']) && is_numeric($_GET['candyCount'])) {
                $candyCount = (int)$_GET['candyCount'];
            }
            $m = new Maze($x, $y, $candyCount);
            $resultData = $m->generate()->getMaze();
            $response->json($resultData);
        });
        $klein->onHttpError(function ($code) {
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
        $dir = $this->getBasePathFromRequest($request);
        $request->server()->set('REQUEST_URI', substr($uri, strlen($dir)));
        return $request;
    }

    protected function getBasePathFromRequest(Request $request):string
    {
        $scriptFilename = $request->server()->get('SCRIPT_FILENAME');
        $documentRoot = $request->server()->get('DOCUMENT_ROOT');
        $dir = dirname(substr($scriptFilename, strlen($documentRoot)));
        if ($dir == '.' || $dir == '..') {
            $dir = '';
        }
        return $dir;
    }
}