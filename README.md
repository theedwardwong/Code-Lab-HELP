# HELP EventVision System

** HELP EventVision System**

## Features

- **Register Event Organisers:** 
- **Event Creation:**
- **Ticket Setup:** 
- **Ticket Booking and Seat Management:** 
- **Payment:** 
- **Manage Waitlist:**
- **Analytics Reports**

## Technology Stack

- **Front-end:** HTML, CSS, JavaScript
- **Back-end:** PHP
- **Database:** MySQL (via phpMyAdmin)
- **Hosting:** Localhost (XAMPP)

## Installation

1. **Install XAMPP:**
   Download and install [XAMPP](https://www.apachefriends.org/index.html) to set up a local server.

2. **Clone the Repository:**
   ```bash
      git clone https://github.com/theedwardwong/EventVision-System.git
   
Set Up Database:

Open phpMyAdmin from XAMPP control panel.
Create a new database called ecodatabase.
Import the provided SQL file (EVSdatabase.sql) into the new database.
Configure the Project:

Move the project folder into the htdocs directory in your XAMPP installation.
Ensure the database connection details are correct in the PHP configuration file (e.g., config.php).
Start the Server:

Open the XAMPP control panel and start Apache and MySQL.

Navigate to http://localhost/EventVision-System/ in your browser to view the project.

Usage
Dashboard: Navigate to the dashboard to access all features.

Database
The system is powered by a MySQL database named EVSdatabase. Ensure that you import the correct database schema from the EVSdatabase.sql file, and configure the database connection in your PHP code.
