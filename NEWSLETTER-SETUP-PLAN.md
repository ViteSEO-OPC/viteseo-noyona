# Newsletter Setup Plan — Brevo + Google Workspace Domain Auth

**Goal:** Let an admin compose newsletters inside WordPress and email them to subscribed WooCommerce customers, for free, with good inbox deliverability.

**Approach:** Use **Brevo's free plan** as the delivery engine (300 emails/day, unlimited contacts) and your **Google Workspace domain** as the identity/authentication layer (SPF/DKIM/DMARC + a branded `from` address). Workspace itself does **not** send the newsletters — it's only used for domain authentication and a professional sender address.

> Estimated time: ~45–60 min total (DNS propagation is the only real wait).

---

## Phase 1 — Create your Brevo account (~5 min)

1. Go to **brevo.com** → **Sign up free**. Use a real address you control.
2. Choose the **Free plan** (300 emails/day, unlimited contacts). No card required.
3. Verify your email, complete the short onboarding (company name, etc.).
4. If onboarding asks for a sender, skip/accept for now — we set the real one up in Phase 3.

---

## Phase 2 — Find your DNS control panel (~5 min)

Your newsletter authentication records go wherever your domain's DNS is managed. **This is usually NOT Google** — Google Workspace uses your DNS but doesn't host it.

1. Recall where you bought the domain (GoDaddy, Namecheap, Cloudflare, your host, etc.) — that's almost certainly your DNS host.
2. If unsure, look up the domain's nameservers (or ask for help).
3. Log in there and find **DNS / DNS records / Zone editor**. Keep this tab open.
4. **Note your existing SPF record** — a TXT record starting with `v=spf1`. It almost certainly contains `include:_spf.google.com` for Workspace. You will **edit this one**, not add a new one.

---

## Phase 3 — Authenticate your domain in Brevo (~5 min)

1. In Brevo: top-right **account name** → **Senders, Domains & Dedicated IPs** → **Domains** tab.
2. Click **Add a domain** → enter `yourdomain.com` (no `www`, no `@`).
3. Brevo shows DNS records to add — typically:
   - **2 DKIM records** (names like `brevo1._domainkey` / `brevo2._domainkey`)
   - **1 DMARC record** (TXT at `_dmarc`) — *only if you don't already have one*
   - A **Brevo code** TXT record (domain ownership verification)
   - An **SPF** include: `include:spf.brevo.com`
4. Leave this Brevo tab open — you'll copy these values in Phase 4.

---

## Phase 4 — Add the records to your DNS (~10 min + propagation)

Go to your DNS panel and add each Brevo record. Three things to get right:

### A) SPF — MERGE, don't add a second one ⚠️ (the #1 mistake)

- Find your existing TXT record: `v=spf1 include:_spf.google.com ~all`
- Edit it to add Brevo's include **before** `~all`:
  ```
  v=spf1 include:_spf.google.com include:spf.brevo.com ~all
  ```
- **Never create a second `v=spf1` record** — two SPF records break email auth for both Google and Brevo.

### B) DKIM

- Add Brevo's two records exactly as shown (copy/paste name + value).
- Watch for trailing dots and your provider auto-appending the domain.

### C) DMARC

- Check if a TXT record already exists at `_dmarc.yourdomain.com`:
  - If **yes**, leave it (don't duplicate).
  - If **no**, add a gentle starter:
    ```
    Name:  _dmarc
    Type:  TXT
    Value: v=DMARC1; p=none; rua=mailto:you@yourdomain.com
    ```

Also add the **Brevo verification code** TXT record.

Then return to Brevo's Domains tab → **Verify / Authenticate**. DNS can take minutes to hours — if it doesn't verify immediately, that's normal. Continue with Phase 5 meanwhile.

---

## Phase 5 — Create your sender address (~2 min)

1. Brevo → **Senders** tab → **Add a sender**.
2. Name: e.g. `Noyona Newsletter`. Email: `newsletter@yourdomain.com`.
   - This address should exist in Google Workspace (create the user/alias in the Workspace admin console, or use one you already have). Brevo emails a confirmation link to it — you must be able to read that inbox.
3. Confirm via the email Brevo sends.

---

## Phase 6 — Install & connect the Brevo WordPress plugin (~10 min)

1. WordPress admin → **Plugins → Add New** → search **"Brevo"** (official plugin) → **Install** → **Activate**.
2. New **Brevo** menu appears → open it → it asks for an **API key**.
3. Get the key: Brevo web → account name → **SMTP & API** → **API Keys** → **Generate a new API key** → copy → paste into WordPress → **Connect**.
4. In plugin settings, enable the **SMTP / transactional email** toggle so WordPress + WooCommerce system emails (order confirmations, etc.) also route through Brevo and inherit your domain authentication.

---

## Phase 7 — Collect subscribers from WooCommerce (~5 min)

1. In the Brevo plugin settings → **WooCommerce** section → enable customer sync (creates/maps a Brevo contact list from your customers).
2. Enable the **checkout opt-in checkbox** ("subscribe to our newsletter") so new customers consent at purchase.
3. To capture non-buyers: Brevo → **Contacts → Forms** → build a signup form → embed it via the plugin's subscription form block/widget.
4. Import existing consented emails: Brevo → **Contacts → Import** (CSV). Only import people who actually opted in.

---

## Phase 8 — Create and send your first newsletter (~10 min)

1. Brevo → **Campaigns → Email → Create a campaign**.
2. **From**: select `newsletter@yourdomain.com`.
3. **Recipients**: pick your WooCommerce list.
4. Design with the **drag-and-drop editor** (or a template).
5. **Send a test to yourself first** → open in Gmail → three-dot menu → **Show original** → confirm **SPF: PASS, DKIM: PASS, DMARC: PASS**. This proves domain auth works.
6. If your list is **over 300**, send in daily batches (free-tier cap) — Brevo can schedule them.
7. **Send.**

---

## Verification checklist

- [ ] Brevo domain shows **Authenticated** (green) in the Domains tab
- [ ] Only **one** `v=spf1` record exists, containing both Google and Brevo
- [ ] Test email shows **SPF/DKIM/DMARC = PASS** in Gmail's "Show original"
- [ ] WordPress sends a test WooCommerce order email through Brevo successfully
- [ ] Checkout opt-in checkbox appears at checkout

---

## Why this setup (the reasoning)

- **Brevo, not Google Workspace, sends the newsletters.** Workspace caps at 2,000 recipients/day (counts your business email too), and Google's policy forbids bulk marketing through Gmail/Workspace — doing it risks throttling or suspending your real business email.
- **Workspace's value is the domain.** Since Feb 2024, Gmail/Yahoo require SPF/DKIM/DMARC for bulk senders or mail goes to spam. Authenticating Brevo against your domain is what lands newsletters in inboxes.
- **Free-tier limits to remember:** 300 emails/day, provider branding in emails, advanced automation/analytics are paid.

## Sources

- Brevo free plan limits: https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan
- Gmail sending limits (Google): https://support.google.com/a/answer/166852
- Email sender guidelines (Google): https://support.google.com/a/answer/81126
