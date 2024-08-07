<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Selection</title>
   <link rel="stylesheet" href="../src/output.css">
   <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
   <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
   <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <style>
      .center-logo {
         display: flex;
         justify-content: center;
         align-items: center;
         height: 100%;
      }
      .bg-primary {
         background-color: #fff;
      }
      .bg-secondary {
         background: linear-gradient(to top, #7FA1C3, #fff);
      }
      .bg-tertiary {
         background-color: #E2DAD6;
      }
      .bg-quaternary {
         background-color: #F5EDED;
      }
      .text-primary {
         color: #6482AD;
      }
      .text-secondary {
         color: #7FA1C3;
      }
      .text-tertiary {
         color: #E2DAD6;
      }
      .text-quaternary {
         color: #F5EDED;
      }
   </style>
</head>
<body class="bg-quaternary">
<nav class="fixed top-0 z-50 w-full bg-primary border-b border-secondary dark:bg-gray-800 dark:border-gray-700">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="flex items-center justify-center h-12">
      <a href="https://flowbite.com" class="center-logo">
        <img src="../images/yieldwerx.png" class="h-12" alt="YieldWerx Logo" />
      </a>
    </div>
  </div>
</nav>

<div class="p-4">
   <div class="p-4 rounded-lg bg-secondary dark:border-gray-700 mt-14">
      <div>
      <?php include('selection_criteria.php');?>
      </div>
   </div>
</div>
</body>
</html>
