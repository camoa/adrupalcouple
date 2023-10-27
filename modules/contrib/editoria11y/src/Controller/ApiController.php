<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * API class project.
 */
class ApiController extends ControllerBase {

  /**
   * API private property.
   *
   * @var \Drupal\editoria11y\Api
   */
  private $api;

  /**
   * Constructs a \Drupal\editoria11y\Api ReportsController object.
   *
   * @param string $api
   *   The editoria11y API service.
   */
  public function __construct($api) {
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('editoria11y.api')
      );
  }

  /**
   * Function to report the results.
   */
  public function report(Request $request) {
    try {
      $results = Json::decode($request->getContent());
      $this->api->testResults($results);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * OK function to check if everything is good.
   */
  public function ok(Request $request) {
    try {
      $dismissal = Json::decode($request->getContent());

      $this->api->dismiss("ok", $dismissal);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Function to hide elements.
   */
  public function hide(Request $request) {
    try {
      $dismissal = Json::decode($request->getContent());
      $this->api->dismiss("hide", $dismissal);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Function to reset the responses.
   */
  public function reset(Request $request) {
    try {
      $dismissal = Json::decode($request->getContent());
      $this->api->dismiss("reset", $dismissal);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * The purgePage function.
   */
  public function purgePage(Request $request) {
    try {
      $page = Json::decode($request->getContent());
      $this->api->purgePage($page);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Purge Dismissals function.
   */
  public function purgeDismissals(Request $request) {
    try {
      $data = Json::decode($request->getContent());
      $this->api->purgeDismissal($data);
      return new JsonResponse("ok");
    }
    catch (\Exception $e) {
      return $this->sendErrorResponse($e);
    }
  }

  /**
   * Function to send error messages.
   */
  private function sendErrorResponse($e) {
    // @todo Record exceptions in log.
    return new JsonResponse(
          [
            "message" => "error",
            "description" => $e->getMessage(),
            "code" => $e->getCode(),
          ]
      );
  }

}
