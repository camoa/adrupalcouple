<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_help\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org help.
 *
 * @group schemadotorg
 */
class SchemaDotOrgHelpTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_help', 'schemadotorg_diagram'];

  /**
   * Test help.
   */
  public function testHelp(): void {
    global $base_path;

    $assert_session = $this->assertSession();
    $this->drupalLogin($this->createUser(['access administration pages']));

    // Check displaying help topics for all Schema.org Blueprints sub-modules.
    $this->drupalGet('/admin/help');
    $assert_session->responseContains('<h2>Schema.org Blueprints</h2>');
    $assert_session->responseContains('<p>The Schema.org Blueprints module uses Schema.org as the blueprint for the content architecture and structured data in a Drupal website.</p>');
    $assert_session->responseContains('<li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg">Schema.org Blueprints</a></li>');
    $assert_session->responseContains('<li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_diagram">Diagram</a></li>');
    $assert_session->responseContains('<li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_help">Help</a></li>');
    $assert_session->responseNotContains('<li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_diagram">Schema.org Blueprints Diagram</a></li>');
    $assert_session->responseNotContains('<li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_help">Schema.org Blueprints Help</a></li>');

    // Check help topic navigation.
    $this->drupalGet('/admin/help/schemadotorg/schemadotorg_help');
    $assert_session->responseContains('<div class="dropbutton-wrapper" data-drupal-ajax-container><div class="dropbutton-widget"><ul class="dropbutton"><li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg">Learn more about the Schema.org Blueprints modules</a></li><li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_diagram">Diagram</a></li><li><a href="' . $base_path . 'admin/help/schemadotorg/schemadotorg_help">Help</a></li></ul></div></div>');
    $assert_session->responseContains('&nbsp; or &nbsp;');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/help/schemadotorg/videos" class="use-ajax button button--small button--extrasmall" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800}">► Watch videos</a>');

    // Check converting a sub-module's README.md markdown into HTML.
    $this->drupalGet('/admin/help/schemadotorg/schemadotorg_help');
    $assert_session->responseContains('<li>Displays help topics for all Schema.org Blueprints sub-modules.</li>');
    $assert_session->responseContains('<li>Converts a sub-module\'s README.md markdown into HTML.</li>');
    $assert_session->responseContains('<li>Embeds diagrams in markdown via DIAGRAM.html and <a href="https://mermaid.js.org/">Mermaid.js</a>.</li>');
    $assert_session->responseContains('<li>Manages videos and provides a watch dialog.</li>');

    // Check embedding diagrams in markdown via DIAGRAM.html.
    $this->drupalGet('/admin/help/schemadotorg/schemadotorg_diagram');
    $assert_session->responseContains('<div class="mermaid" id="example">
  flowchart TB

  Organization((Organization))
  click Organization "https://schema.org/Organization"
  style Organization fill:#ffaacc,stroke:#333,stroke-width:4px;

  LocalBusiness[LocalBusiness]
  click LocalBusiness "https://schema.org/LocalBusiness"

  Organization ---&gt; |subOrganization| LocalBusiness
  LocalBusiness ---&gt; |parentOrganization| Organization
</div>');

    // Check that users with 'access administration pages'permission
    // can't access help videos.
    $this->drupalLogin($this->createUser(['access administration pages']));
    $this->drupalGet('/admin/help/schemadotorg/videos');
    $assert_session->statusCodeEquals(403);

    // Check that users with 'administer schemadotorg' permission can access help videos.
    $this->drupalLogin($this->createUser(['administer schemadotorg']));
    $this->drupalGet('/admin/help/schemadotorg/videos');
    $assert_session->statusCodeEquals(200);

    // Check that help videos display as expected.
    $this->drupalGet('/admin/help/schemadotorg/videos');
    $assert_session->responseContains('<a href="https://youtu.be/Yo6Vw-s1FtM" target="_blank">');
    $assert_session->responseContains('<img style="display: block" src="https://img.youtube.com/vi/Yo6Vw-s1FtM/0.jpg" alt="Schema.org Blueprints for Drupal @ Pittsburgh 2023" />');
    $assert_session->responseContains('<a href="https://youtu.be/Yo6Vw-s1FtM" target="_blank" class="button button--small button--extrasmall">▶ Watch video</a>');
  }

}
