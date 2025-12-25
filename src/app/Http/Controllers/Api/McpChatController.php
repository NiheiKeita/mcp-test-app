<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class McpChatController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array'],
            'messages.*.role' => ['required', 'string', 'in:system,user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $openaiKey = config('services.openai.key');
        if (! $openaiKey) {
            return response()->json(['error' => 'OPENAI_API_KEY is not set.'], 500);
        }

        $mcpUrl = config('services.mcp.url', 'http://localhost:5175');
        $model = config('services.openai.model', 'gpt-4o-mini');

        $toolsList = $this->fetchMcpTools($mcpUrl);

        $systemPrompt = "You are an assistant that can call MCP tools to operate a TV registration API. "
            . "Use mcp_list to inspect tools when needed. "
            . "Use mcp_call with tool name and JSON arguments. "
            . "Available tools: "
            . implode(', ', array_map(fn ($tool) => $tool['name'] ?? 'unknown', $toolsList));

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt],
        ], $validated['messages']);

        $toolDefinitions = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'mcp_list',
                    'description' => 'List MCP tool definitions.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'mcp_call',
                    'description' => 'Call an MCP tool by name with JSON arguments.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'arguments' => ['type' => 'object'],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],
        ];

        $firstResponse = $this->callOpenAi($openaiKey, $model, $messages, $toolDefinitions);
        $firstMessage = $firstResponse['choices'][0]['message'] ?? [];

        $toolCalls = $firstMessage['tool_calls'] ?? [];
        $messages[] = $firstMessage;
        $toolResults = [];

        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'] ?? '';
            $toolArguments = $toolCall['function']['arguments'] ?? '{}';
            $toolCallId = $toolCall['id'] ?? '';
            $parsedArgs = json_decode($toolArguments, true);

            if (! is_array($parsedArgs)) {
                $parsedArgs = [];
            }

            if ($toolName === 'mcp_list') {
                $toolResult = ['tools' => $toolsList];
            } elseif ($toolName === 'mcp_call') {
                $toolResult = $this->callMcpTool(
                    $mcpUrl,
                    (string) ($parsedArgs['name'] ?? ''),
                    $parsedArgs['arguments'] ?? []
                );
            } else {
                $toolResult = ['error' => "Unknown tool: {$toolName}"];
            }

            $toolResults[] = [
                'name' => $toolName,
                'arguments' => $parsedArgs,
                'result' => $toolResult,
            ];

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
            ];
        }

        $finalResponse = $this->callOpenAi($openaiKey, $model, $messages, $toolDefinitions, 'none');
        $finalMessage = $finalResponse['choices'][0]['message'] ?? [];

        return response()->json([
            'message' => $finalMessage,
            'tool_calls' => $toolResults,
        ]);
    }

    private function callOpenAi(
        string $apiKey,
        string $model,
        array $messages,
        array $tools,
        string $toolChoice = 'auto'
    ): array {
        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => $toolChoice,
                'temperature' => 0.3,
            ])
            ->throw();

        return $response->json();
    }

    private function fetchMcpTools(string $mcpUrl): array
    {
        try {
            $response = Http::timeout(5)->get(rtrim($mcpUrl, '/') . '/tools/list');
            $data = $response->json();
            if (is_array($data['tools'] ?? null)) {
                return $data['tools'];
            }
        } catch (\Throwable) {
            return [];
        }

        return [];
    }

    private function callMcpTool(string $mcpUrl, string $name, array $arguments): array
    {
        try {
            $response = Http::timeout(10)
                ->post(rtrim($mcpUrl, '/') . '/tools/call', [
                    'name' => $name,
                    'arguments' => $arguments,
                ])
                ->throw();

            return $response->json();
        } catch (\Throwable $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }
    }
}
