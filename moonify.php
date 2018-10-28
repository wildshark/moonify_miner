<?php
/*
 * Moonify Miner PHP SDK 
 */

class Moonify {
	
	public $config=array(
		"version" => "0.2",
		"userAgent" => "Moonify PHP SDK",
		"url" => "https://api.moonify.io/0.2/"
	);

	public $error;
	private $integrationCode; //deprecated
	private $rawData;

	function __construct(){
		$this->integrationCode=new stdClass();
		$this->reset();
	}	

	/*
	 * Get returned raw data
	 * min 0.1
	 */

	public function getRawData(){
		return $this->rawData;
	}

	/*
	 * Set single or multiple global parameters
	 * ex : Moonify->set(['serviceID'=>'xyz','userID'=>'abc']);
	 * or Moonify->set('serviceID','xyz')
	 * min. 0.2
	 */
	public function set($arg1,$arg2=null){
		if(is_array($arg1)){
			foreach($arg1 as $param=>$value){
				$this->settings[$param]=$value;
			}
		} else {
			$this->settings[$arg1]=$arg2;
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Session setter
	 * ex : Moonify->session(['userID'=>'id123','@team'=>'blue'])->open();
	 * min. 0.2
	 * 
	 */
	function session($params){

		$this->session = array();

		foreach($params as $param=>$value){
			$this->session[$param]=$value;
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Users setter
	 * ex : Moonify->users()->get();
	 * ex : Moonify->users('id123')->get();
	 * min. 0.2
	 * 
	 */
	function users($user=null){
		if($user==null){
			$this->users="__#ALL#__";
		} else {
			$this->users=$user;
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Group setter
	 * ex : Moonify->group('team','blue')->get();
	 * min. 0.2
	 * 
	 */
	function group($name,$value){
		if($name && $value){
			$this->group=[$name,$value];
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Order setter
	 * ex : Moonify->users()->order('score')->get();
	 * min. 0.2
	 * 
	 */
	function order($column,$dir='desc'){
		if($column && $dir){
			$this->orders=[$column,$dir];
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Limit setter
	 * ex : Moonify->users()->order('score')->limit(5)->get();
	 * min. 0.2
	 * 
	 */
	function limit($limit){
		if(is_int($limit)){
			$this->limit=$limit;
		}
		return $this; //return original object for method chaining
	}

	/*
	 * Start setter
	 * ex : Moonify->users()->order('score')->limit(5)->start(5)->get();
	 * min. 0.2
	 * 
	 */
	function start($start){
		if(is_int($start)){
			$this->start=$start;
		}
		return $this; //return original object for method chaining
	}

	/*
	 * App setter
	 * ex : Moonify->app()->get();
	 * min. 0.2
	 * 
	 */
	public function app(){
		$this->app=true;
		return $this;
	}

	function open(){
		//CASE : SESSION ////////////////////////////////////////
		if(isset($this->session)){
			foreach($this->session as $param=>$value){
				$this->params[$param]=urlencode($value);
			}
			//set method type
			$this->setMethod("moonitize/sessions");
			//get deviceID in cookies, create if null
			!empty($_COOKIE['moonify_deviceID']) ? $deviceID = $_COOKIE['moonify_deviceID'] : $deviceID = null;
			$this->params["deviceID"]=$deviceID;

			$this->setRequestMethod("POST");
			$data=$this->send();
			//error handling
			if(is_object($data)){
				if(empty($data->error)){
					$this->tokenID=$data->tokenID;
					$integrationCode = '<script src="https://pkg.moonify.io/moonify.min.js"></script>';
					$integrationCode .= "<script type=\"text/javascript\">Moonify.set({tokenID : '".$data->tokenID."' });</script>";
					return (object) ['integrationCode'=>$integrationCode,'tokenID'=>$this->tokenID];
				} else {
					$this->integrationCode=null;
					$this->error=$data->error;
				}
			} else {
				$this->error="Data error.";
			}
		}
	}

	function get(){

		if(isset($this->orders)) {
			$this->params['order'] = $this->orders[0];
			$this->params['dir'] = $this->orders[1];
		}

		if(isset($this->limit)) {
			if(is_int($this->limit)) $this->params['limit'] = $this->limit;
		}

		if(isset($this->start)) {
			if(is_int($this->start)) $this->params['start'] = $this->start;
		}

		//CASE : USERS ////////////////////////////////////////
		if(isset($this->users) && !isset($this->group)){

			$this->setMethod("moonitize/users");
			$this->setRequestMethod("GET");
			if($this->users=="__#ALL#__"){
				$data=$this->send();
				if(is_object($data)){
					if(empty($data->error)){
						return (object) $data->users;
					} else {
						$this->error = $data->error;
					}
				} else {
					$this->error = "Data error.";
				}
			} else {
				$this->addUrlItem($this->users);
				$data=$this->send();
				if(is_object($data)){
					if(empty($data->error)){
						return (object) $data->user;
					} else {
						$this->error = $data->error;
					}
				} else {
					$this->error = "Data error.";
				}
			}
		} elseif(isset($this->app)){
			
			$this->setMethod("moonitize/app");
			
			if(isset($this->tokenID)){

				$this->params["tokenID"] = $this->tokenID;
				$this->setRequestMethod("GET");
				$data=$this->send();
				if(is_object($data)){
					if(empty($data->error)){
						if(isset($data->app)) return (object) $data->app;
					} else {
						$this->error = $data->error;
					}
				} else {
					$this->error = "Data error.";
				}
			} else {
				$this->rawData = null;
				$this->reset();
				$this->error = "A session has to be started to get a custom app link";
			}

			
		} elseif(isset($this->group)){
			$this->setMethod("moonitize/group");
			$this->setRequestMethod("GET");

			$this->addUrlItem($this->group[0]);
			$this->addUrlItem($this->group[1]);
			
			$value="group";
			if(isset($this->users) && $this->users == "__#ALL#__"){
				$this->addUrlItem("users");
				$value="users";
			} else if(isset($this->users)) {
				$this->addUrlItem("users");
				$this->addUrlItem($this->users);
				$value="user";
			}
			
			$data=$this->send();
			
			if(is_object($data)){
				if(empty($data->error)){
					return (object) $data->$value;
				} else {
					$this->error = $data->error;
				}
			} else {
				$this->error = "Data error.";
			}

		}
	}


	/* 0.1 Deprecated Public functions */

	/*
	 * Set a single global parameter
	 * min. 0.1
	 * Deprecated since 0.2
	 */

	public function setParam($param,$value){
		$this->globalParams[$param]=urlencode($value);
	}

	/*
	 * Set multiples globals parameters
	 * min. 0.1 
	 * Deprecated since 0.2
	 */

	public function setParams($array){
		foreach($array as $param=>$value){
			$this->globalParams[$param]=urlencode($value);
		}
	}

	/*
	 * Get integration code
	 * min 0.1
	 * Deprecated since 0.2
	 */

	public function getIntegrationCode(){
		return $this->integrationCode;
	}

	/*
	 * Open a mining session
	 * min 0.1
	 * Deprecated since 0.2
	 */

	public function openSession($params){

		foreach($params as $param=>$value){
			$this->params[$param]=urlencode($value);
		}

		//set method type
		$this->setMethod("moonitize/sessions");

		//get deviceID in cookies, create if null
		!empty($_COOKIE['moonify_deviceID']) ? $deviceID = $_COOKIE['moonify_deviceID'] : $deviceID = null;

		$this->setParam("deviceID",$deviceID);
		$this->setRequestMethod("POST");
		$data=$this->send();

		//error handling
		if(is_object($data)){
			if(empty($data->error)){
				$this->tokenID=$data->tokenID;

				$integrationCode = '<script src="https://pkg.moonify.io/moonify.min.js"></script>';
				$integrationCode .= "<script type=\"text/javascript\">Moonify.set({tokenID : '".$data->tokenID."' });</script>";
				return $this->integrationCode=$integrationCode;
			} else {
				$this->integrationCode=null;
				$this->error=$data->error;
			}
		} else {
			$this->error="Data error.";
		}
	}
	
	/*
	 * get users stats
	 * min 0.1
	 * Deprecated since 0.2
	 */

	public function getUsers($userID=null){
		
		//set method type
		$this->setMethod("moonitize/users");
		$this->setRequestMethod("GET");

		if($userID==null){
			
			$data=$this->send();

			if(is_object($data)){
				if(empty($data->error)){
					return $data->users;
				} else {
					$this->error=$data->error;
				}
			} else {
				$this->error="Data error.";
			}

		} else {
			//set method type
			$this->addUrlItem($userID);
			$data=$this->send();

			if(is_object($data)){
				if(empty($data->error)){
					return $data->user;
				} else {
					$this->error=$data->error;
				}
			} else {
				$this->error="Data error.";
			}
		}
	}

	/* *** *** *** *** *** *** *** *** *** *** *** *** *** *** */

	private function setMethod($method){
		$this->method = $method;
	}

	private function setRequestMethod($method){
		$this->requestMethod = $method;
	}

	private function addUrlItem($item){
		$this->UrlItems .= "/".$item;
	}

	private function reset(){
		$this->method=null;
		$this->requestMethod=null;
		$this->UrlItems=null;
		$this->params=array();
		unset($this->orders);
		unset($this->limit);
		unset($this->start);
		unset($this->users);
		unset($this->session);
		unset($this->group);
		unset($this->app);
	}

	private function isCurlInstalled(){
    	return function_exists('curl_version');
	}

	private function send(){
		$this->rawData=null;
		$this->error=null;

		//url-ify datas
		$fieldsString="";$nbParams=0;
		if(isset($this->globalParams) && is_array($this->globalParams)) foreach($this->globalParams as $attr=>$value) { $fieldsString .= $attr.'='.$value.'&';$nbParams++;}
		if(isset($this->params) && is_array($this->params)) foreach($this->params as $attr=>$value) { $fieldsString .= $attr.'='.$value.'&';$nbParams++;}
		$fieldsString=trim($fieldsString,"&");

		//test if curl is installed ?
		if($this->isCurlInstalled()){
			//open connection
			$ch = curl_init();
		
			//set curl http post method
			if($this->requestMethod=="POST"){
				curl_setopt($ch,CURLOPT_POST, $nbParams);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fieldsString);
				curl_setopt($ch,CURLOPT_URL, $this->config['url'].$this->method.$this->UrlItems);
			} elseif($this->requestMethod=="GET"){
				curl_setopt($ch,CURLOPT_URL, $this->config['url'].$this->method.$this->UrlItems."?".$fieldsString);
			}
			
			$header = array();
			$header[] = 'Authorization: serviceID '.$this->settings['serviceID'];
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt($ch,CURLOPT_HEADER, 0);
			curl_setopt($ch,CURLOPT_USERAGENT,$this->config['userAgent']." (v. ".$this->config['version'].")");
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);

			$data=curl_exec($ch); //get data + http code
			$this->rawData=$data;
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch); //close connection

			if( $data === false) {
				$returnedData=new stdClass();
				$returnedData->error="CURL Error : ".curl_error($ch);
				$this->reset();
				return $returnedData;
			} else {
				$this->reset();
				return json_decode($data);	
			}
		} else {
			$this->reset();
			return false;
		}
	}
}
?>