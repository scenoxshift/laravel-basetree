<?php


namespace BaseTree\Tests\Integration\Lumen\API;


use BaseTree\Responses\JsonResponse;
use BaseTree\Tests\Integration\LumenTestCase;

class RouteResponsesTest extends LumenTestCase
{
    /** @test */
    public function not_defined_route_should_return_not_found_response(): void
    {
        $response = $this->jsonGet('missing-route');
        $response->assertResponseStatus(JsonResponse::HTTP_NOT_FOUND);
//        $this->assertEquals('Route does not exist.', $response->json('message'));
    }

    /** @test */
    public function not_defined_method_for_valid_route_should_return_method_not_allowed_response(): void
    {
        $response = $this->jsonPost('get-route-no-action');
        $response->assertResponseStatus(JsonResponse::HTTP_METHOD_NOT_ALLOWED);
//        $this->assertEquals('Method not allowed on this route.', $response->json('message'));
    }

    /** @test */
    public function any_internal_application_error_should_respond_with_internal_error_response(): void
    {
        $response = $this->jsonGet('get-route-no-action');
        $response->assertResponseStatus(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
//        $this->assertEquals('Class MissingController does not exist', $response->json('message'));
    }
}