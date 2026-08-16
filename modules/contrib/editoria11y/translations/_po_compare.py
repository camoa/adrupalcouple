#!/usr/bin/env python3
"""
Compare an upstream (human) .po against a machine-translated .po and emit a
review file of translation differences.

Usage:
    python3 _po_compare.py UPSTREAM.po MACHINE.po [--out review.tsv]

Output is a TSV with columns: KEY, MSGID, MACHINE, UPSTREAM[, PLURAL_FORM].
A KEY is "msgctxt\x1emsgid" so context-distinguished entries stay separate.
Only rows where:
    - upstream translation is non-empty, AND
    - upstream != machine
are emitted. (Identical strings and untranslated upstream entries are skipped.)

The companion script _po_apply.py reads a decision file and rewrites the
machine .po, replacing accepted entries with upstream translations.
"""
import argparse
import csv
import sys
import polib

SEP = "\x1e"  # ASCII record separator; safe in po content


def index(po):
    """Return {(msgctxt, msgid): entry} for non-obsolete entries."""
    out = {}
    for e in po:
        if e.obsolete:
            continue
        out[(e.msgctxt or "", e.msgid)] = e
    return out


def diffs(upstream_po, machine_po):
    up = index(upstream_po)
    mt = index(machine_po)
    rows = []
    for key, m_entry in mt.items():
        u_entry = up.get(key)
        if not u_entry:
            continue
        ctx, msgid = key
        # Plural entries
        if m_entry.msgid_plural:
            if not u_entry.msgid_plural:
                continue
            for idx, u_form in (u_entry.msgstr_plural or {}).items():
                m_form = (m_entry.msgstr_plural or {}).get(idx, "")
                if u_form and u_form != m_form:
                    rows.append({
                        "ctx": ctx,
                        "msgid": msgid,
                        "msgid_plural": m_entry.msgid_plural,
                        "plural_index": str(idx),
                        "machine": m_form,
                        "upstream": u_form,
                    })
        else:
            if u_entry.msgstr and u_entry.msgstr != m_entry.msgstr:
                rows.append({
                    "ctx": ctx,
                    "msgid": msgid,
                    "msgid_plural": "",
                    "plural_index": "",
                    "machine": m_entry.msgstr,
                    "upstream": u_entry.msgstr,
                })
    return rows


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("upstream")
    ap.add_argument("machine")
    ap.add_argument("--out", default="po_review.tsv")
    args = ap.parse_args()

    up_po = polib.pofile(args.upstream)
    mt_po = polib.pofile(args.machine)

    rows = diffs(up_po, mt_po)

    with open(args.out, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f, delimiter="\t", quoting=csv.QUOTE_MINIMAL)
        w.writerow(["decision", "ctx", "msgid", "plural_index", "machine", "upstream"])
        for r in rows:
            # decision starts blank — fill in "y" to accept upstream, "n" or
            # blank to keep machine, or write a custom string in the upstream
            # column to use that exact text.
            w.writerow(["", r["ctx"], r["msgid"], r["plural_index"], r["machine"], r["upstream"]])

    print(f"Wrote {len(rows)} differences to {args.out}", file=sys.stderr)


if __name__ == "__main__":
    main()
