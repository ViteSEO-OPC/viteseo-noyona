# Refunds: How They Work on Noyona

**Project:** Noyona (WooCommerce, child theme `viteseo-noyona`)
**Payment gateway:** PayMongo (QR Ph, GCash, Maya)
**Customer-facing policy page:** `/refund-policy/` (template `templates/page-refund-policy.html`)

---

## 1. The reality (read this first)

> **WooCommerce on this site cannot move money. It only records that a refund happened.**
> The actual money refund is done in the **PayMongo Dashboard**, by hand.

Every refund on this site has **two parts**, and both are manual:

1. **WooCommerce** — record the refund (status, note, totals, stock).
2. **PayMongo Dashboard** — actually return the money to the customer.

Both must be done. A refund is **not complete** until both are done.

---

## 2. Current state

- **No custom refund logic** in the theme or in `noyona-order-tracking`.
- **WooCommerce side:** the only available action is **"Refund manually"** — a record-only refund. No automatic refund. WooCommerce does **not** talk to PayMongo.
- **PayMongo side:** the actual money refund is issued from `https://dashboard.paymongo.com` → **Payments**.
- **Customer side:** My Account already groups `cancelled` and `refunded` orders under a **"Cancel / Refund"** tab (see `functions.php`, `cancel-refund` filter), so once an order is set to `refunded` in WC, the customer can see it.

---

## 3. Who does what

### WooCommerce does

- Creates a refund record on the order.
- Adds an order note with the refund amount and reason.
- Updates the order status to `refunded` (full refund) or leaves it unchanged (partial).
- Adjusts the order totals.
- Restocks items if the **"Restock refunded items"** checkbox is ticked.

### WooCommerce does **NOT** do

- Move any money.
- Talk to PayMongo.
- Notify the customer's bank / e-wallet.

### PayMongo does

- Holds the original payment.
- Returns the money to the customer's source account (the bank / e-wallet that paid via QR Ph / GCash / Maya).
- Is the **only** authoritative record of whether money was actually returned.

PayMongo Dashboard is accessed separately at `https://dashboard.paymongo.com`.

---

## 4. How a refund flows

There is **only one refund flow**, and it has two manual halves:

```
WooCommerce (record only)  +  PayMongo Dashboard (actual money refund)
```

Both halves are done by staff. They must match in amount and reason. If you only do the WC half, the customer never gets their money. If you only do the PayMongo half, the order in WC still looks unrefunded.

---

## 5. Staff workflow (the only correct way to refund)

Do every step. Do not skip the PayMongo half.

### Step 1 — Open the order in WooCommerce
WP Admin → **WooCommerce → Orders** → click the order.

### Step 2 — Click "Refund" (manual refund — the only option)
In the order's line-item area, click **"Refund"**, then click **"Refund manually"**.

- **Full refund:** set every line's refund quantity to the full ordered quantity.
- **Partial refund:** set quantity / amount only on the affected line(s).
- Tick **"Restock refunded items"** if the goods never shipped or were returned in sellable condition.

### Step 3 — Add a clear reason / note
Type a short reason (e.g. *"Customer cancelled before shipping"*, *"Damaged on arrival, item returned"*). This becomes a permanent order note.

### Step 4 — Open the PayMongo Dashboard
Go to `https://dashboard.paymongo.com` and log in.

### Step 5 — Go to Payments
In the left-hand navigation, click **Payments**.

### Step 6 — Find the transaction
Search by:
- The customer's email, OR
- The amount, OR
- The PayMongo payment ID (if it was saved in the WC order notes).

Confirm the date and amount match the WooCommerce order before going further.

### Step 7 — Click "Refund" (if available)
On the payment's detail view, click **Refund**. Choose **full** or **partial** to match what was recorded in WooCommerce in Step 2.

If the **Refund** option is **not available** (e.g. transaction too old, or the rail doesn't allow it), do not proceed silently — see §7 (Edge cases).

### Step 8 — Confirm the refund
Confirm the amount in PayMongo. Submit. **Note the refund reference / ID** PayMongo gives you.

### Step 9 — Return to WooCommerce and document it
Add a **private order note** with:
- The PayMongo refund reference / ID.
- The date the refund was submitted.
- Who processed it.

Example: *"Refunded ₱500.00 in PayMongo Dashboard — ref: rfd_xxxxxxxx — processed by [name] on [date]."*

This is how anyone reading the order later can verify the money actually moved.

---

## 6. Critical warnings

> **The "Refunded" status in WooCommerce DOES NOT mean money was returned.**
> It only means a staff member recorded a refund in WC. Without the matching PayMongo step, the customer's money is still with us.

- **Always verify in PayMongo.** The PayMongo Dashboard is the only authoritative source for whether the customer actually got their money back.
- **Only PayMongo can confirm an actual refund.** WooCommerce, the order email, and the customer's My Account page all reflect what staff *typed*, not what was actually moved.
- **Never tell a customer "your refund is processed" based on the WC status alone.** Confirm it in PayMongo first.
- **Always record the PayMongo refund reference in the WC order note** (Step 9). Without it, there is no audit trail.

---

## 7. Edge cases and pitfalls

| Situation | What to do |
|-----------|------------|
| Order is still `pending` / `on-hold` (payment never completed) | Do **not** refund — there's nothing to refund. **Cancel** the order in WooCommerce. Do not touch PayMongo. |
| PayMongo Dashboard "Refund" option is greyed out / unavailable | Likely out of refund window or the rail doesn't allow it. Contact PayMongo support with the payment ID. **Do not** mark the WC order as refunded yet — leave a private note explaining the situation. |
| Customer paid twice (duplicate orders) | Refund the duplicate in **both** WooCommerce and PayMongo. Add a note linking the two orders. |
| Customer asks for the refund to a different account | Not possible. PayMongo always returns funds to the original source account. If that source account is closed, escalate to PayMongo support. |
| Refund needs to exceed the original captured amount | Not possible. Refund the maximum in PayMongo, and handle any goodwill top-up outside WooCommerce + PayMongo. |
| Order fully refunded but stock wasn't restocked | Adjust the product's stock manually on the product edit screen. WC won't retroactively restock. |
| Customer says "I never got the money" after 14 business days | Pull the PayMongo refund reference from the order notes. Send it to PayMongo support and ask the customer to give the same reference to their bank / e-wallet. |
| Refund was processed in PayMongo but staff forgot to update WooCommerce | Go back to the WC order, do Steps 2–3 for the same amount, and add a note pointing to the existing PayMongo reference. |

---

## 8. What the customer sees

- **Full refund:** The order moves to the **"Cancel / Refund"** tab on My Account → Orders. The customer also gets the default **"Refunded order"** email from WooCommerce.
- **Partial refund:** The order stays under its original status tab. The refund line shows in the order's totals breakdown. **No automatic email** is sent for partial refunds — message the customer manually if they need to be told.
- **Money:** Returns to the customer's original payment source (their bank / GCash / Maya). The published policy quotes **7–14 business days**, which is the safe outer bound.

---

## 9. Refunds and the order tracking statuses

The custom statuses from `noyona-order-tracking` (`to-ship`, `to-receive`, `at-hub`, `rider-assigned`, `out-for-delivery`, `shipped`, `in-transit`) are **fulfillment** statuses. They are independent of refunds. A refund can be issued from any of them. Once the order is fully refunded, its status flips to `refunded` and it leaves the tracking workflow.

If a refund happens **after** shipping (return flow):
1. Wait for the package to come back / be confirmed lost.
2. Then process Steps 1–9 above.
3. Restock only if the returned goods are sellable.

---

## 10. What is automatic vs manual

| Step | Done by | Automatic? |
|------|---------|------------|
| Recording the refund on the WC order | Staff click in WooCommerce | Manual click, automatic record |
| Order status change to `refunded` (full refund only) | WooCommerce | Automatic |
| Order note for the refund amount + reason | WooCommerce | Automatic |
| Restocking refunded items | WooCommerce | Automatic *if* checkbox ticked |
| **"Refunded order" email** (full refund only) | WooCommerce | Automatic |
| Notifying the customer of a **partial** refund | Staff | **Manual** |
| **Actually returning the money to the customer** | Staff in PayMongo Dashboard | **Manual — the critical step** |
| Recording the PayMongo refund reference into the WC order note | Staff | **Manual** |
| Customer-facing tab grouping (`Cancel / Refund`) | Theme (`functions.php`) | Already in place |

There is **no** automated path. WooCommerce does not call PayMongo for any refund.

---

## 11. Things we are deliberately *not* doing

- **No customer-initiated refund button.** Refunds are admin-only.
- **No automatic refund on `cancelled` status.** Cancelling an order does not refund it. A cancelled-but-paid order still needs the full Step 1–9 workflow.
- **No partial-refund customer email.** Default WC behavior. Send a manual message if needed.
- **No reporting dashboard for refunds.** We rely on WC's built-in **Reports → Orders** plus the PayMongo Dashboard.

---

## 12. Future possibility (not in scope today)

Automatic refunds — one-click in WooCommerce that also moves the money — are **not implemented** and **not currently supported** by our setup. If this ever becomes available in the future, this doc must be revised before staff are told to use it. Until then, the two-step manual workflow in §5 is the **only** correct process.
