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

    // POST /loans/return
    public function returnBook()
    {
        // TODO: Implement logic to process the return of a book and calculate fines if overdue.
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Return book functionality to be implemented.']);
    }
}
