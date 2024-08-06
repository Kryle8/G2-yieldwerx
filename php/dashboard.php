<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>
   <link rel="stylesheet" href="../src/output.css">
   <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
   <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
   <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <style>
      .logo-centered {
         display: flex;
         justify-content: center;
         align-items: center;
         height: 100%;
         width: 100%;
      }
      .content-container {
         margin-top: 60px; /* Adjust this value if necessary to avoid overlapping with the navbar */
      }
   </style>
</head>
<body class="bg-gray-100">
<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="logo-centered">
      <a href="https://flowbite.com" class="flex">
        <img src="../images/yieldwerx.png" class="h-12" alt="YieldWerx Logo" />
      </a>
    </div>
  </div>
</nav>

<div class="content-container">
   <div class="p-4 rounded-lg dark:border-gray-700">
      <div>
         <?php include('extracted_table.php'); ?>
      </div>
   </div>
</div>
</body>
</html>
