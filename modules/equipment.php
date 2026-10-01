<?php
/**
 * modules/equipment.php
 * Staff-only equipment inventory management.
 * Add, edit, delete equipment.
 */
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /system_prac/index.php');
    exit;
}

// Must be staff
if ($_SESSION['role'] !== 'staff') {
    header('Location: /system_prac/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$errors  = [];
$success = '';

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // =========================
    // ACTION: ADD equipment
    // =========================
    if ($action === 'add') {
        $name       = trim($_POST['name'] ?? '');
        $category   = trim($_POST['category'] ?? '');
        $qty_total  = (int)($_POST['quantity_total'] ?? 0);
        $condition  = $_POST['condition_status'] ?? 'good';

        if ($name === '')                                  $errors[] = 'Equipment name is required.';
        if ($category === '')                              $errors[] = 'Category is required.';
        if ($qty_total <= 0)                               $errors[] = 'Quantity must be greater than 0.';
        if (!in_array($condition, ['good','damaged','maintenance'], true)) $errors[] = 'Invalid condition.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO equipment (name, category, quantity_total, quantity_available, condition_status)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$name, $category, $qty_total, $qty_total, $condition]);
                $success = "Equipment \"$name\" added successfully.";
            } catch (PDOException $e) {
                $errors[] = 'Failed to add: ' . $e->getMessage();
            }
        }
    }

    // =========================
    // ACTION: DELETE equipment
    // =========================
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid equipment ID.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Equipment deleted.";
            } catch (PDOException $e) {
                $errors[] = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

// ---- Fetch all equipment ----
try {
    $equipment_list = $pdo->query("SELECT * FROM equipment ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    die('Error loading equipment: ' . $e->getMessage());
}

$page_title = "Equipment Management";
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between">
    <h1>Equipment Inventory</h1>
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

<!-- ============ ADD FORM ============ -->
<div class="card">
    <h2>Add New Equipment</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label for="name">Equipment Name</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" required>
        </div>

        <div class="form-group">
            <label for="quantity_total">Quantity</label>
            <input type="number" id="quantity_total" name="quantity_total" min="1" required>
        </div>

        <div class="form-group">
            <label for="condition_status">Condition</label>
            <select id="condition_status" name="condition_status">
                <option value="good">Good</option>
                <option value="damaged">Damaged</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>

        <button type="submit" class="btn">Add Equipment</button>
    </form>
</div>

<!-- ============ LIST ============ -->
<div class="card">
    <h2>All Equipment (<?= count($equipment_list) ?>)</h2>

    <?php if (empty($equipment_list)): ?>
        <p style="color:#6b7280;">No equipment yet. Add one above.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Available / Total</th>
                    <th>Condition</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipment_list as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['category']) ?></td>
                        <td><?= (int)$item['quantity_available'] ?> / <?= (int)$item['quantity_total'] ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($item['condition_status']) ?>">
                                <?= htmlspecialchars(ucfirst($item['condition_status'])) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this equipment?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>