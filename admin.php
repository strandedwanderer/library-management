<?php
include 'connect.php';

session_start();
// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.html');
    exit;
}

// Add a new user
if (isset($_POST['add_user'])) {
    $user_id = $_POST['user_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $book_bank_access = isset($_POST['book_bank_access']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("
            INSERT INTO user (user_id, fname, lname, email, phone, address, password, book_bank_access) 
            VALUES (:user_id, :fname, :lname, :email, :phone, :address, :password, :book_bank_access)
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'password' => $password,
            'book_bank_access' => $book_bank_access
        ]);
        echo "<div class='success'>User added successfully!</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error adding user: " . $e->getMessage() . "</div>";
    }
}

// Delete a user
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // First delete all records for this user
        $stmt = $conn->prepare("DELETE FROM record WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        
        // Then delete the user
        $stmt = $conn->prepare("DELETE FROM user WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        
        // Commit transaction
        $conn->commit();
        echo "<div class='success'>User and all associated records deleted successfully!</div>";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "<div class='error'>Error deleting user: " . $e->getMessage() . "</div>";
    }
}

// Add a new book with additional details
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher_id = $_POST['publisher_id'];
    $isbn = $_POST['isbn'];
    $category = $_POST['category'];
    $publication_date = $_POST['publication_date'];
    $available = isset($_POST['available']) ? 1 : 0;

    // Check if the publisher_id exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM publisher WHERE publisher_id = :publisher_id");
    $stmt->execute(['publisher_id' => $publisher_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO books (title, author, publisher_id, isbn, category, publication_date, available) 
                                  VALUES (:title, :author, :publisher_id, :isbn, :category, :publication_date, :available)");
            $stmt->execute([
                'title' => $title,
                'author' => $author,
                'publisher_id' => $publisher_id,
                'isbn' => $isbn,
                'category' => $category,
                'publication_date' => $publication_date,
                'available' => $available
            ]);
            echo "<div class='success'>Book added successfully!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>Error adding book: " . $e->getMessage() . "</div>";
        }
    } else {
        header('Location: publisher_management.php');
        exit;
    }
}

if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher_id = $_POST['publisher_id'];
    $isbn = $_POST['isbn'];
    $category = $_POST['category'];
    $publication_date = $_POST['publication_date'];
    $available = isset($_POST['available']) ? 1 : 0;
    
    // Check if the publisher exists
    if (!empty($publisher_id)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM publisher WHERE publisher_id = :publisher_id");
        $stmt->execute(['publisher_id' => $publisher_id]);
        if ($stmt->fetchColumn() == 0) {
            echo "<div class='error'>Invalid publisher ID. Please add the publisher first.</div>";
            exit;
        }
    }

    try {
        $stmt = $conn->prepare("UPDATE books SET 
            title = :title, 
            author = :author, 
            publisher_id = :publisher_id,
            isbn = :isbn,
            category = :category,
            publication_date = :publication_date,
            available = :available
            WHERE book_id = :book_id");
        
        $stmt->execute([
            'title' => $title,
            'author' => $author,
            'publisher_id' => $publisher_id,
            'isbn' => $isbn,
            'category' => $category,
            'publication_date' => $publication_date,
            'available' => $available,
            'book_id' => $book_id
        ]);
        echo "<div class='success'>Book updated successfully!</div>";
    } catch (PDOException $e) {
        header('Location: publisher_management.php');
        exit;
    }
}

// Delete a book
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // First delete all records for this book
        $stmt = $conn->prepare("DELETE FROM record WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $book_id]);
        
        // Then delete the book
        $stmt = $conn->prepare("DELETE FROM books WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $book_id]);
        
        // Commit transaction
        $conn->commit();
        echo "<div class='success'>Book and all associated records deleted successfully!</div>";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "<div class='error'>Error deleting book: " . $e->getMessage() . "</div>";
    }
}

// Fetch all books with complete details
$books_stmt = $conn->prepare("
    SELECT b.book_id, b.title, b.author, p.name as publisher_name, 
           b.isbn, b.category, b.publication_date, b.available,
           p.publisher_id
    FROM books b 
    JOIN publisher p ON b.publisher_id = p.publisher_id 
    ORDER BY b.book_id");
$books_stmt->execute();
$all_books = $books_stmt->fetchAll();

// Fetch all users with complete details
$users_stmt = $conn->prepare("
SELECT user_id, fname, lname, email, phone, address, book_bank_access 
FROM user 
ORDER BY user_id
");
$users_stmt->execute();
$all_users = $users_stmt->fetchAll();

// Fetch all publishers with complete details
$publishers_stmt = $conn->prepare("
    SELECT publisher_id, name 
    FROM publisher 
    ORDER BY publisher_id");
$publishers_stmt->execute();
$all_publishers = $publishers_stmt->fetchAll();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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
            background-color: #e6ffe6;
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .data-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .data-table tr:hover {
            background-color: #f0f0f0;
        }

        .section-divider {
            margin: 40px 0;
            border-top: 2px solid #eee;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .form-grid input, .form-grid select {
            width: 100%;
            padding: 8px;
            margin: 4px 0;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
                
        .full-width {
            grid-column: 1 / -1;
        }
        
        .button-group {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
        }

        
    </style>
</head>
<body>
    <h1>Welcome, Admin</h1>

    <!-- User Management -->
    <section>
        <h2>Manage Users</h2>
        <!-- Add User Form -->
        <form method="POST" class="form-grid" onsubmit="return validatePassword()">
            <h3 class="full-width">Add New User</h3>
            <input type="text" name="user_id" placeholder="User ID" required>
            <input type="text" name="fname" placeholder="First Name" required>
            <input type="text" name="lname" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="password" name="password" placeholder="Password" required 
                   pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" 
                   title="Password must be at least 8 characters long and contain at least one letter and one number">
            <div class="checkbox-container">
                <input type="checkbox" name="book_bank_access">
                <span>Book Bank Access</span>
            </div>
            <div class="button-group">
                <button type="submit" name="add_user">Add User</button>
            </div>
        </form>

    <!-- Delete User Form -->
    <form method="POST" class="form-grid">
            <h3 class="full-width">Delete User</h3>
            <input type="text" name="user_id" placeholder="User ID" required>
            <div class="button-group">
                <button type="submit" name="delete_user">Delete User</button>
            </div>
        </form>
    </section>

    <!-- Book Management -->
    <section>
        <h2>Manage Books</h2>
        <form method="POST" class="form-grid">
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="isbn" placeholder="ISBN" required>
            <select name="category" required>
                <option value="">Select Genre</option>
                <option value="fantasy">Fantasy</option>
                <option value="educational">Educational</option>
                <option value="mythology">Mythology</option>
            </select>
            <input type="date" name="publication_date" required>
            <input type="text" name="publisher_id" placeholder="Publisher ID" required>
            <input type="text" name="book_id" placeholder="Book ID (for update/delete)">
            <div class="checkbox-container">
                <input type="checkbox" name="available" checked>
                <span>Available for Borrowing</span>
            </div>
            <div class="button-group">
                <button type="submit" name="add_book">Add Book</button>
                <button type="submit" name="update_book">Update Book</button>
                <button type="submit" name="delete_book">Delete Book</button>
            </div>
        </form>
    </section>

    <div class="section-divider"></div>

    <!-- Display All Books -->
    <section>
        <h2>All Books</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Publication Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['book_id']); ?></td>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($book['category']); ?></td>
                    <td><?php echo htmlspecialchars($book['publication_date']); ?></td>
                    <td><?php echo $book['available'] ? 'Available' : 'Not Available'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

     <!-- Display All Users -->
     <section>
        <h2>All Users</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Book Bank Access</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['fname']); ?></td>
                    <td><?php echo htmlspecialchars($user['lname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['address']); ?></td>
                    <td><?php echo $user['book_bank_access'] ? 'Yes' : 'No'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

 <!-- Display All Publishers -->
    <section>
        <h2>All Publishers</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Publisher ID</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_publishers as $publisher): ?>
                <tr>
                    <td><?php echo htmlspecialchars($publisher['publisher_id']); ?></td>
                    <td><?php echo htmlspecialchars($publisher['name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="navigation">
    <a href="index.html" class="back-button">Back to Login Page</a>
</section>

<script>
function validatePassword() {
    const password = document.querySelector('input[name="password"]').value;
    const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
    
    if (!pattern.test(password)) {
        alert('Password must be at least 8 characters long and contain at least one letter and one number');
        return false;
    }
    return true;
}
</script>
</body>
</html>