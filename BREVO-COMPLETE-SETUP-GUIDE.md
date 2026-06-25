# Brevo Newsletter — Complete Setup Guide (Start to Finish)

This guide walks you through **everything** needed to get newsletters working on the website, from zero. It is written for non-technical people — every click is spelled out. Follow it **top to bottom, in order**, and don't skip steps.

> 🧭 **Two guides exist. This is the SETUP guide (do this once).**
> For everyday use after setup — navigating Brevo and sending newsletters — see **`BREVO-ADMIN-GUIDE.md`**.

---

## Before you start — who does what

Setting this up touches three things. You may need help from other people for two of them:

| Part | What it is | Who can do it |
| --- | --- | --- |
| **Brevo account** | The email-sending service | You (this guide) |
| **WordPress** | Connecting the website to Brevo | You + your **web developer** |
| **DNS records** | Proving the website's domain is allowed to send email | Whoever manages the **domain/DNS** (could be IT, a hosting company, or a domain provider) |

> ⚠️ **Important:** The website code that captures subscribers is already built by the developer. Your jobs are: (1) set up Brevo, (2) hand the developer two values, and (3) hand the DNS person a list of records. This guide produces all of those.

---

# PART 1 — Create your Brevo account

1. Open a web browser and go to **https://www.brevo.com**
2. Click the **Sign up free** button (top-right corner).
3. Enter a work email address and a password you'll remember. Click **Sign up**.
4. Brevo sends you a confirmation email. Open your inbox, find the email from Brevo, and click the **confirmation link** inside it.
5. Back in Brevo, answer the short setup questions (company name, your role, etc.). If asked to pick a plan, choose the **Free** plan. *(No credit card needed.)*
6. You'll land on the **Brevo Dashboard** — the home screen. ✅ Account done.

> 💡 **Bookmark** this page in your browser now so it's always one click away.

---

# PART 2 — Create your subscriber list

A "list" is the group of people your newsletters go to. You need one before anything else.

1. In the left-hand menu, click **Contacts**.
2. Click the **Lists** tab near the top.
3. Click the **Add a list** button.
4. Name it `Newsletter Subscribers`. (If it asks for a folder, the default "My first folder" is fine.)
5. Click **Create list** / **Save**.

### 📋 Write down your List ID
- After creating it, look at the list — there is a **number** shown next to or under the list name. This is the **List ID** (for example, `2`).
- You can also see it in the web address bar: when you click the list, the URL contains something like `/lists/2/` — the number is the ID.
- **Write this number down.** The developer needs it. ✏️ **My List ID: ____**

---

# PART 3 — Get your API key

An "API key" is a long secret password that lets the website talk to Brevo.

1. In the top-right corner, click your **account name / company name**.
2. In the dropdown, click **SMTP & API**.
3. Click the **API Keys** tab.
4. Click **Generate a new API key**.
5. Give it a name so you recognize it later, e.g. `website`. Click **Generate**.
6. Brevo shows you the key **once** — it's a long string starting with `xkeysib-`.
7. **Copy it immediately and paste it somewhere safe** (a password manager or a secure note).

> ⚠️ **You only get to see the full key one time.** If you close the window without copying it, you can't get it back — you'd just generate a new one (which is fine and free).

✏️ **My API key:** `xkeysib-________________________________`

---

# PART 4 — Give the developer the two values (WordPress side)

The website needs your **API key** and **List ID** to send subscribers into Brevo. These get added to a secure website settings file — **a developer does this** (it's a small, 2-minute job for them).

**Send your developer this message:**

> "Please add these two Brevo values to the site's `wp-config.php` on the **live/hosted** server:
> - `NOYONA_BREVO_API_KEY` = (the xkeysib-… key)
> - `NOYONA_BREVO_LIST_ID` = (the list number)
> The Brevo integration code is already in the theme (`inc/brevo.php`); it just needs these two constants set on the live site."

> 💡 **Why a developer:** this file holds website secrets and isn't editable from the normal WordPress screens. Once they add these two values, the subscribe box and the checkout "subscribe" checkbox will automatically add people to your Brevo list.

✅ When the developer confirms it's done, **test it**: go to the website, enter an email in the **Subscribe** box, then check **Brevo → Contacts → your list** — the email should appear within seconds.

---

# PART 5 — Do you need the Brevo WordPress plugin? (No)

**For newsletter marketing, you do NOT need the Brevo WordPress plugin.** The newsletter system works end-to-end without it:

- **Capturing subscribers** → handled by the website's own code (Part 4), which sends signups straight to your Brevo list.
- **Creating & sending newsletters** → done on the Brevo website (brevo.com), not inside WordPress.

So nothing about newsletters requires the plugin.

### What about the Brevo plugin that's already installed?

The site has the Brevo plugin (`mailin`) installed from earlier. Its main purpose would be to route the website's **automatic/transactional emails** (order receipts, password resets) through Brevo. **We are deliberately NOT using it for that** — those emails go through **Google Workspace** instead (see **`TRANSACTIONAL-EMAIL-SETUP-GUIDE.md`**).

> ⚠️ **Do NOT turn on the Brevo plugin's "Transactional emails" option.**
> Brevo's free plan is only **300 emails/day**, and that limit is **shared** across everything sent through Brevo. Routing order receipts through Brevo could exhaust the daily quota and cause newsletters — or worse, customers' order receipts — to silently fail. Transactional email is sent through Google Workspace precisely to avoid this.

### Recommendation

You can safely **deactivate** the Brevo plugin — it isn't needed for newsletters. (Leaving it installed with transactional **OFF** is also fine; it just sits unused.) If unsure, ask your developer.

> 💡 **Plain-English summary of how it all fits:**
> - **Website code (Part 4)** → puts *newsletter subscribers* into your Brevo list.
> - **Brevo website (brevo.com)** → where you *create and send* newsletters.
> - **Google Workspace** (separate guide) → sends the website's *automatic emails* (receipts).
> - **Brevo plugin** → not needed; do not enable its transactional option.

---

# PART 6 — Authenticate your domain (the DNS step)

This is the **most important step for getting newsletters into inboxes instead of spam.** It proves to Gmail/Outlook that your newsletters genuinely come from your domain.

> 🔑 **You will likely need to hand this to whoever manages your domain's DNS** (IT, hosting company, or domain registrar). Part 6 produces an exact list of records for them. Your job is to generate the list in Brevo and pass it along.

## 6A — Generate the records in Brevo

1. In Brevo, click your **account name** (top-right) → **Senders, Domains & Dedicated IPs**.
2. Click the **Domains** tab.
3. Click **Add a domain**.
4. Type your website's domain **without** `www` or `http` — just `yourdomain.com`. Click **Save**.
5. Brevo now shows a list of **DNS records** to add. There are usually **four kinds**:

| # | What Brevo calls it | Record type | Purpose |
| --- | --- | --- | --- |
| 1 | Brevo code | TXT | Proves you own the domain |
| 2 | DKIM (often 2 records) | TXT or CNAME | Cryptographically signs your email |
| 3 | SPF | TXT | Lists who's allowed to send for you |
| 4 | DMARC | TXT | Policy + reporting |

6. **Keep this Brevo screen open.** You'll copy the exact values from here. *(Brevo generates values unique to your domain — always copy from your own Brevo screen, never from an example.)*

## 6B — Send the records to your DNS person

Copy **each record exactly** (Name + Type + Value) and send them to whoever manages the DNS, **with these three rules included** (they prevent the most common mistakes):

> **Rule 1 — SPF must be MERGED, not duplicated.**
> The domain already has an SPF record (a TXT record starting with `v=spf1`, used by Google Workspace email). Do **not** add a second one. Instead, **edit the existing** SPF record to add Brevo's part. The result should look like:
> ```
> v=spf1 include:_spf.google.com include:spf.brevo.com ~all
> ```
> Two separate `v=spf1` records will break email for the whole domain.

> **Rule 2 — Add the other records as new, separate records.**
> The Brevo code (TXT), DKIM records, and DMARC (TXT) are each brand-new records. A domain can have many TXT records — that's normal. Only SPF has the "one record" rule.

> **Rule 3 — If the DNS is on Cloudflare, set DKIM records to "DNS only" (grey cloud).**
> For any DKIM record entered as a **CNAME**, the Cloudflare proxy must be **OFF** (grey cloud icon, "DNS only"). If it's proxied (orange cloud), DKIM verification fails.

### Copy-paste template to send your DNS person

```
Hi, please add the following DNS records for [yourdomain.com] to authenticate
our email sender (Brevo). Notes:

1) SPF: do NOT create a new SPF record. EDIT our existing "v=spf1 ..." TXT
   record to ALSO include Brevo, so it reads:
   v=spf1 include:_spf.google.com include:spf.brevo.com ~all
   (keep any other includes we already have)

2) Add these as NEW records exactly as shown:

   [paste the Brevo code TXT record here — Name + Value]

   [paste DKIM record(s) here — Name + Type + Value]

   [paste DMARC TXT record here — Name + Value]

3) If we're on Cloudflare, set any DKIM CNAME records to "DNS only"
   (grey cloud), NOT proxied.

Please let me know once added. Thank you!
```

## 6C — Verify in Brevo

1. After the DNS person confirms the records are added, go back to Brevo's **Domains** tab.
2. Click **Authenticate** / **Verify** on your domain.
3. DNS changes can take anywhere from a few **minutes to a few hours** to take effect. If it doesn't verify the first time, **wait and click verify again** — this is normal, not an error.
4. ✅ Success = the domain shows **Authenticated** with green checkmarks on the Brevo code, DKIM, SPF, and DMARC.

> ⚠️ **Leave these DNS records in place forever.** They are not temporary setup steps — they are how your email keeps working. Removing them later will send your newsletters back to spam.

---

# PART 7 — Create your "From" sender address

This is the name and address your newsletters appear to come from.

1. In Brevo → click your **account name** → **Senders, Domains & Dedicated IPs** → **Senders** tab.
2. Click **Add a sender**.
3. **Sender name:** what people see, e.g. `Noyona`.
4. **Sender email:** use an address on your authenticated domain, e.g. `newsletter@yourdomain.com`.
   - ⚠️ This email address must **actually exist** (so it can receive Brevo's confirmation message). If it doesn't exist yet, ask whoever manages your company email (Google Workspace) to create it or set it as an alias.
5. Brevo emails a **confirmation link** to that address. Open that inbox and click the link.
6. ✅ The sender now shows as verified. Because your domain is authenticated (Part 6), this sender is fully trusted.

---

# PART 8 — Prove it lands in the inbox (final test)

Before sending to real customers, confirm everything works:

1. In Brevo, create a quick test campaign (see `BREVO-ADMIN-GUIDE.md` Part on campaigns) **or** use **Send a test email**.
2. Set the **From** to `newsletter@yourdomain.com`.
3. Send it to a **Gmail** address you control.
4. Open the email in Gmail → click the **three-dot menu** (top-right of the email) → **Show original**.
5. Look for these three lines — all should say **PASS**:
   - **SPF: PASS**
   - **DKIM: PASS**
   - **DMARC: PASS**
6. The email should arrive in the **Inbox**, not Spam.

✅ If all three say PASS and it's in the inbox — **you are fully set up and ready to send real newsletters.**

---

# Setup checklist

- [ ] Brevo account created and confirmed
- [ ] "Newsletter Subscribers" list created — **List ID written down**
- [ ] API key generated and **saved safely**
- [ ] Developer added API key + List ID to the live website (Part 4) — **subscribe box test passes**
- [ ] Confirmed the Brevo plugin is NOT needed; its transactional option left OFF (Part 5)
- [ ] Domain authentication records given to DNS person (Part 6)
- [ ] Domain shows **Authenticated** (green) in Brevo
- [ ] Sender `newsletter@yourdomain.com` created and confirmed (Part 7)
- [ ] Test email shows **SPF/DKIM/DMARC = PASS** and lands in inbox (Part 8)

---

# Things to remember (limits & rules)

- **Free plan = 300 emails per day.** If your list is bigger, send in batches across multiple days, or upgrade.
- **Never delete the DNS records** — your email depends on them permanently.
- **Only email people who agreed to it.** Don't import bought/random lists.
- **Keep the unsubscribe link** in every newsletter (Brevo adds it automatically — leave it in; it's the law).

---

*If a button or screen looks different from this guide, Brevo may have updated its layout — the names might move, but the steps are the same. When stuck, contact your developer or IT.*
