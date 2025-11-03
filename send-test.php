<?php
require __DIR__ . 'PHPMailer/src/PHPMailer.php';
require __DIR__ . 'PHPMailer/src/SMTP.php';
require __DIR__ . 'PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
$m = new PHPMailer(true);
$m->isSMTP();
$m->Host='mail.zerotoplan.com';
$m->SMTPAuth=true;
$m->Username='no-reply@zerotoplan.com';
$m->Password='5)}dQ&%jli4j!8bc';
$m->SMTPSecure=PHPMailer::ENCRYPTION_SMTPS;
$m->Port=465;
$m->setFrom('no-reply@zerotoplan.com','Zero to Plan');
$m->addAddress('jesus.blondell@zerotoplan.com');
$m->isHTML(true);
$m->Subject='SMTP OK test';
$m->Body='Funciona ✅';
var_dump($m->send());
