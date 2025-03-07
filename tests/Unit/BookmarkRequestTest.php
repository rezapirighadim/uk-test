<?php

use App\Http\Requests\BookmarkRequest;
use Illuminate\Support\Facades\Validator;

test('valid urls pass validation', function () {
    // Create a request instance
    $request = new BookmarkRequest();

    // Get validation rules
    $rules = $request->rules();

    // Valid URLs
    $validUrls = [
        'https://example.com',
        'http://example.org',
        'https://example.com/path/to/page',
        'https://subdomain.example.com',
        'http://example.com?param=value',
        'https://www.example.com/page#section',
    ];

    foreach ($validUrls as $url) {
        // Create validator for each URL
        $validator = Validator::make(['url' => $url], $rules);

        // Assert that validation passes
        expect($validator->fails())->toBeFalse();
    }
});


test('request has custom error messages', function () {
    $request = new BookmarkRequest();

    $messages = $request->messages();

    expect($messages)->toBeArray()
        ->and($messages)->toHaveKey('url.required')
        ->and($messages)->toHaveKey('url.url')
        ->and($messages)->toHaveKey('url.max');
});

test('authorization is always true', function () {
    $request = new BookmarkRequest();

    expect($request->authorize())->toBeTrue();
});
