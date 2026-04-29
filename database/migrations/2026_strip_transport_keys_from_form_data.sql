-- Strip transport-only keys that previously leaked into cds_submissions.form_data
-- via the client-resubmit code path in api.php. Without this cleanup, any admin
-- view, re-render, or future email built from an old row could still reveal the
-- raw edit-link `token` and a stringified `formData` blob.
--
-- Keys removed:
--   step, token, formData, submissionUid, supporting_meta, supporting_remove
--
-- Safe to re-run; JSON_REMOVE on a missing path is a no-op.
--
-- Requires MySQL 5.7+ / MariaDB 10.2+ (JSON functions). If form_data is stored
-- as TEXT instead of JSON, the cast in the WHERE clause prevents errors on
-- rows that aren't valid JSON.

UPDATE cds_submissions
SET form_data = JSON_REMOVE(
        CAST(form_data AS JSON),
        '$.step',
        '$.token',
        '$.formData',
        '$.submissionUid',
        '$.supporting_meta',
        '$.supporting_remove'
    )
WHERE form_data IS NOT NULL
  AND JSON_VALID(form_data) = 1
  AND (
        JSON_CONTAINS_PATH(CAST(form_data AS JSON), 'one',
            '$.step', '$.token', '$.formData',
            '$.submissionUid', '$.supporting_meta', '$.supporting_remove')
  );
