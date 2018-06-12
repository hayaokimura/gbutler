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
            if (preg_match('/[0-9]{6,6}/', $event->getText())) {
                $user = ORM::for_table('user')->where("lineid",$event->getText())->find_one();
                if ($user) {
                    $user->set('lineid', $event->getUserId());
                    $user->save();
                    $replyText = "登録が完了しました！\nはじめまして。googleButlerです。\"今日の予定\"や\"明日の予定\"と入力するとgoogleカレンダーの予定を表示します。";
                }
                
            }else{
                $user = ORM::for_table('user')->where("lineid",$event->getUserId())->find_one();
                if ($user) {
                    $notice_time = ORM::for_table('notice_time')->where("user_id",$user->id)->where("time",null)->find_one();
                    $mecab = new MeCab_Tagger();
                    $words = $mecab->split($event->getText());
                    $today_flag = (in_array("予定", $words) && in_array("今日", $words)) || (in_array("予定", $words) && !in_array("明日", $words)) || in_array("今日", $words);
                    $tomorrow_flag = in_array("予定", $words) && in_array("明日", $words);
                    if ($today_flag) {
                        $start = strtotime( date("Y/m/d 00:00:00"));
                        $end = strtotime( "+1 day" , $start ) ;
                        $replyText_array = [schedule($google_client,$start,$end)];
                    }elseif($tomorrow_flag){
                        $today = strtotime( date("Y/m/d 00:00:00"));
                        $start = strtotime( "+1 day" , $today );
                        $end = strtotime( "+2 day" , $today ) ;
                        $replyText_array = [schedule($google_client,$start,$end)];
                    }elseif(preg_match('/[0-9]{4,4}/', $event->getText())){
                        $today = new DateTime(date("Y/m/d 00:00:00"));
                        $format = "Ymd";
                        $start_datetime = DateTime::createFromFormat($format, $today->format('Y').$event->getText());
                        if ($today > $start_datetime) {
                            $start = strtotime("+1 year", $start_datetime->getTimestamp());
                        }else{
                            $start = $start_datetime->getTimestamp();
                        }
                        $end = strtotime("+1 day", $start_datetime->getTimestamp());
                        $replyText_array = [schedule($google_client,$start,$end)];
                    }elseif(in_array("設定", $words)){
                        
                        if (!$notice_time) {
                            $notice_time = ORM::for_table('notice_time')->create();
                            $notice_time->set('user_id',$user->id);
                            $notice_time->save();
                        }
                        $replyText1 = "予定通知時間を設定します。\n当日or翌日のスケジュールを指定時間でお知らせします。当日、翌日に加えて0時から２３時までの時間を指定してください。";
                        $replyText2 = "例\n・当日の予定を朝８時に知りたいとき\n当日8\n・翌日の予定を夜９時に知りたいとき\n翌日21";
                        $replyText_array = [$replyText1,$replyText2];
                    }elseif((in_array("当日", $words)||in_array("翌日", $words)) && $hour = preg_grep("/[0-9]{1,2}/", $words) && $notice_time){
                        $notice_time->set('time', $hour);
                        if (in_array("当日", $words)) $notice_time->set('today_or_tomorrow', 0);
                        elseif(in_array("翌日", $words)) $notice_time->set('today_or_tomorrow', 1);
                        $notice_time->save();
                        $replyText = "登録が完了しました。\n毎日".$notice_time->time."時に". ($notice_time->today_or_tomorrow == 0 ? "当日":"翌日")."の予定をお知らせします。";
                        $replyText_array = [$replyText];
                    }else {
                        $replyText_array = [$event->getText()];
                    }
                }else{
                    $replyText_array = ["まだ連携ができていません！\nこちらのurlをクリックしてgoogleアカウント認証をお願いします。\n"
                .$google_client->createAuthUrl()];
                }
                
                if (isset($replyText_array)) reply_message($replyText_array, $bot);
                
                
            }
            
        }elseif ($type == 'follow') {
            $replyurl = "登録ありがとうございます！\nこちらのurlをクリックしてgoogleアカウント認証をお願いします。\n"
                .$google_client->createAuthUrl();
            $reply6num = "認証後に表示される６桁の番号をトーク画面に入力してください。";
            $replyText_array = [$replyurl,$reply6num];
            reply_message($replyText_array, $bot);
        }elseif ($type == 'unfollow'){
            $user = ORM::for_table('user')->where("lineid",$event->getUserId())->find_one();
            $user->delete();
        }
        
    }
}

function schedule($client,$start,$end){
    $client->getAccessToken();
    $calendar = new Google_Service_Calendar($client);
  
      // 今日の0時0分のUNIX TIMESTAMP
      $today = strtotime( date("Y/m/d 00:00:00") ) ;
      if ($start == $today) {
          $date_str = "今日";
      }elseif ($start == strtotime( "+1 day" , $today )) {
          $date_str = "明日";
      }else{
        $date_str = date("m月d日",$start);
      }
      
      $calendarId = 'primary';
      $optParams = array(
        'orderBy' => 'startTime',
        'singleEvents' => true,
        'timeMin' => date('c',$start),
        'timeMax' => date('c',$end),
      );
      $event_list = $calendar->events->listEvents($calendarId, $optParams);
      $return = null;
      if (empty($event_list->getItems())) {
          return $date_str."の予定はありません。";
      }else{
          $return = $date_str."の予定をお伝えします。\n".$date_str."の予定は\n";
          foreach ($event_list->getItems() as $event) {
            $start = $event->start->dateTime;
            if (empty($start))$start = $event->start->date;
            $end = $event->end->dateTime;
            if (empty($end))$end = $event->end->date;
            $return .= date("H:i",strtotime($start))."-".date("H:i",strtotime($end))." ".$event->getSummary()."\n";
          }
          $return .= "です。頑張っていきましょう！";
          return $return;
      }
      
}

function reply_message($replyText_array,$bot){
    $sendMessage = new MultiMessageBuilder();
    foreach ($replyText_array  as $replyText) {
        $TextMessageBuilder = new TextMessageBuilder($replyText);
        $sendMessage->add($TextMessageBuilder);
    }
    $bot->replyMessage($event->getReplyToken(), $sendMessage);
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