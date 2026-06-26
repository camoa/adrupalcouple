# ADrupalCouple — Deployment Guide

How to deploy the ADrupalCouple sub-theme + content model + the parity/a11y work
built on the `adrupalcouple-rebuild` branch. Read this **in full** before deploying —
there are three manual steps (`§6`, `§7`, `§8`) that the automated `scripts/deploy.sh`
does **not** cover.

> **Status at time of writing (2026-06-26):** all work is committed locally on
> `adrupalcouple-rebuild`. **Nothing is pushed; no PR exists.** Origin
> (`camoa/adrupalcouple`) currently only has `main`. Deciding how this reaches the
> target (push the branch + merge, or fast-forward `main`) is the first decision below.

---

## 1. What this deploys

A `ui_suite_daisyui` sub-theme (`adrupalcouple`) + reconciled content model (article /
couple / person / page) + Layout-Builder Home & About, rebuilt to match the React
reference (`brand/adrupalcouple-brand/react-template`). The most recent layer (this
session) is the visual-parity + accessibility + native-Drupal-cleanup pass.

**Relevant commits (newest first), all on `adrupalcouple-rebuild`:**

| Commit | What |
|---|---|
| `32dad9124` | Language switcher → core `language_block` themed as the React EN/ES pill toggle (dropdown_language removed); comment textarea placeholder |
| `ed79b27a7` | **Re-enable article comments** (React intends them) + "Filed under" tag label |
| `6afd22dfd` | Couple avatars → native media `couple_faces` view mode (avatar-URL preprocess removed) |
| `7e0a1c8da` | Shell (breadcrumb/login-block off, footer copy), card (multi-pill tags, native responsive thumbnail, navigable title/image/author), couples card, about hero/§3, comment suppression, empty-heading a11y sweep, **extlink** install |
| `655b18830` | (earlier) couple faces side-by-side + language switcher |
| `885e814d7` | (earlier) Home + About as real Layout Builder |

---

## 2. Deployment model (four independent channels)

| Channel | Mechanism | Source of truth |
|---|---|---|
| **CONFIG** | `drush config:import` (cim) | `sites/default/sync/` (in git) |
| **CONTENT** (build-created only) | `single_content_sync` `content:import` | `content-export/*.yml` (in git) |
| **i18n** (interface strings) | `drush locale:import es` | `themes/custom/adrupalcouple/translations/es.po` |
| **THEME build** | Vite (`npm run build`) → `dist/css` | source `css/` + templates (dist is **gitignored**, regenerated) |

Recipes (`recipes/`) are **build-time scaffolding only** — NOT the deploy mechanism.
Config sync + content sync + the theme build are.

---

## 3. Prerequisites (target environment)

- **Drupal 11**, PHP per `composer.json`, drush.
- **Node ≥ 20 for the theme build.** ⚠️ **The Vite build does NOT run on Node 16** —
  vite's `lru-cache` needs `node:diagnostics_channel.tracingChannel` (Node 20+). The DDEV
  web container here ships **Node 16**, so the theme is built on the **host (Node 22)**, not
  in the container. On the target: either build on a Node-20+ host and ship `dist/`, or
  bump the container/CI Node to 20+. (Logged as part of [[G-CONV-28]].)
- Contrib modules resolve via `composer install` (see §4). Notably **`drupal/extlink`** is
  now required and **`drupal/dropdown_language` was removed**.
- A writable `sites/default/files` and the DB.

---

## 4. Automated deploy (`scripts/deploy.sh`)

`scripts/deploy.sh` runs, idempotently, on the target after pulling the code:

1. `composer install --no-dev` (installs extlink, drops dropdown_language per the lockfile)
2. theme build — `cd themes/custom/adrupalcouple && npm ci && npm run build`
   ⚠️ **this step needs Node ≥ 20** (see §3). If the target runs an old Node, build the
   theme elsewhere and copy `dist/` instead of running this step in-container.
3. `drush updatedb -y`
4. `drush config:import -y`  ← applies all config (§5)
5. `drush content:import content-export/*.yml -y`  ← build-created content (§6)
6. `drush locale:import es …/translations/es.po`  ← interface translations (§7 — incomplete!)
7. `drush cache:rebuild`

**`deploy.sh` does NOT do §6's `field_founding` data step, §7's new-string translations, or
§8's verification.** Do those manually.

---

## 5. Config applied by `cim` (notable deltas this branch)

These are already in `sites/default/sync/` and apply via `config:import`. Listed so a
reviewer knows what changes on the target:

- **Blocks disabled:** `block.block.adrupalcouple_breadcrumbs`,
  `…_useraccountmenu` (the "Log in" leak), `…_page_title` (was producing empty/duplicate
  `<h1>`s — the a11y fix).
- **Language switcher block** swapped to `language_block:language_interface` (core).
- **Module set** (`core.extension`): `extlink` **enabled**, `dropdown_language`
  **removed**. Ensure `composer install` ran first so extlink's code is present.
- **Views:** `views.view.taxonomy_term` — tid argument "Override title" = `{{ arguments.tid }}`
  (the term name; replaced a preprocess).
- **Entity displays:** `core.entity_view_display.node.article.full`/`default` —
  `field_comments` **visible** (comments on). `core.entity_form_display.comment.comment.default`
  — comment_body placeholder.

No content-type/field storage changes in this branch (those landed earlier on the branch).

---

## 6. Content — `content-export/` + ONE manual data step

**`content:import content-export/*.yml`** (in deploy.sh) imports only the **build-created
structural content** (existing content is SKIPPED — your articles/persons/couples are never
touched):

- `node-page-*.yml` ×3 — **Home**, **About** (incl. the §4 contact: "Find us on LinkedIn —
  Carlos and Ana", both profiles, no email), **Accessibility**. These carry their Layout
  Builder layouts + inline blocks.
- `menu_link_content-*.yml` ×7 — main + footer menu links.

> ⚠️ **MANUAL STEP — `field_founding` on the couple (NOT exported).** The "Founding couple"
> badge needs `field_founding = TRUE` on the Anilu & Camoa couple. That is **Carlos's
> content** (deliberately never round-tripped via single_content_sync), so it is a live DB
> edit that does **not** travel in `content-export/`. On the target, set it once:
> ```bash
> drush php:eval '$ns=\Drupal::entityTypeManager()->getStorage("node")->loadByProperties(["type"=>"couple","title"=>"Anilu & Camoa"]); $n=reset($ns); $n->set("field_founding",TRUE)->set("moderation_state","published")->save();'
> ```
> (Or set it in the UI: edit the couple → check "Founding couple" → save.)

---

## 7. Interface translations (ES) — `es.po` is INCOMPLETE for the new strings

`locale:import es` loads `themes/custom/adrupalcouple/translations/es.po`. **New EN strings
added this session are NOT yet translated** → the ES side of the site will show English for
them until the `.po` is updated. Add these (verified missing):

| English (source) | Spanish (from React `*.strings.ts`) | Where |
|---|---|---|
| `Filed under` | `Temas` | article tag label (`articlePage.strings.ts`) |
| `Founding couple` | `Pareja fundadora` | couples card badge |
| `Written by two people, from both worlds.` | *(confirm ES)* | footer tagline |
| `Built with Drupal, read on quiet pages.` | *(confirm ES)* | footer copyright |
| `The couple` | *(already in es.po)* | footer column heading ✓ |

The language-switcher labels are literal `EN` / `ES` — no translation needed.

> ⚠️ **The comment placeholder is CONFIG, not a `t()` string** — "Share what you have seen
> in production…" lives in `core.entity_form_display.comment.comment.default`. Its ES value
> ("Cuenta lo que has visto en producción…") needs a **config-language override**
> (config translation), not a `.po` entry. Add via the Config Translation UI or
> `language.config.es.core.entity_form_display.comment.comment.default` override if ES parity
> of the placeholder is required.

**To regenerate/extend `es.po`:** add the rows above, re-run `locale:import es … --type=customized
--override=not-customized`, then `drush cr`.

---

## 8. Post-deploy verification checklist

After `deploy.sh` + the manual steps, verify on the target (a `Node ≥ 20` build must have run):

- [ ] **Theme/fonts:** home renders with brand fonts (no 404s on `dist/css/files/*.woff2`),
      brand colors (plum/teal), no red/yellow.
- [ ] **Logo + favicon:** header shows the brand "ac" logo (`logo.svg`), not a text wordmark;
      browser tab shows the brand favicon (theme `favicon.ico` + the apple-touch / PNG links
      from `hook_page_attachments_alter`). (Branding block: `use_site_logo=true`,
      `use_site_name=false` — applied by `cim`.)
- [ ] **Header:** nav shows **Writing · Couples · About**, **no "Log in"**, the EN/ES **pill
      toggle** (active language filled teal), dark-mode ☾. No breadcrumb.
- [ ] **One `<h1>` per page** (page-title block is off): check home/writing/about/couples/tag/
      article/person/couple-profile/accessibility — exactly one non-empty h1 each.
- [ ] **Cards:** article cards show multi-pill tags + 16:9 **responsive** thumbnails
      (`<picture>`/`srcset`), and the **title + image + author** are all clickable.
- [ ] **Couples/About:** couple avatars render (native media `couple_faces`); About hero =
      overlapping avatars before the name; §3 faces-first.
- [ ] **Article:** comments **present** ("Add new comment" + placeholder "Share what you have
      seen in production…"); "Filed under" tag label.
- [ ] **Couples card:** name + "Founding couple" badge (needs §6) + clean excerpt (no
      "Who are we" leak).
- [ ] **External links** (LinkedIn etc.): show the extlink icon + "(opens in a new window)".
- [ ] **Footer:** "The couple" column, "Written by two people, from both worlds.", "read on
      quiet pages."
- [ ] **ES side** (`/es`): re-check after §7 — strings above should be Spanish.
- [ ] `drush config:status` → **No differences** (active == sync).

---

## 9. Known caveats / gotchas

- **Node 16 ≠ buildable** (see §3) — the single biggest deploy footgun.
- **Tailwind cascade-layer quirk:** in this build a base/`@layer components` rule can
  out-cascade a responsive/same-layer one (DaisyUI `.btn` vs custom). Two rules deliberately
  work around it with **unlayered** CSS (`css/a11y.pcss`, the `.language-link.is-active`
  fill in `css/layout/_nav.pcss`) and the menu's `max-md:hidden md:flex`. Don't "tidy" these
  into `@layer` — they'll break. (Logged G-CONV-46.)
- **`dist/` is gitignored** — the theme MUST be rebuilt on deploy; the committed templates
  carry the Tailwind classes the build scans.
- Existing content is **skipped** on content-import — re-running deploy never clobbers
  Carlos's articles/persons/couples.

---

## 10. Outstanding / deferred (not blocking deploy)

- **Comment intro copy:** React frames comments with a "Comments" heading + "Comments are
  open on articles. Add yours below." hint; Drupal shows its standard "Add new comment".
  Matching React's exact copy needs a template/form-alter — intentionally left (cosmetic).
- **`field_author_focus`** (person "what they write about") — likely body/bio copy, not a
  field; do NOT create it without re-checking the React source (same over-engineering class
  as the dropped `field_closing_question`). See gap log G-CONV-26.
- **Push/PR decision:** the branch is local only. Choose: push `adrupalcouple-rebuild` + open
  a PR into `main`, or fast-forward `main` locally then push.

---

## Quick deploy (happy path, Node-20+ target)

```bash
git checkout adrupalcouple-rebuild && git pull            # or merge into main
bash scripts/deploy.sh                                    # §4 (needs Node ≥ 20 for the build)
# manual:
drush php:eval '$ns=\Drupal::entityTypeManager()->getStorage("node")->loadByProperties(["type"=>"couple","title"=>"Anilu & Camoa"]); $n=reset($ns); $n->set("field_founding",TRUE)->set("moderation_state","published")->save();'  # §6
# then update es.po per §7, re-run locale:import es, drush cr
# then walk §8 checklist
```
