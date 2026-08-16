<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\editoria11y\Api;
use Drupal\editoria11y\Exception\Editoria11yApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Issue reporting API.
 *
 * @noinspection PhpParamsInspection
 */
final class ApiController extends ControllerBase {

  /**
   * API private property.
   *
   * @var \Drupal\editoria11y\Api
   */
  private Api $api;

  /**
   * Constructs a \Drupal\editoria11y\Api ReportsController object.
   */
  public function __construct(Api $api) {
    $this->api = $api;
  }

  /**
   * Create API container.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
          $container->get('editoria11y.api')
      );
  }

  /**
   * Decodes and shape-checks a JSON request body.
   *
   * Json::decode() returns NULL for malformed JSON rather than throwing, so
   * without this check a bad body would only fail incidentally somewhere in
   * the Api layer.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Malformed request body.
   */
  private function decodeRequest(Request $request): array {
    $data = Json::decode($request->getContent());
    if (!is_array($data)) {
      throw new Editoria11yApiException('Invalid or empty JSON request body.');
    }
    return $data;
  }

  /**
   * Function to report the results.
   */
  public function report(Request $request): JsonResponse {
    try {
      $results = $this->decodeRequest($request);
      $this->api->testResults($results);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Function to hide elements.
   */
  public function dismiss(Request $request): JsonResponse {
    try {
      $dismissal = $this->decodeRequest($request);
      $this->api->dismiss($dismissal);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * The purgePage function.
   */
  public function purgePage(Request $request): JsonResponse {
    try {
      $data = $this->decodeRequest($request);
      $page = $data['pid'] ?? FALSE;
      $path = $data['page_path'] ?? FALSE;
      $this->api->purgePage($page, $path);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Purge Dismissals function.
   */
  public function purgeDismissals(Request $request): JsonResponse {
    try {
      $data = $this->decodeRequest($request);
      $this->api->purgeDismissal($data);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Function to send error messages.
   *
   * Intentional validation failures carry curated messages the dashboard
   * shows to the submitter. Anything else (e.g. a database exception) may
   * embed queries or driver details, so those are logged and the response
   * stays generic.
   */
  private function sendErrorResponse(\Exception $e): JsonResponse {
    if ($e instanceof Editoria11yApiException) {
      $this->getLogger('editoria11y')->notice('API request rejected: @message', ['@message' => $e->getMessage()]);
      $description = $e->getMessage();
    }
    else {
      $this->getLogger('editoria11y')->error('API request failed: @message', ['@message' => $e->getMessage()]);
      $description = (string) $this->t('The request could not be processed. Details have been logged.');
    }
    return new JsonResponse(
          [
            "message" => "error",
            "description" => $description,
          ],
          400
      );
  }

}
