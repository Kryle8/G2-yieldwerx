# YieldWerx Data Extraction and Analytics System

## Table of Contents
- [Introduction](#introduction)
- [Features](#features)
- [Languages and Tools](#languages-and-tools)
  - [Frontend](#frontend)
  - [Backend](#backend)
  - [Database](#database)
  - [Tools](#tools)
- [Requirements](#requirements)
- [Installation Steps](#installation-steps)
  - [Step 1: Connect PHP to Microsoft SQL Server](#step-1-connect-php-to-microsoft-sql-server)
  - [Step 2: Match PHP version with `php_pdo_sqlsrv` and `php_sqlsrv`](#step-2-match-php-version-with-php_pdo_sqlsrv-and-php_sqlsrv)
  - [Step 3: Transfer `php_pdo_sqlsrv` and `php_sqlsrv` files to PHP ext folder](#step-3-transfer-php_pdo_sqlsrv-and-php_sqlsrv-files-to-php-ext-folder)
  - [Step 4: Insert files as extensions in `php.ini`](#step-4-insert-files-as-extensions-in-phpini)
  - [Step 5: Establish connection in VS Code](#step-5-establish-connection-in-vs-code)
  - [Step 6: Clone the repository](#step-6-clone-the-repository)
  - [Step 7: Install dependencies](#step-7-install-dependencies)
  - [Step 8: Add database information in `.env` file](#step-8-add-database-information-in-env-file)
  - [Step 9: Launch the frontend](#step-9-launch-the-frontend)
  - [Step 10: Visit the application](#step-10-visit-the-application)
- [UI Screenshots](#ui-screenshots)
  - [Dashboard](#dashboard)
  - [Charts and Graphs](#charts-and-graphs)
  - [Data Tables](#data-tables)
  - [Responsive Design](#responsive-design)

## Introduction
Welcome to the YieldWerx Data Extraction and Analytics System, designed to streamline data processing and analysis with an emphasis on maintainability, performance, and user experience.

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

 ![image](https://github.com/user-attachments/assets/35ee70b9-98ed-493d-9c6f-eca4fe4f0255)
 ![image](https://github.com/user-attachments/assets/5ab624ac-1a62-4d09-ac70-1a642036d706)


3. Transfer php_pdo_sqlsrv and php_sqlsrv files to php ext folder (C:\xampp\php\ext)

 ![image](https://github.com/user-attachments/assets/06e70e46-4f44-4201-ab05-e1b709f6004b)
 ![image](https://github.com/user-attachments/assets/7deb30e0-23ff-4cc0-9eab-da47a236c3f4)


4.  Insert the files as extension in php.ini

  ![image](https://github.com/user-attachments/assets/b5c0ee49-7aaf-4491-878c-9a5420bded3a)


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
# UI Screenshots

## Selection Criteria
![image](https://github.com/user-attachments/assets/5ae1bbde-f300-4538-8c7d-a15ed7095cb0)
![image](https://github.com/user-attachments/assets/1205c857-7f35-4cf3-ae93-7ed13e2bdee7)

- **Functionality:** Allows users to define specific criteria for data extraction. The selection process involves filtering data based on the selected Group By conditions. This feature ensures that the extracted data is relevant and meets the user's analysis needs.

## Extracted Table
![Screenshot (274)](https://github.com/user-attachments/assets/d27f6950-e970-4f40-86c9-3c80e67865a7)

- **Functionality:** Presents the data extracted based on the selection criteria in a structured tabular format. Users can view detailed records, perform sorting, and apply filters. This table serves as the central location for reviewing the raw data before it is analyzed or visualized.

## Graphs
### Overview
- **Functionality:** Provides visual representations of the extracted data, enabling users to easily interpret complex datasets. The system offers various types of charts and graphs to suit different analytical needs.

### XY Scatter Plot
![Screenshot (275)](https://github.com/user-attachments/assets/c524dce8-de64-40a1-8bf2-d9c903ded52a)
- **Functionality:** Visualizes the relationship between two variables across the dataset. This plot is ideal for identifying correlations, trends, and outliers within the data.

### Line Chart
![Screenshot (276)](https://github.com/user-attachments/assets/eb194b10-2ecf-4342-ae4a-ac94db4b9965)
- **Functionality:** Depicts trends over time or across sequential data points. The line chart is particularly useful for tracking changes in key metrics, allowing users to observe patterns and predict future outcomes based on historical data.

### Cumulative Probability Chart
![Screenshot (277)](https://github.com/user-attachments/assets/e21bdd15-c42d-4dab-8ac8-b57a2a8bfdaf)
- **Functionality:** Displays the cumulative probability distribution of a dataset. This chart helps users understand the probability of different outcomes occurring within a range of values, providing insights into the overall data distribution and the likelihood of specific results.

## Additional UI

### Chart Settings for Adjusting Chart Margin
![image](https://github.com/user-attachments/assets/b16d1d6b-b3ee-4549-b507-ee7069bb6445)
![image](https://github.com/user-attachments/assets/fdad2ef8-426b-4b25-a172-16a9d2c36da7)
![image](https://github.com/user-attachments/assets/bfbff264-3528-45d7-a31e-5e9ed7432170)
- **Functionality:** Offers users the ability to customize chart margins and other visual settings. By adjusting these parameters, users can refine the display of charts to better fit their analysis needs, ensuring clarity and enhancing the visual presentation of data.
