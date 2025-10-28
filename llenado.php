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
                INSERT INTO contact_form (fullName, email, phoneNumber, direc, descr, created_at)
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
            $subjectUser = "Thank you for contacting Zero to Plan";
            $messageUser = "
            <html>
            <body style='font-family:Arial,sans-serif;color:#333;'>
              <h2>Hi $fullName,</h2>
              <p>Thank you for reaching out to <strong>Zero to Plan</strong>.</p>
              <p>We’ve received your message and one of our team members will get in touch with you soon.</p>
              <p><strong>Your message:</strong></p>
              <blockquote style='border-left:3px solid #006BA6;padding-left:10px;color:#555;'>$descr</blockquote>
              <p style='margin-top:20px;'>Best regards,<br>
              <strong>The Zero to Plan Team</strong><br>
              <a href='https://zerotoplan.com' style='color:#006BA6;'>zerotoplan.com</a></p>
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
