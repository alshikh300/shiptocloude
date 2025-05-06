<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses {
    protected function ok($message, $data = []) {
        return $this->success($message, $data, 200);
    }

    protected function success($message, $data = [], $statusCode = 200): JsonResponse
    {
        return response()->json([
            'code' => "200",
            'status' => 200,
            //'status' => "Success",
            'message' => $message,
            'data' => $data

        ], $statusCode);
    }

    protected function error($errors = [], $code = 20): JsonResponse
    {
        if (is_string($errors)) {
            return response()->json([
                'code' => $code,
                'status' => $code,
                'message' => $errors,
            ], 200);
        }

        return response()->json([
            'code' => $errors['code'] ?? $code,
            'status' => $errors['code'] ?? $code,
            'errors' => $errors['messageText'] ?? $errors['messages'] ?? $errors,
        ],200);
    }

    protected function notAuthorized($message) {
        return $this->error([
            'status' => 401,
            'message' => $message,
            'source' => ''
        ]);
    }
}
