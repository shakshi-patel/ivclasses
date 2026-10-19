-- Fix for phpMyAdmin / server import error #1901 on MariaDB:
--   Function or expression 'ifnull(...)' cannot be used in the GENERATED ALWAYS AS clause
--
-- In your .sql dump, find the `batches` CREATE TABLE `deleted_at_key` line that uses IFNULL
-- and replace it with the CASE expression below (or run this AFTER a partial import failed
-- only if the table was created without that column — usually you edit the dump then re-import).

-- REPLACE THIS (old, breaks MariaDB):
--   `deleted_at_key` datetime GENERATED ALWAYS AS (ifnull(`deleted_at`,_utf8mb4'1970-01-01 00:00:00')) STORED

-- WITH THIS:
--   `deleted_at_key` datetime GENERATED ALWAYS AS (case when `deleted_at` is null then cast('1970-01-01 00:00:00' as datetime) else `deleted_at` end) STORED

-- If `batches` already exists WITHOUT deleted_at_key, you can add it:
-- ALTER TABLE `batches`
--   ADD COLUMN `deleted_at_key` datetime
--     GENERATED ALWAYS AS (
--       CASE
--         WHEN `deleted_at` IS NULL THEN CAST('1970-01-01 00:00:00' AS DATETIME)
--         ELSE `deleted_at`
--       END
--     ) STORED,
--   ADD UNIQUE INDEX `batches_branch_name_unique` (`branch_id`, `name`, `deleted_at_key`);
