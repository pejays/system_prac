<?php
/**
 * modules/approvals.php
 * Staff-only page: view pending borrow requests, approve or reject them.
 * On approval, decrement equipment quantity_available.
 */
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /system_prac/index.php');
    exit;
}

// Staff only
if ($_SESSION['role'] !== 'staff') {
    header('Location: /system_prac/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$staff_id = $_SESSION['user_id'];
$errors   = [];
$success  = '';

// ---- Handle POST: approve or reject ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $request_id   = (int)($_POST['request_id'] ?? 0);

    if ($request_id <= 0) {
        $errors[] = 'Invalid request ID.';
    } elseif (!in_array($action, ['approve', 'reject'], true)) {
        $errors[] = 'Invalid action.';
    } else {
        try {
            // Fetch the request
            $stmt = $pdo->prepare(
                "SELECT r.*, e.quantity_available, e.name AS equipment_name
                 FROM borrow_requests r
                 JOIN equipment e ON e.id = r.equipment_id
                 WHERE r.id = ? LIMIT 1"
            );
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();

            if (!$req) {
                $errors[] = 'Request not found.';
            } elseif ($req['status'] !== 'pending') {
                $errors[] = 'This request has already been ' . $req['status'] . '.';
            } elseif ($action === 'approve') {
                // Re-check availability
                if ($req['quantity'] > (int)$req['quantity_available']) {
                    $errors[] = 'Not enough stock to approve (' . $req['quantity_available'] . ' left).';
                } else {
                    $pdo->beginTransaction();

                    $pdo->prepare(
                        "UPDATE borrow_requests SET status = 'approved', approved_by = ? WHERE id = ?"
                    )->execute([$staff_id, $request_id]);

                    $pdo->prepare(
                        "UPDATE equipment SET quantity_available = quantity_available - ? WHERE id = ?"
                    )->execute([$req['quantity'], $req['equipment_id']]);

                    $pdo->commit();
                    $success = 'Request approved. ' . $req['quantity'] . ' × ' . $req['equipment_name'] . ' reserved.';
                }
            } elseif ($action === 'reject') {
                $pdo->prepare(
                    "UPDATE borrow_requests SET status = 'rejected', approved_by = ? WHERE id = ?"
                )->execute([$staff_id, $request_id]);

                $success = 'Request rejected.';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Action failed: ' . $e->getMessage();
        }
    }
}

// ---- Load pending requests ----
$pending = $pdo->query(
    "SELECT r.*, u.full_name, u.role AS user_role, e.name AS equipment_name
     FROM borrow_requests r
     JOIN users u     ON u.id = r.user_id
     JOIN equipment e ON e.id = r.equipment_id
     WHERE r.status = 'pending'
     ORDER BY r.requested_at ASC"
)->fetchAll();

// ---- Load recently processed requests (for context) ----
$recent = $pdo->query(
    "SELECT r.*, u.full_name, e.name AS equipment_name
     FROM borrow_requests r
     JOIN users u     ON u.id = r.user_id
     JOIN equipment e ON e.id = r.equipment_id
     WHERE r.status IN ('approved','rejected')
     ORDER BY r.requested_at DESC
     LIMIT 10"
)->fetchAll();

$page_title = "Approvals";
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between">
    <h1>Borrow Request Approvals</h1>
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

<!-- ============ PENDING ============ -->
<div class="card">
    <h2>Pending Requests (<?= count($pending) ?>)</h2>

    <?php if (empty($pending)): ?>
        <p style="color:#6b7280;">No pending requests right now.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Role</th>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Purpose</th>
                    <th>Date Needed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($p['user_role'])) ?></td>
                        <td><?= htmlspecialchars($p['equipment_name']) ?></td>
                        <td><?= (int)$p['quantity'] ?></td>
                        <td><?= htmlspecialchars($p['purpose']) ?></td>
                        <td><?= htmlspecialchars($p['date_needed']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= (int)$p['id'] ?>">
                                <button name="action" value="approve" class="btn btn-small">Approve</button>
                                <button name="action" value="reject"  class="btn btn-danger btn-small">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ============ RECENT ============ -->
<div class="card">
    <h2>Recently Processed</h2>
    <?php if (empty($recent)): ?>
        <p style="color:#6b7280;">No processed requests yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Requested On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                        <td><?= (int)$r['quantity'] ?></td>
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