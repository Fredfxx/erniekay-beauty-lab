<?php
/**
 * Erniekay Beauty Lab - Booking Backend Handler (PHP)
 * Saves to MySQL Database & sends instant notification via Resend API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

// Accept both POST form-data and JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$fullName = trim($input['fullName'] ?? '');
$phone    = trim($input['phone'] ?? '');
$email    = trim($input['email'] ?? '');
$address  = trim($input['address'] ?? '');
$date     = trim($input['date'] ?? '');
$time     = trim($input['time'] ?? '');
$session  = trim($input['session'] ?? '');
$notes    = trim($input['notes'] ?? '');

// Validation
if (empty($fullName) || empty($phone) || empty($address) || empty($date) || empty($time) || empty($session)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields.'
    ]);
    exit;
}

// Map service slug to readable title
$serviceNames = [
    'bridal'   => 'Bridal Makeup',
    'hair'     => 'Hair Making & Styling',
    'everyday' => 'Everyday Glam',
    'photo'    => 'Photo & Film Makeup',
    'natural'  => 'Natural Glow Facial',
    'lash'     => 'Lash & Brow Design',
    'other'    => 'Other / Consultation'
];
$serviceTitle = $serviceNames[$session] ?? ucfirst($session);

// 1. Save to MySQL Database (if DB credentials configured)
$appointmentId = null;
if (!empty(DB_NAME) && !empty(DB_USER)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            INSERT INTO appointments (full_name, phone, email, address, service, preferred_date, preferred_time, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$fullName, $phone, $email, $address, $serviceTitle, $date, $time, $notes]);
        $appointmentId = $pdo->lastInsertId();
    } catch (Exception $e) {
        // If database error, log it but continue sending email so the booking is not lost
        error_log("Database error: " . $e->getMessage());
    }
}

// 2. Build Luxury HTML Email
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'/>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0B192C; margin: 0; padding: 24px; color: #ffffff; }
    .container { max-width: 580px; margin: 0 auto; background: #112238; border-radius: 14px; border: 1px solid rgba(201, 169, 110, 0.35); overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .header { background: linear-gradient(135deg, #1A1208, #2E1A06); padding: 32px 24px; text-align: center; border-bottom: 2px solid #C9A96E; }
    .header h1 { margin: 0; color: #E8D5B0; font-size: 22px; letter-spacing: 1px; font-weight: 600; }
    .header p { margin: 8px 0 0; color: #C9A96E; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; }
    .body-content { padding: 32px 28px; }
    .badge { display: inline-block; background: rgba(201, 169, 110, 0.15); color: #E8D5B0; border: 1px solid #C9A96E; padding: 6px 14px; border-radius: 20px; font-size: 13px; margin-bottom: 20px; font-weight: 600; }
    .detail-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .detail-table td { padding: 12px 8px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 14px; }
    .detail-table td.label { color: #A0B2C6; width: 36%; font-weight: 500; }
    .detail-table td.value { color: #ffffff; font-weight: 600; }
    .btn-wa { display: inline-block; background: #25D366; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; margin-top: 24px; text-align: center; font-size: 14px; }
    .footer { text-align: center; padding: 18px; font-size: 12px; color: #A0B2C6; border-top: 1px solid rgba(255,255,255,0.06); }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>✨ Erniekay Beauty Lab</h1>
      <p>New Appointment Request</p>
    </div>
    <div class='body-content'>
      <div class='badge'>🔔 Action Required: New Client Booking</div>
      <table class='detail-table'>
        <tr><td class='label'>Client Name:</td><td class='value'>{$fullName}</td></tr>
        <tr><td class='label'>Phone Number:</td><td class='value'><a href='tel:{$phone}' style='color:#E8D5B0;'>{$phone}</a></td></tr>
        <tr><td class='label'>Email:</td><td class='value'>" . ($email ? htmlspecialchars($email) : "<em>Not provided</em>") . "</td></tr>
        <tr><td class='label'>Location / Address:</td><td class='value'>{$address}</td></tr>
        <tr><td class='label'>Requested Service:</td><td class='value' style='color:#C9A96E;'>{$serviceTitle}</td></tr>
        <tr><td class='label'>Preferred Date:</td><td class='value'>{$date}</td></tr>
        <tr><td class='label'>Preferred Time:</td><td class='value'>{$time}</td></tr>
        <tr><td class='label'>Special Notes:</td><td class='value'>" . ($notes ? nl2br(htmlspecialchars($notes)) : "<em>None</em>") . "</td></tr>
      </table>
      <div style='text-align:center;'>
        <a class='btn-wa' href='https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "' target='_blank'>
          💬 Chat with Client on WhatsApp
        </a>
      </div>
    </div>
    <div class='footer'>
      &copy; " . date('Y') . " Erniekay Beauty Lab &bull; Kumasi, Ghana
    </div>
  </div>
</body>
</html>
";

// 3. Send email using Resend API via cURL
$resendPayload = [
    'from'    => FROM_EMAIL,
    'to'      => [OWNER_EMAIL],
    'subject' => "🔔 New Booking: {$fullName} - {$serviceTitle} on {$date}",
    'html'    => $htmlContent
];

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resendPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . RESEND_API_KEY,
    'Content-Type: application/json',
    'User-Agent: ErniekayBooking/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$emailSent = ($httpCode >= 200 && $httpCode < 300);

echo json_encode([
    'success'        => true,
    'message'        => 'Booking received successfully!',
    'appointment_id' => $appointmentId,
    'email_sent'     => $emailSent,
    'client_name'    => $fullName,
    'service'        => $serviceTitle,
    'date'           => $date,
    'time'           => $time
]);
