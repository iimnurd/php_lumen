<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Log;
use OpenTracing\Formats;
use OpenTracing\GlobalTracer;

class WebhookController extends Controller
{


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

    $spanContext = $tracer->extract(
        Formats\HTTP_HEADERS,
        getallheaders()
    );
    $span = $tracer->startSpan('lumen_span', ['child_of' => $spanContext]);
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

       $span->finish();

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
    $start_time =  microtime(true);
    $random_number = $this ->generateNumber(1,1000); 
    $data = $request->json()->all();

    if (count($data['request']) <= 1){

      return $this->action($start_time, $data);
    }else{
    return $this->forward($start_time, $data);
    }

  }










}