# AttendanceViaLocation

AttendanceViaLocation is a web application for managing employee attendance with location verification, QR code scanning, and selfie capture.

## 📋 Features
- 🔐 **User Login**: Secure login for employees using credentials provided by the admin.
- 📆 **Attendance Marking**: Employees can mark their attendance with 'in' and 'out' options.
- 🌍 **Location-Based Attendance**: Ensures employees are at the correct location when marking attendance.
- 📱 **QR Code Scanning**: For additional verification of in-office attendance.
- 🤳 **Selfie Capture**: Automatic selfie capture for both in-office and outdoor attendance.
- 🛠️ **Admin Panel**: Allows admins to manage users and view employee statistics.
- 📊 **Export Attendance Data to XLSX**: Admins can export attendance records.
- 🗑️ **Deletion of Old Selfies**: Automatically deletes selfies older than 2 days.

## 🚀 Installation

### Using XAMPP and phpMyAdmin

1. **📥 Download and Install XAMPP**:
   - Download XAMPP from [apachefriends.org](https://www.apachefriends.org/index.html).
   - Install XAMPP and start Apache and MySQL from the XAMPP Control Panel.

2. **💾 Set Up Database**:
   - Open phpMyAdmin by navigating to `http://localhost/phpmyadmin/`.
   - Create a new database named `attendance_system`.

3. **📂 Clone the Repository**:
   - Open a terminal and navigate to the `htdocs` directory of your XAMPP installation.
   - Clone the repository:
     ```bash
     git clone https://github.com/Muzaffar206/atten.git
     ```

4. **🔧 Configure Environment**:
   - Navigate to the cloned directory and create a `.env` file from the sample:
     ```bash
     cp .env.example .env
     ```
   - Edit the `.env` file to set your database credentials.

5. **📦 Install Dependencies**:
   - Install PHP dependencies using Composer:
     ```bash
     composer install
     ```

6. **📜 Run Database Migrations**:
   - Import the database schema:
     ```bash
     php artisan migrate
     ```

7. **🚀 Start the Development Server**:
   - If you want to use PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Or access via `http://localhost/atten` if using XAMPP's Apache server.

### Manual Installation

1. **📂 Download Source Code**:
   - Download the source code from the GitHub repository.

2. **💾 Extract Files**:
   - Extract the files to the `htdocs` directory of your XAMPP installation

3. **💾 Set Up Database**:
   - Open phpMyAdmin (or your preferred database management tool).
   - Create a new database named `attendance_system`.

3. **🌍 Set Up Database**:
   - Open any browser and search url (`http://localhost/atten`).

## 📝 Usage

### Employee Guide
1. 🔑 Log in using credentials provided by the admin.
2. 📅 Navigate to the attendance page.
3. 🟢 Select 'in' or 'out' and follow the prompts for QR code scanning and selfie capture.

### Admin Guide
### Admin credential
    username:Admin
    password:Admin
1. 🔐 Log in to the admin panel.
2. 👥 Manage users, view statistics, and export data.
3. 📂 Navigate to the appropriate sections for user management and attendance reports.

## 🤝 Contributing
1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit your changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Open a Pull Request.

## 🛡️ Security
For security issues, please email [shaikhmuzaffar206@gmail.com](mailto:shaikhmuzaffar206@gmail.com).

## 📞 Contact
For support or further questions, contact [shaikhmuzaffar206@gmail.com](mailto:shaikhmuzaffar206@gmail.com).


composer require maennchen/zipstream-php --ignore-platform-reqs
