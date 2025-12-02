<?php
// ====================================
// CONFIGURACIÓN INICIAL
// ====================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// RUTAS A PHPMailer (Ajustar si es necesario, basado en llenado.php)
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

    // 🔍 DEBUG: Mostrar datos recibidos (Solicitado por usuario)
    echo "<div style='background:#f4f4f4; padding:10px; border:1px solid #ccc; margin-bottom:20px; font-family:monospace;'>";
    echo "<h3 style='margin-top:0;'>🔍 Debug Data (Form Submission)</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";

    // --- Anti-bot honeypot ---
    if (!empty($_POST['company'])) {
        die('Bot detected. Submission blocked.');
    }

    // Recoger datos del formulario
    $name_agent          = trim($_POST['agent_name'] ?? '');
    $name_broker         = trim($_POST['broker_name'] ?? '');
    $dba                 = trim($_POST['dba'] ?? '');
    $phone_number_broker = trim($_POST['broker_phone'] ?? '');
    $email_broker        = trim($_POST['broker_email'] ?? '');
    $website_broker      = trim($_POST['broker_website'] ?? '');
    $mailing_broker      = trim($_POST['broker_address'] ?? '');

    $name_client         = trim($_POST['client_name'] ?? '');
    $land_entity         = trim($_POST['client_entity'] ?? '');
    $phone_number_client = trim($_POST['client_phone'] ?? '');
    $email_client        = trim($_POST['client_email'] ?? '');
    $mailing_client      = trim($_POST['client_address'] ?? '');
    $additional_notes_client = trim($_POST['client_notes'] ?? '');

    // Scope Mapping
    $scope_input = $_POST['scope'] ?? '';
    $scope_study = '';
    if ($scope_input === 'massing_only') $scope_study = 'Massing Only';
    elseif ($scope_input === 'full_feasibility') $scope_study = 'Full Feasibility';
    elseif ($scope_input === 'broker_partnership') $scope_study = 'Broker Partnership';

    // Intent Mapping
    $intent_input = $_POST['intent'] ?? '';
    $intent_study = '';
    if ($intent_input === 'acquire') $intent_study = 'Acquire';
    elseif ($intent_input === 'sell') $intent_study = 'Sell';

    // Property Info
    $property_acreage    = $_POST['acreage'] ?? 0;
    $property_zoning     = trim($_POST['zoning'] ?? '');
    $property_density    = intval($_POST['density'] ?? 0);
    
    // Entitled mapping
    $entitled_input = $_POST['entitled'] ?? '';
    $property_entitled = '';
    if ($entitled_input === 'Yes') $property_entitled = 'Yes';
    elseif ($entitled_input === 'No') $property_entitled = 'No';
    elseif ($entitled_input === 'In Process') $property_entitled = 'In Process';
    elseif ($entitled_input === 'Unknown') $property_entitled = 'Unknow';
    else $property_entitled = 'Unknow';

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
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["zoning_d$i"])) {
            $zonings[] = trim($_POST["zoning_d$i"]);
        }
    }
    $property_additional_zonings = implode('/', $zonings);

    $zoning_notes = trim($_POST['zoning_notes'] ?? '');

    // Site Criteria mapping
    $compliance_input = $_POST['compliance'] ?? '';
    $site_criteria = 'Not Comply';
    if ($compliance_input === 'all') $site_criteria = 'All';
    elseif ($compliance_input === 'most') $site_criteria = 'Most';
    elseif ($compliance_input === 'partial') $site_criteria = 'Partially';
    elseif ($compliance_input === 'no') $site_criteria = 'Not Comply';

    // URLs
    $enviroment_phase_one = trim($_POST['env_phase1_url'] ?? '');
    $enviroment_phase_two = trim($_POST['env_phase2_url'] ?? '');
    $survey               = trim($_POST['survey_url'] ?? '');
    $geo_report           = trim($_POST['geotech_url'] ?? '');
    $sketch_site_plan     = trim($_POST['sketch_url'] ?? '');

    $know_encumbrances    = trim($_POST['encumbrances'] ?? '');

    // 🛑 VALIDACIÓN DE CAMPOS REQUERIDOS
    // Lista de campos obligatorios según el HTML
    $required_fields = [
        'Agent Name' => $name_agent,
        'Broker Name' => $name_broker,
        'Broker Phone' => $phone_number_broker,
        'Broker Email' => $email_broker,
        'Client Name' => $name_client,
        'Scope' => $scope_study,
        'Intent' => $intent_study,
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
        exit; // Detener ejecución si faltan datos
    }

    // ====================================
    // CONEXIÓN A LA BASE DE DATOS
    // ====================================
    // (Movido aquí para evitar error de conexión si la validación falla)
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
        $sql = "INSERT INTO ztp_land (
            name_agent, name_broker, dba, phone_number_broker, email_broker, website_broker, mailing_broker,
            name_client, land_entity, phone_number_client, email_client, mailing_client, additional_notes_client,
            scope_study, intent_study, property_acreage, property_zoning, property_density, property_entitled, property_area_parcel,
            property_folio_number, property_additional_folios, property_additional_zonings, zoning_notes,
            site_criteria, enviroment_phase_one, enviroment_phase_two, survey, geo_report, sketch_site_plan,
            know_encumbrances
        ) VALUES (
            :name_agent, :name_broker, :dba, :phone_number_broker, :email_broker, :website_broker, :mailing_broker,
            :name_client, :land_entity, :phone_number_client, :email_client, :mailing_client, :additional_notes_client,
            :scope_study, :intent_study, :property_acreage, :property_zoning, :property_density, :property_entitled, :property_area_parcel,
            :property_folio_number, :property_additional_folios, :property_additional_zonings, :zoning_notes,
            :site_criteria, :enviroment_phase_one, :enviroment_phase_two, :survey, :geo_report, :sketch_site_plan,
            :know_encumbrances
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name_agent' => $name_agent,
            ':name_broker' => $name_broker,
            ':dba' => $dba,
            ':phone_number_broker' => $phone_number_broker,
            ':email_broker' => $email_broker,
            ':website_broker' => $website_broker,
            ':mailing_broker' => $mailing_broker,
            ':name_client' => $name_client,
            ':land_entity' => $land_entity,
            ':phone_number_client' => $phone_number_client,
            ':email_client' => $email_client,
            ':mailing_client' => $mailing_client,
            ':additional_notes_client' => $additional_notes_client,
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
            ':site_criteria' => $site_criteria,
            ':enviroment_phase_one' => $enviroment_phase_one,
            ':enviroment_phase_two' => $enviroment_phase_two,
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
        // ENVÍO AL BROKER/AGENTE (Confirmación)
        // ====================================
        $mail->setFrom($smtpUser, 'Zero to Plan');
        $mail->addAddress($email_broker, $name_agent);
        $mail->Subject = "Land Acquisition Form Received - Zero to Plan";
        $mail->isHTML(true);

        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color:#333;'>
            <h2>Hello {$name_agent},</h2>
            <p>Thank you for submitting the Land Acquisition Intake Form.</p>
            <p>We have received the details for the property at <strong>{$property_area_parcel}</strong> (Folio: {$property_folio_number}).</p>
            <p>Our team will review the information and you will receive an updated email with the Client Representation Agreement signed by us within 24 hours.</p>
            
            <p>Thank you for your business.</p>
            
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
        $mail->Subject = "📩 New Land Form Submission - {$name_agent}";
        $mail->Body = "
        <html>
        <body style='font-family:Arial,sans-serif;color:#333;'>
            <h3>New Land Acquisition Form</h3>
            <p><strong>Agent:</strong> {$name_agent}</p>
            <p><strong>Broker:</strong> {$name_broker}</p>
            <p><strong>Email:</strong> {$email_broker}</p>
            <p><strong>Phone:</strong> {$phone_number_broker}</p>
            <hr>
            <p><strong>Client:</strong> {$name_client}</p>
            <p><strong>Property:</strong> {$property_area_parcel}</p>
            <p><strong>Folio:</strong> {$property_folio_number}</p>
            <p><strong>Acreage:</strong> {$property_acreage}</p>
            <p><strong>Zoning:</strong> {$property_zoning}</p>
            <p><strong>Compliance:</strong> {$site_criteria}</p>
            <p><em>Submitted on " . date('Y-m-d H:i:s') . "</em></p>
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
