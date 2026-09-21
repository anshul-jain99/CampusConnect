# CampusConnect – College Event & Club Management Portal
> 

---

## 1. Project Introduction
**CampusConnect** is a centralized, role-based web application developed to bridge the communication gap between college students, student clubs/societies, and institutional authorities. Built using standard web standards (**HTML5, CSS3, JavaScript, PHP PDO, and MySQL**), it runs locally on the **XAMPP** environment and provides an intuitive, responsive interface for managing events, seat capacities, and student registrations.

---

## 2. Problem Statement
In most collegiate institutions, event notices and workshop announcements are scattered across informal channels such as WhatsApp groups, Telegram channels, Instagram stories, and physical bulletin boards. 

**Key Challenges with the Existing System:**
1. **Missed Deadlines:** Students frequently miss registration windows due to message clutter.
2. **Lack of Centralized Record:** Club organizers track registrations on manual Google Sheets, leading to duplicate entries and overbooking.
3. **No Administrative Oversight:** College administrations lack a verified review process to approve or moderate campus events before they go public.
4. **Poor Accountability:** Paper and spreadsheet-based systems lack unified audit logs and digital attendance passes.

---

## 3. Proposed Solution
CampusConnect provides a unified college web portal where:
- **Students** can browse upcoming approved events, filter by academic/extracurricular category, register in one click, and track their digital event passes.
- **Club Organizers** submit proposed workshops and hackathons, which enter a **`Pending`** queue until verified by authorities.
- **College Administrators** moderate event submissions, view campus-wide analytics via charts, manage active societies, and monitor all student registrations in real time.

---

## 4. Key Features by User Role

### A. Student Module
- **Self Registration & Secure Login:** Account creation with student department, phone, and enrollment number.
- **Browse & Search:** Interactive directory with category filter pills (Technical, Cultural, Sports, Workshop, Competition, Seminar) and real-time JavaScript search.
- **Detailed Event View:** View schedule, venue, club mission, organizer contact, and remaining seats.
- **One-Click Event Registration:** Validates login status, verifies seat availability, checks registration deadlines, and prevents duplicate signups.
- **Digital Pass & Cancellation:** View ticket reference codes (`#CC-REG-XXXX`) and cancel registrations to free up seats.
- **Profile Management:** Update academic details and change passwords.

### B. Club / Organizer Module
- **Organizer Workspace:** Metrics tracking total club events, total attendees registered, and pending approvals.
- **Create Event:** Submit new events with dates, times, venues, capacities, and deadlines. Automatically defaults to **`Pending`** status.
- **Manage Events:** Edit event agendas and schedules; delete events if canceled.
- **Participant Attendance Roster:** View full student roster (Name, Enrollment No, Email, Department, Phone) with a one-click **Print / Export** layout.

### C. Administrator Module
- **Centralized Metrics:** 5 real-time KPI cards (Total Students, Active Clubs, Total Events, Pending Approvals, Total Registrations).
- **Interactive Visualizations:** HTML5 Canvas bar chart displaying event distribution across categories.
- **Event Moderation:** 1-click **Approve** (publishes immediately to student feed) or **Reject** submissions.
- **User Management:** Promote students to club organizers, revert roles, or remove accounts.
- **Club Directory:** Register recognized college societies and assign faculty/student leads.
- **Master Registration Audit Log:** Global filterable log of every ticket booked across the college.

---

## 5. Technology Stack
* **Frontend:** HTML5 (Semantic elements), CSS3 (Custom Responsive Flexbox & Grid, CSS variables), JavaScript (DOM manipulation, canvas charts, live filtering).
* **Backend:** PHP 7.4 / 8.x using **PHP Data Objects (PDO)** with prepared statements to help prevent SQL injection
* **Database:** MySQL / MariaDB (Relational schema with primary and foreign key constraints, `ON DELETE CASCADE`).
* **Environment:** XAMPP (Apache Web Server + MySQL / phpMyAdmin).
* **Development Tool:** Visual Studio Code.

---

## 6. Database Structure (`campusconnect.sql`)

```
users (id, name, email, password, role, department, phone, enrollment_no, created_at)
  │
  ├──< clubs (id, club_name, description, category, organizer_id, created_at)
  │      │
  │      └──< events (id, club_id, title, description, category, event_date, event_time, 
  │             │     venue, max_participants, registration_deadline, status, created_at)
  │             │
  └─────────────┴──< registrations (id, event_id, student_id, registration_date, status)
```

- **Unique Constraints:** Composite unique key `(event_id, student_id)` on `registrations` to mathematically prevent duplicate registrations.
- **Security:** Passwords are securely hashed using PHP's `password_hash()` and verified with `password_verify()`.

---

## 7. Project Folder Structure

```
campusconnect/
├── index.php                      # Public Landing Page (Hero, Featured Events, How it Works)
├── events.php                     # Event Catalog with Search & Category Filters
├── event_details.php              # Event Details & Registration Engine
├── login.php                      # Unified Multi-role Login (with 1-click demo autofill)
├── register.php                   # Student Self-Registration Form
├── logout.php                     # Session Cleanup & Safe Redirection
│
├── student/
│   ├── student_dashboard.php      # Student Workspace & Upcoming Registered Events
│   ├── my_registrations.php       # Digital Event Passes & Cancellation
│   └── profile.php                # Student Profile & Account Details
│
├── organizer/
│   ├── organizer_dashboard.php    # Organizer Metrics & Pending Alerts
│   ├── create_event.php           # Event Submission Form (Pending Approval)
│   ├── manage_events.php          # Organizer Event Management & CRUD
│   ├── edit_event.php             # Event Edit Screen
│   └── participants.php           # Event Attendee Roster & Printable Sheet
│
├── admin/
│   ├── admin_dashboard.php        # System Overview & Canvas Category Chart
│   ├── manage_events.php          # Event Moderation (Approve, Reject, Delete)
│   ├── manage_users.php           # Student & Organizer Management
│   ├── manage_clubs.php           # Club Creation & Organizer Assignment
│   └── manage_registrations.php   # Global Registration Audit Trail
│
├── includes/
│   ├── db.php                     # PDO MySQL Database Connection
│   ├── auth.php                   # Session Guards, Role Enforcement & Flash Toasts
│   ├── header.php                 # Dynamic Responsive Navbar with Active Links
│   └── footer.php                 # Responsive College Footer
│
├── css/
│   └── style.css                  # Custom Academic Theme (Navy, Indigo, Emerald)
│
├── js/
│   └── script.js                  # Live Search, Auto-dismiss Alerts, Canvas Charts
│
├── database/
│   └── campusconnect.sql          # Full Database Schema & Seed Data
│
└── README.md                      # Project Documentation & Viva Guide
```

---

## 8. Installation & Setup on XAMPP

### Step 1: Place Files in XAMPP
Copy the `campusconnect` project folder into your XAMPP web root directory:
```
C:\xampp\htdocs\campusconnect
```

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click the **Start** button next to **Apache**.
3. Click the **Start** button next to **MySQL**.

### Step 3: Import Database in phpMyAdmin
1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. Click on **New** in the left sidebar and name the database: `campusconnect`.
3. Select the `campusconnect` database and navigate to the **Import** tab.
4. Click **Choose File**, select `C:\xampp\htdocs\campusconnect\database\campusconnect.sql`, and click **Import** (or **Go**).
5. All 4 tables (`users`, `clubs`, `events`, `registrations`) and sample records will be loaded.

### Step 4: Run the Application
Open your browser and navigate to:
```
http://localhost/campusconnect/
```

---

## 9. Demo Login Credentials

For testing and internship viva demonstration, convenient one-click auto-fill buttons are provided on the login page:

| Role | Name | Email | Password |
| :--- | :--- | :--- | :--- |
| **Administrator** | System Administrator | `admin@campus.edu` | `admin123` |
| **Club Organizer** | Aarav Sharma (Coding Club) | `organizer.tech@campus.edu` | `org123` |
| **Club Organizer** | Sneha Verma (Cultural Society) | `organizer.cult@campus.edu` | `org123` |
| **Student** | Anshul Jain | `anshul@student.edu` | `student123` |
| **Student** | Rahul Sharma | `rahul@student.edu` | `student123` |
| **Student** | Priya Patel | `priya@student.edu` | `student123` |

---




## 10. Future Scope
- **Payment Gateway Integration:** Support for paid workshops and national-level fests via Razorpay / Stripe.
- **Automated QR-Code Passes:** Generate scannable QR tickets on confirmation emails for mobile door check-ins.
- **SMS & Email Notifications:** Automated deadline reminders and approval alerts via Twilio and PHPMailer.
- **Certificate Generation:** Automated generation of participation certificates in PDF upon event completion.
