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
[$accessToken,$channelSecret] = take_env_var();

if (isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])) {
    
    $inputData = file_get_contents("php://input");
    
    $httpClient = new CurlHTTPClient($accessToken);
    $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
    $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
    $Events = $bot->parseEventRequest($inputData, $signature);
    $userId = $Events[0]->getUserId();
    
    foreach ($Events as $event) {
        if ($event->getText() == "予定") {
            $replyText = "今日の予定をお伝えします。\n今日の予定は".$userId;
        }else {
            $replyText = $event->getText();
        }
        $sendMessage = new MultiMessageBuilder();
        $TextMessageBuilder = new TextMessageBuilder($replyText);
        $sendMessage->add($TextMessageBuilder);
        $bot->replyMessage($event->getReplyToken(), $sendMessage);
    }
}

