# Code Lab @ HELP

**Code Lab @ HELP**  
An educational web application designed to support the teaching and learning of computer programming using visual and animated tools.

## Features

- **User Registration (Admin Only):**  
  Admin can register new students and instructors with role-based access control.

- **Secure Login System:**  
  Role-based authentication for Students, Instructors, and Admins.

- **Browse Lessons and Tutorials:**  
  Students can explore categorized lessons in frontend and backend development.

- **Interactive Coding Exercises (Upcoming in Iteration 2):**  
  Visual-based code editors with real-time feedback (to be implemented).

- **Progress Tracking (Upcoming):**  
  Students can monitor learning milestones and completed exercises.

- **Instructor Module (Upcoming):**  
  Instructors can assign exercises and review student performance.

- **Admin Control Panel:**  
  Manage user accounts, permissions, and system configuration.

## Technology Stack

- **Front-end:** HTML, CSS, JavaScript (Static UI)
- **Back-end:** PHP (via XAMPP)
- **Database:** MySQL (via phpMyAdmin)
- **Hosting:** Localhost (XAMPP)

## Installation

1. **Install XAMPP:**  
   Download and install [XAMPP](https://www.apachefriends.org/index.html) to set up a local server.

2. **Clone the Repository:**  
   ```bash
   git clone https://github.com/theedwardwong/Code-Lab-HELP.git

Set Up Database:

Open phpMyAdmin from the XAMPP Control Panel.

Create a new database named codelab_db.

Import the provided codelab_db.sql file from the project’s database folder.

Configure the Project:

Move the project folder into your htdocs directory (inside your XAMPP installation).

Update your PHP configuration file (e.g., config.php) with the correct database credentials.

Start the Server:

Start Apache and MySQL from the XAMPP Control Panel.


---
## Usage
Students: Browse tutorials and track learning progress.
Instructors: (Planned) Assign exercises and provide feedback.
Admin: Manage user registration and system settings.
...

Open your browser and go to:
```markdown
Open your browser and go to:
```bash
http://localhost/Code-Lab-HELP/

Database
The system is powered by a MySQL database named codelab_db.
Ensure that you import the correct schema and check your connection settings in config.php.






