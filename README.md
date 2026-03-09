# Fintech Backend Roadmap – TODO

## Phase 1 — Core Account System (Already In Progress)

* [x] User registration (multi–step flow)
* [x] Email OTP verification
* [x] Password creation
* [x] Transaction PIN setup
* [x] Sanctum authentication
* [x] Registration session handling
* [x] Reusable OTP service

---

# Phase 2 — KYC Verification System

## Database

* [ ] Create `kyc_verifications` table
* [ ] Fields:

  * `user_id`
  * `bvn`
  * `nin`
  * `document_type`
  * `document_number`
  * `selfie_image`
  * `document_image`
  * `status (pending, verified, rejected)`
  * `verified_at`
  * `rejection_reason`

## Models

* [ ] Create `KycVerification` model
* [ ] Add relationship in `User` model

## API Endpoints

* [ ] `POST /kyc/start`
* [ ] `POST /kyc/upload-document`
* [ ] `POST /kyc/selfie`
* [ ] `POST /kyc/submit`
* [ ] `GET /kyc/status`

## Logic

* [ ] Validate KYC data
* [ ] Store document uploads securely
* [ ] Restrict certain features if KYC not completed
* [ ] Add KYC status middleware

---

# Phase 3 — Naira Virtual Account Integration

## Database

* [ ] Create `virtual_accounts` table
* Fields:

  * `user_id`
  * `account_name`
  * `account_number`
  * `bank_name`
  * `provider`
  * `provider_reference`
  * `status`
  * `created_at`

## Services

* [ ] Create `StrowalletService`
* [ ] Create virtual account method
* [ ] Fetch virtual account details
* [ ] Handle API errors

## Controllers

* [ ] `POST /wallets/virtual-naira/create`
* [ ] `GET /wallets/virtual-naira`

## Security

* [ ] Prevent duplicate account creation
* [ ] Require authentication
* [ ] Log API responses

---

# Phase 4 — Deposit Webhooks

## Webhook Endpoint

* [ ] `POST /webhooks/strowallet/deposit`

## Logic

* [ ] Verify webhook signature
* [ ] Identify user using account number
* [ ] Record transaction
* [ ] Credit ledger
* [ ] Notify user

## Database

* [ ] `transactions` table
* [ ] `ledger_entries` table

---

# Phase 5 — Ledger & Balance System

## Database

* [ ] `ledger_accounts`
* [ ] `ledger_entries`
* [ ] `transactions`

## Logic

* [ ] All balances derived from ledger
* [ ] Debit and credit accounting system
* [ ] Prevent negative balances
* [ ] Wrap transactions in DB transactions

---

# Phase 6 — Virtual Card System

## Database

* [ ] `cards` table
  Fields:
* `user_id`
* `card_provider`
* `card_reference`
* `last_four`
* `expiry`
* `currency`
* `status`

## Services

* [ ] `CardService`
* [ ] Create virtual card
* [ ] Freeze card
* [ ] Unfreeze card
* [ ] Fund card

## API Endpoints

* [ ] `POST /cards/create`
* [ ] `GET /cards`
* [ ] `POST /cards/freeze`
* [ ] `POST /cards/unfreeze`
* [ ] `POST /cards/fund`

---

# Phase 7 — Transfers

## Features

* [ ] User → User transfer
* [ ] Bank withdrawal
* [ ] Card funding

## Security

* [ ] OTP verification
* [ ] Transaction PIN verification
* [ ] Rate limiting

---

# Phase 8 — Notifications

* [ ] Email notifications
* [ ] Push notifications
* [ ] SMS alerts
* [ ] Transaction alerts

---

# Phase 9 — Fraud Protection

* [ ] Failed login detection
* [ ] Suspicious transaction monitoring
* [ ] IP tracking
* [ ] Wallet freeze capability

---

# Phase 10 — Admin System

* [ ] Admin dashboard
* [ ] View users
* [ ] Approve/reject KYC
* [ ] View transactions
* [ ] Freeze accounts
* [ ] Manual ledger adjustments

---

# Phase 11 — Performance & Security

* [ ] Queue system for emails & jobs
* [ ] Redis caching
* [ ] API rate limiting
* [ ] Audit logs
* [ ] Encrypted sensitive fields
* [ ] Monitoring & error tracking

---

# Phase 12 — Production Readiness

* [ ] API documentation
* [ ] Postman collection
* [ ] Automated tests
* [ ] CI/CD pipeline
* [ ] Server deployment
* [ ] Backup system

---

# Optional Future Features

* [ ] USD virtual accounts
* [ ] Crypto wallets (USDT / USDC)
* [ ] FX exchange system
* [ ] Merchant payments
* [ ] Bill payments
* [ ] Debit cards

---
