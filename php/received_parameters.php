<div class="relative flex justify-end">
  <!-- Dropdown Button -->
  <button id="dropdownSelectionsButton" data-dropdown-toggle="dropdownSelections" class="flex items-center justify-end p-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-100">
    Selections
    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.7-3.7a.75.75 0 111.06 1.06l-4 4a.75.75 0 01-1.06 0l-4-4a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>
    </svg>
  </button>

  <!-- Dropdown Menu -->
  <div id="dropdownSelections" class="hidden z-10 w-64 p-3 bg-white rounded-lg shadow dark:bg-gray-800 absolute right-0">
    <div class="text-sm text-gray-700 dark:text-gray-200">
      <!-- Selections Section -->
      <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Selections:</b></p>
      <div class="grid grid-cols-1 gap-y-2">
        <?php foreach ($filters as $key => $values): ?>
          <?php if (!empty($values) && $key !== 'p.abbrev'): ?>
            <div class="italic text-xs">
              <?php if ($key === 'tm.Column_Name'): ?>
                <?php
                // Query the corresponding Test_Name for each Column_Name in the filters
                $displayValues = [];
                foreach ($values as $columnName) {
                    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
                    $stmt = sqlsrv_query($conn, $testNameQuery, [$columnName]);
                    $testName = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['test_name'];
                    $displayValues[] = "$testName";
                    sqlsrv_free_stmt($stmt);
                }
                ?>
                <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Test Name:</b> <?php echo implode(', ', $displayValues); ?></p>
              <?php else: ?>
                <p class="mb-2 text-gray-500 dark:text-gray-400"><b>
                  <?php 
                  switch ($key) {
                      case 'l.facility_id':
                          echo 'Facility_ID';
                          break;
                      case 'l.work_center':
                          echo 'Work Center';
                          break;
                      case 'l.part_type':
                          echo 'Device Name';
                          break;
                      case 'l.Program_Name':
                          echo 'Test Program';
                          break;
                      case 'l.Lot_ID':
                          echo 'Lot_ID';
                          break;
                      case 'l.Wafer_ID':
                          echo 'Wafer ID';
                          break;
                      default:
                          echo preg_replace('/^[^\.]*\./', '', $key);
                  }
                  ?>:</b> <?php echo implode(', ', $values); ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <!-- Group By and Order By Sections -->
      <?php if ($xIndex !== null): ?>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Group by (X):</b> <?php echo preg_replace('/^[^\.]*\./', '', $columns[$xIndex]); ?></p>
      <?php endif; ?>
      <?php if ($yIndex !== null): ?>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Group by (Y):</b> <?php echo preg_replace('/^[^\.]*\./', '', $columns[$yIndex]); ?></p>
      <?php endif; ?>
      <?php if ($orderX !== null): ?>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Order by (X):</b> <?php echo $orderX == 0 ? 'Ascending' : 'Descending'; ?></p>
      <?php endif; ?>
      <?php if ($orderY !== null): ?>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Order by (Y):</b> <?php echo $orderY == 0 ? 'Ascending' : 'Descending'; ?></p>
      <?php endif; ?>
      <?php if (!empty($filters['p.abbrev'])): ?>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Filter by:</b></p>
        <div class="italic text-xs">
          <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Probe Count:</b> <?php echo implode(', ', $filters['p.abbrev']); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // Toggle dropdown visibility
  document.getElementById('dropdownSelectionsButton').addEventListener('click', function () {
    const dropdown = document.getElementById('dropdownSelections');
    dropdown.classList.toggle('hidden');
  });
</script>
