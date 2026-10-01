<?php
/**
 * dashboard.php
 * Main landing page after login. Content changes by role.
 */
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /system_prac/index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$role      = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// ---- Load quick stats depending on role ----
try {
    if ($role === 'staff') {
        // Staff stats
        $total_equipment = $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
        $pending_count   = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'")->fetchColumn();
        $active_loans    = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'approved'")->fetchColumn();
    } else {
        // Student/faculty: their own stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $my_requests = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id']]);
        $my_pending = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE user_id = ? AND status = 'approved'");
        $stmt->execute([$_SESSION['user_id']]);
        $my_approved = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    die('Dashboard error: ' . $e->getMessage());
}

$page_title = "Dashboard";
include __DIR__ . '/includes/header.php';
?>

<h1>Welcome, <?= htmlspecialchars($full_name) ?> 👋</h1>
<p style="color:#6b7280; margin-bottom:24px;">
    You are logged in as <strong><?= htmlspecialchars(ucfirst($role)) ?></strong>.
</p>

<?php if ($role === 'staff'): ?>

    <div class="card">
        <h2>Staff Overview</h2>
        <table>
            <tr><th>Total Equipment</th><td><?= (int)$total_equipment ?></td></tr>
            <tr><th>Pending Requests</th><td><?= (int)$pending_count ?></td></tr>
            <tr><th>Active Loans (approved, not yet returned)</th><td><?= (int)$active_loans ?></td></tr>
        </table>

        <div class="mt-2" style="margin-top:16px;">
            <a href="/system_prac/modules/equipment.php" class="btn">Manage Equipment</a>
            <a href="/system_prac/modules/approvals.php" class="btn">Review Approvals</a>
            <a href="/system_prac/modules/returns.php"   class="btn">Process Returns</a>
        </div>
    </div>

<?php else: ?>

    <div class="card">
        <h2>My Borrowing Summary</h2>
        <table>
            <tr><th>Total Requests</th><td><?= (int)$my_requests ?></td></tr>
            <tr><th>Pending</th><td><?= (int)$my_pending ?></td></tr>
            <tr><th>Approved</th><td><?= (int)$my_approved ?></td></tr>
        </table>

        <div class="mt-2" style="margin-top:16px;">
            <a href="/system_prac/modules/requests.php" class="btn">View / Make Requests</a>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>