<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redis;

class MetarController extends Controller
{
    public function showSampleResponse(){
        $data = array('data' =>'pong');
        $data=json_encode($data);
        return view('station_info',compact('data'));
    }

    public function showStationResponse(){

    if (isset($_GET['scode'])) {
    $station=$_GET['scode'];
    $nocache=isset($_GET['nocache'])?$_GET['nocache']:NULL;
    } else {
      return view('error');
    }
    $url="https://tgftp.nws.noaa.gov/data/observations/metar/stations/".$station.".TXT";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    curl_close($ch);

    $array = preg_split('/[\s]+/', $response );
    $data=array();
    $data['data']['last_observation_date']=$array[0];
    $data['data']['last_observation_time']=$array[1];
    $data['data']['station']=$array[2];
    $data=json_encode($data,JSON_UNESCAPED_SLASHES);
    
    $redis=Redis::connection();
    if (!$redis->exists($station)) {
      $redis->set($station,$response);
      $redis->expire($station,300); 
    }
    if ($nocache==1) {
        $redis->flushAll();
    }
    return view('station_info',compact('data'));
}
}
