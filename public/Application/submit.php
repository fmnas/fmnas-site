<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-10030491-3']);
  _gaq.push(['_setDomainName', 'forgetmenotshelter.org']);
  _gaq.push(['_setAllowLinker', true]);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<?php
require_once __DIR__ . '/../../secrets/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
	//these two functions adapted from http://programanddesign.com/php/base62-encode/
	$char='0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
	function base_encode($val, $chars) {
		if(!isset($base)) $base = strlen($chars);
		$str = '';
		do {
			$m = bcmod($val, $base);
			$str = $chars[$m] . $str;
			$val = bcdiv(bcsub($val, $m), $base);
		} while(bccomp($val,0)>0);
		return $str;
	} function base_decode($str,$chars) {
		if(!isset($base)) $base = strlen($chars);
		$len = strlen($str);
		$val = 0;
		$arr = array_flip(str_split($chars));
		for($i = 0; $i < $len; ++$i) {
			$val = bcadd($val, bcmul($arr[$str[$i]], bcpow($base, $len-$i-1)));
		}
		return $val;
	}

	//Application by Sean Gillen <sean@forgetmenotshelter.org> 2012-2018
	//last updated 2018-11-01
	//$_SERVER["REMOTE_ADDR"]='0123:4567:89ab:cdef:fedc:ba98:7654:3211';

	if(@$_GET["r"]=str_replace(" ","+",$_GET["r"])) {
		$appID=@base_decode($_GET["r"],$char);
		if(strlen($appID)==29||strlen($appID)==57) {
			$date=gmdate("l, F j, Y \&\\n\b\s\p; g:i:s A \G\M\T",strtotime(substr($appID,0,4).'-'.substr($appID,4,2).'-'.substr($appID,6,2).' '.substr($appID,8,2).":".substr($appID,10,2).":".substr($appID,12,2)));
			echo $date." &nbsp; ";
			if(strlen($appID)==29) {
				$ip=intval(substr($appID,17,3)).".".intval(substr($appID,20,3)).".".intval(substr($appID,23,3)).".".intval(substr($appID,26,3));
			} else
				$ip=strtoupper(sprintf("%04s",base_convert(intval(substr($appID,17,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,22,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,27,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,32,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,27,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,42,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,47,5)),10,16)).":".sprintf("%04s",base_convert(intval(substr($appID,52,5)),10,16)));
			echo $ip."<br><br>";
		} else
			echo "Malformed ID.<br><br>";

		if(file_exists("applications/".$_GET["r"])) {
			echo 'Raw data:<div style="border:1px solid #ccc;background-color:whitesmoke;border-radius:5px;padding:5px;box-shadow:-2px 2px 3px #ccc;">$_SERVER = ';
			$rawData=unserialize(gzinflate(file_get_contents("applications/".$_GET["r"])));
			print_r($rawData[0]);
			echo "<br><br>\$_POST = ";
			print_r($rawData[1]);
			echo "</div><br>";
		} else
			echo "No raw data was saved.<br><br>";

		if(file_exists("applications/".$_GET["r"].".html"))
			echo 'HTML data:<div style="border:1px solid #ccc;background-color:whitesmoke;border-radius:5px;padding:5px;box-shadow:-2px 2px 3px #ccc;">'.file_get_contents("applications/".$_GET["r"].".html").'</div>';
		else
			echo "No HTML data was saved.";
	}else{
		$rawData=gzdeflate(serialize(array($_SERVER,$_POST)),9);
		$appID=explode(" ",microtime());
		$appID=array($appID[1],explode(".",$appID[0]));
		$appID=date("YmdHis",$appID[0]).substr($appID[1][1],05);
		if(strpos($_SERVER["REMOTE_ADDR"],":")) //if IPv6
			foreach(explode(":",$_SERVER["REMOTE_ADDR"]) as $value)
				$appID.=sprintf("%05d",base_convert($value,16,10));
		else
			foreach(explode(".",$_SERVER["REMOTE_ADDR"]) as $value)
				$appID.=sprintf("%03d",$value);
		$appID=base_encode($appID,$char);

		file_put_contents("applications/".$appID,$rawData);

		$html='<!DOCTYPE html><html><head><title>Adoption Application</title><meta charset="UTF-8"><style type="text/css">td{padding:5px;}</style></head><body>';

		$coapplicant=0;
		if(@$_POST['CName'])
			$coapplicant=1;

		$_POST["AMAddress~"]=$_POST["AMAddress"]."\n".$_POST["ACity"]." ".$_POST["AState"]." ".$_POST["AZIP"];
		$_POST["CMAddress~"]=$_POST["CMAddress"]."\n".$_POST["CCity"]." ".$_POST["CState"]." ".$_POST["CZIP"];
		if(strlen($_POST["APAddress"])>2) $_POST["APAddress~"]=$_POST["APAddress"]."\n".$_POST["ACity"]." ".$_POST["AState"]." ".$_POST["AZIP"];
		if(strlen($_POST["CPAddress"])>2) $_POST["CPAddress~"]=$_POST["CPAddress"]."\n".$_POST["CCity"]." ".$_POST["CState"]." ".$_POST["CZIP"];

		if(@$_POST["AName"]) {
			$html.='<h1>Basic information</h1>';
			$html.='<table border="1" style="border-collapse:collapse;"><tr><td>&nbsp;</td><td><strong>Applicant</strong></td>';
			if($coapplicant)
				$html.= '<td><strong>Co-applicant</strong></td>';
			$html.='</tr>';
			$BasicInfo = array('Name','Mailing address'=>'MAddress~','Physical address'=>'PAddress~','Home phone'=>'Home','Cell phone'=>'Cell','Email','Age','Employer','Work phone'=>'Work');
			foreach($BasicInfo as $key=>$value) {
				if(@$_POST["A".$value]||@$_POST["C".$value]) {
					$html.='<tr><td><strong>';
					$html.=(is_string($key))?$key:$value;
					$html.='</strong></td><td>';
					$html.=(@$_POST["A".$value])?@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["A".$value])):'&nbsp;';
					$html.='</td>';
					if($coapplicant)
						$html.=(@$_POST["C".$value])?'<td>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["C".$value])).'</td>':'<td>&nbsp;</td>';
					$html.='</tr>';
				}
			}
			if(@isset($_POST["AContact"])||@isset($_POST["CContact"])) {
				$html.='<tr><td>&nbsp;</td><td';
				$html.=($coapplicant)?' colspan="2"':'';
				$html.='>May we contact your employer to verify employment?</td></tr><tr><td>&nbsp;</td><td>';
				$html.=(@isset($_POST["AContact"]))?($_POST["AContact"])?'Yes':'No':'&nbsp;';
				$html.='</td>';
				if($coapplicant)
					$html.='<td>'.((@isset($_POST["CContact"]))?($_POST["CContact"])?'Yes':'No':'&nbsp;').'</td>';
				$html.='</tr>';
			}
			$html.='</table>';
		}

		if(@$_POST["PersonName1"]||@$_POST["PersonAge1"]) {
			$html.='<h1>Other people</h1>';
			$html.='<table border="1" style="border-collapse:collapse;"><tr><td><strong>Name</strong></td><td><strong>Age</strong></td></tr>';
			for($i=1;$i<=$_POST["NumPeople"];$i++)
				$html.=(@$_POST["PersonName".$i]||@$_POST["PersonAge".$i])?'<tr><td>'.((@$_POST["PersonName".$i])?htmlspecialchars($_POST["PersonName".$i]):'&nbsp;').'</td><td>'.((@$_POST["PersonAge".$i])?htmlspecialchars($_POST["PersonAge".$i]):'&nbsp;').'</td></tr>':'';
			$html.='</table>';
		}

		if(@$_POST["PetName1"]||@$_POST["PetBreed1"]||@$_POST["PetAge1"]||@$_POST["PetGender1"]||@$_POST["PetAltered1"]||(@isset($_POST["PetSpecies1"])&&@$_POST["PetSpecies1"]!=="Not specified")) {
			$html.='<h1>Present pets</h1>';
			$html.='<table border="1" style="border-collapse:collapse;"><tr><td><strong>Name</strong></td><td><strong>Species</strong></td><td><strong>Breed</strong></td><td><strong>Age</strong></td><td><strong>Gender</strong></td><td><strong>Spayed/Neutered?</strong></td></tr>';
			for($i=1;$i<=@$_POST["NumPets"];$i++)
				$html.=(@$_POST["PetName".$i]||@$_POST["PetSpecies".$i]!=="Not specified"||@$_POST["PetBreed".$i]||@$_POST["PetAge".$i]||@$_POST["PetGender".$i]||@$_POST["PetAltered".$i])?'<tr><td>'.((@$_POST["PetName".$i])?htmlspecialchars($_POST["PetName".$i]):'&nbsp;').'</td><td>'.((@$_POST["PetSpecies".$i])?(($_POST["PetSpecies".$i]==="Other")?htmlspecialchars($_POST["PetOther".$i]):$_POST["PetSpecies".$i]):'&nbsp;').'</td><td>'.((@$_POST["PetBreed".$i])?htmlspecialchars($_POST["PetBreed".$i]):'&nbsp;').'</td><td>'.((@isset($_POST["PetAge".$i]))?htmlspecialchars($_POST["PetAge".$i]):'&nbsp;').'</td><td>'.(@isset($_POST["PetGender".$i])?(($_POST["PetGender".$i])?'Male':'Female'):'&nbsp;').'</td><td>'.(@isset($_POST["PetAltered".$i])?(($_POST["PetAltered".$i])?'Yes':'No'):'&nbsp;').'</td></tr>':'';
			$html.='</table>';
		}

		if(@$_POST["PastName1"]||@$_POST["PastBreed1"]||@$_POST["PastReason1"]||(@isset($_POST["PastSpecies1"])&&@$_POST["PastSpecies1"]!=="Not specified")) {
			$html.='<h1>Past pets</h1><table border="1" style="border-collapse:collapse;"><tr><td><strong>Name</strong></td><td><strong>Species</strong></td><td><strong>Breed</strong></td><td><strong>Reason for loss</strong></td></tr>';
			for($i=1;$i<=@$_POST["NumPast"];$i++)
				$html.=(@$_POST["PastName".$i]||@$_POST["PastBreed".$i]||@$_POST["PastReason".$i]||@$_POST["PastSpecies".$i]!=="Not specified")?'<tr><td>'.((@$_POST["PastName".$i])?htmlspecialchars($_POST["PastName".$i]):'&nbsp;').'</td><td>'.((@$_POST["PastSpecies".$i])?(($_POST["PastSpecies".$i]==="Other")?htmlspecialchars($_POST["PastOther".$i]):$_POST["PastSpecies".$i]):'&nbsp;').'</td><td>'.((@$_POST["PastBreed".$i])?htmlspecialchars($_POST["PastBreed".$i]):'&nbsp;').'</td><td>'.((@$_POST["PastReason".$i])?htmlspecialchars($_POST["PastReason".$i]):'&nbsp;').'</td></tr>':'';
			$html.='</table>';
		}

		$plurals=array('AdultDog'=>'Adult dogs','AdultCat'=>'Adult cats','Puppy'=>'Puppies','Kitten'=>'Kittens');
		if(@$_POST["AdultDog"]||@$_POST["Puppy"]||@$_POST["AdultCat"]||@$_POST["Kitten"]||@$_POST["Other"]||@$_POST["Preference"]||@$_POST["Listing"]||@$_POST["Characteristics"]) {
			$html.='<h1>Adoption information</h1>';
			if(@$_POST["AdultDog"]||@$_POST["Puppy"]||@$_POST["AdultCat"]||@$_POST["Kitten"]||@$_POST["Other"]||@$_POST["Preference"]) {
				$html.='I am interested in';
				$html.=@$_POST["Prefs"].' ';
				if(@$_POST["AdultDog"]||@$_POST["Puppy"]||@$_POST["AdultCat"]||@$_POST["Kitten"]||@$_POST["Other"]) {
					$html.=':<ul>';
					foreach($plurals as $key=>$value)
						$html.=(@$_POST[$key])?'<li>'.$value.'</li>':'';
					$html.=(@$_POST["Other"])?((@$_POST["Specific"])?'<li>'.htmlspecialchars($_POST["Specific"]).'</li>':''):'';
					$html.='</ul>';
				}
				else
					$html.=' animals.<br>';
			}
			$html.='I <strong>am';
			$html.=(@$_POST["Listing"])?'</strong> applying for '.((@$_POST["Particular"])?htmlspecialchars($_POST["Particular"]).".":'a specific pet, but I did not specify which one.'):' not</strong> applying for a specific pet.';
			$html.=(@$_POST["Characteristics"])?'<br><br>I desire the following qualities and characteristics in my new pet:<blockquote>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["Characteristics"])).'</blockquote>':'';
		}

		$asdf=array("Chained"=>"chained/tied","Fenced"=>"fenced in yard","Leashed"=>"leashed","Free"=>"free to roam");
		if(@$_POST["Residence"]||@isset($_POST["Rent"])||@$_POST["LiveIn"]||@$_POST["LiveOut"]||@isset($_POST["Fence"])||@$_POST["Fencing"]||@$_POST["OutsideMode"]||@$_POST["Sleep"]||@$_POST["Companionship"]) {
			$html.='<h1>Home information</h1>';
			$html.=(@isset($_POST["Rent"]))?'My home is <strong>'.(($_POST["Rent"])?'rented':'owned').'</strong>'.((@$_POST["Residence"])?':':'.'):'';
			$html.=(@$_POST["Residence"])?'<blockquote>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["Residence"])).'</blockquote>':'<br>';
			$liveinvalues=array('LiveIn'=>'inside','LiveOut'=>'outside','LiveBoth'=>'both inside and outside');
			$html.=(@$_POST["LiveIn"])?'The pet will live <strong>'.$liveinvalues[$_POST["LiveIn"]].'</strong>.<br><br>':'';
			$html.=(@$_POST["LiveIn"]!=='LiveIn')?('The yard <strong>'.((@isset($_POST["Fence"]))?(($_POST["Fence"])?'is':'is not'):'may or may not be').'</strong> fenced'.((@$_POST["Fencing"])?':<blockquote>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["Fencing"])).'</blockquote>':'.<br><br>').((@$_POST["OutsideMode"])?'When outside, the pet will be <strong>'.$asdf[$_POST["OutsideMode"]].'</strong>.<br><br>':'')):'';
			$html.=(@$_POST["Sleep"])?'At night, the pet will sleep '.htmlspecialchars($_POST["Sleep"]).'.<br><br>':'';
			$html.=(@$_POST["Companionship"])?'Companionship:<blockquote>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["Companionship"])).'</blockquote>':'';
		}
		else
			$html.="<br>";

		if(@$_POST["LiveOutLIES"]&&@!$_POST["LiveOut"])
			$html.='<strong>This applicant checked then unchecked the &quot;pet will live outside&quot; box.</strong>';

		if(@$_POST["VetName"]||@$_POST["VetAddress"]||@$_POST["VetPhone"]||@$_POST["RefName"]||@$_POST["RefAddress"]||@$_POST["RefPhone"]) {
			$html.='<h1>References</h1>';
			$html.=(@$_POST["VetName"]||@$_POST["VetAddress"]||@$_POST["VetPhone"])?'<h2>Veterinarian</h2>'.@htmlspecialchars($_POST["VetName"]).'<br>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["VetAddress"])).'<br>'.@htmlspecialchars($_POST["VetPhone"]):'';
			$html.=(@$_POST["RefName"]||@$_POST["RefAddress"]||@$_POST["RefPhone"])?'<h2>Personal reference</h2>'.@htmlspecialchars($_POST["RefName"]).'<br>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["RefAddress"])).'<br>'.@htmlspecialchars($_POST["RefPhone"]):'';
		}

		if(@$_POST["Comments"])
			$html.='<h1>Comments</h1>'.@str_replace(array("\r\n","\n","\r"),"<br>",htmlspecialchars($_POST["Comments"]));

		$html.='<br><br><br><br><div style="font-style:italic;font-size:small;font-family:monospace;">'.$appID.'</div>';
		$html.='</body></html>';

		file_put_contents("applications/".$appID.".html",$html);
		echo '<!DOCTYPE html><html><head><title>Adoption Application</title><meta charset="UTF-8"></head><body>';


		//SEND EMAIL
        require '/home/kimgil5/PHPMailer/src/Exception.php';
        require '/home/kimgil5/PHPMailer/src/PHPMailer.php';
        require '/home/kimgil5/PHPMailer/src/SMTP.php';

		$confirmation = new PHPMailer();
		$confirmation->IsSMTP();
        $application->SMTPSecure = @Config::$smtp_security;
        $application->Host = @Config::$smtp_host;
        $application->Port = @Config::$smtp_port;
        $application->SMTPAuth = @Config::$smtp_auth;
        $application->Username = @Config::$smtp_username;
        $application->Password = @Config::$smtp_password;
		$confirmation->From = "adopt@forgetmenotshelter.org";
		$confirmation->FromName = "Forget Me Not Animal Shelter";
		$confirmation->AddReplyTo("adopt@forgetmenotshelter.org","Forget Me Not Animal Shelter");
		if(@$_GET["debug"] === "email") $confirmation->AddAddress("adopt@forgetmenotshelter.org","Forget Me Not Animal Shelter");
		else $confirmation->AddAddress($_POST["AEmail"],$_POST["AName"]);
		if($_POST["CEmail"]) $confirmation->AddCC($_POST["CEmail"],$_POST["CName"]);
		$confirmation->IsHTML(false);
		$confirmation->Subject = "Your Forget Me Not Animal Shelter Adoption Application".((@$_POST["Particular"])?" for ".$_POST["Particular"]:"");
		$confirmation->Body = "Thank you for your adoption application; we will be reviewing it shortly (applications are reviewed Sunday through Thursday), and will contact you with any additional questions. In the meantime, please provide the required supplemental information by responding to this message, or emailing us at adoptions@forgetmenotshelter.org - this will help speed up your application processing time.\r\n\r\nFor our distance adopters (outside the Republic/Curlew area), please email a few photos of home (inside and outside, wherever your pets are allowed to go) /yard/fence/current pets, which can be emailed as .jpg attachments. Since we are unable to do pre-adoption home visits for our distance adopters, we rely on your photos to give us the best picture of the life your Forget Me Not pet will be living when they join your family. If your photos are large, please send only one or two per email (multiple emails are absolutely fine, we will put them all together with your application packet).\r\n\r\nFor all applications, if your home is not registered under your ownership with your county assessor, we need landlord permission for you to adopt. This can be written permission, a copy of the pet clause of your lease, or you can provide the landlord's name and phone number for us to contact directly (this includes homes owned by relatives - they always say YES, but we do need confirmation of that for our records).\r\n\r\nIf you do not hear back from us within 72 hours, something may have gone wrong with the application submission; please contact us with the application ID: $appID\r\n\r\nThanks so much for caring about shelter pets!\r\n\r\nKim Gillen, Adoption Coordinator\r\nForget Me Not Animal Shelter\r\n509-775-2308 (shelter)\r\n208-410-8200 (fax)\r\nhttp://www.forgetmenotshelter.org/\r\nlike us on Facebook: http://www.facebook.com/ForgetMeNotAnimalShelter";

		$application = new PHPMailer();
		$application->IsSMTP();
        $application->SMTPSecure = @Config::$smtp_security;
        $application->Host = @Config::$smtp_host;
        $application->Port = @Config::$smtp_port;
        $application->SMTPAuth = @Config::$smtp_auth;
        $application->Username = @Config::$smtp_username;
        $application->Password = @Config::$smtp_password;
		$application->From = "adopt@forgetmenotshelter.org";
		$application->FromName = "Application System";
		$application->AddReplyTo($_POST["AEmail"],$_POST["AName"]);
		$application->AddAddress('adopt@forgetmenotshelter.org');
		$application->IsHTML(true);
		$application->Subject = "Adoption Application".((@$_POST["Particular"])?" for ".$_POST["Particular"]:"")." from ".$_POST["AName"];
		$application->Body = $html;


		$confirmation->Send();

		if(!(@$_GET["debug"] === "email")) $sent = $application->Send();
		if($sent)
			echo '<!--new-->Thank you. We have received your application; you will be hearing back from us soon.<br><a href="http://forgetmenotshelter.org">Return to the shelter homepage</a><!--'.$appID.'-->';
		else{
			$error = $application->ErrorInfo;
			echo 'Sorry; an error occurred. If you inform us of this error by emailing <a href="mailto:adopt@forgetmenotshelter.org">adopt@forgetmenotshelter.org</a> or calling (509) 775-2308 and reporting the error code <span style="font-size:xx-large;font-weight:bold;font-family:monospace;">'.$appID.'</span>, we may be able to retrieve your application. <!--'.$application->ErrorInfo.'-->';
			//Add error info to application save file
			file_put_contents("applications/".$appID.".html",$application->ErrorInfo,FILE_APPEND);
		}

		echo '</body></html>';
	}
?>
