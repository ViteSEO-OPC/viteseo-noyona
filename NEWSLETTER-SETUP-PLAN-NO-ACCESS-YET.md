# Newsletter Setup Plan — What You Can Do NOW (No DNS / No Workspace Email Access Yet)

**Your situation:** You don't yet have access to the project's Google Workspace email, and you don't yet have access to the domain's DNS. Both are required for the *final* setup (branded `from` address + domain authentication), but you can do a lot of real, testable work first.

**Strategy:** Build and fully test the entire newsletter system now using a **temporary sender** Brevo provides, then swap in your domain (DNS + Workspace address) later in a 15-minute finishing pass. Nothing you do now is wasted — only the sender/auth piece changes at the end.

> See `NEWSLETTER-SETUP-PLAN.md` for the full final version. This file is the "start now" version.

---

## What you CAN do now vs. what must WAIT

| Task | Now? | Needs DNS | Needs Workspace email |
| --- | :---: | :---: | :---: |
| Create Brevo account | ✅ | | |
| Install + connect WP plugin | ✅ | | |
| Sync WooCommerce customers | ✅ | | |
| Build subscription form + checkout opt-in | ✅ | | |
| Design newsletter template | ✅ | | |
| Send TEST emails (to yourself) | ✅ | | |
| Route WooCommerce transactional email via Brevo | ✅ | | |
| Branded `from` = `newsletter@yourdomain.com` | ❌ | | ✅ |
| Domain authentication (SPF/DKIM/DMARC) | ❌ | ✅ | |
| Real send to your subscriber list | ⚠️ later | ✅ | ✅ |

> ⚠️ **Do NOT do a real bulk send to customers until domain auth is done.** Without it, mail lands in spam and hurts your domain's reputation. Testing to *your own* inbox now is fine.

---

## PART A — Do this right now (no DNS / no Workspace needed)

### Step 1 — Create your Brevo account (~5 min)
1. **brevo.com** → **Sign up free** → use any email you control (your personal email is fine for now).
2. Pick the **Free plan** (300 emails/day, unlimited contacts).
3. Verify and finish onboarding.

### Step 2 — Use Brevo's temporary sender (~2 min)
1. Brevo → account name → **Senders, Domains & Dedicated IPs** → **Senders**.
2. Brevo auto-creates a sender from your signup email (something like `you@gmail.com`). **Use this for now.**
3. ⏳ *Later:* you'll replace this with `newsletter@yourdomain.com` once you have the Workspace address.

### Step 3 — Install & connect the WordPress plugin (~10 min)
1. WordPress admin → **Plugins → Add New** → search **"Brevo"** → **Install** → **Activate**.
2. Open the **Brevo** menu → it asks for an **API key**.
3. Brevo web → account name → **SMTP & API** → **API Keys** → **Generate a new API key** → copy → paste into WordPress → **Connect**.

### Step 4 — Sync WooCommerce + build opt-in (~10 min)
1. Brevo plugin → **WooCommerce** section → enable customer sync (creates a contact list).
2. Enable the **checkout opt-in checkbox** so new orders can subscribe.
3. Brevo → **Contacts → Forms** → build a signup form → embed it on the site.
4. *(Optional)* Import a few of your own test addresses via **Contacts → Import** to simulate a list.

### Step 5 — Design your newsletter (~10 min)
1. Brevo → **Campaigns → Email → Create a campaign**.
2. **From:** the temporary sender from Step 2.
3. Build the layout in the drag-and-drop editor (or a template). Save it as a draft/template to reuse later.

### Step 6 — TEST send to yourself (~5 min)
1. In the campaign, use **Send a test email** to your own inbox.
2. Confirm it arrives, images/links work, and the layout looks right on desktop + mobile.
3. Also place a test WooCommerce order (or trigger a test transactional email) to confirm the plugin routes WordPress mail through Brevo.

✅ At this point the **entire system works end to end** — only the branding/auth swap remains.

---

## PART B — Finishing pass (do this once you GET access)

### When you get DNS access:
1. Brevo → **Domains** tab → **Add a domain** → `yourdomain.com`.
2. Add Brevo's **DKIM** records to DNS.
3. **SPF — merge, don't duplicate:** edit the existing `v=spf1 ... include:_spf.google.com ... ~all` record to also include Brevo:
   ```
   v=spf1 include:_spf.google.com include:spf.brevo.com ~all
   ```
   ⚠️ Never create a second `v=spf1` record.
4. Add **DMARC** at `_dmarc` only if one doesn't already exist:
   ```
   v=DMARC1; p=none; rua=mailto:you@yourdomain.com
   ```
5. Add Brevo's **verification code** TXT record → click **Verify** in Brevo (can take minutes to hours).

### When you get the Workspace email:
1. Make sure `newsletter@yourdomain.com` exists in Google Workspace (user or alias).
2. Brevo → **Senders** → **Add a sender** → `newsletter@yourdomain.com` → confirm via the email Brevo sends to that inbox.
3. Edit your campaign/template **From** address → switch from the temporary sender to `newsletter@yourdomain.com`.

### Final verification before any real send:
1. Send a test → open in Gmail → **Show original** → confirm **SPF: PASS, DKIM: PASS, DMARC: PASS**.
2. Confirm Brevo Domains tab shows **Authenticated** (green).
3. Only **now** do a real send to your subscriber list (in 300/day batches if larger).

---

## Quick reference — order of operations

1. ▶️ **Now:** Brevo account → temp sender → WP plugin → WooCommerce sync → design → test to self.
2. ⏳ **When DNS arrives:** add domain → DKIM + merged SPF + DMARC → verify.
3. ⏳ **When Workspace arrives:** create `newsletter@` sender → swap From address.
4. ✅ **Then:** confirm SPF/DKIM/DMARC PASS → real send.

## Sources

- Brevo free plan limits: https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan
- Gmail sending limits (Google): https://support.google.com/a/answer/166852
- Email sender guidelines (Google): https://support.google.com/a/answer/81126
