<?php

use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('middleware allows requests with valid token', function () {
    // Set up
    $validToken = 'test-api-token';
    config(['app.api_token' => $validToken]);

    $request = Request::create('/api/bookmarks', 'GET');
    $request->headers->set('Authorization', "Bearer {$validToken}");

    $middleware = new ApiTokenMiddleware();

    // Create a simple next callback
    $next = function ($request) {
        return response('OK');
    };

    // Execute middleware
    $response = $middleware->handle($request, $next);

    // Verify
    expect($response->getContent())->toBe('OK')
        ->and($response->getStatusCode())->toBe(200);
});

test('middleware rejects requests with invalid token', function () {
    // Set up
    $validToken = 'valid-token';
    $invalidToken = 'invalid-token';
    config(['app.api_token' => $validToken]);

    $request = Request::create('/api/bookmarks', 'GET');
    $request->headers->set('Authorization', "Bearer {$invalidToken}");

    $middleware = new ApiTokenMiddleware();

    // Create a simple next callback
    $next = function ($request) {
        return response('OK');
    };

    // Execute middleware
    $response = $middleware->handle($request, $next);

    // Verify
    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and(json_decode($response->getContent(), true))->toHaveKey('message');
});

test('middleware rejects requests with missing token', function () {
    // Set up
    $validToken = 'valid-token';
    config(['app.api_token' => $validToken]);

    $request = Request::create('/api/bookmarks', 'GET');
    // No Authorization header set

    $middleware = new ApiTokenMiddleware();

    // Create a simple next callback
    $next = function ($request) {
        return response('OK');
    };

    // Execute middleware
    $response = $middleware->handle($request, $next);

    // Verify
    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and(json_decode($response->getContent(), true))->toHaveKey('message');
});
