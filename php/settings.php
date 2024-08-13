<nav class="flex mt-24 ml-16 mb-4" aria-label="Breadcrumb">
  <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
    <li class="inline-flex items-center">
      <a href="selection_page.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
        <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
          <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
        </svg>
        Selection Criteria
      </a>
    </li>
    <li>
      <div class="flex items-center">
        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
        </svg>
        <a href="dashboard.php?<?php echo http_build_query($_GET); ?>" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Extracted Table</a>
      </div>
    </li>
    <li aria-current="page">
      <div class="flex items-center">
        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
        </svg>
        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Graphs</span>
      </div>
    </li>
  </ol>
</nav>
            <div class="fixed top-24 right-4">
                <div class="flex w-full justify-center items-center gap-2">
                <!-- Probe Count Button and Dropdown -->
                <button id="dropdownSearchButtonProbe" data-dropdown-toggle="dropdownSearchProbe" class="inline-flex items-center px-4 py-3 text-sm font-medium text-center text-white bg-red-500 rounded-lg focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800" type="button">
                    <i class="fa-solid fa-gear"></i>
                </button>

                <!-- Probe Count Dropdown menu -->
                <div id="dropdownSearchProbe" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButtonProbe">
                        <li>
                            <div class="flex items-center justify-start p-2 rounded">
                            <span class="text-md font-semibold">Settings</span>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center justify-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                            <div class="flex flex-col items-end w-full">
                            <label for="marginRange" class="text-md font-semibold mb-2">Adjust Range Margin (%)</label>
                            <input type="range" id="marginRange" min="0" max="100" value="10" step="1" class="w-48 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <span id="rangeValue" class="text-sm font-semibold mt-2">5%</span>
                            </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>