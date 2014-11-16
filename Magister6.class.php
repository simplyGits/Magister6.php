<?php

class Magister {

	public $url = '';			//Magister 6 url of school, found by selecting a school from $magister->findSchool('name')
	public $user = '';			//Magister 6 username provided by user
	public $pass = '';			//Magister 6 password provided by user
	public $intSession = ''; 	//internal session ID, used for cookie files
	public $cookieJar = '';		//Used to store file inside variable and destroy files to keep tmp directory empty
	public $magisterId = '';	//Magister 6 username provided by API server
	public $studyId = '';		//Current study the student is following, needed for things like grades
	public $isLoggedIn = false; //Easy check if the user is logged in

	//request storage variables
	public $profile;

	private function curlget($url){
		$cookiefile = 'tmp/'.$this->intSession.'.txt';

		touch($cookiefile);

		if(!empty($this->cookieJar)){
			file_put_contents($cookiefile, $this->cookieJar);
		}

		$referer=parse_url($url);
		if($referer){
			$referer=$referer["scheme"]."://".$referer["host"];
		}
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
		curl_setopt($ch,CURLOPT_TIMEOUT,60);
		//curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_COOKIEJAR,$cookiefile);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$cookiefile);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_REFERER,$referer);
		$result=curl_exec($ch);
		curl_close($ch);

		$this->cookieJar = file_get_contents($cookiefile);

		unlink($cookiefile);

		return $result;
	}

	private function curlpost($url, $post = null){
		$cookiefile = 'tmp/'.$this->intSession.'.txt';

		touch($cookiefile);

		if(!empty($this->cookieJar)){
			file_put_contents($cookiefile, $this->cookieJar);
		}

		$post = json_encode($post, true);

		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
		curl_setopt($ch,CURLOPT_TIMEOUT,5);
		//curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_COOKIEJAR,$cookiefile);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$cookiefile);
		curl_setopt($ch,CURLOPT_REFERER,$this->url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'charset=UTF-8'));
		$result=curl_exec($ch);

		if(curl_errno($ch))
		{
		    echo 'error:' . curl_error($ch);
		}

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		//var_dump($header);

		curl_close($ch);

		$this->cookieJar = file_get_contents($cookiefile);

		unlink($cookiefile);

		return true;
	}

	private function generateSession(){
		return md5($_SERVER['REMOTE_ADDR'].round(microtime(true) * 1000).mt_rand(0,1000)); //generate unique session ID, md5 it to make it look pretty
	}

	private function boolToString($bool){
		if($bool == true){
			return 'true';
		}else if($bool == false){
			return 'false';
		}else{
			return false;
		}
	}

	function __construct(){
		$this->intSession = self::generateSession();
	}

	function getMagisterInfo(){
		if(empty($this->url)){
			return false;
		}else{
			return json_decode(self::curlget($this->url.'api/versie'));
		}
	}

	function getUserInfo(){
		if(empty($this->profile)){
			return false;
		}else{
			return $this->profile;
		}
	}

	function findSchool($string){
		if(empty($string)){
			return false;
		}else{
			return json_decode(self::curlget("https://mijn.magister.net/api/schools?filter=$string"));
		}
	}

	function setSchool($url){
		if(empty($url)){
			return false;
		}else{
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
			$loginUrl = $this->url.'api/sessie';
			$result = self::curlpost($loginUrl, array('Gebruikersnaam' => $this->user, 'Wachtwoord' => $this->pass, "IngelogdBlijven" => true, "GebruikersnaamOnthouden" => true));
			
			$accountUrl = $this->url.'api/account';
			$account = json_decode(self::curlget($accountUrl));

			$this->magisterId = $account->Persoon->Id;

			$this->profile = $account->Persoon;

			$this->isLoggedIn = true;

			//get current study
			$result = json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen?geenToekomstige=true&peildatum='.date("Y-m-d")));

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
			return json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/afspraken?tot='.$dateto.'&van='.$datefrom));
		}
	}

	function getHomework($datefrom, $dateto){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($datefrom) || empty($dateto)){
			return false;
		}else{
			$data = json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/afspraken?tot='.$dateto.'&van='.$datefrom));
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
			$data = json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen/'.$this->studyId.'/vakken'));
			return $data;
		}
	}

	function getTeacherInfo($afkorting){
		if(empty($this->magisterId) || empty($this->url) || $this->isLoggedIn == false || empty($afkorting)){
			return false;
		}else{
			$data = json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/contactpersonen?contactPersoonType=Docent&q='.$afkorting));
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
			$data = json_decode(self::curlget($this->url.'api/personen/'.$this->magisterId.'/aanmeldingen/'.$this->studyId.'/cijfers/cijferoverzichtvooraanmelding?actievePerioden='.$actievePerioden.'&alleenBerekendeKolommen='.$alleenBerekendeKolommen.'&alleenPTAKolommen='.$alleenPTAKolommen));
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