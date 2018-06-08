<?php

function take_env_var(){
    $environment_json = file_get_contents("environment.json");
    $environment = json_decode($environment_json);
    return [$environment->channel_access_token,$environment->channel_secret];
}

function reply_for_Events($bot, $Events){
    foreach ($Events as $event) {
        if ($event->getText() == "予定") {
            $replyText = "今日の予定をお伝えします。\n今日の予定は";
        }else {
            $replyText = $event->getText();
        }
        $sendMessage = new MultiMessageBuilder();
        $TextMessageBuilder = new TextMessageBuilder($replyText);
        $sendMessage->add($TextMessageBuilder);
        $bot->replyMessage($event->getReplyToken(), $sendMessage);
    }
}