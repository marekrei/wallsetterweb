<?php 
class C2DM{
	private static function getGoogleAuthCode($username, $password, $source="Company-AppName-Version", $service="ac2dm") {
		global $_CONFIG;
	    $ch = curl_init();
	    if(!ch){
	        return null;
	    }
	
	    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
	    $post_fields = "accountType=" . urlencode('HOSTED_OR_GOOGLE')
	        . "&Email=" . urlencode($username)
	        . "&Passwd=" . urlencode($password)
	        . "&source=" . urlencode($source)
	        . "&service=" . urlencode($service);
	    curl_setopt($ch, CURLOPT_HEADER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);    
	    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	    // For debugging the request
	    //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	
	    $response = curl_exec($ch);
	
	    // For debugging the request
	    //var_dump(curl_getinfo($ch)); 
	    //var_dump($response);
	
	    curl_close($ch);
	
	    if (strpos($response, '200 OK') === false) {
	        return null;
	    }
	
	    // Find the auth code
	    preg_match("/(Auth=)([\w|-]+)/", $response, $matches);
	
	    if (count($matches) < 2)
	        return null;
	    return $matches[2];
	}
	
	private static function sendMessageToPhone($authCode, $deviceRegistrationId, $msgType, $messageText) {
		global $_CONFIG;
		$headers = array('Authorization: GoogleLogin auth=' . $authCode);
		$data = array(
	            'registration_id' => $deviceRegistrationId,
	            'collapse_key' => $msgType,
	            'data.message' => $messageText     
		);
	
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
		if ($headers)
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
		$response = curl_exec($ch);
		//print "Response: ".$response;
		curl_close($ch);
	
		return $response;
	}
	
	public static function sendNotification($c2dmKey){
		global $_CONFIG;
		$googleAuthCode = DB::getVariable("google_auth_code");
		if($googleAuthCode == null){
			$googleAuthCode = C2DM::getGoogleAuthCode($_CONFIG['c2dm_user'], $_CONFIG['c2dm_pass'], $_CONFIG['c2dm_source'], "ac2dm");
			DB::setVariable("google_auth_code", $googleAuthCode);
		}

		$response = C2DM::sendMessageToPhone($googleAuthCode, $c2dmKey, DB::generateRandomString(), "You've got a new image");
		if($response == null){
			$googleAuthCode = C2DM::getGoogleAuthCode($_CONFIG['c2dm_user'], $_CONFIG['c2dm_pass'], $_CONFIG['c2dm_source'], "ac2dm");
			DB::setVariable("google_auth_code", $googleAuthCode);
			$response = C2DM::sendMessageToPhone($googleAuthCode, $c2dmKey, DB::generateRandomString(), "You've got a new image");
		}
		return $response;
	}
}
?>