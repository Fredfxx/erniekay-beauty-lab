<?php
/**
 * Admin Portal (PHP + MySQL) for Erniekay Beauty Lab
 */
require_once __DIR__ . '/config.php';

session_start();
$ADMIN_PIN = "1234"; // Default PIN, can be changed anytime

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Handle login
$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if ($_POST['pin'] === $ADMIN_PIN) {
        $_SESSION['admin_auth'] = true;
    } else {
        $loginError = true;
    }
}

// Fetch bookings from database if logged in
$bookings = [];
$totalCount = 0;
$pendingCount = 0;
$confirmedCount = 0;

if (!empty($_SESSION['admin_auth'])) {
    if (!empty(DB_NAME) && !empty(DB_USER)) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Handle status update
            if (isset($_GET['action']) && isset($_GET['id'])) {
                $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                $stmt->execute([$_GET['action'], (int)$_GET['id']]);
                header("Location: admin.php");
                exit;
            }

            $stmt = $pdo->query("SELECT * FROM appointments ORDER BY preferred_date DESC, preferred_time ASC");
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalCount = count($bookings);
            foreach ($bookings as $b) {
                if ($b['status'] === 'confirmed') $confirmedCount++;
                else $pendingCount++;
            }
        } catch (Exception $e) {
            $dbError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Portal – <?php echo htmlspecialchars(BUSINESS_NAME); ?></title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body { background: #08111D; color: #F0F4F8; min-height: 100vh; font-family: var(--ff-body); }
    .admin-nav { background: #0B192C; border-bottom: 1px solid rgba(201,169,110,0.25); padding: 16px 36px; display: flex; justify-content: space-between; align-items: center; }
    .admin-title { display: flex; align-items: center; gap: 12px; font-family: var(--ff-heading); font-size: 1.4rem; color: var(--gold); }
    .admin-container { max-width: 1200px; margin: 36px auto; padding: 0 24px; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .stat-card { background: #112238; border: 1px solid rgba(201,169,110,0.22); border-radius: 12px; padding: 24px; display: flex; align-items: center; gap: 18px; }
    .stat-card-icon { width: 50px; height: 50px; border-radius: 10px; background: rgba(201,169,110,0.15); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    .stat-card h4 { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.08em; color: #9BB0C7; margin-bottom: 4px; }
    .stat-card .val { font-size: 1.8rem; font-weight: 700; color: var(--gold-light); }
    .table-wrap { background: #112238; border: 1px solid rgba(201,169,110,0.22); border-radius: 12px; overflow-x: auto; box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #0D1B2A; padding: 16px 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gold); border-bottom: 1px solid rgba(201,169,110,0.2); }
    td { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.9rem; vertical-align: middle; }
    tr:hover td { background: rgba(201,169,110,0.04); }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .status-pending { background: rgba(245,166,35,0.2); color: #F5A623; border: 1px solid #F5A623; }
    .status-confirmed { background: rgba(37,211,102,0.2); color: #25D366; border: 1px solid #25D366; }
    .btn-wa-sm { display: inline-flex; align-items: center; gap: 6px; background: #25D366; color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-decoration: none; }
    .pin-modal { position: fixed; inset: 0; background: rgba(8,17,29,0.96); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 999; }
    .pin-card { background: #112238; border: 1px solid rgba(201,169,110,0.3); padding: 40px; border-radius: 16px; text-align: center; max-width: 360px; width: 90%; }
  </style>
</head>
<body>

<?php if (empty($_SESSION['admin_auth'])): ?>
  <div class="pin-modal">
    <div class="pin-card">
      <div style="font-size: 2.2rem; color: var(--gold); margin-bottom: 12px;"><i class="fas fa-lock"></i></div>
      <h3 style="font-family: var(--ff-heading); font-size: 1.6rem; color: var(--gold-light); margin-bottom: 8px;">Admin Login</h3>
      <p style="color: #9BB0C7; font-size: 0.88rem; margin-bottom: 20px;">Enter your admin PIN to view salon bookings.</p>
      <form method="POST">
        <input type="password" name="pin" placeholder="Enter PIN (Default: 1234)" required style="text-align: center; font-size: 1.2rem; letter-spacing: 4px; margin-bottom: 16px;"/>
        <button type="submit" class="btn-primary" style="width: 100%;">Unlock Portal</button>
      </form>
      <?php if ($loginError): ?>
        <p style="color: #D4847A; font-size: 0.8rem; margin-top: 10px;">Incorrect PIN. Try again.</p>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

  <header class="admin-nav">
    <div class="admin-title">
      <i class="fas fa-crown"></i>
      <span><?php echo htmlspecialchars(BUSINESS_NAME); ?> &bull; Admin Portal</span>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <a href="index.html" class="btn-outline" style="padding: 8px 20px; font-size: 0.85rem;"><i class="fas fa-external-link-alt"></i> Website</a>
      <a href="admin.php?logout=1" class="btn-primary" style="padding: 8px 20px; font-size: 0.85rem; background: #D4847A;">Logout</a>
    </div>
  </header>

  <div class="admin-container">
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
          <h4>Total Bookings</h4>
          <div class="val"><?php echo $totalCount; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="color: #F5A623; background: rgba(245,166,35,0.15);"><i class="fas fa-clock"></i></div>
        <div>
          <h4>Pending Review</h4>
          <div class="val" style="color: #F5A623;"><?php echo $pendingCount; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="color: #25D366; background: rgba(37,211,102,0.15);"><i class="fas fa-check-double"></i></div>
        <div>
          <h4>Confirmed</h4>
          <div class="val" style="color: #25D366;"><?php echo $confirmedCount; ?></div>
        </div>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Client Name</th>
            <th>Phone / Email</th>
            <th>Location</th>
            <th>Service</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="8" style="text-align:center; color:#9BB0C7; padding:40px;">No bookings found in database.</td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $b):
              $waNum = preg_replace('/[^0-9]/', '', $b['phone']);
              $waMsg = urlencode("Hello " . $b['full_name'] . "! We are contacting you regarding your booking for " . $b['service'] . " on " . $b['preferred_date'] . ".");
              $badgeClass = ($b['status'] === 'confirmed') ? 'status-confirmed' : 'status-pending';
            ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($b['preferred_date']); ?></strong><br/><small style="color:#9BB0C7;"><?php echo htmlspecialchars($b['preferred_time']); ?></small></td>
                <td><strong style="color:var(--gold-light);"><?php echo htmlspecialchars($b['full_name']); ?></strong></td>
                <td><a href="tel:<?php echo htmlspecialchars($b['phone']); ?>" style="color:#fff; text-decoration:underline;"><?php echo htmlspecialchars($b['phone']); ?></a><br/><small style="color:#9BB0C7;"><?php echo htmlspecialchars($b['email']); ?></small></td>
                <td><?php echo htmlspecialchars($b['address']); ?></td>
                <td><strong style="color:var(--gold);"><?php echo htmlspecialchars($b['service']); ?></strong></td>
                <td><small style="color:#9BB0C7;"><?php echo htmlspecialchars($b['notes'] ?: '—'); ?></small></td>
                <td><span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                <td style="white-space:nowrap;">
                  <a class="btn-wa-sm" href="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waMsg; ?>" target="_blank">
                    <i class="fab fa-whatsapp"></i> Chat
                  </a>
                  <?php if ($b['status'] !== 'confirmed'): ?>
                    <a href="admin.php?action=confirmed&id=<?php echo $b['id']; ?>" style="color:#25D366; font-size:0.8rem; margin-left:8px; font-weight:600;">Confirm</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
