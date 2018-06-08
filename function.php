<?php

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\Constant\HTTPHeader;

function take_env_var(){
    $environment_json = file_get_contents("environment.json");
    $environment = json_decode($environment_json);
    return [$environment->channel_access_token,$environment->channel_secret];
}

function reply_for_Events($bot, $Events){
    foreach ($Events as $event) {
        switch ($event->getType()) {
            case 'text':
                if ($event->getText() == "予定") {
                    $replyText = "今日の予定をお伝えします。\n今日の予定は";
                }else {
                    $replyText = $event->getText();
                }
                $sendMessage = new MultiMessageBuilder();
                $TextMessageBuilder = new TextMessageBuilder($replyText);
                $sendMessage->add($TextMessageBuilder);
                $bot->replyMessage($event->getReplyToken(), $sendMessage);
                break;
            
            default:
                # code...
                break;
        }
        
    }
}

function client_init(){
    $client = new Google_Client();
    $client->setAuthConfig('client_secret.json');
    $scopes = array(Google_Service_Calendar::CALENDAR_READONLY, Google_Service_Oauth2::USERINFO_PROFILE);
    $client->addScope($scopes);
    $client->setApprovalPrompt('force');
    $client->setAccessType('offline');
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    $client->setRedirectUri($redirect_uri);
    
    return $client;
}