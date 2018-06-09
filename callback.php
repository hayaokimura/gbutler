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

if (isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])) {
    
    //initialize
    $httpClient = new CurlHTTPClient($accessToken);
    $bot = new LINEBot($httpClient,['channelSecret' => $channelSecret]);
    $signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE];
    
    
    
    //take events
    $inputData = file_get_contents("php://input");
    $Events = $bot->parseEventRequest($inputData, $signature);
    
    //how to get userid
    $userId = $Events[0]->getUserId();
    
    reply_for_events($bot,$Events,$google_client);
}

if (isset($_GET['code'])) {
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      $client->setAccessToken($token);
      $user = ORM::for_table('user')->create();
      //takeUserId
      
      $oauth2_service = new Google_Service_Oauth2($client);
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
  </head>
  <body>
      <div class="code">
        <?= $lineidbeta ?>
      </div>
  </body>
  </html>
<?php endif?>