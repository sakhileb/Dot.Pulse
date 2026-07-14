# Dot.Pulse Content Guidelines
Version: 1.0 | Voice, Tone & Microcopy | Classification: Internal

---

## Voice

Dot.Pulse speaks like a **knowledgeable colleague** — direct, helpful, and confident. Not like a marketing team, not like a robot.

| We are | We are not |
|---|---|
| Direct | Blunt |
| Helpful | Patronising |
| Professional | Corporate or stuffy |
| Human | Casual or informal |
| Confident | Arrogant |
| Clear | Oversimplified |

---

## Tone Adjustments by Context

| Context | Tone | Example |
|---|---|---|
| Empty states | Encouraging, warm | "Be the first to start a conversation." |
| Errors | Calm, actionable | "That didn't work. Try again or contact support." |
| Success states | Affirming, brief | "Posted." / "Saved." |
| Moderation messages | Neutral, factual | "Your post is under review." |
| Destructive actions | Cautious, clear | "This cannot be undone." |
| Onboarding | Welcoming, guiding | "Pick the communities that match your work." |
| Notifications | Conversational | "Jane commented on your post." |

---

## Terminology Glossary

Use these terms consistently. Never invent synonyms.

| Use this | Not this | Notes |
|---|---|---|
| Post | Article, entry, submission | Generic term for all content types |
| Community | Group, forum, channel, space | Reddit-style groups |
| Member | User, subscriber, follower (of community) | Person in a community |
| Follow | Subscribe, watch | Person-to-person connection |
| Join | Subscribe, enter | Community membership |
| Leave | Unsubscribe, exit | Leaving a community |
| Reaction | Like, upvote | Emoji-based reactions |
| Solution | Answer (when marked) | Accepted answer to a question |
| Points | Coins, tokens, karma | Reputation currency |
| Badge | Achievement, reward | Earned recognition |
| Marketplace | Store, app store, directory | Platform for items |
| Install | Download, get, add | Acquiring a marketplace item |
| AI Moderation | Auto-review | The AI content pipeline |
| Feed | Timeline, stream | The main content stream |
| Dashboard | Home, overview | The landing page after login |
| Announcement | Notice, update | Official platform/company posts |
| Bug Report | Issue, ticket, defect | Bug-type posts |
| Showcase | Demo, example | "Look what I built" posts |
| Knowledge Graph | (technical — don't surface to users) | Internal concept only |

---

## Button Labels

Buttons should be **verb + noun** when the action needs clarity, or **verb only** when the context is obvious.

| Action | Label | Notes |
|---|---|---|
| Create a post | Publish | Not "Submit" or "Post" |
| Add a comment | Comment | Not "Submit" or "Reply" |
| React to content | (emoji only) | No label needed |
| Join a community | Join | Not "Become a member" |
| Leave a community | Leave | Not "Unsubscribe" |
| Follow a user | Follow | Not "Add friend" |
| Unfollow a user | Following | Not "Unfollow" — greyed ghost state |
| Install marketplace item | Install | Not "Get" or "Download" |
| Save profile | Save Changes | Not "Update" |
| Create community | Create Community | Full noun for clarity |
| Submit a report | Submit Report | Not "Flag" or "Report Content" |
| Mark as solution | Mark as Solution | Not "Accept Answer" |
| Send a message | Send | Icon-only button in chat |

---

## Placeholder Text

Placeholders should set expectation, not ask a question.

| Field | Placeholder |
|---|---|
| Post body | `Share something with the community…` |
| Post title | `Give your post a title…` |
| Comment | `Share your thoughts, experience or solution…` |
| Reply | `Write a reply…` |
| Community search | `Search communities…` |
| Global search | `Search posts, communities, people…` |
| Find a user (messaging) | `Find someone…` |
| Skills (profile) | `Laravel, Fleet Management, AI Automation…` |
| Message body | `Write a message…` |

---

## Empty States

Every empty state needs: an icon, a headline, optionally a subtitle, and when appropriate a CTA.

| Screen | Icon | Headline | Subtitle | CTA |
|---|---|---|---|---|
| Empty feed | `forum` | No posts yet. | Be the first to share something. | New Post |
| No communities | `groups` | No communities found. | Try a different search term. | — |
| No messages | `chat_bubble_outline` | No conversations yet. | Search for someone to start chatting. | — |
| No notifications | `notifications_none` | You're all caught up. | No new notifications. | — |
| No events | `event` | No upcoming events. | — | Create Event |
| Empty marketplace | `storefront` | No items yet. | — | Submit Item |
| No moderation items | `check_circle` | Queue is clear. | No items need review. | — |
| No search results | `manage_search` | No results for "{query}". | Try different keywords. | — |

---

## Error Messages

### Validation Errors (field-level)

Keep field errors short, specific, and non-blaming.

| Situation | Message |
|---|---|
| Required field empty | `This field is required.` |
| Too short | `Must be at least {n} characters.` |
| Too long | `Must be {n} characters or fewer.` |
| Invalid email | `Enter a valid email address.` |
| Invalid URL | `Enter a valid URL starting with https://.` |
| Already taken (slug/name) | `This name is already in use.` |
| File too large | `File must be under {n} MB.` |
| Unsupported file type | `Accepted formats: JPG, PNG, GIF, PDF.` |

### System Errors (toast / inline)

| Situation | Message |
|---|---|
| Generic error | `Something went wrong. Please try again.` |
| Rate limited | `Too many requests. Please wait a moment.` |
| Not authorised | `You don't have permission to do that.` |
| Resource not found | `This content doesn't exist or has been removed.` |
| Network error | `Check your connection and try again.` |

---

## Success Confirmations

Keep success messages brief. The result on screen is confirmation enough — don't repeat it.

| Action | Message |
|---|---|
| Post published | `Your post is being reviewed by AI moderation.` |
| Post approved (fast path) | (post appears in feed — no toast needed) |
| Comment added | (comment appears inline — no toast needed) |
| Profile saved | `Profile updated.` |
| Community created | (redirect to community page) |
| Marketplace item installed | `Installed successfully.` |
| Report submitted | `Thanks for the report. Our team will review it.` |
| Password changed | `Password updated.` |

---

## Notification Copy

Notifications use first-name attribution + concise action description.

| Event | Notification text |
|---|---|
| New comment | `{Name} commented on your post` |
| Follow | `{Name} started following you` |
| Reaction | `{Name} reacted 👍 to your post` |
| Solution accepted | `Your answer was accepted as the solution on "{Post Title}"` |
| Mention | `{Name} mentioned you in a comment` |
| Moderation approved | `Your post has been published` |
| Moderation rejected | `Your post was removed after review` |

---

## Moderation Communication

When content is moderated, communicate clearly and without accusation.

### Post Under Review

```
Your post is under review. It will be published automatically
once it passes our content check. This usually takes less than a minute.
```

### Post Removed

```
Your post was removed after review.
Reason: {reason}

If you believe this is a mistake, you can appeal this decision.
```

### Account Warning

```
Your account has received a warning for a policy violation.
Continued violations may result in suspension.
```

---

## Accessibility Copy

### ARIA Labels

Every interactive element without visible text needs an `aria-label`:

```html
<button aria-label="React with thumbs up">👍</button>
<button aria-label="Open notification menu">🔔</button>
<input aria-label="Search posts and communities" placeholder="Search…">
<a aria-label="View Jane Smith's profile">J</a>
```

### Alt Text

Images must have meaningful alt text:

```html
<!-- Profile photos -->
<img alt="Jane Smith's avatar" src="...">

<!-- Post attachments -->
<img alt="Screenshot showing fleet dashboard" src="...">

<!-- Decorative / empty-state icons -->
<span aria-hidden="true" class="material-symbols-rounded">forum</span>
```

---

## Date and Number Formatting

| Type | Format | Example |
|---|---|---|
| Recent timestamps | Relative | `3 minutes ago`, `2 hours ago` |
| Older timestamps | Day + time | `Mon, 14 Jul · 10:30 AM` |
| Archive timestamps | Full date | `14 July 2026` |
| Large numbers | Abbreviated above 1K | `1.2K`, `42.3K`, `1.2M` |
| Exact counts | Comma-separated | `1,247 members` |
| Percentages | No decimal for whole numbers | `67%` not `67.0%` |
| Points | No decimal | `2,500 pts` |

---

## Writing for the Feed

Post bodies are community content — not marketing copy. Guide users toward:

✅ **Do:**
- Share specific, useful information
- Ask one clear question at a time
- Describe the problem before asking for the solution
- Credit others when building on their work

❌ **Don't:**
- Vague posts like "Anyone else having issues?"
- Self-promotional posts without useful content
- All-caps titles
- Excessive hashtags (3 max)
