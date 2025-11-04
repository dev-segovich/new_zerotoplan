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

    // --- Time-trap ---
    $minElapsed = 3; // segundos mínimos permitidos
    if (isset($_POST['timestamp']) && (time() - $_POST['timestamp'] < $minElapsed)) {
        die('Suspiciously fast submission.');
    }

    $fullName     = trim($_POST["fullName"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $phoneNumber  = trim($_POST["phoneNumber"] ?? "");
    $direc        = trim($_POST["address"] ?? "");
    $descr        = trim($_POST["description"] ?? "");

    // Validar email real
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.'); window.history.back();</script>";
        exit;
    }

    if ($fullName && $email && $direc && $descr) {
        try {
            // Insertar en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO new_people (fullName, email, phoneNumber, direc, descr, created_at)
                VALUES (:fullName, :email, :phoneNumber, :direc, :descr, CURDATE())
            ");
            $stmt->execute([
                ":fullName" => $fullName,
                ":email" => $email,
                ":phoneNumber" => $phoneNumber,
                ":direc" => $direc,
                ":descr" => $descr
            ]);

            // ====================================
            // CONFIGURAR CORREO (PHPMailer)
            // ====================================
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // o STARTTLS si usas 587
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            // ====================================
            // ENVÍO AL CLIENTE
            // ====================================
            $mail->setFrom($smtpUser, 'Zero to Plan');
            $mail->addAddress($email, $fullName);
            $mail->Subject = "Welcome to Zero to Plan: Your Founder Pricing is Confirmed!";
            $mail->isHTML(true);

            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color:#333;'>
                <h2>Dear {$fullName},</h2>
                <p>Thank you so much for considering <strong>Zero to Plan</strong> as your dedicated real estate feasibility and pre-development platform.</p>
                <p>We are happy to inform you that we've received an overwhelming response to our platform! As quality is our top priority, we are currently busy processing existing requests and will be responding to you as soon as possible.</p>
                <p>We would like to confirm that with this submission, you have successfully locked in our <strong>exclusive Founder Pricing</strong>. This special rate is now reserved for you as we begin our partnership.</p>

                <p>Our team will be reaching out shortly with two critical items:</p>
                <ol>
                    <li>A dedicated scheduling link to book a quick introductory call with our team.</li>
                    <li>Your personalized onboarding link, giving you access to your client portal.</li>
                </ol>

                <p>You may use the client portal to upload any specific project information you may have (site plans, zoning notes, financial goals, etc.). This is optional, but the more information you can provide upfront, the more accurate our initial analysis will be.</p>

                <p>This communication will also include a detailed quote based on your site’s size and complexity, along with the estimated delivery time for your Zero to Plan study.</p>

                <p>We are glad to have you here. Welcome to the Zero to Plan family, where every critical decision is built upon robust data and precise numbers.</p>

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
                    <strong style='font-size:16px;color:#001b29;'>The Zero to Plan Team</strong><br>
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

            // ====================================
            // ENVÍO INTERNO AL EQUIPO
            // ====================================
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
              <p><strong>Message:</strong></p>
              <blockquote style='border-left:3px solid #ccc;padding-left:10px;color:#555;'>{$descr}</blockquote>
              <p><em>Submitted on " . date('Y-m-d H:i:s') . "</em><br></p>
              <h5>FZTP - #001</h5>
            </body>
            </html>";

            $mail->send();

            // ====================================
            // CONFIRMACIÓN VISUAL AL USUARIO
            // ====================================
            echo "<script>
                alert('✅ Thank you for contacting us! A confirmation email has been sent to your inbox.');
                window.location.href='index.html';
            </script>";

        } catch (Exception $e) {
            echo "<script>
                alert('⚠️ Mail error: " . addslashes($e->getMessage()) . "');
                window.history.back();
            </script>";
        } catch (PDOException $e) {
            echo "<script>
                alert('⚠️ Database error: " . addslashes($e->getMessage()) . "');
                window.history.back();
            </script>";
        }
    } else {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
