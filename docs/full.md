# نظام روّاد - التوثيق الكامل

## نظرة عامة

نظام روّاد هو نظام إدارة متكامل مبني على Laravel، يدير:
- **الموارد البشرية** (الموظفين، العقوبات، العقود، الرواتب، الإجازات، دوام الموظفين)
- **الطلاب** (التسجيل، المقررات، الفترات، الحضور، الشهادات)
- **المشاريع** (المشاريع، المهام، متابعة التنفيذ)
- **الخدمات اللوجستية** (المشتريات، المستودعات، الأصول، الموافقات)
- **الدعم التقني** (التذاكر، المعدات)

---

## تكنولوجيا المشروع

| التقنية | الإصدار / النوع |
|---------|----------------|
| PHP | ^8.2 |
| Laravel | ^12.0 |
| قواعد البيانات | MySQL (MariaDB) |
| الواجهات | Blade + Bootstrap 5 |
| Livewire | v3 مع Flux UI |
| Excel | maatwebsite/excel |
| المصادقة | Laravel Breeze (Livewire Volt) |
| Node | Vite للموارد (JS/CSS) |

### الاعتماديات (composer.json)

```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "laravel/tinker": "^2.10.1",
  "livewire/flux": "^2.0",
  "livewire/volt": "^1.6.7",
  "maatwebsite/excel": "*"
}
```

---

## هيكل قاعدة البيانات (Database Schema)

### 1. المستخدمون والصلاحيات

#### `users`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| name | string | |
| email | string, unique | |
| official_email | string, nullable | بريد رسمي |
| email_verified_at | timestamp, nullable | |
| password | string | |
| is_active | boolean, default true | |
| type | string(20), nullable | `admin`, `employee`, `beneficiary`, `student`, `super-admin` |
| remember_token | string, nullable | |
| timestamps | | |

#### `groups`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| description | text, nullable |
| timestamps | |

#### `group_user` (Pivot)
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| group_id | FK -> groups(id) (cascade) |
| user_id | FK -> users(id) (cascade) |
| unique | (group_id, user_id) |
| timestamps | |

#### `permissions`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| user_id | FK -> users, nullable | لمستخدم محدد |
| group_id | FK -> groups, nullable | لمجموعة |
| model_names | JSON | مصفوفة أسماء الموديلات |
| model_id | bigint, nullable | عنصر محدد (null = الكل) |
| center_id | FK -> centers, nullable | نطاق المركز (null = الكل) |
| project_id | FK -> projects, nullable | نطاق المشروع (null = الكل) |
| can_view | boolean | |
| can_create | boolean | |
| can_edit | boolean | |
| can_delete | boolean | |
| timestamps | | |

### 2. المراكز والمشاريع

#### `centers`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| address | string, nullable |
| phone | string, nullable |
| timestamps | |

#### `projects`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| description | text, nullable |
| timestamps | |

#### `center_project` (Pivot)
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| center_id | FK -> centers (cascade) |
| project_id | FK -> projects (cascade) |
| unique | (center_id, project_id) |
| timestamps | |

### 3. الإدارات

#### `departments`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name_ar | string |
| name_en | string, nullable |
| description | text, nullable |
| is_active | boolean, default true |
| timestamps | |

### 4. الموارد البشرية (HR)

#### `hr_employees`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| user_id | FK -> users, nullable | nullOnDelete |
| employee_code | string(20), unique | كود الموظف |
| status | enum: active, inactive | |
| id_number | string(50), nullable | رقم الهوية |
| first_name_ar, last_name_ar | string(100) | اسم عربي |
| first_name_en, last_name_en | string(100), nullable | اسم إنجليزي |
| father_name_ar, father_name_en | string(100), nullable | |
| mother_name_ar, mother_name_en | string(100), nullable | |
| gender | enum: male, female | |
| marital_status | enum: single, married, divorced, widowed, nullable | |
| children_count | integer, default 0 | |
| birth_date | date, nullable | |
| birth_place | string(100), nullable | |
| nationality | string(100), nullable | |
| center_id | FK -> centers, nullable | |
| department_id | FK -> departments, nullable | |
| project_id | FK -> projects, nullable | |
| has_photo ... has_blacklist_doc | boolean (20 حقل) | وثائق الموظف |
| notes | text, nullable | |
| timestamps | | |
| softDeletes | | |

#### `hr_job_positions`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| title_ar | string |
| title_en | string, nullable |
| description_ar | text, nullable |
| description_en | text, nullable |
| timestamps | |

#### `hr_employee_educations`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| qualification | string(200) |
| specialization | string(200), nullable |
| university | string(200), nullable |
| grade | string(50), nullable |
| graduation_year | year, nullable |
| timestamps | |

#### `hr_employee_contacts`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| type | string(50) |
| value | string(200) |
| is_primary | boolean, default false |
| timestamps | |

#### `hr_work_schedules`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| day_of_week | tinyInteger (0-6) |
| start_time | time, nullable |
| end_time | time, nullable |
| is_day_off | boolean, default false |
| unique | (employee_id, day_of_week) |
| timestamps | |

#### `hr_contracts`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| contract_type | string(50), nullable |
| job_position_id | FK -> hr_job_positions, nullable |
| start_date | date, nullable |
| contract_start | date, nullable |
| contract_end | date, nullable |
| leave_date | date, nullable |
| timestamps | |

#### `hr_salaries`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| employee_id | FK -> hr_employees (cascade) | |
| currency | string(3), default 'SYP' | |
| salary_unit | string(50), nullable | |
| base_salary | decimal(12,2) | الراتب الأساسي |
| study_allowance | decimal(10,2) | علاوة دراسة |
| marriage_allowance | decimal(10,2) | علاوة زواج |
| experience_allowance | decimal(10,2) | علاوة خبرة |
| transport_allowance | decimal(10,2) | علاوة نقل |
| food_allowance | decimal(10,2) | علاوة طعام |
| housing_allowance | decimal(10,2) | علاوة سكن |
| mobile_allowance | decimal(10,2) | علاوة موبايل |
| risk_allowance | decimal(10,2) | علاوة خطورة |
| overtime_rate | decimal(10,2) | أجر إضافي |
| deduction | decimal(10,2) | خصم |
| total_salary | decimal(12,2) | إجمالي الراتب |
| timestamps | | |

#### `hr_employee_documents`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| document_type | string(50) |
| file_path | string(500) |
| original_name | string(255) |
| timestamps | |

#### `hr_warnings`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| date | date |
| reason | text |
| level | enum: verbal, written, termination |
| is_folded | boolean, default false |
| fold_reason | text, nullable |
| folded_at | timestamp, nullable |
| timestamps | |

#### `hr_employee_notes`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| user_id | FK -> users (cascade) |
| note | text |
| timestamps | |

#### `hr_leave_types`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name_ar | string |
| annual_days | integer, default 0 |
| requires_approval | boolean, default true |
| approver_ids | JSON, nullable |
| color | string, default '#0d6efd' |
| icon | string, default 'bi-calendar' |
| is_active | boolean, default true |
| timestamps | |

#### `hr_leave_requests`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| leave_type_id | FK -> hr_leave_types (cascade) |
| start_date | date |
| end_date | date |
| days_count | integer |
| reason | text, nullable |
| status | enum: pending, approved, rejected, cancelled |
| approved_by | FK -> users, nullable |
| approved_at | timestamp, nullable |
| timestamps | |

#### `hr_leave_balances`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| leave_type_id | FK -> hr_leave_types (cascade) |
| year | integer |
| total_days | integer, default 0 |
| used_days | integer, default 0 |
| unique | (employee_id, leave_type_id, year) |
| timestamps | |

#### `hr_employee_attendances`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| employee_id | FK -> hr_employees (cascade) |
| date | date |
| status | enum: present, absent, excused |
| leave_type_id | FK -> hr_leave_types, nullable |
| notes | text, nullable |
| created_by | FK -> users, nullable |
| unique | (employee_id, date) |
| timestamps | |

### 5. الطلاب (Students)

#### `students`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| student_code | string(20), unique | كود الطالب |
| identity_type | string(50), nullable | نوع الهوية |
| identity_number | string(50), nullable | رقم الهوية |
| user_id | FK -> users, nullable | |
| first_name_ar | string(100) | |
| last_name_ar | string(100) | |
| first_name_en | string(100), nullable | |
| last_name_en | string(100), nullable | |
| father_name | string(100), nullable | |
| mother_name | string(100), nullable | |
| birth_date | date, nullable | |
| birth_place | string(100), nullable | |
| gender | enum: male, female | |
| nationality | string(100), nullable | |
| phone | string(50), nullable | |
| email | string(100), nullable | |
| address | text, nullable | |
| center_id | FK -> centers, nullable | |
| project_id | FK -> projects, nullable | المشروع المباشر |
| status | enum: active, inactive, graduated, suspended | |
| enrollment_date | date, nullable | |
| notes | text, nullable | |
| timestamps | | |
| softDeletes | | |
| indexes | status, gender | |

#### `project_student` (Pivot)
| الحقل | النوع |
|-------|------|
| project_id | FK -> projects (cascade) |
| student_id | FK -> students (cascade) |
| deleted_at | timestamp, nullable (softDeletes) |
| PRIMARY KEY | (project_id, student_id) |

#### `courses`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| project_id | FK -> projects (cascade) |
| name_ar | string(200) |
| name_en | string(200), nullable |
| description | text, nullable |
| duration | integer, nullable (أيام) |
| timestamps | |

#### `periods`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| project_id | FK -> projects (cascade) |
| name_ar | string(200) |
| year | year |
| start_date | date |
| end_date | date |
| is_active | boolean, default true |
| timestamps | |
| softDeletes | |

#### `course_period` (Pivot)
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| course_id | FK -> courses (cascade) |
| period_id | FK -> periods (cascade) |
| unique | (course_id, period_id) |
| timestamps | |

#### `student_enrollments`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| student_id | FK -> students (cascade) |
| course_id | FK -> courses (cascade) |
| period_id | FK -> periods (cascade) |
| enrollment_date | date |
| status | enum: enrolled, passed, failed, dropped |
| grade | decimal(5,2), nullable |
| is_certificate_eligible | boolean, default false |
| index | enrollment_date |
| timestamps | |

#### `attendance`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| student_id | FK -> students (cascade) |
| date | date |
| status | enum: present, absent, excused |
| note | text, nullable |
| created_by | FK -> users, nullable |
| unique | (student_id, date) |
| indexes | date, status |
| timestamps | |

#### `certificate_designs`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string(200) |
| course_id | FK -> courses, nullable |
| template_image | string, nullable |
| fields_config | JSON |
| year | integer |
| start_number | integer, default 1 |
| current_number | integer, default 0 |
| timestamps | |

#### `certificates`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| certificate_number | string(15), unique |
| design_id | FK -> certificate_designs, nullable |
| student_id | FK -> students (cascade) |
| enrollment_id | FK -> student_enrollments, nullable |
| barcode_hash | string(64), unique |
| issue_date | date |
| is_verified | boolean, default false |
| verified_at | timestamp, nullable |
| timestamps | |

#### `certificate_number_sequence`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| year | integer, unique |
| last_number | bigint, default 0 |
| timestamps | |

### 6. إدارة المشاريع (Project Tasks)

#### `project_tasks`
| الحقل | النوع | الملاحظات |
|-------|------|-----------|
| id | bigint, PK | |
| title | string | |
| purpose | text, nullable | الغرض من المهمة |
| start_date | date | |
| end_date | date | |
| needs_media_coverage | boolean | |
| needs_costs | boolean | |
| costs_details | text, nullable | |
| needs_equipment | boolean | |
| equipment_details | text, nullable | |
| assigned_to | FK -> users | المسؤول |
| created_by | FK -> users | المنشئ |
| center_id | FK -> centers, nullable | |
| status | string, default 'pending' | |
| executed | boolean, nullable | تم التنفيذ؟ |
| not_executed_reason | text, nullable | |
| has_delay | boolean, nullable | |
| delay_reason | text, nullable | |
| media_coverage_done | boolean, nullable | |
| no_media_coverage_reason | text, nullable | |
| execution_notes | text, nullable | |
| timestamps | | |
| softDeletes | | |

### 7. الخدمات اللوجستية (Logistics)

#### `logistics_settings`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| key | string, unique |
| value | text, nullable |
| timestamps | |

#### `logistics_approval_rules`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| min_amount | decimal(12,2), default 0 |
| max_amount | decimal(12,2), nullable |
| required_approvals | integer, default 1 |
| notes | text, nullable |
| timestamps | |

#### `logistics_approval_rule_user` (Pivot)
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| rule_id | FK -> logistics_approval_rules (cascade) |
| user_id | FK -> users (cascade) |
| timestamps | |

#### `logistics_purchase_requests`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| request_number | string, unique |
| user_id | FK -> users |
| specifications | text, nullable |
| quantity | integer, nullable |
| unit | string, nullable |
| expected_unit_price | decimal(12,2), nullable |
| expected_total_price | decimal(12,2) |
| center_id | FK -> centers, nullable |
| project_id | FK -> projects, nullable |
| status | string, default 'pending' |
| notes | text, nullable |
| signature_path | string, nullable |
| timestamps | |
| softDeletes | |

#### `logistics_purchase_request_items`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| purchase_request_id | FK -> logistics_purchase_requests (cascade) |
| description | text |
| quantity | integer |
| unit | string |
| unit_price | decimal(12,2) |
| total_price | decimal(12,2) |
| notes | text, nullable |
| timestamps | |

#### `logistics_purchase_request_approvals`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| purchase_request_id | FK -> logistics_purchase_requests (cascade) |
| user_id | FK -> users |
| status | string, default 'pending' |
| notes | text, nullable |
| decided_at | timestamp, nullable |
| timestamps | |

#### `logistics_warehouses`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| center_id | FK -> centers |
| notes | text, nullable |
| timestamps | |
| softDeletes | |

#### `logistics_warehouse_items`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| warehouse_id | FK -> logistics_warehouses (cascade) |
| name | string |
| description | text, nullable |
| quantity | integer, default 0 |
| unit | string |
| status | string, default 'active' |
| timestamps | |

#### `logistics_deleted_items`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| warehouse_id | FK -> logistics_warehouses, nullable |
| item_name | string |
| description | text, nullable |
| quantity | integer |
| unit | string |
| delete_reason | string |
| timestamps | |

#### `logistics_assets`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| asset_code | string, unique |
| name | string |
| type | string |
| center_id | FK -> centers |
| project_id | FK -> projects, nullable |
| room_number | string, nullable |
| status | string |
| notes | text, nullable |
| recipient_id | FK -> users, nullable |
| timestamps | |
| softDeletes | |

### 8. الدعم التقني (Tech Support)

#### `tech_issues`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| title | string |
| description | text |
| center_id | FK -> centers (cascade) |
| project_id | FK -> projects, nullable |
| reported_by | FK -> users (cascade) |
| assigned_to | FK -> users, nullable |
| status | enum: open, in_progress, completed, blocked |
| priority | enum: low, medium, high, urgent |
| admin_response | text, nullable |
| resolved_at | timestamp, nullable |
| indexes | status, priority, center_id |
| timestamps | |

#### `tech_equipment`
| الحقل | النوع |
|-------|------|
| id | bigint, PK |
| name | string |
| type | string, nullable |
| serial_number | string, nullable |
| condition | enum: a, b, c, d, e |
| room | string, nullable |
| center_id | FK -> centers (cascade) |
| project_id | FK -> projects, nullable |
| notes | text, nullable |
| indexes | type, condition |
| timestamps | |

---

## الموديلات والعلاقات (Models & Relationships)

### User (`app\Models\User`)
- `groups()` -> BelongsToMany(Group)
- `permissions()` -> HasMany(Permission)
- `student()` -> HasOne(Student)
- `employee()` -> HasOne(Employee)

### Permission (`App\Models\Admin\Permission`)
- `user()` -> BelongsTo(User)
- `group()` -> BelongsTo(Group)
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)

### Center (`App\Models\Admin\Center`)
- `projects()` -> BelongsToMany(Project, 'center_project')

### Project (`App\Models\Admin\Project`)
- `centers()` -> BelongsToMany(Center, 'center_project')

### Employee (`App\Models\Admin\Hr\Employee`) — SoftDeletes
- `user()` -> BelongsTo(User)
- `center()` -> BelongsTo(Center)
- `department()` -> BelongsTo(Department)
- `project()` -> BelongsTo(Project)
- `educations()` -> HasMany(EmployeeEducation)
- `contacts()` -> HasMany(EmployeeContact)
- `workSchedules()` -> HasMany(WorkSchedule)
- `contracts()` -> HasMany(Contract)
- `salaries()` -> HasMany(Salary)
- `documents()` -> HasMany(EmployeeDocument)
- `warnings()` -> HasMany(Warning)
- `notesRelation()` -> HasMany(EmployeeNote)
- `leaveRequests()` -> HasMany(LeaveRequest)
- `leaveBalances()` -> HasMany(LeaveBalance)
- `attendances()` -> HasMany(EmployeeAttendance)
- booted: creates default work schedule (Sun-Thu 08:00-16:00, Fri-Sat off)

### Student (`App\Models\Admin\Student\Student`) — SoftDeletes
- `user()` -> BelongsTo(User)
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project) — direct project
- `projects()` -> BelongsToMany(Project, 'project_student') — **مع فلتر `whereNull('project_student.deleted_at')`**
- `allProjects()` -> BelongsToMany(Project, 'project_student') — بدون فلتر (لإدارة المحذوفين)
- `enrollments()` -> HasMany(StudentEnrollment)
- `attendance()` -> HasMany(Attendance)
- `certificates()` -> HasMany(Certificate)

### ProjectTask (`App\Models\Admin\ProjectTask`) — SoftDeletes
- `assignedTo()` -> BelongsTo(User, 'assigned_to')
- `createdBy()` -> BelongsTo(User, 'created_by')
- `center()` -> BelongsTo(Center)

### PurchaseRequest (`App\Models\Admin\Logistics\PurchaseRequest`) — SoftDeletes
- `user()` -> BelongsTo(User)
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)
- `approvals()` -> HasMany(PurchaseRequestApproval)
- `items()` -> HasMany(PurchaseRequestItem)
- `getTotalPriceAttribute()` : float
- `getFormattedTotalAttribute()` : string

### Warehouse (`App\Models\Admin\Logistics\Warehouse`) — SoftDeletes
- `center()` -> BelongsTo(Center)
- `items()` -> HasMany(WarehouseItem)

### Asset (`App\Models\Admin\Logistics\Asset`) — SoftDeletes
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)
- `recipient()` -> BelongsTo(User, 'recipient_id')

### TechIssue
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)
- `reporter()` -> BelongsTo(User, 'reported_by')
- `assignee()` -> BelongsTo(User, 'assigned_to')

### TechEquipment
- `center()` -> BelongsTo(Center)
- `project()` -> BelongsTo(Project)

---

## المسارات (Routes) — كاملة

### المجموعة الرئيسية: `/admin` باسم `admin.`

#### الرئيسية
| الطريقة | المسار | الوظيفة |
|---------|--------|---------|
| GET | / | HomeController@index |
| GET | /dashboard | DashboardController@index |

#### المستخدمون
| GET/POST/PUT/DELETE | /users (resource) | UserController (except show) |
| POST | /users/{user}/toggle-status | UserController@toggleStatus |

#### المركز والمشاريع
| GET/POST/PUT/DELETE | /centers (resource) | CenterController (except show) |
| GET/POST/PUT/DELETE | /projects (resource) | ProjectController (except show) |
| GET | /projects/tasks | ProjectTaskController@index |
| GET | /projects/tasks/create | ProjectTaskController@create |
| POST | /projects/tasks | ProjectTaskController@store |
| GET | /projects/tasks/{task} | ProjectTaskController@show |
| GET/PUT | /projects/tasks/{task}/edit | ProjectTaskController@edit, update |
| DELETE | /projects/tasks/{task} | ProjectTaskController@destroy |
| POST | /projects/tasks/{task}/update-status | ProjectTaskController@updateStatus |
| GET | /projects/calendar | ProjectTaskController@calendar |
| GET | /projects/statistics | ProjectTaskController@statistics |

#### الإدارات والمجموعات والصلاحيات
| GET/POST/PUT/DELETE | /departments (resource) | DepartmentController (except show) |
| GET/POST/PUT/DELETE | /groups (resource) | GroupController (except show) |
| GET/POST/PUT/DELETE | /permissions (resource) | PermissionController (except show) |

#### الملف الشخصي
| GET | /profile | ProfileController@index |
| PUT | /profile | ProfileController@update |
| PUT | /profile/password | ProfileController@password |

#### الطلاب
| GET/POST | /students/attendance | AttendanceController |
| GET/POST/PUT/DELETE | /students/courses (resource) | CourseController |
| GET/POST/PUT/DELETE | /students/periods (resource) | PeriodController |
| GET | /students/statistics | StudentStatisticsController@index |
| GET/POST/PUT/DELETE | /students/certificates/* (10 routes) | CertificateController |
| POST | /students/check-identity | StudentController@checkIdentity |
| POST | /students/{student}/add-to-projects | StudentController@addToProjects |
| GET/POST/PUT/DELETE | /students (resource) | StudentController |
| POST | /students/{student}/create-user | StudentController@createUser |
| POST | /students/export | StudentExportController@export |
| POST | /students/export-full | StudentExportController@exportFull |
| POST | /students/import | StudentExportController@import |
| POST | /students/import-full | StudentExportController@importFull |

#### الموارد البشرية
| GET/POST/PUT/DELETE | /hr/employees (resource) | EmployeeController |
| GET | /hr/employees/statistics | EmployeeStatisticsController@index |
| GET/POST/PUT/DELETE | /hr/job-positions (resource) | JobPositionController |
| POST | /hr/employees/export | ExportController@employees |
| POST | /hr/employees/export-full | ExportController@employeesFullExport |
| POST | /hr/employees/import | ExportController@importEmployees |
| POST | /hr/employees/import-full | ExportController@importEmployeesFull |
| GET/POST | /hr/leave-requests (index, create, store, destroy) | LeaveRequestController |
| GET/POST | /hr/leave-approvals | LeaveApprovalController |
| GET/POST/DELETE | /hr/leave-policies | LeavePolicyController |
| GET/POST | /hr/attendances | HrAttendanceController |
| GET | /hr/timesheets | TimesheetController |
| GET | /hr/timesheets/print | TimesheetController@print |

#### التقنية
| GET/POST/PUT/DELETE | /tech/issues (resource) | TechIssueController |
| POST | /tech/issues/{issue}/respond | TechIssueController@respond |
| GET/POST/PUT/DELETE | /tech/equipment (resource) | TechEquipmentController |
| GET | /tech/statistics | TechStatisticsController@index |
| GET | /tech/emails | TechController@emails |

#### اللوجستي
| GET | /logistics/statistics | LogisticsStatisticsController@index |
| GET/POST | /logistics/settings | SettingsController |
| GET/POST/PUT/DELETE | /logistics/approval-rules (resource) | ApprovalRuleController |
| GET/POST | /logistics/purchase-requests (index, create, store, show, destroy) | PurchaseRequestController |
| POST | /logistics/purchase-requests/{pr}/approve | PurchaseRequestApprovalController |
| POST | /logistics/purchase-requests/{pr}/reject | PurchaseRequestApprovalController |
| GET/POST/PUT/DELETE | /logistics/warehouses (resource) | WarehouseController |
| GET/POST | /logistics/warehouses/{wh}/items/* | WarehouseItemController |
| GET | /logistics/deleted-items | WarehouseItemController@deleted |
| GET/POST/PUT/DELETE | /logistics/assets (resource) | AssetController |
| GET | /logistics/export/* (3 exports) | LogisticsExportController |
| POST | /logistics/import/* (3 imports) | LogisticsExportController |

### التحقق العام
| GET | /verify-certificate/{hash} | CertificateController@verifyCertificate |

---

## نظام الصلاحيات

### المكونات
1. **`CheckPermission` middleware** — يتحقق من الصلاحية عند الوصول إلى أي route
2. **`PermissionHelper`** — كلاس المسؤول عن منطق التحقق من الصلاحيات
3. **`PermissionController`** — إدارة الصلاحيات (CRUD) من لوحة التحكم
4. **`@canPermission`** — Blade directive لإظهار/إخفاء العناصر في الواجهات

### كيف يعمل `PermissionHelper::can()`
1. المستخدمون من نوع `super-admin` يمررون تلقائيًا
2. يتم جلب صلاحيات المستخدم المباشرة + صلاحيات المجموعات التي ينتمي إليها
3. يتم البحث باستخدام `whereJsonContains('model_names', $modelName)`
4. لكل صلاحية يتم التحقق من:
   - **model_id**: إذا كان محددًا في الصلاحية والطلب، يجب أن يتطابق
   - **center_id**: إذا كان محددًا في الصلاحية، يجب أن يتطابق مع نطاق الطلب
   - **project_id**: إذا كان محددًا في الصلاحية، يجب أن يتطابق مع نطاق الطلب
   - **can_view / can_create / can_edit / can_delete**: يجب أن تكون true

### تطبيق النطاق (Scoping)
- الميدل وير `CheckPermission` يمرر `center_id` و `project_id` من سجل الموظف (`Employee`)
- إذا ما عند المستخدم سجل موظف، يمرر `null` (بدون نطاق) → تفحص فقط وجود الصلاحية
- الكونترولرات تفلتر البيانات حسب `center_id` و `project_id` من سجل الموظف (أو من طلب المستخدم)

### الموديلات المدعومة في الصلاحيات

| المجموعة | الموديلات |
|----------|-----------|
| الإدارة | Center, Project, Department, User, Group, Permission |
| الموارد البشرية | Employee, JobPosition, Warning, LeaveType, LeaveRequest, EmployeeAttendance |
| الطلاب | Student, Course, Period, StudentEnrollment, Attendance, Certificate, CertificateDesign |
| التقنية | TechIssue, TechEquipment |
| اللوجستي | PurchaseRequest, ApprovalRule, Warehouse, Asset, LogisticsSetting |
| إدارة المشاريع | ProjectTask |
| الصفحات | page:admin.logistics.statistics |

---

## التصدير والاستيراد (Exports & Imports)

### الطلاب
| الملف | النوع | الوصف |
|-------|------|-------|
| StudentExport | تصدير بسيط | قائمة الطلاب (22 عمود) |
| StudentFullExport | تصدير كامل | 3 شيتات: students, enrollments, certificates |
| StudentImport | استيراد بسيط | بحث بـ student_code → تحديث أو إنشاء |
| StudentFullImport | استيراد كامل | 3 شيتات مع studentMap |

### الموظفين
| الملف | النوع | الوصف |
|-------|------|-------|
| EmployeeExport | تصدير بسيط | قائمة الموظفين |
| EmployeeFullExport | تصدير كامل | 7 شيتات: employees, educations, contacts, schedules, contracts, salaries, warnings |
| EmployeeImport | استيراد بسيط | |
| EmployeeFullImport | استيراد كامل | 7 شيتات |

### اللوجستي
| الملف | النوع |
|-------|------|
| AssetExport / AssetImport | الأصول |
| PurchaseRequestExport / Import | طلبات الشراء |
| WarehouseExport / Import | المستودعات |

### Base Classes
- `BaseExport` — كلاس أساسي للتصدير (iterable callback-based columns)
- `BaseImport` — كلاس أساسي للاستيراد
- `BaseSheetExport` / `BaseSheetImport` — للتصدير/الاستيراد متعدد الشيتات

---

## الميدل وير

| الميدل وير | المسار | الوظيفة |
|-----------|--------|---------|
| `CheckPermission` | `app/Http/Middleware/CheckPermission.php` | التحقق من صلاحيات المستخدم للـ routes |
| `auth` | Laravel default | المصادقة |
| `verified` | Laravel default | التحقق من البريد الإلكتروني |

---

## الهيلبرز

| الملف | الوظيفة |
|-------|---------|
| `app/Helpers/PermissionHelper.php` | نظام الصلاحيات المتقدم |

---

## الـ Providers

| الملف | الوظيفة |
|-------|---------|
| `AppServiceProvider.php` | تسجيل Blade directive `@canPermission`، تفعيل Bootstrap 5 Paginator |
| `VoltServiceProvider.php` | تسجيل مكونات Livewire Volt |

---

## الملفات الإضافية

### `bootstrap/app.php`
- تسجيل alias `'permission'` لميدل وير `CheckPermission`

### `routes/web.php`
- جميع مسارات النظام (190 سطر)
- المصادقة عبر Laravel Breeze

### `routes/auth.php`
- مسارات المصادقة (auto-generated by Breeze/Volt)

---

## الـ Livewire Components

(موجودة في `app/Livewire/` و `resources/views/livewire/`)

| المكون | الوظيفة |
|--------|---------|
| `Actions/Logout.php` | تسجيل الخروج |
| `auth/*` | المصادقة (login, register, forgot-password, reset-password, verify-email, confirm-password, choose) |
| `settings/*` | إعدادات الملف الشخصي (profile, password, appearance, delete-user) |

---

## الميزات الرئيسية

### الموارد البشرية
- إدارة الموظفين (إضافة، تعديل، حذف، عرض)
- إدارة الملفات الشخصية (البيانات الشخصية، التعليم، جهات الاتصال، جداول العمل، العقود، الرواتب)
- إدارة الوثائق (رفع ملفات لكل موظف)
- إدارة العقوبات (لفظي، كتابي، إنهاء)
- إدارة الإجازات (أنواع الإجازات، أرصدة، طلبات، موافقات)
- دوام الموظفين (تسجيل الحضور والغياب)
- طباعة التايم شيت
- إحصائيات الموظفين

### الطلاب
- إدارة الطلاب (إضافة، تعديل، حذف، عرض)
- البحث عن الطلاب برقم الهوية
- إضافة الطلاب لمشاريع متعددة (علاقة many-to-many مع soft delete)
- إدارة المقررات الدراسية
- إدارة الفترات الدراسية
- تسجيل الطلاب في المقررات (تسجيل متعدد)
- تسجيل الحضور والغياب
- إدارة الشهادات (تصاميم، إصدار، طباعة دفعة، توثيق)
- إحصائيات الطلاب
- استيراد/تصدير Excel (بسيط وكامل)
- إنشاء حساب مستخدم للطالب

### إدارة المشاريع
- إدارة المهام (إضافة، تعديل، حذف، عرض)
- متابعة تنفيذ المهام (تاريخ البداية، النهاية، التغطية الإعلامية، التكاليف، المعدات)
- لوحة Calendar/Kanban
- إحصائيات المهام

### اللوجستي
- طلبات الشراء مع بنود متعددة وتوقيع
- قواعد الموافقات (حسب المبلغ)
- الموافقات المتعددة على طلب الشراء
- إدارة المستودعات (العناصر، الحذف مع تتبع)
- إدارة الأصول (الكود، النوع، الحالة، المستلم)
- الإعدادات (key-value)
- استيراد/تصدير Excel
- إحصائيات اللوجستي

### التقنية
- تذاكر الدعم (إضافة، تعديل، حذف، ردود)
- إدارة المعدات التقنية (النوع، الحالة، الرقم المسلسل)
- إحصائيات تقنية

---

## سجل التعديلات الرئيسية

### 2026-06-29: إضافة حقول الهوية للطلاب
- `identity_type`, `identity_number` إلى جدول students
- تحديث الموديل، الكونترولر، الفورم، البروفايل، التصدير، الاستيراد

### 2026-06-29: تعديل منطق استيراد الطلاب (منع التكرار)
- `StudentImport` يعيد كتابة ليستخدم `first()` ثم تحديث أو إنشاء

### 2026-06-29: منع تكرار التسجيلات في الاستيراد الكامل
- إضافة `exists` check قبل إنشاء التسجيل

### 2026-06-29: إدارة التسجيلات في فورم الطالب
- جدول ديناميكي في الفورم مع إضافة/حذف صفوف

### 2026-06-29: استيراد/تصدير Excel للطلاب
- StudentExport, StudentFullExport, StudentImport, StudentFullImport

### 2026-07-06: Soft Delete على جدول وسيط project_student
- إضافة `deleted_at` لجدول `project_student`
- تعديل `projects()` في Student model لفلترة المحذوفين
- تعديل `destroy()` في StudentController: soft-delete pivot بدلاً من حذف الطالب
- تعديل `addToProjects()`: إعادة تفعيل الوسيط المحذوف بدلاً من تكراره

### 2026-07-06: إصلاح نطاق الصلاحيات في الميدل وير
- تعديل `CheckPermission` ليمرر `center_id` و `project_id` من سجل الموظف إلى `PermissionHelper::can()`



