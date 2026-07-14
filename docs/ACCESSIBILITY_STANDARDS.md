# Dot.Pulse Accessibility Standards
Version: 1.0 | WCAG 2.2 Level AA | Classification: Internal

---

## Commitment

Dot.Pulse targets **WCAG 2.2 Level AA** compliance. This is both an ethical requirement and an enterprise sales requirement — many government, healthcare, and education customers legally require AA compliance from their vendors.

This document provides implementation-specific guidance for the Dot.Pulse dark theme. WCAG standards are not repeated here — only platform-specific interpretations.

---

## Colour Contrast

WCAG 2.2 AA requires:
- **4.5:1** for normal text (< 18pt / 24px)
- **3:1** for large text (≥ 18pt / 24px) and UI components
- **3:1** for graphical elements (icons, charts, indicators)

### Current Palette — Contrast Ratios

All ratios against the page background `#09090b`:

| Text colour | Hex | Ratio | Pass AA? |
|---|---|---|---|
| `--text-primary` | `#f4f4f5` | 19.4:1 | ✅ |
| `--text-secondary` | `#a1a1aa` | 7.6:1 | ✅ |
| `--text-muted` | `#71717a` | 4.6:1 | ✅ (borderline — do not use for small body text) |
| `--text-faint` | `#52525b` | 2.9:1 | ❌ Use only for decorative / large text |
| `--text-ghost` | `#3f3f46` | 2.1:1 | ❌ Placeholders only — never for readable text |
| `--accent` | `#c084fc` | 5.8:1 | ✅ |

**Rule:** Use `#71717a` (`--text-muted`) only for timestamps, counts, and non-critical metadata in font sizes ≥ 13px. Never for any text the user must read to take an action.

### Status Colour Accessibility

Status colours used in badges need sufficient contrast on their tinted backgrounds:

| Colour | Text | Background (10% opacity) | Text/BG ratio | Pass? |
|---|---|---|---|---|
| Success | `#4ade80` | `#4ade8018` | 4.6:1 on card bg | ✅ |
| Warning | `#fbbf24` | `#fbbf2418` | 5.2:1 on card bg | ✅ |
| Danger | `#f87171` | `#f8717118` | 4.5:1 on card bg | ✅ (borderline) |
| Accent | `#c084fc` | `#c084fc18` | 5.8:1 on card bg | ✅ |

---

## Focus Management

### Focus Indicators

Every interactive element must have a **visible focus ring**. The current `.dot-input` has a focus ring — all buttons and links must match.

```css
/* Apply to all interactive elements */
:focus-visible {
  outline: 2px solid #c084fc;
  outline-offset: 2px;
  border-radius: 6px;
}

/* Remove default outline only if replacing it */
:focus:not(:focus-visible) {
  outline: none;
}
```

**Never use `outline: none` or `outline: 0` without providing a replacement.**

### Focus Order

The tab order must be logical and follow the visual reading order:
1. Skip-to-content link (first focusable element on every page)
2. Topbar controls
3. Sidebar navigation
4. Main content area

### Skip Links

Add a skip-to-content link as the first element in `layouts/app.blade.php`:

```html
<a href="#main-content"
   style="position:absolute;top:-9999px;left:-9999px;z-index:9999;
          background:#c084fc;color:#09090b;padding:8px 16px;font-weight:700;
          border-radius:0 0 8px 0;"
   onfocus="this.style.top='0';this.style.left='0';"
   onblur="this.style.top='-9999px';this.style.left='-9999px';">
  Skip to main content
</a>

<!-- ... layout ... -->

<main id="main-content">{{ $slot }}</main>
```

---

## Keyboard Navigation

Every user action that is possible with a mouse must be possible with a keyboard.

| Action | Keyboard method |
|---|---|
| Navigate feed | Tab to posts, Enter to open |
| React to post | Tab to reaction button, Enter/Space |
| Open notification bell | Tab to bell, Enter |
| Close dropdowns | Escape |
| Submit forms | Enter on submit button, or Enter in single-line inputs |
| Navigate tabs | Tab between tabs, Enter/Space to select |
| Join/leave community | Tab to button, Enter |
| Dismiss modals/overlays | Escape |

**Modals and overlays must trap focus** — Tab should cycle within the modal, not leave it.

---

## ARIA Implementation

### Required ARIA on Existing Components

#### Navigation sidebar

```html
<nav aria-label="Main navigation">
  <div role="group" aria-labelledby="nav-section-discover">
    <span id="nav-section-discover" class="nav-section-label">Discover</span>
    <a href="..." aria-current="page">Feed</a>
    <!-- ... -->
  </div>
</nav>
```

#### Notification bell dropdown

```html
<button aria-expanded="false" aria-haspopup="listbox"
        aria-label="Notifications — 3 unread">
  <span class="material-symbols-rounded" aria-hidden="true">notifications</span>
</button>
```

#### Post type badges

```html
<span role="img" aria-label="Post type: Question">
  <span style="...">Question</span>
</span>
```

#### Feed loading

```html
<div aria-live="polite" aria-atomic="false">
  <!-- Feed items inserted here by Livewire -->
</div>
```

#### Form error messages

```html
<input wire:model="body"
       aria-describedby="body-error"
       aria-invalid="true">
<p id="body-error" role="alert" style="color:#f87171;">
  {{ $message }}
</p>
```

#### Reaction buttons

```html
<button wire:click="react({{ $post->id }})"
        aria-label="React to post with thumbs up. Current count: {{ $post->reactions_count }}"
        aria-pressed="{{ $hasReacted ? 'true' : 'false' }}">
  <span class="material-symbols-rounded" aria-hidden="true">thumb_up</span>
  <span>{{ $post->reactions_count }}</span>
</button>
```

---

## Semantic HTML

### Use the right element for the job

```html
<!-- ✅ Correct -->
<button wire:click="...">Join</button>      <!-- Interactive action -->
<a href="...">View post</a>                 <!-- Navigation -->
<nav>...</nav>                              <!-- Navigation landmark -->
<main>...</main>                            <!-- Main content landmark -->
<article>...</article>                      <!-- Self-contained content unit (post) -->
<aside>...</aside>                          <!-- Secondary content (trending, sidebar widget) -->
<time datetime="2026-07-14T10:30:00Z">2 hours ago</time>

<!-- ❌ Wrong -->
<div onclick="...">Join</div>               <!-- Not focusable, no keyboard access -->
<span class="link">View post</span>         <!-- Not a real link -->
```

### Heading Hierarchy

Every page must have exactly one `<h1>`. Headings must not skip levels.

```
h1 — Page title (e.g., "Community Feed")
  h2 — Section heading (e.g., "Trending Posts")
    h3 — Card/item heading (e.g., "How do I integrate fleet tracking?")
```

The current inline-style headings are semantically correct where they use `<h1>`, `<h2>`, `<h3>` — confirm this is consistent in new views.

---

## Screen Reader Testing

Test with at least one of these tools before marking a feature as complete:

| Tool | Platform | How to use |
|---|---|---|
| NVDA + Firefox | Windows | Free, most common screen reader |
| VoiceOver + Safari | macOS/iOS | Built into macOS — Command+F5 |
| TalkBack | Android | Settings → Accessibility |
| axe DevTools | Browser extension | Automated a11y audit |

### Minimum screen reader test checklist

- [ ] Can navigate to the main content without clicking
- [ ] Post list items read: author name, post type, title/summary, time ago
- [ ] Buttons are announced with their label and state
- [ ] Form errors are announced when they appear
- [ ] Notifications are announced when new ones arrive
- [ ] Modal dialogs trap focus and announce their title

---

## Motion & Animation

Some users are sensitive to motion. Respect the `prefers-reduced-motion` media query:

```css
@media (prefers-reduced-motion: reduce) {
  /* Disable live-pulse animation */
  .live-dot { animation: none; }

  /* Disable sidebar slide transition */
  .sidebar { transition: none; }

  /* Disable all transitions */
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## Text Sizing

All text sizes use `px` in the current implementation. For accessibility, consider migrating to `rem` so users who increase their browser font size see text scale proportionally.

Minimum font sizes:
- Body text: 14px (0.875rem) — already met
- Button labels: 13px — borderline; consider 14px
- Captions/metadata: 11–12px — acceptable for non-critical text only

---

## Automated Accessibility Testing

Add axe-core to the test suite:

```bash
npm install --save-dev @axe-core/playwright
```

```javascript
// tests/accessibility/feed.spec.js
import { checkA11y } from 'axe-playwright';

test('feed page has no accessibility violations', async ({ page }) => {
  await page.goto('/dashboard');
  await checkA11y(page, '#main-content', {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag22aa'] },
  });
});
```

CI gate: zero violations at `wcag2aa` level before merge to main.

---

## Accessibility Checklist (per feature)

Run before every PR merge:

- [ ] All interactive elements keyboard accessible
- [ ] All interactive elements have visible focus indicator
- [ ] Colour contrast ≥ 4.5:1 for all text
- [ ] All images have alt text
- [ ] All icons are aria-hidden when decorative
- [ ] Form fields have associated labels
- [ ] Error messages linked to their field via aria-describedby
- [ ] Dynamic content uses aria-live
- [ ] Heading hierarchy is logical
- [ ] axe-core audit passes at AA level
- [ ] Tested with keyboard only
