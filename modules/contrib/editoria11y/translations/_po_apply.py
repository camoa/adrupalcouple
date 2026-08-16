#!/usr/bin/env python3
"""
Apply decisions from a review TSV back into a machine-translated .po file.

Usage:
    python3 _po_apply.py REVIEW.tsv MACHINE.po [--write]

Decision column values:
    y, yes, 1, accept, a -> use the upstream value (the upstream column)
    (anything else)      -> keep machine value

To override with a custom translation, edit the `upstream` column and set
`decision` to `y`.

This script does targeted line-level edits to preserve the original file's
line wrapping and formatting exactly. It handles single-line msgid entries
with optional msgctxt and single-line msgstr — the common case. For multi-
line / plural entries it falls back to polib (which may re-wrap lines).
"""
import argparse
import csv
import re
import sys

ACCEPT = {"y", "yes", "1", "accept", "a"}


def po_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")


def po_unescape(s: str) -> str:
    # minimal — handles \" \\ \n
    out = []
    i = 0
    while i < len(s):
        c = s[i]
        if c == "\\" and i + 1 < len(s):
            nxt = s[i + 1]
            if nxt == "n":
                out.append("\n"); i += 2; continue
            if nxt == "t":
                out.append("\t"); i += 2; continue
            if nxt in ('"', "\\"):
                out.append(nxt); i += 2; continue
        out.append(c); i += 1
    return "".join(out)


def find_entry_block(lines, ctx, msgid):
    """
    Return (msgstr_line_index, current_msgstr_string) for the entry matching
    (ctx, msgid), or (None, None) if not found / multi-line.

    Looks for a single-line `msgid "..."` (optionally preceded by
    `msgctxt "..."`) followed immediately by a single-line `msgstr "..."`.
    """
    want_ctx_line = f'msgctxt "{po_escape(ctx)}"' if ctx else None
    want_id_line = f'msgid "{po_escape(msgid)}"'
    for i, line in enumerate(lines):
        if line.rstrip("\n") != want_id_line:
            continue
        # check msgctxt above (if required)
        if want_ctx_line:
            # walk up over comment lines
            j = i - 1
            while j >= 0 and lines[j].startswith("#"):
                j -= 1
            if j < 0 or lines[j].rstrip("\n") != want_ctx_line:
                continue
        else:
            # ensure NO msgctxt above
            j = i - 1
            while j >= 0 and lines[j].startswith("#"):
                j -= 1
            if j >= 0 and lines[j].startswith("msgctxt "):
                continue
        # msgstr should be the very next line and not multi-line
        if i + 1 >= len(lines):
            return None, None
        m = re.match(r'^msgstr "(.*)"\s*$', lines[i + 1])
        if not m:
            return None, None
        # confirm no continuation lines
        if i + 2 < len(lines) and lines[i + 2].startswith('"'):
            return None, None
        return i + 1, po_unescape(m.group(1))
    return None, None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("review")
    ap.add_argument("machine")
    ap.add_argument("--write", action="store_true")
    args = ap.parse_args()

    with open(args.machine, encoding="utf-8") as f:
        lines = f.readlines()

    with open(args.review, encoding="utf-8") as f:
        rows = list(csv.DictReader(f, delimiter="\t"))

    changes = 0
    skipped = []
    for r in rows:
        decision = (r["decision"] or "").strip().lower()
        if decision not in ACCEPT:
            continue
        if r["plural_index"]:
            skipped.append((r["msgid"], "plural — apply manually"))
            continue
        idx, current = find_entry_block(lines, r["ctx"], r["msgid"])
        if idx is None:
            skipped.append((r["msgid"], "not found or multi-line — apply manually"))
            continue
        new_value = r["upstream"]
        if current == new_value:
            continue
        lines[idx] = f'msgstr "{po_escape(new_value)}"\n'
        changes += 1
        print(f"  ~ {r['msgid']!r}: {current!r} -> {new_value!r}")

    for msgid, reason in skipped:
        print(f"  ! skipped {msgid!r}: {reason}", file=sys.stderr)

    print(f"\n{changes} change(s).", file=sys.stderr)
    if args.write and changes:
        with open(args.machine, "w", encoding="utf-8") as f:
            f.writelines(lines)
        print(f"Wrote {args.machine}", file=sys.stderr)
    elif not args.write:
        print("(dry run — pass --write to save)", file=sys.stderr)


if __name__ == "__main__":
    main()
