# Changelog — Instant Indexer

All notable changes to the **Instant Indexer** (`myads-instant-indexer`) plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] - 2026-09-14

### Fixed
- **IndexNow Key Verification Route Regex Constraint:** Restricted the dynamic `/{key}.txt` verification route regex to validate keys strictly between 8 and 64 characters (`[a-zA-Z0-9_\-]{8,64}`) adhering to IndexNow protocol specifications and preventing broad URL capture collisions.

## [1.0.0] - 2026-08-19

### Added
- **Initial Release** of the **Instant Indexer** plugin for MYADS v4.5.2.
- **Google Indexing API v3 Integration**:
  - Embedded RS256 JWT signature generation using native PHP OpenSSL.
  - Automatic OAuth2 token exchange with 55-minute cache layer.
  - Publishing `URL_UPDATED` and `URL_DELETED` requests to Google endpoints.
  - URL metadata query and inspection support.
- **IndexNow Protocol Integration**:
  - Full support for Microsoft Bing, Yandex, Seznam, and Naver search engines.
  - Automated IndexNow API key generation and dynamic `/{key}.txt` verification route.
  - Single and batch (`urlList`) URL submission support.
- **Automated Content Observers**:
  - Event listeners on `ForumTopic`, `Status` (videos/shorts), `Product`, `News`, `Directory`, and `Page` models.
  - Automatic push upon creation and update.
  - Optional `URL_DELETED` notification dispatch on content deletion.
- **Admin Dashboard (`/admin/instant-indexer`)**:
  - Real-time statistics cards: Total submissions, Google success rate, IndexNow success rate, and error counters.
  - Interactive activity logs table with search, engine filter, status filter, retry button, and CSV export.
  - Modal viewer for raw JSON response payloads and HTTP status codes.
  - Manual instant URL submitter for single and bulk URL entries.
  - System candidate browser to quickly index unindexed topics, videos, and store products.
  - Live AJAX connection testing tools for both Google and IndexNow credentials.
- **Database & Storage**:
  - Dedicated `instant_indexer_logs` database table and Eloquent model (`IndexerLog`).
- **Localization**:
  - Full Arabic and English translation catalogues (`lang/ar/messages.php` and `lang/en/messages.php`).
- **Automated Test Suite**:
  - Feature and unit test coverage (`tests/Feature/InstantIndexerTest.php`).
