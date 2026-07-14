# Dot.Pulse UI Design System
Version: 1.0 | Dark Theme | Classification: Internal

> This document is the single source of truth for every visual decision in Dot.Pulse.
> Every new component, page, and view must reference these tokens. Never invent new values.

---

## Design Philosophy

Dot.Pulse uses a **deep dark, purple-accented** design language that communicates:

- **Professionalism** — enterprise-grade, not playful
- **Intelligence** — dense data-rich layouts, not padded marketing pages
- **Trust** — consistent, predictable, restrained
- **Energy** — the accent colour and live animations signal activity without noise

The palette is deliberately narrow. The entire UI is built from approximately 8 colours, 3 fonts, and 12 component classes. Restraint is the style.

---

## Colour Tokens

### Core Palette (CSS Custom Properties)

```css
:root {
  /* Accent */
  --accent:     #c084fc;   /* Purple — primary interactive, focus, active states */
  --accent-rgb: 192,132,252;

  /* Backgrounds — from darkest to lightest */
  --bg-base:    #09090b;   /* Page background, deepest layer */
  --bg-sidebar: #0d0d10;   /* Sidebar background */
  --bg-card:    #141416;   /* Cards, panels, elevated surfaces */
  --bg-overlay: #1a1a1f;   /* Dropdowns, modals, tooltips */

  /* Text — from most prominent to least */
  --text-primary:   #f4f4f5;   /* Headings, body text, primary labels */
  --text-secondary: #a1a1aa;   /* Supporting text, descriptions */
  --text-muted:     #71717a;   /* Timestamps, counts, secondary labels */
  --text-faint:     #52525b;   /* Captions, metadata, helper text */
  --text-ghost:     #3f3f46;   /* Placeholders, disabled, separators */

  /* Borders */
  --border-subtle:  rgba(255,255,255,0.06);  /* Dividers, section separators */
  --border-base:    rgba(255,255,255,0.07);  /* Card borders, default inputs */
  --border-hover:   rgba(255,255,255,0.11);  /* Card hover state */
  --border-strong:  rgba(255,255,255,0.15);  /* Active, focused, selected */

  /* Status colours */
  --color-success:  #4ade80;   /* Positive sentiment, solutions, verified */
  --color-warning:  #fbbf24;   /* Polls, pins, borderline moderation */
  --color-danger:   #f87171;   /* Errors, spam, flagged content, rejection */
  --color-info:     #60a5fa;   /* Discussions, info badges */
  --color-cyan:     #22d3ee;   /* Questions */
  --color-teal:     #2dd4bf;   /* Integrations */
  --color-orange:   #fb923c;   /* Announcements, events */
  --color-pink:     #f472b6;   /* Showcases */
  --color-violet:   #a78bfa;   /* Releases */
  --color-indigo:   #818cf8;   /* Tutorials */
  --color-sky:      #38bdf8;   /* Jobs */
}
```

### Accent Usage Rules

| Context | Value | Usage |
|---|---|---|
| Interactive element (button, link) | `#c084fc` | Primary call-to-action |
| Active / selected state | `rgba(192,132,252,0.10)` | Nav active, selected tab bg |
| Active state border | `rgba(192,132,252,0.25)` | Focused input, selected card border |
| Focus ring | `rgba(192,132,252,0.07)` with `rgba(192,132,252,0.45)` border | Focus shadow on inputs |
| Subtle tint | `rgba(192,132,252,0.06–0.12)` | Accent-tinted backgrounds |
| Icon / text highlight | `#c084fc` | Accent icons, active nav items |

### Post Type Colours

Each post type has a fixed colour. Use these consistently in badges, indicators, and type labels:

```
discussion    #60a5fa   info blue
announcement  #fb923c   orange
question      #22d3ee   cyan
idea          #34d399   emerald
bug_report    #f87171   red
release       #a78bfa   violet
success_story #4ade80   green
showcase      #f472b6   pink
tutorial      #818cf8   indigo
agent         #c084fc   accent purple
integration   #2dd4bf   teal
event         #fb923c   orange
article       #94a3b8   slate
poll          #fbbf24   amber
video         #f87171   red
job           #38bdf8   sky
marketplace   #4ade80   green
```

**Badge pattern:** Background is `{color}18` (10% opacity hex), text is the full colour.

```html
<!-- Example: Question badge -->
<span style="background:#22d3ee18; color:#22d3ee; font-size:11px; font-weight:600;
             padding:2px 9px; border-radius:100px;">Question</span>
```

---

## Typography

### Font Stack

```css
/* Headings and brand */
font-family: 'Syne', sans-serif;
/* weights: 600, 700, 800 */

/* Body, labels, UI text */
font-family: 'Inter', system-ui, sans-serif;
/* weights: 400, 500, 600 */

/* Numbers, metrics, code */
font-family: 'JetBrains Mono', monospace;
/* weights: 400, 500 */
```

### Type Scale

| Role | Font | Size | Weight | Colour |
|---|---|---|---|---|
| Page title (h1) | Syne | 1.4–2rem | 700 | `--text-primary` |
| Section heading (h2) | Syne | 1.2rem | 700 | `--text-primary` |
| Card title (h3) | Syne | 0.875rem | 700 | `--text-primary` |
| Body text | Inter | 14px | 400 | `--text-secondary` |
| UI labels | Inter | 13px | 500–600 | `--text-primary` |
| Captions / metadata | Inter | 11–12px | 500 | `--text-faint` |
| Section labels | Inter | 10px, uppercase | 600, `letter-spacing:0.08em` | `--text-ghost` |
| Metric numbers | JetBrains Mono | 1.1–2rem | 500, `letter-spacing:-0.02em` | `--accent` or `--text-primary` |
| Code / mono | JetBrains Mono | 12–13px | 400 | `--text-secondary` |

### Heading Rendering

All `h1` headings on page views use this pattern:

```html
<h1 style="font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:700;
           color:#f4f4f5; margin:0 0 0.2rem; letter-spacing:-0.01em;">
  Page Title
</h1>
<p style="font-size:0.78rem; color:#52525b; margin:0;">
  Subtitle or description
</p>
```

---

## Spacing System

All spacing uses `rem` with a base of `16px`. Prefer multiples of `0.25rem`.

| Token | Value | Use case |
|---|---|---|
| `space-1` | `4px / 0.25rem` | Tight gaps between related elements |
| `space-2` | `8px / 0.5rem` | Icon + label gaps, inline spacing |
| `space-3` | `12px / 0.75rem` | Form field spacing, small card padding |
| `space-4` | `16px / 1rem` | Standard padding, list item gaps |
| `space-5` | `20px / 1.25rem` | Card padding, section spacing |
| `space-6` | `24px / 1.5rem` | Large card padding, between sections |
| `space-8` | `32px / 2rem` | Page padding, between major sections |
| `space-10` | `40px / 2.5rem` | Hero sections, large gaps |

Page content padding: `2rem 2.5rem` (top/bottom × left/right).

---

## Border Radius

| Token | Value | Use case |
|---|---|---|
| `radius-sm` | `6px` | Small badges, pills, dropdowns |
| `radius-md` | `8px` | Buttons, inputs, small cards |
| `radius-lg` | `12px` | Cards (`.dot-card`) |
| `radius-xl` | `14px` | Feature cards, larger panels |
| `radius-2xl` | `20px` | CTAs, modal corners |
| `radius-full` | `100px` | Pills, avatars, type badges |

---

## Component Classes

### `.dot-card`

The primary surface container. Used for every content block.

```css
.dot-card {
  background: #141416;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 12px;
}
.dot-card:hover {
  border-color: rgba(255,255,255,0.11);
}
```

**Variants:**
- Default — no modifier
- Accent tint — add `border-color: rgba(192,132,252,0.15); background: rgba(192,132,252,0.04)`
- Danger tint — add `border-color: rgba(248,113,113,0.2); background: rgba(248,113,113,0.04)`
- Success tint — add `border-color: rgba(74,222,128,0.2); background: rgba(74,222,128,0.04)`

---

### `.dot-btn` Button System

```css
.dot-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.14s;
  border: none;
  text-decoration: none;
  font-family: 'Inter', sans-serif;
}
```

| Variant | Class | Background | Text | Border |
|---|---|---|---|---|
| Primary | `.dot-btn-primary` | `#c084fc` | `#09090b` | none |
| Ghost | `.dot-btn-ghost` | `rgba(255,255,255,0.06)` | `#a1a1aa` | `rgba(255,255,255,0.08)` |
| Danger | (inline) | `rgba(248,113,113,0.10)` | `#f87171` | `rgba(248,113,113,0.20)` |
| Success | (inline) | `rgba(74,222,128,0.10)` | `#4ade80` | `rgba(74,222,128,0.20)` |

**Size modifiers:**

```html
<!-- Default -->
<button class="dot-btn dot-btn-primary">Save</button>

<!-- Small (12px font, 5px 12px padding) -->
<button class="dot-btn dot-btn-primary" style="font-size:12px;padding:5px 12px;">Save</button>

<!-- Large (14px font, 10px 20px padding) -->
<button class="dot-btn dot-btn-primary" style="font-size:14px;padding:10px 20px;">Save</button>
```

---

### `.dot-input` Input System

```css
.dot-input {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px;
  color: #f4f4f5;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  padding: 8px 12px;
  width: 100%;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
}
.dot-input:focus {
  border-color: rgba(192,132,252,0.45);
  box-shadow: 0 0 0 3px rgba(192,132,252,0.07);
}
.dot-input::placeholder {
  color: #3f3f46;
}
select.dot-input option {
  background: #1a1a1f;
}
```

---

### `.dot-badge` Badge System

```css
.dot-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 600;
}
.dot-badge-accent {
  background: rgba(192,132,252,0.12);
  color: #c084fc;
}
```

---

### `.metric-val` Metric Numbers

```css
.metric-val {
  font-family: 'JetBrains Mono', monospace;
  font-weight: 500;
  letter-spacing: -0.02em;
}
```

Use for: follower counts, point totals, KPI numbers, install counts, reaction counts.

---

## Layout Anatomy

```
┌─────────────────────────────────────────────────────────┐
│  TOPBAR (54px, fixed, blur backdrop)                    │
│  [☰] [Dot.Pulse]     [New Post] [🔍] [team] [🔔] [👤]  │
└─────────────┬───────────────────────────────────────────┘
│             │                                            │
│  SIDEBAR    │  CONTENT AREA                             │
│  260px      │  margin-left: 260px                       │
│  fixed      │  padding-top: 54px                        │
│             │                                            │
│  [Brand]    │  [Page padding: 2rem 2.5rem]              │
│             │                                            │
│  [Nav]      │  [Page header]                            │
│             │  h1 + subtitle                            │
│  Discover   │                                            │
│  · Feed     │  [Content]                                │
│  · Communities│  dot-card components                   │
│  · Events   │                                            │
│  · Marketplace│                                         │
│  · Search   │                                            │
│             │                                            │
│  You        │                                            │
│  · Profile  │                                            │
│  · Messages │                                            │
│  · Notifs   │                                            │
│  · Settings │                                            │
│             │                                            │
│  [User]     │                                            │
└─────────────┴───────────────────────────────────────────┘
```

---

## Icons

All icons use **Material Symbols Rounded** from Google Fonts.

```html
<!-- Default (size 24, weight 400, unfilled) -->
<span class="material-symbols-rounded">groups</span>

<!-- Custom size and weight -->
<span class="material-symbols-rounded"
  style="font-size:17px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;">
  notifications
</span>
```

**Icon size guidelines:**

| Context | Size |
|---|---|
| Sidebar nav icons | 17px |
| Topbar button icons | 17px |
| Inline with body text | 14–16px |
| Button icons | 14–15px |
| Card/section icons | 18–22px |
| Hero / empty state | 40–48px |

---

## Avatar Pattern

Avatars use the first letter of the user's name, displayed in the accent colour on an accent-tinted background.

```html
<!-- Small (28px — sidebar footer) -->
<div style="width:28px;height:28px;border-radius:50%;
            background:rgba(192,132,252,0.18);border:1px solid rgba(192,132,252,0.28);
            display:flex;align-items:center;justify-content:center;
            font-size:11px;font-weight:700;color:#c084fc;
            font-family:'Syne',sans-serif;">
  A
</div>

<!-- Medium (34–36px — feed posts) -->
<!-- Large (42px — post detail) -->
<!-- XL (64px — profile page) -->
```

When a real avatar URL exists, render an `<img>` inside the same container with `object-fit:cover`.

---

## Transitions & Animation

| Motion | Duration | Easing | Use case |
|---|---|---|---|
| Hover state | `0.13–0.15s` | linear | Background, colour changes |
| Focus ring | `0.15s` | ease | Input focus glow |
| Sidebar slide | `0.22s` | ease | Mobile sidebar open/close |
| Tab underline | `0.13s` | linear | Tab active state |
| Card hover lift | `0.15s` | ease | border-color only, no transform |

**Live pulse animation** (sidebar brand dot):

```css
@keyframes live-pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(192,132,252,0.45); }
  60%       { opacity: 0.6; box-shadow: 0 0 0 5px rgba(192,132,252,0); }
}
/* Duration: 2.8s, ease-in-out, infinite */
```

---

## Loading States

Use Livewire's `wire:loading` for inline loading indicators:

```html
<!-- Button loading state -->
<button wire:loading.attr="disabled" class="dot-btn dot-btn-primary">
  <span wire:loading.remove>Save</span>
  <span wire:loading>Saving…</span>
</button>

<!-- Skeleton pattern (for initial load) -->
<div style="height:80px;background:rgba(255,255,255,0.04);
            border-radius:12px;animation:skeleton-pulse 1.5s ease-in-out infinite;">
</div>

@keyframes skeleton-pulse {
  0%, 100% { opacity: 0.5; }
  50%       { opacity: 1; }
}
```

---

## Empty States

All empty states follow the same pattern:

```html
<div style="text-align:center;padding:4rem 1rem;color:#3f3f46;">
  <span class="material-symbols-rounded"
        style="font-size:40px;display:block;margin-bottom:0.5rem;color:#3f3f46;">
    forum
  </span>
  <p style="font-size:13px;margin:0;">
    No discussions yet.
  </p>
  <p style="font-size:12px;color:#3f3f46;margin:4px 0 0;">
    Be the first to start a conversation.
  </p>
  <!-- Optional CTA -->
  <button class="dot-btn dot-btn-primary" style="margin-top:1rem;font-size:12px;">
    Start a discussion
  </button>
</div>
```

Choose icons that match the content type: `forum` for posts, `groups` for communities, `event` for events, `storefront` for marketplace, etc.
