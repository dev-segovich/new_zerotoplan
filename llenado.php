<?php
// ====================================
// CONFIGURACIÓN DE CONEXIÓN
// ====================================
$host = 'localhost';
$dbname = 'zero9111_landing';
$username = 'zero9111_jesusrey';
$password = 'o+[ZdH33O£RhD2/';

// ====================================
// CONEXIÓN A LA BASE DE DATOS
// ====================================
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// ====================================
// PROCESAMIENTO DEL FORMULARIO
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName     = trim($_POST["fullName"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $phoneNumber  = trim($_POST["phoneNumber"] ?? "");
    $direc        = trim($_POST["address"] ?? "");
    $descr        = trim($_POST["description"] ?? "");

    if ($fullName && $email && $direc && $descr) {
        try {
            // Insertar datos en la base de datos
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
            // CORREO DE CONFIRMACIÓN AL USUARIO
            // ====================================
            $toUser = $email;
            $subjectUser = "Welcome to Zero to Plan: Your Founder Pricing is Confirmed!";

            $messageUser = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <h2 style='color:#006BA6;'>Dear $fullName,</h2>
            <p>Thank you so much for considering <strong>Zero to Plan</strong> as your dedicated real estate feasibility and pre-development platform.</p>

            <p>We are happy to inform you that we've received an overwhelming response to our platform! As quality is our top priority, we are currently busy processing existing requests and will be responding to you as soon as possible. Thank you for your patience as we ensure every client receives the best possible service.</p>

            <p>We would like to confirm that with this submission, you have successfully <strong>locked in our exclusive Founder Pricing</strong>. This special rate is now reserved for you as we begin our partnership.</p>

            <p><strong>Our team will be reaching out shortly with two critical items:</strong></p>
            <ul>
                <li>A dedicated scheduling link so you can book a quick introductory call with our team.</li>
                <li>Your personalized onboarding link, which will give you access to your client portal.</li>
            </ul>

            <p>You may use the client portal to upload any specific project information you may have (site plans, zoning notes, financial goals, etc.). This detail is not strictly required to begin, but the more information you can provide upfront, the better and more accurate our initial analysis can be.</p>

            <p>This communication will also include a detailed quote based on the size and complexity of your site, along with the estimated delivery time for your Zero to Plan study.</p>

            <p>We are glad to have you here with us. Welcome to the Zero to Plan family, where every critical decision is built upon robust data and precise numbers.</p>

            <p><strong>We look forward to partnering with you on your next successful development.</strong></p>

            <p>Sincerely,<br>The Zero to Plan Team</p>

            <!-- Firma visual -->
            <table cellpadding='6' cellspacing='0' border='0' style='border-top:1px solid #ccc;margin-top:25px;'>
                <tr>
                <td style='vertical-align:middle;'>
                    <img src='https://zerotoplan.com/assets/img/Zerotoplan.webp' alt='Zero to Plan' width='120' style='display:block;border:0;'>
                </td>
                <td style='vertical-align:middle; padding-left:10px; border-left:1px solid #ccc;'>
                    <p style='margin:0;font-size:14px;color:#000;'>
                    <strong>Zero to Plan</strong><br>
                    <em>Real Estate Feasibility Platform</em><br>
                    +1&nbsp;954&nbsp;459&nbsp;3936<br>
                    Email: <a href='mailto:info@zerotoplan.com' style='color:#006BA6;text-decoration:none;'>info@zerotoplan.com</a><br>
                    Website: <a href='https://zerotoplan.com' style='color:#006BA6;text-decoration:none;'>zerotoplan.com</a>
                    </p>
                </td>
                </tr>
            </table>
            </body>
            </html>";

            $headersUser = "MIME-Version: 1.0" . "\r\n";
            $headersUser .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headersUser .= "From: Zero to Plan <no-reply@zerotoplan.com>\r\n";

            @mail($toUser, $subjectUser, $messageUser, $headersUser);


            // ====================================
            // CORREO INTERNO AL EQUIPO
            // ====================================
            $toAdmin = "info@zerotoplan.com";
            $subjectAdmin = "📩 New Contact Form Submission - $fullName";
            $messageAdmin = "
            <html>
            <body style='font-family:Arial,sans-serif;color:#333;'>
              <h3>New Contact Received</h3>
              <p><strong>Name:</strong> $fullName</p>
              <p><strong>Email:</strong> $email</p>
              <p><strong>Phone:</strong> $phoneNumber</p>
              <p><strong>Address:</strong> $direc</p>
              <p><strong>Message:</strong></p>
              <blockquote style='border-left:3px solid #ccc;padding-left:10px;color:#555;'>$descr</blockquote>
              <p><em>Submitted on " . date('Y-m-d H:i:s') . "</em></p>
            </body>
            </html>";

            $headersAdmin = "MIME-Version: 1.0" . "\r\n";
            $headersAdmin .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headersAdmin .= "From: Zero to Plan <no-reply@zerotoplan.com>\r\n";

            @mail($toAdmin, $subjectAdmin, $messageAdmin, $headersAdmin);

            // ====================================
            // CONFIRMACIÓN VISUAL AL USUARIO
            // ====================================
            echo "<script>
                alert('✅ Thank you for contacting us! A confirmation email has been sent to your inbox.');
                window.location.href='index.html';
            </script>";

        } catch (PDOException $e) {
            echo "<script>
                alert('⚠️ Error saving data: " . addslashes($e->getMessage()) . "');
                window.history.back();
            </script>";
        }
    } else {
        echo "<script>
            alert('Please fill in all required fields.');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('Invalid request.');
        window.history.back();
    </script>";
}
?>
