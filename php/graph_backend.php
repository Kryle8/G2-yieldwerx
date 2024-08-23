<?php
    require_once('../libs/Database.php');
    
    $database = new Database();
    $conn = $database->connect();

// Ensure parameter_x and parameter_y are always arrays
$parameterX = isset($_GET['parameter_x']) && is_array($_GET['parameter_x']) ? $_GET['parameter_x'] : [];
$parameterY = isset($_GET['parameter_y']) && is_array($_GET['parameter_y']) ? $_GET['parameter_y'] : [];

// Filters from URL parameters
$filters = [
    "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
    "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
    "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
    "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
    "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
    "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
    "tm.Column_Name" => array_merge($parameterX, $parameterY)
];

$xColumn = isset($_GET["group-x"]) ? $_GET["group-x"][0] : null;
$yColumn = isset($_GET["group-y"]) ? $_GET["group-y"][0] : null;
$chartType = isset($_GET["type"]) ? $_GET["type"] : "scatter"; // default scatter chart


// Prepare SQL filters
$sql_filters = [];
$params = [];
foreach ($filters as $key => $values) {
    if (!empty($values)) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $sql_filters[] = "$key IN ($placeholders)";
        $params = array_merge($params, $values);
    }
}

$groupXY = ['x' => isset($_GET["group-x"]) ? $_GET["group-x"][0] : null,
            'y' => isset($_GET["group-y"]) ? $_GET["group-y"][0] : null];

$filterXY = ['x' => isset($_GET['filter-x']) ? $_GET['filter-x'] : [],
             'y' => isset($_GET['filter-y']) ? $_GET['filter-y'] : []];

foreach ($groupXY as $key => $value) {
    if (!empty($value) && !empty($filterXY[$key])) {
        if ($value === "Program_Name") {
            $value = "l.Program_Name";
        } else if ($value === "Probing_Sequence") {
            $value = "p.abbrev";
        }
        
        $placeholders = implode(',', array_fill(0, count($filterXY[$key]), '?'));
        $sql_filters[] = "$value IN ($placeholders)";
        $params = array_merge($params, $filterXY[$key]);
    }
}

// Create the WHERE clause if filters exist
$where_clause = '';
if (!empty($sql_filters)) {
    $where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
}

$join_table_clause = '';

            // get the corresponding table names
            $query = "SELECT distinct tm.Table_Name FROM LOT l
                      JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                      JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                      JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                      $where_clause";
            
            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) { die('Query failed: ' . print_r(sqlsrv_errors(), true)); }
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $tables[] = $row['Table_Name']; }
            sqlsrv_free_stmt($stmt); // Free the count statement here   
            
            // Define the query to get the mappings
            $query = "
            SELECT DISTINCT Program_Name, Table_Name, Column_Name
            FROM TEST_PARAM_MAP
            WHERE Program_Name IN (" . implode(',', array_fill(0, count($filters['l.program_name']), '?')) . ") AND Column_Name IN (" . implode(',', array_fill(0, count($filters['tm.Column_Name']), '?')) . ");
            ";

            $stmt = sqlsrv_query($conn, $query, array_merge($filters['l.program_name'], $filters['tm.Column_Name']));
            if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

            // Initialize arrays
            $tableToProgram = [];
            $columnToTables = [];

            // Process the results
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $programName = $row['Program_Name'];
                $tableName = $row['Table_Name'];
                $columnName = $row['Column_Name'];

                // Populate program-to-tables mapping
                if ($programName && $tableName) {
                    if (!isset($programToTables[$tableName])) {
                        $tableToProgram[$tableName] = [];
                    }
                    if (!in_array($tableName, $tableToProgram[$tableName])) {
                        $tableToProgram[$tableName][] = $programName;
                    }
                }

                // Populate column-to-tables mapping
                if ($columnName && $tableName) {
                    if (!isset($columnToTables[$columnName])) {
                        $columnToTables[$columnName] = [];
                    }
                    if (!in_array($tableName, $columnToTables[$columnName])) {
                        $columnToTables[$columnName][] = $tableName;
                    }
                }
            }

            $joins = [];
            $programTracking = []; // To track the first table in each program

            foreach ($tables as $currentTable) {
                $programName = $tableToProgram[$currentTable][0]; // Get the program name for the current table

                if (!isset($programTracking[$programName])) {
                    // This is the first table in the program
                    $joins[] = "LEFT JOIN " . $currentTable . " ON " . $currentTable . ".Wafer_Sequence = w.Wafer_Sequence AND l.Program_Name = '{$programName}'";
                    $programTracking[$programName] = $currentTable; // Track this table as the first in the program
                } else {
                    // Subsequent table in the same program
                    $joins[] = "LEFT JOIN " . $currentTable . " ON " . $currentTable . ".Die_Sequence = " . $programTracking[$programName] . ".Die_Sequence AND l.Program_Name = '{$programName}'";
                }
            }

            if (!empty($joins)) {
                $join_table_clause = implode("\n", $joins);
            }

            $dynamic_columns = [];
            foreach ($columnToTables as $column => $tables) {
                // Generate COALESCE statement for each column with all specified tables
                $coalesceParts = [];
                foreach ($tables as $table) {
                    $coalesceParts[] = "{$table}.{$column}";
                }
                $dynamic_columns[$column] = count($coalesceParts) === 1 ? $coalesceParts[0] : "COALESCE(" . implode(", ", $coalesceParts) . ")";
            }

$sort_clause = '';
$xy_clauses = [];
if ($xColumn || $yColumn) {
    if ($xColumn) {
        $xy_clauses[] = $xColumn === "Probing_Sequence" ? "p.abbrev" : ($xColumn === "Program_Name" ? "l.Program_Name" : $xColumn) . " " . $_GET["sort-x"];
    }
    if ($yColumn) {
        $xy_clauses[] = $yColumn === "Probing_Sequence" ? "p.abbrev" : ($yColumn === "Program_Name" ? "l.Program_Name" : $yColumn) . " " . $_GET["sort-y"];
    }

    $sort_clause = 'ORDER BY ' . implode(', ', $xy_clauses);
}

// Determine if we are working with one parameter or more
$isSingleParameter = count($filters['tm.Column_Name']) === 1;
$parameters = $filters['tm.Column_Name'];
$data = [];
$groupedData = [];
$globalCounters = [
    'all' => 0,
    'xcol' => [],
    'ycol' => []
];

if ($isSingleParameter) {
    $parameter = $filters['tm.Column_Name'][0];
    $xLabel = 'Count';
    $yLabel = $parameter;

    // Fetch the test_name corresponding to yLabel
    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];
    $testNameX = $xLabel;
    sqlsrv_free_stmt($testNameStmtY);


    $tsql = "
    SELECT 
        w.Wafer_ID, 
        ". $dynamic_columns[$parameter] ." AS Y, 
        " . ($xColumn ? ($xColumn === "Probing_Sequence" ? "p.abbrev" : ($xColumn === "Program_Name" ? "l.Program_Name" : $xColumn))." AS xGroup" : "'No xGroup' AS xGroup") . ", 
        " . ($yColumn ? ($yColumn === "Probing_Sequence" ? "p.abbrev" : ($yColumn === "Program_Name" ? "l.Program_Name" : $yColumn))." AS yGroup" : "'No yGroup' AS yGroup") . "
    FROM LOT l
    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
    $join_table_clause
    $where_clause
    $sort_clause";

    $stmt = sqlsrv_query($conn, $tsql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $xGroup = $row['xGroup'];
        $yGroup = $row['yGroup'];
        $yValue = floatval($row['Y']);

        if ($xColumn && $yColumn) {
            if (!isset($globalCounters['ycol'][$yGroup][$xGroup])) {
                $globalCounters['ycol'][$yGroup][$xGroup] = count($groupedData[$yGroup][$xGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup][$xGroup]++;
            }
            $groupedData[$yGroup][$xGroup][] = ['x' => $globalCounters['ycol'][$yGroup][$xGroup], 'y' => $yValue];
        } elseif ($xColumn && !$yColumn) {
            if (!isset($globalCounters['xcol'][$yGroup][$xGroup])) {
                $globalCounters['xcol'][$yGroup][$xGroup] = count($groupedData[$yGroup][$xGroup] ?? []) + 1;
            } else {
                $globalCounters['xcol'][$yGroup][$xGroup]++;
            }
            $groupedData[$xGroup][$yGroup][] = ['x' => $globalCounters['xcol'][$yGroup][$xGroup], 'y' => $yValue];
        } elseif (!$xColumn && $yColumn) {

            if (!isset($globalCounters['ycol'][$yGroup])) {
                $globalCounters['ycol'][$yGroup] = count($groupedData[$yGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup]++;
            }
            $groupedData[$yGroup][] = ['x' => $globalCounters['ycol'][$yGroup], 'y' => $yValue];
        } else {

            $globalCounters['all']++;
            $groupedData['all'][] = ['x' => $globalCounters['all'], 'y' => $yValue];
        }
    }

    sqlsrv_free_stmt($stmt);
}
else {
    $combinations = [];
    foreach ($filters['tm.Column_Name'] as $i => $parameter) {
        for ($j = $i + 1; $j < count($filters['tm.Column_Name']); $j++) {
            $combinations[] = [$parameter, $filters['tm.Column_Name'][$j]];
        }
    }

    foreach ($combinations as $index => $combination) {
        $xLabel = $combination[0];
        $yLabel = $combination[1];
        
        $combinationKey = implode('_', $combination);

        $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
        $testNameStmtX = sqlsrv_query($conn, $testNameQuery, [$xLabel]);
        $testNameX = sqlsrv_fetch_array($testNameStmtX, SQLSRV_FETCH_ASSOC)['test_name'];

        $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
        $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];

        sqlsrv_free_stmt($testNameStmtX);
        sqlsrv_free_stmt($testNameStmtY);

        $tsql = "
        SELECT 
            ". $dynamic_columns[$xLabel] ." AS X, 
            ". $dynamic_columns[$yLabel] ." AS Y, 
        " . ($xColumn ? ($xColumn === "Probing_Sequence" ? "p.abbrev" : ($xColumn === "Program_Name" ? "l.Program_Name" : $xColumn))." AS xGroup" : "'No xGroup' AS xGroup") . ", 
        " . ($yColumn ? ($yColumn === "Probing_Sequence" ? "p.abbrev" : ($yColumn === "Program_Name" ? "l.Program_Name" : $yColumn))." AS yGroup" : "'No yGroup' AS yGroup") . "
        FROM LOT l
        JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
        JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
        JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
        $join_table_clause
        $where_clause
        $sort_clause";

        $stmt = sqlsrv_query($conn, $tsql, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $xGroup = $row['xGroup'];
            $yGroup = $row['yGroup'];
            $xValue = $row['X'];
            $yValue = $row['Y'];

            if ($xValue && $yValue) {
                if ($xColumn && $yColumn) {
                    $groupedData[$combinationKey][$yGroup][$xGroup][] = ['x' => $xValue, 'y' => $yValue];
                } elseif ($xColumn && !$yColumn) {
                    $groupedData[$combinationKey][$xGroup][$yGroup][] = ['x' => $xValue, 'y' => $yValue];
                } elseif (!$xColumn && $yColumn) {
                    $groupedData[$combinationKey][$yGroup][] = ['x' => $xValue, 'y' => $yValue];
                } else {
                    $groupedData[$combinationKey]['all'][] = ['x' => $xValue, 'y' => $yValue];
                }
            }
        }

        sqlsrv_free_stmt($stmt);
    }
}
$numDistinctGroups = count($groupedData);

?>

<div class="fixed top-24 right-4">
    <div class="flex w-full justify-center items-center gap-2">
        <!-- Probe Count Button and Dropdown -->
        <button id="dropdownRangeMarginButton" data-dropdown-toggle="dropdownRangeMargin" class="inline-flex items-center px-4 py-3 text-sm font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:outline-none focus:ring-blue-300" type="button">
            <i class="fa-solid fa-gear"></i>
        </button>

        <!-- Probe Count Dropdown menu -->
        <div id="dropdownRangeMargin" class="z-10 hidden bg-white rounded-lg shadow w-60">
            <ul class="h-auto px-3 pb-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButtonProbe">
                <li>
                    <div class="flex items-center justify-start p-2 rounded">
                    <span class="text-md font-semibold">Settings</span>
                    </div>
                </li>
                <li>
                    <div class="flex items-center justify-center p-2 rounded hover:bg-gray-100">
                    <div class="flex flex-col items-end w-full">
                    <label for="marginRange" class="text-md font-semibold mb-2">Adjust Range Margin (%)</label>
                    <input type="range" id="marginRange" min="0" max="100" value="10" step="1" class="w-48 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    <span id="rangeValue" class="text-sm font-semibold mt-2">5%</span>
                    </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php
if(!$isSingleParameter){
echo '<h1 class="text-center text-2xl font-bold w-full mb-6">XY Scatter Plot</h1>';
foreach ($combinations as $index => $combination) {
    $combinationKey = implode('_', $combination);
    $data = $groupedData[$combinationKey];
    $xLabel = $combination[0];
    $yLabel = $combination[1];

    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtX = sqlsrv_query($conn, $testNameQuery, [$xLabel]);
    $testNameX = sqlsrv_fetch_array($testNameStmtX, SQLSRV_FETCH_ASSOC)['test_name'];

    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];

    sqlsrv_free_stmt($testNameStmtX);
    sqlsrv_free_stmt($testNameStmtY);

?>
<!-- Iterate this layout -->
<div class="p-4">
    <div class="dark:border-gray-700 flex flex-col items-center">
        <div class="max-w-fit p-6 border-b-2 border-2">
            <div class="mb-4 text-sm italic">
                <?php 
                echo 'Combination of <b>' . $testNameX . '</b> and <b>' . $testNameY . '</b>';
                ?>
            </div>
            <?php
            if (isset($xColumn) && isset($yColumn)) {
                // Both X and Y parameters are set
                echo '<div class="flex flex-row items-center justify-center w-full">
                        <div class="-rotate-90"><h2 class="text-center text-xl font-semibold">'.$yColumn.'</h2></div>
                        <div class="flex flex-col items-center justify-center w-full">';
                $yGroupKeys = array_keys($data);
                $lastYGroup = end($yGroupKeys);
                foreach ($data as $yGroup => $xGroupData) {
                    echo '<div class="flex flex-row items-center justify-center w-full">';
                    echo '<div><h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
                    echo '<div class="grid gap-2 grid-cols-' . count($xGroupData) . '">';

                    foreach ($xGroupData as $xGroup => $data) {
                        echo '<div class="flex items-center justify-center flex-col">';
                        echo '<canvas id="chartXY_' . $combinationKey . '_' . $yGroup . '_' . $xGroup . '"></canvas>';
                        if ($yGroup === $lastYGroup) {
                            echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3>';
                        }
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
                echo '<h3 class="text-center text-lg font-semibold">'.$xColumn.'</h3>
                    </div>
                </div>';
            } elseif (isset($xColumn) && !isset($yColumn)) {
                // Only X parameter is set
                echo '<div class="flex flex-row items-center justify-center w-full">
                        <div class="flex flex-col items-center justify-center w-full">';
                echo '<div class="flex flex-row items-center justify-center w-full">';
                echo '<div class="grid gap-2 grid-cols-' . $numDistinctGroups . '">';
                foreach ($data as $xGroup => $data) {
                    echo '<div class="flex items-center justify-center flex-col">';
                    echo '<canvas id="chartXY_' . $combinationKey . '_' . $xGroup . '"></canvas>';
                    echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3></div>';
                }
                echo '</div></div>';
                echo '<h3 class="text-center text-lg font-semibold">'.$xColumn.'</h3>
                    </div>
                </div>';
            } elseif (!isset($xColumn) && isset($yColumn)) {
                // Only Y parameter is set
                echo '<div class="flex flex-row items-center justify-center w-full">
                        <div class="-rotate-90"><h2 class="text-center text-xl font-semibold">'.$yColumn.'</h2></div>
                        <div class="flex flex-col items-center justify-center w-full">';
                echo '<div class="flex flex-row items-center justify-center w-full">';
                echo '<div class="grid gap-2 grid-cols-1">';
                foreach ($data as $yGroup => $data) {
                    echo '<div class="flex flex-row justify-center items-center">';
                    echo '<div class="text-center">
                        <h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
                    echo '<canvas id="chartXY_' . $combinationKey . '_' . $yGroup . '"></canvas>';
                    echo '</div>';
                }
                echo '</div></div>';
                echo '</div>
                    </div>';
            } else {
                // Neither X nor Y parameters are set
                echo '<div class="flex items-center justify-center w-full">';
                echo '<div><canvas id="chartXY_' . $combinationKey . '_all"></canvas></div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>
<!-- Iterate until here -->
<?php
    }
} else { ?>
<h1 class="text-center text-2xl font-bold w-full mb-6">XY Line Chart</h1>
 <div class="p-4">
    <div class="dark:border-gray-700 flex flex-col items-center">
        <div class="max-w-fit p-6 border-b-2 border-2">
            <div class="mb-4 text-sm italic">
                <?php 
                echo 'Combination of <b>' . $testNameX . '</b>';
                echo ' and <b>' . $testNameY . '</b>';
                ?>
            </div>
            <?php
            if (isset($xColumn) && isset($yColumn)) {
                // Both X and Y parameters are set
                $yGroupKeys = array_keys($groupedData);
                $lastYGroup = end($yGroupKeys);
                foreach ($groupedData as $yGroup => $xGroupData) {
                    echo '<div class="flex flex-row items-center justify-center w-full">';
                    echo '<div><h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
                    echo '<div class="grid gap-2 grid-cols-' . count($xGroupData) . '">';

                    foreach ($xGroupData as $xGroup => $data) {
                        echo '<div class="flex items-center justify-center flex-col">';
                        echo '<canvas id="chartXY_' . $yGroup . '_' . $xGroup . '"></canvas>';
                        if ($yGroup === $lastYGroup) {
                            echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3>';
                        }
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
            } elseif (isset($xColumn) && !isset($yColumn)) {
                // Only X parameter is set
                echo '<div class="flex flex-row items-center justify-center w-full">';
                echo '<div class="grid gap-2 grid-cols-' . $numDistinctGroups . '">';
                foreach ($groupedData as $xGroup => $data) {
                    echo '<div class="flex items-center justify-center flex-col">';
                    echo '<canvas id="chartXY_' . $xGroup . '"></canvas>';
                    echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3></div>';
                }
                echo '</div></div>';
            } elseif (!isset($xColumn) && isset($yColumn)) {
                // Only Y parameter is set
                echo '<div class="flex flex-row items-center justify-center w-full">';
                echo '<div class="grid gap-2 grid-cols-1">';
                foreach ($groupedData as $yGroup => $data) {
                    echo '<div class="flex flex-row justify-center items-center">';
                    echo '<div class="text-center">
                        <h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
                    echo '<canvas id="chartXY_' . $yGroup . '"></canvas>';
                    echo '</div>';
                }
                echo '</div></div>';
            } else {
                // Neither X nor Y parameters are set
                echo '<div class="flex items-center justify-center w-full">';
                echo '<div><canvas id="chartXY_all"></canvas></div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>
<?php }
?>

<script>
    const groupedData = <?php echo json_encode($groupedData); ?>;
    const xLabel = '<?php echo $testNameX; ?>';
    const yLabel = '<?php echo $testNameY; ?>';
    const xColumn = <?php echo json_encode($xColumn); ?>;
    const yColumn = <?php echo json_encode($yColumn); ?>;
    const hasXColumn = <?php echo json_encode(isset($xColumn)); ?>;
    const hasYColumn = <?php echo json_encode(isset($yColumn)); ?>;
    const isSingleParameter = <?php echo json_encode($isSingleParameter); ?>;
    console.log(groupedData);

</script>
<script src="../js/chart_scatter.js"></script>
<?php
sqlsrv_close($conn);
?>