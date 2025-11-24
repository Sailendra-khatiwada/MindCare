<?php
function checkAppointment($conn, $appointment_id, $user_id = null, $p_id = null) {

    if ($user_id) {
        $sql = "SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointment_id, $user_id);
    }
    elseif ($p_id) {
        $sql = "SELECT * FROM appointments WHERE appointment_id = ? AND p_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointment_id, $p_id);
    } else {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();

    if (!$app) return false;
    if ($app['status'] !== 'approved') return false;

    return true;
}
?>
