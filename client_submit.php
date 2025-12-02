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
$smtpPass = '5)}dQ&%jli4j!8bc'; // Verificar credenciales
$smtpPort = 465;

// ====================================
// PROCESAMIENTO DEL FORMULARIO
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 🔍 DEBUG: Mostrar datos recibidos
    echo "<div style='background:#f4f4f4; padding:10px; border:1px solid #ccc; margin-bottom:20px; font-family:monospace;'>";
    echo "<h3 style='margin-top:0;'>🔍 Debug Data (Client Form Submission)</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";

    // --- Anti-bot honeypot ---
    if (!empty($_POST['company'])) {
        die('Bot detected. Submission blocked.');
    }

    // Recoger datos del formulario
    $full_name      = trim($_POST['full_name'] ?? '');
    $land_entity    = trim($_POST['entity'] ?? '');
    $phone_number   = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $mailing_address = trim($_POST['address'] ?? '');
    
    // Broker Info Logic
    $has_broker = $_POST['has_broker'] ?? '';
    $broker_contact = trim($_POST['broker_contact'] ?? '');
    $broker_agent_info = ($has_broker === 'yes') ? $broker_contact : 'No Broker Involved';

    $additional_notes = trim($_POST['notes'] ?? '');

    // Scope Mapping
    $scope_input = $_POST['scope'] ?? '';
    $scope_study = '';
    if ($scope_input === 'massing_only') $scope_study = 'Massing Only';
    elseif ($scope_input === 'full_feasibility') $scope_study = 'Full Feasibility';

    // Intent Mapping
    $intent_input = $_POST['intent'] ?? '';
    $intent_study = '';
    if ($intent_input === 'acquire') $intent_study = 'Acquire';
    elseif ($intent_input === 'sell') $intent_study = 'Sell';

    // Property Info
    $property_acreage = $_POST['acreage'] ?? 0;
    $property_zoning  = trim($_POST['zoning'] ?? '');
    $property_density = intval($_POST['density'] ?? 0);

    // Entitled mapping
    $entitled_input = $_POST['entitled'] ?? '';
    $property_entitled = 'Unknow';
    if ($entitled_input === 'Yes') $property_entitled = 'Yes';
    elseif ($entitled_input === 'No') $property_entitled = 'No';
    elseif ($entitled_input === 'In Process') $property_entitled = 'In Process';
    elseif ($entitled_input === 'Unknown') $property_entitled = 'Unknow';

    $property_area_parcel = trim($_POST['area'] ?? '');
    $property_folio_number = trim($_POST['folio'] ?? '');

    // Additional Folios
    $folios = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["folio_$i"])) {
            $folios[] = trim($_POST["folio_$i"]);
        }
    }
    $property_additional_folios = implode('/', $folios);

    // Additional Zonings
    $zonings = [];
    // Primary zoning district is usually just 'zoning', but form has 'zoning_d1'
    // If 'zoning_d1' is filled, it might be part of the additional list or the main one.
    // Based on land_submit, we treated d1-d5 as additional or part of the list.
    // Let's collect d1-d5.
    $zoning_list = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["zoning_d$i"])) {
            $zoning_list[] = trim($_POST["zoning_d$i"]);
        }
    }
    $property_additional_zonings = implode('/', $zoning_list);

    $zoning_notes = trim($_POST['zoning_notes'] ?? '');

    // URLs
    $environmental_phase_one = trim($_POST['env_phase1_url'] ?? '');
    $environmental_phase_two = trim($_POST['env_phase2_url'] ?? '');
    $survey                  = trim($_POST['survey_url'] ?? '');
    $geo_report              = trim($_POST['geotech_url'] ?? '');
    $sketch_site_plan        = trim($_POST['sketch_url'] ?? '');

    $know_encumbrances       = trim($_POST['encumbrances'] ?? '');

    // 🛑 VALIDACIÓN DE CAMPOS REQUERIDOS
    $required_fields = [
        'Full Name' => $full_name,
        'Phone Number' => $phone_number,
        'Email Address' => $email,
        'Scope of Study' => $scope_study,
        'Intent of Study' => $intent_study
    ];

    $missing = [];
    foreach ($required_fields as $label => $value) {
        if (empty($value)) {
            $missing[] = $label;
        }
    }

    if (!empty($missing)) {
        $missing_str = implode(', ', $missing);
        echo "<div style='color:red; font-weight:bold; padding:10px; border:1px solid red; background:#ffe6e6;'>";
        echo "⚠️ Missing required fields: $missing_str.<br>";
        echo "Please go back and fill them in.";
        echo "</div>";
        exit;
    }

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
        die("<br><strong>❌ Database connection error:</strong> " . $e->getMessage());
    }

    try {
        // Insertar en base de datos
        $sql = "INSERT INTO ztp_client (
            full_name, land_entity, phone_number, email, mailing_address, broker_agent_info, additional_notes,
            scope_study, intent_study, property_acreage, property_zoning, property_density, property_entitled,
            property_area_parcel, property_folio_number, property_additional_folios, property_additional_zonings,
            zoning_notes, environmental_phase_one, environmental_phase_two, survey, geo_report, sketch_site_plan,
            know_encumbrances
        ) VALUES (
            :full_name, :land_entity, :phone_number, :email, :mailing_address, :broker_agent_info, :additional_notes,
            :scope_study, :intent_study, :property_acreage, :property_zoning, :property_density, :property_entitled,
            :property_area_parcel, :property_folio_number, :property_additional_folios, :property_additional_zonings,
            :zoning_notes, :environmental_phase_one, :environmental_phase_two, :survey, :geo_report, :sketch_site_plan,
            :know_encumbrances
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':land_entity' => $land_entity,
            ':phone_number' => $phone_number,
            ':email' => $email,
            ':mailing_address' => $mailing_address,
            ':broker_agent_info' => $broker_agent_info,
            ':additional_notes' => $additional_notes,
            ':scope_study' => $scope_study,
            ':intent_study' => $intent_study,
            ':property_acreage' => $property_acreage,
            ':property_zoning' => $property_zoning,
            ':property_density' => $property_density,
            ':property_entitled' => $property_entitled,
            ':property_area_parcel' => $property_area_parcel,
            ':property_folio_number' => $property_folio_number,
            ':property_additional_folios' => $property_additional_folios,
            ':property_additional_zonings' => $property_additional_zonings,
            ':zoning_notes' => $zoning_notes,
            ':environmental_phase_one' => $environmental_phase_one,
            ':environmental_phase_two' => $environmental_phase_two,
            ':survey' => $survey,
            ':geo_report' => $geo_report,
            ':sketch_site_plan' => $sketch_site_plan,
            ':know_encumbrances' => $know_encumbrances
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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        // ====================================
        // ENVÍO AL CLIENTE (Confirmación)
        // ====================================
        $mail->setFrom($smtpUser, 'Zero to Plan');
        $mail->addAddress($email, $full_name);
        $mail->Subject = "Client Intake Form Received - Zero to Plan";
        $mail->isHTML(true);

        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color:#333;'>
            <h2>Hello {$full_name},</h2>
            <p>Thank you for submitting your property information to Zero to Plan.</p>
            <p>We have received your request for a <strong>{$scope_study}</strong> regarding the property at <strong>{$property_area_parcel}</strong>.</p>
            <p>Our team will analyze the provided details and contact you shortly to discuss the next steps.</p>
            
            <br><br>
            <p>Sincerely,</p>
            <p><strong>Zero to Plan Team</strong></p>
        </body>
        </html>
        ";

        $mail->send();

        // ====================================
        // ENVÍO INTERNO AL EQUIPO
        // ====================================
        $mail->clearAddresses();
        $mail->addAddress("info@zerotoplan.com");
        $mail->Subject = "📩 New Land Intake Form (Clients) - {$full_name}";
        $mail->Body = "
        <html>
        <body style='font-family:Arial,sans-serif;color:#333;'>
            <h3>New Client Intake Form</h3>
            <p><strong>Client:</strong> {$full_name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Phone:</strong> {$phone_number}</p>
            <p><strong>Scope:</strong> {$scope_study}</p>
            <p><strong>Intent:</strong> {$intent_study}</p>
            <hr>
            <p><strong>Property:</strong> {$property_area_parcel}</p>
            <p><strong>Folio:</strong> {$property_folio_number}</p>
            <p><strong>Acreage:</strong> {$property_acreage}</p>
            <p><strong>Zoning:</strong> {$property_zoning}</p>
            <p><em>Submitted on " . date('Y-m-d H:i:s') . "</em></p>
            <h5>FZTP - #003</h5>
        </body>
        </html>";

        $mail->send();

        // ====================================
        // CONFIRMACIÓN VISUAL
        // ====================================
        echo "<script>
            alert('✅ Form submitted successfully! We will be in touch shortly.');
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
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
