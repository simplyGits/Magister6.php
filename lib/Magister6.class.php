<?php
class Magister {
	//Variables
	public $url = '';			//Magister 6 url of school, found by selecting a school from $magister->findSchool('name')
	public $user = '';			//Magister 6 username provided by user
	public $pass = '';			//Magister 6 password provided by user
	public $magisterId = '';	//Magister 6 username provided by API server
	public $studyId = '';		//Current study the student is following, needed for things like grades
	public $isLoggedIn = false; //Easy check if the user is logged in
	public $curl;				//Container for Curl lib

	//Request storage variables
	public $profile;
	public $session;

	private function boolToString($bool){
		if($bool == true){
			return 'true';
		}else if($bool == false){
			return 'false';
		}else{
			return false;
		}
	}

	function __construct($school = false, $user = false, $pass = false, $autoLogin = false){
		//Initiate Curl
		include('curl/curl.php');
		include('curl/curl_response.php');
		$this->curl = new Curl();
		$this->curl->follow_redirects = false;
		$this->curl->user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6';
		$this->curl->options["CURLOPT_SSL_VERIFYPEER"] = false;
		$this->curl->options['AUTOREFERER'] = true;
		$this->curl->headers = array("Content-Type"=>"application/json;charset=utf-8");

		if($school !== false){
			self::setSchool($school);
		}
		if($user !== false && $pass !== false){
			self::setCredentials($user, $pass);
		}

		if($autoLogin){
			self::login();
		}
	}

	function getMagisterInfo(){
		if(empty($this->url)){
			return false;
		}else{
			return json_decode($this->curl->get($this->url.'api/versie'));
		}
	}

	function getUserInfo(){
		if(empty($this->profile)){
			return false;
		}else{
			return $this->profile;
		}
	}

	function getPicture($width = 85, $height = 105, $crop = false){
		if($this->isLoggedIn == true){
			$url = $this->url.'api/personen/'.$this->magisterId.'/foto?width='.$width.'&height='.$height;
			if($crop == true){
				$url .= "&crop=true";
			}
			$raw = $this->curl->get($url);
			return base64_encode($raw);
		}else{
			return false;
		}
	}

	function getSession(){
		if(empty($this->session)){
			return false;
		}else{
			return $this->session;
		}
	}

	function findSchool($string){
		if(empty($string)){
			return false;
		}else{
			return json_decode($this->curl->get("https://mijn.magister.net/api/schools?filter=$string"));
		}
	}

	function setSchool($url){
		if(empty($url)){
			return false;
		}else{
			//Url flexibility
			if(substr($url, -1, 1) !== "/"){
				$url = $url."/";
			}
			if(substr($url, 0, 7) !== "https://" && substr($url, 0, 6) !== "http://"){
				$url = "https://".$url;
			}

			$this->url = $url;
			return true;
		}
	}

	function setCredentials($user, $pass){
		if(empty($user) || empty($pass)){
			return false;
		}else{
			$this->user = $user;
			$this->pass = $pass;
			return true;
		}
	}

	function login(){
		if(empty($this->user) || empty($this->pass) || empty($this->url)){
			return false;
		}else{
			$deleted = $this->curl->delete($this->url.'api/sessies/huidige');

			$loginUrl = $this->url.'api/sessies';
			$result = json_decode($this->curl->post($loginUrl, json_encode(array('Gebruikersnaam' => $this->user, 'Wachtwoord' => $this->pass, "IngelogdBlijven" => true))));
			if($result->isVerified !== true && $result->state !== "active"){
				throw new Exception("Magister6.class.php: Session not verified",1);
			}
			$this->session = $result;

			$accountUrl = $this->url.'api/account';
			$account = json_decode($this->curl->get($accountUrl));

			if(array_key_exists("Fouttype", $account)){
				if($account->Fouttype == "OngeldigeSessieStatus"){
					throw new Exception('Magister6.class.php: Ongeldige Sessie, check credentials.');
					break;
				}
			}

			$this->magisterId = $account->Persoon->Id;

			$this->profile = $account->Persoon;

			$this->isLoggedIn = true;

			//get current study
			$result = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen?geenToekomstige=true&peildatum='.date("Y-m-d")));

			$now = new DateTime();

			foreach($result->Items as $item){
				if(new DateTime($item->Einde) > $now){
					$this->studyId = $item->Id;
				}
			}

			return true;
		}
	}

	function getAppointments($datefrom, $dateto, $wijzigingen = false){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($datefrom) || empty($dateto)){
			return false;
		}else{
			return json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/afspraken?tot='.$dateto.'&van='.$datefrom));
		}
	}

	function getHomework($datefrom, $dateto){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($datefrom) || empty($dateto)){
			return false;
		}else{
			$data = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/afspraken?tot='.$dateto.'&van='.$datefrom));
			$return;
			$return->Items = array();
			$count = 0;
			foreach($data as $items){
				if(is_array($items)){
					foreach($items as $item){
						if(!empty($item->Inhoud)){
							$return->Items[$count] = $item;
							$count++;
						}
					}
				}
			}
			$return->TotalCount = $count;
			$return->Links = array();

			return $return;
		}
	}

	function getSubjects(){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($this->studyId)){
			return false;
		}else{
			$data = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen/'.$this->studyId.'/vakken'));
			return $data;
		}
	}

	function getTeacherInfo($afkorting){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($afkorting)){
			return false;
		}else{
			$data = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/contactpersonen?contactPersoonType=Docent&q='.$afkorting));
			return $data;
		}
	}

	function getContact($search, $type = "Leerling"){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($search)){
			return false;
		}else{
			$data = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/contactpersonen?contactPersoonType='.$type.'&q='.$search));
			return $data;
		}
	}

	function getGrades($vak = false, $actievePerioden = true, $alleenBerekendeKolommen = false, $alleenPTAKolommen = false){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($this->studyId)){
			return false;
		}else{
			$actievePerioden = self::boolToString($actievePerioden);
			$alleenBerekendeKolommen = self::boolToString($alleenBerekendeKolommen);
			$alleenPTAKolommen = self::boolToString($alleenPTAKolommen);
			$data = json_decode($this->curl->get($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen/'.$this->studyId.'/cijfers/cijferoverzichtvooraanmelding?actievePerioden='.$actievePerioden.'&alleenBerekendeKolommen='.$alleenBerekendeKolommen.'&alleenPTAKolommen='.$alleenPTAKolommen));
			if($vak == false){
				return $data;
			}else{
				$return->TotalCount = 0;
				$return->Links = array();
				$return->Items = array();
				foreach($data as $items){
					if(is_array($items)){
						$count = 0;
						foreach($items as $item){
							if($item->Vak->Afkorting == $vak){
								$return->Items[$count] = $item;
								$count++;
							}
						}
					}
				}
				return $return;
			}
		}
	}

}
?>
