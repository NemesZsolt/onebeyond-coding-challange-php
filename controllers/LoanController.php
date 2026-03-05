<?php

require_once __DIR__ . '/../data/data.php';
require_once __DIR__ . '/../models/BookStock.php';
require_once __DIR__ . '/../models/Fine.php';

class LoanController
{
    /**
     * Implement logic to list active loans with borrower and book details.
     *
     * GET /loans
     * @return void
     */
    public function index()
    {
        global $bookStocks, $borrowers, $books;

        $activeLoans = array_filter($bookStocks, function ($stock) {
            return $stock->isOnLoan;
        });

        $result = [];

        foreach ($activeLoans as $stock) {
            // Find borrower
            $borrower = current(array_filter($borrowers, fn($b) => $b->id === $stock->borrowerId));

            // Find book
            $book = current(array_filter($books, fn($b) => $b->id == $stock->bookId));

            // Skip inconsistent data
            if (!$borrower || !$book) continue;

            $result[] = [
                'borrower' => [
                    'id' => $borrower->id,
                    'name' => $borrower->name,
                    'email' => $borrower->email
                ],
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author_id' => $book->authorId,
                    'isbn' => $book->isbn,
                    'format' => $book->format
                ],
                'loan_end_date' => $stock->loanEndDate,
                'stock_id' => $stock->id
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Implement logic to process the return of a book and calculate fines if overdue.
     *
     * POST /loans/return
     * @return void
     */
    public function returnBook()
    {
        global $bookStocks, $fines;

        $stockId = $_POST['stock_id'] ?? null;

        if (!$stockId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing stock_id']);
            return;
        }

        // Find the stock record
        $stockIndex = null;
        foreach ($bookStocks as $index => $stock) {
            if ($stock->id == $stockId) {
                $stockIndex = $index;
                break;
            }
        }

        if ($stockIndex === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Stock not found']);
            return;
        }

        $stock = $bookStocks[$stockIndex];

        if (!$stock->isOnLoan) {
            http_response_code(400);
            echo json_encode(['error' => 'Book is not currently on loan']);
            return;
        }

        // Calculate overdue fine (if any)
        $today = date('Y-m-d');
        $fineAmount = 0;
        if ($stock->loanEndDate && $stock->loanEndDate < $today) {
            $end = new DateTime($stock->loanEndDate);
            $now = new DateTime($today);
            $daysOverdue = $end->diff($now)->days;
            $fineAmount = $daysOverdue * 1; //1 per day
        }

        // Create fine record if overdue
        if ($fineAmount > 0) {
            $newFineId = empty($fines) ? 1 : max(array_column($fines, 'id')) + 1;
            $fines[] = new Fine(
                $newFineId,
                $stock->borrowerId,
                $fineAmount,
                "Overdue return of stock ID $stockId (due $stock->loanEndDate)"
            );
        }

        // Mark the book as returned
        $stock->isOnLoan = false;
        $stock->loanEndDate = null;
        $stock->borrowerId = null;

        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Book returned successfully',
            'fine_charged' => $fineAmount
        ]);
    }
}
