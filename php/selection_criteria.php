<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3x3 Layout with Tailwind CSS and jQuery</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include('navbar.php'); ?>

    <div class="max-w-7xl mx-auto pt-32">
        <div class="grid grid-cols-3 gap-4">
            <!-- Row 1 -->
            <div class="col-span-1">
                <label for="facility" class="block mb-2 text-sm font-medium text-gray-900">Facility</label>
                <select id="facility" name="facility" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <div class="col-span-1">
                <label for="work_center" class="block mb-2 text-sm font-medium text-gray-900">Work Center</label>
                <select id="work_center" name="work_center" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <div class="col-span-1">
                <label for="device_name" class="block mb-2 text-sm font-medium text-gray-900">Device Name</label>
                <select id="device_name" name="device_name" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <!-- Row 2 -->
            <div class="col-span-1">
                <label for="test_program" class="block mb-2 text-sm font-medium text-gray-900">Test Program</label>
                <select id="test_program" name="test_program" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <div class="col-span-1">
                <label for="lot" class="block mb-2 text-sm font-medium text-gray-900">Lot</label>
                <select id="lot" name="lot" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <div class="col-span-1">
                <label for="wafer" class="block mb-2 text-sm font-medium text-gray-900">Wafer</label>
                <select id="wafer" name="wafer" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options go here -->
                </select>
            </div>
            <!-- Row 3 -->
            <div class="col-span-3">
                <label for="parameter" class="block mb-2 text-sm font-medium text-gray-900">Parameter</label>
                <select id="parameter" name="parameter[]" multiple class="block w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 select2">
                    <!-- Options will be populated by jQuery -->
                </select>
            </div>
        </div>

        <!-- Execute Button -->
        <div class="mt-6">
            <button id="executeBtn" class="w-full p-3 bg-blue-500 text-white rounded-lg hover:bg-blue-700">Execute</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.select2').select2();

            const fetchOptions = (id, query) => {
                $.ajax({
                    url: 'fetch_options.php',
                    type: 'GET',
                    data: { query: query },
                    success: function(data) {
                        var options = JSON.parse(data);
                        options.forEach(function(option) {
                            $('#' + id).append(new Option(option, option));
                        });
                    }
                });
            };

            const queries = {
                facility: "SELECT Facility_ID FROM lot",
                work_center: "SELECT Work_Center FROM lot",
                device_name: "SELECT Part_Type FROM lot",
                test_program: "SELECT Program_Name FROM lot",
                lot: "SELECT Lot_ID FROM lot",
                wafer: "SELECT Wafer_ID FROM wafer ORDER BY Wafer_ID ASC",
                parameter: "SELECT Test_Name FROM TEST_PARAM_MAP"
            };

            Object.keys(queries).forEach(key => {
                fetchOptions(key, queries[key]);
            });

            $('#executeBtn').on('click', function() {
                // Define your action for the execute button here
                alert("Execute button clicked!");
            });
        });
    </script>
</body>
</html>
