# Dot.Pulse Onboarding Flow
Version: 1.0 | First-Time User Experience | Classification: Internal

---

## Why Onboarding Matters

Community platforms live or die on **activation** — the moment a new user first experiences the platform's value. Without a guided onboarding flow, a new user:

1. Logs in and sees an empty or unfamiliar feed
2. Doesn't know what to do next
3. Leaves within 60 seconds
4. Never returns

The onboarding flow must answer: *"Where do I start, and why should I care?"*

**Target activation metric:** User joins at least one community and creates at least one post or comment within their first session.

---

## Current State (Gap)

Currently, after registration, users land directly on `/dashboard` with no onboarding:

```
Register → Email verification → Dashboard (empty feed, no context)
```

This document defines the target state.

---

## Target Onboarding Flow

```
Register / SSO Login
        ↓
[Step 1] Welcome + Profile Basics  (30 seconds)
        ↓
[Step 2] Pick Your Communities     (45 seconds)
        ↓
[Step 3] Follow Interesting People (30 seconds)
        ↓
[Step 4] Your Personalised Feed    (arrive!)
        ↓
[Nudge] First post prompt          (5 minutes later)
```

Total expected time: **under 2 minutes** to complete all steps.

---

## Step 1: Welcome + Profile Basics

**URL:** `/onboarding/profile`  
**Skippable:** Yes (skip link in top right)

### Screen Layout

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   👋  Welcome to Dot.Pulse                           │
│   Let's set up your community profile.               │
│                                                      │
│   [Avatar upload — large circle with camera icon]    │
│                                                      │
│   Headline                                           │
│   [e.g. Fleet Operations Manager at Acme Corp]       │
│                                                      │
│   What describes you best?                           │
│   ○ Customer      ○ Developer    ○ Business           │
│   ○ Partner       ○ Consultant   ○ Just exploring     │
│                                                      │
│   Your industry (optional)                           │
│   [Dropdown: Agriculture / AI / Fleet / etc.]        │
│                                                      │
│   ┌─────────────────┐   Skip for now →              │
│   │  Continue  →    │                                │
│   └─────────────────┘                                │
│                                                      │
│   ● ○ ○ ○  Step 1 of 3                               │
└──────────────────────────────────────────────────────┘
```

### Data collected

- `pulse_profiles.avatar_url` (optional upload)
- `pulse_profiles.headline`
- `pulse_profiles.role` (maps to: customer, developer, business, partner)
- `pulse_profiles.industry` (stored in expertise_tags or a new field)

### Implementation notes

- Upload via Livewire `WithFileUploads` to S3/public disk
- Headline input with live 120-char counter
- Role selector uses visual cards, not a dropdown
- Industry uses the same list as community industries
- "Skip for now" → sets default role to `customer`, goes to step 2

---

## Step 2: Pick Your Communities

**URL:** `/onboarding/communities`  
**Skippable:** Yes, but prompt user to join at least one

### Screen Layout

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   🏘️  Find your communities                          │
│   Join communities relevant to your work.            │
│                                                      │
│   [Search: Filter communities…]                      │
│                                                      │
│   ┌──────────────────┐ ┌──────────────────┐          │
│   │ Fleet Automation │ │ AI Builders      │          │
│   │ Fleet Management │ │ AI & ML          │          │
│   │ 1,240 members    │ │ 980 members      │          │
│   │ [  + Join  ]     │ │ [ ✓ Joined ]     │          │
│   └──────────────────┘ └──────────────────┘          │
│   ┌──────────────────┐ ┌──────────────────┐          │
│   │ Laravel & Livewire│ │ Mining Ops      │          │
│   │ Developers       │ │ Mining          │          │
│   │ 650 members      │ │ 420 members     │          │
│   │ [  + Join  ]     │ │ [  + Join  ]    │          │
│   └──────────────────┘ └──────────────────┘          │
│                                                      │
│   + Show all communities                             │
│                                                      │
│   Joined 2 communities                               │
│   ┌─────────────────┐   Skip for now →              │
│   │  Continue  →    │                                │
│   └─────────────────┘                                │
│                                                      │
│   ○ ● ○ ○  Step 2 of 3                               │
└──────────────────────────────────────────────────────┘
```

### Behaviour

- Pre-filter communities based on role/industry selected in Step 1
- Show top 8 communities initially, expandable to all
- Join/leave togglable inline
- "Continue" enabled once at least 1 community is joined
- If user skips, suggest 1 default community based on their role

### Implementation

```php
// New Livewire component: OnboardingCommunities
// Query communities relevant to user's industry first
// Allow search/filter within the onboarding step
// Call existing CreateCommunity join logic
```

---

## Step 3: Follow Interesting People

**URL:** `/onboarding/follow`  
**Skippable:** Yes (this step is entirely optional)

### Screen Layout

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   🤝  Follow community experts                       │
│   See their posts in your personalised feed.         │
│                                                      │
│   ┌──────────────────────────────────────────────┐   │
│   │ [A]  Pulse Admin          ✓ Verified Expert │   │
│   │      Platform Administrator                  │   │
│   │      9,999 pts · 0 solutions                 │   │
│   │      [  Follow  ]                            │   │
│   └──────────────────────────────────────────────┘   │
│   ┌──────────────────────────────────────────────┐   │
│   │ [J]  Jane Smith           Fleet Management  │   │
│   │      Fleet Ops Manager at Acme Corp          │   │
│   │      2,100 pts · 12 solutions                │   │
│   │      [  Follow  ]                            │   │
│   └──────────────────────────────────────────────┘   │
│                                                      │
│   ○ ○ ● ○  Step 3 of 3                               │
│                                                      │
│   ┌─────────────────┐   Skip this step →            │
│   │  Continue  →    │                                │
│   └─────────────────┘                                │
└──────────────────────────────────────────────────────┘
```

### Suggestion Algorithm

Show users with:
1. High community points
2. Solutions accepted > 0
3. Posts in communities the new user just joined
4. Verified badge holders

Limit to 5–8 suggestions.

---

## Step 4: Welcome to Your Feed

**URL:** `/dashboard` (with onboarding complete flag)

On first arrival at the feed, show a **one-time contextual tooltip ribbon** (not a modal):

```
┌──────────────────────────────────────────────────────┐
│  🎉  You're all set! Here's your personalised feed.  │
│  Share your first post to introduce yourself.  [×]  │
└──────────────────────────────────────────────────────┘
```

The ribbon disappears on click or after 10 seconds.

---

## Activation Nudge: First Post Prompt

5 minutes after the user's first session (or on their second login if no post was made), show an inline card at the top of the feed:

```
┌────────────────────────────────────────────────────────────┐
│  ✏️  Introduce yourself to the community                    │
│  Tell the community who you are and what you work on.       │
│  [ Write a quick introduction ]   [ Not now ]               │
└────────────────────────────────────────────────────────────┘
```

This pre-fills the CreatePost form with type = `discussion` and title = `"Introduce yourself"`.

---

## Onboarding Progress Tracking

Store onboarding state in `pulse_profiles`:

```php
// New columns to add via migration
'onboarding_step'      => 0–4 (0 = not started, 4 = complete)
'onboarding_completed' => boolean
'onboarding_completed_at' => timestamp nullable
```

Or use a separate `pulse_onboarding_progress` table for more flexibility.

---

## Returning User Experience

After onboarding is complete, the `/dashboard` always shows the full feed with no onboarding UI.

Exception: if profile is < 20% complete (no avatar, no headline, no communities), show a **Profile Completeness Card** in the sidebar:

```
Your profile is 20% complete
[Add headline]  [Upload photo]  [Join communities]
```

---

## Success Metrics

| Metric | Target |
|---|---|
| Onboarding completion rate | > 70% |
| Step 1 → Step 2 conversion | > 85% |
| Communities joined per user | ≥ 2 within first session |
| Post or comment in first 10 minutes | > 40% of new users |
| D1 retention (returned next day) | > 50% |
| D7 retention | > 25% |

---

## Implementation Checklist

- [ ] New `OnboardingController` or route group `/onboarding/*`
- [ ] Three Livewire components: `OnboardingProfile`, `OnboardingCommunities`, `OnboardingFollow`
- [ ] Add `onboarding_completed` to `pulse_profiles`
- [ ] Migration for new columns
- [ ] Redirect newly registered users to `/onboarding/profile`
- [ ] Skip links on each step that mark onboarding complete
- [ ] Welcome ribbon component (dismissible)
- [ ] First-post nudge card logic
- [ ] Profile completeness calculation for sidebar card
- [ ] Add onboarding tests to test suite
