<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_report\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org report dialog JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgReportDialogJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_report'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests report link dialogs and the programmatic dialog opener.
   */
  public function testReportDialogJavaScript(): void {
    $this->drupalLogin($this->drupalCreateUser(['access site reports']));
    $this->drupalGet('admin/reports/schemadotorg/Thing');

    $assert = $this->assertSession();

    // Check the report library exposes the public dialog opener.
    $this->assertJsCondition('typeof Drupal.schemaDotOrgOpenDialog === "function"');

    // Click a report link.
    $link = $this->getSession()->getPage()->find('css', 'a[href*="/admin/reports/schemadotorg/"]');
    $this->assertNotEmpty($link);
    $link->click();
    // Check that clicking a report link opens a modal dialog.
    $assert->waitForElementVisible('css', '.ui-dialog');
    $assert->elementExists('css', '.ui-dialog');

    // Check that the programmatic opener creates the expected Drupal AJAX.
    $this->getSession()->executeScript(<<<'JS'
window.schemaDotOrgReportDialogAjax = null;
const originalAjax = Drupal.ajax;
const originalClick = HTMLAnchorElement.prototype.click;
Drupal.ajax = (options) => {
  window.schemaDotOrgReportDialogAjax = options;
  return {};
};
HTMLAnchorElement.prototype.click = () => {};
window.schemaDotOrgReportDialogUrl = Drupal.url('admin/reports/schemadotorg/Person');
Drupal.schemaDotOrgOpenDialog(window.schemaDotOrgReportDialogUrl);
Drupal.ajax = originalAjax;
HTMLAnchorElement.prototype.click = originalClick;
JS);
    // Check that the public opener creates the expected Drupal AJAX request.
    $this->assertJsCondition(<<<'JS'
(function () {
  const ajax = window.schemaDotOrgReportDialogAjax;
  return ajax !== null
    && ajax.url === window.schemaDotOrgReportDialogUrl
    && ajax.dialogType === 'modal'
    && ajax.dialog.width === '1000px';
}())
JS);

    // Check that non-report URLs navigate instead of opening a dialog.
    $this->getSession()->executeScript("Drupal.schemaDotOrgOpenDialog(Drupal.url('admin/reports'));");
    $this->getSession()->wait(10000, "window.location.pathname === new URL(Drupal.url('admin/reports'), window.location.origin).pathname");
    $assert->addressEquals('/admin/reports');
  }

}
