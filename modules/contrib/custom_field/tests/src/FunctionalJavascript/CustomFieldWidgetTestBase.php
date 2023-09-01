<?php

namespace Drupal\Tests\custom_field\FunctionalJavascript;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for testing custom field widget plugins.
 *
 * Test cases provided in this class apply to all widget plugins.
 */
abstract class CustomFieldWidgetTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'user',
    'system',
    'field',
    'text',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The custom fields on the test entity bundle.
   *
   * @var array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fields = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $this->fields = $entity_field_manager
      ->getFieldDefinitions('node', 'custom_field_entity_test');

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
  }

  /**
   * Generates random form data that is ready to save.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field to generate form data for.
   * @param array|int[] $deltas
   *   An array of deltas to generate form data for.
   *
   * @return array|string[]
   *   An associative array of form data.
   */
  protected function generateSampleFormData(FieldDefinitionInterface $field_definition, $deltas = NULL) {
    $field_name = $field_definition->getName();
    if ($deltas === NULL) {
      $deltas = [0];
    }

    $form_values = [];

    /** @var \Drupal\custom_field\CustomFieldGenerateDataInterface $random_generator */
    $random_generator = \Drupal::service('custom_field.generate_data');
    $field_settings = $field_definition->getSetting('field_settings');
    $columns = $field_definition->getSetting('columns');

    foreach ($deltas as $delta) {
      $random_values = $random_generator->generateFieldData($columns, $field_settings);

      // UUID's can't be unset through the GUI.
      unset($random_values['uuid_test']);

      // @todo Hardening: floating point calculation can randomly fail.
      $random_values['decimal_test'] = '0.50';
      $random_values['float_test'] = '10.775';

      // @todo Hardening: we need to treat maps specially due to ajax.
      unset($random_values['map_test']);

      // @todo Hardening: why do color fields not set using ::submitForm?
      unset($random_values['color_test']);

      $keys = array_map(static function ($key) use ($field_name, $delta) {
        return "{$field_name}[$delta][$key]";
      }, array_keys($random_values));

      $form_values[] = array_combine($keys, $random_values);
    }
    return array_merge(['title[0][value]' => 'Test'], ...$form_values);
  }

  /**
   * Test case for a single cardinality field.
   *
   * This method sets various field data and ensures that subsequent visits
   * to the node edit form displays the correct data in the correct places.
   */
  public function testWidgets() {
    $assert = $this->assertSession();
    $this->drupalGet('/node/add/custom_field_entity_test');

    // Fill out the single cardinality field.
    $form_values = $this->generateSampleFormData($this->fields['field_custom_field_test']);

    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');

    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }

    // Fill out the multiple cardinality field.
    $form_values = $this->generateSampleFormData(
      $this->fields['field_custom_field_test_multiple'],
      [0, 1, 2]
    );
    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }

    // Fill out the unlimited cardinality field (and add another several times).
    $page = $this->getSession()->getPage();
    for ($i = 0; $i < 4; ++$i) {
      $page->pressButton('Add another item');
      $assert->assertWaitOnAjaxRequest();
    }
    $form_values = $this->generateSampleFormData(
      $this->fields['field_custom_field_test_unlimite'],
      [0, 1, 2, 3, 4]
    );
    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }
  }

}
