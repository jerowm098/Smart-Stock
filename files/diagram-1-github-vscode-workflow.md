# Diagram 1 — GitHub Repository ↔ VS Code: Connection, Push, Merge, Stages

> Reference base: `files/Infrastructure & Workflow - mam inda.pdf` (Environments Overview, Deployment Process, Configuration Management, Secrets, Migrations, Backup & Restore, Monitoring, Access/Cost/Limits)
> Actual state ngayon: repo `jerowm098/Smart-Stock` (PUBLIC, default branch `main`)

---

## 1.1 — Overview ng Connection

```mermaid
flowchart LR
    DEV["👨‍💻 Developer<br/>Jerome"]
    VS["VS Code<br/>Smart-Stock (local)"]
    GIT["Git (local repo)<br/>.git/"]
    GH["GitHub<br/>jerowm098/Smart-Stock<br/>PUBLIC"]
    DEPLOY["Hosting / Test<br/>Deploy target"]

    DEV -->|"edit files<br/>composer / php artisan"| VS
    VS <-->|"git add / commit<br/><b>STAGE 1</b>"| GIT
    GIT -->|"git push origin main<br/><b>STAGE 2</b>| GH
    GH -->|"git pull / fetch<br/><b>STAGE 3</b>| GIT
    GH -->|"webhook / Actions"| DEPLOY

    style VS fill:#1e4a8a,stroke:#60a5fa,color:#fff
    style GIT fill:#8a5a1e,stroke:#fbbf24,color:#fff
    style GH fill:#4a1e6e,stroke:#c084fc,color:#fff
    style DEPLOY fill:#1e6e4a,stroke:#34d399,color:#fff
```

**Paano sila magkakaugnayan (mechanics):**

| Layer | Ano ang nangyayari |
|---|---|
| **VS Code (editor)** | Nag-iikli ng code. Hindi ito ang nag-iimbak ng history — ang `.git/` folder ang source of truth ng history. |
| **Local Git repo** | `.git/` folder sa loob ng project. Dito nabubuo ang `index` (staging area) at ang commit objects. |
| **Remote (`origin`)** | `https://github.com/jerowm098/Smart-Stock.git` — HTTPS protocol. |
| **GitHub** | Central server ng history + collaboration (PR, Issues, Actions, branch protection). |

---

## 1.2 — Ang 3 Yugto ng Push & Merge (stages)

```mermaid
sequenceDiagram
    autonumber
    actor Dev as 👨‍💻 Dev (VS Code)
    participant Work as Working Directory
    participant Index as Staging Area (.git/index)
    participant Repo as Local Repo (.git)
    participant GH as GitHub origin/main

    Note over Dev,GH: ── STAGE 1 · COMMIT (local, walang network) ──
    Dev->>Work: 1. Edit files (php, blade, css, migration)
    Dev->>Dev: git add <file>
    Dev->>Index: 2. Snapshot ng file → STAGED
    Note right of Index: review: git diff --staged
    Dev->>Dev: 3. git commit -m "feat: ..."
    Repo->>Repo: 4. Commit object (tree + parent + sha)

    Note over Dev,GH: ── STAGE 2 · PUSH (network) ──
    Dev->>GH: 5. git push origin main
    GH-->>Dev: main updated: 9ea06f1 → new sha

    Note over Dev,GH: ── STAGE 3 · PULL / SYNC (network) ──
    GH-->>Dev: 6. git fetch (remote → remote-tracking)
    Dev->>Repo: 7. git pull origin main (fetch + merge)
    Note right of Repo: Conflict? → resolve → commit → push ulit
```

**Kung "stage" ang tinutukoy ng PDF (Sprint release):**

| Stage | Trigger | What happens | Gate |
|---|---|---|---|
| **Stage 1 — Local commit** | `git add` + `git commit` | Commit object ang nabubuo. Walang network. | Wala (developer lang) |
| **Stage 2 — Push to `develop`** | `git push origin develop` | Auto-deploy sa **Test** (ayon sa PDF: "Every merge into develop deploys to Test automatically") | Pipeline green |
| **Stage 3 — PR `develop` → `main`** | Release pull request | Ang ticket list sa PR description = release notes | ✅ **Tanging manual gate** — DevOps + PM approval |
| **Stage 4 — Tag + release** | `git tag v0.<n>.0` | Tag → Staging → Production (naka-pending pa ang MT-201/MT-202) | Smoke check |

---

## 1.3 — Branching Model (kasalukuyang estado)

```mermaid
gitGraph
    commit id: "Initial commit"
    branch develop
    commit id: "Setup + migrations"
    checkout main
    commit id: "Release v0.1"
    checkout develop
    commit id: "Feature: stock adjustments"
    commit id: "Feature: supplier module"
    checkout main
    merge develop tag: "v0.2.0"
    commit id: "Release v0.2"
    checkout develop
    commit id: "Feature: order suggestions"
    checkout main
    merge develop tag: "v0.3.0"
```

| Branch | Purpose | Naka-merge na sa `main`? |
|---|---|---|
| `main` | Production / stable release. **Default branch** ng GitHub. | — |
| `develop` | Integration branch. Every merge dito = auto Test deploy. | ✅ naka-sync |

> ⚠️ **Note:** Walang `.github/workflows/` folder ang repo — kaya **wala pang actual na GitHub Actions pipeline**. Ang PDF ay *reference/target* na "SAMPLES FOR YOUR REFERENCE", hindi kasalukuyang implementasyon.

---

## 1.4 — Mga Secrets & Config ((Configuration Management)

```mermaid
flowchart TB
    subgraph LOCAL["💻 Local (.env — HINDI na-commit)"]
        L1["APP_ENV=local"]
        L2["DB_PASSWORD=***"]
        L3["SUPABASE keys"]
    end

    subgraph EXAMPLE["📄 .env.example — COMMITED, lahat ng value EMPTY"]
        E1["DB_DATABASE="]
        E2["DB_PASSWORD="]
        E3["CACHE_TTL_HOURS="]
        E4["SESSION_IDLE_MINUTES="]
        E5["BCRYPT_COST="]
    end

    subgraph CI["🔐 CI Secret Store (GitHub Secrets)"]
        S1["DB_URL"]
        S2["CATALOGUE_API_KEY"]
        S3["EMAIL_API_KEY"]
    end

    subgraph DB["☁️ Supabase PostgreSQL"]
        D1["aws-0-ap-northeast-1<br/>.pooler.supabase.com:6543<br/>SSLMODE=require"]
    end

    L2 --> D1
    S1 -.->|"future deploy"| D1
    EXAMPLE -.->|"new joiner fills this,<br/>never sees real values"| LOCAL

    style LOCAL fill:#7f1d1d,stroke:#f87171,color:#fff
    style EXAMPLE fill:#14532d,stroke:#4ade80,color:#fff
    style CI fill:#1e3a8a,stroke:#60a5fa,color:#fff
    style DB fill:#134e4a,stroke:#2dd4bf,color:#fff
```

**Rules mula sa PDF (na.apply na sa setup natin):**

1. ✅ Read once at start-up, validate, **never default silently**
2. ✅ `.env.example` committed with empty values
3. ✅ Secrets sa CI secret store — **hindi** sa repo
4. ✅ Secrets **masked in logs** — huwag ever `echo $SECRET`

---

## 1.5 — Mga Access Role

```mermaid
flowchart LR
    subgraph R["👥 Roles"]
        DEV["Dev"]
        QA["QA"]
        DO["DevOps"]
        PM["PM / BA / SA"]
    end

    subgraph A["Access"]
        DEPLOY["🚀 Deploy"]
        LOGS["📋 Logs"]
        RESET["♻️ Data reset"]
        DBACC["🗄️ Database"]
    end

    DEV --> DEPLOY
    DEV --> LOGS
    QA --> DEPLOY
    QA --> LOGS
    QA --> RESET
    DO --> DEPLOY
    DO --> LOGS
    DO --> RESET
    DO --> DBACC
    PM --> LOGS

    style DO fill:#4a1e6e,stroke:#c084fc,color:#fff
    style PM fill:#1e4a8a,stroke:#60a5fa,color:#fff
```

> **Ngayon:** Single-developer setup — si `jerowm098` ang may **full** access (owner).

---

## 1.6 — Backup & Monitoring (target state mula sa PDF)

```mermaid
flowchart LR
    DB[("☁️ Supabase Postgres")]
    SNAP["💾 Daily snapshot<br/>Retention: 7 days"]
    SCRATCH[("🧪 Scratch DB<br/>restore test")]
    HEALTH["❤️ GET /health<br/>200 + commit sha + DB check"]
    ALERT["🚨 Error-rate alert<br/>(Sprint 3 target)"]
    UPTIME["⏱️ External uptime check<br/>every 5 min"]

    DB --> SNAP --> SCRATCH
    DB --> HEALTH
    HEALTH --> ALERT
    UPTIME -.->|"monitor"| HEALTH

    style SNAP fill:#78350f,stroke:#fbbf24,color:#fff
    style ALERT fill:#7f1d1d,stroke:#f87171,color:#fff
    style HEALTH fill:#14532d,stroke:#4ade80,color:#fff
```

> ⚠️ **Hindi pa implemented:** Walang `/health` route, walang automated backup, walang alerting. Ito ang *target state* na dapat gawin bago mag-Production.

---

## 1.7 — Buod: Pipeline ng Isang Release

```mermaid
flowchart LR
    CODE["💻 Code<br/>committed"] --> BR["🌿 push to develop"]
    BR --> PIPE["⚙️ Pipeline<br/>Stages 1–6"]
    PIPE --> MIG["🗄️ Migrate<br/>(reviewed file)"]
    MIG --> DEP["🚀 Deploy → Test"]
    DEP --> SMOKE["🩺 Smoke check<br/>GET /health = 200 + sha"]
    SMOKE --> PR["📋 PR: develop → main"]
    PR --> APPR["✅ Approve<br/>DevOps + PM<br/><i>ONLY manual gate</i>"]
    APPR --> TAG["🏷️ Tag v0.n.0"]
    TAG --> STG["🧪 Staging"]
    STG --> PROD["🏭 Production"]
    PROD --> VER["✅ Verify<br/>3 main journeys by hand"]
    VER --> ANN["📣 Announce<br/>Teams channel"]

    style APPR fill:#7f1d1d,stroke:#f87171,color:#fff
    style SMOKE fill:#14532d,stroke:#4ade80,color:#fff
    style PROD fill:#4a1e6e,stroke:#c084fc,color:#fff
```

**Pre-deploy checklist** (mula sa PDF):
- [ ] Pipeline green sa `develop`, kasama ang authorization stage
- [ ] Migrations reviewed ng iba (hindi ng author) + backward compatible
- [ ] Walang bagong config value na missing sa target environment
- [ ] **Walang secret na na-add sa repository**, kahit sa reverted commit
- [ ] Demo dry run done
- [ ] May available na tao sa loob ng susunod na oras
