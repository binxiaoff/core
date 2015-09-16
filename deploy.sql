ALTER TABLE attachment ADD COLUMN position INT(11) UNSIGNED NOT NULL DEFAULT 1 AFTER path;
ALTER TABLE attachment DROP COLUMN position;
DESC attachment;