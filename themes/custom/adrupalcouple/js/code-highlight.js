/**
 * @file
 * ADrupalCouple honest code highlighter — theme-level Drupal behaviour.
 *
 * Philosophy: "honest, plain code over theatrical highlighting" (data-model.md G17).
 * This is a dependency-free tokenizer that tints comments, strings, keywords, numbers,
 * and PHP/JS variables. No Shiki, no Prism, no external CDN. Colors are fixed hex values
 * because code blocks always sit on the dark `--color-neutral` ground, so they read the same
 * in adc-light and adc-dark.
 *
 * Targets: <pre><code class="language-*"> produced by CKEditor5 codeBlock plugin on render.
 * Matches the React src/lib/highlight.ts tokenizer exactly.
 */

(function (Drupal) {

  'use strict';

  /** @type {Object.<string, string>} Fixed hex palette for the dark neutral ground. */
  const COLOR = {
    comment:  '#8a9a95',
    string:   '#d8b4d8',
    keyword:  '#7dd3c0',
    number:   '#e0b080',
    variable: '#9cdcfe',
  };

  /** Reserved keywords — shared across PHP, JS/TS, Python, Ruby, CSS-ish. */
  const KEYWORDS = [
    'function', 'return', 'if', 'else', 'elseif', 'foreach', 'for', 'while', 'do', 'switch',
    'case', 'break', 'continue', 'use', 'class', 'interface', 'trait', 'public', 'private',
    'protected', 'static', 'final', 'abstract', 'new', 'const', 'var', 'let', 'array', 'true',
    'false', 'null', 'echo', 'print', 'namespace', 'extends', 'implements', 'import', 'export',
    'from', 'as', 'try', 'catch', 'finally', 'throw', 'yield', 'async', 'await',
  ];

  /**
   * Escape HTML special chars in a plain text segment.
   *
   * @param {string} s Plain text string.
   * @return {string} HTML-escaped string.
   */
  function esc(s) {
    return s
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  /**
   * Wrap text in a coloured <span>.
   *
   * @param {string} color Hex colour value.
   * @param {string} text  Already-escaped HTML text.
   * @return {string} HTML span element.
   */
  function span(color, text) {
    return '<span style="color:' + color + '">' + text + '</span>';
  }

  /**
   * Highlight one non-string, non-comment segment: keywords, PHP variables, numbers.
   *
   * @param {string} seg Raw (un-escaped) text segment.
   * @return {string} HTML with inline colour spans.
   */
  function highlightSegment(seg) {
    var s = esc(seg);
    var re = new RegExp(
      '(\\$[a-zA-Z_]\\w*)|(\\b(?:' + KEYWORDS.join('|') + ')\\b)|(\\b\\d+\\b)',
      'g',
    );
    var out  = '';
    var last = 0;
    var m;
    while ((m = re.exec(s)) !== null) {
      out += s.slice(last, m.index);
      if (m[1])      out += span(COLOR.variable, m[1]);
      else if (m[2]) out += span(COLOR.keyword,  m[2]);
      else           out += span(COLOR.number,    m[3]);
      last = re.lastIndex;
    }
    out += s.slice(last);
    return out;
  }

  /**
   * Single-pass tokenizer: carves out comments and strings first (so their contents are
   * never re-tokenized), then tints the remaining segments for keywords/numbers/variables.
   *
   * @param {string} code Raw source text (NOT HTML-escaped).
   * @return {string} Highlighted HTML fragment.
   */
  function highlightToHtml(code) {
    var tokenRe = /(\/\/[^\n]*|#[^\n]*|\/\*[\s\S]*?\*\/)|('(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*")/g;
    var out     = '';
    var last    = 0;
    var m;
    while ((m = tokenRe.exec(code)) !== null) {
      out += highlightSegment(code.slice(last, m.index));
      if (m[1]) {
        // comment — italicise for visual rhythm
        out += span(COLOR.comment, '<i>' + esc(m[1]) + '</i>');
      } else {
        // string
        out += span(COLOR.string, esc(m[2]));
      }
      last = tokenRe.lastIndex;
    }
    out += highlightSegment(code.slice(last));
    return out;
  }

  /**
   * Drupal behaviour — attaches to every <pre><code> block in the rendered article body.
   * Safe to call multiple times (Drupal processes ensures once-only on attach).
   */
  Drupal.behaviors.adcCodeHighlight = {
    attach: function (context) {
      // Find all CKEditor5 code blocks: <pre><code class="language-*">
      // Also handle plain <pre><code> without a language class.
      var blocks = context.querySelectorAll('pre > code');
      blocks.forEach(function (code) {
        // Guard: skip already-highlighted blocks.
        if (code.dataset.adcHighlighted) {
          return;
        }
        code.dataset.adcHighlighted = '1';

        var rawText = code.textContent;
        // Only run the tokenizer on code blocks with meaningful content.
        if (!rawText || rawText.trim().length === 0) {
          return;
        }
        code.innerHTML = highlightToHtml(rawText);
      });
    },
  };

}(Drupal));
