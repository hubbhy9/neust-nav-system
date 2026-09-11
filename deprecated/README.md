# Deprecated / Unused Files

These files were confirmed **not referenced by any `include`/`require`** anywhere
in the live codebase (checked via grep across the whole project on 2026-09-09).
They are experiments or earlier drafts of `map.php` / `dashboard.php` / `login.php`.

| File | Size | Notes |
|---|---|---|
| alternative.php | 3,683 lines | Earlier draft of the map/navigation page |
| alternative2.php | 2,063 lines | Second draft, not wired in |
| alt2.php | 304 lines | Small variant, not wired in |
| trial.php / trial.js / trial.css | — | Experimental navigation UI |
| lol.php | 1,280 lines | Unclear purpose, not referenced anywhere |
| tmp_extracted.js | — | Looks like a scratch/temp file |

**Nothing currently in production (`login.php`, `dashboard.php`, `map.php`, `crud/`,
`functions/`) requires any of these.** They're moved here rather than deleted so you
can recover anything useful. Safe to delete this whole folder once you've confirmed
you don't need anything from it.
