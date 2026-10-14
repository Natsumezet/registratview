<?php
/* ============================================================
   students.php
   Displays every registered student from the `student` table.
   Uses the project's existing connection: require_once 'db.php';
   ============================================================ */

require_once 'db.php';

/* ---------- 1. Fetch the records ---------- */
$students = array();
$dbError  = "";

try {
    $sql = "SELECT studid, lname, fname, mname, sex, bdate, address
            FROM student
            ORDER BY lname ASC, fname ASC";

    $result = $conn->query($sql);

    // query() returns false on failure (when not throwing an exception)
    if ($result === false) {
        throw new Exception($conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $result->free();

} catch (Throwable $e) {
    // Never show raw DB errors to the user; keep it friendly
    $dbError = $e->getMessage();
}

$total = count($students);

/* Small helper: safe output */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Small helper: pretty date, falls back to the raw value */
function formatDate($value) {
    if (empty($value)) {
        return '&mdash;';
    }
    $ts = strtotime($value);
    return $ts ? date('M j, Y', $ts) : e($value);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Students &mdash; Nova Bright Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --violet: #7c3aed;
            --pink:   #ec4899;
            --orange: #f97316;
            --cyan:   #06b6d4;
            --ink:    #1e1b4b;
            --muted:  #6b7280;
            --line:   #e5e7eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 32px 16px 56px;
            font-family: "Poppins", "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: linear-gradient(130deg, #4c1d95, #7c3aed 35%, #db2777 68%, #f97316);
            background-size: 220% 220%;
            animation: drift 18s ease infinite;
        }

        @keyframes drift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ---------- HEADER ---------- */
        .hero {
            padding: 26px 28px;
            border-radius: 26px 26px 8px 8px;
            color: #fff;
            background: linear-gradient(135deg, rgba(124,58,237,.85), rgba(236,72,153,.85));
            border: 1px solid rgba(255, 255, 255, .28);
            border-bottom: none;
            box-shadow: 0 18px 40px rgba(30, 27, 75, .35);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .hero h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: .4px;
            text-shadow: 0 3px 14px rgba(0, 0, 0, .3);
        }

        .hero p {
            margin: 6px 0 0;
            font-size: 13px;
            color: rgba(255, 255, 255, .9);
        }

        .count-pill {
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            border-radius: 99px;
            white-space: nowrap;
            color: #fff;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .4);
        }

        /* ---------- CARD ---------- */
        .card {
            background: #fff;
            border-radius: 8px 8px 26px 26px;
            padding: 26px 24px 30px;
            box-shadow: 0 26px 60px rgba(30, 27, 75, .38);
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .toolbar h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .btn {
            display: inline-block;
            padding: 11px 20px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .3px;
            text-decoration: none;
            color: #fff;
            border-radius: 12px;
            background: linear-gradient(120deg, var(--violet), var(--pink) 55%, var(--orange));
            box-shadow: 0 12px 24px rgba(236, 72, 153, .35);
            transition: transform .15s, box-shadow .15s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(124, 58, 237, .45);
        }

        /* ---------- TABLE ---------- */
        .table-scroll {
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 780px;
        }

        thead th {
            text-align: left;
            padding: 14px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #3730a3;
            background: linear-gradient(135deg, #ede9fe, #fce7f3);
            border-bottom: 1px solid #ddd6fe;
            white-space: nowrap;
        }

        tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #faf9ff; }
        tbody tr:hover { background: #f3f0ff; }

        .id-cell {
            font-weight: 700;
            color: var(--violet);
        }

        .sex-pill {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 11px;
            border-radius: 99px;
            background: #ede9fe;
            color: #5b21b6;
        }

        .empty,
        .error {
            margin: 0;
            padding: 34px 22px;
            text-align: center;
            border-radius: 14px;
            font-size: 15px;
        }

        .empty {
            color: var(--muted);
            background: #f8f7ff;
            border: 1px dashed #c4b5fd;
        }

        .error {
            color: #9f1239;
            background: #fff1f2;
            border: 1px solid #fecdd3;
        }

        .error code {
            display: block;
            margin-top: 10px;
            font-size: 12px;
            color: #be123c;
            word-break: break-word;
        }

        footer {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: rgba(255, 255, 255, .85);
        }

        /* ---------- RESPONSIVE: turn rows into cards on small screens ---------- */
        @media (max-width: 700px) {
            .hero h1 { font-size: 21px; }
            .table-scroll { border: none; overflow-x: visible; }

            table { min-width: 0; }
            thead { display: none; }

            tbody tr {
                display: block;
                margin-bottom: 14px;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 6px 16px rgba(30, 27, 75, .08);
                overflow: hidden;
            }

            tbody tr:nth-child(even) { background: #fff; }

            tbody td {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                padding: 10px 14px;
                border-bottom: 1px solid var(--line);
            }

            tbody td:last-child { border-bottom: none; }

            tbody td::before {
                content: attr(data-label);
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: #6d28d9;
                flex: 0 0 42%;
            }
        }
    </style>

    <!-- ===== Muted palette overrides: calm, low-brightness ===== -->
    <style>
        :root {
            --violet: #3f6b6b;   /* muted teal */
            --pink:   #a5707a;   /* muted rose */
            --orange: #b08d6e;   /* muted clay */
            --cyan:   #5d8a8a;   /* muted sea  */
            --ink:    #26333d;
            --muted:  #6b7885;
            --line:   #e3e8ea;
        }
        body {
            background: linear-gradient(135deg, #2f4858, #3d5a63 45%, #486b6b 100%);
            background-size: 220% 220%;
        }
        .hero { background: linear-gradient(135deg, rgba(63,107,107,.94), rgba(47,72,88,.94)); }
        .btn {
            background: linear-gradient(120deg, #3f6b6b, #4e7a7a 55%, #b08d6e);
            box-shadow: 0 12px 24px rgba(63,107,107,.30);
        }
        .btn:hover { box-shadow: 0 16px 30px rgba(47,72,88,.40); }
        thead th {
            color: #2f4858;
            background: linear-gradient(135deg, #eaf0ef, #f3ece6);
            border-bottom-color: #d3dedc;
        }
        tbody tr:nth-child(even) { background: #f7fafa; }
        tbody tr:hover { background: #eef4f4; }
        .sex-pill { background: #eaf0ef; color: #2f4858; }
        .empty { background: #f6f9f9; border-color: #b9cfcf; }
        .error { color: #8f3d4a; background: #fbeeee; border-color: #e7c6c9; }
        .error code { color: #8f3d4a; }
        tbody td::before { color: #2f4858; }
        .hero { box-shadow: 0 18px 40px rgba(38,51,61,.35); }
        .card { box-shadow: 0 26px 60px rgba(38,51,61,.35); }
    </style>
</head>
<body>
    <div class="wrap">

        <!-- ===== HEADER ===== -->
        <header class="hero">
            <div>
                <h1>&#127891; Registered Students</h1>
                <p>Nova Bright Academy &mdash; Student Enrollment System</p>
            </div>
            <span class="count-pill"><?php echo $total; ?> record<?php echo $total === 1 ? '' : 's'; ?></span>
        </header>

        <!-- ===== CONTENT ===== -->
        <main class="card">
            <div class="toolbar">
                <h2>Student List</h2>
                <a class="btn" href="index.php">&#43; Register New Student</a>
            </div>

            <?php if ($dbError !== ""): ?>

                <p class="error">
                    <strong>Sorry, we couldn&rsquo;t load the student records.</strong>
                    <code><?php echo e($dbError); ?></code>
                </p>

            <?php elseif ($total === 0): ?>

                <p class="empty">
                    No students have been registered yet.<br>
                    <a class="btn" style="margin-top:16px;" href="index.php">Register the first student</a>
                </p>

            <?php else: ?>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Sex</th>
                                <th>Birth Date</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td data-label="Student ID" class="id-cell"><?php echo e($s['studid']); ?></td>
                                    <td data-label="Last Name"><?php echo e($s['lname']); ?></td>
                                    <td data-label="First Name"><?php echo e($s['fname']); ?></td>
                                    <td data-label="Middle Name"><?php echo e($s['mname']); ?></td>
                                    <td data-label="Sex"><span class="sex-pill"><?php echo e($s['sex']); ?></span></td>
                                    <td data-label="Birth Date"><?php echo formatDate($s['bdate']); ?></td>
                                    <td data-label="Address"><?php echo e($s['address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </main>

        <footer>&copy; <?php echo date("Y"); ?> Nova Bright Academy &mdash; Student Enrollment System</footer>
    </div>
</body>
</html>
