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
?>

<style>
    select:not([size]) {
        background: white !important;
    }
</style>
<div class="container mx-auto p-6">
    <h1 class="text-center text-2xl font-bold mb-4 w-full text-cyan-700">Selection Criteria</h1>
    <form action="dashboard.php" method="GET" id="criteriaForm">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="facility" class="block text-sm font-bold text-cyan-700">Facility</label>
                <select id="facility" name="facility[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility ?>"><?= $facility ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="work_center" class="block text-sm font-bold text-cyan-700">Work Center</label>
                <select id="work_center" name="work_center[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on facility selection -->
                </select>
            </div>

            <div>
                <label for="device_name" class="block text-sm font-bold text-cyan-700">Device Name</label>
                <select id="device_name" name="device_name[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on work center selection -->
                </select>
            </div>

            <div>
                <label for="test_program" class="block text-sm font-bold text-cyan-700">Test Program</label>
                <select id="test_program" name="test_program[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on device name selection -->
                </select>
            </div>

            <div>
                <label for="lot" class="block text-sm font-bold text-cyan-700">Lot</label>
                <select id="lot" name="lot[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on test program selection -->
                </select>
            </div>

            <div>
                <label for="wafer" class="block text-sm font-bold text-cyan-700">Wafer</label>
                <select id="wafer" name="wafer[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on lot selection -->
                </select>
            </div>

        </div>
        
        <div class="grid grid-cols-1 gap-0 mb-0"></div>

            <!-- Parameter selection -->
            <div>
                <label for="parameter" class="block text-sm font-bold text-cyan-700">Parameter</label>
                <select id="parameter" name="parameter[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on wafer selection -->
                </select>
            </div>
            


        <div class="text-right w-full flex justify-end gap-4">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded mt-3">Select</button>
            <button type="button" id="resetButton" class="px-4 py-2 bg-gray-500 text-white rounded mt-3">Reset</button>
        </div>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
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
                    // Sort the response based on numeric value after 'T'
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

    // Event listeners for each select element
    $('#facility').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#work_center'), 'work_center');
    });

    $('#work_center').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#device_name'), 'device_name');
    });

    $('#device_name').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#test_program'), 'test_program');
    });

    $('#test_program').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#lot'), 'lot');
    });

    $('#lot').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#wafer'), 'wafer');
    });

    $('#wafer').change(function() {
        let selectedValue = $(this).val();
        fetchOptions(selectedValue, $('#parameter'), 'parameter');
    });

    // Reset button functionality
    $('#resetButton').click(function() {
        // Clear all the selections except facility
        $('#work_center').html('');
        $('#device_name').html('');
        $('#test_program').html('');
        $('#lot').html('');
        $('#wafer').html('');
        $('#parameter').html('');

        // Reset the facility selection
        $('#facility').val(null).trigger('change');
    });
});
</script>
