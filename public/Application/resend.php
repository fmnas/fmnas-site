<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
	if(!isset($_GET['id'])) die();

    require '/home/kimgil5/PHPMailer/src/Exception.php';
    require '/home/kimgil5/PHPMailer/src/PHPMailer.php';
    require '/home/kimgil5/PHPMailer/src/SMTP.php';

    var_dump(unserialize(file_get_contents()))

	$application = new PHPMailer();
	$application->IsSMTP();
	$application->SMTPAuth = true;
	$application->SMTPSecure = 'tls';
	$application->Host = "smtp.gmail.com";
	$application->Port = 587;
	$application->SMTPAuth = true;
	$application->Username = "apps@forgetmenotshelter.org";
	$application->Password = "xxsP3cPQU42KExM9cIZB";
	$application->From = "adopt@forgetmenotshelter.org";
	$application->FromName = "Application System";
	$application->AddReplyTo($_POST["AEmail"],$_POST["AName"]);
	$application->AddAddress('adopt@forgetmenotshelter.org');
	$application->AddAddress('admin@forgetmenotshelter.org');
	$application->IsHTML(true);
	$application->Subject = "Recovered application from ".date('F j, Y, g:i A',filemtime('applications/'.$_GET['id']));
	$application->Body = file_get_contents("applications/".$_GET['id'].".html");

	var_dump($application, $application->Send());
