# ABCD Museum Management Module

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![ABCD](https://img.shields.io/badge/ABCD-v4.0%2B-success.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)

The **Museum Management Module** is a robust, plug-and-play extension designed for the **ABCD v4** (Automatización de Bibliotecas y Centros de Documentación) ecosystem. It introduces comprehensive museum logistics, legal auditing, and procedure management fully compliant with the **Spectrum 5.0** standard.

By operating on isolated auxiliary databases and utilizing a modern modal-based UI, this module ensures that museum logistics (such as entry/exit receipts and location tracking) do not conflict with ABCD's traditional bibliocentric logic (MARC21).

## ✨ Key Features

*   **Spectrum 5.0 Compliance:** Standardized management of Object Entry, Exit, Location Control, and Loans.
*   **Zero-Touch Installation:** Automated setup wizard that dynamically deploys the required FDT, FST, PFT, and PAR files directly into your ABCD environment.
*   **Legal PDF Generation:** Instantly generates professional, printable PDF receipts for object movements, featuring dynamic institutional headers, logos, and auto-injected legal terms of responsibility.
*   **Immutable Audit Trail:** Operates an isolated tracking system (`spec_movements`) that logs every physical location change of an object using cryptographic security hashes.
*   **Seamless UI/UX:** Replaces legacy full-screen takeovers with elegant modals (iframes) and JavaScript watchers to maintain a clean workflow.
*   **Multilingual Support:** Native compatibility with English, Portuguese, and Spanish, leveraging ABCD's `LanguageManager` to prevent namespace collisions.
*   **Ready-to-Use Statistics:** Pre-configured matrixes to extract cross-referenced insights (e.g., Condition by Material, Objects by Physical Location).

## 📂 Directory Structure

```text
museum/
├── index.php                 # Main Dashboard (Catalog and Procedures)
├── browse.php                # Custom list viewer (modal UI wrapper)
├── install.php               # Automated Installation Wizard
├── plugin-bootstrap.php      # Hooks Registration (menu, translations, save events)
├── plugin.json               # Plugin manifest (version, description)
├── actions/                  
│   └── print_receipt.php     # Endpoint for PDF receipt generation
├── admin/
│   └── configure.php         # Admin panel (Institution ID, Legal Terms, Audit Settings)
├── assets/css/               # Isolated stylesheets for the UI and PDF outputs
├── lang/                     # Translation dictionaries (pt, es, en)
├── src/                      # PHP Classes (PSR-4)
│   ├── Audit/                # Movement auditing via CISIS/WXIS hooks
│   └── Reports/              # mPDF integration for Receipt Generation
├── install/                  # Blueprint structure for auxiliary databases
│   ├── spec_receipts/        # Entry & Exit Procedures
│   ├── spec_movements/       # Location Audit Trail
│   └── spec_loans/           # Loans and Acquisitions
└── vendor/                   # Bundled dependencies (mPDF)
```

## 🛠️ Installation

This plugin is designed to be **100% plug-and-play**. No command-line operations or Composer installations are required from the end-user.

1.  Extract the `museum` folder into your ABCD plugins directory: `/content/plugins/museum/`.
2.  Log into your ABCD Central module as an Administrator.
3.  Navigate to the Museum Module. The system will automatically detect if the auxiliary databases are missing and present the **Welcome Wizard**.
4.  Click **Run Installation Wizard**. The system will securely copy and register the following databases:
    *   `spec_receipts`
    *   `spec_movements`
    *   `spec_loans`
5.  Go to **Administration > Configure Module** to set up your Institution Name, Logo URL, and Default Legal Terms.

## 🏛️ Database Architecture

The plugin relies on the core `spectrum` database for cataloging but delegates procedural tasks to three auxiliary bases:

*   **`spec_receipts`**: Manages the legal transfer of custody. Captures depositor information, associated object IDs, and generates PDF receipts.
*   **`spec_movements`**: An immutable log that tracks the physical location lifecycle of an object (Origin → Destination), identifying authorized personnel and timestamps.
*   **`spec_loans`**: Manages temporary custody agreements, capturing insurance policies, transport conditions, and condition report references.

## ⚙️ Administration & Compliance

The module includes a dedicated Configuration Panel (`admin/configure.php`) that saves parameters globally in `bases/par/museum.def`:
*   **Institutional Identity:** Define the official name, legal ID, address, and logo URL for PDF headers.
*   **Default Terms:** Automatically inject "Terms of Responsibility" boilerplate text into entry/exit receipts to save curators time.
*   **Strict Audit Mode:** When enabled, forces the recording of an "Authorized By" reference for any internal location change.

## 🧑‍💻 Development Notes

*   **Vendor Bundling:** The `mPDF` library is pre-bundled in the `vendor/` directory. Do not remove this folder, as it guarantees PDF generation capabilities even on ABCD instances without global Composer support.
*   **Namespace Protection:** All language keys in `lang/*.tab` are prefixed with `museum_` to prevent collisions with the ABCD core dictionaries.

---
*Developed for the ABCD Community.*
