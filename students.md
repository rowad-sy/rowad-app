# Students Module - Requirements & Plan

## Overview
نظام متكامل لإدارة الطلاب، الكورسات، الحضور، الشهادات مع دعم الفلترة والفرز وإصدار الشهادات المخصصة.

---

## Phase 1: Core Structure

### 1.1 Database Migrations

#### Students Table (`students`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| student_code | string(20), unique | كود الطالب |
| user_id | FK→users, nullable | ربط بحساب مستخدم |
| first_name_ar | string(100) | |
| last_name_ar | string(100) | |
| first_name_en | string(100), nullable | |
| last_name_en | string(100), nullable | |
| father_name | string(100), nullable | |
| mother_name | string(100), nullable | |
| birth_date | date, nullable | |
| birth_place | string(100), nullable | |
| gender | enum('male','female') | |
| nationality | string(100), nullable | |
| phone | string(50), nullable | |
| email | string(100), nullable | |
| address | text, nullable | |
| center_id | FK→centers, nullable | |
| project_id | FK→projects, nullable | |
| status | enum('active','inactive','graduated','suspended') | |
| enrollment_date | date, nullable | تاريخ التسجيل |
| notes | text, nullable | |
| timestamps, softDeletes | | |

#### Courses Table (`courses`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| project_id | FK→projects | الكورس تابع لمشروع |
| name_ar | string(200) | اسم الكورس (e.g. كورس الحاسوب) |
| name_en | string(200), nullable | |
| description | text, nullable | |
| duration | integer, nullable | المدة (بالأيام أو الأسابيع) |
| sessions_per_year | integer, default: 3 | عدد الدورات في السنة |
| timestamps | | |

#### Course Sessions Table (`course_sessions`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| course_id | FK→courses | |
| name_ar | string(200) | اسم الدورة (e.g. الدورة الأولى 2026) |
| start_date | date | |
| end_date | date | |
| is_active | boolean, default true | |
| timestamps | | |

#### Student Enrollments Table (`student_enrollments`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| student_id | FK→students | |
| course_id | FK→courses | |
| session_id | FK→course_sessions | الدورة الحالية |
| enrollment_date | date | |
| status | enum('enrolled','passed','failed','dropped') | |
| grade | decimal(5,2), nullable | العلامة النهائية |
| is_certificate_eligible | boolean, default false | هل يستحق شهادة |
| timestamps | | |

#### Attendance Table (`attendance`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| student_id | FK→students | |
| date | date | |
| status | enum('present','absent','excused') | |
| note | text, nullable | |
| created_by | FK→users | من سجل الحضور |
| timestamps | | |
| *unique* | [student_id, date] | |

### 1.2 Models
- `App\Models\Admin\Student\Student`
- `App\Models\Admin\Student\Course`
- `App\Models\Admin\Student\CourseSession`
- `App\Models\Admin\Student\StudentEnrollment`
- `App\Models\Admin\Student\Attendance`

### 1.3 Relationships
- Student belongsTo Center, Project, User
- Student hasMany Enrollments, Attendance
- Course belongsTo Project
- Course hasMany Sessions
- Session hasMany Enrollments
- Enrollment belongsTo Student, Course, Session

---

## Phase 2: Student CRUD

### 2.1 Controller + Routes
- `app/Http/Controllers/Admin/Student/StudentController` (full resource)
- Routes under `admin/students/` prefix

### 2.2 Views
- `resources/views/admin/students/index.blade.php` - table with filters/sort
- `resources/views/admin/students/form.blade.php` - create/edit form

### 2.3 Import from Excel
- `app/Imports/StudentsImport.php` - import basic info, map center/project by name
- Import button + modal in index view
- Ignore fields not in DB, log warnings for missing FK references

### 2.4 Filters & Sorting in Index
- Search by name/code/phone
- Filter by: center, project, status, enrollment date range
- Per-page selector (reuse `x-per-page-selector`)
- Sortable columns (click header to sort)

---

## Phase 3: Attendance

### 3.1 Attendance Page
- View: `resources/views/admin/students/attendance/index.blade.php`
- Shows today's date by default
- Date picker to change date
- Table: Student name, code, Present/Absent/Excused radio buttons
- Save attendance button
- Can filter by center/project/course

### 3.2 Controller
- `AttendanceController@index` - show attendance form
- `AttendanceController@store` - save attendance for selected date

### 3.3 Permissions
- `App\Models\Admin\Student\Attendance` with view/create

---

## Phase 4: Courses & Enrollments CRUD

### 4.1 Course Management
- Controller: `CourseController` (resource)
- Views: index, form (with project select, sessions_per_year)
- Sessions inline in course form or separate

### 4.2 Enrollment Management
- Controller: `EnrollmentController` (resource) or inline in student profile
- Enroll students in courses → sessions
- Track pass/fail status and certificate eligibility

---

## Phase 5: Student Profile

### 5.1 Profile Page
- `resources/views/admin/students/profile.blade.php`
- Student info display
- Enrollment history with grades
- Attendance history
- Certificate history
- Link to user account (select existing user or create new)

### 5.2 User Integration
- Student can have a linked User with `type = 'student'`
- When student logs in, redirect to student dashboard
- Student can view their own grades, attendance, certificates

---

## Phase 6: Student Dashboard/Statistics

### 6.1 Statistics Page
- `resources/views/admin/students/statistics.blade.php`
- Total students count
- Students by center/project/course
- Attendance rate charts
- Pass/fail rates
- Filterable by date range, center, project, course

---

## Phase 7: Certificates

### 7.1 Certificate Design System
**Pages:**
1. Select students from table → click "إصدار شهادة"
2. Choose course + session for the certificate
3. Certificate designer page:

**Designer Layout:**
- Canvas A4 landscape (297mm × 210mm) in center
- Right sidebar: draggable fields (student name, code, course, date, certificate #, barcode, etc.)
- Upload template background image
- Drag & drop fields onto canvas
- Each field: position (x, y, width, height), font size, alignment, color
- Barcode field: generates unique secure barcode

**Certificate Number:**
- Format: YYYY-NNNNN (e.g. 2026-00001)
- Auto-increment per year, resets each year

**Save Design:**
- Save to `certificate_designs` table:
  - Template image path
  - Field configurations (JSON: positions, sizes, fonts, etc.)
  - Year + starting certificate number
  - Barcode secret/hash

### 7.2 Certificate Generation
- Generate certificates for selected students × selected course/session
- Each student gets a unique certificate number
- Store in `certificates` table:
  - certificate_number (YYYY-NNNNN)
  - student_id, enrollment_id
  - design_id (FK→certificate_designs)
  - barcode_hash (unique, for verification)
  - issue_date
  - is_verified (boolean, default false)

### 7.3 Certificate Preview & Print
- Preview button opens browser print dialog
- A4 landscape format
- Render using HTML/CSS (pixel perfect)
- No PDF library - pure browser print
- Re-render certificate from saved design data

### 7.4 Certificate Verification
- Barcode scanner → URL like `/verify-certificate/{barcode_hash}`
- Page shows certificate info: valid/invalid, student name, course, date
- Button "إظهار الشهادة كاملة" renders full certificate in browser
- Print button available

### 7.5 Certificate Tables

#### Certificate Designs (`certificate_designs`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string(200) | اسم التصميم |
| course_id | FK→courses, nullable | مرتبط بكورس معين أو عام |
| template_image | string, nullable | مسار صورة القالب |
| fields_config | json | حقول الشهادة (name, type, x, y, w, h, font, size, color) |
| year | integer(4) | السنة (e.g. 2026) |
| start_number | integer | بداية ترقيم الشهادات لهذا التصميم |
| current_number | integer | آخر رقم تم إصداره |
| timestamps | | |

#### Certificates (`certificates`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| certificate_number | string(15) | YYYY-NNNNN |
| design_id | FK→certificate_designs | |
| student_id | FK→students | |
| enrollment_id | FK→student_enrollments | |
| barcode_hash | string(64), unique | للتحقق الآمن |
| issue_date | date | |
| is_verified | boolean, default false | هل تم التحقق من قبل |
| verified_at | datetime, nullable | |
| timestamps | | |

---

## Phase 8: Permissions

Register in permission system:
- `App\Models\Admin\Student\Student` (view, create, edit, delete)
- `App\Models\Admin\Student\Course` (view, create, edit, delete)
- `App\Models\Admin\Student\Attendance` (view, create)
- `App\Models\Admin\Student\Certificate` (view, create)

---

## Notes
- RTL (Arabic-first) UI - same style as HR module
- Bootstrap 5 + custom CSS (same app.css)
- Reuse existing components: `x-per-page-selector`, `x-filter-bar`
- JavaScript for drag-and-drop in certificate designer (vanilla JS or lightweight library)
- Barcode: use a lightweight JS library (JsBarcode) for rendering, hash-based verification
- Certificate design config stored as JSON in DB
- No PDF libraries - browser print only
