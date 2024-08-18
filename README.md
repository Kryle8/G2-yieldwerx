# Features
- Utilizes the Repository Pattern and Services for maintainability
- Follows clean code and Separation of Concerns principles
- Optimizes database performance by avoiding the N+1 problem.
- Implements a responsive design to ensure a good user experience on all devices
- Uses reusable components and layouts to avoid duplication of code
- utilizes charts and graphs to ensure that users can easily understand the information presented
- features a simple design to ensure easy navigation for users

# Languages and Tools
## Frontend
- Tailwind CSS
- Flowbite
- Html

## Backend
- PHP 8
- Javascript

## Database
- SQL Server Management Studio 20

## Tools
- Git
- Github
- ODBC driver
- composer
- Microsoft Drivers for PHP for SQL Server
- XAMPP
- SQL Server Management Studio 20
 
## Requirements
- Node.js
- Composer
- PHP 8
- ODBC driver
- chart.js


### Installation Steps
1. Connect PHP to Microsoft SQL Server

   ``` bash
   https://youtu.be/TN0gV3KPp10?si=G0b-q2H1FV_pEucl
   ```
   
2. Make sure the PHP version matches the php_pdo_sqlsrv and php_sqlsrv

 !
 !

3. Transfer php_pdo_sqlsrv and php_sqlsrv files to php ext folder

 !
 !

4.  Insert the files as extension in php.ini

  !

5. Establish connection in VS Code



6. Clone the repository inside C:\xampp\htdocs\

   ```bash
   git clone https://github.com/Kryle8/G2-yieldwerx.git
   ```

7. Install the dependencies

   ```bash
   composer install
   ```

   ```bash
   npm install
   ```
   
8. In the (.env file), add database information to connect to the database

   ```env
   DB_SERVERNAME=SERVERNAME
   DB_DATABASE=yielWerx_OJT2024_Updated
   DB_USERNAME=
   DB_PASSWORD=
   ```
   
9. Launch the frontend asset of the system

   ```bash
   npm run dev
   ```

10. Visit the application

    ```bash
    http://localhost/G2-YIELDWERX/php/selection_page.php
    ```
