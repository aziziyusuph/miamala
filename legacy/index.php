<?php
declare(strict_types=1);

session_start();

/*
 |--------------------------------------------------------------------------
 | Miamala - Single-file PHP Transaction Manager
 |--------------------------------------------------------------------------
 | Default XAMPP database credentials:
 | Host: localhost
 | User: root
 | Password: empty
 |
 | Change these values if your MySQL setup is different.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'miamala_db';
const DB_USER = 'root';
const DB_PASS = '';

$providers = ['M-Pesa', 'Airtel Money', 'Mixx by Yas', 'Bank', 'Cash', 'Other'];
$statuses = ['pending', 'completed', 'failed', 'refunded'];
$categories = ['Sale', 'School Fees', 'Rent', 'Donation', 'Invoice', 'Membership', 'Service', 'Other'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float|string|null $amount): string
{
    return number_format((float) $amount, 2);
}

function redirectHome(array $query = []): never
{
    $url = 'index.php';
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid or expired form token. Refresh the page and try again.');
    }
}

function buildFilters(array $input, array &$params): string
{
    $where = [];

    $search = trim((string) ($input['q'] ?? ''));
    if ($search !== '') {
        $term = '%' . $search . '%';
        $where[] = '(customer_name LIKE :q_customer
            OR phone LIKE :q_phone
            OR transaction_id LIKE :q_transaction
            OR order_reference LIKE :q_order)';
        $params[':q_customer'] = $term;
        $params[':q_phone'] = $term;
        $params[':q_transaction'] = $term;
        $params[':q_order'] = $term;
    }

    $provider = trim((string) ($input['provider'] ?? ''));
    if ($provider !== '') {
        $where[] = 'provider = :provider';
        $params[':provider'] = $provider;
    }

    $status = trim((string) ($input['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $category = trim((string) ($input['category'] ?? ''));
    if ($category !== '') {
        $where[] = 'category = :category';
        $params[':category'] = $category;
    }

    $dateFrom = trim((string) ($input['date_from'] ?? ''));
    if ($dateFrom !== '') {
        $where[] = 'payment_date >= :date_from';
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }

    $dateTo = trim((string) ($input['date_to'] ?? ''));
    if ($dateTo !== '') {
        $where[] = 'payment_date <= :date_to';
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }

    return $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
}

try {
    // Create the database automatically when the MySQL user has permission.
    $serverPdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $serverPdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(120) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            provider VARCHAR(50) NOT NULL,
            transaction_id VARCHAR(100) NULL,
            category VARCHAR(80) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_date DATETIME NOT NULL,
            order_reference VARCHAR(100) NULL,
            expected_amount DECIMAL(15,2) NULL,
            reconciled TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_phone (phone),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_status (status),
            INDEX idx_provider (provider),
            INDEX idx_category (category),
            INDEX idx_payment_date (payment_date),
            INDEX idx_order_reference (order_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit(
        '<h2>Database connection failed</h2>' .
        '<p>Make sure MySQL is running in XAMPP and confirm the database credentials at the top of index.php.</p>' .
        '<pre>' . e($exception->getMessage()) . '</pre>'
    );
}

// Export filtered records before any HTML output.
if (($_GET['export'] ?? '') === 'csv') {
    $params = [];
    $whereSql = buildFilters($_GET, $params);
    $statement = $pdo->prepare(
        'SELECT id, customer_name, phone, provider, transaction_id, category, amount, status,
                payment_date, order_reference, expected_amount, reconciled, notes, created_at
         FROM transactions' . $whereSql . ' ORDER BY payment_date DESC, id DESC'
    );
    $statement->execute($params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="miamala-transactions-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        exit('Unable to create CSV export.');
    }

    // UTF-8 BOM helps Excel display names correctly.
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'ID', 'Customer', 'Phone', 'Provider', 'Transaction ID', 'Category', 'Amount',
        'Status', 'Payment Date', 'Order Reference', 'Expected Amount', 'Reconciled',
        'Notes', 'Created At'
    ]);

    while ($row = $statement->fetch()) {
        $row['reconciled'] = (int) $row['reconciled'] === 1 ? 'Yes' : 'No';
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add_transaction') {
            $customerName = trim((string) ($_POST['customer_name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $provider = trim((string) ($_POST['provider'] ?? ''));
            $transactionId = trim((string) ($_POST['transaction_id'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $amount = (float) ($_POST['amount'] ?? 0);
            $status = trim((string) ($_POST['status'] ?? 'pending'));
            $paymentDateInput = trim((string) ($_POST['payment_date'] ?? ''));
            $orderReference = trim((string) ($_POST['order_reference'] ?? ''));
            $expectedAmountRaw = trim((string) ($_POST['expected_amount'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));

            if ($customerName === '' || $phone === '' || $category === '' || $paymentDateInput === '') {
                throw new RuntimeException('Customer, phone, category and payment date are required.');
            }
            if (!in_array($provider, $providers, true)) {
                throw new RuntimeException('Select a valid payment provider.');
            }
            if (!in_array($status, $statuses, true)) {
                throw new RuntimeException('Select a valid payment status.');
            }
            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $paymentDate = DateTime::createFromFormat('Y-m-d\TH:i', $paymentDateInput);
            if (!$paymentDate) {
                throw new RuntimeException('Enter a valid payment date and time.');
            }

            if ($transactionId !== '') {
                $duplicate = $pdo->prepare('SELECT id FROM transactions WHERE transaction_id = :transaction_id LIMIT 1');
                $duplicate->execute([':transaction_id' => $transactionId]);
                if ($duplicate->fetch()) {
                    throw new RuntimeException('That transaction ID has already been recorded.');
                }
            }

            $expectedAmount = $expectedAmountRaw === '' ? null : (float) $expectedAmountRaw;
            if ($expectedAmount !== null && $expectedAmount <= 0) {
                throw new RuntimeException('Expected order amount must be greater than zero.');
            }

            $reconciled = 0;
            if ($orderReference !== '' && $expectedAmount !== null && abs($amount - $expectedAmount) < 0.01) {
                $reconciled = 1;
            }

            $insert = $pdo->prepare(
                'INSERT INTO transactions
                (customer_name, phone, provider, transaction_id, category, amount, status,
                 payment_date, order_reference, expected_amount, reconciled, notes)
                VALUES
                (:customer_name, :phone, :provider, :transaction_id, :category, :amount, :status,
                 :payment_date, :order_reference, :expected_amount, :reconciled, :notes)'
            );
            $insert->execute([
                ':customer_name' => $customerName,
                ':phone' => $phone,
                ':provider' => $provider,
                ':transaction_id' => $transactionId !== '' ? $transactionId : null,
                ':category' => $category,
                ':amount' => $amount,
                ':status' => $status,
                ':payment_date' => $paymentDate->format('Y-m-d H:i:s'),
                ':order_reference' => $orderReference !== '' ? $orderReference : null,
                ':expected_amount' => $expectedAmount,
                ':reconciled' => $reconciled,
                ':notes' => $notes !== '' ? $notes : null,
            ]);

            setFlash('success', 'Transaction recorded successfully.');
            redirectHome();
        }

        if ($action === 'update_status') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $status = trim((string) ($_POST['status'] ?? ''));
            if (!$id || !in_array($status, $statuses, true)) {
                throw new RuntimeException('Invalid transaction status update.');
            }

            $update = $pdo->prepare('UPDATE transactions SET status = :status WHERE id = :id');
            $update->execute([':status' => $status, ':id' => $id]);
            setFlash('success', 'Transaction status updated.');
            redirectHome($_GET);
        }

        if ($action === 'toggle_reconciled') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('Invalid transaction.');
            }

            $update = $pdo->prepare(
                'UPDATE transactions SET reconciled = CASE WHEN reconciled = 1 THEN 0 ELSE 1 END WHERE id = :id'
            );
            $update->execute([':id' => $id]);
            setFlash('success', 'Reconciliation status updated.');
            redirectHome($_GET);
        }

        if ($action === 'delete_transaction') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('Invalid transaction.');
            }

            $delete = $pdo->prepare('DELETE FROM transactions WHERE id = :id');
            $delete->execute([':id' => $id]);
            setFlash('success', 'Transaction deleted.');
            redirectHome($_GET);
        }

        throw new RuntimeException('Unknown action.');
    } catch (Throwable $exception) {
        setFlash('error', $exception->getMessage());
        redirectHome();
    }
}

$stats = $pdo->query(
    "SELECT
        COALESCE(SUM(CASE WHEN DATE(payment_date) = CURDATE() AND status = 'completed' THEN amount ELSE 0 END), 0) AS today_total,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS completed_total,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_total,
        COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) AS failed_total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
        SUM(CASE WHEN order_reference IS NOT NULL AND reconciled = 0 THEN 1 ELSE 0 END) AS unreconciled_count,
        COUNT(*) AS transaction_count
     FROM transactions"
)->fetch();

$providerTotals = $pdo->query(
    "SELECT provider, COUNT(*) AS transaction_count, COALESCE(SUM(amount), 0) AS total
     FROM transactions
     WHERE DATE(payment_date) = CURDATE() AND status = 'completed'
     GROUP BY provider
     ORDER BY total DESC"
)->fetchAll();

$params = [];
$whereSql = buildFilters($_GET, $params);
$listStatement = $pdo->prepare(
    'SELECT * FROM transactions' . $whereSql . ' ORDER BY payment_date DESC, id DESC LIMIT 250'
);
$listStatement->execute($params);
$transactions = $listStatement->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$activeFilters = $_GET;
unset($activeFilters['export']);
$exportQuery = array_merge($activeFilters, ['export' => 'csv']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miamala - Transaction Manager</title>
    <style>
        :root {
            --primary: #0f766e;
            --primary-dark: #115e59;
            --primary-soft: #ccfbf1;
            --surface: #ffffff;
            --background: #f3f6f8;
            --border: #dbe3e8;
            --text: #17202a;
            --muted: #667085;
            --success: #087443;
            --warning: #a15c00;
            --danger: #b42318;
            --shadow: 0 10px 30px rgba(16, 24, 40, 0.07);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color: inherit; }
        .container { width: min(1450px, 94%); margin: 0 auto; }
        .topbar {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 24px 0;
            box-shadow: var(--shadow);
        }
        .brand-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .brand h1 { margin: 0; font-size: clamp(1.7rem, 3vw, 2.4rem); }
        .brand p { margin: 5px 0 0; color: #d5fffa; }
        .date-chip { background: rgba(255,255,255,.14); padding: 10px 14px; border-radius: 999px; font-size: .9rem; }
        main { padding: 28px 0 50px; }
        .grid { display: grid; gap: 18px; }
        .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        .stat-card { padding: 20px; }
        .stat-label { color: var(--muted); font-size: .88rem; margin-bottom: 8px; }
        .stat-value { font-size: 1.55rem; font-weight: 800; }
        .stat-note { margin-top: 7px; color: var(--muted); font-size: .82rem; }
        .layout { grid-template-columns: 390px minmax(0, 1fr); align-items: start; margin-top: 20px; }
        .section-header { padding: 20px 22px 0; }
        .section-header h2 { margin: 0; font-size: 1.15rem; }
        .section-header p { color: var(--muted); margin: 5px 0 0; font-size: .9rem; }
        .card-body { padding: 20px 22px 22px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
        .field-full { grid-column: 1 / -1; }
        label { display: block; font-size: .82rem; font-weight: 700; margin-bottom: 6px; }
        input, select, textarea {
            width: 100%;
            border: 1px solid #cbd5dc;
            border-radius: 9px;
            padding: 10px 11px;
            font: inherit;
            background: #fff;
            color: var(--text);
            outline: none;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,118,110,.12); }
        textarea { min-height: 80px; resize: vertical; }
        .btn {
            appearance: none;
            border: 0;
            border-radius: 9px;
            padding: 10px 14px;
            font: inherit;
            font-weight: 750;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-light { background: #eef3f5; color: #344054; }
        .btn-danger { background: #fff1f0; color: var(--danger); }
        .btn-small { padding: 7px 9px; font-size: .78rem; }
        .actions-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-card { margin-bottom: 18px; }
        .filters { display: grid; grid-template-columns: 1.4fr repeat(5, minmax(120px, .8fr)); gap: 10px; align-items: end; }
        .filter-actions { display: flex; gap: 8px; }
        .alert { margin: 0 0 18px; padding: 13px 16px; border-radius: 10px; font-weight: 650; }
        .alert-success { background: #eafaf2; color: var(--success); border: 1px solid #b7ebcf; }
        .alert-error { background: #fff1f0; color: var(--danger); border: 1px solid #fecaca; }
        .table-wrap { overflow-x: auto; border-radius: 0 0 16px 16px; }
        table { width: 100%; border-collapse: collapse; min-width: 1150px; }
        th, td { text-align: left; padding: 12px 13px; border-bottom: 1px solid #e8edf0; vertical-align: top; }
        th { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; background: #f9fbfc; }
        td { font-size: .86rem; }
        tr:last-child td { border-bottom: 0; }
        .amount { font-weight: 800; white-space: nowrap; }
        .muted { color: var(--muted); }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 9px; font-size: .74rem; font-weight: 800; white-space: nowrap; }
        .badge-completed, .badge-reconciled { color: #067647; background: #ecfdf3; }
        .badge-pending { color: #93370d; background: #fffaeb; }
        .badge-failed { color: #b42318; background: #fef3f2; }
        .badge-refunded { color: #344054; background: #f2f4f7; }
        .badge-unreconciled { color: #b54708; background: #fff6ed; }
        .badge-unlinked { color: #475467; background: #f2f4f7; }
        .status-select { min-width: 115px; padding: 7px 8px; font-size: .78rem; }
        .inline-form { display: inline-flex; gap: 6px; align-items: center; margin: 0; }
        .provider-summary { padding: 16px 22px 22px; border-top: 1px solid var(--border); }
        .provider-summary h3 { margin: 0 0 10px; font-size: .92rem; }
        .provider-list { display: grid; gap: 8px; }
        .provider-item { display: flex; justify-content: space-between; gap: 12px; font-size: .84rem; }
        .empty { text-align: center; padding: 42px 20px; color: var(--muted); }
        .difference-positive { color: var(--success); }
        .difference-negative { color: var(--danger); }
        .mobile-label { display: none; }

        @media (max-width: 1150px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .layout { grid-template-columns: 1fr; }
            .filters { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 700px) {
            .container { width: min(94%, 100%); }
            .brand-row { align-items: flex-start; flex-direction: column; }
            .stats, .form-grid, .filters { grid-template-columns: 1fr; }
            .field-full { grid-column: auto; }
            .filter-actions { flex-direction: column; }
            .filter-actions .btn { width: 100%; }
            .date-chip { display: none; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="container brand-row">
        <div class="brand">
            <h1>Miamala</h1>
            <p>Simamia malipo yako kwa urahisi.</p>
        </div>
        <div class="date-chip"><?= e(date('l, d F Y')) ?></div>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <section class="grid stats">
        <article class="card stat-card">
            <div class="stat-label">Today's completed payments</div>
            <div class="stat-value">TZS <?= money($stats['today_total']) ?></div>
            <div class="stat-note">Payments completed today</div>
        </article>
        <article class="card stat-card">
            <div class="stat-label">All completed payments</div>
            <div class="stat-value">TZS <?= money($stats['completed_total']) ?></div>
            <div class="stat-note"><?= (int) $stats['transaction_count'] ?> total transactions</div>
        </article>
        <article class="card stat-card">
            <div class="stat-label">Pending payments</div>
            <div class="stat-value">TZS <?= money($stats['pending_total']) ?></div>
            <div class="stat-note"><?= (int) $stats['pending_count'] ?> transaction(s)</div>
        </article>
        <article class="card stat-card">
            <div class="stat-label">Needs reconciliation</div>
            <div class="stat-value"><?= (int) $stats['unreconciled_count'] ?></div>
            <div class="stat-note"><?= (int) $stats['failed_count'] ?> failed payment(s)</div>
        </article>
    </section>

    <section class="grid layout">
        <aside class="card">
            <div class="section-header">
                <h2>Record transaction</h2>
                <p>Add a mobile-money, bank or cash payment.</p>
            </div>
            <div class="card-body">
                <form method="post" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_transaction">

                    <div class="field-full">
                        <label for="customer_name">Customer name *</label>
                        <input id="customer_name" name="customer_name" maxlength="120" required>
                    </div>

                    <div>
                        <label for="phone">Phone number *</label>
                        <input id="phone" name="phone" maxlength="30" placeholder="2557..." required>
                    </div>

                    <div>
                        <label for="provider">Provider *</label>
                        <select id="provider" name="provider" required>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= e($provider) ?>"><?= e($provider) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-full">
                        <label for="transaction_id">Transaction ID</label>
                        <input id="transaction_id" name="transaction_id" maxlength="100" placeholder="Example: QHG8K2ABCD">
                    </div>

                    <div>
                        <label for="amount">Amount (TZS) *</label>
                        <input id="amount" name="amount" type="number" min="1" step="0.01" required>
                    </div>

                    <div>
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= $status === 'completed' ? 'selected' : '' ?>>
                                    <?= e(ucfirst($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category) ?>"><?= e($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="payment_date">Payment date *</label>
                        <input id="payment_date" name="payment_date" type="datetime-local" value="<?= e(date('Y-m-d\TH:i')) ?>" required>
                    </div>

                    <div>
                        <label for="order_reference">Order/reference</label>
                        <input id="order_reference" name="order_reference" maxlength="100" placeholder="ORD-1001">
                    </div>

                    <div>
                        <label for="expected_amount">Expected amount</label>
                        <input id="expected_amount" name="expected_amount" type="number" min="1" step="0.01" placeholder="For reconciliation">
                    </div>

                    <div class="field-full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Optional payment details"></textarea>
                    </div>

                    <button class="btn btn-primary field-full" type="submit">Save transaction</button>
                </form>
            </div>

            <div class="provider-summary">
                <h3>Today's completed totals by provider</h3>
                <div class="provider-list">
                    <?php if ($providerTotals === []): ?>
                        <div class="muted">No completed payments today.</div>
                    <?php else: ?>
                        <?php foreach ($providerTotals as $providerTotal): ?>
                            <div class="provider-item">
                                <span><?= e($providerTotal['provider']) ?> (<?= (int) $providerTotal['transaction_count'] ?>)</span>
                                <strong>TZS <?= money($providerTotal['total']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <div>
            <section class="card filter-card">
                <div class="section-header">
                    <h2>Search and filter</h2>
                    <p>Find payments by customer, phone, transaction ID or order reference.</p>
                </div>
                <div class="card-body">
                    <form method="get" class="filters">
                        <div>
                            <label for="q">Search</label>
                            <input id="q" name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="Phone, transaction ID...">
                        </div>
                        <div>
                            <label for="filter_provider">Provider</label>
                            <select id="filter_provider" name="provider">
                                <option value="">All</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?= e($provider) ?>" <?= (($_GET['provider'] ?? '') === $provider) ? 'selected' : '' ?>><?= e($provider) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="filter_status">Status</label>
                            <select id="filter_status" name="status">
                                <option value="">All</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= e($status) ?>" <?= (($_GET['status'] ?? '') === $status) ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="filter_category">Category</label>
                            <select id="filter_category" name="category">
                                <option value="">All</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category) ?>" <?= (($_GET['category'] ?? '') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="date_from">From</label>
                            <input id="date_from" name="date_from" type="date" value="<?= e((string) ($_GET['date_from'] ?? '')) ?>">
                        </div>
                        <div>
                            <label for="date_to">To</label>
                            <input id="date_to" name="date_to" type="date" value="<?= e((string) ($_GET['date_to'] ?? '')) ?>">
                        </div>
                        <div class="filter-actions field-full">
                            <button class="btn btn-primary" type="submit">Apply filters</button>
                            <a class="btn btn-light" href="index.php">Reset</a>
                            <a class="btn btn-light" href="?<?= e(http_build_query($exportQuery)) ?>">Export CSV</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="section-header" style="padding-bottom: 16px;">
                    <h2>Transactions</h2>
                    <p>Showing <?= count($transactions) ?> record(s). The newest payments appear first.</p>
                </div>
                <div class="table-wrap">
                    <?php if ($transactions === []): ?>
                        <div class="empty">No transactions match the selected filters.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order reconciliation</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <?php
                                    $hasOrder = !empty($transaction['order_reference']);
                                    $isReconciled = (int) $transaction['reconciled'] === 1;
                                    $difference = null;
                                    if ($transaction['expected_amount'] !== null) {
                                        $difference = (float) $transaction['amount'] - (float) $transaction['expected_amount'];
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($transaction['customer_name']) ?></strong><br>
                                        <span class="muted"><?= e($transaction['phone']) ?></span><br>
                                        <span class="muted"><?= e($transaction['category']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= e($transaction['provider']) ?></strong><br>
                                        <span class="muted"><?= e($transaction['transaction_id'] ?: 'No transaction ID') ?></span>
                                    </td>
                                    <td class="amount">TZS <?= money($transaction['amount']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= e($transaction['status']) ?>"><?= e(ucfirst($transaction['status'])) ?></span>
                                        <form method="post" class="inline-form" style="margin-top: 8px;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= e($status) ?>" <?= $transaction['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!$hasOrder): ?>
                                            <span class="badge badge-unlinked">Not linked</span>
                                        <?php else: ?>
                                            <strong><?= e($transaction['order_reference']) ?></strong><br>
                                            <?php if ($transaction['expected_amount'] !== null): ?>
                                                <span class="muted">Expected: TZS <?= money($transaction['expected_amount']) ?></span><br>
                                                <?php if ($difference !== null && abs($difference) >= 0.01): ?>
                                                    <span class="<?= $difference > 0 ? 'difference-positive' : 'difference-negative' ?>">
                                                        Difference: TZS <?= money($difference) ?>
                                                    </span><br>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <span class="badge <?= $isReconciled ? 'badge-reconciled' : 'badge-unreconciled' ?>" style="margin-top: 5px;">
                                                <?= $isReconciled ? 'Reconciled' : 'Needs review' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= e(date('d M Y', strtotime($transaction['payment_date']))) ?><br>
                                        <span class="muted"><?= e(date('H:i', strtotime($transaction['payment_date']))) ?></span>
                                    </td>
                                    <td>
                                        <div class="actions-row">
                                            <?php if ($hasOrder): ?>
                                                <form method="post" class="inline-form">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="toggle_reconciled">
                                                    <input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>">
                                                    <button class="btn btn-light btn-small" type="submit">
                                                        <?= $isReconciled ? 'Mark unreconciled' : 'Reconcile' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this transaction permanently?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete_transaction">
                                                <input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>">
                                                <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </section>
</main>
</body>
</html>
