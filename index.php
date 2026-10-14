<?php
/* ============================================================
   EDIT THESE THREE LINES TO CHANGE THE SCHOOL BRANDING
   ============================================================ */
$school_name = "Nova Bright Academy";
$school_motto = "Where Every Dream Finds Its Wings";
$school_tagline = "Modern Facilities &bull; Caring Teachers &bull; A Future Worth Enrolling In";

/* Load the course list for the Course dropdown (from the `course` table) */
require_once 'db.php';
$courses = array();
$courseResult = $conn->query("SELECT courseid, coursename FROM course ORDER BY coursename ASC");
if ($courseResult) {
    while ($c = $courseResult->fetch_assoc()) {
        $courses[] = $c;
    }
    $courseResult->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title><?php echo $school_name; ?> &mdash; Student Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --violet: #7c3aed;
            --pink:   #ec4899;
            --orange: #f97316;
            --cyan:   #06b6d4;
            --lime:   #22c55e;
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
            max-width: 860px;
            margin: 0 auto;
        }

        /* ---------- HERO / SCHOOL HEADER ---------- */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 32px 28px 34px;
            border-radius: 26px 26px 8px 8px;
            text-align: center;
            color: #fff;
            background: linear-gradient(135deg, rgba(124,58,237,.85), rgba(236,72,153,.85));
            border: 1px solid rgba(255, 255, 255, .28);
            border-bottom: none;
            box-shadow: 0 18px 40px rgba(30, 27, 75, .35);
            backdrop-filter: blur(6px);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(6px);
            opacity: .45;
        }

        .hero::before {
            width: 220px; height: 220px;
            top: -110px; left: -70px;
            background: radial-gradient(circle, var(--cyan), transparent 70%);
        }

        .hero::after {
            width: 240px; height: 240px;
            bottom: -140px; right: -80px;
            background: radial-gradient(circle, var(--orange), transparent 70%);
        }

        .crest {
            position: relative;
            width: 92px;
            height: 92px;
            margin: 0 auto 16px;
            display: grid;
            place-items: center;
            font-size: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--orange), var(--pink), var(--violet));
            border: 4px solid rgba(255, 255, 255, .85);
            box-shadow: 0 10px 26px rgba(0, 0, 0, .3);
        }

        .school-name {
            position: relative;
            margin: 0;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: .5px;
            text-shadow: 0 3px 14px rgba(0, 0, 0, .3);
        }

        .motto {
            position: relative;
            margin: 8px 0 0;
            font-size: 16px;
            font-weight: 500;
            font-style: italic;
            color: #fef3c7;
        }

        .tagline {
            position: relative;
            margin: 14px auto 0;
            max-width: 520px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .4px;
            color: rgba(255, 255, 255, .9);
        }

        /* ---------- FORM CARD ---------- */
        .card {
            background: #fff;
            border-radius: 8px 8px 26px 26px;
            padding: 30px 28px 34px;
            box-shadow: 0 26px 60px rgba(30, 27, 75, .38);
        }

        .card-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .card-head .badge {
            width: 6px;
            height: 30px;
            border-radius: 99px;
            background: linear-gradient(var(--violet), var(--pink), var(--orange));
        }

        .card-head h2 {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
        }

        .card-head p {
            margin: 2px 0 0;
            font-size: 13px;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }

        .col-span-2 { grid-column: span 2; }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #4338ca;
        }

        .field label .req { color: var(--pink); }

        .field input,
        .field select {
            width: 100%;
            padding: 11px 13px;
            font-family: inherit;
            font-size: 14px;
            color: var(--ink);
            background: #f8f7ff;
            border: 1.5px solid var(--line);
            border-radius: 11px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .field input::placeholder { color: #a5a3b8; }

        .field input:hover,
        .field select:hover { border-color: #c4b5fd; }

        .field input:focus,
        .field select:focus {
            background: #fff;
            border-color: var(--violet);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, .18);
        }

        /* ---------- BUTTON ---------- */
        .actions {
            margin-top: 26px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1 1 240px;
            padding: 14px 22px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .4px;
            color: #fff;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            background: linear-gradient(120deg, var(--violet), var(--pink) 55%, var(--orange));
            background-size: 180% 180%;
            box-shadow: 0 12px 24px rgba(236, 72, 153, .38);
            transition: transform .15s, box-shadow .15s, background-position .4s;
        }

        .btn:hover {
            transform: translateY(-2px);
            background-position: 100% 50%;
            box-shadow: 0 16px 30px rgba(124, 58, 237, .45);
        }

        .btn:active { transform: translateY(0); }

        /* Row of navigation buttons in the card header */
        .head-nav {
            margin-left: auto;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Secondary "link" button used for navigation */
        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .3px;
            text-decoration: none;
            white-space: nowrap;
            color: #5b21b6;
            background: linear-gradient(135deg, #ede9fe, #fce7f3);
            border: 1px solid #ddd6fe;
            border-radius: 12px;
            transition: transform .15s, box-shadow .15s;
        }

        .btn-ghost:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(124, 58, 237, .25);
        }

        .hint {
            font-size: 12px;
            color: var(--muted);
        }

        .why {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px dashed var(--line);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            font-size: 12px;
            font-weight: 600;
            padding: 7px 13px;
            border-radius: 99px;
            color: #3730a3;
            background: linear-gradient(135deg, #ede9fe, #fce7f3);
            border: 1px solid #ddd6fe;
        }

        footer {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: rgba(255, 255, 255, .85);
        }

        @media (max-width: 620px) {
            .school-name { font-size: 26px; }
            .grid { grid-template-columns: 1fr; }
            .col-span-2 { grid-column: span 1; }
            .card, .hero { padding-left: 20px; padding-right: 20px; }
        }
    </style>

    <!-- ===== Muted palette overrides: calm, low-brightness ===== -->
    <style>
        :root {
            --violet: #3f6b6b;   /* muted teal   */
            --pink:   #a5707a;   /* muted rose   */
            --orange: #b08d6e;   /* muted clay   */
            --cyan:   #5d8a8a;   /* muted sea    */
            --lime:   #7a9a86;   /* muted sage   */
            --ink:    #26333d;
            --muted:  #6b7885;
            --line:   #e3e8ea;
        }
        body {
            background: linear-gradient(135deg, #2f4858, #3d5a63 45%, #486b6b 100%);
            background-size: 220% 220%;
        }
        .hero {
            background: linear-gradient(135deg, rgba(63,107,107,.94), rgba(47,72,88,.94));
        }
        .motto { color: #e8dcc4; }
        .field label { color: #2f4858; }
        .field input,
        .field select { background: #f4f7f7; }
        .field input::placeholder { color: #9aa6ac; }
        .field input:hover,
        .field select:hover { border-color: #a9c2c2; }
        .field input:focus,
        .field select:focus { box-shadow: 0 0 0 4px rgba(63,107,107,.18); }
        .btn {
            background: linear-gradient(120deg, #3f6b6b, #4e7a7a 55%, #b08d6e);
            box-shadow: 0 12px 24px rgba(63,107,107,.30);
        }
        .btn:hover { box-shadow: 0 16px 30px rgba(47,72,88,.40); }
        .btn-ghost {
            color: #2f4858;
            background: linear-gradient(135deg, #eaf0ef, #f3ece6);
            border-color: #d3dedc;
        }
        .btn-ghost:hover { box-shadow: 0 10px 22px rgba(63,107,107,.20); }
        .chip {
            color: #2f4858;
            background: linear-gradient(135deg, #eaf0ef, #f3ece6);
            border-color: #d3dedc;
        }
        .hero { box-shadow: 0 18px 40px rgba(38,51,61,.35); }
        .card { box-shadow: 0 26px 60px rgba(38,51,61,.35); }
    </style>
</head>
<body>
    <div class="wrap">

        <!-- ===== SCHOOL HEADER ===== -->
        <header class="hero">
            <div class="crest">&#127891;</div>
            <h1 class="school-name"><?php echo $school_name; ?></h1>
            <p class="motto">&ldquo;<?php echo $school_motto; ?>&rdquo;</p>
            <p class="tagline"><?php echo $school_tagline; ?></p>
        </header>

        <!-- ===== REGISTRATION FORM ===== -->
        <main class="card">
            <div class="card-head">
                <span class="badge"></span>
                <div>
                    <h2>Student Registration</h2>
                    <p>Fill out the form below to enroll. It only takes a minute.</p>
                </div>
                <div class="head-nav">
                    <a class="btn-ghost" href="students.php">&#128101; View Registered Students</a>
                    <a class="btn-ghost" href="registrar_dashboard.php">&#128202; Registrar Dashboard</a>
                </div>
            </div>

            <form action="/enrollment_system/save_student.php" method="POST">
                <div class="grid">

                    <div class="field">
                        <label for="lname">Last Name <span class="req">*</span></label>
                        <input type="text" id="lname" name="lname" placeholder="Dela Cruz" required>
                    </div>

                    <div class="field">
                        <label for="fname">First Name <span class="req">*</span></label>
                        <input type="text" id="fname" name="fname" placeholder="Juan" required>
                    </div>

                    <div class="field">
                        <label for="mname">Middle Name</label>
                        <input type="text" id="mname" name="mname" placeholder="Santos">
                    </div>

                    <div class="field">
                        <label for="sex">Sex</label>
                        <select id="sex" name="sex">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="bdate">Birth Date</label>
                        <input type="date" id="bdate" name="bdate">
                    </div>

                    <div class="field">
                        <label for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" placeholder="juan@example.com" required>
                    </div>

                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" minlength="6" required>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" minlength="6" required>
                    </div>

                    <div class="field col-span-2">
                        <label for="courseid">Course</label>
                        <select id="courseid" name="courseid" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo (int)$c['courseid']; ?>">
                                    <?php echo htmlspecialchars($c['coursename'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field col-span-2">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="123 Mabini St., Barangay San Roque, City">
                    </div>

                </div>

                <div class="actions">
                    <button type="submit" class="btn">Enroll Now</button>
                    <span class="hint">By registering you become part of the <?php echo $school_name; ?> family.</span>
                </div>
            </form>

            <div class="why">
                <span class="chip">&#127775; Bright Future</span>
                <span class="chip">&#128218; Quality Education</span>
                <span class="chip">&#129309; Friendly Community</span>
                <span class="chip">&#127942; Achievers&rsquo; School</span>
            </div>
        </main>

        <footer>&copy; <?php echo date("Y"); ?> <?php echo $school_name; ?> &mdash; <?php echo $school_motto; ?></footer>
    </div>

    <script>
        // Make sure "Confirm Password" matches "Password" before submitting
        (function () {
            var form = document.querySelector('form[action*="save_student.php"]');
            if (!form) { return; }
            var pw  = document.getElementById('password');
            var cpw = document.getElementById('confirm_password');
            if (!pw || !cpw) { return; }

            function checkMatch() {
                cpw.setCustomValidity(
                    pw.value !== cpw.value ? 'Passwords do not match.' : ''
                );
            }
            pw.addEventListener('input', checkMatch);
            cpw.addEventListener('input', checkMatch);

            form.addEventListener('submit', function (e) {
                checkMatch();
                if (!form.checkValidity()) {
                    e.preventDefault();
                    form.reportValidity();
                }
            });
        })();
    </script>
</body>
</html>
