<?php

namespace TravelAI\Services;

use TravelAI\Config\EnvConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private Client $httpClient;
    
    public function __construct()
    {
        $this->apiKey = EnvConfig::getGeminiApiKey();
        $this->model = EnvConfig::getGeminiModel();
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true
        ]);
    }
    
    public function generateTripPlan(string $startPlace, string $destination): ?array
    {
        $prompt = $this->buildPrompt($startPlace, $destination);
        
        try {
            $response = $this->callGeminiApi($prompt);
            return $this->parseResponse($response, $startPlace, $destination);
        } catch (\Exception $e) {
            error_log("Gemini API Error: " . $e->getMessage());
            return null;
        }
    }
    
    private function buildPrompt(string $startPlace, string $destination): string
    {
        return <<<PROMPT
You are a professional travel planning assistant. Generate a detailed travel plan from {$startPlace} to {$destination}.

Please provide the following information in a structured format:

1. ESTIMATED BUDGET (in INR):
   - Total estimated budget
   - Breakdown: flights, accommodation, food, activities, miscellaneous

2. FLIGHT SUGGESTIONS (provide 2-3 options):
   For each flight:
   - Flight name/airline and flight number
   - Price in INR
   - Duration
   - Departure airport code
   - Arrival airport code

3. BEST PLACES TO VISIT (5-7 locations):
   For each location:
   - Name of the place
   - Brief description (2-3 sentences)
   - Recommended time to visit (morning/afternoon/evening)

4. BEST SEASON/TIME TO TRAVEL:
   - Describe the best months/season to visit
   - Weather conditions
   - Any festivals or events

5. SUGGESTED ITINERARY (day-by-day for 3-5 days):
   For each day:
   - Day number
   - List of activities/places to visit
   - Brief timing suggestions

Format your response as a JSON object with the following structure:
{
  "budget": {
    "estimatedTotal": number,
    "currency": "INR",
    "breakdown": {
      "flights": number,
      "accommodation": number,
      "food": number,
      "activities": number,
      "miscellaneous": number
    }
  },
  "flights": [
    {
      "flightName": "string",
      "price": number,
      "currency": "INR",
      "duration": "string",
      "from": "string",
      "to": "string"
    }
  ],
  "locations": [
    {
      "name": "string",
      "description": "string",
      "recommendedTime": "string"
    }
  ],
  "seasonInfo": "string",
  "itinerary": [
    {
      "day": number,
      "activities": ["string"]
    }
  ]
}

Respond ONLY with valid JSON, no additional text or markdown.
PROMPT;
    }
    
    private function callGeminiApi(string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];
        
        try {
            $response = $this->httpClient->post($url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            $body = json_decode($response->getBody()->getContents(), true);
            
            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                return $body['candidates'][0]['content']['parts'][0]['text'];
            }
            
            throw new \Exception('Invalid response format from Gemini API');
        } catch (GuzzleException $e) {
            throw new \Exception('Gemini API request failed: ' . $e->getMessage());
        }
    }
    
    private function parseResponse(string $response, string $startPlace, string $destination): ?array
    {
        // Clean the response - remove markdown code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);
        
        // Try to decode JSON
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON parsing fails, return a default structure
            error_log("Failed to parse Gemini response as JSON: " . json_last_error_msg());
            return $this->getDefaultTripPlan($startPlace, $destination);
        }
        
        // Validate and structure the response
        return [
            'startPlace' => $startPlace,
            'destination' => $destination,
            'budget' => $data['budget'] ?? $this->getDefaultBudget(),
            'flights' => $data['flights'] ?? [],
            'locations' => $data['locations'] ?? [],
            'seasonInfo' => $data['seasonInfo'] ?? 'Contact a travel agent for seasonal information.',
            'itinerary' => $data['itinerary'] ?? []
        ];
    }
    
    private function getDefaultTripPlan(string $startPlace, string $destination): array
    {
        return [
            'startPlace' => $startPlace,
            'destination' => $destination,
            'budget' => $this->getDefaultBudget(),
            'flights' => [],
            'locations' => [],
            'seasonInfo' => 'Please consult with a travel expert for detailed seasonal information.',
            'itinerary' => []
        ];
    }
    
    private function getDefaultBudget(): array
    {
        return [
            'estimatedTotal' => 0,
            'currency' => 'INR',
            'breakdown' => [
                'flights' => 0,
                'accommodation' => 0,
                'food' => 0,
                'activities' => 0,
                'miscellaneous' => 0
            ]
        ];
    }
}