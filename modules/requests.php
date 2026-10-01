<?php
/**
 * modules/requests.php
 * Student / Faculty view: browse equipment and submit borrow requests.
 * Also shows the user's own request history.
 */
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /system_prac/index.php');
    exit;
}

// Students and faculty only (staff uses approvals.php instead)
if ($_SESSION['role'] === 'staff') {
    header('Location: /system_prac/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = '';

// ---- Handle POST: submit a new request ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    $quantity     = (int)($_POST['quantity'] ?? 0);
    $purpose      = trim($_POST['purpose'] ?? '');
    $date_needed  = trim($_POST['date_needed'] ?? '');

    // Validation
    if ($equipment_id <= 0)  $errors[] = 'Please select equipment.';
    if ($quantity <= 0)      $errors[] = 'Quantity must be greater than 0.';
    if ($purpose === '')     $errors[] = 'Purpose is required.';
    if ($date_needed === '') {
        $errors[] = 'Date needed is required.';
    } elseif ($date_needed < date('Y-m-d')) {
        $errors[] = 'Date needed cannot be in the past.';
    }

    // Check availability
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT quantity_available, condition_status FROM equipment WHERE id = ? LIMIT 1");
        $stmt->execute([$equipment_id]);
        $eq = $stmt->fetch();

        if (!$eq) {
            $errors[] = 'Selected equipment not found.';
        } elseif ($eq['condition_status'] !== 'good') {
            $errors[] = 'This equipment is not currently available (condition: ' . $eq['condition_status'] . ').';
        } elseif ($quantity > (int)$eq['quantity_available']) {
            $errors[] = 'Only ' . $eq['quantity_available'] . ' unit(s) available.';
        }
    }

    // Insert request
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO borrow_requests (user_id, equipment_id, quantity, purpose, date_needed, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$user_id, $equipment_id, $quantity, $purpose, $date_needed]);
            $success = 'Borrow request submitted! Waiting for staff approval.';
        } catch (PDOException $e) {
            $errors[] = 'Failed to submit request: ' . $e->getMessage();
        }
    }
}

// ---- Load available equipment ----
$equipment_list = $pdo->query(
    "SELECT id, name, category, quantity_available, condition_status
     FROM equipment
     WHERE condition_status = 'good' AND quantity_available > 0
     ORDER BY name"
)->fetchAll();

// ---- Load this user's request history ----
$stmt = $pdo->prepare(
    "SELECT r.*, e.name AS equipment_name
     FROM borrow_requests r
     JOIN equipment e ON e.id = r.equipment_id
     WHERE r.user_id = ?
     ORDER BY r.requested_at DESC"
);
$stmt->execute([$user_id]);
$my_requests = $stmt->fetchAll();

$page_title = "My Requests";
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between">
    <h1>My Borrow Requests</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
            • <?= htmlspecialchars($e) ?><br>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============ NEW REQUEST FORM ============ -->
<div class="card">
    <h2>Submit a Borrow Request</h2>

    <?php if (empty($equipment_list)): ?>
        <p style="color:#6b7280;">No equipment currently available.</p>
    <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label for="equipment_id">Equipment</label>
                <select id="equipment_id" name="equipment_id" required>
                    <option value="">— Select equipment —</option>
                    <?php foreach ($equipment_list as $eq): ?>
                        <option value="<?= (int)$eq['id'] ?>">
                            <?= htmlspecialchars($eq['name']) ?>
                            (<?= htmlspecialchars($eq['category']) ?>) —
                            <?= (int)$eq['quantity_available'] ?> available
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" required>
            </div>

            <div class="form-group">
                <label for="purpose">Purpose</label>
                <textarea id="purpose" name="purpose" rows="3" required
                          placeholder="e.g., Presentation for IT415 class"></textarea>
            </div>

            <div class="form-group">
                <label for="date_needed">Date Needed</label>
                <input type="date" id="date_needed" name="date_needed"
                       min="<?= date('Y-m-d') ?>" required>
            </div>

            <button type="submit" class="btn">Submit Request</button>
        </form>
    <?php endif; ?>
</div>

<!-- ============ MY REQUESTS HISTORY ============ -->
<div class="card">
    <h2>My Request History (<?= count($my_requests) ?>)</h2>

    <?php if (empty($my_requests)): ?>
        <p style="color:#6b7280;">You have not submitted any requests yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Date Needed</th>
                    <th>Status</th>
                    <th>Requested On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_requests as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                        <td><?= (int)$r['quantity'] ?></td>
                        <td><?= htmlspecialchars($r['date_needed']) ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($r['status']) ?>">
                                <?= htmlspecialchars(ucfirst($r['status'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['requested_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>