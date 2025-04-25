<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;
use App\Models\Patient;
use GuzzleHttp\Client;

function swaggerDummyAnnotations()
{
}
/**
 * @OA\Info(title="User Service API", version="1.0", description = "API that handles data regarding patients or users")
 *
 * @OA\Post(
 *     path="/patients",
 *     summary="Create a new patient",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"patient_name","username","email","password","date_of_birth","gender"},
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="patient_name", type="string", example="Ikki Keren"),
 *             @OA\Property(property="username", type="string", example="kiikki"),
 *             @OA\Property(property="email", type="string", format="email", example="ikkigamingchannel@hotmail.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123"),
 *             @OA\Property(property="date_of_birth", type="string", format="date", example="2001-09-11"),
 *             @OA\Property(property="gender", type="string", example="male")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Patient created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Patient")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/patients/{id}",
 *     summary="Get patient by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of patient to retrieve",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Patient found",
 *         @OA\JsonContent(ref="#/components/schemas/Patient")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Patient not found",
 *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Patient not found"))
 *     )
 * )
 *
 * @OA\Get(
 *     path="/patients",
 *     summary="Get list of all patients",
 *     @OA\Response(
 *         response=200,
 *         description="List of patients",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Patient"))
 *     )
 * )
 *
 * @OA\Get(
 *     path="/patients/{id}/reviews",
 *     summary="Get reviews for a patient",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of patient to get reviews for",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of reviews",
 *         @OA\JsonContent(type="array", @OA\Items(type="object"))
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error fetching reviews",
 *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Unable to fetch reviews or ReviewService unavailable"))
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Patient",
 *     type="object",
 *     title="Patient",
 *     required={"id", "patient_name", "username", "email", "date_of_birth", "gender"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_name", type="string", example="Ikki Keren"),
 *     @OA\Property(property="username", type="string", example="kiikki"),
 *     @OA\Property(property="email", type="string", format="email", example="ikkigamingchannel@hotmail.com"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", example="2001-09-11"),
 *     @OA\Property(property="gender", type="string", example="male")
 * )
 */


$router->post('/patients', function (Request $request) {
    $data = $request->only(['patient_name', 'username', 'email', 'password', 'date_of_birth', 'gender']);
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

    $patient = Patient::create($data);

    return response()->json($patient);
});

$router->get('/patients/{id}', function ($id) {
    $patient = Patient::find($id);
    if (!$patient) {
        return response()->json(['error' => 'Patient not found'], 404);
    }
    return response()->json($patient);
});

$router->get('/patients', function () {
    $patients = Patient::all();
    return response()->json($patients);
});

$router->get('/patients/{id}/reviews', function ($id) {
    $client = new Client();

    try {
        $response = $client->get("http://localhost:8003/reviews?patient_id={$id}");
        if ($response->getStatusCode() !== 200) {
            return response()->json(['error' => 'Unable to fetch reviews'], 500);
        }
        $reviews = json_decode($response->getBody(), true);
    } catch (\Exception $e) {
        return response()->json(['error' => 'ReviewService unavailable'], 500);
    }

    return response()->json($reviews);
});
