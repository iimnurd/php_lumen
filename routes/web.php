<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/



// $router->post('/webhook', ['uses' =>
//     'WebhookController@receiveRequest']);
$router->post('/health', function (Request $req) {
  http_response_code(200);
});
$router->post('/webhook','WebhookController@receiveRequest');
/*
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/foo', function (Request $req) {
    var_dump($req->all()); die();
});


$router->post('/yy', function (Request $req) {
$rawPostData = file_get_contents("php://input");
var_dump($rawPostData);
});



$router->get('/webhook', function () {
    return 'hi, iam webhook';
});

$router->get('/api/{id}', function ($id) {
  return 'Get '.$id;
});

*/

