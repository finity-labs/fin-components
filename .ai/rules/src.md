---
paths:
  - 'packages/*/src/**'
---

# Src

## Docblocks are src to the standing greps
The standing greps that guard these packages (`grep -rn "authorize('" src/`, `grep -rn "InstalledVersions" src/`, `grep -rn 'Laravel\Ai' src/`) run over the raw file text and do not know what a comment is. A docblock that spells out the banned form to explain why the code avoids it will match and fail the gate.

Reword the sentence instead of quoting the banned form: say "the closure form, never the string form" rather than writing `authorize('...')`, and name the SDK in prose rather than by class. Four separate Phase 10 executors tripped this independently and each had to reword after the fact.
