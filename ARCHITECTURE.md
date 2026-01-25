# ARQUITECTURA DEL PLUGIN - Engine Editorial "El Día de Michoacán"

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    KZMCITO-IA-SEO.PHP (Main Plugin File)                │
│                                                                           │
│  • Singleton Pattern                                                     │
│  • Autoloader                                                            │
│  • Hooks Registration                                                    │
│  • Database Table Creation                                               │
│  • Meta Box Registration                                                 │
│  • AJAX Handlers                                                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐         ┌─────────────────────┐
        │   ADMIN UI          │         │   FRONTEND          │
        │  (Admin Panel)      │         │  (Public Display)   │
        └─────────────────────┘         └─────────────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
┌──────────────┐      ┌──────────────────┐
│ Settings     │      │ Prompt Editor    │
│ Page         │      │ Language Manager │
└──────────────┘      └──────────────────┘
                                │
                                ▼
                    ┌─────────────────────┐
                    │   CORE ORCHESTRATOR │
                    │   (class-core.php)  │
                    └─────────────────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PHASE 1  │    │ PHASE 2  │    │ PHASE 3  │
        │ Analysis │───▶│Transform │───▶│ SEO Inj. │
        └──────────┘    └──────────┘    └──────────┘
                                                │
                                                ▼
                                        ┌──────────┐
                                        │ PHASE 4  │
                                        │Translate │
                                        └──────────┘

════════════════════════════════════════════════════════════════════════════

PHASE 1: ANALYSIS (class-core.php)
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  INPUT: Post Content + Title + Categories                               │
│                                                                          │
│  PROCESS:                                                                │
│  ├─ Detect Category (michoacan, educacion, etc.)                        │
│  ├─ Load Prompts (Global + Category) ◄─── Prompt Manager               │
│  ├─ Extract Keywords                                                     │
│  ├─ Extract Entities                                                     │
│  ├─ Count Words                                                          │
│  ├─ Count Headings (H2, H3, H4)                                         │
│  ├─ Determine if needs expansion                                        │
│  ├─ Determine if needs TOC                                              │
│  └─ Determine if needs FAQ                                              │
│                                                                          │
│  OUTPUT: Analysis Data Object                                           │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

PHASE 2: TRANSFORMATION (class-content-processor.php)
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  INPUT: Post Content + Analysis Data                                    │
│                                                                          │
│  PROCESS:                                                                │
│  1. Clean Content                                                        │
│     ├─ Remove MSO tags (Office garbage)                                 │
│     ├─ Remove inline styles                                             │
│     ├─ Remove empty spans/divs                                          │
│     └─ Sanitize with wp_kses_post                                       │
│                                                                          │
│  2. Expand Content (if < 850 words)                                     │
│     ├─ Build expansion prompt                                           │
│     ├─ Call AI API ◄─── API Client                                     │
│     └─ Merge expanded content                                           │
│                                                                          │
│  3. Enhance Headings                                                     │
│     ├─ Analyze paragraphs                                               │
│     ├─ Generate H2-H4 from context                                      │
│     └─ Insert headings strategically                                    │
│                                                                          │
│  4. Insert TOC (if ≥ 2 H2)                                              │
│     ├─ Extract all H2 headings                                          │
│     ├─ Generate anchor IDs                                              │
│     ├─ Build TOC HTML                                                   │
│     └─ Insert after first paragraph                                     │
│                                                                          │
│  5. Insert FAQ (if applicable)                                          │
│     ├─ Generate FAQ with AI ◄─── API Client                            │
│     ├─ Build FAQ HTML                                                   │
│     ├─ Generate Schema JSON-LD                                          │
│     └─ Append to content                                                │
│                                                                          │
│  OUTPUT: Transformed Content                                            │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

PHASE 3: SEO INJECTION (class-seo-injector.php)
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  INPUT: Post ID + Post Object + Analysis Data                           │
│                                                                          │
│  PROCESS:                                                                │
│  1. Generate SEO Metadata                                               │
│     ├─ Focus Keyword (from top keywords)                                │
│     ├─ Meta Description (155-160 chars)                                 │
│     ├─ SEO Title (≤60 chars)                                            │
│     └─ Additional Keywords                                              │
│                                                                          │
│  2. Inject RankMath Meta                                                │
│     ├─ rank_math_focus_keyword                                          │
│     ├─ rank_math_description                                            │
│     ├─ rank_math_title                                                  │
│     └─ rank_math_focus_keywords                                         │
│                                                                          │
│  3. Configure Advanced RankMath                                         │
│     ├─ Robots meta (index, follow, etc.)                                │
│     ├─ Rich Snippets (Article/NewsArticle)                              │
│     ├─ Open Graph settings                                              │
│     ├─ Twitter Card settings                                            │
│     ├─ Canonical URL                                                    │
│     └─ Pillar Content (if ≥1500 words)                                  │
│                                                                          │
│  4. Optimize Slug                                                        │
│     ├─ Generate from title                                              │
│     ├─ Limit to 50 chars                                                │
│     └─ Ensure uniqueness                                                │
│                                                                          │
│  OUTPUT: SEO Score 100/100                                              │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

PHASE 4: LOCALIZATION (class-translation-manager.php)
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  INPUT: Post ID + Post Object + Analysis Data                           │
│                                                                          │
│  PROCESS:                                                                │
│  1. Get Active Languages                                                │
│     └─ Query kzmcito_languages table                                    │
│                                                                          │
│  2. For Each Language:                                                  │
│     ├─ Check cache first                                                │
│     ├─ If not cached:                                                   │
│     │  ├─ Translate content ◄─── API Client                            │
│     │  ├─ Translate title ◄─── API Client                              │
│     │  └─ Translate meta description ◄─── API Client                   │
│     └─ Store translation data                                           │
│                                                                          │
│  3. Save to Cache                                                        │
│     ├─ kzmcito_translations_cache (all translations)                    │
│     ├─ _kzmcito_available_languages (language codes)                    │
│     └─ _kzmcito_last_translated (timestamp)                             │
│                                                                          │
│  OUTPUT: Multilingual Content Cache                                     │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

SUPPORTING COMPONENTS
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  PROMPT MANAGER (class-prompt-manager.php)                              │
│  ├─ Load Global Prompt (system-prompt-global.md)                        │
│  ├─ Load Category Prompt (01-michoacan.md, etc.)                        │
│  ├─ Merge Prompts (hierarchical)                                        │
│  ├─ Replace Variables ({{site_name}}, etc.)                             │
│  ├─ Backup System (automatic on save)                                   │
│  └─ Fallback Mode (if category not found)                               │
│                                                                          │
│  API CLIENT (class-api-client.php)                                      │
│  ├─ Claude Integration (Anthropic)                                      │
│  ├─ Gemini Integration (Google)                                         │
│  ├─ GPT Integration (OpenAI)                                            │
│  ├─ Error Handling                                                      │
│  └─ Connection Testing                                                  │
│                                                                          │
│  META FIELDS (class-meta-fields.php)                                    │
│  ├─ Processing Fields (_kzmcito_last_processed, etc.)                   │
│  ├─ Analysis Fields (_kzmcito_keywords, etc.)                           │
│  ├─ SEO Fields (_kzmcito_seo_score, etc.)                               │
│  ├─ Translation Fields (kzmcito_translations_cache, etc.)               │
│  └─ REST API Support                                                    │
│                                                                          │
│  ADMIN UI (class-admin-ui.php)                                          │
│  ├─ Settings Page (API keys, configuration)                             │
│  ├─ Prompt Editor (visual editor with sidebar)                          │
│  ├─ Language Manager (CRUD operations)                                  │
│  └─ Statistics Dashboard (processed posts, translations)                │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

DATA FLOW
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  User Saves Post                                                         │
│         │                                                                │
│         ▼                                                                │
│  wp_insert_post_data (hook)                                             │
│         │                                                                │
│         ▼                                                                │
│  Core::process_content()                                                │
│         │                                                                │
│         ├─▶ Phase 1: Analysis                                           │
│         │   └─▶ Analysis Data                                           │
│         │                                                                │
│         ├─▶ Phase 2: Transformation                                     │
│         │   └─▶ Transformed Content                                     │
│         │                                                                │
│         └─▶ Mark for Phase 3 & 4                                        │
│                                                                          │
│  Post Saved to Database                                                 │
│         │                                                                │
│         ▼                                                                │
│  save_post (hook)                                                       │
│         │                                                                │
│         ▼                                                                │
│  Core::process_translations()                                           │
│         │                                                                │
│         ├─▶ Phase 3: SEO Injection                                      │
│         │   └─▶ RankMath Meta Updated                                   │
│         │                                                                │
│         └─▶ Phase 4: Localization                                       │
│             └─▶ Translations Cached                                     │
│                                                                          │
│  Processing Complete ✓                                                  │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

CATEGORY DETECTION & FALLBACK
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  Post Categories                                                         │
│         │                                                                │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "michoacan" │──YES──▶ Load 01-michoacan.md                       │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "educacion" │──YES──▶ Load 02-educacion.md                       │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "entreteni" │──YES──▶ Load 03-entretenimiento.md                 │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "justicia"  │──YES──▶ Load 04-justicia.md                        │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "salud"     │──YES──▶ Load 05-salud.md                           │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────┐                                                    │
│  │ Has "seguridad" │──YES──▶ Load 06-seguridad.md                       │
│  └─────────────────┘                                                    │
│         │ NO                                                             │
│         ▼                                                                │
│  ┌─────────────────────────────────────┐                                │
│  │ FALLBACK MODE                       │                                │
│  │ Use only system-prompt-global.md    │                                │
│  │ Log event for analysis              │                                │
│  └─────────────────────────────────────┘                                │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════

SECURITY LAYERS
┌────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  1. INPUT SANITIZATION                                                  │
│     ├─ wp_kses_post() for HTML content                                  │
│     ├─ sanitize_text_field() for text                                   │
│     ├─ absint() for integers                                            │
│     └─ esc_url() for URLs                                               │
│                                                                          │
│  2. OUTPUT ESCAPING                                                     │
│     ├─ esc_html() for HTML output                                       │
│     ├─ esc_attr() for attributes                                        │
│     └─ wp_json_encode() for JSON                                        │
│                                                                          │
│  3. PERMISSION CHECKS                                                   │
│     ├─ current_user_can('edit_posts')                                   │
│     ├─ current_user_can('manage_options')                               │
│     └─ check_admin_referer() for nonces                                 │
│                                                                          │
│  4. NONCE VERIFICATION                                                  │
│     ├─ wp_nonce_field() in forms                                        │
│     ├─ check_ajax_referer() in AJAX                                     │
│     └─ wp_verify_nonce() for validation                                 │
│                                                                          │
│  5. DATABASE SECURITY                                                   │
│     ├─ $wpdb->prepare() for queries                                     │
│     ├─ Parameterized queries                                            │
│     └─ Type casting (%s, %d, etc.)                                      │
│                                                                          │
└────────────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════════
