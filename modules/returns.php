<?php
/**
 * modules/returns.php
 * Staff-only page: mark approved requests as returned.
 * Restores equipment quantity_available.
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

$errors  = [];
$success = '';

// ---- Handle POST: mark as returned ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);

    if ($request_id <= 0) {
        $errors[] = 'Invalid request ID.';
    } else {
        try {
            // Fetch request + equipment info
            $stmt = $pdo->prepare(
                "SELECT r.*, e.name AS equipment_name
                 FROM borrow_requests r
                 JOIN equipment e ON e.id = r.equipment_id
                 WHERE r.id = ? LIMIT 1"
            );
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();

            if (!$req) {
                $errors[] = 'Request not found.';
            } elseif ($req['status'] !== 'approved') {
                $errors[] = 'Only approved requests can be returned. Current status: ' . $req['status'] . '.';
            } else {
                $pdo->beginTransaction();

                // Mark request as returned
                $pdo->prepare(
                    "UPDATE borrow_requests SET status = 'returned', returned_at = NOW() WHERE id = ?"
                )->execute([$request_id]);

                // Restore stock
                $pdo->prepare(
                    "UPDATE equipment SET quantity_available = quantity_available + ? WHERE id = ?"
                )->execute([$req['quantity'], $req['equipment_id']]);

                $pdo->commit();
                $success = 'Returned: ' . $req['quantity'] . ' × ' . $req['equipment_name'] . '.';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Return failed: ' . $e->getMessage();
        }
    }
}

// ---- Load outstanding (approved, not yet returned) ----
$outstanding = $pdo->query(
    "SELECT r.*, u.full_name, u.role AS user_role, e.name AS equipment_name
     FROM borrow_requests r
     JOIN users u     ON u.id = r.user_id
     JOIN equipment e ON e.id = r.equipment_id
     WHERE r.status = 'approved'
     ORDER BY r.date_needed ASC"
)->fetchAll();

// ---- Recently returned ----
$recent = $pdo->query(
    "SELECT r.*, u.full_name, e.name AS equipment_name
     FROM borrow_requests r
     JOIN users u     ON u.id = r.user_id
     JOIN equipment e ON e.id = r.equipment_id
     WHERE r.status = 'returned'
     ORDER BY r.returned_at DESC
     LIMIT 10"
)->fetchAll();

$page_title = "Returns";
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between">
    <h1>Process Returns</h1>
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

<!-- ============ OUTSTANDING ============ -->
<div class="card">
    <h2>Outstanding Loans (<?= count($outstanding) ?>)</h2>

    <?php if (empty($outstanding)): ?>
        <p style="color:#6b7280;">No items currently borrowed.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Role</th>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Date Needed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($outstanding as $o): ?>
                    <tr>
                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($o['user_role'])) ?></td>
                        <td><?= htmlspecialchars($o['equipment_name']) ?></td>
                        <td><?= (int)$o['quantity'] ?></td>
                        <td><?= htmlspecialchars($o['date_needed']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Mark this item as returned?');">
                                <input type="hidden" name="request_id" value="<?= (int)$o['id'] ?>">
                                <button type="submit" class="btn btn-small">Mark Returned</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ============ RECENT RETURNS ============ -->
<div class="card">
    <h2>Recently Returned</h2>
    <?php if (empty($recent)): ?>
        <p style="color:#6b7280;">No returns recorded yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Returned On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                        <td><?= (int)$r['quantity'] ?></td>
                        <td><?= htmlspecialchars($r['returned_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>