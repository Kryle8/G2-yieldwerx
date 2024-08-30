<?php
    require_once('../controllers/SelectionController.php');

    $selectionController = new SelectionController();
    $facilities = $selectionController->getFacilities();
    $lotGroups = $selectionController->getLotHeaders();
    $waferGroups = $selectionController->getWaferHeaders();
    $abbrev = $selectionController->getProbingFilter();
?>

<script>
    function fetchFilters(selectedValue, targetElement, type) {
        $.ajax({
            url: 'fetch_filters.php',
            method: 'GET',
            data: {
                value: selectedValue
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                // Create the <ul> element with the required classes
                const ul = $('<ul>', {
                    class: 'max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700',
                    'aria-labelledby': 'dropdownFilterButton'
                });

                // Add items from the response
                response.forEach(item => {
                    const li = $('<li>');
                    const div = $('<div>', {
                        class: 'flex items-center p-2 rounded hover:bg-gray-100'
                    });

                    const checkbox = $('<input>', {
                        id: `checkbox-item-${item}`,
                        name: `filter-${type}[]`,
                        type: 'checkbox',
                        value: item,
                        class: 'w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500'
                    });

                    const label = $('<label>', {
                        for: `checkbox-item-${item}`,
                        class: 'w-full ms-2 text-sm font-medium text-gray-900 rounded',
                        text: item
                    });

                    div.append(checkbox, label);
                    li.append(div);
                    ul.append(li);
                });

                // Clear previous content and append the new <ul>
                $(targetElement).empty().append(ul);
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                console.log('Response Text:', xhr.responseText);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Handle form submission to determine selected parameters and show confirmation
        document.getElementById('criteriaForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent the default form submission

            // Get selected parameters
            const selectedX = document.querySelectorAll('#parameter_x option:checked').length > 0;
            const selectedY = document.querySelectorAll('#parameter_y option:checked').length > 0;

            let chartSelection;
            let confirmationMessage;

            if (selectedX && selectedY) {
                chartSelection = 1;
                confirmationMessage = "You selected both parameters X and Y. \nThis would mean that a XY Scatter Plot will be generated. \nDo you want to proceed with this selection?";
            } else if (selectedX) {
                chartSelection = 2;
                confirmationMessage = "You selected parameter X. \nThis would mean that a Cumulative Probability will be generated. \nDo you want to proceed with this selection?";
            } else if (selectedY) {
                chartSelection = 0;
                confirmationMessage = "You selected parameter Y. \nThis would mean that a Line Chart will be generated. \nDo you want to proceed with this selection?";
            } else {
                alert("Please select at least one parameter.");
                return; // Do not submit the form if no parameter is selected
            }

            // Show confirmation dialog
            if (confirm(confirmationMessage)) {
                // User chose to proceed

                // Dynamically create the input element for chart selection
                const chartInput = document.createElement('input');
                chartInput.type = 'hidden';
                chartInput.name = 'chart';
                chartInput.value = chartSelection;

                // Append the hidden input to the form
                this.appendChild(chartInput);

                // Submit the form
                this.submit();
            } else {
                // User chose to go back and edit their selections
                alert("You can edit your selections and try again.");
            }
        });
    });

</script>

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

<div class="container mx-auto px-12 py-6 rounded-md shadow-md">
    <h1 class="text-center text-2xl font-bold mb-6 w-full">Selection Criteria</h1>
    <form action="dashboard.php" method="GET" id="criteriaForm">


        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="facility" class="block text-sm font-medium text-gray-700">Facility</label>
                <select id="facility" name="facility[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility ?>"><?= $facility ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="work_center" class="block text-sm font-medium text-gray-700">Work Center</label>
                <select id="work_center" name="work_center[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on facility selection -->
                </select>
            </div>

            <div>
                <label for="device_name" class="block text-sm font-medium text-gray-700">Device Name</label>
                <select id="device_name" name="device_name[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on work center selection -->
                </select>
            </div>

            <div>
                <label for="test_program" class="block text-sm font-medium text-gray-700">Test Program</label>
                <select id="test_program" name="test_program[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on device name selection -->
                </select>
            </div>

            <div>
                <label for="lot" class="block text-sm font-medium text-gray-700">Lot</label>
                <select id="lot" name="lot[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on test program selection -->
                </select>
            </div>

            <div>
                <label for="wafer" class="block text-sm font-medium text-gray-700">Wafer</label>
                <select id="wafer" name="wafer[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on lot selection -->
                </select>
            </div>

            <div class="flex gap-4 col-span-3">
                <div class="flex-1">
                    <label for="parameter_x" class="block text-sm font-medium text-gray-700">Parameter X</label>
                    <select id="parameter_x" name="parameter_x[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                        <!-- Options will be populated based on wafer selection -->
                    </select>
                </div>
                
                <div class="flex-1">
                    <label for="parameter_y" class="block text-sm font-medium text-gray-700">Parameter Y</label>
                    <select id="parameter_y" name="parameter_y[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                        <!-- Options will be populated based on wafer selection -->
                    </select>
                </div>
            </div>


            <div class="flex gap-4 col-span-3">
                <div class="flex-1">
                    <div class="border-2 border-gray-200 rounded-lg p-4">
                        <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center"><i class="fa-solid fa-layer-group"></i>&nbsp;Group by (X)</h2>
                        <div class="flex w-full justify-start items-center gap-2">
                            <button id="dropdownGroupXButton" data-dropdown-toggle="dropdownGroupX" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                                X Axis
                                <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <div id="dropdownGroupX" class="z-10 hidden w-auto h-64 overflow-y-auto bg-white divide-y divide-gray-200 rounded-lg shadow">
                                <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                                    <?php foreach ($lotGroups as $group): ?>
                                    <li>
                                        <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                            <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-x[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                            <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                                    <?php foreach ($waferGroups as $group): ?>
                                    <li>
                                        <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                            <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-x[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                            <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Select input for sorting -->
                            <label for="sort-x" class="sr-only">Sort X</label>
                            <select id="sort-x" name="sort-x" class="block py-2.5 px-4 text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none focus:outline-none focus:ring-0 focus:border-gray-200 peer ml-auto">
                                <option value="ASC" selected>Ascending</option>
                                <option value="DESC">Descending</option>  
                            </select>
                        </div>

                        <!-- Selected group display -->
                        <div id="selectedGroup" class="text-gray-600 dark:text-gray-300 mt-4">
                            <span class="font-medium">Selected Group:</span>
                            <div id="selectedGroupContainer" class="mt-2 flex space-x-2 overflow-x-auto">
                                <!-- Selected group will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const dropdownGroupX = document.getElementById('dropdownGroupX');
                            const selectedGroupContainer = document.getElementById('selectedGroupContainer');

                            function updateSelectedGroup() {
                                const selectedGroup = document.querySelector('input[name="group-x[]"]:checked');
                                const selectedText = selectedGroup.nextElementSibling.textContent;
                                selectedGroupContainer.innerHTML = ''; // Clear current display
                                if (selectedGroup) {
                                    const listItem = document.createElement('div');
                                    listItem.className = 'flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-600 rounded';
                                    listItem.textContent = selectedText;
                                    selectedGroupContainer.appendChild(listItem);
                                }
                                fetchFilters(selectedText, $('#dropdownXFilter'), 'x');
                            }

                            // Toggle dropdown visibility
                            document.getElementById('dropdownGroupXButton').addEventListener('click', function () {
                                dropdownGroupX.classList.toggle('hidden');
                            });

                            // Update selected group on radio button change
                            document.querySelectorAll('input[name="group-x[]"]').forEach(radio => {
                                radio.addEventListener('change', updateSelectedGroup);
                            });
                        });
                    </script>
                </div>

                <div class="flex-1">
                    <div class="custom-box mb-4 w-2/3">
                        <div class="border-2 border-gray-200 rounded-lg p-4">
                            <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center"><i class="fa-solid fa-layer-group"></i>&nbsp;Group by (Y)</h2>
                            <div class="flex w-full justify-start items-center gap-2">
                                <button id="dropdownGroupYButton" data-dropdown-toggle="dropdownGroupY" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                                    Y Axis
                                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div id="dropdownGroupY" class="z-10 hidden w-auto h-64 overflow-y-auto bg-white divide-y divide-gray-200 rounded-lg shadow">
                                    <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                                        <?php foreach ($lotGroups as $group): ?>
                                        <li>
                                            <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                                <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-y[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                                <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                                        <?php foreach ($waferGroups as $group): ?>
                                        <li>
                                            <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                                <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-y[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                                <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Select input for sorting -->
                                <select id="sort-y" name="sort-y" class="block py-2.5 px-4 text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none focus:outline-none focus:ring-0 focus:border-gray-200 peer ml-auto">
                                    <option value="ASC" selected>Ascending</option>
                                    <option value="DESC">Descending</option>  
                                </select>
                            </div>

                            <!-- Selected group display -->
                            <div id="selectedGroupY" class="text-gray-600 dark:text-gray-300 mt-4">
                                <span class="font-medium">Selected Group:</span>
                                <div id="selectedGroupYContainer" class="mt-2 flex space-x-2 overflow-x-auto">
                                    <!-- Selected group will be dynamically inserted here -->
                                </div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const dropdownGroupY = document.getElementById('dropdownGroupY');
                                const selectedGroupYContainer = document.getElementById('selectedGroupYContainer');

                                function updateSelectedGroupY() {
                                    const selectedGroupY = document.querySelector('input[name="group-y[]"]:checked');
                                    const selectedText = selectedGroupY.nextElementSibling.textContent;
                                    selectedGroupYContainer.innerHTML = ''; // Clear current display
                                    if (selectedGroupY) {
                                        const listItem = document.createElement('div');
                                        listItem.className = 'flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-600 rounded';
                                        listItem.textContent = selectedText;
                                        selectedGroupYContainer.appendChild(listItem);
                                    }
                                    fetchFilters(selectedText, $('#dropdownYFilter'), 'y');
                                }

                                // Toggle dropdown visibility
                                document.getElementById('dropdownGroupYButton').addEventListener('click', function () {
                                    dropdownGroupY.classList.toggle('hidden');
                                });

                                // Update selected group on radio button change
                                document.querySelectorAll('input[name="group-y[]"]').forEach(radio => {
                                    radio.addEventListener('change', updateSelectedGroupY);
                                });
                            });
                        </script>
                    </div>
                </div>

                <div class="flex-1">
                    <div class="custom-box mb-4 w-2/3">
                        <div class="border-2 border-gray-200 rounded-lg p-4">
                            <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center"><i class="fa-solid fa-filter"></i>&nbsp;Filter by</h2>
                            <!-- Dropdown menu -->
                            <div class="flex w-full justify-start items-center gap-2 mb-4">
                                <button id="dropdownXFilterButton" data-dropdown-toggle="dropdownXFilter" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                                    X-Filter
                                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                    </svg>
                                </button>

                                <div id="dropdownXFilter" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                                    
                                </div>

                                <button id="dropdownYFilterButton" data-dropdown-toggle="dropdownYFilter" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                                    Y-Filter
                                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                    </svg>
                                </button>

                                <div id="dropdownYFilter" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                                    
                                </div>
                            </div>
                        </div>
                    </div>


                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const dropdownFilter = document.getElementById('dropdownSearchProbe');
                        const selectedFiltersContainer = document.getElementById('selectedFiltersContainer');

                        function updateSelectedFilters() {
                            const selectedFilters = document.querySelectorAll('.filter-checkbox-abbrev:checked');
                            selectedFiltersContainer.innerHTML = ''; // Clear current list
                            selectedFilters.forEach(checkbox => {
                                const listItem = document.createElement('div');
                                listItem.className = 'flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-600 rounded';
                                listItem.textContent = checkbox.nextElementSibling.textContent;
                                selectedFiltersContainer.appendChild(listItem);
                            });
                        }

                        // Toggle dropdown visibility
                        document.getElementById('dropdownSearchButtonProbe').addEventListener('click', function () {
                            dropdownFilter.classList.toggle('hidden');
                        });

                        // Update selected filters on checkbox change
                        document.querySelectorAll('.filter-checkbox-abbrev').forEach(checkbox => {
                            checkbox.addEventListener('change', updateSelectedFilters);
                        });

                        // Select all functionality
                        document.getElementById('select-all-abbrev').addEventListener('change', function () {
                            const isChecked = this.checked;
                            document.querySelectorAll('.filter-checkbox-abbrev').forEach(checkbox => {
                                checkbox.checked = isChecked;
                            });
                            updateSelectedFilters();
                        });
                    });

                    </script>
                </div>


                <!-- <div class="flex-1 max-w-[100px]">
                    <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
                        <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Type of Chart</h2>
                        <div class="flex flex-col w-full justify-start items-center gap-2">
                            <div class="flex items-center">
                                <input type="radio" id="chart-1" name="chart" value="0" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" required>
                                <label for="chart-1" class="ms-2 text-sm font-medium text-gray-900">Line</label>
                            </div>
                            <div class="flex items-center">
                                <input input type="radio" id="chart-2" name="chart" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                <label for="chart-2" class="ms-2 text-sm font-medium text-gray-900">Scatter</label>
                            </div>
                        </div>
                    </div>
                </div> -->
                <div class="flex-1 max-w-[100px]">
                    <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
                        <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Type of Chart</h2>
                        <p class="text-sm text-gray-700">
                            The selection of the type of chart differ in what type of parameter you select:
                            <ul class="list-disc list-inside text-sm">
                                <li><strong>Both X and Y:</strong> A XY Scatter Plot will be generated.</li>
                                <li><strong>Only X:</strong> A Cumulative Probability chart will be generated.</li>
                                <li><strong>Only Y:</strong> A Line Chart will be generated.</li>
                            </ul>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <div class="text-center w-full flex justify-start gap-4" style="display: flex; justify-content: flex-end;">

            <!-- Modal toggle
            <button data-modal-target="select-modal" data-modal-toggle="select-modal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
            Submit&nbsp;<i class="fa-solid fa-arrow-right"></i>
            </button> -->

            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg">Submit</button>
            <button type="button" id="resetButton" class="px-4 py-2 bg-red-500 text-white rounded-lg">Reset</button>
        </div>

        <!-- ?php include('chart_type_modal.php'); ? -->

        
    </form>
</div>

<script>
// document.getElementById('select-all').addEventListener('change', function() {
//     var checkboxes = document.querySelectorAll('.filter-checkbox');
//     for (var checkbox of checkboxes) {
//         checkbox.checked = this.checked;
//     }
// });

$(document).ready(function() {
    // Function to fetch options based on previous selection
    function fetchOptions(selectedValue, targetElement, queryType) {
        $.ajax({
            url: 'fetch_options.php',
            method: 'GET',
            data: {
                value: JSON.stringify(selectedValue),
                type: queryType
            },
            dataType: 'json',
            success: function(response) {
                let options = '';
                if (queryType === 'parameter_x') {
                    $.each(response, function(index, item) {
                        options += `<option value="${item.value}">${item.display}</option>`;
                    });
                }
                else if (queryType === 'parameter_y') {
                    $.each(response, function(index, item) {
                        options += `<option value="${item.value}">${item.display}</option>`;
                    });
                } else {
                    $.each(response, function(index, value) {
                        options += `<option value="${value}">${value}</option>`;
                    });
                }
                targetElement.html(options);
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                console.log('Response Text:', xhr.responseText);
            }
        });
    }
    
    const selectedValues = {
        Facility_ID: null,
        Work_Center: null,
        Part_Type: null,
        Program_Name: null,
        Lot_ID: null,
        Wafer_ID: null
    };

    // Event listeners for each select element
    $('#facility').change(function() {
        const selectedFacility = $(this).val();
        selectedValues.Facility_ID = selectedFacility;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#work_center'), 'work_center');
    });

    $('#work_center').change(function() {
        const selectedWorkCenter = $(this).val();
        selectedValues.Work_Center = selectedWorkCenter;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#device_name'), 'device_name');
    });

    $('#device_name').change(function() {
        const selectedDeviceName = $(this).val();
        selectedValues.Part_Type = selectedDeviceName;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#test_program'), 'test_program');
    });

    $('#test_program').change(function() {
        const selectedTestProgram = $(this).val();
        selectedValues.Program_Name = selectedTestProgram;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#lot'), 'lot');
    });

    $('#lot').change(function() {
        const selectedLot = $(this).val();
        selectedValues.Lot_ID = selectedLot;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#wafer'), 'wafer');
    });

    $('#wafer').change(function() {
        const selectedWafer = $(this).val();
        selectedValues.Wafer_ID = selectedWafer;
        console.log(selectedValues);
        fetchOptions(selectedValues, $('#parameter_x'), 'parameter_x');
        fetchOptions(selectedValues, $('#parameter_y'), 'parameter_y');
    });

    // Reset button functionality
    $('#resetButton').click(function() {
        $('#criteriaForm')[0].reset();
        $('#work_center, #device_name, #test_program, #lot, #wafer, #parameter_x, #parameter_y').html('');
    });
    
});
</script>

