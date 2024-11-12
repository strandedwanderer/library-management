<?php
include 'connect.php';

session_start();
// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.html');
    exit;
}

// Add a new publisher
if (isset($_POST['add_publisher'])) {
    $name = $_POST['name'];
    $year_of_publication = $_POST['year_of_publication'];

    try {
        $stmt = $conn->prepare("INSERT INTO publisher (name, year_of_publication) VALUES (:name, :year_of_publication)");
        $stmt->execute(['name' => $name, 'year_of_publication' => $year_of_publication]);
        echo "<div class='success'>Publisher added successfully!</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error adding publisher: " . $e->getMessage() . "</div>";
    }
}

// Update publisher information
if (isset($_POST['update_publisher'])) {
    $publisher_id = $_POST['publisher_id'];
    $name = $_POST['name'];
    $year_of_publication = $_POST['year_of_publication'];

    try {
        $stmt = $conn->prepare("UPDATE publisher SET name = :name, year_of_publication = :year_of_publication WHERE publisher_id = :publisher_id");
        $stmt->execute(['name' => $name, 'year_of_publication' => $year_of_publication, 'publisher_id' => $publisher_id]);
        echo "<div class='success'>Publisher updated successfully!</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error updating publisher: " . $e->getMessage() . "</div>";
    }
}

// Delete a publisher
if (isset($_POST['delete_publisher'])) {
    $publisher_id = $_POST['publisher_id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // First delete all books for this publisher
        $stmt = $conn->prepare("DELETE FROM books WHERE publisher_id = :publisher_id");
        $stmt->execute(['publisher_id' => $publisher_id]);

        // Then delete the publisher
        $stmt = $conn->prepare("DELETE FROM publisher WHERE publisher_id = :publisher_id");
        $stmt->execute(['publisher_id' => $publisher_id]);

        // Commit transaction
        $conn->commit();
        echo "<div class='success'>Publisher and all associated books deleted successfully!</div>";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "<div class='error'>Error deleting publisher: " . $e->getMessage() . "</div>";
    }
}

// Fetch all publishers
$stmt = $conn->prepare("SELECT * FROM publisher");
$stmt->execute();
$publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error {
            color: red;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid red;
            background-color: #ffe6e6;
        }
        .success {
            color: green;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid green;
            background-color: #666;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="admin.php" class="back-button">Back to Admin Panel</a>

    <h1>Publisher Management</h1>

    <!-- Add Publisher -->
    <section>
        <h2>Add Publisher</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Publisher Name" required>
            <input type="text" name="year_of_publication" placeholder="Year of Publication" required>
            <button type="submit" name="add_publisher">Add Publisher</button>
        </form>
    </section>

    <!-- Update Publisher -->
    <section>
        <h2>Update Publisher</h2>
        <form method="POST">
            <input type="text" name="publisher_id" placeholder="Publisher ID" required>
            <input type="text" name="name" placeholder="Publisher Name" required>
            <input type="text" name="year_of_publication" placeholder="Year of Publication" required>
            <button type="submit" name="update_publisher">Update Publisher</button>
        </form>
    </section>

    <!-- Delete Publisher -->
    <section>
        <h2>Delete Publisher</h2>
        <form method="POST">
            <input type="text" name="publisher_id" placeholder="Publisher ID" required>
            <button type="submit" name="delete_publisher">Delete Publisher</button>
        </form>
    </section>

    <!-- List of Publishers -->
    <section>
        <h2>All Publishers</h2>
        <table>
            <thead>
                <tr>
                    <th>Publisher ID</th>
                    <th>Name</th>
                    <th>Year of Publication</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($publishers as $publisher): ?>
                <tr>
                    <td><?= $publisher['publisher_id'] ?></td>
                    <td><?= $publisher['name'] ?></td>
                    <td><?= $publisher['year_of_publication'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>