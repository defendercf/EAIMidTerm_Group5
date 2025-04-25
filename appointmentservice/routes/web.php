<?php



/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;
use App\Models\Appointment;
use GuzzleHttp\Client;

function swaggerDummyAnnotations()
{
}
/**
 * 
 * @OA\Info(title="Appointment Service API", version="1.0", description = "API that handles data regarding patients appointments")
 *
 * @OA\Post(
 *     path="/appointments",
 *     summary="Create a new appointment",
 *     tags={"Appointments"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"patient_id", "date", "time"},
 *             @OA\Property(property="patient_id", type="integer", example=1),
 *             @OA\Property(property="date", type="string", format="date", example="2025-05-01"),
 *             @OA\Property(property="time", type="string", example="14:00")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Appointment created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Appointment")
 *     ),
 *     @OA\Response(response=400, description="Invalid input or service unavailable")
 * )
 *
 * @OA\Get(
 *     path="/appointments/{id}",
 *     summary="Get an appointment by ID",
 *     tags={"Appointments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Appointment ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Appointment found",
 *         @OA\JsonContent(ref="#/components/schemas/Appointment")
 *     ),
 *     @OA\Response(response=404, description="Appointment not found")
 * )
 *
 * @OA\Get(
 *     path="/appointments",
 *     summary="Get list of appointments, optionally filtered by patient ID",
 *     tags={"Appointments"},
 *     @OA\Parameter(
 *         name="patient_id",
 *         in="query",
 *         description="Filter appointments by patient ID",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of appointments",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Appointment"))
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Appointment",
 *     type="object",
 *     required={"patient_id", "date", "time", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="date", type="string", format="date", example="2025-05-01"),
 *     @OA\Property(property="time", type="string", example="14:00"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-25T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-25T10:00:00Z")
 * )
 */


$router->post('/appointments', function (Request $request) {
    $data = $request->only(['patient_id', 'date', 'time']);

    $client = new Client();

    try {
        $response = $client->get("http://localhost:8001/patients/{$data['patient_id']}");
        if ($response->getStatusCode() !== 200) {
            return response()->json(['error' => 'Invalid patient ID'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'UserService unavailable or invalid patient'], 400);
    }

    $appointment = Appointment::create([
        'patient_id' => $data['patient_id'],
        'date' => $data['date'],
        'time' => $data['time'],
        'status' => 'pending',
    ]);

    return response()->json($appointment);
});

$router->get('/appointments/{id}', function ($id) {
    $appointment = Appointment::find($id);
    if (!$appointment) {
        return response()->json(['error' => 'Appointment not found'], 404);
    }
    return response()->json($appointment);

});

$router->get('/appointments', function () {
    $appointments = Appointment::all();
    return response()->json($appointments);
});

$router->get('/appointments', function (Illuminate\Http\Request $request) {
    $patientId = $request->query('patient_id');
    if ($patientId) {
        $appointments = Appointment::where('patient_id', $patientId)->get();
    } else {
        $appointments = Appointment::all();
    }
    return response()->json($appointments);
});
