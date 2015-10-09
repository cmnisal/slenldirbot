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
function loadarea($playarea) {
	include_once ('dbAccess.php');
	$db = dbAccess::getInstance();
    $db->setQuery("select * from agents where playarea like '%$playarea%'");
	$agent = $db->loadAssocList();
	if(empty($agent)){
		return urlencode('No Agents Found in 📍'.$playarea.' Area');
	}
	$i = 1;
	$reply = urlencode('Agents in 📍'.$playarea.' Area
');
       foreach ($agent as $agents) {
            $reply .= urlencode($i . ') @' . $agents['username'] . ' - '.$agents['tel'].'
');
            $i++;
        }
    return $reply;
}
function loadprofile($username) {
	include_once ('dbAccess.php');
	$db = dbAccess::getInstance();
    $db->setQuery("select * from agents where username like '$username'");
	$agent = $db->loadAssoc();
	$reply = urlencode("@".$username.",
Name - ".$agent['name']."
Telephone - ".$agent['tel']."
PlayArea - ".$agent['playarea']."");
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
    $message_text = str_replace('@SLEnlDirBot','',$messageobj['message']['text']);
	$message_part = explode(' ', strtolower($message_text));
    $request_message = $message_part[0];
    $chat_id = $messageobj['message']['chat']['id'];
	$user_id = $messageobj['message']['from']['id'];
	$username = $messageobj['message']['from']['username'];
	$verified = in_array($chat_id,array(-32674710,-27924249,-35458778,-15987932)) || in_array($username,array("CMNisal","RamdeshLota"));
	
	if($request_message=="/addmetodir"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id' OR username = '$username'");
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
	if($request_message=="/getme"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id'");
		$agent = $db->loadAssoc();
		if(empty($agent)){
			$reply = urlencode("@".$username.",
You are ".$messageobj['message']['from']['first_name']." ".$messageobj['message']['from']['last_name'].".
 I can't say more about you.");
			send_curl(build_reply($chat_id,$reply));				
		}else{
			send_curl(build_reply($chat_id,loadprofile($agent['username'])));

		}
		return;
	}
	if($request_message=="/getagent"){
		if(!$verified){
			$reply = urlencode("@".$username.",
You/Your group is not Verified Group!");
			send_curl(build_reply($chat_id,$reply));
			return;
		}
		if($message_part[1]=='' || $message_part[1] == NULL){
			$reply = urlencode("@".$username.",
Who are you looking for");
			send_curl(build_forcereply($chat_id,$reply));
			return;
		}
		$query = str_replace('@','',$message_part[1]);
		$db->setQuery("SELECT * FROM agents WHERE username = '$query' OR name LIKE '%$query%' ");
		$agent = $db->loadAssoc();
		if(empty($agent)){
			$reply = urlencode("@".$username.",
Sorry,I don't know about ".$query);
			send_curl(build_reply($chat_id,$reply));				
		}else{
			send_curl(build_reply($chat_id,loadprofile($agent['username'])));
		}
		return;
	}if($request_message=="/getagentsbyarea"){
		if(!$verified){
			$reply = urlencode("@".$username.",
You/Your group is not Verified Group!");
			send_curl(build_reply($chat_id,$reply));
			return;
		}
		if($message_part[1]=='' || $message_part[1] == NULL){
			$reply = urlencode("@".$username.",
What is the area you are looking for");
			send_curl(build_forcereply($chat_id,$reply));
			return;
		}else{
			send_curl(build_reply($chat_id,loadarea(str_replace('@','',$message_part[1]))));
		}
		return;
	}
	if($request_message=="/addagent"){
		if($message_part[4]=='' ||$message_part[4]==NULL ){
				$reply = urlencode("@".$username.",
New Agent Adding format is
/addagent <@agentname> <RealName> <tel> <playarea>");
			send_curl(build_reply($chat_id,$reply));
			return;
			}
		$query = str_replace('@','',$message_part[1]);
		$db->setQuery("SELECT * FROM agents WHERE username = '$query' OR name LIKE '%$query%' ");
		$agent = $db->loadAssoc();
		if(empty($agent)){
				$agent = new stdClass();
				$agent->username = $query;
				$agent->name = $message_part[2];
				$agent->tel = $message_part[3];
				$agent->playarea = $message_part[4];
				$db->insertObject('agents', $agent);
				$reply = urlencode("@".$username.",
Your data Successfully saved.");
				send_curl(build_reply($chat_id,$reply));
				return;
		}else{
			$reply = urlencode("@".$username.",
".ucfirst ($query)." is Already in the Database");
			send_curl(build_reply($chat_id,$reply));
		}
		return;
		
	}
	if($request_message=="/addmetodir"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id' OR username = '$username'");
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
if($message_part[0]=="/editmytel"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id'");
		$agent = $db->loadAssoc();
		if(!empty($agent)){
			$reply = urlencode("@".$username.",
Enter your correct Telephone.");
			send_curl(build_forcereply($chat_id,$reply));				
		}else{
			$reply = "You are not in the Directory.
send /addmetodir to add you";
			send_curl(build_reply($chat_id,$reply));
		}
		return;
	}if($request_message=="/editmyplayarea"){
		$db->setQuery("SELECT * FROM agents WHERE tgid = '$user_id'");
		$agent = $db->loadAssoc();
		if(!empty($agent)){
			$reply = urlencode("@".$username.",
Enter your correct playarea(s).");
			send_curl(build_forcereply($chat_id,$reply));				
		}else{
			$reply = "You are not in the Directory.
send /addmetodir to add you";
			send_curl(build_reply($chat_id,$reply));
		}
		return;
	}if($request_message=="/requestverify"){
			if($verified){
				$reply = urlencode("Already Verified");
				send_curl(build_reply($chat_id,$reply));				
				return;
			}
			if($chat_id==$user_id){
				$reply = urlencode("This is not a Group but Your request to verify you has been sent to bot Administrators.👍");
			}else{
			$reply = urlencode("Your request to verify this group has been sent to bot Administrators.
You will notify when this group is added to verified list.
Thank You.
Bot Admin.");
			}
			send_curl(build_reply($chat_id,$reply));
			$reply = urlencode("@CMNisal,
New #verifyRequest from ".$username."
[".$chat_id."]".$messageobj['message']['chat']['title']);
			send_curl(build_reply(38722085,$reply));
			send_curl(build_reply(-27924249,$reply));			
		return;
	}
	if($request_message=="/help" || $request_message=="/start"){
			$reply = urlencode('This is the SL ENL Directory Bot. 
Commands:
/addmetodir - Add yourDetails
/addagent - Add Another Agent
/getme - Get your Details
/editmytel - Edit Your Telephone
/editmyplayarea - Edit your PlayArea(s)
/requestverify - Request Verification
/help - Display this help text.
* /getagent - Get AgentDetails
* /getagentsbyarea - Get AgentDetails by their Play Area     		


*Verified Groups Only');

			send_curl(build_reply($chat_id,$reply));			
		return;
	}
	if(array_key_exists('reply_to_message', $messageobj['message'])){
		$reply_to_message = $messageobj['message']['reply_to_message']['text'];//botReply
		$replyuser = substr($reply_to_message,0, strpos($reply_to_message, ','));
		if($replyuser=='@'.$username){
			if (strpos($reply_to_message, 'Who') !== false) {
			$query = str_replace('@','',$message_text);
		$db->setQuery("SELECT * FROM agents WHERE username = '$query' OR name LIKE '%$query%' ");
		$agent = $db->loadAssoc();
		if(empty($agent)){
			$reply = urlencode("@".$username.",
Sorry,I don't know about ".$query);
			send_curl(build_reply($chat_id,$reply));				
		}else{
			send_curl(build_reply($chat_id,loadprofile($agent['username'])));
		}
		return;
			}
			if (strpos($reply_to_message, 'name') !== false) {
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
				$agent->tel = $message_text;
				$db->updateObject('agents', $agent, 'username');
				if (strpos($reply_to_message, 'correct') !== false){
					send_curl(build_reply($chat_id,"👍"));
					return;}
				$reply = urlencode("@".$username.",
What is your playarea(s)");
				$url = build_forcereply($chat_id,$reply);
			}if (strpos($reply_to_message, 'playarea') !== false) {
				$agent = new stdClass();
				$agent->username = $username;
				$agent->playarea = $message_text;
				$db->updateObject('agents', $agent, 'username');
				if (strpos($reply_to_message, 'correct') !== false){
					send_curl(build_reply($chat_id,"👍"));
					return;}
				$reply = urlencode("@".$username.",
Your data Successfully saved.");
				$url = build_reply($chat_id,$reply);
				send_curl(build_reply(38722085,loadprofile($agent['username'])));
			}if (strpos($reply_to_message, 'the area') !== false) {
				send_curl(build_reply($chat_id,loadarea(str_replace('@','',$message_text))));
				return;
			}if (strpos($reply_to_message, '#verifyRequest') !== false && $message_text=='👍') {
				$chat_id = substr($reply_to_message,(strpos($reply_to_message,'[')+1),(strpos($reply_to_message,']')-1));
				$reply = urlencode("👏👏👏
				You've added to Verified List.
Now you can use /getagent , /getagentsbyarea commands.");
				send_curl(build_reply($chat_id,$reply));	
				return;
			}
				send_curl($url);
		}else{
			$reply = urlencode("@".$username.",
You are not ".$replyuser);
			send_curl(build_reply($chat_id,$reply));	
		}
		return;
	}

//end	
}
send_response(file_get_contents('php://input'));
