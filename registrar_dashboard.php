<?php
/* ============================================================
   registrar_dashboard.php
   Registrar Dashboard for Nova Bright Academy
   Reuses the project's existing connection: require_once 'db.php';

   Modules with real data:
     1. Student Information Management
     2. Course Management
     4. Scheduling Tools (from the `subject` table)
     6. Reporting & Analytics
   Modules without supporting tables are shown as "not yet available"
   instead of displaying fake data.
   ============================================================ */

require_once 'db.php';

/* ---------- Small helpers ---------- */
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Return the first column of the first row (or a default). */
function q1($conn, $sql, $default = 0) {
    try {
        $r = $conn->query($sql);
        if ($r) {
            $row = $r->fetch_row();
            $r->free();
            return $row[0];
        }
    } catch (Throwable $x) {
        /* ignore - caller falls back to default */
    }
    return $default;
}

/* Return all rows as an array of associative arrays. */
function qall($conn, $sql) {
    $out = array();
    try {
        $r = $conn->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $out[] = $row;
            }
            $r->free();
        }
    } catch (Throwable $x) {
        /* ignore */
    }
    return $out;
}

function ageFrom($bdate) {
    if (empty($bdate)) { return '&mdash;'; }
    try {
        $d = date_diff(date_create($bdate), date_create('today'));
        return $d->y;
    } catch (Throwable $x) {
        return '&mdash;';
    }
}

/* ---------- Key metrics ---------- */
$totalStudents    = (int) q1($conn, "SELECT COUNT(*) FROM student");
$totalCourses     = (int) q1($conn, "SELECT COUNT(*) FROM course");
$totalSubjects    = (int) q1($conn, "SELECT COUNT(*) FROM subject");
$totalInstructors = (int) q1($conn, "SELECT COUNT(DISTINCT instructor) FROM subject WHERE instructor IS NOT NULL AND instructor <> ''");
$totalEnrolled    = (int) q1($conn, "SELECT COUNT(DISTINCT studid) FROM enrolled");
$enrollRows       = (int) q1($conn, "SELECT COUNT(*) FROM enrolled");
$notEnrolled      = max(0, $totalStudents - $totalEnrolled);
$enrollRate       = $totalStudents > 0 ? round($totalEnrolled / $totalStudents * 100) : 0;

/* Current school year / semester */
$sy  = qall($conn, "SELECT schoolyear, semid FROM schoolyear ORDER BY syid DESC LIMIT 1");
$sem = qall($conn, "SELECT semester FROM semester ORDER BY semid DESC LIMIT 1");
$currentSY  = $sy  ? $sy[0]['schoolyear']  : '&mdash;';
$currentSem = $sem ? $sem[0]['semester']   : '&mdash;';

/* ---------- Data lists ---------- */
$students = qall($conn,
    "SELECT s.studid, s.lname, s.fname, s.mname, s.sex, s.bdate, s.address, s.email,
            cs.coursename AS chosen_course,
            (SELECT COUNT(*) FROM enrolled e WHERE e.studid = s.studid) AS enroll_count
     FROM student s
     LEFT JOIN course cs ON cs.courseid = s.courseid
     ORDER BY s.lname ASC, s.fname ASC");

$courses = qall($conn,
    "SELECT c.courseid, c.coursename, c.code,
            COUNT(DISTINCT s.subjid) AS subjects,
            (SELECT COUNT(DISTINCT e.studid) FROM enrolled e WHERE e.courseid = c.courseid) AS enrolled
     FROM course c
     LEFT JOIN subject s ON s.courseid = c.courseid
     GROUP BY c.courseid, c.coursename, c.code
     ORDER BY c.coursename ASC");

$sexCounts = qall($conn,
    "SELECT sex, COUNT(*) AS c FROM student GROUP BY sex ORDER BY c DESC");
$sexTotal  = 0;
foreach ($sexCounts as $r) { $sexTotal += (int)$r['c']; }

$enrollByCourse = qall($conn,
    "SELECT c.coursename, COUNT(e.studid) AS c
     FROM course c
     LEFT JOIN enrolled e ON e.courseid = c.courseid
     GROUP BY c.courseid, c.coursename
     ORDER BY c DESC, c.coursename ASC");
$maxCourse = 0;
foreach ($enrollByCourse as $r) { $maxCourse = max($maxCourse, (int)$r['c']); }

$byYearLevel = qall($conn,
    "SELECT yearlevel, COUNT(*) AS c FROM enrolled GROUP BY yearlevel ORDER BY yearlevel ASC");

/* ---------- Class schedules (with optional course filter) ---------- */
$filterCourse = isset($_GET['course']) ? (int)$_GET['course'] : 0;   // cast to int = safe
$schedWhere   = $filterCourse > 0 ? " WHERE s.courseid = " . $filterCourse : "";

$schedule = qall($conn,
    "SELECT s.code, s.title, s.units, s.yearlevel, s.day, s.room, s.instructor,
            TIME_FORMAT(s.time_start, '%h:%i %p') AS t_start,
            TIME_FORMAT(s.time_end,   '%h:%i %p') AS t_end,
            c.coursename
     FROM subject s
     JOIN course c ON c.courseid = s.courseid
     $schedWhere
     ORDER BY c.coursename ASC, s.yearlevel ASC, s.code ASC
     LIMIT 500");

$courseFilterList = qall($conn,
    "SELECT courseid, coursename FROM course ORDER BY coursename ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard &mdash; Nova Bright Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:   #0f172a;
            --navy-2: #1e293b;
            --indigo: #4f46e5;
            --violet: #7c3aed;
            --cyan:   #06b6d4;
            --green:  #16a34a;
            --amber:  #f59e0b;
            --rose:   #e11d48;
            --ink:    #1e293b;
            --muted:  #64748b;
            --line:   #e2e8f0;
            --bg:     #f1f5f9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Poppins", "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        /* ---------- LAYOUT ---------- */
        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            flex: 0 0 250px;
            background: linear-gradient(180deg, var(--navy), var(--navy-2));
            color: #e2e8f0;
            padding: 22px 16px;
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px 18px;
            border-bottom: 1px solid rgba(255,255,255,.12);
            margin-bottom: 16px;
        }

        .brand .crest {
            width: 42px; height: 42px;
            display: grid; place-items: center;
            font-size: 22px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--indigo), var(--cyan));
        }

        .brand b { display: block; font-size: 14px; line-height: 1.2; }
        .brand span { font-size: 11px; color: #94a3b8; }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            margin-bottom: 2px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
            text-decoration: none;
            transition: background .15s, color .15s;
        }

        .nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav a .ico { width: 18px; text-align: center; }

        .content { flex: 1 1 auto; min-width: 0; padding: 26px 30px 60px; }

        /* ---------- TOP BAR ---------- */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .topbar h1 { margin: 0; font-size: 24px; font-weight: 800; }
        .topbar p  { margin: 3px 0 0; font-size: 13px; color: var(--muted); }

        .term-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            color: #312e81;
            background: #e0e7ff;
            border: 1px solid #c7d2fe;
            white-space: nowrap;
        }

        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .3px;
            text-decoration: none;
            color: #fff;
            white-space: nowrap;
            border-radius: 12px;
            background: linear-gradient(120deg, var(--indigo), var(--violet));
            box-shadow: 0 10px 22px rgba(79, 70, 229, .35);
            transition: transform .15s, box-shadow .15s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(79, 70, 229, .45);
        }

        /* ---------- CARDS / KPI ---------- */
        .kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .kpi {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px 18px 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .05);
        }

        .kpi .k-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .kpi .k-value {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.1;
            margin: 8px 0 2px;
            color: var(--navy);
        }

        .kpi .k-sub { font-size: 12px; color: var(--muted); }
        .kpi .k-bar { height: 6px; border-radius: 99px; background: #eef2ff; margin-top: 12px; overflow: hidden; }
        .kpi .k-bar i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--indigo), var(--cyan)); }

        /* ---------- SECTIONS ---------- */
        section { margin-bottom: 34px; scroll-margin-top: 20px; }

        .sec-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .sec-head .num {
            width: 30px; height: 30px;
            flex: 0 0 30px;
            display: grid; place-items: center;
            font-size: 13px; font-weight: 700; color: #fff;
            border-radius: 9px;
            background: linear-gradient(135deg, var(--indigo), var(--violet));
        }

        .sec-head h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .sec-head p  { margin: 2px 0 0; font-size: 12.5px; color: var(--muted); }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .05);
        }

        /* ---------- TABLE ---------- */
        .table-scroll { overflow-x: auto; border-radius: 12px; border: 1px solid var(--line); }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.wide { min-width: 760px; }

        thead th {
            text-align: left;
            padding: 12px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #3730a3;
            background: #eef2ff;
            border-bottom: 1px solid #c7d2fe;
            white-space: nowrap;
        }

        tbody td { padding: 11px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eef2ff; }

        .tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            background: #e0e7ff;
            color: #3730a3;
        }
        .tag.ok  { background: #dcfce7; color: #166534; }
        .tag.off { background: #fee2e2; color: #991b1b; }

        .empty { padding: 26px; text-align: center; color: var(--muted); font-size: 14px; }

        /* ---------- CHART BARS ---------- */
        .bars { display: grid; gap: 12px; }
        .bar-row { display: grid; grid-template-columns: 190px 1fr 44px; align-items: center; gap: 12px; font-size: 13px; }
        .bar-row .b-name { color: var(--ink); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bar-track { height: 14px; border-radius: 99px; background: #eef2ff; overflow: hidden; }
        .bar-track i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--indigo), var(--cyan)); }
        .bar-row .b-val { text-align: right; font-weight: 700; color: var(--navy); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

        /* ---------- PLACEHOLDER ---------- */
        .soon {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
        }
        .soon .soon-ico {
            width: 40px; height: 40px; flex: 0 0 40px;
            display: grid; place-items: center;
            border-radius: 10px;
            background: #e2e8f0; color: #475569; font-size: 18px;
        }
        .soon b { display: block; font-size: 14px; }
        .soon p { margin: 4px 0 0; font-size: 12.5px; color: var(--muted); line-height: 1.55; }
        .soon code { background: #eef2ff; padding: 1px 6px; border-radius: 5px; color: #3730a3; font-size: 12px; }

        .filter-bar {
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .filter-bar label { font-size: 12.5px; font-weight: 600; color: #3730a3; }
        .filter-bar select {
            padding: 9px 12px; font-family: inherit; font-size: 13px;
            border: 1.5px solid var(--line); border-radius: 10px; background: #fff; color: var(--ink);
        }
        .filter-bar .btn {
            padding: 9px 16px; font-family: inherit; font-size: 13px; font-weight: 700;
            border: none; border-radius: 10px; cursor: pointer; color: #fff;
            background: linear-gradient(120deg, var(--indigo), var(--violet));
            text-decoration: none;
        }

        .mini-note { font-size: 12px; color: var(--muted); margin-top: 10px; }

        footer.page-foot { margin-top: 10px; font-size: 12px; color: var(--muted); text-align: center; }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 780px) {
            .layout { flex-direction: column; }
            .sidebar {
                flex: none; width: 100%; height: auto; position: static;
                padding: 14px 14px 8px;
            }
            .brand { padding-bottom: 12px; margin-bottom: 10px; }
            .nav { display: flex; flex-wrap: wrap; gap: 4px; }
            .nav a { margin-bottom: 0; padding: 8px 10px; font-size: 12px; }
            .content { padding: 18px 16px 44px; }
            .bar-row { grid-template-columns: 120px 1fr 36px; }
        }
    </style>

    <!-- ===== Muted palette overrides: calm, low-brightness ===== -->
    <style>
        :root {
            --navy:   #26333d;   /* deep slate  */
            --navy-2: #2f3f4a;
            --indigo: #3f6b6b;   /* muted teal  */
            --violet: #4e7a7a;
            --cyan:   #5d8a8a;   /* muted sea   */
            --green:  #6f9179;   /* muted sage  */
            --amber:  #b08d6e;   /* muted clay  */
            --rose:   #a5707a;   /* muted rose  */
            --ink:    #26333d;
            --muted:  #6b7885;
            --line:   #e3e8ea;
            --bg:     #eef2f2;
        }
        .term-badge { color: #2f4858; background: #e6efee; border-color: #cbdcdb; }
        .btn-primary { box-shadow: 0 10px 22px rgba(63,107,107,.30); }
        .btn-primary:hover { box-shadow: 0 14px 28px rgba(47,72,88,.40); }
        .kpi .k-bar { background: #eaf0ef; }
        thead th { color: #2f4858; background: #eaf0ef; border-bottom-color: #d3dedc; }
        tbody tr:nth-child(even) { background: #f8fafa; }
        tbody tr:hover { background: #eaf0ef; }
        .tag { background: #e6efee; color: #2f4858; }
        .tag.ok { background: #e4efe6; color: #3f6b52; }
        .tag.off { background: #f3e2e4; color: #8f3d4a; }
        .bar-track { background: #eaf0ef; }
        .soon { border-color: #cbd5d5; background: #f6f9f9; }
        .soon code { background: #eaf0ef; color: #2f4858; }
        .filter-bar label { color: #2f4858; }
    </style>
</head>
<body>
<div class="layout">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar">
        <div class="brand">
            <span class="crest">&#127891;</span>
            <span>
                <b>Nova Bright Academy</b>
                <span>Registrar Dashboard</span>
            </span>
        </div>
        <nav class="nav">
            <a href="#students"><span class="ico">&#128101;</span> Student Information</a>
            <a href="#courses"><span class="ico">&#128218;</span> Course Management</a>
            <a href="#academic"><span class="ico">&#127891;</span> Academic Records</a>
            <a href="#scheduling"><span class="ico">&#128197;</span> Scheduling Tools</a>
            <a href="#communication"><span class="ico">&#128172;</span> Communication</a>
            <a href="#analytics"><span class="ico">&#128202;</span> Reporting &amp; Analytics</a>
            <a href="#financial"><span class="ico">&#128176;</span> Financial Info</a>
            <a href="#compliance"><span class="ico">&#9878;</span> Compliance</a>
            <a href="#security"><span class="ico">&#128274;</span> Access &amp; Security</a>
            <a href="#help"><span class="ico">&#10067;</span> Help &amp; Support</a>
        </nav>
    </aside>

    <!-- ================= CONTENT ================= -->
    <main class="content">

        <div class="topbar">
            <div>
                <h1>Registrar Dashboard</h1>
                <p>Overview of student and academic data &mdash; generated <?php echo e(date('M j, Y g:i A')); ?></p>
            </div>
            <div class="top-actions">
                <span class="term-badge">&#128197; SY <?php echo e($currentSY); ?> &bull; <?php echo e($currentSem); ?></span>
                <a class="btn-primary" href="index.php">&#10133; Register Student</a>
            </div>
        </div>

        <!-- ===== KPI CARDS ===== -->
        <div class="kpis">
            <div class="kpi">
                <div class="k-label">Total Students</div>
                <div class="k-value"><?php echo e($totalStudents); ?></div>
                <div class="k-sub"><?php echo e($notEnrolled); ?> not yet enrolled</div>
                <div class="k-bar"><i style="width:100%"></i></div>
            </div>
            <div class="kpi">
                <div class="k-label">Active Enrollments</div>
                <div class="k-value"><?php echo e($totalEnrolled); ?></div>
                <div class="k-sub"><?php echo e($enrollRate); ?>% of students</div>
                <div class="k-bar"><i style="width:<?php echo (int)$enrollRate; ?>%"></i></div>
            </div>
            <div class="kpi">
                <div class="k-label">Courses Offered</div>
                <div class="k-value"><?php echo e($totalCourses); ?></div>
                <div class="k-sub">degree programs</div>
                <div class="k-bar"><i style="width:100%"></i></div>
            </div>
            <div class="kpi">
                <div class="k-label">Class Subjects</div>
                <div class="k-value"><?php echo e($totalSubjects); ?></div>
                <div class="k-sub"><?php echo e($totalInstructors); ?> instructors</div>
                <div class="k-bar"><i style="width:100%"></i></div>
            </div>
        </div>

        <!-- ============ 1. STUDENT INFORMATION MANAGEMENT ============ -->
        <section id="students">
            <div class="sec-head">
                <span class="num">1</span>
                <div>
                    <h2>Student Information Management</h2>
                    <p>Student profiles, enrollment status and current admissions</p>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom:18px;">
                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Enrollment Status</div>
                    <div class="bars">
                        <div class="bar-row">
                            <span class="b-name">Enrolled this term</span>
                            <span class="bar-track"><i style="width:<?php echo (int)$enrollRate; ?>%"></i></span>
                            <span class="b-val"><?php echo e($totalEnrolled); ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="b-name">Not yet enrolled</span>
                            <span class="bar-track"><i style="width:<?php echo $totalStudents > 0 ? (int)round($notEnrolled / $totalStudents * 100) : 0; ?>%"></i></span>
                            <span class="b-val"><?php echo e($notEnrolled); ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="b-name">Total records</span>
                            <span class="bar-track"><i style="width:100%"></i></span>
                            <span class="b-val"><?php echo e($totalStudents); ?></span>
                        </div>
                    </div>
                    <p class="mini-note">New-admission and withdrawal trends need an enrollment-date/status column, which the <code>student</code> table does not track yet.</p>
                </div>

                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Demographics &mdash; Sex</div>
                    <?php if ($sexTotal === 0): ?>
                        <p class="empty">No student records.</p>
                    <?php else: ?>
                        <div class="bars">
                            <?php foreach ($sexCounts as $s):
                                $pct = $sexTotal > 0 ? round($s['c'] / $sexTotal * 100) : 0; ?>
                                <div class="bar-row">
                                    <span class="b-name"><?php echo e($s['sex'] !== '' ? $s['sex'] : 'Unspecified'); ?></span>
                                    <span class="bar-track"><i style="width:<?php echo (int)$pct; ?>%"></i></span>
                                    <span class="b-val"><?php echo e($s['c']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel" style="padding:0;overflow:hidden;">
                <div class="table-scroll">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>ID</th><th>Name</th><th>Sex</th><th>Age</th>
                                <th>Birth Date</th><th>Email</th><th>Address</th>
                                <th>Course</th><th>Enrollment</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="9" class="empty">No students registered yet.</td></tr>
                        <?php else: foreach ($students as $st):
                            $courseName = $st['chosen_course'] ?: '&mdash;'; ?>
                            <tr>
                                <td><b>#<?php echo e($st['studid']); ?></b></td>
                                <td><?php echo e(trim($st['lname'] . ', ' . $st['fname'] . ' ' . $st['mname'])); ?></td>
                                <td><?php echo e($st['sex']); ?></td>
                                <td><?php echo ageFrom($st['bdate']); ?></td>
                                <td><?php echo e($st['bdate']); ?></td>
                                <td><?php echo $st['email'] !== '' ? e($st['email']) : '&mdash;'; ?></td>
                                <td><?php echo e($st['address']); ?></td>
                                <td><?php echo $st['chosen_course'] ? e($courseName) : '&mdash;'; ?></td>
                                <td>
                                    <?php if ((int)$st['enroll_count'] > 0): ?>
                                        <span class="tag ok">Enrolled</span>
                                    <?php else: ?>
                                        <span class="tag off">Not enrolled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ============ 2. COURSE MANAGEMENT ============ -->
        <section id="courses">
            <div class="sec-head">
                <span class="num">2</span>
                <div>
                    <h2>Course Management</h2>
                    <p>Courses offered and how many students are enrolled in each</p>
                </div>
            </div>

            <div class="panel" style="padding:0;overflow:hidden;">
                <div class="table-scroll">
                    <table class="wide">
                        <thead>
                            <tr><th>Course ID</th><th>Course</th><th>Code</th><th>Subjects</th><th>Enrolled Students</th></tr>
                        </thead>
                        <tbody>
                        <?php if (count($courses) === 0): ?>
                            <tr><td colspan="5" class="empty">No courses found.</td></tr>
                        <?php else: foreach ($courses as $c): ?>
                            <tr>
                                <td><b>#<?php echo e($c['courseid']); ?></b></td>
                                <td><?php echo e($c['coursename']); ?></td>
                                <td><?php echo $c['code'] !== '' && $c['code'] !== null ? e($c['code']) : '&mdash;'; ?></td>
                                <td><?php echo e($c['subjects']); ?></td>
                                <td>
                                    <?php if ((int)$c['enrolled'] > 0): ?>
                                        <span class="tag ok"><?php echo e($c['enrolled']); ?> enrolled</span>
                                    <?php else: ?>
                                        <span class="tag">none</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ============ 3. ACADEMIC RECORDS ============ -->
        <section id="academic">
            <div class="sec-head">
                <span class="num">3</span>
                <div>
                    <h2>Academic Records</h2>
                    <p>Transcripts, GPA and degree audits</p>
                </div>
            </div>
            <div class="panel">
                <div class="soon">
                    <span class="soon-ico">&#128196;</span>
                    <div>
                        <b>Not yet available</b>
                        <p>Grades, GPA and degree audits require a grades table (e.g. <code>grade</code> or <code>academic_record</code> linking <code>studid</code> &rarr; <code>subjid</code> &rarr; grade). That table does not exist in <code>nova_bright_academy</code> yet, so no transcript data can be shown.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 4. SCHEDULING TOOLS ============ -->
        <section id="scheduling">
            <div class="sec-head">
                <span class="num">4</span>
                <div>
                    <h2>Scheduling Tools</h2>
                    <p>Class schedules with room and instructor details (from the <b>subject</b> table)</p>
                </div>
            </div>

            <div class="panel">
                <form class="filter-bar" method="get" action="registrar_dashboard.php#scheduling">
                    <label for="course">Filter by course</label>
                    <select id="course" name="course">
                        <option value="0">All courses</option>
                        <?php foreach ($courseFilterList as $cf): ?>
                            <option value="<?php echo (int)$cf['courseid']; ?>" <?php echo $filterCourse === (int)$cf['courseid'] ? 'selected' : ''; ?>>
                                <?php echo e($cf['coursename']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">Apply</button>
                    <span class="mini-note" style="margin:0;">Showing <?php echo count($schedule); ?> class schedule<?php echo count($schedule) === 1 ? '' : 's'; ?>.</span>
                </form>

                <div class="table-scroll">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>Code</th><th>Subject</th><th>Course</th><th>Year</th>
                                <th>Day</th><th>Time</th><th>Room</th><th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($schedule) === 0): ?>
                            <tr><td colspan="8" class="empty">No class schedules found for this selection.</td></tr>
                        <?php else: foreach ($schedule as $sc): ?>
                            <tr>
                                <td><b><?php echo e($sc['code']); ?></b></td>
                                <td><?php echo e($sc['title']); ?></td>
                                <td><?php echo e($sc['coursename']); ?></td>
                                <td><?php echo e($sc['yearlevel']); ?></td>
                                <td><?php echo e($sc['day']); ?></td>
                                <td><?php echo e($sc['t_start'] . ' - ' . $sc['t_end']); ?></td>
                                <td><?php echo $sc['room'] !== null && $sc['room'] !== '' ? e($sc['room']) : '&mdash;'; ?></td>
                                <td><?php echo $sc['instructor'] !== null && $sc['instructor'] !== '' ? e($sc['instructor']) : '&mdash;'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="soon" style="margin-top:16px;">
                    <span class="soon-ico">&#128221;</span>
                    <div>
                        <b>Exam schedules &mdash; not yet available</b>
                        <p>There is no exam table in the database. An <code>exam_schedule</code> table would be needed to list exam dates, locations and times.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 5. COMMUNICATION TOOLS ============ -->
        <section id="communication">
            <div class="sec-head">
                <span class="num">5</span>
                <div>
                    <h2>Communication Tools</h2>
                    <p>Notifications and messaging</p>
                </div>
            </div>
            <div class="panel">
                <div class="soon" style="margin-bottom:14px;">
                    <span class="soon-ico">&#128276;</span>
                    <div>
                        <b>Active term</b>
                        <p>Current academic term: <b>SY <?php echo e($currentSY); ?>, <?php echo e($currentSem); ?></b>. Registration deadlines and calendar events would appear here once an announcements/events table is added.</p>
                    </div>
                </div>
                <div class="soon">
                    <span class="soon-ico">&#128172;</span>
                    <div>
                        <b>Messaging system &mdash; not yet available</b>
                        <p>Sending messages to students, faculty and staff requires a <code>message</code> table and user accounts, neither of which exists in the database yet.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 6. REPORTING & ANALYTICS ============ -->
        <section id="analytics">
            <div class="sec-head">
                <span class="num">6</span>
                <div>
                    <h2>Reporting &amp; Analytics</h2>
                    <p>Enrollment metrics computed live from the database</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Enrollment by Course</div>
                    <?php if ($maxCourse === 0): ?>
                        <p class="empty">No enrollments recorded yet.</p>
                    <?php else: ?>
                        <div class="bars">
                            <?php foreach ($enrollByCourse as $r):
                                $w = $maxCourse > 0 ? round((int)$r['c'] / $maxCourse * 100) : 0; ?>
                                <div class="bar-row">
                                    <span class="b-name" title="<?php echo e($r['coursename']); ?>"><?php echo e($r['coursename']); ?></span>
                                    <span class="bar-track"><i style="width:<?php echo (int)$w; ?>%"></i></span>
                                    <span class="b-val"><?php echo e($r['c']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Enrollment by Year Level</div>
                    <?php if (count($byYearLevel) === 0): ?>
                        <p class="empty">No enrollments recorded yet.</p>
                    <?php else:
                        $maxY = 0;
                        foreach ($byYearLevel as $r) { $maxY = max($maxY, (int)$r['c']); } ?>
                        <div class="bars">
                            <?php foreach ($byYearLevel as $r):
                                $w = $maxY > 0 ? round((int)$r['c'] / $maxY * 100) : 0; ?>
                                <div class="bar-row">
                                    <span class="b-name"><?php echo e($r['yearlevel']); ?></span>
                                    <span class="bar-track"><i style="width:<?php echo (int)$w; ?>%"></i></span>
                                    <span class="b-val"><?php echo e($r['c']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel" style="margin-top:18px;">
                <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Summary</div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Metric</th><th>Value</th></tr></thead>
                        <tbody>
                            <tr><td>Total students</td><td><b><?php echo e($totalStudents); ?></b></td></tr>
                            <tr><td>Students enrolled</td><td><b><?php echo e($totalEnrolled); ?></b> (<?php echo e($enrollRate); ?>%)</td></tr>
                            <tr><td>Total enrollment records</td><td><b><?php echo e($enrollRows); ?></b></td></tr>
                            <tr><td>Courses offered</td><td><b><?php echo e($totalCourses); ?></b></td></tr>
                            <tr><td>Class subjects</td><td><b><?php echo e($totalSubjects); ?></b></td></tr>
                            <tr><td>Instructors</td><td><b><?php echo e($totalInstructors); ?></b></td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mini-note">Custom reports (by date range, program, etc.) require additional tracking columns such as admission date or status.</p>
            </div>
        </section>

        <!-- ============ 7. FINANCIAL INFORMATION ============ -->
        <section id="financial">
            <div class="sec-head">
                <span class="num">7</span>
                <div><h2>Financial Information</h2><p>Tuition, fees and financial aid</p></div>
            </div>
            <div class="panel">
                <div class="soon">
                    <span class="soon-ico">&#128176;</span>
                    <div>
                        <b>Not yet available</b>
                        <p>Tuition rates, payment deadlines, outstanding balances and financial-aid status need <code>fees</code> / <code>payment</code> / <code>financial_aid</code> tables. None exist in the database yet.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 8. COMPLIANCE & ACCREDITATION ============ -->
        <section id="compliance">
            <div class="sec-head">
                <span class="num">8</span>
                <div><h2>Compliance &amp; Accreditation</h2><p>Accreditation tracking and policy documents</p></div>
            </div>
            <div class="panel">
                <div class="soon">
                    <span class="soon-ico">&#9878;</span>
                    <div>
                        <b>Not yet available</b>
                        <p>Accreditation records and policy documents require tables such as <code>accreditation</code> and <code>policy_document</code> (with file uploads). These are not part of the current database.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 9. USER ACCESS & SECURITY ============ -->
        <section id="security">
            <div class="sec-head">
                <span class="num">9</span>
                <div><h2>User Access &amp; Security</h2><p>Role management and audit logs</p></div>
            </div>
            <div class="panel">
                <div class="soon" style="margin-bottom:14px;">
                    <span class="soon-ico">&#128274;</span>
                    <div>
                        <b>Role management &mdash; not yet available</b>
                        <p>There is no <code>user</code> / <code>role</code> table, and the system currently has no login. Registrar accounts and access levels require an authentication layer (with <code>password_hash()</code>).</p>
                    </div>
                </div>
                <div class="soon">
                    <span class="soon-ico">&#128203;</span>
                    <div>
                        <b>Audit logs &mdash; not yet available</b>
                        <p>Change tracking needs an <code>audit_log</code> table written to on every insert/update/delete. It does not exist yet.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 10. HELP & SUPPORT ============ -->
        <section id="help">
            <div class="sec-head">
                <span class="num">10</span>
                <div><h2>Help &amp; Support</h2><p>FAQs and technical support</p></div>
            </div>
            <div class="grid-2">
                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Frequently Asked Questions</div>
                    <p style="font-size:13px;line-height:1.6;margin:0 0 10px;"><b>How do I register a student?</b><br>Use the <a href="index.php">Student Registration</a> form. The record is saved to the <code>student</code> table.</p>
                    <p style="font-size:13px;line-height:1.6;margin:0 0 10px;"><b>Where do I view all students?</b><br>Open the <a href="students.php">Registered Students</a> list.</p>
                    <p style="font-size:13px;line-height:1.6;margin:0;"><b>Why are some modules empty?</b><br>Those features need database tables that have not been created yet &mdash; they are listed as &ldquo;not yet available&rdquo; above.</p>
                </div>
                <div class="panel">
                    <div class="k-label" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px;">Technical Support</div>
                    <p style="font-size:13px;line-height:1.7;margin:0;">
                        &#128231; registrar-support@novabright.edu<br>
                        &#9742; (000) 000-0000 local 100<br>
                        &#128336; Mon&ndash;Fri, 8:00 AM &ndash; 5:00 PM
                    </p>
                    <p class="mini-note">Demo contact details &mdash; replace with your real support information.</p>
                </div>
            </div>
        </section>

        <footer class="page-foot">
            &copy; <?php echo date('Y'); ?> Nova Bright Academy &mdash; Registrar Dashboard &bull;
            <a href="index.php" style="color:#3f6b6b;">Registration</a> &bull;
            <a href="students.php" style="color:#3f6b6b;">Student List</a>
        </footer>

    </main>
</div>
</body>
</html>
