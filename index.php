<?php
function request(string $method, string $url, ?array $data = null)
{
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

  if ($data !== null) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  }

  $response = curl_exec($ch);
  if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    return ['error' => "cURL error: $error"];
  }

  curl_close($ch);

  #for debug
  // echo "<pre>Raw response from $url:\n" . htmlspecialchars($response) . "</pre>";

  $decoded = json_decode($response, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    return [
      'error' => 'Invalid JSON response',
      'raw_response' => $response,
      'json_error' => json_last_error_msg()
    ];
  }

  return $decoded;
}

function getMessageFromResponse($response, $successPrefix)
{
  if (!is_array($response)) {
    return 'No response or invalid response from service';
  }
  if (isset($response['error'])) {
    return $response['error'];
  }
  if (isset($response['id'])) {
    return $successPrefix . $response['id'];
  }
  return 'Unexpected response format';
}

$message = '';
$currentPatientId = $_POST['review_patient_id'] ?? null;

// Create patient
if (isset($_POST['create_patient'])) {
  $data = [
    'patient_name' => $_POST['patient_name'],
    'username' => $_POST['username'],
    'email' => $_POST['email'],
    'password' => $_POST['password'],
    'date_of_birth' => $_POST['date_of_birth'],
    'gender' => $_POST['gender'],
  ];
  $response = request('POST', 'http://localhost:8001/patients', $data);
  $message = getMessageFromResponse($response, 'Patient created with ID ');
}

// Create appointment
if (isset($_POST['create_appointment'])) {
  $data = [
    'patient_id' => $_POST['patient_id'],
    'date' => $_POST['date'],
    'time' => $_POST['time'],
  ];
  $response = request('POST', 'http://localhost:8002/appointments', $data);
  $message = getMessageFromResponse($response, 'Appointment created with ID ');
}

// Create review with validation
if (isset($_POST['create_review'])) {
  if (empty($_POST['review_patient_id'])) {
    $message = 'Please select a patient.';
  } elseif (empty($_POST['appointment_id'])) {
    $message = 'Please select an appointment.';
  } elseif (empty($_POST['rating'])) {
    $message = 'Please provide a rating.';
  } else {
    $data = [
      'patient_id' => $_POST['review_patient_id'],
      'appointment_id' => $_POST['appointment_id'],
      'rating' => (int) $_POST['rating'],
      'comment' => $_POST['comment'] ?? '',
    ];
    $response = request('POST', 'http://localhost:8003/reviews', $data);
    $message = getMessageFromResponse($response, 'Review created with ID ');
  }
}

// Fetch patients for dropdowns
$patients = request('GET', 'http://localhost:8001/patients');
if (!is_array($patients)) {
  $patients = [];
}

// Fetch appointments for selected patient using query parameter filter
$appointments = [];
if ($currentPatientId) {
  $appointmentsResponse = request('GET', "http://localhost:8002/appointments?patient_id=$currentPatientId");
  if (is_array($appointmentsResponse)) {
    $appointments = $appointmentsResponse;
  }
} else {
  $appointments = [];
}

// Fetch reviews for current patient
$reviews = [];
if ($currentPatientId) {
  $reviewsResponse = request('GET', "http://localhost:8001/patients/$currentPatientId/reviews");
  if (is_array($reviewsResponse)) {
    $reviews = $reviewsResponse;
  }
}

// Filter appointments to exclude those already reviewed
if (!empty($appointments) && !empty($reviews)) {
  $reviewedAppointmentIds = array_map(fn($r) => $r['appointment_id'], $reviews);
  $appointments = array_filter($appointments, function ($appointment) use ($reviewedAppointmentIds) {
    return !in_array($appointment['id'], $reviewedAppointmentIds);
  });
  $appointments = array_values($appointments); // Re-index array
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Healthcare Microservices</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/style.css">
</head>

<body>

  <div class="title">
    <h1>Healthcare Microservices</h1>
  </div>

  <?php if ($message): ?>
    <p><strong><?= htmlspecialchars($message) ?></strong></p>
  <?php endif; ?>

  <div class="form patient">
    <h2>Create Patient</h2>
    <div class="sub patient">
      <form method="POST">
        <div class="form_input">
          <input type="hidden" name="create_patient" />
          <label>Name: <input type="text" name="patient_name" required></label><br>
          <label>Username: <input type="text" name="username" required></label><br>
          <label>Email: <input type="email" name="email" required></label><br>
          <label>Password: <input type="password" name="password" required></label><br>
          <label>Date of Birth: <input type="date" name="date_of_birth" required></label><br>
          <label>Gender:
            <select name="gender" required>
              <option value="">--Select--</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
          </label><br>
        
        <button type="submit">Create Patient</button>
        </div>
      </form>
    </div>
  </div>


  <div class="form appointment">
    <h2>Create Appointment</h2>
    <div class="sub appointment">
      <form method="POST">
        <div class="form_input">
          <input type="hidden" name="create_appointment" />
          <label>Patient:
            <select name="patient_id" required>
              <option value="">--Select Patient--</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['patient_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label><br>
          <label>Date: <input type="date" name="date" required></label><br>
          <label>Time: <input type="time" name="time" required></label><br>
        
          <button type="submit">Create Appointment</button>
        </div>
      </form>
    </div>
  </div>



  <div class="form review">
  <h2>Create Review</h2>
    <div class="sub review">
      <form method="POST" id="reviewForm">
        <div class="form_input">
          <input type="hidden" name="create_review" />
          <label>Patient:
            <select name="review_patient_id" required onchange="this.form.submit()">
              <option value="">--Select Patient--</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= htmlspecialchars($p['id']) ?>" <?= ($p['id'] == $currentPatientId) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['patient_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label><br>

          <label>Appointment:
            <select name="appointment_id" required <?= empty($appointments) ? 'disabled' : '' ?>>
              <option value="">--Select Appointment--</option>
              <?php foreach ($appointments as $a): ?>
                <option value="<?= htmlspecialchars($a['id']) ?>">
                  <?= htmlspecialchars("ID {$a['id']} on {$a['date']} at {$a['time']}") ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label><br>

          <label>Rating (1-5): <input type="number" name="rating" min="1" max="5" required></label><br>
          <label>Comment: <input type="text" name="comment"></label><br>
          <button type="submit" <?= empty($appointments) ? 'disabled' : '' ?>>Create Review</button>
          <?php if (empty($appointments)): ?>
            <p><em>*No appointments available for selected patient to review.</em></p>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>


  <div class="form review_results">
    <h2>Reviews for Selected Patient</h2>
    <div class="result">
      <?php if (!empty($reviews)): ?>
        <ul>
          <?php foreach ($reviews as $review): ?>
            <li>
              <strong>Appointment ID:</strong> <?= htmlspecialchars($review['appointment_id']) ?>,
              <strong>Rating:</strong> <?= htmlspecialchars($review['rating']) ?>,
              <strong>Comment:</strong> <?= htmlspecialchars($review['comment'] ?? '') ?>
              <strong>Sentiment:</strong> <?= htmlspecialchars($review['sentiment'] ?? '') ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No reviews found for selected patient.</p>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>