# Diagram 2 — Tables Structure: GitHub ↔ VS Code ↔ Database

> Repo: `jerowm098/Smart-Stock` (Laravel 12, PHP 8.2)
> Database: **Supabase PostgreSQL** via PgBouncer (port `6543`, `DB_SSLMODE=require`)
> Source of schema truth: `database/migrations/` — **hindi** ang live DB ang ina-source ng schema.

---

## 2.1 — Paano dumadaloy ang data: VS Code → GitHub → Database

```mermaid
flowchart LR
    subgraph VSC["💻 VS Code — Working Directory"]
        MIG1["database/migrations/<br/>2026_09_17_181118_<br/>create_products_table.php"]
        MIG2["database/migrations/<br/>2026_09_30_000003_<br/>create_order_suggestions_table.php"]
        MODEL["app/Models/Product.php"]
        CTRL["app/Http/Controllers/…"]
        MIGE["app/Models/User.php"]
    end

    subgraph REPO["📦 GitHub — jerowm098/Smart-Stock"]
        MAIN["main<br/>(stable schema)"]
        DEV["develop<br/>(in-progress schema)"]
    end

    DB[("☁️ Supabase Postgres<br/>aws-0-ap-northeast-1<br/>:6543 · SSL")]

    MIG1 --> MODEL --> CTRL
    MIG2 --> MODEL
    MIGE --> CTRL

    CTRL -->|"Eloquent ORM<br/>read / write"| DB
    MIG1 -->|"git commit + push"| DEV
    DEV -->|"PR → approve → merge"| MAIN
    MAIN -->|"migrate (reviewed file,<br/>hindi manual SQL)"| DB

    style VSC fill:#1e4a8a,stroke:#60a5fa,color:#fff
    style REPO fill:#4a1e6e,stroke:#c084fc,color:#fff
    style DB fill:#134e4a,stroke:#2dd4bf,color:#fff
    style DEV fill:#78350f,stroke:#fbbf24,color:#fff
```

> 🔑 **Rule mula sa PDF:** *"Every schema change is a reviewed file in the repository. No hand-edited databases, ever."* Ang schema ay **code**, hindi database state.

---

## 2.2 — Database Schema (ER Diagram)

```mermaid
erDiagram
    USERS ||--o{ PRODUCTS : "owns (user_id)"
    USERS ||--o{ PRODUCTS : "supplier created"
    USERS ||--o{ SALES : "makes"
    USERS ||--o{ STOCK_ADJUSTMENTS : "records"
    USERS ||--o{ STOCK_INS : "receives"
    USERS ||--o{ ORDER_SUGGESTIONS : "gets"

    PRODUCTS ||--o{ SALE_ITEMS : "sold in"
    SALES ||--o{ SALE_ITEMS : "contains"
    PRODUCTS ||--o{ STOCK_ADJUSTMENTS : "adjusted"
    PRODUCTS ||--o{ STOCK_INS : "received"
    PRODUCTS ||--o{ ORDER_SUGGESTIONS : "suggested"
    SUPPLIERS ||--o{ PRODUCTS : "supplies"

    USERS {
        bigint id PK
        string name
        string email UK
        string username UK
        string password
        string role "admin|staff|user"
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    PRODUCTS {
        bigint id PK
        bigint user_id FK "owner"
        bigint supplier_id FK "nullable"
        string name
        string sku UK "unique per user"
        string category
        decimal price
        int stock_quantity
        string unit
        decimal receiving_units
        string status "high|low"
        boolean is_active
        timestamp created_at
    }

    SUPPLIERS {
        bigint id PK
        bigint user_id FK "owner"
        string name
        string contact_email
        string contact_phone
        string address
        timestamp created_at
    }

    SALES {
        bigint id PK
        bigint user_id FK "cashier"
        decimal total_amount
        timestamp sold_at
        timestamp created_at
    }

    SALE_ITEMS {
        bigint id PK
        bigint sale_id FK
        bigint product_id FK
        int quantity
        decimal unit_price
        decimal subtotal
    }

    STOCK_ADJUSTMENTS {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        int quantity_before
        int quantity_after
        string reason
        timestamp created_at
    }

    STOCK_INS {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        int quantity_received
        string reference_number
        timestamp received_at
    }

    ORDER_SUGGESTIONS {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        int suggested_quantity
        decimal estimated_cost
        string status "pending|approved|rejected"
        timestamp created_at
    }
```

---

## 2.3 — Migration Timeline (order matters!)

```mermaid
gantt
    title Schema Build Order — database/migrations/
    dateFormat YYYY-MM-DD
    axisFormat %b %d

    section Foundation
    0001_01_01_000000 create users table      :done, f1, 2025-01-01, 1d
    0001_01_01_000001 create cache table       :done, f2, after f1, 1d
    0001_01_01_000002 create jobs table        :done, f3, after f2, 1d

    section Core Domain
    create_products_table                      :done, c1, 2026-09-17, 1d
    create_alerts_table                        :done, c2, 2026-09-18, 1d
    create_suppliers_table                     :done, c3, 2026-09-23, 1d
    create_stock_adjustments_table             :done, c4, 2026-09-24, 1d
    create_sales_table                         :done, c5, 2026-09-24, 1d
    create_sale_items_table                    :done, c6, 2026-09-24, 1d
    create_stock_ins_table                     :done, c7, 2026-09-24, 1d

    section User Scoping
    add_username_to_users_table                :done, u1, 2026-09-21, 1d
    add_role_to_users_table                    :done, u2, 2026-09-22, 1d
    add_user_id_to_products_table              :done, u3, 2026-09-22, 1d
    make_sku_unique_per_user                   :done, u4, 2026-09-22, 1d
    add_receiving_units_to_products_table      :done, u5, 2026-09-24, 1d

    section Consolidation
    consolidate_shared_inventory               :done, s1, 2026-09-28, 1d

    section Hardening
    add_is_active_to_users_table               :done, h1, 2026-09-30, 1d
    add_is_active_to_products_table            :done, h2, 2026-09-30, 1d
    create_order_suggestions_table             :done, h3, 2026-09-30, 1d
    protect_history_from_user_cascade_delete   :done, h4, 2026-09-30, 1d
```

> ⚠️ **Migration ordering gotcha:** Ang timestamps ay **hindi chronological** sa pinakahuling batch — `2026_09_21_*` (add_user_id) ay lumagpas sa `2026_09_23_*` (create_sales). Laging tingnan ang **filename**, hindi ang dami.
>
> ✅ **Safe:** `protect_history_from_user_cascade_delete` — pinoprotektahan ang historical records (Sales, StockAdjustments) kapag ang user ay tinanggal. Tugma ito sa PDF rule: *"A destructive change is split in two: release n stops using the column, release n+1 drops it."*

---

## 2.4 — Table Categories

```mermaid
flowchart TB
    subgraph ID["🆔 Identity & Access"]
        U["users<br/><i>8 columns + username, role, is_active</i>"]
    end

    subgraph CAT["📦 Catalog"]
        P["products<br/><i>per-user SKU uniqueness</i>"]
        S["suppliers"]
    end

    subgraph SALES["💰 Transactions"]
        SA["sales"]
        SI["sale_items"]
    end

    subgraph INV["📥 Inventory Movement"]
        SAJ["stock_adjustments<br/><i>before → after</i>"]
        SIN["stock_ins<br/><i>receiving</i>"]
    end

    subgraph INT["🧠 Intelligence"]
        OS["order_suggestions<br/><i>AI / rule-based</i>"]
    end

    subgraph SYS["⚙️ System (Laravel built-in)"]
        CA["cache"]
        JO["jobs"]
    end

    U --> P
    U --> S
    U --> SA
    U --> SAJ
    U --> SIN
    U --> OS
    P --> SAJ
    P --> SIN
    P --> OS
    P --> SI
    S --> P
    SA --> SI

    style U fill:#78350f,stroke:#fbbf24,color:#fff
    style P fill:#1e4a8a,stroke:#60a5fa,color:#fff
    style SA fill:#4a1e6e,stroke:#c084fc,color:#fff
    style SI fill:#4a1e6e,stroke:#c084fc,color:#fff
    style OS fill:#14532d,stroke:#4ade80,color:#fff
    style SYS fill:#374151,stroke:#9ca3af,color:#fff
```

| Table | Purpose | Key Constraint |
|---|---|---|
| `users` | Accounts + roles | `email` & `username` unique, soft-disabled via `is_active` |
| `products` | Inventory master | `sku` **unique per user** (multi-tenant scoping) |
| `suppliers` | Vendor registry | Scoped per user |
| `sales` / `sale_items` | POS transactions | `sale_items` = line items w/ price snapshot |
| `stock_adjustments` | Audit trail | Stores `quantity_before` + `quantity_after` |
| `stock_ins` | Receiving log | References product + user |
| `order_suggestions` | Replenishment proposals | Status workflow: pending → approved/rejected |
| `cache` / `jobs` | Laravel internals | System-managed, huwag gawing manually |

---

## 2.5 — Data Lifecycle: Dev → Prod

```mermaid
flowchart LR
    subgraph LOCAL["💻 VS Code Local"]
        W["Working file<br/>ProductSeeder.php"]
        ST["git add → STAGE"]
        CM["git commit<br/><i>schema + data definition</i>"]
    end

    subgraph GH["📦 GitHub"]
        DV["develop branch"]
        PR["Pull Request<br/>review: migration backward-compatible?"]
        MN["main branch<br/>tag v0.n.0"]
    end

    subgraph DB[("☁️ Supabase")]
        EMPTY[("🧪 Empty DB<br/>CI migration test")]
        PROD[("🏭 Live DB<br/>migrate only")]
        SNAP[("💾 Snapshot<br/>7-day retention")]
    end

    W --> ST --> CM --> DV
    DV --> PR
    PR -->|"review ✅"| MN
    MN -->|"php artisan migrate"| EMPTY
    EMPTY -->|"pass?"| PROD
    PROD --> SNAP
    SNAP -.->|"restore drill<br/>before Sprint review"| EMPTY

    style PR fill:#7f1d1d,stroke:#f87171,color:#fff
    style EMPTY fill:#14532d,stroke:#4ade80,color:#fff
    style PROD fill:#4a1e6e,stroke:#c084fc,color:#fff
```

**Mga rules mula sa PDF na saklaw ng diagram:**

| Rule | Saan nakikita |
|---|---|
| Schema change = reviewed file, hindi hand-edit | `database/migrations/` lamang ang source |
| Migrations run against empty DB every CI run | `EMPTY` node — "free test of the migration path" |
| Applied by the pipeline, never by a person | `MN → PROD` edge |
| Backwards-compatible for one release | PR review checkpoint |
| Destructive change split in two releases | `protect_history_from_user_cascade_delete` |
| 7-day snapshot retention | `SNAP` node |

---

## 2.6 — Connection String Breakdown

```mermaid
flowchart LR
    ENV[".env"] -->|"Laravel config<br/>config/database.php"| POOL["🔌 PgBouncer<br/>aws-0-ap-northeast-1<br/>.pooler.supabase.com:6543"]
    POOL -->|"|sslmode=require"| PG[("🐘 postgres<br/>database: postgres")]
    POOL -.->|"connection pool"| APP["Laravel app"]
    APP -->|"Eloquent"| POOL

    style POOL fill:#78350f,stroke:#fbbf24,color:#fff
    style PG fill:#134e4a,stroke:#2dd4bf,color:#fff
```

| Setting | Value | Note |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | PostgreSQL driver |
| `DB_HOST` | `aws-0-ap-northeast-1.pooler.supabase.com` | Supabase **pooler** (not direct) |
| `DB_PORT` | `6543` | ⚠️ **Transaction mode** pooler — hindi `5432` |
| `DB_DATABASE` | `postgres` | Default project DB |
| `DB_USERNAME` | `postgres.<project-ref>` | Supabase scoped user |
| `DB_SSLMODE` | `require` | Encrypted connection |

> 🔒 **Erasure obligation:** Ang 7-day snapshot window + 7-day erasure obligation ay magkasalungat. Isang account na binura ngayon, nandoon pa rin sa snapshots ng isang linggo — kaya't *"within seven days"* ang sinulat sa requirement, hindi *"immediately"*.

---

## 2.7 — Connection: GitHub Tables ↔ VS Code Tables

```mermaid
flowchart LR
    subgraph GH["📦 GitHub — Versioned Schema"]
        GF1["📄 database/migrations/<br/>21 migration files"]
        GF2["🏭 database/factories/<br/>Product · StockIn · Supplier · User"]
        GF3["🌱 database/seeders/<br/>Product · Sale · Supplier"]
    end

    subgraph VS["💻 VS Code — Application Layer"]
        V1["🧩 app/Models/<br/>8 models"]
        V2["🎮 app/Http/Controllers"]
        V3["👁️ resources/views/"]
        V4["🛣️ routes/web.php"]
        V5["⚙️ app/Services/"]
    end

    subgraph DB[("☁️ Supabase — Runtime Data")]
        D1["users"]
        D2["products"]
        D3["suppliers"]
        D4["sales"]
        D5["sale_items"]
        D6["stock_adjustments"]
        D7["stock_ins"]
        D8["order_suggestions"]
    end

    V4 --> V2 --> V1
    V5 --> V1
    V1 -->|"Eloquent models<br/>map 1:1 sa tables"| D1
    V1 --> D2
    V1 --> D3
    V1 --> D4
    V1 --> D5
    V1 --> D6
    V1 --> D7
    V1 --> D8

    GF1 -.->|"defines"| D2
    GF1 -.->|"defines"| D8
    GF2 -.->|"feeds"| V1
    GF3 -.->|"seeds"| DB

    style GH fill:#4a1e6e,stroke:#c084fc,color:#fff
    style VS fill:#1e4a8a,stroke:#60a5fa,color:#fff
    style DB fill:#134e4a,stroke:#2dd4bf,color:#fff
```

| GitHub holds | VS Code holds | Supabase holds |
|---|---|---|
| **Definition** — migrations, models, factories, seeders | **Definition** — same files + controllers, views, routes, services | **Runtime data** — actual rows |
| Versioned, reviewed, merged via PR | Working copy, uncommitted changes possible | Mutable, no history |
| Source of truth for schema ✅ | Source of truth for logic ✅ | Disposable (backed up 7d) |

---

## 📌 Buod

1. **Schema = code.** Ang lahat ng table structure ay nasa `database/migrations/` at naka-version sa GitHub. Hindi ka kailanman mag-hahawo-edit ng DB.
2. **Connection path:** VS Code → Eloquent Models → PgBouncer (`:6543`) → Supabase Postgres.
3. **8 domain tables** + 2 Laravel system tables (cache, jobs).
4. **Migration filename** ang tumatakda sa execution order — hindi ang petsa.
5. **Ang PDF ay target/reference**, hindi kasalukuyang estado. Kulang pa: CI pipeline, `/health` endpoint, automated backup, alerting.
