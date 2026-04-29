-- Migration: Supporting documents column
-- Adds an optional JSON column to cds_submissions for client-supplied supporting
-- documents (utility bills, bank statements, TIN certs, etc.). These files are
-- stored on the server only and are NOT pushed to CSE — keeping them in their
-- own column avoids any risk of the CSE upload loop ever picking them up.
-- Run: mysql -u user -p database_name < database/migrations/2026_supporting_documents.sql

ALTER TABLE `cds_submissions`
    ADD COLUMN `supporting_documents` JSON DEFAULT NULL AFTER `image_paths`;
