<?php
// ====================================
// CONFIGURACIÓN INICIAL
// ====================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// RUTAS A PHPMailer
require __DIR__ . '/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/PHPMailer-master/PHPMailer-master/src/Exception.php';

// CREDENCIALES SMTP
$smtpHost = 'mail.zerotoplan.com';
$smtpUser = 'no-reply@zerotoplan.com';
$smtpPass = '5)}dQ&%jli4j!8bc'; // cámbiala por la real
$smtpPort = 465; // o 587 si usas STARTTLS

// ====================================
// CONEXIÓN A LA BASE DE DATOS
// ====================================
$host = 'localhost';
$dbname = 'zero9111_landing';
$username = 'zero9111_jesusrey';
$password = 'o+[ZdH33O£RhD2/';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection error: " . $e->getMessage());
}

// ====================================
// PROCESAMIENTO DEL FORMULARIO
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- Anti-bot honeypot ---
    if (!empty($_POST['website'])) {
        die('Bot detected. Submission blocked.');
    }

    $timestamp = 0;
    if (isset($_POST["timestamp"]) && is_numeric($_POST["timestamp"])) {
        $timestamp = (int) $_POST["timestamp"];
    }

    // Calcula diferencia absoluta en segundos
    $delta = abs(time() - $timestamp);

    // Si el timestamp no existe, es menor a 3 segundos, o tiene más de 24 h → sospechoso
    if ($timestamp === 0 || $delta < 3 || $delta > 86400) {
        echo "Server time: " . time() . "<br>";
        echo "Timestamp received: " . $timestamp . "<br>";
        echo "Delta: " . abs(time() - $timestamp) . " seconds<br>";
        die("Suspiciously fast submission. Possible bot detected.");
    }


    $fullName     = trim($_POST["fullName"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $phoneNumber  = trim($_POST["phoneNumber"] ?? "");
    $direc        = trim($_POST["address"] ?? "");
    $descr        = trim($_POST["description"] ?? "");
    $brokerInvolvedRaw = trim($_POST["brokerInvolved"] ?? "");
    $brokerInvolved = ($brokerInvolvedRaw === "yes") ? 1 : 0;

    // Validar email real
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.'); window.history.back();</script>";
        exit;
    }

    if ($fullName && $email && $direc && $descr) {
        // 1. PROCESO DE BASE DE DATOS
        $dbError = null;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ztp_contact (fullName, email, phoneNumber, direc, descr, broker_involved)
                VALUES (:fullName, :email, :phoneNumber, :direc, :descr, :brokerInvolved)
            ");
            $stmt->execute([
                ":fullName" => $fullName,
                ":email" => $email,
                ":phoneNumber" => $phoneNumber,
                ":direc" => $direc,
                ":descr" => $descr,
                ":brokerInvolved" => $brokerInvolved
            ]);
        } catch (PDOException $e) {
            $dbError = $e->getMessage();
            error_log("❌ Database Error: " . $dbError);
        }

        // 2. PROCESO DE CORREO (Independiente)
        $mailError = null;
        try {
            // ====================================
            // CONFIGURAR CORREO (PHPMailer)
            // ====================================
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            // ENVÍO AL CLIENTE
            $mail->setFrom($smtpUser, 'Zero to Plan');
            $mail->addAddress($email, $fullName);
            $mail->Subject = "Welcome to Zero to Plan: Your Founder Pricing is Confirmed!";
            $mail->isHTML(true);

            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color:#333;'>
                <h2>Dear {$fullName},</h2>
                <p>Thank you for selecting <strong>Zero to Plan</strong> as your dedicated real estate feasibility and pre-development platform.</p>
                <p>We are pleased to confirm the successful submission of your request. Due to the <strong>overwhelming demand</strong> for our platform, and as quality remains our top priority, we are diligently processing existing requests and will be in contact with you shortly. </p>
                <p>We would like to confirm that your submission has <strong>successfully locked in our exclusive Founder Pricing</strong>. This special rate is now reserved for you as we initiate our partnership.</p>

                <p>Our team will be reaching out soon with two essential items:</p>
                <ol>
                    <li>A dedicated <strong>scheduling link</strong> to book an introductory call with our team.</li>
                    <li>Your personalized <strong>onboarding link.</strong></li>
                </ol>

                <p><strong>Next Steps and Project Details</stong></p>

                <p>The personalized onboarding link allows you to upload any specific project information you may have (e.g., site plans, zoning notes, financial goals). Providing this information is optional, but the <strong>more detailed data you provide upfront, the more accurate our initial analysis will be.</strong></p>

                <p>This communication will also include a detailed quote based on your site’s size and complexity, along with the estimated delivery time for your Zero to Plan study.</p>

                <p>We are delighted to welcome you. Every critical decision you make will now be built upon the <strong>robust data and precise numbers</strong> delivered by Zero to Plan. </p>

                <p>We look forward to partnering with you on your next successful development.</p>

                <br><br>
                <p>Sincerely,</p>
                
                <!-- Firma corporativa -->
                <table style='font-family:Arial,sans-serif;width:100%;max-width:500px;margin-top:10px;border-top:1px solid #ccc;padding-top:15px;'>
                <tr>
                    <td style='vertical-align:middle;padding-right:15px;width:90px;'>
                    <img src='https://zerotoplan.com/assets/img/Zerotoplan.webp' alt='Zero to Plan' style='max-width:90px;'>
                    </td>
                    <td style='vertical-align:middle;font-size:14px;color:#333;'>
                    <strong style='font-size:16px;color:#001b29;'>Zero to Plan Team</strong><br>
                    <span>Real Estate Feasibility Platform</span><br>
                    <span style= 'font-size:12px;color:#575757;'>Website: </span><a href='https://zerotoplan.com' style='color:#003f61;text-decoration:none;'>www.zerotoplan.com</a><br>
                    <span style= 'font-size:12px;color:#575757;'>Mail: </span><a href='mailto:info@zerotoplan.com' style='color:#003f61;text-decoration:none;'>info@zerotoplan.com</a><br>
                    <span style= 'font-size:12px;color:#575757;'>Number: </span><a href='tel:+19547305416' style='color:#003f61;text-decoration:none;'>+1 (954) 730-5416</a>
                    </td>
                </tr>
                </table>

            </body>
            </html>
            ";

            $mail->send();

            // ENVÍO INTERNO AL EQUIPO
            $mail->clearAddresses();
            $mail->addAddress("info@zerotoplan.com");
            $mail->Subject = "📩 New Quotation Form Submission - {$fullName}";
            $mail->Body = "
            <html>
            <body style='font-family:Arial,sans-serif;color:#333;'>
              <h3>New Contact Received</h3>
              <p><strong>Name:</strong> {$fullName}</p>
              <p><strong>Email:</strong> {$email}</p>
              <p><strong>Phone:</strong> {$phoneNumber}</p>
              <p><strong>Address:</strong> {$direc}</p>
              <p><strong>Broker/Agent Involved:</strong> " . ($brokerInvolved === 1 ? 'Yes' : 'No') . "</p>
              <p><strong>Message:</strong></p>
              <blockquote style='border-left:3px solid #ccc;padding-left:10px;color:#555;'>{$descr}</blockquote>
              <p><em>Submitted on " . date('Y-m-d H:i:s') . "</em><br></p>
              <h5>FZTP - #001</h5>
            </body>
            </html>";

            $mail->send();

            // ENVÍO ADICIONAL SI HAY BROKER
            if ($brokerInvolved === 1) {
                $mail->clearAddresses();
                $mail->addAddress($email, $fullName);
                $mail->Subject = "Additional Information Needed: Broker/Agent Details";
                $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif; color:#333;'>
                    <h2>Dear {$fullName},</h2>
                    <p>Thank you for your submission. As you indicated that there is a <strong>broker or agent involved</strong> in your project, we need some additional information.</p>
                    <p>Please take a moment to fill out our <strong>Broker/Agent Information Form</strong> so we can better understand your project and provide you with the best service.</p>
                    <p style='text-align:center; margin: 30px 0;'>
                        <a href='https://zerotoplan.com/land_form.html' style='display:inline-block; padding:15px 30px; background-color:#003f61; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold;'>Complete Broker/Agent Form</a>
                    </p>
                    <p>We appreciate your time and look forward to working with you.</p>
                    <br><br>
                    <p>Best regards,</p>
                    
                    <!-- Firma corporativa -->
                    <table style='font-family:Arial,sans-serif;width:100%;max-width:500px;margin-top:10px;border-top:1px solid #ccc;padding-top:15px;'>
                    <tr>
                        <td style='vertical-align:middle;padding-right:15px;width:90px;'>
                        <img src='https://zerotoplan.com/assets/img/Zerotoplan.webp' alt='Zero to Plan' style='max-width:90px;'>
                        </td>
                        <td style='vertical-align:middle;font-size:14px;color:#333;'>
                        <strong style='font-size:16px;color:#001b29;'>Zero to Plan Team</strong><br>
                        <span>Real Estate Feasibility Platform</span><br>
                        <span style= 'font-size:12px;color:#575757;'>Website: </span><a href='https://zerotoplan.com' style='color:#003f61;text-decoration:none;'>www.zerotoplan.com</a><br>
                        <span style= 'font-size:12px;color:#575757;'>Mail: </span><a href='mailto:info@zerotoplan.com' style='color:#003f61;text-decoration:none;'>info@zerotoplan.com</a><br>
                        <span style= 'font-size:12px;color:#575757;'>Number: </span><a href='tel:+19547305416' style='color:#003f61;text-decoration:none;'>+1 (954) 730-5416</a>
                        </td>
                    </tr>
                    </table>
                </body>
                </html>";
                $mail->send();
            }
        } catch (Exception $e) {
            $mailError = $e->getMessage();
            error_log("❌ Mail Error: " . $mailError);
        }

        // 3. RESPUESTA FINAL
        if ($dbError && $mailError) {
            // Ambos fallaron
            echo "<script>
                alert('⚠️ Total failure: Database and Email could not be processed.');
                window.history.back();
            </script>";
        } elseif ($dbError) {
            // Solo falló DB, mail enviado
            echo "<script>
                alert('✅ Email sent, but there was a database issue. Our team is notified.');
                window.location.href='index.html';
            </script>";
        } elseif ($mailError) {
            // Solo falló Mail, DB guardada
            echo "<script>
                alert('✅ Data saved, but there was an email issue. We will contact you soon.');
                window.location.href='index.html';
            </script>";
        } else {
            // Todo éxito
            echo "<script>
                alert('✅ Thank you! Your request has been received and confirmed.');
                window.location.href='index.html';
            </script>";
        }
    } else {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
