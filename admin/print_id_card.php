<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireRole(['admin', 'school_admin', 'teacher']);

$user_id = (int)($_GET['user_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, u.full_name, u.email, u.profile_photo, c.class_name, c.section 
    FROM sms_student_profiles p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN sms_classes c ON p.class_id = c.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}

$photo_url = $student['profile_photo'] ?: '../uploads/profiles/default_student.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --school-primary: #1B5E20;
            --school-accent: #F9A825;
            --white: #ffffff;
            --text-dark: #0f172a;
        }
        body { 
            background: #f1f5f9; 
            font-family: 'Outfit', sans-serif; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding-top: 50px;
        }
        
        .id-card-wrapper {
            width: 330px;
            height: 520px;
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            background: linear-gradient(to bottom, #fff 70%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
        }

        /* Top Header Section */
        .id-header {
            background: var(--school-primary);
            height: 140px;
            padding: 20px;
            color: #fff;
            text-align: center;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        .header-logo { font-size: 1.8rem; margin-bottom: 5px; color: var(--school-accent); }
        .school-name { font-weight: 800; font-size: 1rem; text-transform: uppercase; letter-spacing: 1.5px; margin: 0; }
        .header-tag { font-size: 0.65rem; opacity: 0.8; letter-spacing: 1px; }

        /* Photo Section */
        .photo-container {
            position: absolute;
            top: 85px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }
        .photo-border {
            width: 130px;
            height: 130px;
            background: #fff;
            border-radius: 50%;
            padding: 5px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .student-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--school-primary);
        }

        /* Details Section */
        .id-content {
            margin-top: 85px;
            text-align: center;
            padding: 0 25px;
        }
        .student-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        .student-role {
            display: inline-block;
            background: rgba(27, 94, 32, 0.1);
            color: var(--school-primary);
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 15px;
            border-radius: 50px;
            text-transform: uppercase;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            text-align: left;
            margin-top: 10px;
        }
        .detail-item label {
            display: block;
            font-size: 0.65rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .detail-item span {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Bottom Footer */
        .id-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px 25px;
            box-sizing: border-box;
            background: #fff;
            border-top: 1px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .barcode-area { height: 35px; opacity: 0.8; }
        .valid-until { font-size: 0.6rem; color: #94a3b8; text-align: right; }
        .valid-until strong { display: block; color: #64748b; font-size: 0.7rem; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .id-card-wrapper {
                box-shadow: none;
                border: 1px solid #ddd;
                position: absolute;
                top: 0;
                left: 0;
            }
        }

        .btn-print {
            margin-bottom: 30px;
            background: var(--school-primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }

        /* Iframe adjustments */
        body.is-iframe { padding-top: 10px; background: transparent; }
        body.is-iframe .btn-print { display: none; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('iframe=1')) {
                document.body.classList.add('is-iframe');
            }
        });
    </script>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Print Official Badge
    </button>

    <div class="id-card-wrapper">
        <div class="id-header">
            <i class="fas fa-graduation-cap header-logo"></i>
            <h1 class="school-name">EthioServe School</h1>
            <div class="header-tag">Official Institutional ID Card</div>
        </div>

        <div class="photo-container">
            <div class="photo-border">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" class="student-photo" alt="Student">
            </div>
        </div>

        <div class="id-content">
            <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
            <div class="student-role">Student</div>

            <div class="details-grid">
                <div class="detail-item">
                    <label>Student ID No</label>
                    <span><?php echo htmlspecialchars($student['student_id_number']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Grade / Class</label>
                    <span><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Parent/Guardian</label>
                    <span><?php echo htmlspecialchars($student['parent_name'] ?: 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <label>Emergency No</label>
                    <span><?php echo htmlspecialchars($student['emergency_contact'] ?: 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <div class="id-footer">
            <div class="barcode-area">
                <svg id="barcode"></svg>
                <!-- Simple barcode mock -->
                <div style="font-family: 'Courier New', monospace; font-weight: bold; font-size: 14px; letter-spacing: 2px;">
                    ||| | || ||| | |||
                </div>
            </div>
            <div class="valid-until">
                <strong>Academic Year</strong>
                2024 - 2025
            </div>
        </div>
    </div>

</body>
</html>
