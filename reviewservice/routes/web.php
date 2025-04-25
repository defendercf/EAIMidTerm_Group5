<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;
use App\Models\Review;
use GuzzleHttp\Client;


function swaggerDummyAnnotations()
{
}
/**
 * 
 * @OA\Info(title="Review Service API", version="1.0", description = "API that handles data regarding patients reviews of appointment")
 * 
 * 
 * @OA\Post(
 *     path="/reviews",
 *     summary="Create a new review",
 *     tags={"Reviews"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"patient_id", "appointment_id", "rating", "comment"},
 *             @OA\Property(property="patient_id", type="integer", example=1),
 *             @OA\Property(property="appointment_id", type="integer", example=1),
 *             @OA\Property(property="rating", type="integer", example=5),
 *             @OA\Property(property="comment", type="string", example="kerja bagus!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Review created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Review")
 *     ),
 *     @OA\Response(response=400, description="Invalid input or service unavailable")
 * )
 *
 * @OA\Get(
 *     path="/reviews/{id}",
 *     summary="Get a review by ID",
 *     tags={"Reviews"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Review ID",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Review found",
 *         @OA\JsonContent(ref="#/components/schemas/Review")
 *     ),
 *     @OA\Response(response=404, description="Review not found")
 * )
 *
 * @OA\Get(
 *     path="/reviews",
 *     summary="Get list of reviews, optionally filtered by patient ID",
 *     tags={"Reviews"},
 *     @OA\Parameter(
 *         name="patient_id",
 *         in="query",
 *         description="Filter reviews by patient ID",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of reviews",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Review"))
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     required={"patient_id", "appointment_id", "rating", "comment", "sentiment"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="appointment_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="comment", type="string", example="keren banget!"),
 *     @OA\Property(property="sentiment", type="string", example="positive"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-25T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-25T10:00:00Z")
 * )
 */

$router->post('/reviews', function (Request $request) {
    $data = $request->only(['patient_id', 'appointment_id', 'rating', 'comment']);

    // Validate appointment_id
    if (empty($data['appointment_id']) || !is_numeric($data['appointment_id'])) {
        return response()->json(['error' => 'Invalid or missing appointment ID'], 400);
    }

    // Validate patient_id
    if (empty($data['patient_id']) || !is_numeric($data['patient_id'])) {
        return response()->json(['error' => 'Invalid or missing patient ID'], 400);
    }

    $client = new Client();

    try {
        $patientResp = $client->get("http://localhost:8001/patients/{$data['patient_id']}");
        if ($patientResp->getStatusCode() !== 200) {
            return response()->json(['error' => 'Invalid patient ID'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'UserService unavailable or invalid patient'], 400);
    }

    try {
        $appointmentResp = $client->get("http://localhost:8002/appointments/{$data['appointment_id']}");
        if ($appointmentResp->getStatusCode() !== 200) {
            return response()->json(['error' => 'Invalid appointment ID'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'AppointmentService unavailable or invalid appointment'], 400);
    }

    $apiKey = getenv('OPENAI_API_KEY');
    $openAiClient = OpenAI::client($apiKey);

    // Call OpenAI chat to get the sentiment analysis
    $prompt = "Please analyze the sentiment based on the following review comment and its rating (in the context of Healthcare Appointments Review). Reply with only one or two words: positive, negative, highly positive, highly negative, or neutral.\n\nComment: \"{$data['comment']}\"\nRating: {$data['rating']}";

    $response = $openAiClient->chat()->create([
        'model' => 'gpt-4.1-mini',  // or your preferred model
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $sentiment = strtolower(trim($response->choices[0]->message->content));

    $validSentiments = ['positive', 'negative', 'neutral', 'highly positive', 'highly negative'];

    if (!in_array($sentiment, $validSentiments)) {
        $sentiment = 'neutral'; //
    }

    // Save review with sentiment
    $review = Review::create([
        'patient_id' => $data['patient_id'],
        'appointment_id' => $data['appointment_id'],
        'rating' => $data['rating'],
        'comment' => $data['comment'],
        'sentiment' => $sentiment,
    ]);

    return response()->json($review);
});

$router->get('/reviews/{id}', function ($id) {
    $review = Review::find($id);
    if (!$review) {
        return response()->json(['error' => 'Review not found'], 404);
    }
    return response()->json($review);
});

$router->get('/reviews', function (Request $request) {
    $patientId = $request->query('patient_id');

    if ($patientId) {
        $reviews = Review::where('patient_id', $patientId)->get();
    } else {
        $reviews = Review::all();
    }

    return response()->json($reviews);
});
