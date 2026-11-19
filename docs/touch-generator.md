# Touch generation on method pages

This guide describes touch search and its controls for contributors working on the method page. See the [development guide](development-deployment.md) for setup and JavaScript tests.

## Search model

- The **Touches** tab searches locally in browser web workers, showing distinct true touches ordered by length (shortest first), then by the preferred calling's music points per 100 changes (highest first).
- [Compile.js](../assets/js/touch_generator/Compile.js) prepares the method and calls; [Search.js](../assets/js/touch_generator/Search.js) explores call sequences, checks every row for truth, and removes rotation-equivalent results.
- The work is divided into separate jobs by:
  - the first two calls, for example "plain, plain", "plain, bob", "bob, plain" and "bob, bob" (more when singles are allowed);
  - the length limit (see below);
  - whether the touch comes round at a lead end or mid-lead.

  Together these give up to c20 jobs. Jobs never overlap, so several web workers (capped at half the user's number of cores, or 6) can search different jobs at the same time.
- Each worker searches its job for a short period of time, then stops and sends back any touches it found plus a snapshot of where it stopped. The snapshot goes back in the queue, and any free worker can later pick it up and carry on. Taking turns like this stops one long job from holding up the rest, and lets pausing or changing settings take effect quickly.
- Evaluating a discovered touch's rotations is a separate, resumable job. Workers yield between rotations, so long touches do not require assessing all their music within one search slice. Worker result batches are independent of the display limit.
- A job's progress is never split further. Late in a search, when only a few long jobs remain, some workers may sit idle.
- The length limits are about one, two and four courses, plus the full selected length. The pool alternates between them, which helps find multipart touches early without neglecting longer touches.
- Touches start at the method's first change and end at the first return to rounds. Multipart discovery repeats shorter call patterns within the selected length limits.
- [TouchSearchPool.js](../assets/js/ui/MethodView/TouchSearchPool.js) manages workers, task scheduling and shared results.
- Pruning tables skip impossible branches without replacing exact truth checks.
- Settings control lengths, minimum parts, calling positions, singles, music, display limits and search time. The limit shows the best N matching discoveries so far: reaching it does not stop exploration. Search continues until the time budget, Stop, a hidden-page pause or exhaustion. Pausing retains work; broader settings can restart exploration while retaining proved results.

## Calling-position filters

- **Allowed calling positions** rejects disallowed calls during exploration, not just in the displayed results. The optional **Tenors together** switch intersects this filter with call-specific permissions derived from the plain lead's directed working-place cycle. At every lead head it preserves the two heaviest working bells' directed separation as at rounds: continuing plain until the heavier bell is home must bring the other bell home too. They need not be neighbours in the cycle. This is not an adjacency check on intermediate rows, nor only a check at Home positions actually reached in the touch. Multipart touches and display rotations are rechecked. The switch is off by default, hidden without defined calling positions, and disabled with an explanation unless the working places form one cycle and calls end at the lead head. Its note lists the method's permissions independently of other selected settings.
- Calling-position names for singles refer to the corresponding bob at the same decision, not simply the single's destination. With standard 14 bobs and 1234 singles (for example, Bristol or Cambridge Major), single Before finishes in 2nds while bob Before finishes in 3rds; single In finishes in 3rds while bob In finishes in 2nds. The optional single-position name Thirds (T) is not used: the label remains In (I). As the [CCCBR explanation](https://callingitround.cccbr.org.uk/docs/calling/3_demystifying_in_out_make/) notes, names vary by method and stage; no method-specific Out/Make override is applied.

## Music analysis and filters

- **Music** filters depend on the rotation, because rotating a touch relabels its bells. Touches with no passing rotation are omitted. If none of the passing rotations supports a **By Course** table, the preferred calling is the highest-scoring rotation (after preferring a normal lead start). Otherwise the existing tenor-calling simplicity criteria apply, with higher music breaking simplicity ties. Lexical order only breaks remaining ties with equal music. Higher-scoring alternatives to a simpler course calling remain available in the details.
- **No 65s at back** (even stages only, on by default; labelled for the stage, e.g. 87s on Major, 09s on Royal) rejects backstrokes ending in the two heaviest bells reversed.
- **Required named rows** (a Choices.js multi-select of [Music.js](../assets/js/helpers/Music.js)'s rows for the stage) must all appear at least once. Named rows may wrap.
- **Assume tenor behind** (odd stages only, on by default) appends a fixed covering tenor when analysing all music.
- **Min. music points / 100 changes**, **Min. runs (including wraps)** and **Min. CRUs** default to zero but can be increased to filter for music.
- **Awaiting refreshed music** counts cached touches within the selected lengths whose rechecks are queued or in progress. All workers prioritise these rechecks before discovering new touches or preparing singles search tables; Stop and the time budget still apply. Completed checks, including rejected touches, are not counted. Rejected cached callings remain hidden until a later settings change makes them eligible again.
- **Contains:** lists runs, thirds, interleaves, CRUs where applicable, and named rows. Interleaves are Tittums-like sequences of consecutive bells at alternate positions (such as 1x2x3), not necessarily the complete named row Tittums. Different categories can overlap.

## Interface and tests

- [Touches.js](../assets/js/ui/MethodView/Touches.js) handles the UI; [Presentation.js](../assets/js/touch_generator/Presentation.js) builds lead/course layouts, the written-out line, compact sequences and Siril exports.
- The details dialog shows additional details about the touch, including links to proving tools.
- Line tooltips list each scoring passage on a separate line, prefixed by its points to three decimal places (for example, `1.250: Run of 4 bells, at back, backstroke`).
- The dialog's **Line** tab writes out every row as text, built only when the tab is first opened, with the bell numbers centred in the panel. Music scored by the same analysis as the filters is shaded green (65s at back at backstroke in red), and hovering or tapping anywhere in a row explains all its highlighted music. With **Assume tenor behind**, the covering tenor is shown in grey and included in the music. A left-hand column gives each row's positive music points to three decimal places, crediting passages that wrap to their backstroke row. Each call is named in full, with its calling position if the method declares calling positions (for example, *Bob (Home)*), beside the row from which its first change is made; a bracket spans the rows of calls that replace several changes. Segment heads are ruled off with the method line's dashed rule-off.
- When starting or ending falls mid-segment, the table and Siril listing show the preceding/suceeding segment head in brackets instead of rounds (if needed calculating backwards using inverse plain changes). It is not counted in the touch; the start description gives the numbered change on which the touch starts in that segment.
- Source-file comments explain the algorithms, scheduling, worker loading and presentation rules in more detail. Regression tests live in [tests/JavaScript/TouchGenerator/](../tests/JavaScript/TouchGenerator/).

## Related documentation

- [Architecture and workflows](architecture-and-workflows.md)
- [Development and installation](development-deployment.md)
- [Design language](design-language.md)
