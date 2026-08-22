# HR Module Summary

## Excel Import/Export

### Libraries
- **maatwebsite/excel** v3.1.69 (Laravel-native, Arabic/UTF-8 support, reusable base classes)

### Base Classes
- `app/Exports/BaseExport.php` - base export with `map()` that handles closures in `$columns`
- `app/Exports/EmployeeExport.php` - simple 16-column export with Arabic headings
- `app/Imports/EmployeeImport.php` - simple single-sheet import (Arabic headings, extends BaseImport)
- `app/Imports/BaseImport.php` - base import with `map()` and `afterCreate` closures

### Multi-Sheet Export (`app/Exports/FullExport/`)
- `EmployeeFullExport.php` - main class (WithMultipleSheets)
- `Sheets/BaseSheetExport.php` - common boilerplate (FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle)
- **7 sheets:** EmployeesSheet, EducationsSheet, ContactsSheet, SchedulesSheet, ContractsSheet, SalariesSheet, WarningsSheet
- Employees sheet exports all 48 fields including `has_*` booleans and FK names (center_name, dept_name_ar, project_name, email)
- Full export ~16 KB for 155 employees across 7 sheets

### Multi-Sheet Import (`app/Imports/FullImport/`)
- `EmployeeFullImport.php` - main class with `$employeeMap[code→id]`
- `Sheets/BaseSheetImport.php` - common boilerplate
- **7 sheets:** EmployeesSheetImport (processed first to build map), EducationsSheetImport, ContactsSheetImport, SchedulesSheetImport, ContractsSheetImport (with job position lookup), SalariesSheetImport, WarningsSheetImport
- Uses `updateOrCreate` by `employee_code`
- FK lookup by ID then by name (center, department, project, user)
- Auto-maps Arabic marital_status/gender/status to English codes

### Controllers & Routes
- `app/Http/Controllers/Admin/ExportController.php` - `employees()`, `employeesFullExport()`, `importEmployees()`, `importEmployeesFull()`
- Routes: `export`, `export-full`, `import`, `import-full` under `admin/hr/employees/`

### Views
- `resources/views/admin/hr/employees/index.blade.php` - import/export buttons + modals (both simple and full)

### Import Data File
- `project-files/import-form.xlsx` - 154 employees + relations (educations, contacts, contracts, salaries)
- All dates in ISO format (YYYY-mm-dd)
- Gender/marital_status/status in English codes

## Employee Model Features

### Default Work Schedule (`app/Models/Admin/Hr/Employee.php`)
- `booted()` with `created` event auto-creates 7 schedule rows:
  - Saturday: OFF
  - Sunday-Thursday: 08:00-16:00
  - Friday: OFF

### Form (`resources/views/admin/hr/employees/form.blade.php`)
- Schedule fields pre-filled with defaults (08:00-16:00 for work days, OFF for weekend)

## Users CRUD

### Controller (`app/Http/Controllers/Admin/UserController.php`)
- Full CRUD: index, create, store, edit, update, destroy
- `toggleStatus()` for activate/deactivate
- Permission middleware: view, create, edit, delete

### Views
- `resources/views/admin/users/index.blade.php` - list with add/edit/delete/toggle + status filter + per-page
- `resources/views/admin/users/form.blade.php` - create/edit form (name, email, password, type, is_active)

### Routes
- `Route::resource('users', UserController::class)->except(['show'])`
- `POST users/{user}/toggle-status`

## Filtering & Pagination

### Component
- `resources/views/components/per-page-selector.blade.php` - reusable dropdown (10/25/50/100)

### User Filters
- `status` (all/active/inactive)
- `per_page` + search

### Employee Filters
- `center_id` (select with all centers)
- `project_id` (select with all projects)
- `status` (all/active/inactive)
- `per_page` + search

### Controllers
- Both `UserController@index` and `EmployeeController@index` use `->appends()` to preserve filter params across pagination

## Known Issues / Edge Cases
- Duplicate `employee_code` in source data (ODR2306 at rows 136+137) — updateOrCreate handles this by updating
- PSR-4 autoload for `Maatwebsite\Excel\` was lost during `composer dump-autoload` (Windows encoding) — restored manually in `vendor/composer/autoload_psr4.php` and `autoload_static.php`
- `local` filesystem disk root is `storage_path('app/private')` (not `storage/app`)
- Sheet `Sheet1` in `import-form.xlsx` has 83 rows of reference data (unused by import but preserved)
