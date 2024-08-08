<?php
require __DIR__ . '/../connection.php';

// Query to populate the initial facility options
$query = "SELECT Facility_ID FROM lot";
$facilities = [];
$stmt = sqlsrv_query($conn, $query);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $facilities[] = $row['Facility_ID'];
}
sqlsrv_free_stmt($stmt);

// Query to populate filter options from ProbingSequenceOrder
$filterQuery = "SELECT abbrev, probing_sequence FROM ProbingSequenceOrder";
$abbrev = [];
$filterStmt = sqlsrv_query($conn, $filterQuery);
while ($row = sqlsrv_fetch_array($filterStmt, SQLSRV_FETCH_ASSOC)) {
    $abbrev[] = ['abbrev' => $row['abbrev'], 'probing_sequence' => $row['probing_sequence']];
}
sqlsrv_free_stmt($filterStmt);
?>

<style>
    select:not([size]) {
        background: white !important;
    }
    .filter-text-header {
        margin-top: -28px;
    }
    .custom-box {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 0.5rem;
    }
</style>

<div class="container mx-auto p-6">
    <h1 class="text-center text-2xl font-bold mb-4 w-full">Selection Criteria</h1>
    <form action="dashboard.php" method="GET" id="criteriaForm">
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="facility" class="block text-sm font-medium text-gray-700">Facility</label>
                <select id="facility" name="facility[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility ?>"><?= $facility ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="work_center" class="block text-sm font-medium text-gray-700">Work Center</label>
                <select id="work_center" name="work_center[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on facility selection -->
                </select>
            </div>

            <div>
                <label for="device_name" class="block text-sm font-medium text-gray-700">Device Name</label>
                <select id="device_name" name="device_name[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on work center selection -->
                </select>
            </div>

            <div>
                <label for="test_program" class="block text-sm font-medium text-gray-700">Test Program</label>
                <select id="test_program" name="test_program[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on device name selection -->
                </select>
            </div>

            <div>
                <label for="lot" class="block text-sm font-medium text-gray-700">Lot</label>
                <select id="lot" name="lot[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on test program selection -->
                </select>
            </div>

            <div>
                <label for="wafer" class="block text-sm font-medium text-gray-700">Wafer</label>
                <select id="wafer" name="wafer[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on lot selection -->
                </select>
            </div>

            <div>
                <label for="parameter" class="block text-sm font-medium text-gray-700">Parameter</label>
                <select id="parameter" name="parameter[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on wafer selection -->
                </select>
            </div>

            <div class="custom-box">
                <label class="block text-sm font-medium text-gray-700">Filter by</label>
                <div class="flex flex-col gap-4">
                    <!-- Filter by -->
                    <div class="border-2 border-gray-200 rounded-lg p-4">
                        <h2 class="text-md italic mb-4 text-gray-500 bg-gray-50 filter-text-header text-center"><i class="fa-solid fa-filter"></i>&nbsp;Filter by</h2>
                        <div class="flex w-full justify-start items-start gap-2">
                            <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                                Abbreviation
                                <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <!-- Dropdown menu -->
                            <div id="dropdownSearch" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                                <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButton">
                                    <li>
                                        <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <input id="select-all-abbrev" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                            <label for="select-all-abbrev" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">Select All</label>
                                        </div>
                                    </li>
                                    <?php foreach ($abbrev as $item): ?>
                                    <li>
                                        <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <input id="checkbox-item-<?= htmlspecialchars($item['abbrev']) ?>" name="abbrev[]" type="checkbox" value="<?= htmlspecialchars($item['probing_sequence']) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 filter-checkbox-abbrev">
                                            <label for="checkbox-item-<?= htmlspecialchars($item['abbrev']) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300"><?= htmlspecialchars($item['abbrev']) ?></label>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="custom-box">
                <label class="block text-sm font-medium text-gray-700">Group by</label>
                <div class="border-2 border-gray-200 rounded-lg p-4">
                    <h2 class="text-md italic mb-4 text-gray-500 bg-gray-50 filter-text-header text-center"><i class="fa-solid fa-layer-group"></i>&nbsp;Group by</h2>
                    <div class="flex w-full justify-start items-center gap-2">
                        <div class="flex items-center me-4">
                            <input id="lot-checkbox" name="group_lot" type="checkbox" value="1" class="w-4 h-4 text-yellow-400 bg-gray-100 border-gray-300 rounded focus:ring-yellow-500 dark:focus:ring-yellow-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="lot-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Lot</label>
                        </div>
                        <div class="flex items-center me-4">
                            <input id="wafer-checkbox" name="group_wafer" type="checkbox" value="1" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="wafer-checkbox" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Wafer</label>
                        </div>
                    </div>
                </div>
                <label class="block text-sm font-medium text-gray-700">Group by Parameter</label>
                <input type="checkbox" id="group_by_param" name="group_by_param" value="1" onchange="toggleGroupByOptions()">
                <div id="groupByOptions" style="display:none;">
                    <label for="group_by_param_select" class="block text-sm font-medium text-gray-700 mt-2">Select Parameter to Group By</label>
                    <select id="group_by_param_select" name="group_by_param_select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <!-- Options will be populated dynamically based on selected parameters -->
                    </select>

                    <label for="group_by_direction" class="block text-sm font-medium text-gray-700 mt-2">Group Direction</label>
                    <select id="group_by_direction" name="group_by_direction" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="horizontal">Horizontal</option>
                        <option value="vertical">Vertical</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">&nbsp;Submit</button>
            <button type="button" id="resetButton" class="px-4 py-2 bg-red-500 text-white rounded">&nbsp;Reset</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
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
                    response.sort(function(a, b) {
                        let numA = parseInt(a.value.slice(1)); // Extract number from 'Txxx'
                        let numB = parseInt(b.value.slice(1));
                        return numA - numB;
                    });
                    $.each(response, function(index, item) {
                        options += `<option value="${item.value}">${item.value}: ${item.display}</option>`;
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

    $('#resetButton').click(function() {
        $('#criteriaForm')[0].reset();
    });

    $('#select-all-abbrev').click(function() {
        $('.filter-checkbox-abbrev').prop('checked', this.checked);
    });

    $('.filter-checkbox-abbrev').click(function() {
        if ($('.filter-checkbox-abbrev:checked').length === $('.filter-checkbox-abbrev').length) {
            $('#select-all-abbrev').prop('checked', true);
        } else {
            $('#select-all-abbrev').prop('checked', false);
        }
    });
});

function toggleGroupByOptions() {
    const groupByParam = document.getElementById('group_by_param');
    const groupByOptions = document.getElementById('groupByOptions');
    const parameterSelect = document.getElementById('parameter');
    const groupByParamSelect = document.getElementById('group_by_param_select');
    
    if (groupByParam.checked) {
        // Show group by options
        groupByOptions.style.display = 'block';

        // Clear existing options
        groupByParamSelect.innerHTML = '';

        // Populate with selected parameters
        Array.from(parameterSelect.options).forEach(option => {
            if (option.selected) {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.textContent = option.textContent;
                groupByParamSelect.appendChild(newOption);
            }
        });
    } else {
        // Hide group by options
        groupByOptions.style.display = 'none';
    }
}

document.getElementById('parameter').addEventListener('change', toggleGroupByOptions);

</script>
