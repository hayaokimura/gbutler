<?php

require_once(__DIR__."/vendor/autoload.php");

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\Constant\HTTPHeader;

//アクセストークンとシークレット取得
$environment_json = file_get_contents("environment.json");
$environment = json_decode($environment_json);

$accessToken = $environment->channel_access_token;
$channelSecret = $environment->channel_secret;

if (isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])) {
    
    $inputData = file_get_contents("php://input");
    
    $httpClient = new CurlHTTPClient($accessToken);
    $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
    $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
    $Events = $bot->parseEventRequest($inputData, $signature);
    
    foreach ($Events as $event) {
        $sendMessage = new MultiMessageBuilder();
        $TextMessageBuilder = new TextMessageBuilder("Hello!");
        $sendMessage->add($TextMessageBuilder);
        $bot->replyMessage($event->getReqlyToken(), $sendMessage);
    }
}

