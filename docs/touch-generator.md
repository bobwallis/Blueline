# Touch generation on method pages

This guide describes touch search and its controls for contributors working on the method page. See the [development guide](development-deployment.md) for setup and JavaScript tests.

## Search model

- The **Touches** tab searches locally in browser web workers, showing distinct true touches ordered by length.
- [Compile.js](../assets/js/touch_generator/Compile.js) prepares the method and calls; [Search.js](../assets/js/touch_generator/Search.js) explores call sequences, checks every row for truth, and removes rotation-equivalent results.
- The work is divided into separate jobs by:
  - the first two calls, for example "plain, plain", "plain, bob", "bob, plain" and "bob, bob" (more when singles are allowed);
  - the length limit (see below);
  - whether the touch comes round at a lead end or mid-lead.

  Together these give up to c20 jobs. Jobs never overlap, so several web workers (capped at half the user's number of cores, or 6) can search different jobs at the same time.
- Each worker searches its job for a short period of time, then stops and sends back any touches it found plus a snapshot of where it stopped. The snapshot goes back in the queue, and any free worker can later pick it up and carry on. Taking turns like this stops one long job from holding up the rest, and lets pausing or changing settings take effect quickly.
- A job's progress is never split further. Late in a search, when only a few long jobs remain, some workers may sit idle.
- The length limits are about one, two and four courses, plus the full selected length. The pool alternates between them, which helps find multipart touches early without neglecting longer touches.
- Touches start at the method's first change and end at the first return to rounds. Multipart discovery repeats shorter call patterns within the selected length limits.
- [TouchSearchPool.js](../assets/js/ui/MethodView/TouchSearchPool.js) manages workers, task scheduling and shared results.
- Pruning tables skip impossible branches without replacing exact truth checks.
- Settings control lengths, minimum parts, calling positions, singles, music, result limits and search time. Pausing retains work; broader settings can restart exploration while retaining proved results.

## Calling-position filters

- **Allowed calling positions** rejects disallowed calls during exploration, not just in the displayed results. The optional **Tenors together** switch intersects this filter with call-specific permissions derived from the plain lead's directed working-place cycle. At every lead head it preserves the two heaviest working bells' directed separation as at rounds: continuing plain until the heavier bell is home must bring the other bell home too. They need not be neighbours in the cycle. This is not an adjacency check on intermediate rows, nor only a check at Home positions actually reached in the touch. Multipart touches and display rotations are rechecked. The switch is off by default, hidden without defined calling positions, and disabled with an explanation unless the working places form one cycle and calls end at the lead head. Its note lists the method's permissions independently of other selected settings.
- Calling-position names for singles refer to the corresponding bob at the same decision, not simply the single's destination. With standard 14 bobs and 1234 singles (for example, Bristol or Cambridge Major), single Before finishes in 2nds while bob Before finishes in 3rds; single In finishes in 3rds while bob In finishes in 2nds. The optional single-position name Thirds (T) is not used: the label remains In (I). As the [CCCBR explanation](https://callingitround.cccbr.org.uk/docs/calling/3_demystifying_in_out_make/) notes, names vary by method and stage; no method-specific Out/Make override is applied.

## Music filters

- **Music** filters depend on the rotation, because rotating a touch relabels its bells. Each touch is shown in a rotation that passes the filters, and touches with no passing rotation are dropped. Music is read in handstroke/backstroke pairs, so named rows can be found wrapped across the pair; for Rounds, only wrapped matches count. **No 65s at back** (even stages only, on by default; labelled for the stage, e.g. 87s on Major, 09s on Royal) rejects backstrokes ending in the two heaviest bells reversed. It prunes during exploration. For that to be safe, the duplicate-rotation pruning compares only rotations that start at a lead head with both tenors home, which leave every row's 65s status unchanged (or none, for odd lead lengths). **Required named rows** (a Choices.js multi-select of Music.js's rows for the stage) must all appear, and are checked only when a touch is recorded. On odd stages, **Assume tenor behind** (on by default) reads every row with a covering tenor appended and offers the next stage's named rows instead (Rounds is omitted, since it could only be the closing row); for example Queens on Triples is 1357246(8), and Back Rounds can wrap as 8|7654321. Cards and the details dialog list the named rows found and any backstroke 65s.

## Interface and tests

- [Touches.js](../assets/js/ui/MethodView/Touches.js) handles the UI; [Presentation.js](../assets/js/touch_generator/Presentation.js) builds lead/course layouts, compact sequences and Siril exports.
- The details dialog shows additional details about the touch, including links to proving tools.
- When starting or ending falls mid-segment, the table and Siril listing show the preceding/suceeding segment head in brackets instead of rounds (if needed calculating backwards using inverse plain changes). It is not counted in the touch; the start description gives the numbered change on which the touch starts in that segment.
- Source-file comments explain the algorithms, scheduling, worker loading and presentation rules in more detail. Regression tests live in [tests/JavaScript/TouchGenerator/](../tests/JavaScript/TouchGenerator/).

## Related documentation

- [Architecture and workflows](architecture-and-workflows.md)
- [Development and installation](development-deployment.md)
- [Design language](design-language.md)
