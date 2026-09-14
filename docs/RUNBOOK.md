# Release and Rollback Runbook

This is the release approach I would use when changing an existing WordPress platform with multiple custom plugins.

## Before a release

1. Create a database backup and verify the artifact exists.
2. Record the current application/plugin versions or Git commit.
3. Deploy the candidate changes to staging.
4. Run the critical-path smoke tests from `TEST_PLAN.md`.
5. Review PHP/application logs for new warnings or errors.
6. Confirm any schema change is backward-compatible or has a tested rollback path.

## Production release

1. Use a maintenance window for schema-affecting changes.
2. Deploy code from a tagged/known commit rather than editing production files directly.
3. Run a short smoke test:
   - login;
   - course access;
   - lesson listing;
   - progress write;
   - admin reports;
   - WooCommerce test path where practical.
4. Watch error rate and audit events immediately after deployment.

## Rollback trigger examples

Rollback when a release causes:

- fatal PHP errors;
- broken authentication/access control;
- failed checkout/enrollment flow;
- unexpected database write failures;
- sustained increase in 5xx responses.

## Rollback procedure

1. Restore the previous known-good code revision.
2. If a non-backward-compatible schema migration occurred, execute the tested reverse migration or restore the pre-release database backup.
3. Purge caches if applicable.
4. Re-run the smoke test.
5. Record the incident and root cause before retrying the release.
