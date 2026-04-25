-- Migration: drop submission_edit_tokens.uses_remaining
--
-- The 3-use limit was redundant: once the client successfully resubmits, the
-- row flips to status=submitted_to_cse and the token endpoint refuses it on
-- the status check alone. Expiry (expires_at, 3 days) is the only guard we
-- still need.
--
-- Idempotent: if the column has already been dropped, migrate.php logs the
-- "Unknown column" error and continues.

ALTER TABLE `submission_edit_tokens` DROP COLUMN `uses_remaining`;
