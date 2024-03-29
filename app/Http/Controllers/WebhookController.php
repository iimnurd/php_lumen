<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Log;
use Jaeger\Config;
use OpenTracing\GlobalTracer;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;
use Prometheus\Storage\InMemory;


class WebhookController extends Controller
{
 public $registry;

  public function __construct()
    {
      \Prometheus\Storage\Redis::setDefaultOptions(
        [
            'host' => 'redis',
            'port' => 6379,
            'password' => null,
            'timeout' => 0.1, // in seconds
            'read_timeout' => '10', // in seconds
            'persistent_connections' => false
        ]
    );

      $this->registry = \Prometheus\CollectorRegistry::getDefault();
       
    }



  public function getDateFormat()
{
  $mytime = Carbon::now();
 return $mytime->toDateTimeString();
}



public function timeDiff(String $timeA, String $timeB)
    {
        $time1 = Carbon::createFromFormat('Y-m-d H:i:s',($timeA));
        $time2 = Carbon::createFromFormat('Y-m-d H:i:s',($timeB));
        $difference = $time2->diff($time1);
        return $difference;
    }


  public function generateNumber($min=1, $max=100){
    $rand = random_int (  $min ,  $max ) ;
    return $rand;
  }

  function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}



  public function action ($start_time, $data){
    
    $tracer = GlobalTracer::get();
    $spanContext = $tracer->extract(Formats\HTTP_HEADERS, getallheaders());
    $tracer->startSpan('my_span', [
        'child_of' => $spanContext,
    ]);
    $tracer->close();
    #generate random number and save log or db
    $number = $this ->generateNumber(1,1000) ; 
    $response_time= array();

      $finish_time = microtime(true);
      $diff_time =  ($finish_time - $start_time)*1000;
      if (getenv('DEBUG') == TRUE ){
      $response_time[getenv('APP_NAME')."-default-".$this->generateNumber()] = $diff_time;
       }else{
      $response_time[getenv('APP_NAME')] = $diff_time;
       }

       $resp = response()->json(['id'=> $data['id'], 'number'=> $number , 'response_time'=> $response_time]);
       Log::info('Act Request : '.$resp."\n");


    return $resp;
  }




  public function forward($start_time, $data){

    $response_time= array();
    $next_request = array_pop($data['request']);

    $ch = curl_init($next_request);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
    }
    curl_close($ch);

    if (isset($error_msg)) {
      $json_re['error'] = $error_msg; 

    }else {

    $json_re = json_decode($response,true);
    $finish_time = microtime(true);
    $diff_time =  ($finish_time - $start_time)*1000;

    if (getenv('DEBUG') == TRUE ){
    $response_time[getenv('APP_NAME')."-forward-".$this->generateNumber()] = $diff_time;
    }else{
    $response_time[getenv('APP_NAME')] = $diff_time;
    }
    $json_re['response_time'] = array_merge($json_re['response_time'],$response_time) ; 

  }
    Log::info('FW Request: '.response()->json($json_re)."\n");
    return response()->json($json_re); 

  }





  public function receiveRequest(Request $request)
  {

    $config = new Config(
      [
          'sampler' => [
              'type' => Jaeger\SAMPLER_TYPE_CONST,
              'param' => true,
          ],
          'logging' => true,
      ],
      'your-app-name'
  );
  $config->initializeTracer();
  
  $tracer = GlobalTracer::get();
  
  $scope = $tracer->startActiveSpan('TestSpan', []);
  $scope->close();
  
  $tracer->flush();

  
    
    $start_time =  microtime(true);
    $random_number = $this ->generateNumber(1,1000); 
    $data = $request->json()->all();

    if (count($data['request']) <= 1){

      return $this->action($start_time, $data);
    }else{
    return $this->forward($start_time, $data);
    }

  }


  public function getProme(Request $request)
  {
$renderer = new RenderTextFormat();
$result = $renderer->render($this->registry->getMetricFamilySamples());

header('Content-type: ' . RenderTextFormat::MIME_TYPE);
echo $result;
  }
  

  public function get_report(Request $request)
  {

    $executionStartTime = microtime(true);
    $count = preg_match('/^[0-9]+$/', $_GET['c'])
    ? intval($_GET['c'])
    : floatval($_GET['c']);

    sleep($count);
    $executionEndTime = microtime(true);

    $seconds = $executionEndTime - $executionStartTime;
    echo $seconds;
    $histogram = $this->registry->registerHistogram('myapp', 'process_time', 'it observes', ['code','method','path','version'], [ 1, 2, 5]);
    $histogram->observe($seconds, ['200','GET','get_report','v.1.0.0']);


    $counter = $this->registry->registerCounter('my_app', 'counter', 'it increases', ['code','method','path','version']);
    $counter->incBy(1, ['200','GET','get_report','v.1.0.0']);
    //echo "ok";
  }

  public function get_trx(Request $request)
  {

    $executionStartTime = microtime(true);
    
    $ch = curl_init(); 

   
    curl_setopt($ch, CURLOPT_URL, "https://httpstat.us/200?sleep=5000");

    // return the transfer as a string 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch); 

    curl_close($ch);      

    
    $executionEndTime = microtime(true);

    $seconds = $executionEndTime - $executionStartTime;
    echo $seconds;
    $histogram = $this->registry->registerHistogram('myapp', 'process_time', 'it observes', ['code','method','path','version'], [ 1, 2, 5]);
    $histogram->observe($seconds, ['200','GET','get_trx','v.1.0.0']);

    $counter = $this->registry->registerCounter('my_app', 'counter', 'it increases', ['code','method','path','version']);
    $counter->incBy(1, ['200','GET','get_trx','v.1.0.0']);
    //echo "ok";
  }


  public function getFlush(Request $request){
   echo "flush";
    

  }








}