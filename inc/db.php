<?php 
class DB{
	
	/**
	 * Connect to the database
	 */
	public static function init(){
		global $_CONFIG;
		$connection = mysql_connect($_CONFIG["mysql_host"], $_CONFIG["mysql_username"], $_CONFIG["mysql_password"]);
		if (!$connection){
			die('Could not connect: ' . mysql_error());
		}
		
		mysql_select_db($_CONFIG["mysql_db"], $connection);
		mysql_query("SET NAMES 'utf8'");
	}
	
	/**
	 * Register a new user
	 */
	public static function register($c2dmKey, $oldPrivateKey = null){
		global $_CONFIG;
		$prefix = $_CONFIG['mysql_prefix'];
		
		// Generating a new public and private key
		// The public key is for uploading photos on the webpage
		// The private key is for downloading them into your phone
		$publicKey = DB::generateKey(false);
		$privateKey = DB::generateKey(true);		
		
		// If there is someone already with the same C2DM key then something is wrong and we delete the old records
		$query = sprintf("select * from %susers where c2dm_key='%s'",
			mysql_real_escape_string($_CONFIG['mysql_prefix']),
			mysql_real_escape_string($c2dmKey));
		$result = mysql_query($query);
		if(mysql_num_rows($result) > 0){
			$query = sprintf("delete from %susers where c2dm_key='%s'",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($c2dmKey));
			mysql_query($query);
		}
		
		// This is just to check if the old private key that was passed in is actually in the database
		// If not, we set it to null
		if($oldPrivateKey != null){
			$query = sprintf("select * from %susers where private_key='%s'",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($oldPrivateKey));
			$result = mysql_query($query);
			if(mysql_num_rows($result) == 0)
				$oldPrivateKey = null;
		}
		
		// If the $oldPrivateKey is still not null, records are updated with all new keys
		if($oldPrivateKey != null){
			$query = sprintf("update %susers set public_key='%s', private_key='%s', c2dm_key='%s' where private_key='%s'",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($publicKey),
				mysql_real_escape_string($privateKey),
				mysql_real_escape_string($c2dmKey),
				mysql_real_escape_string($oldPrivateKey));
				
			mysql_query($query);
			
			// Also, the image is moved under a new name
			if(file_exists("upload/".$oldPrivateKey.".png")){
				rename("upload/".$oldPrivateKey.".png", "upload/".$privateKey.".png");
			}
		}
		// Otherwise, a new entry into the database is made
		else{
			$query = sprintf("insert into %susers set public_key='%s', private_key='%s', c2dm_key='%s', time_registered=UTC_TIMESTAMP()",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($publicKey),
				mysql_real_escape_string($privateKey),
				mysql_real_escape_string($c2dmKey));
		
			mysql_query($query);
		}
		
		// Returning the public and private keys
		return $publicKey."\n".$privateKey;
	}
	
	/**
	 * Save an image update into the DB
	 */
	public static function imageUpdate($privateKey){
		global $_CONFIG;
		$query = sprintf("update %susers set time_image_updated=UTC_TIMESTAMP(), count_image_updated=count_image_updated+1 where private_key='%s'",
			mysql_real_escape_string($_CONFIG['mysql_prefix']),
			mysql_real_escape_string($privateKey));
		mysql_query($query);
	}
	
	/**
	* Save an image download into the DB
	*/
	public static function imageDownload($privateKey){
		global $_CONFIG;
		$query = sprintf("update %susers set time_image_downloaded=UTC_TIMESTAMP(), count_image_downloaded=count_image_downloaded+1 where private_key='%s'",
		mysql_real_escape_string($_CONFIG['mysql_prefix']),
		mysql_real_escape_string($privateKey));
		mysql_query($query);
	}
	
	/**
	* Retrieve a private key corresponding to the public key
	*/
	public static function getPrivateKey($publicKey){
		global $_CONFIG;
		$query = sprintf("select * from %susers where public_key='%s' limit 1",
			mysql_real_escape_string($_CONFIG['mysql_prefix']),
			mysql_real_escape_string($publicKey));
		
		$result = mysql_query($query);
		if(mysql_num_rows($result) == 1){
			$data = mysql_fetch_array($result);
			return $data["private_key"];
		}
		return null;
	}
	
	/**
	* Retrieve the C2DM key corresponding to the public key
	*/
	public static function getC2dmKey($publicKey){
		global $_CONFIG;
		$query = sprintf("select * from %susers where public_key='%s' limit 1",
		mysql_real_escape_string($_CONFIG['mysql_prefix']),
		mysql_real_escape_string($publicKey));
	
		$result = mysql_query($query);
		if(mysql_num_rows($result) == 1){
			$data = mysql_fetch_array($result);
			return $data["c2dm_key"];
		}
		return null;
	}
	
	/**
	* Get a variable value from the DB
	*/
	public static function getVariable($key){
		global $_CONFIG;
		$query = sprintf("select * from %svariables where vkey='%s' limit 1",
			mysql_real_escape_string($_CONFIG['mysql_prefix']),
			mysql_real_escape_string($key));
		$result = mysql_query($query);
		if($result && mysql_num_rows($result) > 0){
			$data = mysql_fetch_array($result);
			return $data['vval'];
		}
		return null;
	}
	
	/**
	* Set the variable value in the DB
	*/
	public static function setVariable($key, $val){
		global $_CONFIG;
		$query = sprintf("select * from %svariables where vkey='%s' limit 1",
			mysql_real_escape_string($_CONFIG['mysql_prefix']),
			mysql_real_escape_string($key));
		$result = mysql_query($query);
		if($result && mysql_num_rows($result) >= 1){
			$query = sprintf("update %svariables set vval='%s', time_updated=UTC_TIMESTAMP() where vkey='%s'",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($val),
				mysql_real_escape_string($key));
			mysql_query($query);
		}
		else{
			$query = sprintf("insert into %svariables set vval='%s', vkey='%s', time_updated=UTC_TIMESTAMP()",
				mysql_real_escape_string($_CONFIG['mysql_prefix']),
				mysql_real_escape_string($val),
				mysql_real_escape_string($key));
			mysql_query($query);
		}
	}
	
	/**
	* Install a new instance
	*/
	public static function install(){
		global $_CONFIG;
		$query =   sprintf("CREATE TABLE IF NOT EXISTS `%susers` (
					  `id` int(9) NOT NULL AUTO_INCREMENT,
					  `public_key` varchar(32) NOT NULL,
					  `private_key` varchar(32) NOT NULL,
					  `c2dm_key` text NOT NULL,
					  `time_registered` datetime NOT NULL,
					  `time_image_updated` datetime DEFAULT NULL,
					  `count_image_updated` int(6) NOT NULL DEFAULT '0',
					  `time_image_downloaded` datetime DEFAULT NULL,
					  `count_image_downloaded` int(6) NOT NULL DEFAULT '0',
					  PRIMARY KEY (`id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
					",
					mysql_real_escape_string($_CONFIG['mysql_prefix']));

		mysql_query($query);
		$query =   sprintf("CREATE TABLE IF NOT EXISTS `%svariables` (
					  `vkey` varchar(32) NOT NULL,
					  `vval` text NOT NULL,
					  `time_updated` datetime DEFAULT NULL,
					  PRIMARY KEY (`vkey`)
					) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
		mysql_real_escape_string($_CONFIG['mysql_prefix']));
		mysql_query($query);
	}
	
	/**
	 * Generate a new key (public or private)
	 */
	public static function generateKey($private){
		global $_CONFIG;
		$key = "";
		$prefix = $_CONFIG['mysql_prefix'];
		do{
			if($private){
				$key = DB::generateRandomString(16);
				$query = sprintf("select * from %susers where private_key='%s'",
				mysql_real_escape_string($prefix),
				mysql_real_escape_string($key));
			}
			else{
				$key = DB::generateRandomString(8);
				$query = sprintf("select * from %susers where public_key='%s'",
				mysql_real_escape_string($prefix),
				mysql_real_escape_string($key));
			}
	
		} while(mysql_num_rows(mysql_query($query)) > 0);
	
		return $key;
	
	}
	
	/**
	 * Generate a random string
	 */
	public static function generateRandomString($length = 10) {
		global $_CONFIG;
		$string = "";
		$characters = $_CONFIG['allowed_characters'];
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters))];
		}
		return $string;
	}
	
	/**
	* Remove any illegal characters from the key
	*/
	public static function filterKey($key){
		global $_CONFIG;
		$key = strtolower($key);
		$newKey = "";
		for($i = 0; $i < strlen($key); $i++){
			if(strstr($_CONFIG['allowed_characters'], $key{$i}) != false){
				$newKey = $newKey.$key{$i};
			}
		}
		return $newKey;
	}
}
?>