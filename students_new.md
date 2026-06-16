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
| duration | integer, nullable | المدة (بالأيام) |
| timestamps | | |

#### Periods Table (`periods`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| project_id | FK→projects | كل مشروع له دوراته الخاصة (معهد تقني: 3 دورات/سنة) |
| name_ar | string(200) | اسم الدورة (e.g. الدورة الأولى 2026) |
| year | integer(4) | السنة (e.g. 2026) |
| start_date | date | تاريخ بدء الدورة |
| end_date | date | تاريخ انتهاء الدورة |
| is_active | boolean, default true | |
| timestamps, softDeletes | | |

#### Course Period Pivot (`course_period`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| course_id | FK→courses | |
| period_id | FK→periods | |
| timestamps | | |
| *unique* | [course_id, period_id] | |

#### Student Enrollments Table (`student_enrollments`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| student_id | FK→students | |
| course_id | FK→courses | |
| period_id | FK→periods | الدورة الحالية (period) |
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
- `App\Models\Admin\Student\Period`
- `App\Models\Admin\Student\StudentEnrollment`
- `App\Models\Admin\Student\Attendance`

### 1.3 Relationships
- Student belongsTo Center, Project, User
- Student hasMany Enrollments, Attendance
- Course belongsTo Project
- Course belongsToMany Periods (via course_period)
- Period belongsTo Project
- Period belongsToMany Courses (via course_period)
- Period hasMany Enrollments
- Enrollment belongsTo Student, Course, Period

---

## Phase 2: Student CRUD
- `StudentController` (full resource), routes `admin/students/`
- Views: index (with filters/sort), form (create/edit)
- Import from Excel (map center/project by name, ignore extra fields)
- Filters: search, center, project, status, enrollment date, per_page

---

## Phase 3: Attendance
- Page with today's date default, date picker to change
- Student list with Present/Absent/Excused radio buttons
- Filter by center/project/course/period
- `AttendanceController@index`, `AttendanceController@store`
- Permission: `App\Models\Admin\Student\Attendance`

---

## Phase 4: Courses & Periods CRUD
- `CourseController`, `PeriodController` (resource)
- Period belongs to Project (each project defines own periods per year)
- Period has year, start_date, end_date
- Course has project_id, belongsToMany Periods
- Enrollments: student + course + period, track pass/fail

---

## Phase 5: Student Profile
- Profile page: info, enrollments, attendance, certificates
- Link to User with `type = 'student'`

---

## Phase 6: Statistics
- Counts by center/project/course/period
- Attendance rates, pass/fail rates
- Filterable

---

## Phase 7: Certificates
1. Select students → "إصدار شهادة"
2. Choose course + period
3. Designer: drag & drop fields on A4 landscape canvas, upload template image
4. Certificate number: `YYYY-NNNNN` auto-increment per year
5. Barcode: unique hash, verification URL `/verify-certificate/{hash}`
6. Preview & print (browser print, no PDF library)
7. Save design config (JSON: positions, sizes, fonts)
8. Tables: `certificate_designs` (config), `certificates` (issued)

---

## Phase 8: Permissions
- `App\Models\Admin\Student\Student` (view, create, edit, delete)
- `App\Models\Admin\Student\Course` (view, create, edit, delete)
- `App\Models\Admin\Student\Period` (view, create, edit, delete)
- `App\Models\Admin\Student\Attendance` (view, create)
- `App\Models\Admin\Student\Certificate` (view, create)

---

## Notes
- RTL (Arabic-first) UI - same style as HR module
- Bootstrap 5 + custom CSS, reuse `x-per-page-selector`
- Vanilla JS for drag-and-drop certificate designer
- JsBarcode for barcode rendering
- No PDF libraries - browser print only
