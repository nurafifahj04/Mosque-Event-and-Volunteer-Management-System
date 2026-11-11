<?php
// seed.php - MEVMS Full Database Reset & Seed
$conn = new mysqli('localhost', 'root', '', 'mevms');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
foreach (['event_reports','token_records','attendance_records','role_assignments','volunteer_registrations','event_roles','events','users'] as $t) {
    $conn->query("TRUNCATE TABLE $t");
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$pw = password_hash('password123', PASSWORD_DEFAULT);

// ── 1. USERS ──────────────────────────────────────────────────────────────
$s = $conn->prepare("INSERT INTO users (user_id,username,full_name,phone_number,residential_area,password_hash,user_role,committee_position,account_status) VALUES (?,?,?,?,?,?,?,?,?)");
$users = [
    [1,  'afifah',    'Nur Afifah binti Jamil',       '012-3456789', 'Kg Rinching Hulu', $pw, 'Volunteer', null, 'Active'],
    [2,  'hakim',     'Muhammad Hakim bin Zain',       '013-9876543', 'Beranang',         $pw, 'Volunteer', null, 'Active'],
    [3,  'faiz',      'Ahmad Faiz bin Rosli',          '017-5554321', 'Kg Rinching Hulu', $pw, 'Volunteer', null, 'Active'],
    [6,  'siti',      'Siti Nur Aisyah binti Kamal',  '011-2345678', 'Semenyih',         $pw, 'Volunteer', null, 'Active'],
    [7,  'hafiz',     'Mohd Hafiz bin Zainal',         '014-8765432', 'Kg Rinching Hulu', $pw, 'Volunteer', null, 'Active'],
    [8,  'sarah',     'Nur Sarah binti Othman',        '016-9990011', 'Beranang',         $pw, 'Volunteer', null, 'Active'],
    [4,  'committee', 'Ustaz Ahmad Suhaimi',           '019-1112233', 'Semenyih',         $pw, 'Mosque Committee Member',      'Secretary', 'Active'],
    [5,  'nazir',     'Haji Rahman bin Kassim',        '012-8889900', 'Kg Rinching Hulu', $pw, 'Head of the Mosque Committee', 'Nazir',     'Active'],
];
foreach ($users as $u) {
    $s->bind_param("issssssss", $u[0],$u[1],$u[2],$u[3],$u[4],$u[5],$u[6],$u[7],$u[8]);
    $s->execute();
}
$s->close();

// ── 2. EVENTS ─────────────────────────────────────────────────────────────
$s = $conn->prepare("INSERT INTO events (event_id,event_name,event_description,event_date,start_time,end_time,location,event_pic_id,volunteer_quota,planned_budget,funding_source,event_status,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
$events = [
    [1,'Maulidur Rasul Community Programme',
     'Support the mosque community programme through welcoming congregants, guest assistance and meal coordination.',
     '2026-11-15','08:00:00','13:00:00','Masjid Kg Rinching Hulu – Dewan Utama',4,3,1500.00,'Mosque Fund','Open',4],
    [2,'Gotong-Royong Perdana Masjid',
     'Mosque compound clean-up, landscape beautification and prayer hall maintenance with local residents.',
     '2026-11-22','07:30:00','11:30:00','Masjid Kg Rinching Hulu – Kawasan Luar',4,3,800.00,'Community Fund','Open',4],
    [3,'Ramadhan Iftar & Bubur Lambuk 2025',
     'Preparation and distribution of Iftar meals and bubur lambuk for congregants and the local kariah.',
     '2025-03-15','16:00:00','19:00:00','Masjid Kg Rinching Hulu – Dapur & Kawasan',4,5,2000.00,'Mosque Charity Fund','Completed',4],
    [4,'Community Clean-Up 2025',
     'Annual mosque compound landscape maintenance, drain cleaning and ablution area sanitation.',
     '2025-07-20','07:30:00','11:30:00','Masjid Compound & Surrounding Kariah',4,4,500.00,'Mosque Fund','Completed',4],
    [5,'Ceramah Perdana Israk Mikraj 2026',
     'Special night ceramah, hadith recitation and communal programme in conjunction with Israk Mikraj.',
     '2026-08-25','19:30:00','22:30:00','Masjid Kg Rinching Hulu – Dewan Utama',4,5,1200.00,'Mosque Fund','Completed',4],
];
foreach ($events as $e) {
    $s->bind_param("issssssiiidss",$e[0],$e[1],$e[2],$e[3],$e[4],$e[5],$e[6],$e[7],$e[8],$e[9],$e[10],$e[11],$e[12]);
    $s->execute();
}
$s->close();

// ── 3. EVENT ROLES ────────────────────────────────────────────────────────
// Schema compatibility check to ensure both max_volunteers & role_quota exist
$colsRes = $conn->query("SHOW COLUMNS FROM event_roles");
$existingCols = [];
if ($colsRes) {
    while ($c = $colsRes->fetch_assoc()) $existingCols[] = $c['Field'];
}
if (!in_array('role_quota', $existingCols)) {
    @$conn->query("ALTER TABLE event_roles ADD COLUMN role_quota INT NULL");
}
if (!in_array('max_volunteers', $existingCols)) {
    @$conn->query("ALTER TABLE event_roles ADD COLUMN max_volunteers INT NULL");
}
if (!in_array('required_skills', $existingCols)) {
    @$conn->query("ALTER TABLE event_roles ADD COLUMN required_skills VARCHAR(255) NULL DEFAULT ''");
}

$s = $conn->prepare("INSERT INTO event_roles (event_role_id,event_id,role_name,team_unit,max_volunteers,role_description) VALUES (?,?,?,?,?,?)");
$roles = [
    [1,1,'Meal Distribution Coordinator','Unit A – Logistics',  2,'Coordinate and distribute meals to guests.'],
    [2,1,'Registration & Welcoming',     'Unit B – Frontline',  1,'Manage registration table and welcome guests.'],
    [3,2,'Compound Cleaning Lead',       'Unit A – Cleaning',   2,'Lead mosque compound sweeping and rubbish removal.'],
    [4,2,'Landscape & Gardening',        'Unit B – Landscape',  1,'Trim grass and maintain garden beds.'],
    [5,3,'Bubur Lambuk Cook & Pack',     'Unit A – Kitchen',    3,'Cook and pack bubur lambuk for distribution.'],
    [6,3,'Iftar Distribution Team',      'Unit B – Distribution',2,'Distribute iftar packs to congregants.'],
    [7,4,'Landscape Beautification',     'Unit A – Grounds',    3,'Garden landscaping and compound beautification.'],
    [8,4,'Drain & Ablution Cleaning',    'Unit B – Sanitation', 1,'Clean drains and ablution facilities.'],
    [9,5,'Protocol & Reception',         'Unit A – Protocol',   2,'Receive guests and assist speaker.'],
    [10,5,'A/V & Sound System',          'Unit B – Technical',  1,'Operate sound system and presentation slides.'],
    [11,5,'Security & Crowd Management', 'Unit C – Operations', 2,'Manage crowd flow and event security.'],
];
foreach ($roles as $r) {
    $rId = $r[0]; $rEvId = $r[1]; $rName = $r[2]; $rUnit = $r[3]; $rMax = $r[4]; $rDesc = $r[5];
    $s->bind_param("iissis", $rId, $rEvId, $rName, $rUnit, $rMax, $rDesc);
    $s->execute();
}
$s->close();

@$conn->query("UPDATE event_roles SET role_quota = max_volunteers WHERE role_quota IS NULL OR role_quota = 0");
@$conn->query("UPDATE event_roles SET max_volunteers = role_quota WHERE max_volunteers IS NULL OR max_volunteers = 0");

// ── 4. VOLUNTEER REGISTRATIONS ────────────────────────────────────────────
// Event 1 & 2: 3 PENDING each (quota = 3, so committee can review and approve all)
// Events 3,4,5: Historical APPROVED for Afifah and others (for history/token demo)
$s = $conn->prepare("INSERT INTO volunteer_registrations (registration_id,event_id,volunteer_id,preferred_event_role_id,registration_date,registration_status,decision_by,decision_at,decision_comment) VALUES (?,?,?,?,?,?,?,?,?)");
$regs = [
    // Event 1: 0 registrations initially (Quota = 3) -> Wide open for Afifah to test registration!
    // Event 2: 2 pending applicants (Siti with role choice, Hakim with No Preference) -> 1 space left
    [13,2,6, 3,   '2026-10-21 10:00:00','Pending',null,null,null],
    [14,2,2, null,'2026-10-21 11:00:00','Pending',null,null,null],
    // Historical approved (Events 3,4,5) – for Afifah's volunteer history and token status
    [1,3,1,5,'2025-03-01 10:00:00','Approved',4,'2025-03-02 11:00:00','Approved – meal distribution unit'],
    [2,4,1,7,'2025-07-10 09:30:00','Approved',4,'2025-07-11 14:00:00','Approved – compound beautification'],
    [3,5,2,9,'2026-08-10 14:00:00','Approved',4,'2026-08-11 10:00:00','Approved – VIP guest reception'],
];
foreach ($regs as $r) {
    list($rId, $rEvId, $rVolId, $rPref, $rDate, $rStatus, $rDecBy, $rDecAt, $rDecCom) = $r;
    $s->bind_param("iiiisssis", $rId, $rEvId, $rVolId, $rPref, $rDate, $rStatus, $rDecBy, $rDecAt, $rDecCom);
    $s->execute();
}
$s->close();

// ── 5. ROLE ASSIGNMENTS (historical) ─────────────────────────────────────
$s = $conn->prepare("INSERT INTO role_assignments (assignment_id,registration_id,event_role_id,assigned_by,assigned_at) VALUES (?,?,?,?,?)");
foreach ([[1,1,5,4,'2025-03-02 11:05:00'],[2,2,7,4,'2025-07-11 14:05:00'],[3,3,9,4,'2026-08-11 10:05:00']] as $a) {
    list($aId, $aRegId, $aRoleId, $aAssBy, $aAssAt) = $a;
    $s->bind_param("iiiis", $aId, $aRegId, $aRoleId, $aAssBy, $aAssAt);
    $s->execute();
}
$s->close();

// ── 6. ATTENDANCE (historical) ───────────────────────────────────────────
$s = $conn->prepare("INSERT INTO attendance_records (attendance_id,registration_id,check_in_time,attendance_status,verification_status,verified_by,verified_at) VALUES (?,?,?,?,?,?,?)");
foreach ([
    [1,1,'2025-03-15 16:12:00','Present','Verified',4,'2025-03-15 19:30:00'],
    [2,2,'2025-07-20 07:28:00','Present','Verified',4,'2025-07-20 12:00:00'],
    [3,3,'2026-08-25 19:25:00','Present','Verified',4,'2026-08-25 22:45:00'],
] as $att) {
    list($attId, $attRegId, $attTime, $attStatus, $attVerStatus, $attVerBy, $attVerAt) = $att;
    $s->bind_param("iisssis", $attId, $attRegId, $attTime, $attStatus, $attVerStatus, $attVerBy, $attVerAt);
    $s->execute();
}
$s->close();

// ── 7. TOKEN RECORDS (historical) ────────────────────────────────────────
$s = $conn->prepare("INSERT INTO token_records (token_id,registration_id,token_type,token_status,distributed_at,verification_status,verified_by,verified_at) VALUES (?,?,?,?,?,?,?,?)");
foreach ([
    [1,1,'Mosque Volunteer T-Shirt & Certificate','Distributed','2025-03-15 19:00:00','Verified',5,'2025-03-16 10:00:00'],
    [2,2,'Certificate of Appreciation & Goodies', 'Distributed','2025-07-20 12:00:00','Verified',5,'2025-07-21 09:00:00'],
    [3,3,'Certificate of Appreciation & Goodies', 'Distributed','2026-08-25 22:30:00','Verified',5,'2026-08-26 11:00:00'],
] as $t) {
    list($tId, $tRegId, $tType, $tStatus, $tDistAt, $tVerStatus, $tVerBy, $tVerAt) = $t;
    $s->bind_param("iisssssi", $tId, $tRegId, $tType, $tStatus, $tDistAt, $tVerStatus, $tVerBy, $tVerAt);
    $s->execute();
}
$s->close();

// ── 8. EVENT REPORT (Ceramah 2026) ───────────────────────────────────────
$s = $conn->prepare("INSERT INTO event_reports (report_id,event_id,generated_by,actual_expenditure,team_units_present,skill_gap_notes,report_format,generated_at,file_path) VALUES (?,?,?,?,?,?,?,?,?)");
$repId = 1;
$repEventId = 5;
$repGenBy = 5;
$repExp = 1150.00;
$repUnits = 'Unit A (Protocol), Unit B (Technical), Unit C (Operations)';
$repNotes = 'Sound system setup required earlier coordination.';
$repFormat = 'PDF';
$repDate = '2026-08-26 14:00:00';
$repPath = null;
$s->bind_param("iiidsssss", $repId, $repEventId, $repGenBy, $repExp, $repUnits, $repNotes, $repFormat, $repDate, $repPath);
$s->execute();
$s->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MEVMS Seed</title>
<style>
  body { font-family: Arial, sans-serif; background: #FDF6F1; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border: 1px solid #EDD5C4; border-radius: 16px; padding: 36px 40px; max-width: 560px; width: 100%; box-shadow: 0 8px 30px rgba(90,30,10,0.12); }
  .icon { font-size: 48px; text-align: center; margin-bottom: 12px; }
  h2 { color: #5C2208; font-size: 20px; text-align: center; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #EDD5C4; }
  th { background: #5C2208; color: #fff; font-size: 11px; text-transform: uppercase; }
  .btn { display: block; margin: 20px auto 0; padding: 11px 24px; background: #C4531A; color: #fff; border-radius: 8px; text-align: center; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>
<div class="box">
  <div class="icon">✅</div>
  <h2>MEVMS Database Seeded Successfully</h2>
  <table>
    <tr><th>Account</th><th>Username</th><th>Role</th></tr>
    <tr><td>Volunteer 1</td><td>afifah</td><td>Volunteer</td></tr>
    <tr><td>Volunteer 2</td><td>hakim</td><td>Volunteer</td></tr>
    <tr><td>Volunteer 3</td><td>faiz</td><td>Volunteer</td></tr>
    <tr><td>Volunteer 4</td><td>siti</td><td>Volunteer</td></tr>
    <tr><td>Volunteer 5</td><td>hafiz</td><td>Volunteer</td></tr>
    <tr><td>Volunteer 6</td><td>sarah</td><td>Volunteer</td></tr>
    <tr><td>Committee</td><td>committee</td><td>Mosque Committee Member</td></tr>
    <tr><td>Head</td><td>nazir</td><td>Head of the Mosque Committee</td></tr>
  </table>
  <p style="font-size:12px;color:#7A5544;margin-top:14px;text-align:center;">All passwords: <strong>password123</strong> &nbsp;|&nbsp; Quota per event: <strong>3</strong></p>
  <a href="login.php" class="btn">Go to Login Page</a>
</div>
</body>
</html>
