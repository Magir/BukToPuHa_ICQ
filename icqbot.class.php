<?php
class telegrambot{
	function __construct($token){
		$this->token=$token;
		if (file_exists('offset'.md5($token))){
			$this->offset=(int)file_get_contents('offset'.md5($token));
		}else{
			$this->offset=0;
		}
		$this->ch=curl_init();
		curl_setopt ($this->ch, CURLOPT_HEADER, false);
		curl_setopt ($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1;en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, 280);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, 'cookie_'.md5($token)); // CHANGE COOKIE FILE PATH IF NEEDED
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, 'cookie_'.md5($token)); // See previous line comment
		curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($this->ch, CURLOPT_COOKIESESSION, false);
		curl_setopt ($this->ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, true);
	}
	function getUpdates(){
		$updates=$this->request('events/get',array('lastEventId'=>$this->offset, 'pollTime'=>1));
		if (debug==1) print_r($updates);
		if ($updates['events'] && count($updates['events'])!=0){
			$this->offset=$updates['events'][count($updates['events'])-1]['eventId'];
			file_put_contents('offset'.md5($this->token),$this->offset);

			$return = [];
			foreach ($updates['events'] as $event) {
				if ($event["type"] == "newMessage") {
					$return[] = array(
						"message" => array (
							"message_id" => $event["payload"]["msgId"],
							"from" => array (
								"id" => $event["payload"]["from"]["userId"],
								"first_name" => $event["payload"]["from"]["firstName"],
								"last_name" => $event["payload"]["from"]["lastName"],
								"username" => $event["payload"]["from"]["userId"]
							),
							"chat" => array (
								"id" => $event["payload"]["chat"]["chatId"],
								"first_name" => $event["payload"]["from"]["firstName"],
								"last_name" => $event["payload"]["from"]["lastName"],
								"username" => $event["payload"]["from"]["userId"]
							),
							"date" => $event["payload"]["timestamp"],
							"text" => $event["payload"]["text"]
						)
					);
				}
			}
			if (debug==1) print_r($return);
			return $return;
		}
		return false;
	}

	function request($method,$params=array()){
		if (!$method) return;
		curl_setopt($this->ch, CURLOPT_POST, false);
		curl_setopt($this->ch,CURLOPT_URL,'https://api.icq.net/bot/v1/'.$method.'?token='.$this->token.(count($params)!=0?'&'.http_build_query($params):''));
		$ret=curl_exec($this->ch);
		if (debug==1) echo $ret;
		return json_decode($ret,1);
	}	
	function requestpost($method,$params=array()){
		if (!$method) return;
		$params['token']=$this->token;
		curl_setopt($this->ch, CURLOPT_POST,1);
		curl_setopt($this->ch, CURLOPT_URL,'https://api.icq.net/bot/v1/'.$method);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
		$ret=curl_exec($this->ch);
		return json_decode($ret,1);
	}
	function send($chatid,$text){
		if (debug==1) echo 'Send '.$chatid.': '.$text."\n";
		$arr=array(
			'chatId'=>$chatid,
			'text'=>$text,
		);
		$this->request('/messages/sendText',$arr);
	}
	function sendimg($chatid,$img,$caption){
		if (debug==1) echo 'Send '.$chatid.': '.$img.' '.$caption."\n";
		$img=realpath($img);
		if (!file_exists($img)) return false;
		$arr=array(
			'chatId'=>$chatid,
			'caption'=>$caption,
			'file'=>curl_file_create($img)
		);
		$this->requestpost('/messages/sendFile',$arr);
	}

}

?>
