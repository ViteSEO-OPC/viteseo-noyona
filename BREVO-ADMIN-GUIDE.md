# Brevo Newsletter Guide for Admins (Everyday Use)

A plain-English, click-by-click reference for running newsletters with Brevo. **No technical knowledge needed.** Keep this open whenever you work in Brevo.

> 🧭 **Two guides exist. This is the EVERYDAY guide.**
> For the one-time setup (creating the account, DNS, etc.), see **`BREVO-COMPLETE-SETUP-GUIDE.md`**. This guide assumes setup is already done.

---

## Table of contents

1. [What Brevo is (the simple version)](#1-what-brevo-is-the-simple-version)
2. [Words you'll see a lot](#2-words-youll-see-a-lot)
3. [Logging in](#3-logging-in)
4. [A tour of the Brevo dashboard](#4-a-tour-of-the-brevo-dashboard)
5. [Understanding Contacts and Lists](#5-understanding-contacts-and-lists)
6. [How customers get onto your list](#6-how-customers-get-onto-your-list)
7. [Adding subscribers yourself](#7-adding-subscribers-yourself)
8. [The Brevo plugin in WordPress](#8-the-brevo-plugin-in-wordpress)
9. [Creating a newsletter — click by click](#9-creating-a-newsletter--click-by-click)
10. [Always send a test first](#10-always-send-a-test-first)
11. [Sending or scheduling](#11-sending-or-scheduling)
12. [Reading your results](#12-reading-your-results)
13. [Limits and rules](#13-limits-and-rules)
14. [Problems and fixes](#14-problems-and-fixes)
15. [Pre-send checklist (print this)](#15-pre-send-checklist-print-this)

---

## 1. What Brevo is (the simple version)

Brevo is the tool that **sends your newsletters**. Think of it as your post office:

- **You** write the newsletter and choose who gets it.
- **Brevo** delivers it to everyone's inbox.

Your website (WooCommerce) collects customer emails automatically. Those emails flow into Brevo. Then you use Brevo to design an email and send it to everyone at once.

You do everything in a web browser at **brevo.com** — nothing to install.

---

## 2. Words you'll see a lot

| Word | What it means in plain English |
| --- | --- |
| **Contact** | One person's email address (a customer or subscriber). |
| **List** | A group of contacts. You send newsletters *to a list*. Yours is called **"Newsletter Subscribers."** |
| **Campaign** | One newsletter you send. Each email blast = one "campaign." |
| **Sender** | The "from" name and address your newsletter appears to come from (e.g. *Noyona — newsletter@yourdomain.com*). |
| **Template** | A saved newsletter design you can reuse instead of starting over. |
| **Test email** | A practice send to yourself to check it looks right before sending to everyone. |
| **Open rate** | The % of people who opened your email. |
| **Click rate** | The % of people who clicked a link/button in your email. |

---

## 3. Logging in

1. Open a browser and go to **https://www.brevo.com**
2. Click **Log in** (top-right corner).
3. Enter the account email and password.
   - 🔑 Don't have them? Ask whoever set up the Brevo account.
4. You land on the **Dashboard** (the home screen).

> 💡 **Bookmark the page** so it's always one click away.

---

## 4. A tour of the Brevo dashboard

When you log in, the important things are on the **left-hand menu**:

| Menu item | What you use it for |
| --- | --- |
| **Campaigns** | Create and send newsletters. **(Your main area.)** |
| **Contacts** | See your subscribers and lists; add/import people. |
| **Statistics** | See how past newsletters performed. |
| **Templates** | Saved designs you can reuse. |
| **Your account name** (top-right) | Settings, API keys, **Senders, Domains** (set-up things). |

> 💡 For everyday newsletter work, you'll mostly live in **Campaigns** and **Contacts**.

---

## 5. Understanding Contacts and Lists

This is the one core idea — 30 seconds well spent:

- A **Contact** = one email address.
- A **List** = a folder of contacts.
- **You send newsletters to a List**, never to people one by one.

So you always need **a List that has people in it.** Yours is **"Newsletter Subscribers,"** and it fills up automatically from the website.

**To see your lists:** left menu → **Contacts** → **Lists** tab. Click a list to see who's in it.

---

## 6. How customers get onto your list

People are added **automatically** in three ways — you don't have to do anything:

### A) They tick the box at checkout
When a customer buys on the website, they can tick a **"Subscribe to our newsletter"** box at checkout. If they do, they're added to your Brevo list automatically (with their name).

### B) They use the Subscribe box on the website
The website has a newsletter **Subscribe** box (e.g. in the homepage strip). Anyone who enters their email there is added automatically.

### C) (Already set up) Website connection
The website is already wired to send these signups straight into Brevo. As long as it's working, your list grows on its own.

**To confirm it's working:** Contacts → Lists → open **Newsletter Subscribers** → you should see emails appearing over time. To test on demand, enter your own email in the website's Subscribe box, then refresh this list — you should appear within seconds.

> ❓ **Not seeing new subscribers?** See [Problems and fixes](#14-problems-and-fixes).

---

## 7. Adding subscribers yourself

Sometimes you collect emails offline (an event, a sign-up sheet). You can add them manually.

> ⚠️ **Only add people who agreed to receive emails from you.** Adding random or purchased emails gets your account flagged and sends everything to spam.

### Add one person
1. Left menu → **Contacts** → **Contacts** tab.
2. Click **Add a contact**.
3. Type their **email** (and name if you have it).
4. Under lists, tick **Newsletter Subscribers**.
5. Click **Save**.

### Add many at once (from a spreadsheet)
1. Make a spreadsheet with a column titled `EMAIL` (and optionally `FIRSTNAME`, `LASTNAME`). Save it as `.csv` or `.xlsx`.
2. **Contacts** → **Import contacts**.
3. Upload your file.
4. When asked, **match the columns** (Brevo usually does this automatically).
5. Choose the list: **Newsletter Subscribers**.
6. Click **Import**.

---

## 8. Do you use the Brevo WordPress plugin? (No)

For newsletters, you do **not** use the WordPress Brevo plugin at all:

- **Subscribers** are captured by the website automatically and sent to Brevo (built by your developer).
- **Newsletters** are created and sent at **brevo.com**, not in WordPress.

The website's **automatic emails** (order receipts, password resets) are handled separately through **Google Workspace** — *not* Brevo — so Brevo's 300/day stays fully available for newsletters.

> ⚠️ **Do not enable the Brevo plugin's "Transactional emails" option.** It would route order receipts through Brevo and eat into the 300/day newsletter limit. Those emails belong on Google Workspace.

> 💡 **Rule of thumb:** Do all newsletter work at **brevo.com**. You don't need to open the WordPress Brevo plugin for newsletters at all.

---

## 9. Creating a newsletter — click by click

This is the main thing you'll do. Follow in order.

### Step 1 — Start a new campaign
- Left menu → **Campaigns** → **Email** → click **Create an email campaign** (top-right button).

### Step 2 — Name it (internal only)
- Type a name just for your records, e.g. `June Sale Newsletter`. **Customers never see this name.**
- Click **Next step** / continue.

### Step 3 — Set the "From" and subject
- **From name:** what shows as the sender, e.g. `Noyona`.
- **From email:** choose `newsletter@yourdomain.com` from the dropdown. *(If it's not there, it wasn't set up — see the setup guide.)*
- **Subject line:** the headline people see in their inbox. Keep it short and clear.
  - ✅ Good: *"20% off this weekend only"*
  - ❌ Avoid: *"FREE!!! BUY NOW!!!"* (looks like spam)
- **Preview text** (optional): the small grey line shown after the subject in most inboxes.

### Step 4 — Choose who gets it (Recipients)
- Click **Recipients** / **Send to**.
- Tick your list: **Newsletter Subscribers**.
- Brevo shows how many people will receive it.

> ⚠️ **No list appears here?** Your list is empty or none is selected. Go to Contacts → Lists, make sure **Newsletter Subscribers** has people in it, then come back.

### Step 5 — Design the newsletter
- Click **Design this email** to open the **drag-and-drop editor**.
- Easiest path: pick a **ready-made template**, then replace the text and images with yours.
- To edit anything: **click a block** (text, image, button) and change it. Drag new blocks in from the right-hand panel.
- Make sure you include:
  - ✅ Your **logo** at the top
  - ✅ Clear text + a **button** (e.g. "Shop Now") linking to your website
  - ✅ An **unsubscribe link** at the bottom — *Brevo adds this automatically; do not remove it (it's legally required).*
- Tip: click **Preview** to see how it looks on desktop and mobile.
- Click **Save & Quit** when done.

> 💡 **Save time next month:** after building a design you like, save it as a **Template** (look for "Save as template"). Next time, just duplicate it and change the words.

### Step 6 — Send a test (don't skip — see Section 10)

### Step 7 — Send or schedule (see Section 11)

---

## 10. Always send a test first

**Never send to your whole list without testing.** A typo or broken image otherwise goes to everyone.

1. In the campaign, click **Send a test** (near the top, or in the Design step).
2. Enter **your own email** (and a colleague's if you like). Send it.
3. Open it in your inbox and check:
   - ✅ Subject line looks right?
   - ✅ Images showing?
   - ✅ Buttons/links go to the **correct** pages? (Click them!)
   - ✅ Looks OK **on your phone**?
4. Fix anything, then test again before the real send.

> 📌 **If the test lands in spam:** this means the domain email setup (DNS authentication) isn't finished or has a problem. **Stop and tell your developer/IT.** Don't do a real send until tests land in the **inbox** — see the setup guide, Part 8.

---

## 11. Sending or scheduling

When your test looks perfect:

### Send now
1. Click **Send** / **Schedule** (top-right).
2. Choose **Send now**.
3. Confirm. 🎉 Done — it goes out immediately.

### Schedule for later
1. Click **Send** / **Schedule** → choose **Schedule**.
2. Pick the **date and time**.
3. Confirm. Brevo sends it automatically at that time.

### ⚠️ If your list is bigger than 300 people
The free plan sends **300 emails per day**. If your list is larger:
- Brevo will only send to 300 that day, OR
- **Better:** split it — schedule part today, part tomorrow, etc., until everyone is covered.

---

## 12. Reading your results

After sending, see how it did: left menu → **Campaigns** → click the campaign name (or check **Statistics**).

| Number | What it tells you | What's normal |
| --- | --- | --- |
| **Sent** | How many emails went out. | — |
| **Open rate** | % who opened it. | 20–40% is healthy |
| **Click rate** | % who clicked a link/button. | 2–5% is typical |
| **Bounces** | Couldn't be delivered (bad/closed addresses). | Keep low |
| **Unsubscribes** | People who opted out. | A few is normal |

> 💡 Low opens → try a better **subject line** next time. Low clicks → make your **offer/buttons** clearer.

---

## 13. Limits and rules

- **300 emails per day** (free plan). Bigger list = send in batches across days, or upgrade.
- **Brevo branding** appears at the bottom of free-plan emails.
- **Always keep the unsubscribe link** (Brevo adds it — leave it in). It's the law.
- **Only email people who agreed.** No bought or scraped lists — it ruins delivery for everyone and can suspend the account.
- **Sending resets daily** — if you hit 300, the rest waits until tomorrow.

---

## 14. Problems and fixes

**"No list shows up when choosing recipients."**
→ Your list is empty or none selected. Contacts → Lists → make sure **Newsletter Subscribers** exists and has contacts, then retry.

**"My newsletter went to spam."**
→ Usually the domain email setup (DNS) isn't finished or has an issue. Tell your developer/IT. Also avoid spammy subject lines (ALL CAPS, lots of "!!!", words like "FREE FREE FREE").

**"I hit a sending limit."**
→ Free plan = 300/day. Schedule the rest for tomorrow, or upgrade.

**"A customer says they didn't get it."**
→ Ask them to check their spam folder. In the campaign, check the **Bounces** list to see if their address failed. Confirm they're on the list (Contacts → search their email).

**"New subscribers aren't appearing in Brevo."**
→ The website connection may be down. Ask your developer to confirm the Brevo settings on the live site are still in place. (Quick test: enter your own email in the website Subscribe box and see if it shows up.)

**"I already sent it and made a mistake."**
→ Sent emails can't be recalled. If serious, send a short follow-up correction. *(This is exactly why Section 10 — testing — matters.)*

---

## 15. Pre-send checklist (print this)

Before every send:

- [ ] Campaign has a clear **subject line** (not spammy)
- [ ] Correct **From name** and **From email** (`newsletter@yourdomain.com`)
- [ ] Correct **list** selected under Recipients (**Newsletter Subscribers**)
- [ ] Design checked — logo, text, images load, **buttons go to the right pages**
- [ ] **Unsubscribe link** present at the bottom
- [ ] **Test email sent to myself** — looks right on desktop **and** phone
- [ ] Test landed in **inbox**, not spam
- [ ] Within the **300/day limit** (or scheduled across days)
- [ ] Ready → **Send** or **Schedule**

---

*If a button or screen looks different from this guide, Brevo may have updated its layout — the names might move, but the steps stay the same. When in doubt, ask your developer or IT.*
