# Email Functions Audit

**Project:** Noyona WordPress + WooCommerce site  
**Scope checked:** `themes/child-noyona`, WooCommerce email classes, WP Mail SMTP, WP Mail Logger, Contact Form 7, and PayMongo payment gateway references  
**Date:** 2026-05-29

## Summary

The child theme has two custom functions that directly send email through `wp_mail()`:

- `noyona_handle_newsletter_subscribe()` in `inc/newsletter.php`
- `noyona_handle_contact_form()` in `inc/contact-form.php`

The customer account and order emails are not custom theme emails. They are handled by WordPress and WooCommerce core email classes. The theme triggers or customizes the surrounding flows, but WooCommerce owns the actual customer email generation for account creation, password reset, processing orders, completed orders, failed orders, cancelled orders, and invoices.

WP Mail Logger can confirm that WordPress generated an email and called `wp_mail()`. Actual inbox delivery still depends on WP Mail SMTP, the configured mail provider, DNS authentication, and recipient spam filtering.

## Summary Table

| Email flow | Source | Exists in codebase | Recipient | Works if | Key recommendation |
| --- | --- | --- | --- | --- | --- |
| Newsletter signup | Theme `noyona_handle_newsletter_subscribe()` | Yes | WordPress admin email | Nonce passes, optional reCAPTCHA passes, email is valid, and SMTP/mail transport works | Add rate limiting and store signups somewhere persistent |
| Custom contact form | Theme `noyona_handle_contact_form()` | Yes | WordPress admin email | Nonce, honeypot, optional reCAPTCHA, rate limit, validation, and SMTP/mail transport pass | Check `wp_mail()` return value and store submissions |
| Contact Form 7 form | Contact Form 7 plugin with theme captcha validation | Yes, via plugin | CF7 configured recipient | CF7 mail settings are valid, captcha passes, and SMTP/mail transport works | Verify CF7 recipient, From, Reply-To, and body settings |
| Account creation | WooCommerce `WC_Email_Customer_New_Account` | Yes | New customer account email | WooCommerce is active, email is enabled, and registration uses `wc_create_new_customer()` | Confirm WooCommerce New account email is enabled |
| Password reset | WooCommerce `WC_Email_Customer_Reset_Password` | Yes | User account email | Reset request is valid, reset email is enabled, and SMTP/mail transport works | Test the reset link end to end |
| Order confirmation / processing | WooCommerce `WC_Email_Customer_Processing_Order` | Yes | Order billing email | Order transitions to processing, email is enabled, and billing email is valid | Test with the real payment gateway flow in sandbox |
| On-hold order | WooCommerce `WC_Email_Customer_On_Hold_Order` | Yes | Order billing email | Order enters on-hold, email is enabled, and billing email is valid | Include if manual/asynchronous payment methods are used |
| Completed order | WooCommerce `WC_Email_Customer_Completed_Order` | Yes | Order billing email | Order is marked completed and the email is enabled | Test with a disposable order only |
| Failed order, customer | WooCommerce `WC_Email_Customer_Failed_Order` | Yes | Order billing email | Order failure notification triggers and customer failed email is enabled | Trigger only through sandbox failure or disposable order |
| Failed order, admin | WooCommerce `WC_Email_Failed_Order` | Yes | Configured admin/store recipient | Order failure notification triggers and admin failed email is enabled | Verify admin recipient in WooCommerce email settings |
| Cancelled order, customer | WooCommerce `WC_Email_Customer_Cancelled_Order` | Yes | Order billing email | Processing/on-hold order changes to cancelled and customer cancelled email is enabled | Test only with a disposable order |
| Cancelled order, admin | WooCommerce `WC_Email_Cancelled_Order` | Yes | Configured admin/store recipient | Cancellation notification triggers and admin cancelled email is enabled | Verify admin recipient in WooCommerce email settings |
| Customer invoice | WooCommerce `WC_Email_Customer_Invoice`, triggered by PayMongo code | Yes | Order billing email | PayMongo flow triggers invoice, invoice email is enabled, and billing email is valid | Confirm PayMongo invoice behavior in sandbox |

## Mail Delivery Components

### Theme Direct Mail

`noyona_handle_newsletter_subscribe()`

- Registered on `admin_post_nopriv_noyona_newsletter_subscribe` and `admin_post_noyona_newsletter_subscribe`.
- Receives submissions from `blocks/newsletter-strip/render.php`.
- Validates nonce, optional reCAPTCHA v3, and email format.
- Sends a plain-text email to `get_option( 'admin_email' )`.
- Subject: `Newsletter Signup`.
- Returns a failure redirect if `wp_mail()` returns false.

Status: Exists and should work if WordPress mail transport is configured correctly.

Security notes:

- Good: nonce validation, email sanitization, optional reCAPTCHA.
- Recommendation: store signups in the database or email marketing platform as the source of truth, because email-only notification can be lost or filtered.
- Recommendation: add rate limiting similar to the contact form to reduce repeated signup spam.

`noyona_handle_contact_form()`

- Registered on `admin_post_nopriv_noyona_contact_form_submit` and `admin_post_noyona_contact_form_submit`.
- Receives submissions from `blocks/contact-form/render.php` when no Contact Form 7 form is configured.
- Validates nonce, honeypot, optional reCAPTCHA v2, IP rate limit, name, email, phone, subject, and message.
- Sends a plain-text email to `get_option( 'admin_email' )`.
- Subject format: `Contact Form: {submitted subject}`.
- Uses the submitter as `Reply-To`, not as `From`, which is good for deliverability.

Status: Exists and should work if WordPress mail transport is configured correctly.

Security notes:

- Good: nonce, honeypot, rate limit, validation, and `Reply-To` usage.
- Recommendation: check the return value of `wp_mail()` and redirect with a failure notice if sending fails. The newsletter handler already does this, but the contact handler currently redirects to success even if mail sending fails.
- Recommendation: consider storing contact submissions or logging a minimal audit record so a mail outage does not lose customer messages.

### Contact Form 7 Mail

`noyona_validate_contact_form_7_recaptcha_v2()`

- Does not send mail directly.
- Adds reCAPTCHA v2 validation before Contact Form 7 sends its configured email.
- Applies only to the Noyona contact form marker or matching posted fields.

`noyona_contact_cf7_captcha_display_message()`

- Does not send mail directly.
- Replaces the Contact Form 7 validation message when captcha verification fails.

Status: Exists as validation support. Actual CF7 email sending is handled by the Contact Form 7 plugin through `wp_mail()`.

Recommendation: verify the CF7 form mail settings in the WordPress admin, especially recipient, From, Reply-To, and message body fields.

### Account Creation Email

Relevant theme code:

- Custom registration in `inc/shortcodes.php` uses `wc_create_new_customer()` when WooCommerce is available.
- If WooCommerce is not available, it falls back to `wp_insert_user()`.

Relevant WooCommerce email:

- `WC_Email_Customer_New_Account`
- Email ID: `customer_new_account`
- Recipient: the new user's email address.

Status: Exists through WooCommerce and should send when WooCommerce is active, the email is enabled, and the custom registration flow creates the customer through `wc_create_new_customer()`.

Important caveat:

- The fallback `wp_insert_user()` path does not explicitly call `wp_new_user_notification()`. If WooCommerce were disabled, account creation may create the user but not send an account email.

Recommendations:

- Keep WooCommerce active for customer registration.
- Confirm **WooCommerce > Settings > Emails > New account** is enabled.
- Test with a real inbox and confirm the WP Mail Logger recipient matches the registered email.
- If WooCommerce might ever be inactive, add an explicit notification call after the fallback `wp_insert_user()` path.

### Password Reset Email

Relevant theme code:

- `noyona_validate_lost_password_email()` in `inc/theme-setup.php`.
- Custom WooCommerce lost password template at `woocommerce/myaccount/form-lost-password.php`.

Relevant WooCommerce email:

- `WC_Email_Customer_Reset_Password`
- Email ID: `customer_reset_password`
- Recipient: the user's account email address.

Status: Exists through WooCommerce and should send when the lost password request is valid and the reset email is enabled.

Security notes:

- Good: the theme requires a valid registered email before allowing the reset request.
- Caution: returning "No account is registered with that email address" improves usability but can reveal whether an email has an account. For stronger privacy, use a generic response such as "If an account exists, a reset link has been sent."

Recommendations:

- Confirm **WooCommerce > Settings > Emails > Reset password** is enabled.
- Test the reset link end to end, not just the email log.
- Consider switching to a generic lost-password response if account enumeration is a concern.

### Order Confirmation / Processing Order Email

Relevant WooCommerce email:

- `WC_Email_Customer_Processing_Order`
- Email ID: `customer_processing_order`
- Recipient: the order billing email.

Status: Exists through WooCommerce. This is the usual customer "order confirmation" for paid orders that enter processing.

Trigger behavior:

- Sends when a new order is paid for and WooCommerce transitions it to processing.
- Sends only if enabled and the order has a valid billing email.

Recommendations:

- Confirm **WooCommerce > Settings > Emails > Processing order** is enabled.
- Test with the actual payment gateway flow, because gateway status transitions decide whether the processing email fires.
- Check WP Mail Logger for the order number, billing email, and subject.
- Confirm the email body contains correct products, totals, billing details, and payment method.

### On-Hold Order Email

Relevant WooCommerce email:

- `WC_Email_Customer_On_Hold_Order`
- Email ID: `customer_on_hold_order`
- Recipient: the order billing email.

Status: Exists through WooCommerce.

Why it matters:

- Some payment methods or manual payment flows create an "on-hold" order rather than a "processing" order.
- This may be the actual order confirmation email for unpaid/manual-payment flows.

Recommendations:

- Include on-hold order email in QA if the site supports bank transfer, cash on delivery, or asynchronous payment methods.
- Confirm **WooCommerce > Settings > Emails > Order on-hold** is enabled if those flows are used.

### Completed Order Email

Relevant WooCommerce email:

- `WC_Email_Customer_Completed_Order`
- Email ID: `customer_completed_order`
- Recipient: the order billing email.

Status: Exists through WooCommerce and should send when an order is marked completed, if enabled.

Recommendations:

- Confirm **WooCommerce > Settings > Emails > Completed order** is enabled.
- Test by moving a safe test order from processing to completed.
- Confirm WP Mail Logger shows the billing email as recipient.

### Failed Order Email

Relevant WooCommerce customer email:

- `WC_Email_Customer_Failed_Order`
- Email ID: `customer_failed_order`
- Recipient: the order billing email.

Relevant WooCommerce admin email:

- `WC_Email_Failed_Order`
- Email ID: `failed_order`
- Recipient: configured admin/store recipient, defaulting to the WordPress admin email.

Status: Customer and admin failed order email classes exist in this WooCommerce version. They send only when enabled and when the expected failed-order status notification is triggered.

Recommendations:

- Confirm whether **WooCommerce > Settings > Emails > Failed order** shows both admin and customer-facing failed-order emails in this installed WooCommerce version.
- If available, confirm the customer failed-order email is enabled.
- Trigger only with a safe sandbox payment failure or a disposable test order.
- Do not test failed payments on live customer orders.

### Cancelled Order Email

Relevant WooCommerce customer email:

- `WC_Email_Customer_Cancelled_Order`
- Email ID: `customer_cancelled_order`
- Recipient: the order billing email.

Relevant WooCommerce admin email:

- `WC_Email_Cancelled_Order`
- Email ID: `cancelled_order`
- Recipient: configured admin/store recipient, defaulting to the WordPress admin email.

Status: Customer and admin cancelled order email classes exist in this WooCommerce version. Customer cancelled email triggers when a processing or on-hold order changes to cancelled, if enabled.

Recommendations:

- Confirm whether **WooCommerce > Settings > Emails > Cancelled order** shows both admin and customer-facing cancelled-order emails.
- If available, confirm the customer cancelled-order email is enabled.
- Test with a disposable order that starts as processing or on-hold, then cancel it.
- Do not cancel real customer orders for email QA.

### Customer Invoice Email

Relevant plugin code:

- `plugins/wc-paymongo-payment-gateway/classes/Utils.php`
- `sendInvoice()` triggers `WC_Email_Customer_Invoice`.
- `completeOrder()` can also trigger `WC_Email_Customer_Invoice` when `$send_invoice` is true.

Relevant WooCommerce email:

- `WC_Email_Customer_Invoice`
- Email ID: `customer_invoice`
- Recipient: the order billing email.

Status: Exists and can be triggered by the PayMongo payment gateway code, depending on gateway flow and `$send_invoice`.

Recommendations:

- Include invoice email in payment-gateway QA if PayMongo is active for checkout.
- Confirm whether invoice sending is enabled/configured in WooCommerce and PayMongo settings.

## What WP Mail Logger Proves

WP Mail Logger proves that the application generated an email and called `wp_mail()`.

It does not prove final inbox delivery.

Use the logger to verify:

- The email event fired.
- The recipient is correct.
- The subject is correct.
- The body has the expected content.
- Attachments, if any, are present.
- The sending status is not failed.

Then verify inbox delivery separately:

- Inbox received the message.
- Message is not in spam or promotions.
- Links work, especially password reset links.
- Sender identity looks correct.
- Reply-To behaves correctly for contact form messages.

## Secure QA Checklist

Before testing:

- Use sandbox/test payment mode.
- Use test customer accounts and disposable test orders.
- Use real inboxes controlled by the project team.
- Confirm WP Mail SMTP is connected to the intended mail provider.
- Confirm WordPress admin email and WooCommerce email recipients are correct.
- Confirm the sending domain has SPF, DKIM, and DMARC configured.

For each email:

- Trigger the real user flow, not only the email preview.
- Confirm WP Mail Logger shows the email.
- Confirm the recipient matches the account or order billing email.
- Confirm the email arrives in the recipient inbox.
- Confirm the content is accurate.
- Confirm links work and do not expose private tokens after use.
- Confirm no sensitive information is sent unnecessarily.

For order emails:

- Test processing, on-hold, completed, failed, cancelled, and invoice flows separately.
- Record the order status before and after each trigger.
- Do not use live customer orders.
- Do not use real payment cards except approved sandbox credentials.

## Recommended Improvements

1. Add send-failure handling to `noyona_handle_contact_form()`.

The contact form currently calls `wp_mail()` and redirects to success without checking the return value. Match the newsletter handler by storing the result and redirecting to an error if sending fails.

2. Add persistent storage for critical submissions.

Newsletter and contact submissions currently rely on email notification. If SMTP fails, a contact message can be lost. Store contact form submissions in the database, a CRM, or a secure admin-only log.

3. Centralize custom theme email helpers.

If more custom emails are added, create a small helper that standardizes recipient validation, headers, plain-text body generation, failure logging, and redirects.

4. Keep customer emails in WooCommerce unless there is a strong reason not to.

WooCommerce already handles account and order email triggers, recipients, templates, and settings. Prefer configuring WooCommerce emails over duplicating those flows in the theme.

5. Review lost-password privacy.

The current lost-password validation reveals when an email is not registered. This may be acceptable for usability, but a generic response is more private and secure.

6. Document enabled WooCommerce email settings from admin.

The code confirms the email classes exist, but final "works" status requires admin settings. Capture screenshots or notes for each enabled email under **WooCommerce > Settings > Emails**.

7. Run a real SMTP deliverability test.

Use WP Mail SMTP's test email feature and check SPF, DKIM, DMARC, and mailbox placement. WP Mail Logger alone is not enough to prove delivery.

## Task Completion Criteria

The email QA task can be considered complete when:

- Account creation email appears in WP Mail Logger and arrives in the test user's inbox.
- Password reset email appears in WP Mail Logger, arrives in the inbox, and the reset link works.
- Processing or on-hold order confirmation appears in WP Mail Logger and arrives with correct order details.
- Completed order email appears and arrives after a safe test order is completed.
- Failed order customer email is enabled, safely triggered, logged, and received, or documented as disabled/not required.
- Cancelled order customer email is enabled, safely triggered, logged, and received, or documented as disabled/not required.
- Any PayMongo invoice email requirement is confirmed, safely triggered, logged, and received.
- WP Mail SMTP test passes.
- DNS authentication for the sending domain is confirmed.
- No test used live customer orders or unsafe live payments.

