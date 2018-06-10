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

function reply_for_Events($bot, $Events,$google_client){
    foreach ($Events as $event) {
        $type = $event->getType();
        if ($type == 'message') {
            if (preg_match([0-9]{6,6}, $event->getText())) {
                $user = ORM::for_table('user')->where("lineid",$event->getText)->find_one();
                if ($user) {
                    $user->lineid = $event->userId;
                    $user->save();
                    $replyText = "登録が完了しました！";
                }
                
            }elseif ($event->getText() == "予定") {
                $replyText = "今日の予定をお伝えします。\n今日の予定は";
            }else {
                $replyText = $event->getText();
            }
            $sendMessage = new MultiMessageBuilder();
            $TextMessageBuilder = new TextMessageBuilder($replyText);
            $sendMessage->add($TextMessageBuilder);
            $bot->replyMessage($event->getReplyToken(), $sendMessage);
        }elseif ($type == 'follow') {
            $replyText = "登録ありがとうございます！\nこちらのurlをクリックしてgoogleアカウント認証をお願いします。\n"
                .$google_client->createAuthUrl();
            $sendMessage = new MultiMessageBuilder();
            $TextMessageBuilder = new TextMessageBuilder($replyText);
            $sendMessage->add($TextMessageBuilder);
            $bot->replyMessage($event->getReplyToken(), $sendMessage);
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

function DB_init(){
    ORM::configure('mysql:host=localhost;dbname=gbutler');
    ORM::configure('username', 'gbutler');
    ORM::configure('password', 'pR1mCvFCnSd4bMFk');
    ORM::configure('driver_options', [
        PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES utf8',
        PDO::ATTR_EMULATE_PREPARES         => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);

}