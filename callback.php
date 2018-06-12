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

$google_client = client_init();
DB_init();

if ($argc == 2) {
  //initialize
    $time = date('G');
    $notice_times = ORM::for_table('notice_time')->where("time",$time)->find_many();
    foreach ($$notice_times as $notice_time) {
      $user = ORM::for_table('user')->find_one($notice_time->user_id);
      $httpClient = new CurlHTTPClient($accessToken);
      $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
      $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('hello');
      $response = $bot->pushMessage($user->lineid, $textMessageBuilder);
    }
    
    
}

if (isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])) {
    
    //initialize
    $httpClient = new CurlHTTPClient($accessToken);
    $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
    $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
    
    
    
    //take events
    $inputData = file_get_contents("php://input");
    $Events = $bot->parseEventRequest($inputData, $signature);
    
    //how to get userid
    $lineid = $Events[0]->getUserId();
    
    $user = ORM::for_table('user')->where("lineid",$lineid)->find_one();
    if ($user) {
        $token =$google_client->fetchAccessTokenWithRefreshToken($user->refresh_token);
        if (array_key_exists("refresh_token", $token)) {
          $google_client->setAccessToken($token);
        }else{
          $user->delete();
        }
    }
    
    reply_for_events($bot,$Events,$google_client);
}

if (isset($_GET['code'])) {
      $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
      $google_client->setAccessToken($token);
      $user = ORM::for_table('user')->create();
      //takeUserId
      
      $oauth2_service = new Google_Service_Oauth2($google_client);
      $userinfo = $oauth2_service->userinfo->get();
      $googleid = $userinfo->id;
      
      //make lineidbata
      $string_length = 6;
      $lineidbeta = null;
      for( $i=0; $i<$string_length; $i++ )$lineidbeta .= random_int( 0, 9);
      
      $user_array  = array('lineid' => $lineidbeta,
                           'google_id' => $googleid,
                           'refresh_token' => $token["refresh_token"]);
      $user->set($user_array);
      $user->save();
}

?>
<?php if(isset($_GET['code'])):?>
<!doctype html>
  <html>
  <head>
    <title>gbutler code</title>
    <link rel="stylesheet" type="text/css" href="callback.css" />
    <meta name="viewport" content="width=device-width">
  </head>
  <body>
      <div class="code" align="center">
        <form>
            <p>こちらの番号をLINEのトーク画面に入力してください。</p>
            <input type="text" class="number" value=<?=$lineidbeta?> readonly />
        </form>
      </div>
  </body>
  </html>
<?php endif?>