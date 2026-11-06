# PMNow Demo Fixtures — QA-only, additive

This feature supplies synthetic, clearly labelled stories, editorial records, utilities and reader scenarios for **local/testing/staging** evaluation. It is not a substitute for verified news or real utility information.

## Run locally

```bash
php tools/demo-fixtures.php --apply
php tools/demo-fixtures.php --apply     # idempotent: no duplicate fixtures
php tools/demo-fixtures.php --purge     # remove only records from this fixture suite
```

## Run in DigiOps staging

The application must explicitly run with `APP_ENV=staging` **and** an opt-in environment flag. The CLI additionally requires `--allow-staging`. If PMNow's current deployment is set to `production` (the safe DigiOps default), this feature will refuse to run. Do not alter a live production site's environment solely to permit demonstration data.

```bash
APP_ENV=staging PMNOW_DEMO_DATA_ALLOWED=true php private_html/pmnow/tools/demo-fixtures.php --apply --allow-staging
APP_ENV=staging PMNOW_DEMO_DATA_ALLOWED=true php private_html/pmnow/tools/demo-fixtures.php --purge --allow-staging
```

**Safety invariants:** the fixture command never calls external sources, never creates admin credentials, never publishes real alerts, never schedules runnable jobs, never deletes untagged records, and never invokes `tools/seed.php`. It does not change an existing fixture on rerun, so editorial test changes remain intact. Files are stored in the existing JSON collections with `demo_fixture_suite=pmnow-safe-demo-v1` and `metadata.demo=true`. Purging checks the fixture suite marker before deleting.

The `public/health.php` and `public/ready.php` endpoints remain unaffected. Running this command is not part of automatic deployment.

## Coverage

| Area | Seeded scenarios |
|---|---|
| Home/Explore | 18 synthetic published items covering 11 categories and 12 Pune/PCMC areas |
| Story rendering | Articles, photo gallery, developing timeline, live timeline, reels |
| Editorial | Ready, review-required, duplicate, rejected, archived, Hindi and Marathi drafts |
| Pune Utility | 8 synthetic entities, 8 active sample advisories, 1 resolved advisory |
| Reader journeys | 2 synthetic reader records, preferences, bookmarks, follows, in-app notifications |
| Jobs | One completed and one failed **non-executable** historical sample |

Synthetic users are **data fixtures**, not interactive logged-in demo accounts: their identities are not assigned to a browser session. Use them for database/repository and analytics checks; for personalized browser testing, select areas/topics in My Pune using a fresh browser session. The suite does not add images to the repository; it reuses existing bundled demo media.

## QA checklist

1. Verify nonproduction safety by confirming production returns exit code 3 and creates no records.
2. Run `--apply` twice and check the second output has no newly inserted records.
3. Confirm the home feed and category pages show `[DEMO]` headlines.
4. Open Gallery, Live, Developing and Watch and inspect layout/timeline behavior.
5. Check Newsroom's editorial inbox for unpublished drafts.
6. Check Utility board and its detail pages for `DEMO · Unverified synthetic data` rather than a real verification claim.
7. Exercise My Pune preferences, bookmarks and follows in a normal test browser session.
8. Run `--purge`, confirm fixture records disappear, and verify pre-existing records remain unchanged.

**Important:** Some existing original seed stories predate this suite and carry other demo markers. They are intentionally **not** removed by this suite's purge command.
