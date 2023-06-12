Schema.org Blueprints
---------------------

# Tips

- Schema.org Blueprints module should provide 80% of a site's base content
  architecture and the remaining 20% is custom configuration and code.

- The structured data examples from Schema.org should be considered the
  canonical reference for implementation guidelines.

- For SEO friendly structured data examples, Google should be a close second.

- Relationships should be a top down (a.k.a. parent to child) and not a child
  to parent relationships.
  - Use 'episodes' instead of 'partOfSeason'.
  - Use 'has' instead of 'partOf'.
  - Top down makes it easier to build JSON-LD which recurse downward.
  - Top down supports inline entity references with weighting.

# Supported/recommended entity and field types

_The use-case for different entity and field types._

- **Content:** Used for any Schema.org types that should have a dedicated URL.

- **Paragraphs:** Used for complex Intangibles and StructuredData.

- **Block:** Used for Intangibles and Schema.org types that are embedded in node and layouts.

- **Taxonomy:** Used for vocabularies of DefinedTerm and CategoryCode sets.

- **User:** Used for when a dedicated Person type is needed for online community management.

- **Flexfield:** User for simple Intangibles and StructuredData. \[DEPRECATED\]
