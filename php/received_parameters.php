<div id="accordion-open" data-accordion="open" class="bg-white shadow-sm">
  <h2 id="accordion-open-heading-1">
    <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right text-gray-500 border border-gray-200 rounded-t-md focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 dark:border-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 gap-3" data-accordion-target="#accordion-open-body-1" aria-expanded="true" aria-controls="accordion-open-body-1">
      <span class="flex items-center"><svg class="w-5 h-5 me-2 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 0 100-2zm0 8a1 1 0 100-2 1 0 000 2z" clip-rule="evenodd"></path></svg> Received Parameters</span>
      <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
      </svg>
    </button>
  </h2>
  <div id="accordion-open-body-1" class="hidden" aria-labelledby="accordion-open-heading-1">
    <div class="p-5 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm grid grid-cols-2">
      <div>
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
          <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Filters:</b></p>
          <div class="mx-4 italic text-xs">
            <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Probe Count:</b> <?php echo implode(', ', $filters['p.abbrev']); ?></p>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Selections:</b></p>
        <?php foreach ($filters as $key => $values): ?>
          <?php if (!empty($values) && $key !== 'p.abbrev'): ?>
            <div class="mx-4 italic text-xs">
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
                <p class="mb-2 text-gray-500 dark:text-gray-400"><b>Test_Name:</b> <?php echo implode(', ', $displayValues); ?></p>
              <?php else: ?>
                <p class="mb-2 text-gray-500 dark:text-gray-400"><b>
                  <?php 
                  // Handle specific cases for display names
                  switch ($key) {
                      case 'l.part_type':
                          echo 'Device Name';
                          break;
                      case 'l.Program_Name':
                          echo 'Test Program';
                          break;
                      case 'l.work_center':
                          echo 'Work Center';
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
    </div>
  </div>
</div>
