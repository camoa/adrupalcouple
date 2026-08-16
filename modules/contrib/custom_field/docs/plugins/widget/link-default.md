# Link widget
The **Link** (`link_default`) widget provides a user-friendly interface for entering web
addresses using the native HTML `<input type="url">` element. It optionally
supports a separate **Link text** field and additional configuration options for
enabling other HTML attributes.

## Settings
| Setting           | Label                           | Description                                                 | Default |
|-------------------|---------------------------------|-------------------------------------------------------------|:--------|
| placeholder_url   | Placeholder for URL             | Text shown inside the URL field when empty                  |         |
| placeholder_title | Placeholder for link text       | Text shown inside the link text field when empty            |         |
| maxlength         | Max length for link text        | Maximum amount of characters allowed in the link text field | 255     |
| maxlength_js      | Show max length character count | Display a live character counter below the link text field  | FALSE   |

> [!Note]
> The `maxlength_js` setting is visible when the `title` field is enabled and
> the [MaxLength](https://www.drupal.org/project/maxlength){:target="_blank"} module is installed.

## Field types
- [link](../type/link.md)