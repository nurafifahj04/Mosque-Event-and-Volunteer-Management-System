<?php
// process/register-event.php - Volunteer Event Registration (FR-06)
require_once "../includes/db.php";
require_once "../includes/auth.php";

require_role("Volunteer");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../volunteer-events.php");
    exit;
}

$volunteerId = $_SESSION["user_id"];
$eventId = intval($_POST["event_id"] ?? 0);
// Preferred role: null when volunteer chose "no preference"
$preferredRoleId = (!empty($_POST["volunteer_role"]) && intval($_POST["volunteer_role"]) > 0)
    ? intval($_POST["volunteer_role"])
    : null;

if ($eventId <= 0) {
    $_SESSION["error_message"] = "Invalid event selected.";
    header("Location: ../volunteer-events.php");
    exit;
}

// 1. Check if event exists and get quota and current status
$stmt = $conn->prepare("SELECT event_name, volunteer_quota, event_status FROM events WHERE event_id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION["error_message"] = "Selected event not found.";
    header("Location: ../volunteer-events.php");
    exit;
}

if ($event["event_status"] === "Closed" || $event["event_status"] === "Completed") {
    $_SESSION["error_message"] = "Registration is closed for " . htmlspecialchars($event["event_name"]) . ".";
    header("Location: ../volunteer-events.php");
    exit;
}

// 2. Prevent duplicate registration (FR-06 / NFR-06)
$stmt = $conn->prepare("SELECT registration_id FROM volunteer_registrations WHERE event_id = ? AND volunteer_id = ?");
$stmt->bind_param("ii", $eventId, $volunteerId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $_SESSION["error_message"] = "You have already registered for this event. Please check My Registrations.";
    header("Location: ../volunteer-status.php");
    exit;
}

// 3. Check overall event quota (e.g. 3 volunteers max)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_registered FROM volunteer_registrations WHERE event_id = ? AND registration_status != 'Rejected'");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$countRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($countRow["total_registered"] >= $event["volunteer_quota"]) {
    // Auto-update event status to Closed if quota is full
    $conn->query("UPDATE events SET event_status = 'Closed' WHERE event_id = " . intval($eventId));
    $_SESSION["error_message"] = "Sorry, volunteer quota (" . $event["volunteer_quota"] . " spaces) for this event is now full.";
    header("Location: ../volunteer-events.php");
    exit;
}

// 4. Save registration as Pending
// Use separate queries depending on whether a preferred role was chosen,
// so that preferred_event_role_id is NULL (not 0) when no preference given.
if ($preferredRoleId !== null) {
    $stmt = $conn->prepare("
        INSERT INTO volunteer_registrations
        (event_id, volunteer_id, preferred_event_role_id, registration_status)
        VALUES (?, ?, ?, 'Pending')
    ");
    $stmt->bind_param("iii", $eventId, $volunteerId, $preferredRoleId);
} else {
    $stmt = $conn->prepare("
        INSERT INTO volunteer_registrations
        (event_id, volunteer_id, registration_status)
        VALUES (?, ?, 'Pending')
    ");
    $stmt->bind_param("ii", $eventId, $volunteerId);
}

if ($stmt->execute()) {
    $newRegId = $stmt->insert_id;
    $stmt->close();

    // Check if quota reached after this registration and auto-close
    if (($countRow["total_registered"] + 1) >= $event["volunteer_quota"]) {
        $conn->query("UPDATE events SET event_status = 'Closed' WHERE event_id = " . intval($eventId));
    }

    $_SESSION["success_message"] = "Your registration for " . htmlspecialchars($event["event_name"]) . " has been submitted with status 'Pending Review'.";
    header("Location: ../volunteer-status.php");
    exit;
} else {
    $_SESSION["error_message"] = "Failed to submit registration: " . $conn->error;
    $stmt->close();
    header("Location: ../volunteer-events.php");
    exit;
}
?>
