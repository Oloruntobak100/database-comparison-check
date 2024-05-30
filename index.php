<?php

// Database configurations
$db1 = array(
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'Casablanca@kay49',
    'database' => 'db_comparison1'
);

$db2 = array(
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'Casablanca@kay49',
    'database' => 'db_comparison2'
);

// Table names
$table1 = 'expenditure';
$table2 = 'expenditure';

// Function to query the database and get the total records in a specific table
function getTableRecords($db, $table) {
    // Connect to the database
    $conn = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to get the total records in the table
    $sql = "SELECT COUNT(*) AS count FROM $table";
    $result = $conn->query($sql);

    // Fetch the result
    $row = $result->fetch_assoc();
    $count = $row['count'];

    // Get the table size (in KiB and MB)
    $tableSizes = getTableSize($conn, $table);

    // Close the connection
    $conn->close();

    // Return the result as JSON
    return json_encode(array("DB_records_count" => $count, "DB_table_size_KiB" => $tableSizes['size_kib'] . " KiB", "DB_table_size_MB" => $tableSizes['size_mb'] . " MB"));
}

// Function to get the size of a table in KiB and MB
function getTableSize($conn, $table) {
    // Query to get the size of the table in bytes
    $sql = "SELECT SUM(data_length + index_length) AS size_bytes FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = '$table'";
    $result = $conn->query($sql);

    // Fetch the result
    $row = $result->fetch_assoc();
    $sizeBytes = $row['size_bytes'];

    // Convert size to KiB and MB
    $sizeKiB = round($sizeBytes / 1024, 2);
    $sizeMB = round($sizeBytes / 1024 / 1024, 2);

    return array('size_kib' => $sizeKiB, 'size_mb' => $sizeMB);
}

// Function to compare the records and sizes of two tables
function compareTables($db1, $table1, $db2, $table2) {
    // Get the record count and table size for table1 in db1
    $result1 = json_decode(getTableRecords($db1, $table1), true);
    $count1 = $result1['DB_records_count'];
    $size1KiB = $result1['DB_table_size_KiB'];
    $size1MB = $result1['DB_table_size_MB'];

    // Get the record count and table size for table2 in db2
    $result2 = json_decode(getTableRecords($db2, $table2), true);
    $count2 = $result2['DB_records_count'];
    $size2KiB = $result2['DB_table_size_KiB'];
    $size2MB = $result2['DB_table_size_MB'];

    // Compare the record counts
    $statusCount = ($count1 == $count2) ? 1 : 2;

    // Compare the table sizes in KiB and MB
    $statusSizeKiB = ($size1KiB == $size2KiB) ? 1 : 2;
    $statusSizeMB = ($size1MB == $size2MB) ? 1 : 2;

    // Prepare the comparison result
    $comparisonResult = array(
        $table1 => array(
            "record_comparison" => array(
                "status" => $statusCount,
                "msg" => "The $table1 table in DB1 has $count1 records and the $table2 table in DB2 has $count2 records."
            ),
            "size_comparison" => array(
                "KiB" => array(
                    "status" => $statusSizeKiB,
                    "msg" => "The $table1 table in DB1 is $size1KiB and the $table2 table in DB2 is $size2KiB KiB."
                ),
                "MB" => array(
                    "status" => $statusSizeMB,
                    "msg" => "The $table1 table in DB1 is $size1MB and the $table2 table in DB2 is $size2MB MB."
                )
            )
        )
    );

    // Return the comparison result as JSON
    return json_encode($comparisonResult);
}

// Compare tables and output the result
echo compareTables($db1, $table1, $db2, $table2);

?>
