# Campus Equipment Borrowing System — Project Knowledge

## 1. Overview
A web-based system that digitizes equipment borrowing for a college department.
Replaces manual logbooks with a searchable, trackable, role-based system.

## 2. Problem
- Students/faculty cannot check equipment availability.
- Staff struggle to track borrowed and returned items.

## 3. Target Users
- Student — view equipment, request borrow
- Faculty — same as student (priority)
- Staff — manage inventory, approve requests, record returns

## 4. Tech Stack
- Frontend: HTML5, CSS3
- Backend: PHP 8+
- Database: MySQL (via XAMPP)
- Server: Apache (XAMPP)
- Version Control: Git + GitHub

## 5. Database Schema
### users
- id (PK), full_name, email (UNIQUE), password (hashed), role ENUM('student','faculty','staff'), created_at

### equipment
- id (PK), name, category, quantity_total, quantity_available, condition_status ENUM('good','damaged','maintenance'), created_at

### borrow_requests
- id (PK), user_id (FK), equipment_id (FK), quantity, purpose, date_needed,
  status ENUM('pending','approved','rejected','returned'),
  requested_at, approved_by (FK NULL), returned_at (NULL)

## 6. Folder Structure
(see README.md)

## 7. Core Features
1. Login (role-based)
2. Equipment inventory management (staff)
3. Availability viewer (student/faculty)
4. Borrow request submission
5. Approve/reject requests (staff)
6. Return tracking
7. Borrowing history log

## 8. Business Rules
- Cannot borrow more than `quantity_available`.
- Only staff can add/edit equipment.
- Only staff can approve/reject.
- On approval → `quantity_available` decreases.
- On return → `quantity_available` increases.
- Passwords must be hashed (password_hash).