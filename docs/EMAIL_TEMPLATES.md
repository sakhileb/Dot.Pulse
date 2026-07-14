# Dot.Pulse Email Templates
Version: 1.0 | Transactional Email | Classification: Internal

> **Current state:** `MAIL_MAILER=log` — all emails are written to logs only.
> This document defines the templates to implement when real email delivery is configured.

---

## Email Configuration (When Ready)

Recommended providers:
- **Mailgun** — best for transactional volume, good analytics
- **AWS SES** — cost-effective at high volume, requires more setup
- **Postmark** — best deliverability for notifications

Update `.env` when ready:
```
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=pulse.infodot.app
MAILGUN_SECRET=your-key
MAIL_FROM_ADDRESS="hello@pulse.infodot.app"
MAIL_FROM_NAME="Dot.Pulse"
```

---

## Email Design System

All emails share the same visual identity as the platform.

### Base Layout

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject }}</title>
  <style>
    body { margin:0; padding:0; background:#f4f4f5; font-family:'Inter',Helvetica,Arial,sans-serif; font-size:14px; color:#3f3f46; }
    .wrapper { max-width:600px; margin:0 auto; padding:24px 16px; }
    .card { background:#ffffff; border-radius:12px; padding:32px; border:1px solid #e4e4e7; }
    .header { text-align:center; padding:0 0 24px; border-bottom:1px solid #f4f4f5; margin-bottom:24px; }
    .logo { font-family:Georgia,'Times New Roman',serif; font-size:20px; font-weight:700; color:#09090b; letter-spacing:-0.02em; }
    .logo span { color:#9333ea; }
    .btn { display:inline-block; background:#9333ea; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:600; font-size:14px; }
    .footer { text-align:center; padding:24px 0 0; font-size:12px; color:#a1a1aa; line-height:1.6; }
    h1 { font-size:22px; font-weight:700; color:#09090b; margin:0 0 8px; }
    p { line-height:1.6; margin:0 0 16px; }
    .meta { font-size:12px; color:#a1a1aa; }
    .quote { background:#fafafa; border-left:3px solid #9333ea; padding:12px 16px; border-radius:0 8px 8px 0; margin:16px 0; font-style:italic; color:#52525b; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <div class="logo">Dot<span>.</span>Pulse</div>
        <div style="font-size:11px;color:#a1a1aa;margin-top:4px;text-transform:uppercase;letter-spacing:0.08em;">Community Intelligence</div>
      </div>

      <!-- Content slot -->
      {{ $slot }}

    </div>
    <div class="footer">
      You received this email because you're a member of Dot.Pulse.<br>
      <a href="{{ $unsubscribeUrl ?? '#' }}" style="color:#9333ea;">Unsubscribe</a> ·
      <a href="https://pulse.infodot.app/settings/privacy" style="color:#9333ea;">Privacy</a> ·
      <a href="https://pulse.infodot.app" style="color:#9333ea;">Open Dot.Pulse</a>
    </div>
  </div>
</body>
</html>
```

Note: Email uses `#9333ea` (standard purple) instead of `#c084fc` — lighter colours don't render well on white backgrounds.

---

## Template 1: Welcome Email

**Trigger:** After email verification  
**Subject:** `Welcome to Dot.Pulse, {name}`

```
Hi {name},

You're now part of the Dot.Pulse community — a space for businesses,
developers, and partners to share knowledge, discover automation, and
build what's next together.

[Complete Your Profile →]

Here's what you can do:
• Ask questions and get answers from the community
• Join industry-specific communities
• Discover AI agents and automations in the Marketplace
• Share your own insights and success stories

If you have any questions, reply to this email — a real person reads it.

Welcome aboard,
The Dot.Pulse Team
```

---

## Template 2: Email Verification

**Trigger:** Registration  
**Subject:** `Verify your Dot.Pulse account`

```
Hi {name},

Tap the button below to verify your email address and activate your account.

[Verify Email Address →]

This link expires in 60 minutes.

If you didn't create an account on Dot.Pulse, you can safely ignore this email.
```

---

## Template 3: New Comment on Your Post

**Trigger:** `NewCommentOnPost` notification (if email channel enabled)  
**Subject:** `{author_name} commented on your post`

```
{author_name} left a comment on your post.

Your post:
"{post_title_or_preview}"

Their comment:
"{comment_preview}"

[View the discussion →]

Reply directly in Dot.Pulse to keep the conversation going.
```

---

## Template 4: Your Answer Was Accepted as a Solution

**Trigger:** `SolutionAccepted` notification  
**Subject:** `Your answer was accepted as a solution! 🎉`

```
Your answer was accepted as the solution on Dot.Pulse.

Post: "{post_title}"
Community: {community_name}

You earned 10 community points.
Your solutions accepted count: {solutions_accepted}

[View the solution →]

Keep contributing — you're building your reputation as an expert.
```

---

## Template 5: New Follower

**Trigger:** `NewFollower` notification  
**Subject:** `{follower_name} is now following you`

```
{follower_name} started following you on Dot.Pulse.

[View their profile →]   [Follow back →]

You now have {follower_count} followers.
```

---

## Template 6: Password Reset

**Trigger:** Password reset request  
**Subject:** `Reset your Dot.Pulse password`

```
Hi {name},

We received a request to reset your Dot.Pulse password.

[Reset Password →]

This link expires in 60 minutes.

If you didn't request a password reset, you can safely ignore this email.
Your password will not be changed.

For security questions, contact us at security@infodot.co.za.
```

---

## Template 7: Team Invitation

**Trigger:** Team member invited  
**Subject:** `{inviter_name} invited you to join {team_name} on Dot.Pulse`

```
Hi,

{inviter_name} has invited you to join the {team_name} workspace on Dot.Pulse.

[Accept Invitation →]

If you don't have a Dot.Pulse account, you'll be asked to create one first.

This invitation expires in 7 days.
If you weren't expecting this, you can ignore it.
```

---

## Template 8: Post Moderation Decision

**Trigger:** Post approved or rejected after review  
**Subject (approved):** `Your post has been published`  
**Subject (rejected):** `Your post was removed after review`

**Approved:**
```
Good news — your post passed our content review and has been published.

Post: "{post_title}"
Community: {community_name}

[View your post →]
```

**Rejected:**
```
Your post was reviewed and removed from Dot.Pulse.

Post: "{post_title}"
Reason: {moderation_rationale}

If you believe this was a mistake, you can appeal this decision.
[Submit an Appeal →]

Repeated violations may result in account restrictions.
Review our Community Guidelines to avoid future issues.
[Community Guidelines →]
```

---

## Template 9: Weekly Digest (Planned)

**Trigger:** Weekly scheduled job (opt-in only)  
**Subject:** `Your Dot.Pulse week — {date range}`

```
Here's what happened this week in your communities:

TOP DISCUSSIONS
• {post_title_1} — {comment_count} comments
• {post_title_2} — {comment_count} comments
• {post_title_3} — {comment_count} comments

YOUR ACTIVITY
• {posts_created} posts published
• {points_earned} points earned this week
• Total points: {total_points}

TRENDING IN {community_name}
• {trending_topic_1}
• {trending_topic_2}

[Open your feed →]

To change your email preferences, visit your notification settings.
[Manage preferences →]
```

---

## Template 10: Security Alert

**Trigger:** New login from unrecognised device (planned)  
**Subject:** `New sign-in to your Dot.Pulse account`

```
Hi {name},

We detected a new sign-in to your Dot.Pulse account.

Time:     {timestamp}
Location: {city}, {country}
Device:   {browser} on {os}
IP:       {ip_address}

If this was you, no action is needed.

If this wasn't you, secure your account immediately:
[Change Password →]   [Revoke All Sessions →]

If you have concerns, contact our security team at security@infodot.co.za.
```

---

## Implementation Checklist

When the email mailer is configured:

- [ ] Create `resources/views/emails/` directory with base layout component
- [ ] Implement Mailable classes for each template in `app/Mail/`
- [ ] Update notification `via()` methods to add `'mail'` when user has email enabled
- [ ] Add email preference toggles to notification settings UI
- [ ] Store email preferences in `pulse_profiles` or dedicated table
- [ ] Test all templates in email preview tools (Mailtrap, Litmus, Email on Acid)
- [ ] Test plain-text fallbacks for all templates
- [ ] Verify unsubscribe links work correctly
- [ ] Add DKIM and SPF records to DNS
- [ ] Configure DMARC policy
- [ ] Test delivery to major providers (Gmail, Outlook, Apple Mail)
