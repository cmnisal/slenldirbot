<?php
/*
 * Sri Lanka Ingress Enlightened Faction Agent Information Directory.
 */

function build_reply($chat_id, $text) {
    $returnvalue = 'https://api.telegram.org/bot115962358:AAGeI53igONmekB1BWYfnWdGpLgUCEVgQFs/sendMessage?chat_id='
            . $chat_id . '&text=' . $text.'&disable_web_page_preview=true';
    return $returnvalue;
}
function build_forcereply($chat_id,$text) {
	$markup['force_reply'] = true;
	$markup['selective'] = true;
    $returnvalue = 'https://api.telegram.org/bot115962358:AAGeI53igONmekB1BWYfnWdGpLgUCEVgQFs/sendMessage?chat_id='
        . $chat_id . '&text=' . $text . '&reply_markup=' . json_encode($markup);
    return $returnvalue;
}
function send_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('Curl failed: ' . curl_error($ch));
    }
    // Close connection
    curl_close($ch);
}
function loadprofile($username) {
	include_once ('dbAccess.php');
	$db = dbAccess::getInstance();
    $db->setQuery("select * from agents where username like '$username'");
	
    return $reply;
}

function send_response($input_raw) {
	include_once ('dbAccess.php');
	$db = dbAccess::getInstance();
    //$response = send_curl('https://api.telegram.org/bot115962358:AAGeI53igONmekB1BWYfnWdGpLgUCEVgQFs/getUpdates');
    /*$input_raw = '{
                      "update_id": 89018516,
                      "message": {
                        "message_id": 62,
                        "from": {
                          "id": 38722085,
                          "first_name": "Ramindu \"RamdeshLota\"",
                          "last_name": "Deshapriya",
                          "username": "CMNisal"
                        },
                        "chat": {
                          "id":38722085,
                          "title": "Bottest"
                        },
                        "date": 1435508622,
                        "text": "/addmetodir"
                      }
                    }';*/
    // let's log the raw JSON message first
    $messageobj = json_decode($input_raw, true);
    $message_text = $messageobj['message']['text'];
	$message_part = explode(' ', strtolower($message_text));
    $request_message = str_replace('@SLEnlDirBot','',$message_part[0]);
    $chat_id = $messageobj['message']['chat']['id'];
	$user_id = $messageobj['message']['from']['id'];
	$username = $messageobj['message']['from']['username'];
	$replytobot = (array_key_exists('reply_to_message', $messageobj['message']));
	$verified = array("CMNisal","RamdeshLota","Slpooh");
	
	if($message_part[0]=="/addmetodir"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id'");
		$agent = $db->loadAssoc();
		if(empty($agent)){
			$reply = urlencode("@".$username.",
What is your name.");
			send_curl(build_forcereply($chat_id,$reply));				
		}else{
			$reply = "You are Already in the Directory.";
			send_curl(build_reply($chat_id,$reply));
		}
		return;
	}
	if($replytobot){
		$reply_to_message = $messageobj['message']['reply_to_message']['text'];//botReply
		$replyuser = substr($reply_to_message,0, strpos($reply_to_message, ','));
		if($replyuser=='@'.$username){
			if (strpos($reply_to_message, 'name') !== false) {
			//ask name
				$agent = new stdClass();
				$agent->tgid = $user_id;
				$agent->username = $username;
				$agent->name = $message_text;
				$db->insertObject('agents', $agent);
				$reply = urlencode("@".$username.",
What is your Telephone no.");
				$url = build_forcereply($chat_id,$reply);
			}if (strpos($reply_to_message, 'Telephone') !== false) {
				$agent = new stdClass();
				$agent->username = $username;
				$agent->telephone = $message_text;
				$db->updateObject('agents', $agent, 'username');
				$reply = urlencode("@".$username.",
What is your playarea(s)");
				$url = build_forcereply($chat_id,$reply);
			}if (strpos($reply_to_message, 'playarea') !== false) {
				$agent = new stdClass();
				$agent->username = $username;
				$agent->playarea = $message_text;
				$db->updateObject('agents', $agent, 'username');
				$reply = urlencode("@".$username.",
Your data Successfully saved.");
				$url = build_reply($chat_id,$reply);
			}
				send_curl($url);
		}else{
			$reply = urlencode("@".$username.",
You are not -".$replyuser);
			send_curl(build_reply($chat_id,$reply));	
		}
		return;
	}

//end	
}
send_response(file_get_contents('php://input'));
