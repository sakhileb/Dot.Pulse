# Dot.Pulse UX Principles
Version: 1.0 | Classification: Internal

---

## North Star

> Every interaction on Dot.Pulse should make someone smarter, more connected, or more productive within the Dot ecosystem.

This is not a social media platform for engagement metrics. It is a **professional community intelligence platform**. Every UX decision filters through this lens.

---

## The 9 User Personas

### 1. The Customer
**Who:** A business user of Dot ecosystem products (Dot.Admin, Dot.Agents, etc.)  
**Goal:** Get answers to product questions, report bugs, share feedback  
**Frustration:** Can't find the answer they need, feels unheard by the vendor  
**Primary jobs:**
- Ask a question and get a useful answer quickly
- Report a bug and see it acknowledged
- Discover how others solved a similar problem

**Design implication:** Questions must be easy to ask. Unanswered questions must be surfaced. Solutions must be prominently marked.

---

### 2. The Business
**Who:** A company using Dot ecosystem products  
**Goal:** Showcase their products, build brand presence, attract customers  
**Frustration:** No professional space to demonstrate expertise  
**Primary jobs:**
- Publish release notes and product updates
- Share success stories and case studies
- Monitor what customers say about their product

**Design implication:** Business/company content needs visual differentiation. Announcements and releases should stand out in the feed.

---

### 3. The Developer
**Who:** Technical builder creating agents, integrations, or automations  
**Goal:** Share what they built, find collaborators, get technical feedback  
**Frustration:** Generic platforms don't understand technical content  
**Primary jobs:**
- Post an agent or integration to get feedback
- Find other developers working on similar problems
- Publish to the marketplace

**Design implication:** Code-friendly content rendering. Agent/integration post types need dedicated UI. Marketplace submission must be frictionless.

---

### 4. The Partner
**Who:** A technology or consulting partner building on the Dot ecosystem  
**Goal:** Generate leads, demonstrate expertise, build reputation  
**Frustration:** Hard to differentiate from regular users  
**Primary jobs:**
- Get verified partner badge/status
- Publish content that establishes expertise
- Connect with enterprise customers

**Design implication:** Verification signals and reputation scores must be visible and credible.

---

### 5. The Enterprise Employee
**Who:** Staff at an enterprise customer using Dot ecosystem internally  
**Goal:** Find answers without going to support, contribute to internal knowledge  
**Frustration:** Support is slow, knowledge is scattered  
**Primary jobs:**
- Search for a solved problem before asking again
- Access the internal community feed
- Join industry-specific communities

**Design implication:** Search must be fast and accurate. Internal (enterprise) feeds must be clearly separated from public content.

---

### 6. The Moderator
**Who:** A community manager or platform admin  
**Goal:** Keep communities healthy, fair, and on-topic  
**Frustration:** Too many false positives from AI, slow queue, no context  
**Primary jobs:**
- Review flagged content quickly with full context
- Act (approve/reject) in one click
- See the AI's reasoning

**Design implication:** The moderation queue must show everything needed without clicking through. Approve and reject must be in the viewport.

---

### 7. The Administrator
**Who:** Dot engineering or ops staff managing the platform  
**Goal:** Operational control and visibility  
**Frustration:** Flying blind without metrics  
**Primary jobs:**
- See platform health at a glance
- Manage user accounts and community settings
- Respond to security or abuse escalations

**Design implication:** Admin views need dense information, not simplified cards.

---

### 8. The Guest
**Who:** Visitor not yet logged in  
**Goal:** Understand what the platform is and decide whether to join  
**Frustration:** Can't see anything without registering  
**Primary jobs:**
- See what kinds of discussions happen here
- Understand the value before committing
- Register easily

**Design implication:** Public feed/landing page must demonstrate value immediately. Registration must take < 60 seconds.

---

### 9. The Expert
**Who:** A power user with high reputation and solutions accepted  
**Goal:** Help others, build reputation, stay relevant  
**Frustration:** Contributions feel invisible and unrewarded  
**Primary jobs:**
- Answer questions before anyone else
- Get recognised for accepted solutions
- See their reputation grow visibly

**Design implication:** Reputation signals (points, badges, solutions accepted) must be prominent on profiles. "Accepted solution" badges must stand out in threads.

---

## Core UX Principles

### 1. Zero dead ends
Every empty state has a next action. Every error message tells you what to do next. No blank screens.

```
❌ Empty feed with no guidance
✅ "No posts yet. Be the first to share something." + [New Post] button
```

### 2. Immediate feedback
Every user action gets a visual response within 200ms. Long operations show progress.

```
❌ Button that does nothing visible for 2 seconds
✅ Button disables + text changes to "Posting…" immediately
```

### 3. Progressive disclosure
Don't show everything at once. Show the minimum needed, reveal more on demand.

```
❌ Giant post creation form always visible
✅ Single "Share something…" prompt → expands into full form on click
```

### 4. Earned trust
Reputation, badges, and verification signals must be earned, visible, and meaningful. A verified expert badge means something.

### 5. Speed is a feature
No interaction should feel slow. If it's slow, show that work is happening. If it's very slow, do it in the background.

### 6. Professional, not sterile
The platform serves businesses. Content should feel serious and credible, but not cold. Human warmth comes from community dynamics, not from rounded pastel buttons.

### 7. The feed is a product
The smart feed is a core differentiator. Users should feel that the platform understands them, not that it's showing them random content.

---

## Key User Journeys

### Journey 1: First-time User (New Registration)

```
Land on pulse.infodot.app
    ↓
See landing page with 8 feature highlights
    ↓
Click "Get started — it's free"
    ↓
Register (name, email, password)
    ↓
Email verification (or skip if SSO)
    ↓
[ONBOARDING FLOW — see ONBOARDING_FLOW.md]
    ↓
First community joined
    ↓
First feed view with relevant content
    ↓
"Post something" prompt appears
```

**Success metric:** User creates first post or comment within 10 minutes of registration.

---

### Journey 2: Asking a Question

```
User has a problem with a Dot product
    ↓
Opens feed or relevant community
    ↓
Clicks "Share something with the community…"
    ↓
Selects type: Question
    ↓
Writes title + body
    ↓
(Optional) Selects relevant community
    ↓
Submits → AI moderation runs in background
    ↓
Post appears in feed (status: pending → published)
    ↓
Notification when someone answers
    ↓
Can mark one answer as "Solution"
    ↓
Solution author gets notified + 10 points
```

**Success metric:** Question gets a response within 24 hours.

---

### Journey 3: Community Discovery

```
User wants to find their industry community
    ↓
Clicks "Communities" in sidebar
    ↓
Sees grid of communities with industry tags
    ↓
Searches "fleet" or browses Fleet Management tag
    ↓
Finds "Fleet Automation Hub"
    ↓
Views community page (posts, members, rules)
    ↓
Clicks "Join"
    ↓
Feed now includes Fleet community posts
```

---

### Journey 4: Marketplace Install

```
User wants an AI agent for invoice processing
    ↓
Clicks "Marketplace" in sidebar
    ↓
Filters by "Agents"
    ↓
Sees "Invoice Automation Workflow" (800 installs, 4.7★)
    ↓
Reads description
    ↓
Clicks "Install" → one click
    ↓
Confirmation toast / counter increments
    ↓
Can review after use
```

---

### Journey 5: Moderation Review

```
Post flagged by AI (spam score 0.7, safety 0.6)
    ↓
Appears in moderator's queue (/moderation)
    ↓
Moderator sees: author, community, post preview,
                AI scores, rationale
    ↓
Clicks "View" to read full post (new tab)
    ↓
Clicks "Approve" or "Reject"
    ↓
Decision logged, author notified
    ↓
Queue item disappears
```

---

## Interaction Patterns

### Tabs

Used for: post detail (comment/info), community detail (posts/members/rules), user profile (posts/badges).

```
Active tab:   border-bottom: 2px solid #c084fc; color: #c084fc;
Inactive tab: border-bottom: 2px solid transparent; color: #71717a;
Hover:        color: #d4d4d8; transition: 0.13s
```

### Cards as Links

Feed posts, community cards, and marketplace items are entire-card links. The card lifts (border brightens) on hover. Never wrap cards in `<a>` — use `wire:navigate` or `href` on the card element directly.

### Reactions

Reactions toggle. Clicking a reaction you've already given removes it. The count updates immediately (optimistic UI) and is corrected by server response.

### Follow / Unfollow

Follow button is primary (purple) when not following, ghost when following. Text changes: "Follow" → "Following". No confirmation dialog.

### Delete Confirmations

Destructive actions (delete post, leave community) need inline confirmation, not a modal. Show "Are you sure?" with confirm/cancel inline.

---

## Feedback States

Every form must have four states: **Default → Focused → Error → Success**

| State | Visual |
|---|---|
| Default | `border: 1px solid rgba(255,255,255,0.08)` |
| Focused | `border: rgba(192,132,252,0.45)` + `box-shadow: 0 0 0 3px rgba(192,132,252,0.07)` |
| Error | `border: rgba(248,113,113,0.45)` + red helper text below |
| Success | `border: rgba(74,222,128,0.35)` (momentarily after save) |

Error messages appear **below the field** in `font-size:11px; color:#f87171; margin-top:3px`.

---

## Navigation Principles

1. **The sidebar is the map.** Every major destination is one click from the sidebar.
2. **Active state is always visible.** The current page is always highlighted in the nav.
3. **Breadcrumb on detail pages.** Post detail, community show, user profile all have a back link.
4. **No multi-level dropdowns.** Flat nav — everything is at most one level deep.
5. **Admin-only items are conditionally shown.** Moderation link only appears for moderators/admins.
