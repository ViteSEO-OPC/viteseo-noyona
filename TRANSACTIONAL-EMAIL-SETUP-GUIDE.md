# Transactional Email — How It Works & Your Options

This guide is about the website's **automatic emails** — order confirmations, "your order shipped," password resets, and account emails. These are the emails the website sends **by itself**, one at a time, whenever a customer does something.

**The good news: these emails already work right now.** When a customer places an order, the receipt does go out. So nothing is broken, and you don't *have* to change anything today.

**But there's a recommended upgrade**, and **the choice is yours**. This guide lays out both options in plain English — what you have now, what you could move to, and the honest pros and cons of each — so you (the owner/admin) can decide. A few steps need help from your **developer** or **Google Workspace admin**; those are clearly marked with a 🔑 and come with a ready-to-send message you can just forward. You won't have to do anything technical yourself.

> 🧭 **How this fits with the other guides:**
> - **Newsletters** (the "20% off!" type emails sent to many people at once) → sent through **Brevo** → see `BREVO-COMPLETE-SETUP-GUIDE.md`
> - **Automatic emails** (order receipts, password resets, etc.) → *this guide*

---

## The short version — your two options

| | **Option A — Keep what you have now** | **Option B — Google Workspace (recommended)** |
| --- | --- | --- |
| **What it is** | The website's own server sends the emails (the default setup) | The website hands emails to Google to send for you |
| **Setup needed** | ✅ None — it's already on | A one-time setup (this guide walks you through it) |
| **Cost** | Free | Free *if you already pay for Google Workspace*; otherwise it's the Workspace subscription you may already have |
| **Reliability** | ⚠️ Works, but a higher chance of landing in **spam** | ✅ Strong, lands in the **inbox** reliably |
| **Looks professional** | ⚠️ Sometimes shows an odd "from" address | ✅ Comes cleanly from your own domain |
| **Record of what was sent** | ❌ No easy log | ✅ Full log of every email |
| **Best for** | Just launching, low order volume, or you want zero setup today | A real, growing store that depends on customers getting their receipts |

**You don't have to decide this minute.** You can keep Option A and switch to Option B later at any time. Read the next two sections, then pick.

---

## Option A — Keep your current setup (what "it already works" really means)

Right now, the website sends its automatic emails using the **website server's own built-in email**. There's nothing to install and nothing to pay for — it's simply on by default.

**What's good about it:**
- ✅ It already works — receipts and password resets do go out.
- ✅ Zero setup, zero cost.
- ✅ Perfectly fine for testing, a soft launch, or a store with only a few orders.

**The limitations — what to honestly expect if you stay on this:**
- ⚠️ **Spam risk.** Because these emails aren't "verified" the way big providers like Gmail and Outlook prefer, they have a **higher chance of landing in the customer's spam/junk folder** — or occasionally not arriving at all. The customer may think you never sent a receipt.
- ⚠️ **No "proof of identity."** Professional email uses three invisible stamps (called SPF, DKIM, and DMARC) that tell Gmail "this really is from this store." The current setup usually **can't provide all three**, so some inboxes treat the email with suspicion.
- ⚠️ **The "from" address can look off.** Emails may appear to come from something like `wordpress@yourserver` instead of a clean `orders@yourdomain.com`, which looks less trustworthy to customers.
- ⚠️ **The hosting company may limit it.** Many web hosts quietly cap how many emails a site can send per hour or day, and may **silently drop** extras during busy periods (like a sale). You wouldn't get a warning.
- ⚠️ **No record.** There's no easy log, so if a customer says "I never got my receipt," it's hard to check whether it actually went out.
- ⚠️ **It can break quietly.** If the host changes servers or tightens its email rules, sending can stop working without any obvious sign.

**Bottom line:** Option A is genuinely fine for getting started or for low volume. But for a store that relies on customers reliably receiving receipts and reset links, the spam risk is the main thing to weigh.

> ✅ **If you choose to stay on Option A**, you don't need to do anything — just skim the short **"If you stay on your current setup"** section near the bottom for a couple of things to keep an eye on.

---

## Option B — Google Workspace (our recommendation)

If you want customers to **reliably get their emails in the inbox**, we recommend sending the automatic emails through **Google Workspace** (the paid Google email service many businesses already use for their `@yourdomain.com` addresses).

**Why it's better:**
- ✅ **Lands in the inbox.** Google is one of the most trusted senders in the world, so its emails rarely get flagged as spam.
- ✅ **Properly verified.** It provides all three "identity stamps" (SPF, DKIM, DMARC), so other inboxes trust it.
- ✅ **Clean, professional "from" address** like `orders@yourdomain.com`.
- ✅ **Plenty of room.** Google Workspace allows about **2,000 emails a day** — far more than a normal store's receipts will ever need.
- ✅ **A full record.** You get a log of every email, so you can always check whether a receipt was sent.

> 💡 **Do you already pay for Google Workspace?** Many businesses already use it for staff email (`name@yourdomain.com`). If so, using it for the website's automatic emails costs **nothing extra**. If you don't have Workspace, you'd need a subscription — that's part of what you're deciding.

**The rest of this guide (Parts 1–5) is the step-by-step setup for Option B.** Only follow it if you decide to switch. If you're staying on Option A for now, you can stop reading here and come back whenever you're ready.

> ⚠️ **One rule for either option:** Don't send *newsletters* through Google Workspace. Google only allows *automatic/personal* email there, not bulk marketing. Newsletters = Brevo. Receipts = your chosen option here. Keep them in their own lanes and everything stays happy.

---

# ⬇️ The steps below are ONLY for Option B (switching to Google Workspace)

*If you're keeping your current setup (Option A), you can skip everything below.*

---

## Who does what (so nothing surprises you)

| Step | Who does it |
| --- | --- |
| Create the email address receipts come from | 🔑 **Google Workspace admin** (you forward them Part 1) |
| Install the helper plugin | ✅ **You** (Part 2) |
| Connect WordPress to Google | 🔑 **You + developer** (Part 3 — we give a message to forward) |
| Set the "from" address in the store settings | ✅ **You** (Part 4) |
| Test it | ✅ **You** (Part 5) |

> Don't have Google Workspace admin access yet? You can't finish Option B until you do — request it first. In the meantime, your current setup (Option A) keeps running exactly as it is, so nothing stops working while you wait.

---

# PART 1 — Create the "from" address (the Workspace admin does this)

Receipts need a proper address on your own domain to come from — for example `orders@yourdomain.com`. The simplest, free way is an **alias** (an extra address that drops into an inbox you already use).

🔑 **You probably can't do this yourself** unless you're the Google Workspace admin. So just **forward the message below** to whoever manages your company email:

> **Copy-paste message to your Workspace admin:**
>
> "Hi! Could you please create an email **alias** `orders@ourdomain.com` on my existing user account (so replies land in my normal inbox)? It's free and just needs to be added in the Google Admin console under Directory → Users → [my account] → Add alternate emails. We'll use it as the 'from' address for our website's order emails. Thank you!"

*(You can use `no-reply@` instead of `orders@` if you prefer — but `orders@` is friendlier and lets customers reply.)*

✅ **When they confirm it's done, continue to Part 2.**

---

# PART 2 — Install the helper plugin (you do this — easy!)

This little plugin is what tells WordPress "send emails through Google Workspace." It's free.

1. Log in to the website admin: go to **your-website.com/wp-admin** and sign in.
2. In the left menu, hover **Plugins**, then click **Add New**.
3. In the search box (top right), type **FluentSMTP**.
4. Find **FluentSMTP** in the results, click **Install Now**, then click **Activate**.
5. That's it — a FluentSMTP setup screen appears. Leave it open for the next part.

> 💡 If you ever can't find its settings later, they're under **Settings → FluentSMTP** in the left menu.

---

# PART 3 — Connect WordPress to Google (the developer does this)

This is the one genuinely technical step — connecting the plugin to your Google account securely. **Don't try to muddle through it yourself**; hand it to your developer with the message below. It takes them about 15 minutes.

🔑 **Copy-paste message to your developer:**

> "Hi! I've installed the **FluentSMTP** plugin on the site and we have a Google Workspace alias `orders@ourdomain.com` ready. Could you connect FluentSMTP to send our WooCommerce/WordPress transactional emails through Google Workspace?
>
> - Preferred: the **Google OAuth (Gmail API)** connection method for reliability.
> - Or, if simpler: **App Password** over SMTP (`smtp.gmail.com`, port 465, SSL), authenticating as our real Workspace user and sending 'as' the `orders@` alias. *(Note: an App Password requires 2-Step Verification to be turned on for that Google account first.)*
> - Set the **From** address to `orders@ourdomain.com` and **From name** to our store name.
> - Please also make sure **Workspace DKIM** is turned on in the Google Admin console so our mail is authenticated.
>
> Then send a test email and confirm it shows SPF/DKIM/DMARC = PASS. Thanks!"

> 💡 **What's happening, in plain English:** the developer is giving the plugin secure permission to send email on your behalf through Google. Once done, you never touch it again.

✅ **When the developer confirms it's connected, continue to Part 4.**

---

# PART 4 — Set the store's "from" address (you do this — easy!)

This makes order emails come from your domain instead of some random address.

1. In the website admin, left menu → hover **WooCommerce**, click **Settings**.
2. Click the **Emails** tab along the top.
3. Scroll all the way to the bottom to the section called **"Email sender options."**
4. Set these two boxes:
   - **"From" name:** your store name (for example, `Noyona`).
   - **"From" address:** `orders@yourdomain.com` (the exact address from Part 1).
5. Click the blue **Save changes** button.

> 🚫 **Make sure the "From" address is NOT a `@gmail.com` address.** It must be your own domain (`@yourdomain.com`), or emails can look fake and go to spam.

---

# PART 5 — Test that it works (you do this)

Let's make sure receipts actually arrive.

### Quick test
1. Left menu → **Settings → FluentSMTP** → click the **Email Test** tab.
2. In "Send To," type a **Gmail address you can open** (your own is fine).
3. Click **Send Test Email**. You should see a green "success" message.
4. Open your Gmail and check the email arrived in the **inbox** (not spam).

### Prove it's properly authenticated (optional but reassuring)
1. Open the test email in Gmail.
2. Click the **three dots** (⋮) at the top-right of the email → click **Show original**.
3. Look for these three lines — all should say **PASS**:
   - **SPF: PASS**
   - **DKIM: PASS**
   - **DMARC: PASS**

### Real-world test
1. On the website, add a product to the cart and go through checkout.
   - Tip: turn on **Cash on Delivery** (WooCommerce → Settings → Payments) so you don't need to actually pay.
2. Complete the order.
3. Check that the **order confirmation email** arrives in the inbox, from `orders@yourdomain.com`. 🎉

✅ **Lands in the inbox + PASS = you're done. Order emails are now reliable.**

---

# Good to know (limits & rules for Option B)

- **Google Workspace allows about 2,000 emails a day** — far more than a normal store's receipts will ever need.
- **Keep newsletters out of here.** Workspace is for automatic emails only; sending bulk marketing through it breaks Google's rules.
- **Newsletters stay on Brevo.** The two systems never compete — receipts go through Google, newsletters through Brevo.
- **You get a record of every email.** FluentSMTP keeps an **Email Logs** list, so if a customer says "I didn't get my receipt," you can check whether it actually went out.

---

# If you stay on your current setup (Option A)

If you decide **not** to switch to Google Workspace for now, that's a valid choice — just keep these in mind:

- 👀 **Watch your spam folder.** Every so often, place a small test order to a Gmail/Outlook address you control and confirm the receipt lands in the **inbox**, not spam.
- 🧾 **Spot-check around busy periods.** During a sale or a burst of orders, your host is most likely to rate-limit. If customers report missing receipts, that's the usual cause.
- ❓ **If a customer says they didn't get a receipt**, ask them to check spam first. There's no built-in log on this setup, so you're partly relying on the customer to check.
- 🔁 **You can upgrade any time.** The moment receipts-in-spam becomes a real problem, follow Parts 1–5 above to switch to Google Workspace. Nothing is lost by starting on Option A.

---

# If something goes wrong (Option B)

**"The test email failed."**
→ The Google connection (Part 3) likely needs attention. Send your developer the error message FluentSMTP shows.

**"The email arrived but from the wrong address."**
→ Tell your developer the `orders@` alias needs to be added as a **"Send mail as"** address in the Google account. (Quick fix on their side.)

**"It still goes to spam."**
→ Ask your developer to confirm **Workspace DKIM** is turned on (Google Admin console → Apps → Gmail → Authenticate email). Without it, mail isn't fully signed.

**"A customer says they didn't get their receipt."**
→ Check **Settings → FluentSMTP → Email Logs** to see if it was sent. If it shows as sent, ask the customer to check their spam folder and confirm their email address was typed correctly.

---

# Simple checklist (Option B — Google Workspace)

- [ ] Workspace admin created the `orders@yourdomain.com` alias (Part 1)
- [ ] FluentSMTP plugin installed and activated (Part 2)
- [ ] Developer connected FluentSMTP to Google (Part 3)
- [ ] Store "From" address set to `orders@yourdomain.com`, not gmail (Part 4)
- [ ] Test email lands in inbox and shows **SPF/DKIM/DMARC = PASS** (Part 5)
- [ ] A real test order's receipt arrived correctly (Part 5)

---

*If a screen looks a little different from this guide, Google or the plugin may have updated their layout — the names might move, but the steps are the same. Anything with a 🔑 is meant for your developer or Workspace admin, so don't worry if it looks technical — just forward it.*
