# MSULI Credentials, Roles, and Permissions

This document lists all default credentials, roles, and permissions configured for the **MSU Language Institute (MSULI)** project. 

---

## 1. Web Application Seed Users

All seeded web application users share the same default password:

* **Default Password:** `password`

Below is the list of pre-configured users, their emails, departments, sections, and associated roles.

| Name | Email | Department / Unit | Section | Role (Spatie / MSULI) |
| :--- | :--- | :--- | :--- | :--- |
| **Executive Director** | `executive.director@msunli.edu` | `AOS` (Admin & Operations Support) | `Operations and Systems` | `executive_director` |
| **Deputy Director** | `deputy.director@msunli.edu` | `AOS` (Admin & Operations Support) | `Operations and Systems` | `deputy_director` |
| **Admin Assistant** | `admin.assistant@msunli.edu` | `AOS` (Admin & Operations Support) | `Operations and Systems` | `admin_assistant` |
| **Secretary** | `secretary@msunli.edu` | `AOS` (Admin & Operations Support) | `Executive and Office Support` | `secretary` |
| **Receptionist** | `receptionist@msunli.edu` | `AOS` (Admin & Operations Support) | `Executive and Office Support` | `receptionist` |
| **Language Expert** | `expert@msunli.edu` | `SNSU` (Special Needs Services Unit) | *Unassigned* | `language_expert` |
| **Part Time Staff** | `parttime@msunli.edu` | `ILASU` (Int. Languages & Area Studies) | `Regional Languages` | `part_time_staff` |
| **ICT Administrator** | `admin@msunli.edu` | `AOS` (Admin & Operations Support) | `Operations and Systems` | `ict_administrator` |
| **Test Client** | `client@example.com` | *None* | *None* | `client` |
| **Mapfumo L** | `mapfumol@staff.msu.ac.zw` | *None* | *None* | `language_expert` |

---

## 2. Roles and Permissions Matrix

The Spatie roles are assigned specific permissions as defined in `RolesAndPermissionsSeeder.php`.

### Permissions Glossary
- **User & Role Management:** `manage users`, `manage roles`
- **System Administration:** `manage system`
- **Client & Inquiry Management:** `manage clients`, `manage inquiries`
- **Service Requests:** `create service requests`, `view service requests`, `manage service requests`
- **Quotations:** `manage quotations`, `view quotations`, `approve quotations`
- **Assignments & Tasks:** `manage assignments`, `view assignments`, `manage tasks`, `view tasks`
- **Procurement:** `manage procurement`, `approve procurement`
- **Office & Communications:** `manage schedule`, `manage correspondence`, `manage executive communications`, `manage administrative documentation`, `manage executive notifications`
- **Reporting:** `view reports`

### Role-specific Permissions Map

1. **`executive_director` & `ict_administrator`**
   - **Permissions:** **All Permissions** (Dynamically loaded via `Permission::all()`)

2. **`deputy_director`**
   - `manage users`
   - `view service requests`
   - `approve quotations`
   - `view assignments`
   - `view reports`
   - `approve procurement`
   - `view quotations`

3. **`admin_assistant`**
   - `manage procurement`
   - `view reports`
   - `manage clients`
   - `manage quotations`
   - `view quotations`

4. **`secretary`**
   - `manage service requests`
   - `view reports`
   - `manage assignments`
   - `view assignments`
   - `view tasks`
   - `view quotations`
   - `manage schedule`
   - `manage correspondence`
   - `manage inquiries`
   - `manage executive communications`
   - `manage administrative documentation`
   - `manage executive notifications`

5. **`receptionist`**
   - `manage clients`
   - `create service requests`
   - `view service requests`

6. **`language_expert` & `part_time_staff`**
   - `view assignments`
   - `view tasks`
   - `manage tasks`

7. **`client`**
   - `create service requests`
   - `view service requests`

8. **`student`**
   - *No permissions assigned by default.*

---

## 3. Remote Server SSH Credentials

The automated deployment script (`deploy.py`) references the following production environment access credentials:

* **Host:** `10.10.9.1`
* **Port:** `22`
* **SSH Username:** `liberty`
* **SSH Password:** `liberty`
* **Application Directory:** `/var/www/msu-language-institute`
* **Server Port (Nginx HTTP):** `8083`
* **Deployment Repository:** `https://github.com/liberty20/MSU-Language-Institute.git`

---

## 4. Database Credentials

Database details from the project `.env` file and database initialization script:

* **Connection:** `mysql`
* **Host:** `127.0.0.1`
* **Port:** `3306`
* **Database Name:** `msunli_db`
* **Username:** `liberty`
* **Password:** `libs@2026`
