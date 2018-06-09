<?php

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\Constant\HTTPHeader;

require_once(__DIR__."/vendor/autoload.php");
include_once __DIR__ . '/function.php';
date_default_timezone_set('Asia/Tokyo');

//アクセストークンとシークレット取得
list($accessToken,$channelSecret) = take_env_var();

if (isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])) {
    
    //initialize
    $httpClient = new CurlHTTPClient($accessToken);
    $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
    $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
    $google_client = client_init();
    
    //take events
    $inputData = file_get_contents("php://input");
    $Events = $bot->parseEventRequest($inputData, $signature);
    
    //how to get userid
    $userId = $Events[0]->getUserId();
    
    reply_for_events($bot,$Events);
}

