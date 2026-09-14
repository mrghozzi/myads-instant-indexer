# ⚡ Instant Indexer for MYADS

**Instant Indexer** is an automated real-time search engine push indexing plugin for the **MYADS v4.5.0+** ecosystem. It connects your platform directly with major search engines—including **Google** via the **Google Indexing API** and **Microsoft Bing, Yandex, Seznam, and Naver** via the **IndexNow protocol**—to index fresh content instantly upon publication.

---

## ✨ Key Features

1. **Google Indexing API Integration**:
   - Self-contained **RS256 JWT** signing using standard PHP OpenSSL (no bulky external Google SDK dependencies).
   - Automatic OAuth2 Bearer token exchange and 55-minute cache management to optimize API quotas.
   - Real-time `URL_UPDATED` (publish/update) and `URL_DELETED` (removal) notifications.
   - On-demand URL metadata lookup directly from Google.

2. **IndexNow Protocol Integration**:
   - Native support for the official IndexNow hub (**Microsoft Bing, Yandex, Seznam, and Naver**).
   - Automated IndexNow key generation, storage, and key verification serving via dynamic route `/{key}.txt` and public file syncing.
   - Supports both single URL and batch multi-URL (`urlList`) submissions.

3. **Automated Content Triggers & Event Listeners**:
   - Automatically detects and pushes URLs when content is created, updated, or deleted:
     - **Forum Topics & Discussions** (`ForumTopic`)
     - **Videos, Clips & Shorts** (`Status` / `/video`)
     - **Store Products & Marketplace Listings** (`Product` / `/store`)
     - **News & Articles** (`News` / `/news`)
     - **Directory Listings** (`Directory` / `/directory`)
     - **Custom & Static Pages** (`Page` / `/page`)
   - Independent granular toggles per content type in the admin settings.

4. **Real-Time Admin Dashboard (`/admin/instant-indexer`)**:
   - Live KPI metric cards: Total submissions (all-time & today), Google success rate, IndexNow success rate, and error counters.
   - Comprehensive indexing activity logs table (`instant_indexer_logs`) with full-text search, engine filters, status filters, and CSV export.
   - Interactive JSON modal viewer for inspection of raw HTTP response payloads and status codes.
   - Built-in retry mechanism for failed submissions.
   - Manual instant URL submitter (single or bulk up to 100 URLs).
   - "Quick System Indexer" to browse recently published platform content and index candidates with one click.
   - Interactive AJAX connection test tools for both Google Service Accounts and IndexNow keys.

---

## 📋 Requirements

- **MYADS Core**: v4.5.0 or higher
- **PHP**: 8.2+ with `openssl` and `json` extensions enabled
- **Google Cloud Account**: (Optional, for Google Indexing API) with Service Account JSON credentials and Search Console ownership.

---

## 🛠️ Configuration & Setup

### 1. Google Indexing API
1. Create a project in [Google Cloud Console](https://console.cloud.google.com/).
2. Enable the **Web Search Indexing API**.
3. Create a **Service Account** with the `Owner` role under **APIs & Services > Credentials**.
4. Create and download a **JSON Key** for the Service Account.
5. In [Google Search Console](https://search.google.com/search-console), add the Service Account email address as an **Owner** under **Settings > Users and permissions**.
6. Paste the JSON key into **Instant Indexer > Settings** and click **Test Google Connection**.

### 2. IndexNow Protocol
1. Navigate to **Instant Indexer > Settings**.
2. Click **Generate New Key** or use the pre-generated key.
3. The verification file `/{key}.txt` is automatically served by the plugin.
4. Click **Test IndexNow Connection** to verify host and key availability.

---

## 📁 File Structure

```text
plugins/myads-instant-indexer/
├── CHANGELOG.md
├── README.md
├── boot.php
├── plugin.json
├── thumbnail.png
├── database/
│   └── migrations/
│       └── 2026_08_19_000000_create_instant_indexer_logs_table.php
├── lang/
│   ├── ar/messages.php
│   └── en/messages.php
├── src/
│   ├── Controllers/
│   │   └── AdminIndexerController.php
│   ├── Models/
│   │   └── IndexerLog.php
│   └── Services/
│       ├── GoogleIndexingService.php
│       ├── IndexNowService.php
│       └── InstantIndexerService.php
└── views/
    └── index.blade.php
```

---

## 👤 Author

- **Author**: MrGhozzi
- **Website**: [https://github.com/mrghozzi](https://github.com/mrghozzi)
- **License**: MIT
