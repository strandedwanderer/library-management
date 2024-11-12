<?php
include 'connect.php';

session_start();
// Check if the user is logged in
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.html');
    exit;
}

// Retrieve user's book bank access
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT book_bank_access FROM user WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch();
$book_bank_access = $user['book_bank_access'];

// Borrow a book
if (isset($_POST['borrow_book'])) {
    $book_id = $_POST['book_id'];
    $borrow_date = $_POST['borrow_date'];
    
    // Validate if the book is available
    $check_stmt = $conn->prepare("SELECT available FROM books WHERE book_id = :book_id");
    $check_stmt->execute(['book_id' => $book_id]);
    $book = $check_stmt->fetch();

    if ($book && $book['available']) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Calculate due date based on book bank access
            $due_date = $book_bank_access ? 
                date('Y-m-d', strtotime($borrow_date . ' +1 year')) : 
                date('Y-m-d', strtotime($borrow_date . ' +3 months'));

            // Insert the borrow record
            $stmt = $conn->prepare("
                INSERT INTO record (user_id, book_id, borrow_date, due_date) 
                VALUES (:user_id, :book_id, :borrow_date, :due_date)
            ");
            $stmt->execute([
                'user_id' => $user_id, 
                'book_id' => $book_id, 
                'borrow_date' => $borrow_date, 
                'due_date' => $due_date
            ]);

            // Update book availability
            $update_stmt = $conn->prepare("
                UPDATE books SET available = 0 
                WHERE book_id = :book_id
            ");
            $update_stmt->execute(['book_id' => $book_id]);

            // Commit transaction
            $conn->commit();
            echo "<div class='success'>Book borrowed successfully!</div>";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            echo "<div class='error'>Error borrowing book: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>Book is not available for borrowing</div>";
    }
}

// Display borrowed books
$books_stmt = $conn->prepare("SELECT books.title, record.borrow_date, record.due_date FROM record JOIN books ON record.book_id = books.book_id WHERE record.user_id = :user_id");
$books_stmt->execute(['user_id' => $user_id]);
$borrowed_books = $books_stmt->fetchAll();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
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

            .borrow-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 600px;
            margin: 20px 0;
        }
        
        .borrow-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .borrow-form button {
            grid-column: 1 / -1;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .borrow-form button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            display: none;
            grid-column: 1 / -1;
        }
        }
    </style>
</head>
<body>
    <h1>Welcome, User</h1>

    <!-- Borrow Book -->
    <section>
        <h2>Borrow a Book</h2>
        <form method="POST" class="borrow-form" id="borrowForm">
            <input type="text" name="book_id" placeholder="Book ID" required>
            <input type="date" name="borrow_date" required 
                   min="<?php echo date('Y-m-d'); ?>" 
                   value="<?php echo date('Y-m-d'); ?>">
            <div id="dateError" class="error-message">Borrow date cannot be in the past</div>
            <button type="submit" name="borrow_book">Borrow Book</button>
        </form>
    </section>

    <!-- View Borrowed Books -->
    <section>
        <h2>Your Borrowed Books</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowed_books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($book['due_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- All Available Books -->
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

    <section class="navigation">
    <a href="index.html" class="back-button">Back to Login Page</a>
</section>

<script>
        document.getElementById('borrowForm').addEventListener('submit', function(e) {
            const borrowDate = new Date(document.getElementsByName('borrow_date')[0].value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (borrowDate < today) {
                e.preventDefault();
                document.getElementById('dateError').style.display = 'block';
            }
        });
    </script>

</body>
</html>
