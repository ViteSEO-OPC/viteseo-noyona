# NOYONA_GOOGLE_FIRST_AUTH_WORKFLOW.md

## Changelog

### Implemented

* Phase 1 — Google → WooCommerce customer bridge (Nextend integration, customer role, account linking, password-setup flag, admin auto-link protection, core registration email suppression).
* Phase 2 — First-time Set Password flow (Set Password button + modal, handler, notices, automatic switch to Change Password, email+password login after creation).
* Authentication Hardening — core new-user notification suppressed; themed WooCommerce welcome email is the single source of truth; WooCommerce automatic customer_new_account email disabled after the manual send to prevent duplicates.
* Google Name Forwarding — Google first_name / last_name / display_name forwarded into WooCommerce customer creation (only when provided).
* Phase 3 — Google-First Registration (COMMITTED, MERGED INTO QA). Register page renders the Google-only CTA by default; manual registration disabled via the `noyona_allow_manual_registration` filter (defaults false) at both the UI and the server handler.
* Phase 4 — One-time welcome modal for first-time Google-created users (COMMITTED `ec07e4e`; NOT YET MERGED INTO QA). Explicit-dismissal model: new `noyona_welcome_modal_pending` flag set at Google registration; auto-opens on My Account (profile tab) and is cleared ONLY by an explicit dismissal (Continue Later / Complete Profile / Back / backdrop) via the `noyona_dismiss_welcome_modal` handler. Reuses existing modal markup/CSS; fully additive. Kill switch: `noyona_enable_welcome_modal`.

### Currently in QA

* Phase 1 (Google signup → customer account, welcome email, account linking).
* Phase 2 (first-time Set Password → email login).
* Authentication Hardening (single welcome email guarantee across edge cases).
* Google Name Forwarding (Full Name population + welcome email greeting).
* Phase 3 (Google-only registration CTA, manual-form suppression, direct-POST rejection) — committed and merged into QA; pending QA validation.
* Phase 4 (welcome modal: appears for new Google users, persists until explicit dismissal, never shows for existing/auto-linked users, Complete Profile opens existing Edit modal) — committed (`ec07e4e`); not yet merged into QA; pending QA validation.

### Future Scope

* None pending. Phase 4 is implemented and committed. Future enhancements TBD.

---

## Current Status

### Completed

#### Phase 1

Google → WooCommerce Customer Bridge

Implemented:

* Nextend Social Login integration
* WooCommerce customer creation
* Customer role assignment
* Google account linking
* Password setup flag
* Admin auto-link protection
* Core registration email suppression
* Google-created customer account flow

Status:

READY FOR QA

Notes:

* Current analysis indicates exactly ONE welcome email should be sent in the normal flow.
* Duplicate emails are only possible in specific non-standard edge cases.
* Authentication Hardening now implemented (see below).
* Google Name Forwarding now implemented (see below).

---

#### Phase 2

First-Time Set Password Flow

Implemented:

* Set Password button for Google-created users
* Dedicated Set Password modal
* Password setup handler
* Success/Error notices
* Automatic switch to Change Password after password creation
* Email + Password login support after password creation

Status:

READY FOR QA

---

### Implemented Improvements

#### Authentication Hardening

Recommended:

* Prevent duplicate welcome emails in non-exit edge cases.
* Use welcome email as single source of truth.

Implemented:

* WordPress core "new user" notification suppressed during Google registration (`remove_action( 'register_new_user', 'wp_send_new_user_notifications' )`).
* Themed WooCommerce welcome email triggered manually exactly once as the single source of truth.
* WooCommerce automatic `customer_new_account` email disabled after the manual send (`woocommerce_email_enabled_customer_new_account` → false) to guarantee no duplicate on rare non-exit paths.

Status:

IMPLEMENTED — READY FOR QA

---

#### Google Name Forwarding

Recommended:

Forward Google profile name data into:

* first_name
* last_name
* display_name

Benefits:

* Full Name field populated automatically
* Welcome email greeting uses customer name
* Better WooCommerce customer profile experience

Implemented:

* Google-provided first_name, last_name, and display_name are forwarded into `wc_create_new_customer()` during Google registration.
* Keys are only included when present, preserving prior behavior when Google does not share a name.

Status:

IMPLEMENTED — READY FOR QA

---

### Implemented & Committed

#### Phase 3

Google-First Registration

Scope:

* Convert Register page into Google-only CTA
* Disable manual account creation
* Remove email/password registration workflow

Current state (verified in codebase):

* IMPLEMENTED. The Register page (`[noyona_register_form]` / `woocom_ct_register_form_shortcode`) renders the "Continue with Google" CTA by default ("Create your account securely with Google.").
* Manual registration is gated behind the `noyona_allow_manual_registration` filter, which defaults to `false`. Nothing in the codebase sets it to `true`.
* The manual form markup is retained but only renders when that filter is `true` (kept for easy rollback).
* Server-side enforcement: `woocom_ct_handle_register_form()` short-circuits (redirect to login) BEFORE any account-creation logic when the filter is `false`, so manual account creation is impossible even via a direct/bypassed POST.

Commit status:

* COMMITTED and MERGED INTO QA (part of the Google-first registration / password-setup work, e.g. `b29dfb6`, merged into `qa`).

Status:

IMPLEMENTED — COMMITTED — MERGED INTO QA — PENDING QA VALIDATION

Next steps:

* QA the Google-only registration path (CTA render, manual-form suppression, direct-POST rejection).

---

# Final Business Goal

## Registration

Google Only

Flow:

User
↓
Continue with Google
↓
Google verifies email ownership
↓
WooCommerce Customer Created
↓
Welcome Email Sent
↓
Redirect to My Account

Account is immediately usable.

---

## Existing Profile Workflow

NO PROFILE REDESIGN

After Google signup:

User lands on the existing My Account page.

Existing functionality remains unchanged:

* Full Name
* Email
* Phone Number
* Edit Profile
* Existing Profile Layout
* Existing Styling

The current customer experience should remain intact.

---

## Optional Profile Completion

User may optionally update:

* Full Name
* Phone Number

Using existing functionality only.

Do NOT introduce:

* First Name field
* Last Name field
* Address field

unless already supported elsewhere by the current implementation.

Reuse existing profile functionality only.

---

## Password Flow

### New Google User

Google Signup
↓
Account Created
↓
My Account
↓
Set Password (Optional)
↓
Password Saved

After successful setup:

Set Password
↓
replaced by
↓
Change Password

No forced password setup.

---

## Login Methods

Both must work for the SAME account.

### Option 1

Continue with Google

### Option 2

Email + Password

Example:

Email:

[bruce@gmail.com](mailto:bruce@gmail.com)

Password:

MySecurePassword123

Result:

* Same User
* Same Orders
* Same WooCommerce Customer
* Same Account

No duplicate accounts.

---

# Critical QA Checklist

## Test 1

Google Signup

Expected:

* Account created
* Customer role assigned
* Redirect to My Account
* Logged in automatically
* Google account linked

PASS / FAIL

---

## Test 2

Welcome Email

Expected:

Exactly ONE welcome email.

Verify:

* No duplicate email
* No WordPress default registration email
* Only Noyona welcome email

PASS / FAIL

IMPORTANT:

Highest priority validation item.

---

## Test 3

Google Name Population

Expected:

When Google provides profile name:

* Full Name populated correctly

Verify:

* Profile page
* Welcome email greeting

PASS / FAIL

Pending improvement if not yet implemented.

---

## Test 4

Password Action

New Google-created user should see:

Set Password

User should NOT see:

Change Password

PASS / FAIL

---

## Test 5

Set Password Validation

Verify:

* Empty password
* Short password
* Password mismatch

Expected:

Proper validation messages.

PASS / FAIL

---

## Test 6

Successful Password Setup

Set:

MySecurePassword123

Expected:

* Password saved
* Success notice displayed
* Set Password replaced by Change Password

PASS / FAIL

---

## Test 7

Email Login

Logout

Login using:

Email:
Google account email

Password:
Created password

Expected:

Successful login

PASS / FAIL

---

## Test 8

Google Login

Logout

Login using:

Continue with Google

Expected:

Successful login

PASS / FAIL

---

## Test 9

Same Account Verification

Verify:

Email Login
AND
Google Login

Both enter:

* Same account
* Same orders
* Same profile
* Same customer record

PASS / FAIL

---

## Test 10

Existing User Regression

Existing customer account

Verify:

* Login works
* Logout works
* Change Password works
* Edit Profile works
* Orders visible

PASS / FAIL

---

# Known Edge Cases

## Pre-Existing Google Users

Users created before Phase 1:

* No password setup flag
* Will see Change Password
* May not know random password

Current workaround:

* Lost Password flow

Future improvement possible:

* One-time password setup flag backfill

Status:

OUT OF SCOPE

---

## Google Account Without Email Scope

Expected:

Nextend fallback flow handles email collection.

Status:

LOW RISK

---

## WooCommerce Disabled

Expected:

Bridge bypassed.

Nextend falls back to standard user creation.

Status:

LOW RISK

---

# Scope Restrictions

DO NOT MODIFY

* Full Name field
* Email field
* Phone Number field
* Existing Edit Profile functionality
* Existing Edit Profile modal
* Existing Change Password modal
* Existing Profile Layout
* Existing CSS
* Existing Account Structure

Allowed:

* Set Password button
* Set Password modal
* Authentication-related code only
* Google signup flow
* WooCommerce customer bridge

Preferred implementation:

Additive only.

No UI redesign.

No profile refactor.

---

# Before Phase 3

ALL of the following must PASS:

* Google Signup
* Welcome Email
* Set Password
* Email Login
* Google Login
* Same Account Verification
* Existing User Regression

Only then:

Proceed to Phase 3.

---

# Phase 3 Goal (Implemented — Committed — Merged into QA — Pending QA Validation)

Convert:

/register/

from:

Manual Registration Form

to:

Continue with Google

CTA only.

Then:

Disable manual account creation.

Result:

Registration:
Google Only

Login:
Google OR Email + Password

This closes the unverified-email ownership issue.

Status note:

* This goal is realized via the `noyona_allow_manual_registration` filter (defaults false). The change is committed and merged into QA, pending QA validation.
* See the Phase 3 entry under "Current Status" for verification details and commit status.

---

# Source of Truth

Target User Journey:

User
↓
Continue with Google
↓
Google verifies email ownership
↓
Account Created
↓
Welcome Message / Welcome Email
↓
Redirect to Existing My Account
↓
(Optional) Update Full Name
↓
(Optional) Update Phone Number
↓
(Optional) Set Password
↓
Future Login:
- Continue with Google
- Email + Password

Both login methods must authenticate the same customer account.

---

# Phase 4 — One-Time Welcome Modal (IMPLEMENTED — Committed `ec07e4e` — Explicit Dismissal)

Status: IMPLEMENTED — COMMITTED (`ec07e4e`) — NOT YET MERGED INTO QA — PENDING QA VALIDATION. Explicit-dismissal approach (clear-on-render REJECTED). Fully additive.

Implemented in:
* `inc/google-auth.php` — `NOYONA_WELCOME_MODAL_META` constant, `noyona_user_has_pending_welcome()` helper, flag set in `noyona_nsl_after_google_register()`, and `noyona_dismiss_welcome_modal_handler()` on `admin_post_noyona_dismiss_welcome_modal`.
* `inc/shortcodes.php` — welcome modal markup (`#noyona-account-welcome-modal`) + `$noyona_show_welcome` gate inside the `[noyona_account_page]` profile branch.
* No CSS changes (reuses existing `.noyona-account-modal*` classes).

## Concept

A lightweight, informational welcome modal shown to first-time Google-created users when they land on My Account after signup. The modal persists (re-shows on each My Account profile visit) until the user explicitly dismisses it; only then is the flag cleared.

Scenario:

User
↓
Continue with Google
↓
Account Created
↓
Welcome Email
↓
Redirect to My Account
↓
Welcome Modal (until explicitly dismissed)

## Messaging (exact)

Welcome to Noyona!

Your account has been created successfully using Google.

You can continue using Google to sign in, or set a password later if you would like to sign in using your email address.

[ Complete Profile ]
[ Continue Later ]

## Behavior Requirements (approved)

* Google-created users only.
* Show once only (never re-shows after explicit dismissal).
* Never show for existing users.
* Never show for auto-linked users.
* Informational only.
* No forced profile completion.
* No forced password setup.
* Never blocks access to My Account.
* Reuse existing modal infrastructure.
* Fully additive.

## Hard Constraints (must remain true)

* No changes to Register page, Login page, Profile layout, Edit Profile workflow, Set Password workflow, or Change Password workflow.
* No CSS architecture changes / no CSS refactor.
* No authentication or account workflow changes.
* Only add the welcome modal behavior.

## Dismissal model (APPROVED — explicit only)

Clear-on-render is explicitly NOT used. The `noyona_welcome_modal_pending` flag is removed ONLY after an intentional user action, guaranteeing the user actually saw and interacted with the modal. The flag survives tab close, refresh, network interruption, and navigation-away — the modal simply re-appears on the next My Account profile visit until dismissed.

Valid dismissal actions (all clear the flag):

* Complete Profile
* Continue Later
* Explicit close (Back chevron / backdrop)

Mechanism: every dismissal affordance is a link to a dedicated `admin-post` handler (`admin_post_noyona_dismiss_welcome_modal`) that verifies a nonce, deletes the flag, and redirects. This is no-JS, mirrors the existing `admin_post_noyona_set_account_password` convention, and clears the flag only on a deliberate click.

## Technical design

* New user meta: `noyona_welcome_modal_pending = '1'` (constant `NOYONA_WELCOME_MODAL_META`).
  - Set additively in the existing `noyona_nsl_after_google_register()` hook, alongside `noyona_requires_password_setup`.
  - Do NOT reuse `noyona_requires_password_setup` (separate lifecycles).
  - Guarded by an optional kill-switch filter `noyona_enable_welcome_modal` (default true).
* Helper: `noyona_user_has_pending_welcome( $user_id )` (mirrors `noyona_user_requires_password_setup()`).
* Dismiss handler: `noyona_dismiss_welcome_modal_handler()` on `admin_post_noyona_dismiss_welcome_modal` — logged-in + nonce check, `delete_user_meta`, `wp_safe_redirect( redirect_to )`.
* Auto-open (server-driven, no clear-on-render): in the `[noyona_account_page]` profile branch, render the modal with `is-open` when:
  `'profile' === $active_tab && '' === $active_modal && noyona_enable_welcome_modal && noyona_user_has_pending_welcome( current user )`.
  Gating on `'' === $active_modal` prevents stacking on top of an explicitly requested modal (e.g. `set_password`).
* Markup: new modal `#noyona-account-welcome-modal` reusing the existing `.noyona-account-modal` / `.noyona-account-modal-dialog` / `.noyona-account-modal-backdrop` / `.noyona-account-modal-actions` classes. No new CSS.
* Buttons:
  - Continue Later / Back / backdrop → dismiss handler, redirect to the plain account URL.
  - Complete Profile (Option A) → dismiss handler, redirect to account URL with `?noyona_modal=edit`, which opens the EXISTING Edit Profile modal server-side. No new onboarding steps; does NOT route into Set Password.

## Interaction with the existing password-setup flag

* Independent flags. Welcome flag cleared on explicit dismissal; password-setup flag cleared only when a password is actually set (existing handler, untouched).
* After dismissal, the profile still shows the existing "Set Password" button (password-setup flag still set). The welcome flow never reads or clears it.
* No modal collision: welcome auto-opens only when no other modal is requested.

## Rollback strategy

* Instant disable: set filter `noyona_enable_welcome_modal` to false.
* Code removal: remove the meta-set line, constant, helper, and dismiss handler from `google-auth.php`; remove the welcome modal block + `$show_welcome` logic from `shortcodes.php`. No CSS to revert.
* Optional data cleanup: `DELETE FROM wp_usermeta WHERE meta_key = 'noyona_welcome_modal_pending';`

## QA checklist

* New Google signup → My Account → welcome modal appears.
* Refresh / navigate away without dismissing → modal re-appears (flag NOT consumed).
* Continue Later → flag cleared, modal gone, stays on My Account; does not reappear on reload.
* Complete Profile → flag cleared, opens existing Edit Profile modal; no forced completion.
* Back / backdrop → flag cleared, modal gone.
* Existing email/password user → never shows.
* Auto-linked Google user → never shows.
* Non-Google / admin → never shows.
* Set Password button still present after dismissal; setting a password still works (Phase 2 regression).
* No welcome on orders/wishlist/addresses/payments tabs; no stacking with `?noyona_modal=...`.
* My Account not full-page cached for logged-in users.
* Strings localized under `noyona-childtheme`.
