# Reusable Skills & Procedures

## Skill 1: Create a New Module Page
1. Create file in `modules/<name>.php`.
2. Add `require_once '../config/db.php';`
3. Add session check (redirect if not logged in).
4. Add role check (staff-only if applicable).
5. Include `../includes/header.php` at top and `../includes/footer.php` at bottom.
6. Write PHP query logic using PDO prepared statements.
7. Add HTML form/table below.
8. Test in browser via `http://localhost/system_prac/modules/<name>.php`.

## Skill 2: Add Input Validation (Checklist)
- [ ] Trim all inputs
- [ ] Check for empty fields
- [ ] Validate email format (filter_var)
- [ ] Validate numeric fields (is_numeric, > 0)
- [ ] Check quantity <= quantity_available
- [ ] Show clear error message, preserve form values
- [ ] Sanitize output with htmlspecialchars()

## Skill 3: Write a PDO Prepared Statement
```php
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);