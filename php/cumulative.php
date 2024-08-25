<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Chart</title>
   <link rel="stylesheet" href="css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #chartsContainer {
            display: grid;
            grid-gap: 10px;
            justify-content: center;
        }
       .chart-container {
           overflow: auto;
           max-width: 100%;
       }
       td {
           padding: 16px;
       }
       canvas {
           height: 400px;
           width: 450px;
       }
       .-rotate-90 {
            --tw-rotate: -90deg;
            transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
        }
        .mt-24 {
            margin-top: 6rem /* 96px */;
        }
        .max-w-fit {
            max-width: fit-content;
        }
        .customize-text-header{
            margin-top:-28px;
        }

        .right-4 {
            right: 2.5rem /* 16px */;
        }
        .top-24 {
            top: 6rem /* 96px */;
        }
        .ml-16 {
            margin-left: 4rem /* 64px */;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include('admin_components.php'); ?>
    <?php include('settings.php'); ?>
    <div class="p-4 w-full">
        <div class="p-4 rounded-lg dark:border-gray-700 mt-14">
            <div>
            <?php
                include ('../php/cumulative_probability_chart.php');    
            ?>
            </div>
        </div>
    </div>
</body>
</html>