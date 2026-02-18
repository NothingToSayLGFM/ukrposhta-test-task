<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Service\PostIndexService;
use App\DTO\PostIndexDTO;

class PostIndexController
{
    public function __construct(private PostIndexService $service) {}

    /**
     * GET /post-indexes
     *
     * Retrieve post indexes from the database.
     *
     * Query parameters:
     * - post_code: string - retrieve a specific post index
     * - q: string - search by part of the address (city or post office)
     * - page: int - pagination page number (50 records per page)
     *
     * Returns JSON array of PostIndexDTO objects or a single object if post_code is provided.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $postCode = $params["post_code"] ?? null;
        $q = $params["q"] ?? null;
        $page = max((int) ($params["page"] ?? 1), 1);

        if ($postCode) {
            $data = $this->service->getByPostCode($postCode);
            $result = $data ? $data->toArray() : null;
        } elseif ($q) {
            $result = $this->service->search($q, $page);
        } else {
            $result = $this->service->listAll($page);
        }

        $response
            ->getBody()
            ->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader("Content-Type", "application/json");
    }

    /**
     * POST /post-indexes
     *
     * Add one or more post indexes to the database.
     *
     * Request body: JSON array of PostIndexDTO objects
     *
     * Example payload:
     * [
     *   {
     *     "post_code": "99999",
     *     "region": "Test Region",
     *     "district": "Test District",
     *     "city": "Test City",
     *     "post_office": "Test Post Office"
     *   }
     * ]
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function add(Request $request, Response $response): Response
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            $response
                ->getBody()
                ->write(json_encode(["error" => "Invalid payload"]));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $dtos = array_map(fn($item) => new PostIndexDTO($item), $payload);
        $this->service->add($dtos);

        $response->getBody()->write(json_encode(["status" => "ok"]));
        return $response->withHeader("Content-Type", "application/json");
    }

    /**
     * DELETE /post-indexes
     *
     * Delete one or more post indexes from the database.
     *
     * Request body: JSON object with "post_codes" array
     *
     * Example payload:
     * {
     *   "post_codes": ["99999", "88888"]
     * }
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function delete(Request $request, Response $response): Response
    {
        $payload = $request->getParsedBody();
        $postCodes = $payload["post_codes"] ?? null;

        if (!is_array($postCodes) || empty($postCodes)) {
            $response
                ->getBody()
                ->write(json_encode(["error" => "No post_codes provided"]));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $this->service->delete($postCodes);
        $response->getBody()->write(json_encode(["status" => "deleted"]));
        return $response->withHeader("Content-Type", "application/json");
    }
}
