# Dot.Pulse Responsive Design Guide
Version: 1.0 | Mobile-First | Classification: Internal

---

## Current State

The layout has a **partial mobile implementation**:
- ✅ Sidebar hides off-screen on screens < 900px
- ✅ Hamburger menu shows (topbar)
- ✅ Alpine.js controls sidebar open/close
- ✅ Content takes full width on mobile
- ❌ No touch-optimised tap targets
- ❌ No tested breakpoints for tablets
- ❌ Many views use fixed widths that overflow on small screens
- ❌ The messaging view (full-viewport two-panel) breaks on mobile
- ❌ No tested layouts below 375px

---

## Breakpoints

```
xs:   < 480px   — Small phones (iPhone SE)
sm:   480–767px — Standard phones
md:   768–899px — Large phones / small tablets
lg:   900–1199px — Tablets / small laptops (sidebar activates at 900px)
xl:   1200px+   — Desktop (current default design)
```

### Sidebar breakpoint: 900px

Below 900px: sidebar hidden, topbar spans full width, hamburger visible.
Above 900px: sidebar fixed 260px, content indented.

---

## Layout Rules by Breakpoint

### Topbar

| Size | Behaviour |
|---|---|
| `xl` (≥ 900px) | Full topbar with all controls, left: 260px |
| `md/sm/xs` (< 900px) | Full-width, hamburger visible, team badge hidden |

### Sidebar

| Size | Behaviour |
|---|---|
| `xl` | Fixed, always visible |
| `md/sm/xs` | Hidden off-screen left, slides in via Alpine `sidebarOpen` |

### Content padding

| Size | Padding |
|---|---|
| `xl` | `padding: 2rem 2.5rem` |
| `lg` | `padding: 1.5rem 2rem` |
| `md` | `padding: 1.25rem 1.5rem` |
| `sm/xs` | `padding: 1rem` |

Implement via CSS:

```css
.page-content {
  padding: 2rem 2.5rem;
}

@media (max-width: 1200px) {
  .page-content { padding: 1.5rem 2rem; }
}
@media (max-width: 899px) {
  .page-content { padding: 1.25rem 1.5rem; }
}
@media (max-width: 600px) {
  .page-content { padding: 1rem; }
}
```

---

## Grid Adaptations

### Post Feed

```
xl:  Single column (max-width: 900px)
md:  Single column
sm:  Single column
```
Feed cards are already single-column — no adaptation needed.

### Communities Grid

```
xl:  auto-fill, minmax(200px, 1fr)  → 3–4 columns
md:  auto-fill, minmax(180px, 1fr)  → 2 columns
sm:  1 column (stacked)
```

```css
@media (max-width: 480px) {
  .communities-grid {
    grid-template-columns: 1fr;
  }
}
```

### Dashboard KPI Strip

```
xl:  3 columns (current)
sm:  3 columns (small cards)
xs:  1 column (stacked)
```

### Marketplace Grid

```
xl:  auto-fill minmax(260px, 1fr) → 3–4 columns
md:  2 columns
sm:  1 column
```

### Community Detail (2-column layout)

```
xl:  1fr 280px (posts + sidebar)
md:  1fr (sidebar moves below posts)
```

```css
@media (max-width: 899px) {
  .community-layout {
    grid-template-columns: 1fr;
  }
  .community-sidebar {
    order: 2;  /* Sidebar moves below posts on mobile */
  }
}
```

---

## Messaging View (Critical Fix Needed)

The current messaging layout uses a fixed two-panel grid:

```css
display: grid;
grid-template-columns: 280px 1fr;
height: calc(100vh - 54px);
```

This completely breaks on mobile. Target behaviour:

```
Mobile (< 768px):
- Show ONLY the conversation list by default
- Tapping a conversation shows ONLY the chat view
- Back button returns to conversation list
- No split-panel

Desktop (≥ 768px):
- Current two-panel layout
```

```php
// Livewire state to add to Messaging.php
public bool $mobileShowChat = false;

// When opening a conversation on mobile
public function openConversation(int $id): void
{
    $this->conversationId = $id;
    $this->mobileShowChat = true;
    unset($this->activeConversation);
}

public function backToList(): void
{
    $this->mobileShowChat = false;
}
```

```blade
<div style="display:grid; grid-template-columns:280px 1fr;"
     class="messaging-layout"
     x-data="{ mobile: window.innerWidth < 768 }">
  <!-- Left panel: hidden on mobile when chat is open -->
  <div x-show="!mobile || !$wire.mobileShowChat">
    <!-- conversation list -->
  </div>
  <!-- Right panel: hidden on mobile when list is showing -->
  <div x-show="!mobile || $wire.mobileShowChat">
    @if($this->activeConversation && $mobileShowChat)
      <button wire:click="backToList">← Back</button>
    @endif
    <!-- chat view -->
  </div>
</div>
```

---

## Touch Targets

WCAG 2.5.8 (Level AA) requires touch targets of at least **24×24px**, recommended **44×44px**.

Current button sizes:

| Component | Current size | Compliant? |
|---|---|---|
| `.topbar-btn` | 30×30px | ⚠️ borderline |
| `.dot-btn` default | ~30px height | ⚠️ borderline |
| Reaction button | inline, ~20px | ❌ too small |
| Nav items | 38px height | ✅ |
| "Reply" text button | text link, ~16px | ❌ too small |

**Fix:** On mobile, increase minimum touch target to 44px using padding:

```css
@media (max-width: 899px) {
  .topbar-btn {
    width: 44px;
    height: 44px;
  }

  .dot-btn {
    min-height: 44px;
    padding: 10px 16px;
  }

  /* Increase reaction button hit area */
  .reaction-btn {
    padding: 8px 12px;
    margin: -8px -12px;  /* negative margin maintains visual layout */
  }
}
```

---

## Typography on Mobile

| Element | Desktop | Mobile |
|---|---|---|
| Page h1 | 1.4rem | 1.2rem |
| Body text | 14px | 14px (no change) |
| Section labels | 10px | 10px (no change) |
| Post card text | 13px | 13px (no change) |
| Metric values | 2rem | 1.5rem |

---

## Mobile Navigation Patterns

### Bottom tab bar (Phase 2)

For mobile, a fixed bottom navigation bar is more thumb-friendly than a sidebar:

```
[Feed] [Communities] [+Post] [Messages] [Profile]
```

Implement as a fixed bottom bar visible only below 600px. This is a significant UX improvement for mobile users.

---

## Responsive Testing Checklist

Test every new view at these viewport sizes before merge:

- [ ] 375×812px (iPhone SE / standard phone)
- [ ] 390×844px (iPhone 14)
- [ ] 768×1024px (iPad portrait)
- [ ] 1024×768px (iPad landscape)
- [ ] 1280×800px (standard laptop)
- [ ] 1440×900px (large desktop)

### Tools

```bash
# Chrome DevTools responsive mode
# Set to "Responsive" and test each breakpoint above

# Playwright viewport testing
await page.setViewportSize({ width: 375, height: 812 });
```

---

## Known Mobile Issues (Priority Queue)

| Issue | Severity | Fix |
|---|---|---|
| Messaging two-panel breaks | Critical | Implement mobile-first single-panel |
| Touch targets too small (reactions, reply buttons) | High | Add mobile padding |
| Post detail actions row overflows | Medium | Flex-wrap at < 400px |
| Community banner image stretches | Low | Set `height` cap on mobile |
| Dashboard KPI 3-column too narrow | Low | Stack to 1-col below 400px |
| Long community names truncated too aggressively | Low | Adjust truncation length |
