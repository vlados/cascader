---
target: cascader component
total_score: 21
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 3
timestamp: 2026-08-02T17-47-12Z
slug: src-resources-views-cascader-blade-php
---
# Critique: cascader component (src/resources/views/cascader.blade.php)

Method: dual-agent (A: design review · B: detector)

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | No loading/empty states; no AT announcement of selection |
| 2 | Match System / Real World | 3 | "Parent / Child" path notation reads naturally; desktop/mobile commit models diverge |
| 3 | User Control and Freedom | 2 | showModal() focus trap; no undo on desktop instant-commit; can't clear from mobile sheet |
| 4 | Consistency and Standards | 1 | Zero ARIA combobox semantics; modal primitive for non-modal popup; hardcoded teal vs Flux accent tokens |
| 5 | Error Prevention | 2 | Good: unselectable parents, disabled Confirm, isEmpty allows 0. Missing: required/invalid support |
| 6 | Recognition Rather Than Recall | 3 | State restoration on reopen is thoughtful; undermined by hover destroying selection highlight |
| 7 | Flexibility and Efficiency | 1 | No arrow keys, no type-ahead, Enter suppressed in search, no mobile search |
| 8 | Aesthetic and Minimalist Design | 3 | Restrained, clean; color-mix() icon chips; loses to palette break + unbounded panel width |
| 9 | Error Recovery | 1 | No user-facing error surface; empty options = silent blank panel |
| 10 | Help and Documentation | 3 | Strong README; no a11y/keyboard story; hover-to-expand untaught in UI |
| **Total** | | **21/40** | **Acceptable — significant improvements needed** |

## Design Specificity Verdict

The trigger is a studied reproduction of Flux's select (down to `border-b-zinc-300/80`) — correct instinct for a reusable form control. The panel is stock Tailwind with hardcoded teal in eleven places while Flux is an accent-token system; in any non-teal host app the control fights its design system, and selected (teal) vs focused (zinc) are two competing identities.

Deterministic scan: clean — 0 findings across 4 runs (file, directory, --no-config, text mode), verified genuine via a control fixture that correctly fired 8 findings. Caveat: as a .blade.php fragment the file routes to the regex engine; page-level analyzers are gated off. Browser overlay skipped: package has no host app; an uncompiled Blade template is not renderable.

## Priority Issues

- **[P0] Two-column cascade is mouse-only.** hoverParent binds only @mouseenter; selectParent() no-ops for parents with children; Enter is suppressed in search. Keyboard users' only path is the undocumented search box. Fix: @focus companion, roving tabindex (arrows/Enter/Home/End), Enter selects first search result.
- **[P0] Zero ARIA semantics; attribute bag lands on wrapper div.** No role/aria-expanded/aria-selected anywhere; icon-only clear buttons unnamed; id/aria-*/disabled/required attach to a div and never reach the control. Fix: forward form/aria attrs to trigger; combobox/listbox/option roles; aria-live selection announcement.
- **[P1] Out-of-tree values fail silently three ways.** labelForValue() → null → trigger shows placeholder + visible clear button while Livewire holds a real value. Depth cap of 2 uncommunicated. Fix: explicit unresolved-value state; enforce or lift depth limit loudly.
- **[P1] No disabled/invalid/empty states.** disabled silently no-ops; validation styling impossible (border on inner button); options=[] renders blank void ("No results" only shows while searching). Fix: disabled/invalid props mirroring Flux select; "No options available" state.
- **[P1] No viewport collision handling.** Fixed top/left from trigger rect, min-w-max columns, no flip-up, no clamping; scroll re-applies position sliding panel off-screen. Fix: clamp, flip, max-width + truncation.

## Persona Red Flags

- **Sam (a11y)**: label-for can't name the control (id on div); popup existence never announced; selection state is color+unlabelled SVG only; placeholder contrast ~2.6:1 light mode (needs 4.5:1); no live announcements.
- **Jordan (first-timer)**: clicks "Electronics" (biggest target) → literally nothing happens; hover interaction never taught; right column silently swaps on stray mouse movement.
- **Casey (mobile)**: no mobile search (worst-scrolling platform denied the fastest tool); sheet has no title; Cancel/Confirm 36px (<44px); can't unset a value from inside the sheet.

## Cognitive Load

6/8 checklist failures. Sharpest: hoveredParent never resets on mouse-leave, so after one stray hover the panel shows no indication of the current selection until closed and reopened.

## Minor Observations

- h-safe-area-inset-bottom is not a Tailwind utility and nothing defines it — iOS safe-area spacer is a no-op.
- Mobile sheet leave transition can never play (dialog .close() is synchronous with open=false).
- Mobile breadcrumb "active" teal state is unreachable dead code (x-if guard contradicts the condition).
- Desktop/mobile @close handlers asymmetric (mobile leaves temp state stale; openCascader masks it).
- showModal focus trap prevents Tab from leaving the popup to the next field (combobox pattern wants the opposite).
- RTL half-done: trigger uses logical props, everything inside uses physical left/right.
- x-cloak used but README never mentions the required CSS rule.
- :focus instead of :focus-visible leaves a ring after mouse clicks.
- Options read once at init — server-side option changes don't propagate without wire:key.

## Questions to Consider

1. If a keyboard user's only working path is the search box, why is Enter the one key explicitly disabled — should search be the primary interaction?
2. Reproduced Flux's border to the eighth-opacity, then hardcoded teal — what happens in an app whose Flux accent is indigo?
3. Desktop commits on one click; mobile demands Confirm. Which one is wrong?

## Verdict

No redesign. The interaction architecture (Ant cascader pattern, native dialog top-layer, state restoration, leaf-only search) and the Flux-matched visual language are right, and the detector confirms no generic-pattern debt. What drags the score to 21/40 is interaction completeness — keyboard, ARIA, states, collision handling — plus the teal/accent-token conflict. A redesign would discard the good architecture and solve none of these. Refine hard instead.
