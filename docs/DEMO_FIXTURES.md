# PMNow Demo Data — Web management

PMNow provides a protected **Admin → Demo Data** page for loading and removing synthetic QA records. No shell or Bash access is needed for everyday use.

## Open the page

- After deploying this branch/PR: `https://stage.digiti.in/pmnow/admin/demo`
- Sign in as an authorized **super_admin**.
- Select **Load demo data**. The page shows live counts for stories, editorial drafts, utility advisories, and demo readers.
- Test the Reader Feed, Watch, Editorial Inbox, and Pune Utility using the links on the page.
- To clean up, enter **REMOVE DEMO** in the confirmation box and select **Remove demo data**.

The web manager is intentionally hidden and its routes return 404 when disabled. The UI is not a substitute for server-side access control: every API request also checks active admin identity, super_admin role, CSRF, and the environment flag.

## Enable on staging (once)

In the PMNow **private application environment** (not GitHub and not the publicly accessible website), configure:

```ini
APP_ENV=staging
PMNOW_DEMO_DATA_ALLOWED=true
```

Do this only on an isolated staging deployment that is access-controlled and excluded from search indexing. The existing DigiOps deployment defaults to `APP_ENV=production`; in that mode all demo mutations are blocked even if an administrator guesses the URL. Do not enable demo mode on real production data. Keep the existing `APP_URL`, `APP_BASE_PATH`, HTTPS and session settings valid for the environment.

The web controls are available only when **both** the environment and the opt-in flag pass the checks. No admin can toggle these deployment security settings from the browser.

## What's included

| Module | Synthetic records |
|---|---|
| News and discovery | 18 stories: articles, gallery, developing, live and reels |
| Editorial | 7 drafts across review/ready/rejected/archived/duplicate states; Hindi and Marathi samples |
| Utility | 8 kinds, 9 advisories (including resolved), clear DEMO/unverified labels |
| Reader scenarios | 2 synthetic readers with preferences, follows, bookmarks and notifications |
| Job dashboard | 2 historical, non-executable jobs (completed/failed) |
| Taxonomy | 11 categories and 12 Pune/PCMC areas |

The synthetic reader records are examples for data/analytics testing, not accounts that can log in as staff. A test visitor can use the My Pune interface to create their own session preferences.

## Data safeguards

- Web access: logged-in super_admin only, CSRF protected, explicit confirmation for changes.
- Environment: local/testing/staging with `PMNOW_DEMO_DATA_ALLOWED=true`; production is always blocked.
- Additive: existing records are preserved; repeated loading reuses marked fixtures without changing them.
- Cleanup: removes only records tagged with `demo_fixture_suite=pmnow-safe-demo-v1`.
- All test headlines carry `[DEMO]`; utility items identify synthetic and unverified status.
- The tool does not use source APIs, external AI services, real push channels or runnable background jobs.
- Shared service and an operation lock make browser and CLI changes use the same logic.
- Successful actions are written to the private `demo-management` audit event stream.

**Known limitation:** The existing M1–M12 sample seed in `tools/seed.php` is a separate legacy demo dataset. Its records are deliberately not removed by this newer fixture manager. The dataset is also displayed in the nonproduction public feed, so staging itself must be access-controlled and noindexed.

## Engineering / CI

The legacy CLI tool `tools/demo-fixtures.php` is retained for automated smoke tests. Browser QA verifies load, duplicate prevention, cleanup and preservation of existing feed data. CI also checks the production write guard and fixture data contracts.

No automatic fixture load occurs during normal DigiOps deployment.
