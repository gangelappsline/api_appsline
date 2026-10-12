<?php

it('serves the Scramble reference at the documentation URL', function () {
    $this->get('/docs')
        ->assertOk()
        ->assertSee('<html lang="en"', false);
});

it('generates an OpenAPI document with API routes and bearer authentication', function () {
    $response = $this->getJson('/docs/openapi.json')->assertOk();
    $document = $response->json();

    expect($document['openapi'])->toBeString()
        ->and($document['paths'])->toHaveKeys(['/login', '/projects', '/public/projects'])
        ->and($document['components']['securitySchemes']['http']['scheme'])->toBe('bearer')
        ->and($document['paths']['/public/projects']['get']['security'])->toBe([]);
});

it('respects the documentation access settings for the UI and OpenAPI document', function () {
    config(['docs.enabled' => false]);

    $this->get('/docs')->assertNotFound();
    $this->get('/docs/openapi.json')->assertNotFound();

    config(['docs.enabled' => true, 'docs.environments' => ['production']]);

    $this->get('/docs')->assertNotFound();
    $this->get('/docs/openapi.json')->assertNotFound();
});
