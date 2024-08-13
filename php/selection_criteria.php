<?php
require __DIR__ . '/../connection.php';

// Check if the form was submitted and save the selected values in the session
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['abbrev'])) {
        $_SESSION['abbrev'] = $_GET['abbrev'];
    }
    if (isset($_GET['x'])) {
        $_SESSION['x'] = $_GET['x'];
    }
    if (isset($_GET['y'])) {
        $_SESSION['y'] = $_GET['y'];
    }
    if (isset($_GET['order-x'])) {
        $_SESSION['order-x'] = $_GET['order-x'];
    }
}

// Fetch previously selected values from the session, if any
$selectedAbbrev = isset($_SESSION['abbrev']) ? $_SESSION['abbrev'] : [];
$selectedX = isset($_SESSION['x']) ? $_SESSION['x'] : null;
$selectedY = isset($_SESSION['y']) ? $_SESSION['y'] : null;
$selectedOrderX = isset($_SESSION['order-x']) ? $_SESSION['order-x'] : null;

// Query to populate the initial facility options
$query = "SELECT DISTINCT Facility_ID FROM lot";
$facilities = [];
$stmt = sqlsrv_query($conn, $query);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $facilities[] = $row['Facility_ID'];
}
sqlsrv_free_stmt($stmt);

// Query to populate filter options from ProbingSequenceOrder
$filterQuery = "SELECT DISTINCT p.abbrev,  w.probing_sequence FROM ProbingSequenceOrder p JOIN wafer w on w.probing_sequence = p.probing_sequence ORDER BY p.abbrev ASC";
$abbrev = [];
$filterStmt = sqlsrv_query($conn, $filterQuery);
while ($row = sqlsrv_fetch_array($filterStmt, SQLSRV_FETCH_ASSOC)) {
    $abbrev[] = ['abbrev' => $row['abbrev'], 'probing_sequence' => $row['probing_sequence']];
}
sqlsrv_free_stmt($filterStmt);

$columns = [
    'Facility ID', 'Work Center', 'Part Type', 'Program Name', 'Test Temprature', 'Lot ID',
    'Wafer ID', 'Probe Count', 'Wafer Start_Time', 'Wafer Finish_Time', 'Unit Number', 'X', 'Y', 'Head Number',
    'Site Number', 'HBin Number', 'SBin Number', 'Tests Executed', 'Test Time', 
    'Column Name', 'Test Name',
];
?>

<style>
    select:not([size]) {
        background: white !important;
    }
    .filter-text-header{
        margin-top:-28px;
    }

    .bg-cyan-700 {
        --tw-bg-opacity: 1;
        background-color: rgb(14 116 144 / var(--tw-bg-opacity)) /* #0e7490 */;
    }

    .px-12 {
        padding: 3rem /* 48px */;
    }
</style>

<div class="container mx-auto px-12 py-6 bg-white rounded-md shadow-md">
    <h1 class="text-center text-2xl font-bold mb-6 w-full">Selection Criteria</h1>
    <form action="dashboard.php" method="GET" id="criteriaForm">
    <div class="flex flex-row justify-between w-full gap-4">
        <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/3">
                <h2 class="text-md italic mb-4 w-24 text-gray-500 bg-white filter-text-header text-center"><i class="fa-solid fa-filter"></i>&nbsp;Filter by</h2>
                <div class="flex w-full justify-start items-start gap-2">
                <!-- Probe Count Button and Dropdown -->
                <button id="dropdownSearchButtonProbe" data-dropdown-toggle="dropdownSearchProbe" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                    Probe Count
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Probe Count Dropdown menu -->
                <div id="dropdownSearchProbe" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonProbe">
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="select-all-abbrev" type="checkbox" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="select-all-abbrev" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Select All</label>
                            </div>
                        </li>
                        <?php foreach ($abbrev as $item): ?>
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-<?= htmlspecialchars($item['abbrev']) ?>" name="abbrev[]" type="checkbox" value="<?= htmlspecialchars($item['abbrev']) ?>" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 filter-checkbox-abbrev">
                                <label for="checkbox-item-<?= htmlspecialchars($item['abbrev']) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300"><?= htmlspecialchars($item['abbrev']) ?></label>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>


            <!-- Group by Section -->
        <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/3">
            <h2 class="text-md italic mb-4 w-24 text-gray-500 bg-white filter-text-header text-center"><i class="fa-solid fa-layer-group"></i>&nbsp;Group by</h2>
            <div class="flex w-full justify-start items-center gap-2">
                
                <!-- X Button and Dropdown -->
                <button id="dropdownSearchButtonX" data-dropdown-toggle="dropdownSearchX" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800" type="button">
                    X-Axis
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- X Dropdown menu -->
                <div id="dropdownSearchX" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonX">
                        <?php foreach ($columns as $index => $column): ?>
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-x-<?= $index ?>" name="x" type="radio" value="<?= $index ?>" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-x-<?= $index ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300"><?= $column ?></label>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Y Button and Dropdown -->
                <button id="dropdownSearchButtonY" data-dropdown-toggle="dropdownSearchY" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-yellow-400 rounded-lg hover:bg-yellow-500 focus:ring-4 focus:outline-none focus:ring-yellow-300 dark:bg-yellow-600 dark:hover:bg-yellow-700 dark:focus:ring-yellow-800" type="button">
                    Y-Axis
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Y Dropdown menu -->
                <div id="dropdownSearchY" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonY">
                        <?php foreach ($columns as $index => $column): ?>
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-y-<?= $index ?>" name="y" type="radio" value="<?= $index ?>" class="w-4 h-4 text-yellow-500 bg-gray-100 border-gray-300 rounded focus:ring-yellow-500 dark:focus:ring-yellow-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-y-<?= $index ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300"><?= $column ?></label>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sort by Section -->
        <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/3">
            <h2 class="text-md italic mb-4 w-20 text-gray-500 bg-white filter-text-header text-center"><i class="fa-solid fa-sort"></i>&nbsp;Sort by</h2>
            <div class="flex w-full justify-start items-center gap-2">
                <!-- Sort Button and Dropdown -->
                <button id="dropdownSearchButtonSort" data-dropdown-toggle="dropdownSearchSort" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-orange-500 rounded-lg hover:bg-orange-800 focus:ring-4 focus:outline-none focus:ring-orange-300 dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800" type="button">
                    X-Axis
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Sort Dropdown menu -->
                <div id="dropdownSearchSort" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-18 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonSort">
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-x-0" name="order-x" type="radio" value="0" class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 dark:focus:ring-orange-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-x-0" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Ascending</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-x-1" name="order-x" type="radio" value="1" class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 dark:focus:ring-orange-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-x-1" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Descending</label>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- ASC or DESC Button and Dropdown -->
                <button id="dropdownSearchButtonOrder" data-dropdown-toggle="dropdownSearchOrder" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-cyan-700 rounded-lg hover:bg-cyan-800 focus:ring-4 focus:outline-none focus:ring-cyan-300 dark:bg-cyan-600 dark:hover:bg-cyan-700 dark:focus:ring-cyan-800" type="button">
                    Y-Axis
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Order Dropdown menu -->
                <div id="dropdownSearchOrder" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-18 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonOrder">
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-x-0" name="order-y" type="radio" value="0" class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500 dark:focus:ring-cyan-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-x-0" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Ascending</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="checkbox-item-x-1" name="order-y" type="radio" value="1" class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500 dark:focus:ring-cyan-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="checkbox-item-x-1" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Descending</label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        

        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="facility" class="block text-sm font-medium text-gray-700">Facility</label>
                <select id="facility" name="facility[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility ?>"><?= $facility ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="work_center" class="block text-sm font-medium text-gray-700">Work Center</label>
                <select id="work_center" name="work_center[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on facility selection -->
                </select>
            </div>

            <div>
                <label for="device_name" class="block text-sm font-medium text-gray-700">Device Name</label>
                <select id="device_name" name="device_name[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on work center selection -->
                </select>
            </div>

            <div>
                <label for="test_program" class="block text-sm font-medium text-gray-700">Test Program</label>
                <select id="test_program" name="test_program[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on device name selection -->
                </select>
            </div>

            <div>
                <label for="lot" class="block text-sm font-medium text-gray-700">Lot</label>
                <select id="lot" name="lot[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on test program selection -->
                </select>
            </div>

            <div>
                <label for="wafer" class="block text-sm font-medium text-gray-700">Wafer</label>
                <select id="wafer" name="wafer[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on lot selection -->
                </select>
            </div>

            <div class="col-span-3">
                <label for="parameter" class="block text-sm font-medium text-gray-700">Parameter</label>
                <select id="parameter" name="parameter[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple required>
                    <!-- Options will be populated based on wafer selection -->
                </select>
            </div>
        </div>

        <div class="text-center w-full flex justify-start gap-4">

            <!-- Modal toggle -->
            <button data-modal-target="select-modal" data-modal-toggle="select-modal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
            Submit&nbsp;<i class="fa-solid fa-arrow-right"></i>
            </button>

            <!-- <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg"><i class="fa-solid fa-play"></i>&nbsp;Execute</button> -->
            <button type="button" id="resetButton" class="px-4 py-2 bg-red-500 text-white rounded-lg">Reset&nbsp;<i class="fa-solid fa-delete-left"></i></button>
        </div>

        <?php include('chart_type_modal.php'); ?>
    </form>
</div>
<script src="../js/selection.js"></script>
<script>
$(document).ready(function() {
// Function to fetch options based on previous selection
function fetchOptions(selectedValue, targetElement, queryType) {
    $.ajax({
        url: 'fetch_options.php',
        method: 'GET',
        data: {
            value: selectedValue,
            type: queryType
        },
        dataType: 'json',
        success: function(response) {
            let options = '';
            if (queryType === 'parameter') {
                $.each(response, function(index, item) {
                    options += `<option value="${item.value}">${item.display}</option>`;
                });
            } else {
                $.each(response, function(index, value) {
                    options += `<option value="${value}">${value}</option>`;
                });
            }
            targetElement.html(options);
        }
    });
}

// Event listeners for each select element
$('#facility').change(function() {
    const selectedFacility = $(this).val();
    fetchOptions(selectedFacility, $('#work_center'), 'work_center');
});

$('#work_center').change(function() {
    const selectedWorkCenter = $(this).val();
    fetchOptions(selectedWorkCenter, $('#device_name'), 'device_name');
});

$('#device_name').change(function() {
    const selectedDeviceName = $(this).val();
    fetchOptions(selectedDeviceName, $('#test_program'), 'test_program');
});

$('#test_program').change(function() {
    const selectedTestProgram = $(this).val();
    fetchOptions(selectedTestProgram, $('#lot'), 'lot');
});

$('#lot').change(function() {
    const selectedLot = $(this).val();
    fetchOptions(selectedLot, $('#wafer'), 'wafer');
});

$('#wafer').change(function() {
    const selectedWafer = $(this).val();
    fetchOptions(selectedWafer, $('#parameter'), 'parameter');
});

// Reset button functionality
$('#resetButton').click(function() {
    $('#criteriaForm')[0].reset();
    $('#work_center, #device_name, #test_program, #lot, #wafer, #parameter').html('');
});
});
</script>
