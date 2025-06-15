<?php
// Database connection details
$servername = "localhost"; // e.g., localhost
$username = "root"; // Your MySQL username
$password = "Inno@3702"; // Your MySQL password
$dbname = "sanjaydb"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$symbol = $_POST['symbol'];
$entry_date = $_POST['entry_date'];
$entry_price = $_POST['entry_price'];
$quantity = $_POST['quantity'];
$broker_name = $_POST['broker_name'];
$strategy = $_POST['strategy'];
$stoploss = !empty($_POST['stoploss']) ? $_POST['stoploss'] : null; // Set to null if empty

// Prepare the SQL query to check for existing data for the Symbol
// We need to check if an entry for this symbol already exists to decide between INSERT and UPDATE
$stmt_check = $conn->prepare("SELECT Entry_Date_1, Entry_Price_1, Quantity_1, Entry_Date_2, Entry_Price_2, Quantity_2, Entry_Date_3, Entry_Price_3, Quantity_3, Stoploss FROM tbl_tradeJournal WHERE Symbol = ?");
$stmt_check->bind_param("s", $symbol);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Data for this symbol already exists, proceed with update logic
    $row = $result_check->fetch_assoc();

    $update_fields = [];
    $update_params = [];
    $types = "";

    // Logic to update Entry Date, Entry Price, Quantity
    if (empty($row['Entry_Date_1']) && empty($row['Entry_Price_1']) && empty($row['Quantity_1'])) {
        $update_fields[] = "Entry_Date_1 = ?, Entry_Price_1 = ?, Quantity_1 = ?";
        $update_params[] = $entry_date;
        $update_params[] = $entry_price;
        $update_params[] = $quantity;
        $types .= "sdi";
    } elseif (empty($row['Entry_Date_2']) && empty($row['Entry_Price_2']) && empty($row['Quantity_2'])) {
        $update_fields[] = "Entry_Date_2 = ?, Entry_Price_2 = ?, Quantity_2 = ?";
        $update_params[] = $entry_date;
        $update_params[] = $entry_price;
        $update_params[] = $quantity;
        $types .= "sdi";
    } elseif (empty($row['Entry_Date_3']) && empty($row['Entry_Price_3']) && empty($row['Quantity_3'])) {
        $update_fields[] = "Entry_Date_3 = ?, Entry_Price_3 = ?, Quantity_3 = ?";
        $update_params[] = $entry_date;
        $update_params[] = $entry_price;
        $update_params[] = $quantity;
        $types .= "sdi";
    } else {
        // All three entry slots are filled. You might want to handle this case:
        // - Error message: "All entry slots for this symbol are filled."
        // - Or, update the last one (Entry_Date_3, etc.)
        // For this example, we'll just not update the entry details if all are filled.
        // You can add an error message or alternative logic here.
        echo "<p class='error'>Error: All entry slots for this symbol are filled. Consider adding more columns or handling this differently.</p>";
        $conn->close();
        exit();
    }

    // Always update Stoploss if provided
    if ($stoploss !== null) {
        $update_fields[] = "Stoploss = ?";
        $update_params[] = $stoploss;
        $types .= "d";
    }

    // Update Broker Name and Strategy if provided (or you can decide to only update if not null)
    if (!empty($broker_name)) {
        $update_fields[] = "Broker_Name = ?";
        $update_params[] = $broker_name;
        $types .= "s";
    }
    if (!empty($strategy)) {
        $update_fields[] = "Strategy = ?";
        $update_params[] = $strategy;
        $types .= "s";
    }

    if (!empty($update_fields)) {
        $sql_update = "UPDATE tbl_tradeJournal SET " . implode(", ", $update_fields) . " WHERE Symbol = ?";
        $stmt_update = $conn->prepare($sql_update);

        // Add symbol to update parameters
        $update_params[] = $symbol;
        $types .= "s";

        // Dynamically bind parameters
        $stmt_update->bind_param($types, ...$update_params);

        if ($stmt_update->execute()) {
            echo "<p class='success'>Record updated successfully!</p>";
        } else {
            echo "<p class='error'>Error updating record: " . $stmt_update->error . "</p>";
        }
        $stmt_update->close();
    } else {
        echo "<p class='message'>No updates made for existing symbol (no empty entry slots or stoploss/broker/strategy not provided).</p>";
    }

} else {
    // No existing data for this symbol, insert a new row
    $sql_insert = "INSERT INTO tbl_tradeJournal (Symbol, Entry_Date_1, Entry_Price_1, Quantity_1, Broker_Name, Strategy, Stoploss) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssdissd", $symbol, $entry_date, $entry_price, $quantity, $broker_name, $strategy, $stoploss);

    if ($stmt_insert->execute()) {
        echo "<p class='success'>New record created successfully!</p>";
    } else {
        echo "<p class='error'>Error: " . $sql_insert . "<br>" . $conn->error . "</p>";
    }
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();
?>