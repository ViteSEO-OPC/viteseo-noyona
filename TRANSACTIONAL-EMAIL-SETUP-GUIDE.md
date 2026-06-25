# Transactional Email Setup Guide (Google Workspace)

This guide makes the website's **automatic emails** — order confirmations, "your order shipped," password resets, account emails — arrive reliably in customers' inboxes instead of the spam folder.

It's written in plain English, step by step. A few steps need help from your **developer** or your **Google Workspace admin** — those are clearly labelled with a 🔑, and we give you a ready-to-send message to forward to them. You won't have to do anything technical yourself.

> 🧭 **How this fits with the other guides:**
> - **Newsletters** (the "20% off!" type emails) → sent through **Brevo** → see `BREVO-COMPLETE-SETUP-GUIDE.md`
> - **Automatic emails** (order receipts, etc.) → sent through **Google Workspace** → *this guide*

---

## First, the simple idea behind this

The website sends two very different kinds of email, and we use a different service for each — like using a **delivery van** for one job and a **motorbike** for another:

| Kind of email | Example | We send it with |
| --- | --- | --- |
| **Newsletters** (lots at once) | "Weekend sale — 20% off" | **Brevo** |
| **Automatic emails** (a few at a time) | "Thanks for your order!" | **Google Workspace** |

**Why split them?** Brevo's free plan only allows **300 emails a day**. If newsletters and order receipts shared that limit, one big newsletter could "use up" the day's allowance and a customer's order receipt might silently fail to send. We never want a customer to miss their receipt — so receipts go through Google Workspace (which allows about **2,000 a day** and is built exactly for this).

> ⚠️ **Important:** Don't try to send newsletters through Google Workspace. Google only allows *automatic/personal* email there, not bulk marketing. Newsletters = Brevo. Receipts = Workspace. Keep them in their own lanes and everything stays happy.

---

## Who does what (so nothing surprises you)

| Step | Who does it |
| --- | --- |
| Create the email address receipts come from | 🔑 **Google Workspace admin** (you forward them Part 1) |
| Install the helper plugin | ✅ **You** (Part 2) |
| Connect WordPress to Google | 🔑 **You + developer** (Part 3 — we give a message to forward) |
| Set the "from" address in the store settings | ✅ **You** (Part 4) |
| Test it | ✅ **You** (Part 5) |

> Don't have Google Workspace admin access yet? You can't finish this guide until you do — request it first. In the meantime, leave the automatic emails as they are, and **don't** turn on Brevo's transactional option.

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
> - Or, if simpler: **App Password** over SMTP (`smtp.gmail.com`, port 465, SSL), authenticating as our real Workspace user and sending 'as' the `orders@` alias.
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

# Good to know (limits & rules)

- **Google Workspace allows about 2,000 emails a day** — far more than a normal store's receipts will ever need.
- **Keep newsletters out of here.** Workspace is for automatic emails only; sending bulk marketing through it breaks Google's rules.
- **Brevo's 300/day stays fully free for newsletters** now — the two systems never compete.
- **You get a record of every email.** FluentSMTP keeps an **Email Logs** list, so if a customer says "I didn't get my receipt," you can check whether it actually went out.

---

# If something goes wrong

**"The test email failed."**
→ The Google connection (Part 3) likely needs attention. Send your developer the error message FluentSMTP shows.

**"The email arrived but from the wrong address."**
→ Tell your developer the `orders@` alias needs to be added as a **"Send mail as"** address in the Google account. (Quick fix on their side.)

**"It still goes to spam."**
→ Ask your developer to confirm **Workspace DKIM** is turned on (Google Admin console → Apps → Gmail → Authenticate email). Without it, mail isn't fully signed.

**"A customer says they didn't get their receipt."**
→ Check **Settings → FluentSMTP → Email Logs** to see if it was sent. If it shows as sent, ask the customer to check their spam folder and confirm their email address was typed correctly.

---

# Simple checklist

- [ ] Workspace admin created the `orders@yourdomain.com` alias (Part 1)
- [ ] FluentSMTP plugin installed and activated (Part 2)
- [ ] Developer connected FluentSMTP to Google (Part 3)
- [ ] Store "From" address set to `orders@yourdomain.com`, not gmail (Part 4)
- [ ] Test email lands in inbox and shows **SPF/DKIM/DMARC = PASS** (Part 5)
- [ ] A real test order's receipt arrived correctly (Part 5)

---

*If a screen looks a little different from this guide, Google or the plugin may have updated their layout — the names might move, but the steps are the same. Anything with a 🔑 is meant for your developer or Workspace admin, so don't worry if it looks technical — just forward it.*
