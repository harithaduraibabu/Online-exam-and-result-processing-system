<?php
// student/certificate.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$student_id = $_SESSION['user_id'];

// Fetch submission details
$query = "
    SELECT s.*, e.title as exam_title, e.total_marks, u.full_name, u.college
    FROM submissions s
    JOIN exams e ON s.exam_id = e.id
    JOIN users u ON s.student_id = u.id
    WHERE s.id = $submission_id AND s.student_id = $student_id AND (s.status = 'published' OR s.status = 'evaluated')
";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    die("Certificate not available yet or invalid request.");
}

$data = $result->fetch_assoc();
$percentage = ($data['total_marks'] > 0) ? round(($data['score'] / $data['total_marks']) * 100, 2) : 0;
$date = date('F d, Y', strtotime($data['finished_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($data['exam_title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Outfit:wght@300;400;600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --gold: #C5A059;
        }
        body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .certificate-container {
            width: 900px;
            height: 650px;
            background: white;
            position: relative;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border: 15px solid #fff;
            box-sizing: border-box;
            background-image: 
                radial-gradient(circle at 100% 100%, rgba(79, 70, 229, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 0% 0%, rgba(197, 160, 89, 0.05) 0%, transparent 20%);
        }
        .border-outer {
            border: 2px solid var(--gold);
            height: 100%;
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            position: relative;
        }
        .border-inner {
            border: 5px solid var(--gold);
            height: 100%;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px;
        }
        .logo {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 3rem;
            color: #1e293b;
            margin: 20px 0;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .student-name {
            font-family: 'Cinzel', serif;
            font-size: 3.5rem;
            color: var(--gold);
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .award-text {
            font-size: 1.2rem;
            color: #334155;
            max-width: 600px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .exam-name {
            font-weight: 700;
            color: var(--primary);
        }
        .cert-footer {
            margin-top: auto;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 40px;
        }
        .footer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #94a3b8;
            margin-top: 10px;
            padding-top: 5px;
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
        }
        .stamp {
            position: absolute;
            bottom: 60px;
            right: 100px;
            width: 120px;
            height: 120px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }
        @media print {
            body { background: white; }
            .certificate-container { box-shadow: none; margin: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
        }
        .print-btn:hover { background: #4338ca; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Download PDF
    </button>

    <div class="certificate-container">
        <div class="border-outer">
            <div class="border-inner">
                <div class="logo">EXAM PORTAL</div>
                <div class="cert-title">Certificate</div>
                <div class="subtitle">of Achievement</div>
                
                <div style="margin-bottom: 10px; color: #64748b; font-weight: 600;">This is to certify that</div>
                <div class="student-name"><?php echo htmlspecialchars($data['full_name']); ?></div>
                
                <div class="award-text">
                    has successfully completed the assessment for <br>
                    <span class="exam-name"><?php echo htmlspecialchars($data['exam_title']); ?></span> <br>
                    with a score of <strong><?php echo $data['score']; ?>/<?php echo $data['total_marks']; ?> (<?php echo $percentage; ?>%)</strong>
                </div>

                <div class="cert-footer">
                    <div class="footer-item">
                        <div style="font-weight: 700; color: #1e293b;"><?php echo $date; ?></div>
                        <div class="signature-line">Date of Completion</div>
                    </div>
                    <div class="footer-item">
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: #1e293b; font-weight: 700;">Exam Portal Admin</div>
                        <div class="signature-line">Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
